<?php 
session_start(); 
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); } 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Study Hub | Vocab AI Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #1e293b; --accent: #10b981; --bg: #f8fafc;
            --border: #e2e8f0; --text-muted: #64748b;
            --spring: cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        body { 
            font-family: 'Inter', sans-serif; background: var(--bg); 
            margin: 0; padding: 40px; color: var(--primary); 
            display: flex; align-items: center; justify-content: center; min-height: 100vh;
        }

        /* Entrance Animation */
        @keyframes slideUp { 
            from { opacity: 0; transform: translateY(30px); } 
            to { opacity: 1; transform: translateY(0); } 
        }

        .container { max-width: 800px; width: 100%; text-align: center; }

        .hub-title { 
            font-size: 2.5rem; font-weight: 800; margin-bottom: 10px; 
            animation: slideUp 0.5s var(--spring) forwards; 
        }
        
        .hub-subtitle { 
            color: var(--text-muted); font-weight: 600; margin-bottom: 50px;
            animation: slideUp 0.5s var(--spring) forwards 0.1s; opacity: 0;
        }

        /* --- MODE CARDS --- */
        .modes-grid { 
            display: grid; grid-template-columns: 1fr 1fr; gap: 25px; 
        }

        .mode-card {
            background: white; padding: 45px 30px; border-radius: 32px;
            text-decoration: none; color: inherit; border: 1px solid var(--border);
            box-shadow: 0 10px 30px rgba(0,0,0,0.03);
            transition: all 0.4s var(--spring);
            display: flex; flex-direction: column; align-items: center;
            opacity: 0; animation: slideUp 0.6s var(--spring) forwards;
        }

        .mode-card:hover {
            transform: translateY(-10px) scale(1.02);
            border-color: var(--accent);
            box-shadow: 0 20px 40px rgba(16, 185, 129, 0.1);
        }

        .mode-card.quiz-delay { animation-delay: 0.2s; }
        .mode-card.flash-delay { animation-delay: 0.3s; }

        .icon-circle {
            width: 80px; height: 80px; border-radius: 24px;
            display: flex; align-items: center; justify-content: center;
            font-size: 2.5rem; margin-bottom: 25px; transition: 0.3s;
        }

        .quiz-icon { background: #ecfdf5; color: #10b981; }
        .flash-icon { background: #eff6ff; color: #3b82f6; }

        .mode-card:hover .icon-circle {
            transform: rotate(10deg);
        }

        .mode-name { font-size: 1.5rem; font-weight: 800; margin-bottom: 12px; }
        .mode-desc { color: var(--text-muted); font-size: 0.95rem; line-height: 1.5; font-weight: 500; }

        /* Responsive */
        @media (max-width: 600px) {
            .modes-grid { grid-template-columns: 1fr; }
            body { padding: 20px; }
        }
    </style>
</head>
<body>

    <?php include 'nav.php'; ?>

    <div class="container">
        <h1 class="hub-title">Choose Your Mission</h1>
        <p class="hub-subtitle">Personalized learning powered by Gemini AI</p>

        <div class="modes-grid">
            <a href="quiz.php" class="mode-card quiz-delay">
                <div class="icon-circle quiz-icon">🎯</div>
                <div class="mode-name">Smart Quiz</div>
                <div class="mode-desc">
                    Test your memory with AI-generated sentences and multiple choice.
                </div>
                <div style="margin-top: 20px; font-weight: 800; color: var(--accent); font-size: 0.8rem; text-transform: uppercase;">
                    Best for Mastery →
                </div>
            </a>

            <a href="flashcards.php" class="mode-card flash-delay">
                <div class="icon-circle flash-icon">🃏</div>
                <div class="mode-name">Flashcards</div>
                <div class="mode-desc">
                    Classic active recall with 3D flip cards to cement word meanings.
                </div>
                <div style="margin-top: 20px; font-weight: 800; color: #3b82f6; font-size: 0.8rem; text-transform: uppercase;">
                    Best for Review →
                </div>
            </a>
        </div>

        <a href="index.php" style="display: inline-block; margin-top: 50px; color: var(--text-muted); text-decoration: none; font-weight: 700; font-size: 0.9rem;">
            ← Back to Dashboard
        </a>
    </div>

</body>
</html>