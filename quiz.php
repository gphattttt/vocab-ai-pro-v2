<?php 
session_start(); 
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); } 
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Trắc nghiệm thông minh | Vocab AI Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
    <style>
        :root {
            --primary: #1e293b; --accent: #10b981; --danger: #ef4444;
            --bg: #f8fafc; --border: #e2e8f0; --text-muted: #64748b;
            --spring: cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        body { font-family: 'Be Vietnam Pro', sans-serif; background: var(--bg); margin: 0; padding: 40px 20px; color: var(--primary); }
        .container { max-width: 700px; margin: 0 auto; }

        /* --- STATUS BADGE --- */
        .status-badge {
            display: inline-flex; align-items: center; gap: 8px; padding: 8px 16px; 
            border-radius: 20px; font-size: 0.8rem; font-weight: 700; margin-bottom: 25px;
            background: white; border: 1px solid var(--border); box-shadow: 0 4px 10px rgba(0,0,0,0.02);
        }
        .status-dot { width: 10px; height: 10px; border-radius: 50%; background: #94a3b8; transition: 0.3s; }
        .status-online { background: var(--accent); box-shadow: 0 0 12px rgba(16, 185, 129, 0.4); }
        .status-offline { background: var(--danger); box-shadow: 0 0 12px rgba(239, 68, 68, 0.4); }

        .card { 
            background: white; padding: 40px; border-radius: 35px; 
            border: 1px solid var(--border); box-shadow: 0 20px 50px rgba(0,0,0,0.04);
            position: relative; animation: slideUp 0.6s var(--spring);
        }

        /* --- PROGRESS BAR --- */
        .progress-container { width: 100%; height: 8px; background: #f1f5f9; border-radius: 10px; margin-bottom: 40px; overflow: hidden; }
        #progressBar { height: 100%; width: 0%; background: var(--accent); transition: 0.6s var(--spring); }

        .quiz-title { font-size: 0.85rem; font-weight: 800; text-transform: uppercase; color: var(--text-muted); letter-spacing: 1px; margin-bottom: 10px; }
        
        .sentence-display { font-size: 1.4rem; line-height: 1.6; font-weight: 600; margin-bottom: 25px; color: #334155; }
        .sentence-display b { color: var(--accent); border-bottom: 3px dashed var(--accent); padding: 0 5px; }

        /* --- HINT SYSTEM --- */
        #hintContainer { margin-bottom: 30px; text-align: center; }
        #wordHint { 
            background: #eff6ff; color: #3b82f6; padding: 12px 24px; border-radius: 18px; 
            font-weight: 700; font-size: 0.95rem; border: 1px dashed #bfdbfe;
            display: inline-block;
        }

        /* --- MULTIPLE CHOICE GRID --- */
        .options-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .option-btn {
            background: white;
            border: 2px solid var(--border);
            padding: 20px;
            border-radius: 20px;
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--primary);
            cursor: pointer;
            transition: all 0.3s var(--spring);
            text-align: center;
        }
        .option-btn:hover:not(:disabled) {
            border-color: var(--accent);
            background: #f0fdf4;
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(16, 185, 129, 0.1);
        }
        .option-btn:active:not(:disabled) { transform: translateY(0); }
        .option-btn:disabled { cursor: not-allowed; opacity: 0.7; }

        /* Trạng thái đúng/sai cho các nút */
        .option-btn.opt-correct { background: var(--accent); border-color: var(--accent); color: white; box-shadow: 0 10px 20px rgba(16,185,129,0.3); }
        .option-btn.opt-wrong { background: var(--danger); border-color: var(--danger); color: white; box-shadow: 0 10px 20px rgba(239,68,68,0.3); }

        .btn-next {
            width: 100%; padding: 20px; border-radius: 18px; border: none;
            background: var(--accent); color: white; font-weight: 800; font-size: 1.1rem;
            cursor: pointer; transition: 0.3s var(--spring);
        }
        .btn-next:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(16,185,129,0.2); }

        /* --- FEEDBACK & RESULTS --- */
        .feedback { display: none; margin-top: 10px; margin-bottom: 20px; padding: 20px; border-radius: 20px; font-weight: 700; text-align: center; animation: slideUp 0.4s var(--spring); }
        .correct { background: #ecfdf5; color: #059669; border: 1px solid #dcfce7; }
        .wrong { background: #fef2f2; color: #dc2626; border: 1px solid #fee2e2; }

        #resultScreen { display: none; text-align: center; }
        .score-circle { width: 140px; height: 140px; border-radius: 50%; border: 10px solid var(--accent); margin: 0 auto 30px auto; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; font-weight: 800; color: var(--primary); }

        /* --- DEBUGGER --- */
        #debuggerArea {
            display: none; background: #0f172a; color: #38bdf8; padding: 25px;
            border-radius: 20px; margin-top: 30px; font-family: 'Courier New', monospace; font-size: 0.8rem;
        }
        .debug-label { color: var(--danger); font-weight: bold; margin-bottom: 8px; display: block; }

        @keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        
        @media (max-width: 600px) { 
            .card { padding: 30px 20px; } 
            .sentence-display { font-size: 1.15rem; } 
            .options-grid { grid-template-columns: 1fr; } /* Chuyển thành 1 cột trên điện thoại */
        }
    </style>
</head>
<body>

    <?php include 'nav.php'; ?>

    <div class="container">
        <div class="status-badge">
            <div class="status-dot" id="statusDot"></div>
            <span id="statusText">Đang kiểm tra AI Engine...</span>
        </div>

        <div class="card" id="quizCard">
            <div id="loader" style="text-align:center; padding: 60px 0;">
                <p style="font-weight:700; color:var(--text-muted); animation: pulse 1.5s infinite;">Gemini đang soạn câu trắc nghiệm cho bạn...</p>
            </div>

            <div id="quizContent" style="display:none;">
                <div class="progress-container"><div id="progressBar"></div></div>
                <div class="quiz-title" id="questionCounter">Câu hỏi 1 / 10</div>
                
                <div class="sentence-display" id="sentenceText"></div>

                <div id="hintContainer">
                    <span id="wordHint">Gợi ý: Đang tải...</span>
                </div>

                <!-- Lưới đáp án trắc nghiệm -->
                <div id="inputArea" class="options-grid"></div>

                <div id="feedbackBox" class="feedback"></div>
                
                <button class="btn-next" id="nextBtn" style="display:none;" onclick="loadNextQuestion()">Tiếp tục →</button>
            </div>

            <div id="resultScreen">
                <div class="score-circle" id="finalScore">0%</div>
                <h2 style="font-weight:800; letter-spacing: -1px;">Thử thách hoàn tất!</h2>
                <p id="resultMsg" style="color:var(--text-muted); font-weight:500; margin-bottom:35px;"></p>
                
                <div id="xpAlert" style="display:none; background:#ecfdf5; color:#10b981; padding:20px; border-radius:20px; margin-bottom:30px; font-weight:800;">
                    ✨ Tuyệt vời! Bạn nhận được +50 XP ⚡
                </div>

                <button class="btn-next" onclick="window.location.href='study.php'" style="background: var(--primary);">Quay lại Trung tâm học tập</button>
            </div>
        </div>

        <div id="debuggerArea">
            <span class="debug-label">⚠️ DEBUGGER:</span>
            <div id="debugContent"></div>
            <pre id="debugRaw" style="background:#1e293b; padding:15px; border-radius:10px; margin-top:10px; white-space: pre-wrap; font-size: 0.75rem; color: #94a3b8;"></pre>
        </div>
    </div>

<script>
    const API_URL = '/api';
    let allWords = []; // Lưu toàn bộ kho từ để làm đáp án nhiễu
    let quizWords = [];
    let currentIdx = 0;
    let score = 0;
    const TOTAL_Q = 10;

    // Danh sách từ vựng dự phòng nếu kho từ của user có quá ít từ (< 4 từ)
    const fallbackVocab = ['resilient', 'ubiquitous', 'serendipity', 'ephemeral', 'eloquent', 'aesthetic', 'diligent', 'meticulous', 'inevitable', 'ambiguous'];

    async function checkAI() {
        const dot = document.getElementById('statusDot');
        const text = document.getElementById('statusText');
        try {
            const res = await fetch(`${API_URL}/health`);
            if (res.ok) {
                dot.className = "status-dot status-online";
                text.innerText = "AI Engine: Sẵn sàng";
                loadQuizData();
            } else { throw new Error(); }
        } catch (e) {
            dot.className = "status-dot status-offline";
            text.innerText = "AI Engine: Mất kết nối";
            showDebugger({message: "Lỗi kết nối tới Flask Backend", raw: e});
        }
    }
    checkAI();

    async function loadQuizData() {
        try {
            const res = await fetch('fetch_board.php');
            allWords = await res.json();
            
            // Trộn và lấy ngẫu nhiên câu hỏi
            quizWords = [...allWords].sort(() => 0.5 - Math.random()).slice(0, TOTAL_Q);
            
            if (quizWords.length === 0) {
                document.getElementById('loader').innerHTML = "<p>Kho từ vựng trống. Hãy thêm từ trước khi làm trắc nghiệm!</p>";
                return;
            }
            loadNextQuestion();
        } catch (e) {
            showDebugger({message: "Không thể lấy từ vựng từ Database", raw: e});
        }
    }

    async function loadNextQuestion() {
        if (currentIdx >= quizWords.length) {
            showResults();
            return;
        }

        const wordObj = quizWords[currentIdx];
        
        // Reset UI cho câu hỏi mới
        document.getElementById('loader').style.display = "block";
        document.getElementById('quizContent').style.display = "none";
        document.getElementById('feedbackBox').style.display = "none";
        document.getElementById('nextBtn').style.display = "none";
        
        try {
            const res = await fetch(`${API_URL}/quiz-sentence?word=${encodeURIComponent(wordObj.word)}`);
            const data = await res.json();
            if (!res.ok) throw data;

            let sentence = data.sentence;

            // Đục lỗ nếu AI chưa đục
            const regex = new RegExp(wordObj.word, 'gi'); 
            if (!sentence.includes('_____')) {
                sentence = sentence.replace(regex, '_____');
            }

            // Gợi ý nghĩa tiếng Việt
            const formText = wordObj.word_form ? `(${wordObj.word_form})` : '';
            document.getElementById('wordHint').innerText = `Gợi ý: ${wordObj.definition_vi} ${formText}`;

            // Hiển thị câu hỏi
            document.getElementById('sentenceText').innerHTML = sentence.replace('_____', '<b>_____</b>');
            document.getElementById('questionCounter').innerText = `Câu hỏi ${currentIdx + 1} / ${quizWords.length}`;
            document.getElementById('progressBar').style.width = `${(currentIdx / quizWords.length) * 100}%`;
            
            // --- TẠO ĐÁP ÁN TRẮC NGHIỆM ---
            // Lọc bỏ đáp án đúng ra khỏi danh sách nhiễu
            let distractors = allWords.filter(w => w.word.toLowerCase() !== wordObj.word.toLowerCase());
            // Lấy ngẫu nhiên 3 từ gây nhiễu
            distractors = distractors.sort(() => 0.5 - Math.random()).slice(0, 3);
            
            let options = [{ word: wordObj.word, isCorrect: true }];
            distractors.forEach(d => options.push({ word: d.word, isCorrect: false }));
            
            // Nếu người dùng có ít hơn 4 từ vựng trong kho, bù bằng fallbackVocab
            while(options.length < 4) {
                let f = fallbackVocab[Math.floor(Math.random() * fallbackVocab.length)];
                if (!options.find(o => o.word.toLowerCase() === f.toLowerCase())) {
                    options.push({ word: f, isCorrect: false });
                }
            }

            // Trộn ngẫu nhiên 4 đáp án (A,B,C,D)
            options = options.sort(() => 0.5 - Math.random());

            // Render nút bấm
            let optionsHtml = '';
            options.forEach(opt => {
                optionsHtml += `<button class="option-btn" onclick="checkChoice(this, ${opt.isCorrect}, '${wordObj.word}')">${opt.word}</button>`;
            });
            document.getElementById('inputArea').innerHTML = optionsHtml;

            document.getElementById('loader').style.display = "none";
            document.getElementById('quizContent').style.display = "block";

        } catch (e) {
            showDebugger({message: `Lỗi AI khi tạo câu hỏi cho từ "${wordObj.word}"`, raw: e});
        }
    }

    // Xử lý khi click vào đáp án
    function checkChoice(buttonElement, isCorrect, correctWord) {
        // Vô hiệu hóa tất cả các nút ngay lập tức để tránh bấm 2 lần
        const allButtons = document.querySelectorAll('.option-btn');
        allButtons.forEach(btn => btn.disabled = true);

        const feedback = document.getElementById('feedbackBox');

        if (isCorrect) {
            score++;
            buttonElement.classList.add('opt-correct');
            feedback.className = "feedback correct";
            feedback.innerText = "Chính xác! ✨";
        } else {
            buttonElement.classList.add('opt-wrong');
            feedback.className = "feedback wrong";
            feedback.innerText = `Chưa đúng. Đáp án chính xác là: ${correctWord}`;
            
            // Đánh dấu xanh cho nút đúng để người dùng biết
            allButtons.forEach(btn => {
                if (btn.innerText.toLowerCase() === correctWord.toLowerCase()) {
                    btn.classList.add('opt-correct');
                }
            });
        }

        feedback.style.display = "block";
        document.getElementById('nextBtn').style.display = "block";

        // Cập nhật SRS vào Database
        const srsData = new FormData();
        srsData.append('id', quizWords[currentIdx].id);
        srsData.append('is_correct', isCorrect);
        fetch('update_srs.php', { method: 'POST', body: srsData });

        currentIdx++;
    }

    function showResults() {
        document.getElementById('quizContent').style.display = "none";
        document.getElementById('resultScreen').style.display = "block";
        document.getElementById('progressBar').style.width = "100%";

        const percent = Math.round((score / quizWords.length) * 100);
        document.getElementById('finalScore').innerText = percent + "%";
        
        if (percent === 100) {
            document.getElementById('resultMsg').innerText = "Thật không thể tin nổi! Bạn đã thuộc lòng toàn bộ.";
            document.getElementById('xpAlert').style.display = "block";
            
            const fd = new FormData();
            fd.append('amount', 50);
            fetch('reward_xp.php', { method: 'POST', body: fd });
            
            confetti({ particleCount: 150, spread: 70, origin: { y: 0.6 }, colors: ['#10b981', '#1e293b'] });
        } else if (percent >= 70) {
            document.getElementById('resultMsg').innerText = "Làm tốt lắm! Bạn đang tiến bộ rất nhanh.";
        } else {
            document.getElementById('resultMsg').innerText = "Đừng nản lòng, luyện tập thêm để ghi nhớ sâu hơn nhé!";
        }
    }

    function showDebugger(err) {
        const area = document.getElementById('debuggerArea');
        area.style.display = "block";
        document.getElementById('debugContent').innerText = err.message;
        document.getElementById('debugRaw').innerText = typeof err.raw === 'object' ? JSON.stringify(err.raw, null, 2) : err.raw;
    }
</script>
</body>
</html>
