from flask import Flask, request, jsonify
from flask_cors import CORS
import os
import json
import requests
from dotenv import load_dotenv

# Tải biến môi trường từ file .env
load_dotenv()

app = Flask(__name__)
CORS(app)

# Lấy OpenRouter API Key (Tuyệt đối không hardcode để bảo mật)
OPENROUTER_API_KEY = os.getenv("OPENROUTER_API_KEY")

# --- HÀM TIỆN ÍCH: GỌI OPENROUTER ---
def call_openrouter(prompt):
    headers = {
        "Authorization": f"Bearer {OPENROUTER_API_KEY}",
        "Content-Type": "application/json",
        "HTTP-Referer": "https://study4ever.site",
        "X-Title": "Vocab AI Pro"
    }
    
    payload = {
        "model": "google/gemini-2.5-flash", 
        "messages": [
            {"role": "system", "content": "You are a backend API. Respond ONLY with valid, raw JSON. Do not include markdown formatting like ```json or any conversational text."},
            {"role": "user", "content": prompt}
        ]
    }

    response = requests.post("https://openrouter.ai/api/v1/chat/completions", headers=headers, json=payload)
    response.raise_for_status()
    
    content = response.json()['choices'][0]['message']['content'].strip()
    
    # Dọn dẹp markdown nếu AI lỡ sinh ra
    if content.startswith("```json"): content = content[7:]
    elif content.startswith("```"): content = content[3:]
    if content.endswith("```"): content = content[:-3]
        
    return json.loads(content.strip())


# ==========================================
# API 1: SINH ĐỀ THI (GENERATE TEST)
# ==========================================
@app.route('/api/generate-test', methods=['POST'])
def generate_test():
    data = request.json
    test_type = data.get('type', 'general')

    if test_type == 'general':
        prompt = """
        You are an expert English language examiner. Generate a JSON response containing an English placement test.
        The test must have exactly 20 questions in total:
        
        Section 1: Multiple Choice (10 questions: 2 Easy, 3 Medium, 5 Hard).
        Format: {"id": 1, "type": "mcq", "level": "Easy", "question": "...", "options": ["A", "B", "C", "D"], "correct_answer": "B"}
        
        Section 2: Word Form (10 questions: 5 Easy, 2 Medium, 3 Hard). Provide a sentence with a missing word and root word in parentheses.
        Format: {"id": 11, "type": "word_form", "level": "Easy", "question": "She is a very _____ (beauty) girl.", "root_word": "beauty", "correct_answer": "beautiful"}
        
        Output strictly in this JSON format:
        { "test_type": "general", "questions": [ <list of 20 objects> ] }
        """
    elif test_type == 'ielts':
        prompt = """
        You are an IELTS examiner. Generate ONE random, challenging IELTS Writing Task 2 topic.
        Output strictly in this JSON format:
        {
            "test_type": "ielts",
            "topic": "...",
            "instructions": "You should spend about 40 minutes on this task. Write at least 250 words.",
            "criteria": ["Task Response", "Coherence and Cohesion", "Lexical Resource", "Grammatical Range and Accuracy"]
        }
        """
    else:
        return jsonify({"error": "Invalid test type"}), 400

    try:
        result = call_openrouter(prompt)
        return jsonify(result)
    except Exception as e:
        return jsonify({"error": str(e)}), 500


# ==========================================
# API 2: CHẤM ĐIỂM & LỘ TRÌNH (EVALUATE TEST)
# ==========================================
@app.route('/api/evaluate-test', methods=['POST'])
def evaluate_test():
    data = request.json
    test_type = data.get('type', 'general')
    answers = data.get('answers', {})
    
    if test_type == 'general':
        prompt = f"""
        You are an expert English examiner. The user took a 20-question placement test.
        Here are the user's answers (JSON format mapping question IDs or names to their answers): {json.dumps(answers)}
        
        Evaluate these answers logically. Provide a strict JSON response containing:
        1. "score": Total correct answers out of 20 (e.g., "15/20").
        2. "level": Estimated CEFR level based on the score (e.g., "A2", "B1", "B2", "C1").
        3. "feedback": A short paragraph evaluating strengths and weaknesses based on their mistakes.
        4. "roadmap": A detailed, bulleted study plan (HTML format with <ul><li> tags) to help them reach the next level.
        
        Output strictly in this JSON format:
        {{ "score": "...", "level": "...", "feedback": "...", "roadmap": "..." }}
        """
    elif test_type == 'ielts':
        essay = answers.get('essay', '')
        prompt = f"""
        You are a highly qualified IELTS examiner. Evaluate the following IELTS Writing Task 2 essay.
        Essay: "{essay}"
        
        Evaluate strictly based on the 4 IELTS criteria (TR, CC, LR, GRA).
        Provide a strict JSON response containing:
        1. "score": Overall Band Score (e.g., "6.5").
        2. "level": "IELTS " + Band Score.
        3. "feedback": Detailed feedback on the 4 criteria and major grammar/vocabulary corrections.
        4. "roadmap": An actionable roadmap (HTML format with <ul><li> tags) to improve their writing score by at least 0.5 - 1.0 band.
        
        Output strictly in this JSON format:
        {{ "score": "...", "level": "...", "feedback": "...", "roadmap": "..." }}
        """
    else:
        return jsonify({"error": "Invalid test type"}), 400

    try:
        result = call_openrouter(prompt)
        return jsonify(result)
    except Exception as e:
        return jsonify({"error": str(e)}), 500


if __name__ == '__main__':
    # Chạy ở port 5001
    app.run(host='0.0.0.0', port=5001, debug=True)
