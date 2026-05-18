from flask import Flask, request, jsonify
from flask_cors import CORS
import os
import json
import re
import html
import requests
from dotenv import load_dotenv

# Tải biến môi trường từ file .env
load_dotenv()

app = Flask(__name__)
CORS(app)

# Lấy OpenRouter API Key từ .env
# Tuyệt đối không hardcode API key trong source code.
OPENROUTER_API_KEY = os.getenv("OPENROUTER_API_KEY")


# ==========================================================
# HÀM TIỆN ÍCH: GỌI OPENROUTER
# ==========================================================
def call_openrouter(prompt):
    """
    Gọi OpenRouter để sinh đề, chấm IELTS, hoặc tạo feedback/roadmap.

    Lưu ý:
    - Hàm này chỉ nên dùng cho phần cần AI thật sự.
    - Với General test, điểm số phải được chấm bằng code, không để AI tự đoán.
    """
    if not OPENROUTER_API_KEY:
        raise RuntimeError("Thiếu OPENROUTER_API_KEY trong file .env")

    headers = {
        "Authorization": f"Bearer {OPENROUTER_API_KEY}",
        "Content-Type": "application/json",
        "HTTP-Referer": "https://study4ever.site",
        "X-Title": "Vocab AI Pro"
    }

    payload = {
        "model": "google/gemini-2.5-flash-lite",
        "messages": [
            {
                "role": "system",
                "content": (
                    "You are a backend API. Respond ONLY with valid, raw JSON. "
                    "Do not include markdown formatting like ```json or any conversational text."
                )
            },
            {"role": "user", "content": prompt}
        ]
    }

    response = requests.post(
        "https://openrouter.ai/api/v1/chat/completions",
        headers=headers,
        json=payload,
        timeout=90
    )
    response.raise_for_status()

    content = response.json()["choices"][0]["message"]["content"].strip()

    # Dọn dẹp markdown nếu AI lỡ bọc JSON bằng ```json
    if content.startswith("```json"):
        content = content[7:]
    elif content.startswith("```"):
        content = content[3:]

    if content.endswith("```"):
        content = content[:-3]

    return json.loads(content.strip())


# ==========================================================
# HÀM TIỆN ÍCH: CHUẨN HOÁ ĐÁP ÁN
# ==========================================================
def normalize_answer(value):
    """
    Chuẩn hoá đáp án để so sánh công bằng hơn.

    Ví dụ:
    - " Beautiful " -> "beautiful"
    - "A" -> "a"
    - nhiều khoảng trắng -> một khoảng trắng
    """
    if value is None:
        return ""

    value = str(value).strip().lower()
    value = re.sub(r"\s+", " ", value)

    return value


def normalize_mcq_answer(value):
    """
    Chuẩn hoá đáp án trắc nghiệm.
    Thường đáp án là A/B/C/D.
    """
    if value is None:
        return ""

    return str(value).strip().upper()


def get_user_answer(answers, question, index):
    """
    Lấy đáp án user theo nhiều kiểu key để tránh lệch format.

    Frontend hiện có thể gửi:
    - answers["1"] nếu input name là ans_1
    - answers["0"] nếu input name là ans_0
    - answers[question_id] nếu dùng id thật của câu hỏi

    Hàm này thử theo thứ tự:
    1. id thật của câu hỏi
    2. index 0-based
    3. index 1-based
    """
    question_id = question.get("id")

    possible_keys = []

    if question_id is not None:
        possible_keys.append(str(question_id))

    possible_keys.append(str(index))
    possible_keys.append(str(index + 1))

    for key in possible_keys:
        if key in answers:
            return answers.get(key)

    return ""


def cefr_from_score(correct_count, total_questions):
    """
    Quy đổi số câu đúng sang level CEFR đơn giản.
    Có thể chỉnh ngưỡng sau nếu bạn muốn khó/dễ hơn.
    """
    if total_questions <= 0:
        return "A1"

    ratio = correct_count / total_questions

    if ratio < 0.30:
        return "A1"
    if ratio < 0.50:
        return "A2"
    if ratio < 0.70:
        return "B1"
    if ratio < 0.85:
        return "B2"
    if ratio < 0.95:
        return "C1"

    return "C2"


def fallback_general_feedback(correct_count, total_questions, details):
    """
    Feedback dự phòng nếu AI lỗi khi tạo feedback/roadmap.
    """
    wrong_items = [item for item in details if not item["is_correct"]]

    if correct_count == total_questions:
        feedback = (
            "Bạn làm rất tốt trong bài kiểm tra này. Kết quả cho thấy nền tảng từ vựng "
            "và ngữ pháp của bạn khá vững."
        )
    elif correct_count == 0:
        feedback = (
            "Bạn chưa trả lời đúng câu nào trong bài kiểm tra này. Nên bắt đầu lại với "
            "các điểm nền tảng như từ loại, cấu trúc câu cơ bản và cách dùng từ trong ngữ cảnh."
        )
    else:
        feedback = (
            f"Bạn trả lời đúng {correct_count}/{total_questions} câu. "
            "Hãy xem lại các lỗi sai, đặc biệt là phần word form và lựa chọn đáp án theo ngữ cảnh."
        )

    roadmap = """
    <ul>
        <li>Ôn lại từ loại cơ bản: noun, verb, adjective, adverb.</li>
        <li>Luyện word form mỗi ngày với các họ từ thường gặp.</li>
        <li>Làm lại các câu sai và ghi chú vì sao đáp án đúng hợp lý hơn.</li>
        <li>Học từ vựng theo ngữ cảnh thay vì học từng từ riêng lẻ.</li>
    </ul>
    """

    if wrong_items:
        roadmap += "<ul>"
        for item in wrong_items[:5]:
            roadmap += (
                "<li>"
                f"Xem lại câu {html.escape(str(item['id']))}: "
                f"đáp án của bạn là <strong>{html.escape(str(item['user_answer']))}</strong>, "
                f"đáp án đúng là <strong>{html.escape(str(item['correct_answer']))}</strong>."
                "</li>"
            )
        roadmap += "</ul>"

    return feedback, roadmap


def score_general_test(questions, answers):
    """
    Chấm General test bằng code.

    Đây là phần sửa lỗi quan trọng:
    - Không để AI tự đoán score.
    - So sánh user answer với correct_answer trong từng câu.
    - Trả về score thật.
    """
    details = []
    correct_count = 0
    total_questions = len(questions)

    for index, question in enumerate(questions):
        question_type = question.get("type", "")
        correct_answer = question.get("correct_answer", "")
        user_answer = get_user_answer(answers, question, index)

        # Với MCQ, so sánh A/B/C/D dạng uppercase.
        if question_type == "mcq":
            user_norm = normalize_mcq_answer(user_answer)
            correct_norm = normalize_mcq_answer(correct_answer)
        else:
            # Với word_form, so sánh lowercase và bỏ khoảng trắng thừa.
            user_norm = normalize_answer(user_answer)
            correct_norm = normalize_answer(correct_answer)

        is_correct = bool(user_norm) and user_norm == correct_norm

        if is_correct:
            correct_count += 1

        details.append({
            "id": question.get("id", index + 1),
            "type": question_type,
            "level": question.get("level", ""),
            "question": question.get("question", ""),
            "user_answer": user_answer,
            "correct_answer": correct_answer,
            "is_correct": is_correct
        })

        # Log để debug trên pm2 logs.
        print(
            f"DEBUG SCORE Q{index + 1}: "
            f"type={question_type}, user={user_answer}, correct={correct_answer}, is_correct={is_correct}",
            flush=True
        )

    return correct_count, total_questions, details


def build_general_feedback_with_ai(correct_count, total_questions, level, details):
    """
    Dùng AI để viết feedback và roadmap sau khi điểm đã được chấm bằng code.

    AI không quyết định score nữa.
    AI chỉ viết nhận xét dựa trên kết quả đã chấm.
    """
    safe_details = []

    for item in details:
        safe_details.append({
            "id": item["id"],
            "type": item["type"],
            "level": item["level"],
            "question": item["question"],
            "user_answer": item["user_answer"],
            "correct_answer": item["correct_answer"],
            "is_correct": item["is_correct"]
        })

    prompt = f"""
    You are an expert English learning advisor.

    The student's General Placement Test has already been graded by deterministic backend code.
    Do NOT change the score or level.

    Score: {correct_count}/{total_questions}
    CEFR Level: {level}

    Detailed results:
    {json.dumps(safe_details, ensure_ascii=False)}

    Provide a strict JSON response containing:
    1. "feedback": A short paragraph in Vietnamese explaining strengths and weaknesses.
    2. "roadmap": A detailed, bulleted study plan in HTML format with <ul><li> tags, written in Vietnamese.

    Output strictly in this JSON format:
    {{ "feedback": "...", "roadmap": "..." }}
    """

    result = call_openrouter(prompt)

    feedback = result.get("feedback", "")
    roadmap = result.get("roadmap", "")

    if not feedback or not roadmap:
        raise ValueError("AI feedback response missing feedback or roadmap")

    return feedback, roadmap


# ==========================================================
# API 1: SINH ĐỀ THI
# ==========================================================
@app.route("/api/generate-test", methods=["POST"])
def generate_test():
    data = request.json or {}
    test_type = data.get("type", "general")

    if test_type == "general":
        prompt = """
        You are an expert English language examiner. Generate a JSON response containing an English placement test.
        The test must have exactly 20 questions in total.

        Section 1: Multiple Choice (10 questions: 2 Easy, 3 Medium, 5 Hard).
        Format:
        {"id": 1, "type": "mcq", "level": "Easy", "question": "...", "options": ["A", "B", "C", "D"], "correct_answer": "B"}

        Section 2: Word Form (10 questions: 5 Easy, 2 Medium, 3 Hard).
        Provide a sentence with a missing word and root word in parentheses.
        Format:
        {"id": 11, "type": "word_form", "level": "Easy", "question": "She is a very _____ (beauty) girl.", "root_word": "beauty", "correct_answer": "beautiful"}

        Important:
        - IDs must be unique from 1 to 20.
        - correct_answer must always exist.
        - MCQ correct_answer must be one of "A", "B", "C", "D".
        - Word form correct_answer must be the exact expected word.

        Output strictly in this JSON format:
        { "test_type": "general", "questions": [ <list of 20 objects> ] }
        """

    elif test_type == "ielts":
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

        # Validate nhẹ cho general test để tránh AI trả thiếu câu.
        if test_type == "general":
            questions = result.get("questions", [])

            if not isinstance(questions, list) or len(questions) != 20:
                return jsonify({
                    "error": "AI tạo đề không đúng 20 câu. Vui lòng thử lại."
                }), 500

            for index, question in enumerate(questions):
                if "correct_answer" not in question:
                    return jsonify({
                        "error": f"Câu {index + 1} thiếu correct_answer. Vui lòng thử lại."
                    }), 500

        return jsonify(result)

    except Exception as e:
        print(f"generate_test error: {e}", flush=True)
        return jsonify({"error": str(e)}), 500


# ==========================================================
# API 2: CHẤM ĐIỂM & LỘ TRÌNH
# ==========================================================
@app.route("/api/evaluate-test", methods=["POST"])
def evaluate_test():
    data = request.json or {}
    test_type = data.get("type", "general")
    answers = data.get("answers", {})

    if test_type == "general":
        """
        General test phải được chấm bằng code.

        Backend cần nhận thêm:
        {
            "type": "general",
            "answers": {...},
            "questions": [...]
        }

        questions phải là đề gốc có correct_answer.
        Ở bước sau, take_a_test.php sẽ lưu đề trong session và gửi questions sang đây.
        """
        questions = data.get("questions", [])

        if not isinstance(questions, list) or len(questions) == 0:
            return jsonify({
                "error": (
                    "Thiếu questions để chấm General test. "
                    "Cần cập nhật take_a_test.php để gửi đề gốc từ session sang backend."
                )
            }), 400

        try:
            correct_count, total_questions, details = score_general_test(questions, answers)
            level = cefr_from_score(correct_count, total_questions)

            try:
                feedback, roadmap = build_general_feedback_with_ai(
                    correct_count,
                    total_questions,
                    level,
                    details
                )
            except Exception as ai_error:
                # Nếu AI lỗi, vẫn trả score đúng bằng code.
                print(f"AI feedback error: {ai_error}", flush=True)
                feedback, roadmap = fallback_general_feedback(
                    correct_count,
                    total_questions,
                    details
                )

            return jsonify({
                "score": f"{correct_count}/{total_questions}",
                "level": level,
                "feedback": feedback,
                "roadmap": roadmap,
                "details": details
            })

        except Exception as e:
            print(f"evaluate general error: {e}", flush=True)
            return jsonify({"error": str(e)}), 500

    elif test_type == "ielts":
        """
        IELTS Writing vẫn cần AI chấm vì là bài luận.
        """
        essay = answers.get("essay", "")

        if not essay.strip():
            return jsonify({
                "error": "Bài IELTS Writing đang rỗng."
            }), 400

        prompt = f"""
        You are a highly qualified IELTS examiner. Evaluate the following IELTS Writing Task 2 essay.

        Essay:
        {essay}

        Evaluate strictly based on the 4 IELTS criteria:
        - Task Response
        - Coherence and Cohesion
        - Lexical Resource
        - Grammatical Range and Accuracy

        Provide a strict JSON response containing:
        1. "score": Overall Band Score, e.g. "6.5".
        2. "level": "IELTS " + Band Score.
        3. "feedback": Detailed feedback in Vietnamese on the 4 criteria and major grammar/vocabulary corrections.
        4. "roadmap": An actionable roadmap in HTML format with <ul><li> tags to improve the writing score by at least 0.5 - 1.0 band.

        Output strictly in this JSON format:
        {{ "score": "...", "level": "...", "feedback": "...", "roadmap": "..." }}
        """

        try:
            result = call_openrouter(prompt)
            return jsonify(result)
        except Exception as e:
            print(f"evaluate IELTS error: {e}", flush=True)
            return jsonify({"error": str(e)}), 500

    else:
        return jsonify({"error": "Invalid test type"}), 400


if __name__ == "__main__":
    # Chạy ở port 5001
    app.run(host="0.0.0.0", port=5001, debug=True)
