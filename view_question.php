<?php
include 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }
$user_id = $_SESSION['user_id'];
$question_id = $_GET['id'] ?? 0;

// 1. Handle Answer Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reply_content'])) {
    $content = trim($_POST['reply_content']);
    if (!empty($content)) {
        $stmt = $conn->prepare("INSERT INTO answers (question_id, user_id, content) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $question_id, $user_id, $content);
        $stmt->execute();
        // Reward 5 XP for helping others
        $conn->query("UPDATE users SET xp = xp + 5 WHERE id = $user_id");
        header("Location: view_question.php?id=$question_id");
        exit();
    }
}

// 2. Fetch Question
$q_res = $conn->query("SELECT q.*, u.username, u.profile_pic FROM questions q JOIN users u ON q.user_id = u.id WHERE q.id = $question_id");
$question = $q_res->fetch_assoc();

if (!$question) { die("Question not found!"); }

// 3. Fetch Answers
$a_res = $conn->query("SELECT a.*, u.username, u.profile_pic FROM answers a JOIN users u ON a.user_id = u.id WHERE a.question_id = $question_id ORDER BY a.created_at ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($question['title']); ?> | Forum</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #1e293b; --accent: #10b981; --bg: #f8fafc; --border: #e2e8f0; --spring: cubic-bezier(0.175, 0.885, 0.32, 1.275); }
        body { font-family: 'Inter', sans-serif; background: var(--bg); margin: 0; padding: 40px; color: var(--primary); }
        .container { max-width: 800px; margin: 40px auto; }
        .back-link { text-decoration: none; color: #64748b; font-weight: 700; font-size: 0.9rem; }
        
        .card { background: white; padding: 35px; border-radius: 28px; border: 1px solid var(--border); box-shadow: 0 10px 30px rgba(0,0,0,0.02); margin-top: 20px; }
        .user-row { display: flex; align-items: center; gap: 12px; margin-bottom: 20px; }
        .avatar { width: 40px; height: 40px; border-radius: 12px; object-fit: cover; background: #e2e8f0; }
        
        .reply-card { background: white; padding: 20px; border-radius: 20px; border: 1px solid var(--border); margin-top: 15px; }
        textarea { width: 100%; padding: 15px; border: 2px solid var(--border); border-radius: 15px; font-family: inherit; margin-top: 20px; box-sizing: border-box; }
        .btn-reply { background: var(--primary); color: white; border: none; padding: 12px 25px; border-radius: 12px; font-weight: 700; cursor: pointer; margin-top: 10px; transition: 0.3s; }
        .btn-reply:hover { background: var(--accent); transform: translateY(-2px); }
    </style>
</head>
<body>
    <?php include 'nav.php'; ?>
    <div class="container">
        <a href="forum.php" class="back-link">← Back to Feed</a>
        
        <div class="card">
            <div class="user-row">
                <img src="uploads/avatars/<?php echo $question['profile_pic']; ?>" class="avatar">
                <div>
                    <div style="font-weight: 800;"><?php echo htmlspecialchars($question['username']); ?></div>
                    <div style="font-size: 0.75rem; color: #64748b;"><?php echo date("M d, Y", strtotime($question['created_at'])); ?></div>
                </div>
            </div>
            <h1 style="margin: 0 0 15px 0;"><?php echo htmlspecialchars($question['title']); ?></h1>
            <p style="line-height: 1.6; font-size: 1.1rem; color: #334155;"><?php echo nl2br(htmlspecialchars($question['content'])); ?></p>
        </div>

        <h3 style="margin-top: 40px;">Discussion (<?php echo $a_res->num_rows; ?>)</h3>
        <?php while($row = $a_res->fetch_assoc()): ?>
            <div class="reply-card">
                <div class="user-row" style="margin-bottom: 10px;">
                    <img src="uploads/avatars/<?php echo $row['profile_pic']; ?>" class="avatar" style="width: 30px; height: 30px;">
                    <span style="font-weight: 700; font-size: 0.9rem;"><?php echo htmlspecialchars($row['username']); ?></span>
                </div>
                <p style="margin: 0; font-size: 0.95rem;"><?php echo nl2br(htmlspecialchars($row['content'])); ?></p>
            </div>
        <?php endwhile; ?>

        <form method="POST">
            <textarea name="reply_content" rows="4" placeholder="Write your helpful reply..." required></textarea>
            <button type="submit" class="btn-reply">Post Reply</button>
        </form>
    </div>
</body>
</html>