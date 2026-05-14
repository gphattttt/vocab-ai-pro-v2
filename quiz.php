<?php 
session_start(); 
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); } 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Quiz | Vocab AI Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
    <style>
        :root {
            --primary: #1e293b; --accent: #10b981; --danger: #ef4444;
            --bg: #f8fafc; --border: #e2e8f0; --text-muted: #64748b;
            --spring: cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        body { font-family: 'Inter', sans-serif; background: var(--bg); margin: 0; padding: 40px; color: var(--primary); overflow-x: hidden; }

        /* --- ENTRANCE ANIMATIONS --- */
        @keyframes slideUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        .animate-in { animation: slideUp 0.6s var(--spring) forwards; }

        /* --- QUIZ UI --- */
        .container { max-width: 600px; margin: 60px auto 0 auto; }
        .quiz-card { background: white; padding: 40px; border-radius: 30px; box-shadow: 0 15px 40px rgba(0,0,0,0.04); border: 1px solid var(--border); position: relative; }
        
        .progress-container { width: 100%; background: #e2e8f0; height: 10px; border-radius: 20px; margin-bottom: 30px; overflow: hidden; }
        .progress-bar { height: 100%; background: linear-gradient(90deg, #10b981, #34d399); width: 0%; transition: width 0.4s var(--spring); }

        .question-box { min-height: 140px; text-align: center; margin-bottom: 30px; transition: 0.3s; }
        .sentence-text { font-size: 1.4rem; line-height: 1.6; font-weight: 600; color: #334155; }
        .sentence-text span { color: var(--accent); border-bottom: 3px dashed var(--accent); padding: 0 5px; background: #f0fdf4; border-radius: 4px; }

        .options { display: grid; gap: 15px; }
        .option { 
            padding: 20px; border: 2px solid var(--border); border-radius: 20px; 
            cursor: pointer; transition: 0.2s var(--spring); font-weight: 700; text-align: center; font-size: 1.1rem;
            background: white;
        }
        .option:hover { transform: scale(1.02); border-color: var(--primary); background: #f8fafc; }
        .option.correct { background: var(--accent) !important; color: white; border-color: var(--accent); transform: scale(1.02); }
        .option.wrong { background: var(--danger) !important; color: white; border-color: var(--danger); animation: shake 0.4s; }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-8px); }
            75% { transform: translateX(8px); }
        }

        #nextBtn { 
            margin-top: 30px; width: 100%; padding: 20px; border-radius: 18px; border: none; 
            background: var(--primary); color: white; cursor: pointer; font-weight: 800; font-size: 1rem;
            display: none; transition: 0.3s var(--spring);
        }
        #nextBtn:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(0,0,0,0.1); }

        /* --- RESULTS --- */
        #resultScreen { display: none; text-align: center; animation: slideUp 0.8s var(--spring); }
        .score-circle { 
            width: 160px; height: 160px; border-radius: 50%; border: 12px solid var(--accent); 
            display: flex; align-items: center; justify-content: center; 
            font-size: 3rem; font-weight: 800; margin: 30px auto; color: var(--primary);
            background: #f0fdf4;
        }
    </style>
</head>
<body>

    <?php include 'nav.php'; ?>

    <div class="container animate-in">
        <div class="quiz-card">
            <div style="display: flex; justify-content: space-between; font-size: 0.75rem; margin-bottom: 15px; font-weight: 800; text-transform: uppercase; color: var(--text-muted);">
                <span id="qCountText">Question 1 / 10</span>
                <span id="scoreText">Accuracy: 0%</span>
            </div>
            <div class="progress-container">
                <div class="progress-bar" id="progressBar"></div>
            </div>

            <div id="quizContent">
                <div class="question-box">
                    <div id="loadingMsg" style="color: var(--text-muted); font-weight: 600;">Gemini is crafting a question... 🧠</div>
                    <div class="sentence-text" id="sentenceText"></div>
                </div>
                <div class="options" id="optionsBox"></div>
                <button id="nextBtn" onclick="nextQuestion()">Next Question →</button>
            </div>

            <div id="resultScreen">
                <h2 style="font-size: 2rem; font-weight: 800;">Session Complete!</h2>
                <div class="score-circle" id="finalScore">0%</div>
                <p id="resultComment" style="color: var(--text-muted); margin-bottom: 30px; font-weight: 600;"></p>
                <div id="xpAlert" style="display:none; color: var(--accent); font-weight: 900; margin-bottom: 30px; font-size: 1.2rem;">🏆 PERFECT! +50 XP REWARDED</div>
                <button onclick="location.reload()" class="option" style="width: 100%; background: var(--primary); color: white;">Restart Session</button>
                <a href="index.php" style="display: block; margin-top: 20px; color: var(--text-muted); text-decoration: none; font-weight: 700;">Back to Dashboard</a>
            </div>
        </div>
    </div>

<script>
    let quizWords = [];
    let currentIdx = 0;
    let score = 0;
    const TOTAL_Q = 10;
    let currentWord = null;

    async function initQuiz() {
        const res = await fetch('fetch_board.php');
        quizWords = await res.json();
        
        if (quizWords.length < 4) {
            alert("Your vault is too small! Add at least 4 words first.");
            window.location.href = "index.php";
            return;
        }
        nextQuestion();
    }

    async function nextQuestion() {
        if (currentIdx >= Math.min(TOTAL_Q, quizWords.length)) {
            showResults();
            return;
        }

        document.getElementById('nextBtn').style.display = "none";
        document.getElementById('sentenceText').innerHTML = "";
        document.getElementById('optionsBox').innerHTML = "";
        document.getElementById('loadingMsg').style.display = "block";

        currentWord = quizWords[currentIdx];
        currentIdx++;
        
        document.getElementById('qCountText').innerText = `Question ${currentIdx} / ${Math.min(TOTAL_Q, quizWords.length)}`;
        document.getElementById('progressBar').style.width = (currentIdx / Math.min(TOTAL_Q, quizWords.length) * 100) + "%";

        try {
            // UPDATED: Fetch AI-generated gap-fill sentence via Nginx Proxy
            const res = await fetch(`/api/quiz-sentence?word=${encodeURIComponent(currentWord.word)}`);
            const data = await res.json();
            
            document.getElementById('loadingMsg').style.display = "none";
            document.getElementById('sentenceText').innerHTML = `"${data.sentence.replace('_____', '<span>_____</span>')}"`;

            // Prepare multiple choice options
            let choices = [currentWord.word];
            while (choices.length < 4) {
                let r = quizWords[Math.floor(Math.random() * quizWords.length)].word;
                if (!choices.includes(r)) choices.push(r);
            }
            choices.sort(() => Math.random() - 0.5);

            choices.forEach((c, index) => {
                const b = document.createElement('div');
                b.className = "option";
                b.style.animation = `slideUp 0.4s var(--spring) forwards ${0.1 * index}s`;
                b.style.opacity = "0";
                b.innerText = c;
                b.onclick = () => checkAnswer(b, c);
                document.getElementById('optionsBox').appendChild(b);
            });
        } catch(e) { 
            console.error("Quiz Error", e);
            document.getElementById('loadingMsg').innerText = "AI is offline. Using backup question...";
            document.getElementById('sentenceText').innerText = `What is the meaning of: ${currentWord.definition_en}`;
        }
    }

    async function checkAnswer(btn, selected) {
        const options = document.querySelectorAll('.option');
        options.forEach(o => o.onclick = null); 

        const is_correct = (selected === currentWord.word);

        if (is_correct) {
            btn.classList.add('correct');
            score++;
        } else {
            btn.classList.add('wrong');
            options.forEach(o => { if(o.innerText === currentWord.word) o.classList.add('correct'); });
        }

        // --- BACKGROUND SRS UPDATE ---
        const srsData = new FormData();
        srsData.append('id', currentWord.id);
        srsData.append('is_correct', is_correct);
        fetch('update_srs.php', { method: 'POST', body: srsData });

        document.getElementById('scoreText').innerText = `Accuracy: ${Math.round((score/currentIdx)*100)}%`;
        document.getElementById('nextBtn').style.display = "block";
    }

    function showResults() {
        document.getElementById('quizContent').style.display = "none";
        document.getElementById('resultScreen').style.display = "block";
        const percent = Math.round((score / Math.min(TOTAL_Q, quizWords.length)) * 100);
        document.getElementById('finalScore').innerText = percent + "%";
        
        if (percent === 100) {
            confetti({ particleCount: 150, spread: 70, origin: { y: 0.6 }, colors: ['#10b981', '#1e293b'] });
            document.getElementById('xpAlert').style.display = "block";
            const fd = new FormData();
            fd.append('amount', 50);
            fetch('reward_xp.php', { method: 'POST', body: fd });
        }
    }

    window.onload = initQuiz;
</script>
</body>
</html>
