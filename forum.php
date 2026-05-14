<?php
include 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

/** * We use a LEFT JOIN for answers count so that questions 
 * with 0 replies still show up correctly.
 */
$sql = "SELECT q.*, u.username, u.xp, COUNT(a.id) as reply_count 
        FROM questions q 
        JOIN users u ON q.user_id = u.id 
        LEFT JOIN answers a ON q.id = a.question_id 
        GROUP BY q.id 
        ORDER BY q.created_at DESC";

$result = $conn->query($sql);

// Check if query failed to avoid the "bool" error
if (!$result) {
    die("Query Failed: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Community Forum | Vocab AI Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #1e293b; --accent: #10b981; --bg: #f8fafc;
            --border: #e2e8f0; --text-muted: #64748b;
            --spring: cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        body { font-family: 'Inter', sans-serif; background: var(--bg); margin: 0; padding: 40px; color: var(--primary); }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-in { animation: slideUp 0.5s var(--spring) forwards; opacity: 0; }

        .container { max-width: 900px; margin: 60px auto 0 auto; }

        .forum-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; }
        .btn-ask { 
            background: var(--accent); color: white; padding: 14px 28px; 
            border-radius: 16px; text-decoration: none; font-weight: 700; 
            transition: 0.3s var(--spring); box-shadow: 0 4px 15px rgba(16, 185, 129, 0.2);
        }
        .btn-ask:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(16, 185, 129, 0.3); }

        .search-box { 
            width: 100%; background: white; padding: 5px; border-radius: 20px;
            border: 1px solid var(--border); display: flex; margin-bottom: 30px;
        }
        .search-box input { flex: 1; border: none; padding: 15px 20px; border-radius: 15px; outline: none; font-family: inherit; }

        .forum-card {
            background: white; border-radius: 24px; padding: 30px;
            border: 1px solid var(--border); margin-bottom: 20px;
            text-decoration: none; color: inherit; display: block;
            transition: 0.3s var(--spring);
        }
        .forum-card:hover { transform: translateY(-5px); border-color: var(--accent); box-shadow: 0 15px 35px rgba(0,0,0,0.05); }

        .user-meta { display: flex; align-items: center; gap: 10px; margin-bottom: 15px; }
        .avatar { width: 32px; height: 32px; background: #e2e8f0; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-weight: 800; color: var(--text-muted); }
        
        .question-title { font-size: 1.3rem; font-weight: 800; margin: 0 0 10px 0; }
        
        /* Fixed CSS Warning: Added standard line-clamp */
        .question-excerpt { 
            color: var(--text-muted); line-height: 1.5; margin-bottom: 20px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            line-clamp: 2; 
            overflow: hidden; 
        }

        .card-footer { display: flex; gap: 15px; border-top: 1px solid #f1f5f9; padding-top: 20px; }
        .badge { padding: 6px 12px; border-radius: 10px; font-size: 0.8rem; font-weight: 700; }
        .badge-reply { background: #f0fdf4; color: #10b981; }
        .badge-xp { background: #fffbeb; color: #d97706; }
    </style>
</head>
<body>

    <?php include 'nav.php'; ?>

    <div class="container">
        <div class="forum-header animate-in">
            <div>
                <h1 style="margin:0; font-weight:800; font-size: 2.2rem;">Community Feed</h1>
                <p style="color: var(--text-muted); margin-top:5px;">Join the discussion with <?php echo $result->num_rows; ?> students</p>
            </div>
            <a href="ask_question.php" class="btn-ask">+ New Topic</a>
        </div>

        <div class="search-box animate-in" style="animation-delay: 0.1s;">
            <input type="text" id="forumSearch" placeholder="Search discussions..." onkeyup="filterForum()">
        </div>

        <div id="forumFeed">
            <?php 
            $delay = 0.2;
            while($row = $result->fetch_assoc()): 
            ?>
                <a href="view_question.php?id=<?php echo $row['id']; ?>" class="forum-card animate-in" style="animation-delay: <?php echo $delay; ?>s;">
                    <div class="user-meta">
                        <div class="avatar"><?php echo strtoupper(substr($row['username'], 0, 1)); ?></div>
                        <span style="font-weight:700;"><?php echo htmlspecialchars($row['username']); ?></span>
                        <span style="color:var(--text-muted);">• <?php echo date("M d", strtotime($row['created_at'])); ?></span>
                    </div>

                    <h2 class="question-title"><?php echo htmlspecialchars($row['title']); ?></h2>
                    <p class="question-excerpt"><?php echo htmlspecialchars($row['content']); ?></p>

                    <div class="card-footer">
                        <span class="badge badge-reply">💬 <?php echo $row['reply_count']; ?> Replies</span>
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