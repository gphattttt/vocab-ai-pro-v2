<?php
include 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Logic: Process the form within the same file
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);

    if (!empty($title) && !empty($content)) {
        $stmt = $conn->prepare("INSERT INTO questions (user_id, title, content) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $user_id, $title, $content);
        
        if ($stmt->execute()) {
            // Reward XP for community participation
            $conn->query("UPDATE users SET xp = xp + 15 WHERE id = $user_id");
            header("Location: forum.php");
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ask the Community | Vocab AI Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #1e293b; --accent: #10b981; --bg: #f8fafc;
            --border: #e2e8f0; --text-muted: #64748b;
            --spring: cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        body { font-family: 'Inter', sans-serif; background: var(--bg); margin: 0; padding: 40px; color: var(--primary); }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-in { animation: slideUp 0.6s var(--spring) forwards; }

        .container { max-width: 700px; margin: 60px auto 0 auto; }
        
        .header-section { margin-bottom: 40px; }
        .btn-back { text-decoration: none; color: var(--text-muted); font-weight: 700; font-size: 0.9rem; transition: 0.2s; }
        .btn-back:hover { color: var(--primary); }

        .form-card {
            background: white; border-radius: 30px; padding: 40px;
            border: 1px solid var(--border); box-shadow: 0 10px 40px rgba(0,0,0,0.03);
        }

        .input-group { margin-bottom: 25px; }
        .input-group label { display: block; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; margin-bottom: 10px; color: var(--text-muted); letter-spacing: 0.05em; }
        
        input[type="text"], textarea {
            width: 100%; padding: 18px; border: 2px solid var(--border);
            border-radius: 18px; font-size: 1rem; box-sizing: border-box;
            font-family: inherit; transition: 0.3s; background: #fff;
        }
        input:focus, textarea:focus { outline: none; border-color: var(--accent); background: #f0fdf4; }

        .btn-submit {
            width: 100%; padding: 20px; background: var(--primary); color: white;
            border: none; border-radius: 18px; font-weight: 800; font-size: 1rem;
            cursor: pointer; transition: 0.3s var(--spring);
        }
        .btn-submit:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); background: var(--accent); }

        .char-count { text-align: right; font-size: 0.75rem; color: var(--text-muted); margin-top: 5px; font-weight: 600; }
    </style>
</head>
<body>

    <?php include 'nav.php'; ?>

    <div class="container animate-in">
        <div class="header-section">
            <a href="forum.php" class="btn-back">← Back to Discussions</a>
            <h1 style="margin: 15px 0 5px 0; font-size: 2.2rem; font-weight: 800;">Start a Discussion</h1>
            <p style="color: var(--text-muted); font-weight: 500;">Ask about grammar, IPA, or study tips! (+15 XP)</p>
        </div>

        <div class="form-card">
            <form method="POST">
                <div class="input-group">
                    <label>Catchy Title</label>
                    <input type="text" name="title" maxlength="100" placeholder="e.g. How to pronounce 'Aesthetic' properly?" required>
                </div>

                <div class="input-group">
                    <label>Explain your question</label>
                    <textarea name="content" id="contentArea" rows="8" placeholder="Be specific so the community can help you better..." required onkeyup="updateCount()"></textarea>
                    <div class="char-count"><span id="charNum">0</span> characters</div>
                </div>

                <button type="submit" class="btn-submit">🚀 Post to Community</button>
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