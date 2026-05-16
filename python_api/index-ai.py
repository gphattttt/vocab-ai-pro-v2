import json
import os
import re

import requests
from dotenv import load_dotenv
from flask import Flask, jsonify, request
from flask_cors import CORS

# Load env vars from /python_api/.env
load_dotenv()

app = Flask(__name__)
CORS(app)

# --- CẤU HÌNH HỆ THỐNG ---
OPENROUTER_API_KEY = os.getenv("OPENROUTER_API_KEY")
MODEL_NAME = "openai/gpt-oss-120b"


def universal_json_repair(text):
    if not text:
        return None
    try:
        start_idx = text.find('{')
        end_idx = text.rfind('}')
        if start_idx == -1 or end_idx == -1:
            return None
        json_str = text[start_idx:end_idx + 1].strip()
        open_count = json_str.count('{')
        close_count = json_str.count('}')
        while close_count > open_count and json_str.endswith('}'):
            json_str = json_str[:-1].strip()
            close_count -= 1
        json_str = re.sub(r'[\x00-\x1F\x7F]', '', json_str)
        return json_str
    except Exception:
        return None


def ask_gemini_with_retry(prompt, retries=2):
    api_key = (OPENROUTER_API_KEY or "").strip()
    if not api_key:
        return None

    for i in range(retries):
        try:
            headers = {
                "Authorization": f"Bearer {api_key}",
                "Content-Type": "application/json",
                "HTTP-Referer": "https://study4ever.site",
                "X-Title": "Vocab AI Pro",
            }
            payload = {
                "model": MODEL_NAME,
                "messages": [{"role": "user", "content": prompt}],
                "response_format": {"type": "json_object"},
                "temperature": 0.3,
            }
            response = requests.post(
                url="https://openrouter.ai/api/v1/chat/completions",
                headers=headers,
                data=json.dumps(payload),
                timeout=15,
            )
            if response.status_code == 200:
                raw_content = response.json()['choices'][0]['message']['content']
                clean_data = universal_json_repair(raw_content)
                if clean_data:
                    try:
                        return json.loads(clean_data)
                    except Exception:
                        print(f"Lần thử {i + 1}: JSON Parse thất bại, đang thử lại...")
                        continue
            else:
                print(f"Lỗi OpenRouter ({response.status_code}): {response.text}")
        except Exception as e:
            print(f"Lỗi kết nối lượt {i + 1}: {str(e)}")
            continue
    return None


# --- ENDPOINTS ---
@app.route('/health', methods=['GET'])
def health_check():
    return jsonify({"status": "online"}), 200


@app.route('/get-vocab', methods=['GET'])
def get_vocab():
    word = request.args.get('word')
    if not word:
        return jsonify({"error": "No word"}), 400

    prompt = f"""
    Task: Analyze the English word '{word}'.
    Return ONLY a JSON object with this exact structure:
    {{
      "word": "{word}",
      "ipa": "/.../",
      "level": "A1-C2",
      "word_form": "noun/verb/adj...",
      "definition_en": "...",
      "definition_vi": "...",
      "example_sentence": "...",
      "synonyms": "word1, word2",
      "antonyms": "word1, word2"
    }}
    """
    result = ask_gemini_with_retry(prompt)
    return jsonify(result) if result else (jsonify({"error": "AI Engine Error"}), 500)


@app.route('/quiz-sentence', methods=['GET'])
def quiz_sentence():
    word = request.args.get('word')
    if not word:
        return jsonify({"error": "No word"}), 400

    prompt = f"""
    Task: Create a natural English sentence for the word '{word}'.
    Rule: Replace '{word}' or its variations (plural/tense) with '_____'.
    """
    result = ask_gemini_with_retry(prompt)
    if result and 'sentence' in result:
        sentence = result['sentence']
        if '_____' not in sentence:
            pattern_str = rf'\b({re.escape(word)}'
            if word.lower() == 'mice':
                pattern_str += '|mouse'
            elif word.lower() == 'mouse':
                pattern_str += '|mice'
            pattern_str += r')\b'
            sentence = re.sub(pattern_str, '_____', sentence, flags=re.IGNORECASE)
            result['sentence'] = sentence
        return jsonify(result)
    return jsonify({"error": "AI Engine Error"}), 500


@app.route('/extract-vocab', methods=['POST'])
def extract_vocab():
    data = request.json
    essay = data.get('essay', '')
    req = data.get('requirement', 'C1 level')

    prompt = f"""
    Analyze this essay: "{essay}"

    You are a WRITING COACH. Focus ONLY on writing quality:
    - grammar, clarity, structure, coherence, vocabulary
    - do NOT judge feelings or topic
    - Output in Vietnamese

    Provide:
    1) A short summary of writing quality
    2) 2 pros
    3) 2 cons

    Then extract advanced vocabulary matching "{req}".
    Create TEMPORARY categories (topics) for the words.

    Return ONLY JSON:
    {{
      "coaching": "...",
      "categories": ["Category A", "Category B"],
      "words": [
        {{
          "word": "example",
          "ipa": "/.../",
          "level": "A1-C2",
          "definition_en": "...",
          "definition_vi": "...",
          "example_sentence": "...",
          "category": "Category A"
        }}
      ]
    }}
    """
    result = ask_gemini_with_retry(prompt)
    return jsonify(result) if result else (jsonify({"error": "AI Engine Error"}), 500)


if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000, debug=False)
