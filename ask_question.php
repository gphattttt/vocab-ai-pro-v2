<?php
include 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Logic: Xử lý form (Giữ nguyên backend)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);

    if (!empty($title) && !empty($content)) {
        $stmt = $conn->prepare("INSERT INTO questions (user_id, title, content) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $user_id, $title, $content);
        
        if ($stmt->execute()) {
            // Thưởng XP (Giữ nguyên logic của bạn)
            $conn->query("UPDATE users SET xp = xp + 15 WHERE id = $user_id");
            header("Location: forum.php");
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt câu hỏi | Vocab AI Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #1e293b;
            --accent: #10b981; 
            --bg: #f8fafc;
            --border: #e2e8f0; 
            --text-muted: #64748b;
            --transition: cubic-bezier(0.4, 0, 0.2, 1);
        }

        body { 
            font-family: 'Be Vietnam Pro', sans-serif; 
            background: var(--bg); 
            margin: 0; 
            padding: 40px;
            color: var(--primary); 
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .animate-in { 
            animation: slideUp 0.6s var(--transition) forwards; 
            opacity: 0;
        }

        .container { max-width: 700px; margin: 60px auto 0 auto; }
        
        .header-section { margin-bottom: 40px; }
        
        .btn-back { 
            text-decoration: none; 
            color: var(--text-muted); 
            font-weight: 700; 
            font-size: 0.95rem; 
            transition: 0.3s;
            display: inline-block;
        }
        .btn-back:hover { color: var(--accent); transform: translateX(-5px); }

        .form-card {
            background: white;
            border-radius: 32px; 
            padding: 45px;
            border: 1px solid var(--border); 
            box-shadow: 0 10px 40px rgba(0,0,0,0.03);
        }

        .input-group { margin-bottom: 30px; }
        
        .input-group label { 
            display: block; 
            font-size: 0.75rem; 
            font-weight: 800; 
            text-transform: uppercase;
            margin-bottom: 12px; 
            color: var(--text-muted); 
            letter-spacing: 0.05em; 
        }
        
        input[type="text"], textarea {
            width: 100%;
            padding: 18px 24px; 
            border: 2px solid var(--border);
            border-radius: 20px; 
            font-size: 1rem; 
            box-sizing: border-box;
            font-family: inherit; 
            transition: 0.3s; 
            background: #fff;
        }
        
        input:focus, textarea:focus { 
            outline: none; 
            border-color: var(--accent); 
            background: #f0fdf4;
            box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.05);
        }

        .btn-submit {
            width: 100%;
            padding: 20px; 
            background: var(--primary); 
            color: white;
            border: none; 
            border-radius: 18px; 
            font-weight: 800; 
            font-size: 1.05rem;
            cursor: pointer; 
            transition: 0.3s var(--transition);
        }
        
        .btn-submit:hover { 
            transform: translateY(-4px); 
            box-shadow: 0 12px 24px rgba(16, 185, 129, 0.2); 
            background: var(--accent);
        }

        .char-count { 
            text-align: right; 
            font-size: 0.8rem; 
            color: var(--text-muted); 
            margin-top: 8px; 
            font-weight: 600; 
        }

        @media (max-width: 600px) {
            body { padding: 20px; padding-top: 60px; }
            .form-card { padding: 30px 20px; }
        }
    </style>
</head>
<body>

    <?php include 'nav.php'; ?>

    <div class="container animate-in">
        <div class="header-section">
            <a href="forum.php" class="btn-back">← Quay lại Diễn đàn</a>
            <h1 style="margin: 15px 0 8px 0; font-size: 2.4rem; font-weight: 800; letter-spacing: -1px;">Bắt đầu thảo luận mới</h1>
            <p style="color: var(--text-muted); font-weight: 500; font-size: 1.1rem;">
                Đặt câu hỏi về ngữ pháp, phát âm IPA hoặc mẹo học tập! <span style="color: var(--accent); font-weight: 800;">(+15 XP)</span>
            </p>
        </div>

        <div class="form-card">
            <form method="POST">
                <div class="input-group">
                    <label>Tiêu đề bài viết</label>
                    <input type="text" name="title" maxlength="100" placeholder="VD: Cách phát âm từ 'Aesthetic' chuẩn nhất?" required>
                </div>

                <div class="input-group">
                    <label>Nội dung chi tiết</label>
                    <textarea name="content" id="contentArea" rows="8" placeholder="Hãy mô tả chi tiết vấn đề của bạn để cộng đồng có thể hỗ trợ tốt nhất..." required onkeyup="updateCount()"></textarea>
                    <div class="char-count"><span id="charNum">0</span> ký tự</div>
                </div>

                <button type="submit" class="btn-submit">🚀 Đăng lên cộng đồng</button>
            </form>
        </div>
    </div>

<script>
    function updateCount() {
        const text = document.getElementById('contentArea').value;
        document.getElementById('charNum').innerText = text.length;
    }
</script>
</body>
</html>
