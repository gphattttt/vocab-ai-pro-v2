import json
import re
import requests
from flask import Flask, request, jsonify
from flask_cors import CORS

app = Flask(__name__)
CORS(app)

# --- CẤU HÌNH HỆ THỐNG ---
# Đảm bảo thay bằng API Key chính xác của bạn (sk-or-v1-...)
OPENROUTER_API_KEY = "MY_SECRET_KEY" 
MODEL_NAME = "google/gemini-2.5-flash-lite"

def universal_json_repair(text):
    """
    Bộ lọc vạn năng bóc tách JSON.
    Tìm khối { ... } và tự động cân bằng dấu ngoặc nhọn nếu AI trả về dư thừa.
    """
    if not text: return None
    try:
        start_idx = text.find('{')
        end_idx = text.rfind('}')
        
        if start_idx == -1 or end_idx == -1:
            return None
        
        json_str = text[start_idx:end_idx + 1].strip()
        
        # Xử lý lỗi dư dấu ngoặc đóng (}) ở cuối chuỗi
        open_count = json_str.count('{')
        close_count = json_str.count('}')
        while close_count > open_count and json_str.endswith('}'):
            json_str = json_str[:-1].strip()
            close_count -= 1
            
        # Loại bỏ ký tự điều khiển gây lỗi parse
        json_str = re.sub(r'[\x00-\x1F\x7F]', '', json_str)
        return json_str
    except:
        return None

def ask_gemini_with_retry(prompt, retries=2):
    """
    Cơ chế gọi AI thông minh: Tự động thử lại nếu AI trả về sai định dạng JSON.
    """
    for i in range(retries):
        try:
            headers = {
                "Authorization": f"Bearer {OPENROUTER_API_KEY.strip()}",
                "Content-Type": "application/json",
                "HTTP-Referer": "http://localhost",
                "X-Title": "Vocab AI Pro"
            }
            
            payload = {
                "model": MODEL_NAME,
                "messages": [{"role": "user", "content": prompt}],
                "response_format": { "type": "json_object" },
                "temperature": 0.3 # Giữ AI hoạt động ổn định, ít biến động
            }

            response = requests.post(
                url="https://openrouter.ai/api/v1/chat/completions",
                headers=headers,
                data=json.dumps(payload),
                timeout=15
            )
            
            if response.status_code == 200:
                raw_content = response.json()['choices'][0]['message']['content']
                clean_data = universal_json_repair(raw_content)
                
                if clean_data:
                    try:
                        return json.loads(clean_data)
                    except:
                        print(f"Lần thử {i+1}: JSON Parse thất bại, đang thử lại...")
                        continue 
            else:
                print(f"Lỗi OpenRouter ({response.status_code}): {response.text}")
                
        except Exception as e:
            print(f"Lỗi kết nối lượt {i+1}: {str(e)}")
            continue
            
    return None

# --- CÁC ENDPOINT API ---

@app.route('/health', methods=['GET'])
def health_check():
    return jsonify({"status": "online"}), 200

@app.route('/get-vocab', methods=['GET'])
def get_vocab():
    word = request.args.get('word')
    if not word: return jsonify({"error": "No word"}), 400
    
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
    if not word: return jsonify({"error": "No word"}), 400

    prompt = f"""
    Task: Create a natural English sentence for the word '{word}'.
    Rule: Replace '{word}' or its variations (plural/tense) with '_____'.
    
    Example 1: Word 'apple' -> {{"sentence": "I eat an _____ every morning."}}
    Example 2: Word 'mice' -> {{"sentence": "The cat is chasing the _____."}}
    Example 3: Word 'necessary' -> {{"sentence": "Sleep is _____ for health."}}
    
    Now generate for Word: '{word}'
    """
    
    result = ask_gemini_with_retry(prompt)
    
    if result and 'sentence' in result:
        sentence = result['sentence']
        # Hậu kiểm bằng Regex: Đảm bảo luôn có ô trống '_____'
        if '_____' not in sentence:
            pattern_str = rf'\b({re.escape(word)}'
            if word.lower() == 'mice': pattern_str += '|mouse'
            elif word.lower() == 'mouse': pattern_str += '|mice'
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

    # Prompt Few-shot: Ép AI trả về Array of Objects để tránh lỗi undefined
    prompt = f"""
    Analyze this essay: "{essay}"
    1. Coaching: Summary, 2 pros, 2 cons (Written in VIETNAMESE).
    2. Vocabulary: Extract advanced words matching "{req}".
    
    CRITICAL: The "words" array MUST contain objects, NOT strings.
    EXPECTED FORMAT:
    {{
      "coaching": "...",
      "words": [
        {{
          "word": "example",
          "ipa": "/.../",
          "definition": "nghĩa",
          "example": "ví dụ"
        }}
      ]
    }}
    """
    result = ask_gemini_with_retry(prompt)
    return jsonify(result) if result else (jsonify({"error": "AI Engine Error"}), 500)

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000, debug=False)
