<?php
include 'db.php';
session_start();

// Bảo mật: Đảm bảo người dùng đã đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$q_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($q_id === 0) {
    header("Location: forum.php");
    exit();
}

// --- 1. XỬ LÝ GỬI CÂU TRẢ LỜI MỚI ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['answer_content'])) {
    $content = trim($_POST['answer_content']);
    if (!empty($content)) {
        // Sử dụng bảng 'answers' thống nhất với forum.php
        $stmt = $conn->prepare("INSERT INTO answers (question_id, user_id, content) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $q_id, $user_id, $content);
        
        if ($stmt->execute()) {
            // HỆ THỐNG ĐIỂM THƯỞNG: +5 XP cho việc hỗ trợ cộng đồng (Theo Blueprint)
            $conn->query("UPDATE users SET xp = xp + 5 WHERE id = $user_id");
            
            // Chuyển hướng để tránh gửi lại biểu mẫu khi làm mới trang
            header("Location: view_thread.php?id=$q_id&success=1");
            exit();
        }
    }
}

// --- 2. TRUY XUẤT CHI TIẾT CÂU HỎI (Sử dụng Prepared Statement) ---
$q_stmt = $conn->prepare("SELECT q.*, u.username, u.profile_pic FROM questions q JOIN users u ON q.user_id = u.id WHERE q.id = ?");
$q_stmt->bind_param("i", $q_id);
$q_stmt->execute();
$question = $q_stmt->get_result()->fetch_assoc();

if (!$question) {
    die("Không tìm thấy câu hỏi.");
}

// --- 3. TRUY XUẤT TẤT CẢ CÂU TRẢ LỜI ---
$a_stmt = $conn->prepare("SELECT a.*, u.username, u.profile_pic FROM answers a JOIN users u ON a.user_id = u.id WHERE a.question_id = ? ORDER BY a.created_at ASC");
$a_stmt->bind_param("i", $q_id);
$a_stmt->execute();
$answers = $a_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($question['title']); ?> | Cộng đồng Vocab AI Pro</title>
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

        /* --- THREAD LAYOUT --- */
        .container { 
            max-width: 850px;
            margin: 60px auto 0 auto; 
        }
        
        .back-link { 
            display: inline-block;
            margin-bottom: 25px; 
            text-decoration: none; 
            color: var(--text-muted); 
            font-weight: 700; 
            font-size: 0.95rem;
            transition: 0.3s;
        }
        .back-link:hover { color: var(--accent); transform: translateX(-5px); }

        .card { 
            background: white;
            padding: 40px; 
            border-radius: 32px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.02); 
            border: 1px solid var(--border); 
            margin-bottom: 25px;
            animation: slideUp 0.5s var(--transition) forwards;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .question-card { border-top: 6px solid var(--primary); }

        .user-meta { 
            display: flex; 
            align-items: center; 
            gap: 15px; 
            margin-bottom: 25px; 
        }
        
        .avatar { 
            width: 50px; 
            height: 50px; 
            border-radius: 18px; 
            object-fit: cover; 
            background: #f1f5f9;
            border: 2px solid white;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        }
        
        .username { font-weight: 800; font-size: 1.05rem; color: var(--primary); }
        .date { font-size: 0.85rem; color: var(--text-muted); font-weight: 500; }

        h1 { 
            margin: 0 0 20px 0; 
            font-size: 1.8rem; 
            line-height: 1.3; 
            font-weight: 800;
            letter-spacing: -0.5px;
        }
        
        .content-text { 
            line-height: 1.7; 
            color: #334155; 
            font-size: 1.1rem; 
            white-space: pre-wrap; 
        }

        /* Answers Section */
        .answer-count { 
            font-weight: 800;
            margin-bottom: 20px; 
            color: var(--text-muted); 
            text-transform: uppercase; 
            letter-spacing: 0.1em; 
            font-size: 0.8rem;
            padding-left: 10px;
        }
        
        .answer-card { border-left: 6px solid var(--accent); }

        /* Reply Form */
        textarea { 
            width: 100%;
            padding: 20px; 
            border: 2px solid var(--border); 
            border-radius: 20px; 
            font-family: inherit; 
            font-size: 1rem; 
            box-sizing: border-box; 
            margin-bottom: 20px; 
            resize: vertical;
            transition: 0.3s;
        }
        textarea:focus { outline: none; border-color: var(--accent); background: #f0fdf4; }
        
        .btn-reply { 
            background: var(--primary);
            color: white; 
            border: none; 
            padding: 18px 35px; 
            border-radius: 16px; 
            font-weight: 800; 
            cursor: pointer; 
            transition: 0.3s var(--transition);
            font-size: 1rem;
        }
        .btn-reply:hover { background: var(--accent); transform: translateY(-3px); box-shadow: 0 10px 20px rgba(16, 185, 129, 0.2); }

        .xp-alert {
            background: #ecfdf5;
            color: #10b981; 
            padding: 18px; 
            border-radius: 20px; 
            margin-bottom: 25px; 
            font-size: 1rem; 
            font-weight: 700;
            text-align: center;
            border: 1px solid #dcfce7;
            animation: slideUp 0.5s var(--transition);
        }

        @media (max-width: 600px) {
            body { padding: 20px; padding-top: 60px; }
            .card { padding: 25px; }
            h1 { font-size: 1.5rem; }
        }
    </style>
</head>
<body>

    <?php include 'nav.php'; ?>

    <div class="container">
        <a href="forum.php" class="back-link">← Quay lại Diễn đàn</a>

        <?php if(isset($_GET['success'])): ?>
            <div class="xp-alert">✨ Đã đăng câu trả lời thành công! Bạn nhận được +5 XP.</div>
        <?php endif; ?>

        <div class="card question-card">
            <div class="user-meta">
                <?php 
                    $q_pic = !empty($question['profile_pic']) 
                             ? 'uploads/avatars/' . $question['profile_pic'] 
                             : 'https://ui-avatars.com/api/?name=' . $question['username'] . '&background=1e293b&color=fff';
                ?>
                <img src="<?php echo htmlspecialchars($q_pic); ?>" class="avatar">
                <div>
                    <span class="username"><?php echo htmlspecialchars($question['username']); ?></span>
                    <div class="date">Đã hỏi vào <?php echo date('d/m/Y', strtotime($question['created_at'])); ?></div>
                </div>
            </div>
            <h1><?php echo htmlspecialchars($question['title']); ?></h1>
            <div class="content-text"><?php echo htmlspecialchars($question['content']); ?></div>
        </div>

        <div class="answer-count">Câu trả lời từ cộng đồng (<?php echo $answers->num_rows; ?>)</div>

        <?php if ($answers->num_rows > 0): ?>
            <?php while($a = $answers->fetch_assoc()): ?>
                <div class="card answer-card">
                    <div class="user-meta">
                        <?php 
                            $a_pic = !empty($a['profile_pic']) 
                                     ? 'uploads/avatars/' . $a['profile_pic'] 
                                     : 'https://ui-avatars.com/api/?name=' . $a['username'] . '&background=10b981&color=fff';
                        ?>
                        <img src="<?php echo htmlspecialchars($a_pic); ?>" class="avatar">
                        <div>
                            <span class="username"><?php echo htmlspecialchars($a['username']); ?></span>
                            <div class="date">Đã trả lời vào <?php echo date('d/m/Y', strtotime($a['created_at'])); ?></div>
                        </div>
                    </div>
                    <div class="content-text"><?php echo htmlspecialchars($a['content']); ?></div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p style="text-align: center; color: var(--text-muted); font-style: italic; margin: 40px 0; font-weight: 500;">
                Chưa có câu trả lời nào. Hãy là người đầu tiên giúp đỡ cộng đồng!
            </p>
        <?php endif; ?>

        <div class="card">
            <h3 style="margin-top:0; font-weight: 800; margin-bottom: 20px;">Câu trả lời của bạn</h3>
            <form action="view_thread.php?id=<?php echo $q_id; ?>" method="POST">
                <textarea name="answer_content" rows="5" placeholder="Viết lời giải thích hoặc câu trả lời của bạn tại đây..." required></textarea>
                <button type="submit" class="btn-reply">Đăng câu trả lời & Nhận 5 XP</button>
            </form>
        </div>
    </div>

</body>
</html>
