<?php 
session_start(); 
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); } 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Active Recall | Flashcards</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #1e293b; --accent: #10b981; --bg: #f8fafc;
            --border: #e2e8f0; --text-muted: #64748b;
            --spring: cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        body { 
            font-family: 'Inter', sans-serif; background: var(--bg); 
            margin: 0; padding: 20px; color: var(--primary); 
            min-height: 100vh;
        }

        .container { 
            max-width: 450px; margin: 80px auto; 
            perspective: 1000px; /* Gives the 3D depth */
        }

        /* --- THE 3D FLIPPER --- */
        .flashcard-inner {
            width: 100%; height: 420px;
            transition: transform 0.6s var(--spring);
            transform-style: preserve-3d; /* CRITICAL: Allows 3D layers */
            position: relative;
            cursor: pointer;
        }

        /* Flip Action */
        .flashcard-inner.is-flipped {
            transform: rotateY(180deg);
        }

        .card-face {
            position: absolute; width: 100%; height: 100%;
            -webkit-backface-visibility: hidden; /* Safari */
            backface-visibility: hidden; /* Hide the 'back' of the div when turned */
            display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            padding: 40px; border-radius: 32px; box-sizing: border-box;
            box-shadow: 0 10px 30px rgba(0,0,0,0.04);
            border: 2px solid var(--border);
            background: white;
        }

        /* Front: Facing the user at 0deg */
        .card-front {
            z-index: 2;
            transform: rotateY(0deg);
        }

        /* Back: Facing away from the user at 180deg */
        .card-back {
            transform: rotateY(180deg); 
            background: white;
        }

        /* Text Styles */
        #cardWord { font-size: 2.8rem; font-weight: 800; margin: 0; }
        .ipa-text { font-family: 'Courier New', monospace; color: var(--accent); font-weight: 700; margin-bottom: 15px; font-size: 1.1rem; }
        .definition { font-size: 1.1rem; line-height: 1.5; font-weight: 600; margin-bottom: 15px; color: #334155; }
        .vi-translation { color: #1e40af; background: #eff6ff; padding: 8px 16px; border-radius: 10px; font-style: italic; }

        /* --- UI CONTROLS --- */
        .controls { display: flex; justify-content: center; gap: 15px; margin-top: 30px; }
        .btn-nav {
            background: white; border: 2px solid var(--border); padding: 12px 24px;
            border-radius: 16px; cursor: pointer; font-weight: 800; transition: 0.3s;
        }
        .btn-nav:hover { transform: translateY(-3px); border-color: var(--primary); }

        .progress-indicator { text-align: center; margin-top: 20px; font-weight: 700; color: var(--text-muted); font-size: 0.8rem; }
    </style>
</head>
<body>

    <?php include 'nav.php'; ?>

    <div class="container">
        <div class="flashcard-inner" id="flipper" onclick="this.classList.toggle('is-flipped')">
            
            <div class="card-face card-front">
                <span id="cardLevel" style="font-size: 0.6rem; font-weight: 900; color: #94a3b8; text-transform: uppercase; margin-bottom: 10px;">Level</span>
                <h1 id="cardWord">Word</h1>
                <div style="margin-top: 20px; color: var(--text-muted); font-weight: 600; font-size: 0.8rem;">Tap to Flip 🔄</div>
            </div>

            <div class="card-face card-back">
                <div style="width: 100%; text-align: center;">
                    <div class="ipa-text" id="cardIpa">/phonetics/</div>
                    <div class="definition" id="cardDef">Definition goes here.</div>
                    <div class="vi-translation" id="cardVi">Bản dịch</div>
                    <p style="margin-top: 20px; font-size: 0.8rem; color: #64748b; font-style: italic;" id="cardEx">Example sentence.</p>
                </div>
            </div>

        </div>

        <div class="controls">
            <button class="btn-nav" onclick="prevCard()">← Prev</button>
            <button class="btn-nav" style="background: var(--primary); color: white; border: none;" onclick="nextCard()">Next →</button>
        </div>
        <div class="progress-indicator" id="progressText">Word 1 of 10</div>
    </div>

<script>
    let cards = [];
    let currentIdx = 0;

    async function loadCards() {
        const res = await fetch('fetch_board.php');
        cards = await res.json();
        if (cards.length > 0) updateCardUI();
    }

    function updateCardUI() {
        const wordData = cards[currentIdx];
        const flipper = document.getElementById('flipper');
        
        // Reset to front face
        flipper.classList.remove('is-flipped');

        document.getElementById('cardWord').innerText = wordData.word;
        document.getElementById('cardLevel').innerText = `${wordData.level} • ${wordData.word_form}`;
        document.getElementById('cardIpa').innerText = wordData.ipa;
        document.getElementById('cardDef').innerText = wordData.definition_en;
        document.getElementById('cardVi').innerText = wordData.definition_vi;
        document.getElementById('cardEx').innerText = `"${wordData.example_sentence}"`;
        document.getElementById('progressText').innerText = `Word ${currentIdx + 1} of ${cards.length}`;
    }

    function nextCard() {
        if (currentIdx < cards.length - 1) {
            currentIdx++;
            updateCardUI();
        }
    }

    function prevCard() {
        if (currentIdx > 0) {
            currentIdx--;
            updateCardUI();
        }
    }

    // Keyboard support
    document.addEventListener('keydown', (e) => {
        if (e.code === 'Space') { e.preventDefault(); document.getElementById('flipper').classList.toggle('is-flipped'); }
        if (e.code === 'ArrowRight') nextCard();
        if (e.code === 'ArrowLeft') prevCard();
    });

    window.onload = loadCards;
</script>
</body>
</html>