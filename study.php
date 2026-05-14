<?php 
session_start(); 
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); } 
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trung tâm Học tập | Vocab AI Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #1e293b;
            --accent: #10b981; 
            --bg: #f8fafc;
            --border: #e2e8f0; 
            --text-muted: #64748b;
            --spring: cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        body { 
            font-family: 'Be Vietnam Pro', sans-serif;
            background: var(--bg); 
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
            font-size: 2.8rem;
            font-weight: 800; margin-bottom: 10px; 
            letter-spacing: -1px;
            animation: slideUp 0.5s var(--spring) forwards; 
        }
        
        .hub-subtitle { 
            color: var(--text-muted);
            font-weight: 500; margin-bottom: 50px; font-size: 1.1rem;
            animation: slideUp 0.5s var(--spring) forwards 0.1s; opacity: 0;
        }

        /* --- MODE CARDS --- */
        .modes-grid { 
            display: grid;
            grid-template-columns: 1fr 1fr; gap: 25px; 
        }

        .mode-card {
            background: white;
            padding: 45px 30px; border-radius: 32px;
            text-decoration: none; color: inherit; border: 1px solid var(--border);
            box-shadow: 0 10px 30px rgba(0,0,0,0.03);
            transition: all 0.4s var(--spring);
            display: flex; flex-direction: column; align-items: center;
            opacity: 0; animation: slideUp 0.6s var(--spring) forwards;
            position: relative;
            overflow: hidden;
        }

        .mode-card:hover {
            transform: translateY(-12px) scale(1.02);
            border-color: var(--accent);
            box-shadow: 0 20px 40px rgba(16, 185, 129, 0.12);
        }

        .mode-card.quiz-delay { animation-delay: 0.2s; }
        .mode-card.flash-delay { animation-delay: 0.3s; }

        .icon-circle {
            width: 85px;
            height: 85px; border-radius: 24px;
            display: flex; align-items: center; justify-content: center;
            font-size: 2.8rem; margin-bottom: 25px; transition: 0.3s;
        }

        .quiz-icon { background: #ecfdf5; color: #10b981; }
        .flash-icon { background: #eff6ff; color: #3b82f6; }

        .mode-card:hover .icon-circle {
            transform: rotate(10deg) scale(1.1);
        }

        .mode-name { font-size: 1.6rem; font-weight: 800; margin-bottom: 12px; }
        .mode-desc { color: var(--text-muted); font-size: 0.95rem; line-height: 1.6; font-weight: 500; }

        /* XP Badge */
        .xp-badge {
            position: absolute; top: 20px; right: 20px;
            background: #fffbeb; color: #d97706; border: 1px solid #fef3c7;
            padding: 4px 12px; border-radius: 12px; font-size: 0.75rem; font-weight: 800;
        }

        .best-for {
            margin-top: 25px; font-weight: 800; font-size: 0.8rem; 
            text-transform: uppercase; letter-spacing: 0.5px;
            padding: 8px 16px; border-radius: 50px;
        }

        /* Responsive */
        @media (max-width: 650px) {
            .modes-grid { grid-template-columns: 1fr; }
            body { padding: 20px; padding-top: 60px; }
            .hub-title { font-size: 2.2rem; }
        }
    </style>
</head>
<body>

    <?php include 'nav.php'; ?>

    <div class="container">
        <h1 class="hub-title">Chọn nhiệm vụ của bạn</h1>
        <p class="hub-subtitle">Lộ trình cá nhân hóa, trợ lực bởi Gemini AI</p>

        <div class="modes-grid">
            <a href="quiz.php" class="mode-card quiz-delay">
                <div class="xp-badge">Lên đến +50 XP</div>
                <div class="icon-circle quiz-icon">🎯</div>
                <div class="mode-name">Trắc nghiệm</div>
                <div class="mode-desc">
                    Kiểm tra trí nhớ với các câu hỏi điền từ vào câu ngữ cảnh do AI tạo ra.
                </div>
                <div class="best-for" style="background: #ecfdf5; color: #10b981;">
                    Dành cho Thông thạo →
                </div>
            </a>

            <a href="flashcards.php" class="mode-card flash-delay">
                <div class="icon-circle flash-icon">🃏</div>
                <div class="mode-name">Thẻ ghi nhớ</div>
                <div class="mode-desc">
                    Phương pháp Active Recall với thẻ 3D lật mặt để khắc sâu ý nghĩa từ vựng.
                </div>
                <div class="best-for" style="background: #eff6ff; color: #3b82f6;">
                    Dành cho Ôn tập →
                </div>
            </a>
        </div>

        <a href="index.php" style="display: inline-block; margin-top: 50px; color: var(--text-muted); text-decoration: none; font-weight: 700; font-size: 0.95rem; transition: 0.3s;">
            ← Quay lại Bảng điều khiển
        </a>
    </div>

</body>
</html>
