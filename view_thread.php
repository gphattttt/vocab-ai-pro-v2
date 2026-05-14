<?php
include 'db.php';
session_start();

// Security: Ensure the user is logged in
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

// --- 1. HANDLE NEW ANSWER SUBMISSION ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['answer_content'])) {
    $content = trim($_POST['answer_content']);
    
    if (!empty($content)) {
        // Insert the answer
        $stmt = $conn->prepare("INSERT INTO forum_answers (question_id, user_id, content) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $q_id, $user_id, $content);
        
        if ($stmt->execute()) {
            // --- DOPAMINE SYSTEM: REWARD XP ---
            // Reward +20 XP for helping the community
            $conn->query("UPDATE users SET xp = xp + 20 WHERE id = $user_id");
            
            // Redirect to refresh and show new answer (prevents form resubmission)
            header("Location: view_thread.php?id=$q_id&success=1");
            exit();
        }
    }
}

// --- 2. FETCH QUESTION DETAILS ---
$q_query = "SELECT q.*, u.username, u.profile_pic 
            FROM forum_questions q 
            JOIN users u ON q.user_id = u.id 
            WHERE q.id = $q_id";
$q_res = $conn->query($q_query);
$question = $q_res->fetch_assoc();

if (!$question) {
    die("Question not found.");
}

// --- 3. FETCH ALL ANSWERS ---
$a_query = "SELECT a.*, u.username, u.profile_pic 
            FROM forum_answers a 
            JOIN users u ON a.user_id = u.id 
            WHERE a.question_id = $q_id 
            ORDER BY a.created_at ASC";
$answers = $conn->query($a_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($question['title']); ?> | Community Forum</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { 
            --primary: #1e293b; 
            --accent: #10b981; 
            --bg: #f8fafc; 
            --border: #e2e8f0; 
            --text-muted: #64748b;
        }

        body { font-family: 'Inter', sans-serif; background: var(--bg); margin: 0; padding: 40px; color: var(--primary); }

        /* --- HAMBURGER MENU CSS --- */
        .nav-wrapper { position: fixed; top: 20px; left: 20px; z-index: 1000; }
        .menu-toggle { cursor: pointer; display: flex; flex-direction: column; gap: 5px; padding: 10px; background: white; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); border: 1px solid var(--border); }
        .bar { width: 22px; height: 3px; background-color: #1e293b; border-radius: 2px; transition: 0.3s; }
        .side-menu { position: fixed; top: 0; left: -280px; width: 280px; height: 100%; background: white; box-shadow: 5px 0 25px rgba(0,0,0,0.1); transition: 0.3s ease-in-out; z-index: 1001; padding: 30px; box-sizing: border-box; }
        .side-menu.active { left: 0; }
        .menu-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; }
        .close-btn { font-size: 2rem; cursor: pointer; color: #64748b; }
        nav a { display: block; padding: 15px 0; text-decoration: none; color: #1e293b; font-weight: 600; font-size: 1.1rem; border-bottom: 1px solid #f1f5f9; }
        nav a:hover { color: #10b981; }
        .menu-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.3); display: none; z-index: 999; backdrop-filter: blur(3px); }
        .menu-overlay.active { display: block; }

        /* --- THREAD LAYOUT --- */
        .container { max-width: 800px; margin: 60px auto 0 auto; }
        
        .back-link { display: inline-block; margin-bottom: 20px; text-decoration: none; color: var(--text-muted); font-weight: 600; font-size: 0.9rem; }
        .back-link:hover { color: var(--primary); }

        .card { 
            background: white; padding: 30px; border-radius: 24px; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.03); border: 1px solid var(--border); 
            margin-bottom: 25px; 
        }

        .question-card { border-left: 6px solid var(--primary); }

        .user-meta { display: flex; align-items: center; gap: 12px; margin-bottom: 20px; }
        .avatar { width: 45px; height: 45px; border-radius: 50%; object-fit: cover; background: #eee; border: 2px solid var(--border); }
        .username { font-weight: 700; font-size: 1rem; color: var(--primary); }
        .date { font-size: 0.8rem; color: var(--text-muted); }

        h1 { margin: 0 0 15px 0; font-size: 1.6rem; line-height: 1.3; }
        .content-text { line-height: 1.7; color: #334155; font-size: 1.05rem; white-space: pre-wrap; }

        /* Answers Section */
        .answer-count { font-weight: 700; margin-bottom: 20px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; font-size: 0.8rem; }
        
        .answer-card { border-left: 6px solid var(--accent); }

        /* Reply Form */
        textarea { 
            width: 100%; padding: 15px; border: 1px solid var(--border); 
            border-radius: 16px; font-family: inherit; font-size: 1rem; 
            box-sizing: border-box; margin-bottom: 15px; resize: vertical;
        }
        textarea:focus { outline: none; border-color: var(--accent); }
        
        .btn-reply { 
            background: var(--accent); color: white; border: none; 
            padding: 14px 28px; border-radius: 14px; font-weight: 700; 
            cursor: pointer; transition: 0.2s; 
        }
        .btn-reply:hover { opacity: 0.9; transform: translateY(-1px); }

        .xp-alert {
            background: #ecfdf5; color: #10b981; padding: 12px; 
            border-radius: 12px; margin-bottom: 20px; font-size: 0.9rem; font-weight: 600;
            text-align: center;
        }
    </style>
</head>
<body>

    <?php include 'nav.php'; ?>

    <div class="container">
        <a href="forum.php" class="back-link">← Back to Forum</a>

        <?php if(isset($_GET['success'])): ?>
            <div class="xp-alert">✨ Answer posted! You earned +20 XP.</div>
        <?php endif; ?>

        <div class="card question-card">
            <div class="user-meta">
                <?php 
                    $q_pic = !empty($question['profile_pic']) && file_exists($question['profile_pic']) 
                             ? $question['profile_pic'] 
                             : 'https://ui-avatars.com/api/?name=' . $question['username'] . '&background=1e293b&color=fff';
                ?>
                <img src="<?php echo $q_pic; ?>" class="avatar">
                <div>
                    <span class="username"><?php echo htmlspecialchars($question['username']); ?></span>
                    <div class="date">Asked on <?php echo date('M d, Y', strtotime($question['created_at'])); ?></div>
                </div>
            </div>
            <h1><?php echo htmlspecialchars($question['title']); ?></h1>
            <div class="content-text"><?php echo htmlspecialchars($question['content']); ?></div>
        </div>

        <div class="answer-count">Community Answers (<?php echo $answers->num_rows; ?>)</div>

        <?php if ($answers->num_rows > 0): ?>
            <?php while($a = $answers->fetch_assoc()): ?>
                <div class="card answer-card">
                    <div class="user-meta">
                        <?php 
                            $a_pic = !empty($a['profile_pic']) && file_exists($a['profile_pic']) 
                                     ? $a['profile_pic'] 
                                     : 'https://ui-avatars.com/api/?name=' . $a['username'] . '&background=10b981&color=fff';
                        ?>
                        <img src="<?php echo $a_pic; ?>" class="avatar">
                        <div>
                            <span class="username"><?php echo htmlspecialchars($a['username']); ?></span>
                            <div class="date">Replied on <?php echo date('M d, Y', strtotime($a['created_at'])); ?></div>
                        </div>
                    </div>
                    <div class="content-text"><?php echo htmlspecialchars($a['content']); ?></div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p style="text-align: center; color: var(--text-muted); font-style: italic; margin-bottom: 30px;">No answers yet. Be the first to help!</p>
        <?php endif; ?>

        <div class="card">
            <h3 style="margin-top:0;">Your Answer</h3>
            <form action="view_thread.php?id=<?php echo $q_id; ?>" method="POST">
                <textarea name="answer_content" rows="5" placeholder="Write your explanation or answer here..." required></textarea>
                <button type="submit" class="btn-reply">Post Answer & Earn 20 XP</button>
            </form>
        </div>
    </div>

    <script>
        function toggleMenu() {
            document.getElementById('sideMenu').classList.toggle('active');
            document.getElementById('menuOverlay').classList.toggle('active');
        }
    </script>
</body>
</html>