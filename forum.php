<?php
include 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

/** * Đã đưa về tên bảng gốc 'questions' và 'answers' để khớp với database của bạn
 */
$sql = "SELECT q.*, u.username, u.xp, COUNT(a.id) as reply_count 
        FROM questions q 
        JOIN users u ON q.user_id = u.id 
        LEFT JOIN answers a ON q.id = a.question_id 
        GROUP BY q.id 
        ORDER BY q.created_at DESC";

$result = $conn->query($sql);

if (!$result) {
    die("Lỗi truy vấn: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cộng đồng thảo luận | Vocab AI Pro</title>
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
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .animate-in { 
            animation: slideUp 0.5s var(--transition) forwards; 
            opacity: 0; 
        }

        .container { max-width: 900px; margin: 60px auto 0 auto; }

        .forum-header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 40px; 
        }

        .btn-ask { 
            background: var(--accent);
            color: white; 
            padding: 14px 28px; 
            border-radius: 16px; 
            text-decoration: none; 
            font-weight: 700; 
            transition: 0.3s var(--transition);
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.15);
        }

        .btn-ask:hover { 
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.25); 
        }

        .search-box { 
            width: 100%;
            background: white; 
            padding: 5px; 
            border-radius: 24px;
            border: 1px solid var(--border); 
            display: flex; 
            margin-bottom: 30px;
        }

        .search-box input { 
            flex: 1; 
            border: none; 
            padding: 16px 24px; 
            border-radius: 20px;
            outline: none; 
            font-family: inherit; 
            font-size: 1rem;
        }

        .forum-card {
            background: white;
            border-radius: 28px; 
            padding: 32px;
            border: 1px solid var(--border); 
            margin-bottom: 20px;
            text-decoration: none; 
            color: inherit; 
            display: block;
            transition: 0.3s var(--transition);
        }

        .forum-card:hover { 
            transform: translateY(-5px); 
            border-color: var(--accent); 
            box-shadow: 0 15px 35px rgba(0,0,0,0.05); 
        }

        .user-meta { 
            display: flex; 
            align-items: center; 
            gap: 12px; 
            margin-bottom: 18px; 
        }

        .avatar { 
            width: 36px; 
            height: 36px; 
            background: #f1f5f9; 
            border-radius: 12px; 
            display: flex;
            align-items: center; 
            justify-content: center; 
            font-weight: 800; 
            color: var(--accent); 
            border: 1px solid var(--border);
        }
        
        .question-title { 
            font-size: 1.4rem;
            font-weight: 800; 
            margin: 0 0 12px 0; 
            letter-spacing: -0.5px;
        }
        
        .question-excerpt { 
            color: var(--text-muted);
            line-height: 1.6; 
            margin-bottom: 24px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            line-clamp: 2; 
            overflow: hidden;
            font-size: 0.95rem;
        }

        .card-footer { 
            display: flex; 
            gap: 12px; 
            border-top: 1px solid #f8fafc; 
            padding-top: 24px; 
        }

        .badge { 
            padding: 8px 14px; 
            border-radius: 12px; 
            font-size: 0.8rem; 
            font-weight: 700; 
        }

        .badge-reply { background: #f0fdf4; color: #10b981; }
        .badge-xp { background: #fffbeb; color: #d97706; }

        @media (max-width: 600px) {
            body { padding: 20px; padding-top: 60px; }
            .forum-header { flex-direction: column; align-items: flex-start; gap: 20px; }
            .btn-ask { width: 100%; text-align: center; box-sizing: border-box; }
        }
    </style>
</head>
<body>

    <?php include 'nav.php'; ?>

    <div class="container">
        <div class="forum-header animate-in">
            <div>
                <h1 style="margin:0; font-weight:800; font-size: 2.4rem; letter-spacing: -1px;">Bảng tin Cộng đồng</h1>
                <p style="color: var(--text-muted); margin-top:5px; font-weight: 500;">Thảo luận cùng <?php echo $result->num_rows; ?> học viên khác</p>
            </div>
            <a href="ask_question.php" class="btn-ask">+ Chủ đề mới (+15 XP)</a>
        </div>

        <div class="search-box animate-in" style="animation-delay: 0.1s;">
            <input type="text" id="forumSearch" placeholder="Tìm kiếm thảo luận..." onkeyup="filterForum()">
        </div>

        <div id="forumFeed">
            <?php 
            $delay = 0.2;
            while($row = $result->fetch_assoc()): 
            ?>
                <a href="view_thread.php?id=<?php echo $row['id']; ?>" class="forum-card animate-in" style="animation-delay: <?php echo $delay; ?>s;">
                    <div class="user-meta">
                        <div class="avatar"><?php echo strtoupper(substr($row['username'], 0, 1)); ?></div>
                        <span style="font-weight:700;"><?php echo htmlspecialchars($row['username']); ?></span>
                        <span style="color:var(--text-muted);">• <?php echo date("d/m", strtotime($row['created_at'])); ?></span>
                    </div>

                    <h2 class="question-title"><?php echo htmlspecialchars($row['title']); ?></h2>
                    <p class="question-excerpt"><?php echo htmlspecialchars($row['content']); ?></p>

                    <div class="card-footer">
                        <span class="badge badge-reply">💬 <?php echo $row['reply_count']; ?> Bình luận</span>
                        <span class="badge badge-xp">⚡ <?php echo $row['xp']; ?> XP</span>
                    </div>
                </a>
            <?php 
                $delay += 0.05;
            endwhile; 
            ?>
        </div>
    </div>

<script>
    function filterForum() {
        const filter = document.getElementById('forumSearch').value.toUpperCase();
        const cards = document.getElementsByClassName('forum-card');
        for (let i = 0; i < cards.length; i++) {
            const txt = cards[i].innerText.toUpperCase();
            cards[i].style.display = txt.includes(filter) ? "" : "none";
        }
    }
</script>
</body>
</html>
