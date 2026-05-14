<?php
include 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) { 
    header("Location: login.php"); 
    exit(); 
}
$user_id = $_SESSION['user_id'];

// 1. Fetch User Info (Upgraded to Prepared Statement)
$stmt = $conn->prepare("SELECT username, email, xp, bio, profile_pic, created_at FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// 2. Fetch User's Posts (Upgraded to Prepared Statement)
$stmt_posts = $conn->prepare("SELECT q.*, (SELECT COUNT(*) FROM answers WHERE question_id = q.id) as reply_count 
                              FROM questions q 
                              WHERE q.user_id = ? 
                              ORDER BY q.created_at DESC");
$stmt_posts->bind_param("i", $user_id);
$stmt_posts->execute();
$my_posts_query = $stmt_posts->get_result();
$stmt_posts->close();

// 3. Stats & Leveling Logic (Upgraded to Prepared Statements & Fixed `rank` Bug)
$stmt_rank = $conn->prepare("SELECT COUNT(*) + 1 as `rank` FROM users WHERE xp > (SELECT xp FROM users WHERE id = ?)");
$stmt_rank->bind_param("i", $user_id);
$stmt_rank->execute();
$rank_res = $stmt_rank->get_result();
$rank = ($rank_res && $rank_res->num_rows > 0) ? $rank_res->fetch_assoc()['rank'] : "N/A";
$stmt_rank->close();

$stmt_words = $conn->prepare("SELECT COUNT(*) as total FROM vocabularies WHERE user_id = ?");
$stmt_words->bind_param("i", $user_id);
$stmt_words->execute();
$words_res = $stmt_words->get_result();
$total_words = ($words_res && $words_res->num_rows > 0) ? $words_res->fetch_assoc()['total'] : 0;
$stmt_words->close();

$xp = $user['xp'] ?? 0;
$level = floor($xp / 100) + 1;
$xp_in_level = $xp % 100;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | Vocab AI Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #1e293b; --accent: #10b981; --bg: #f8fafc;
            --border: #e2e8f0; --text-muted: #64748b;
            --spring: cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        body { font-family: 'Inter', sans-serif; background: var(--bg); margin: 0; padding: 40px; color: var(--primary); }
        
        @keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .animate-in { animation: slideUp 0.6s var(--spring) forwards; opacity: 0; }

        .container { max-width: 700px; margin: 60px auto 0 auto; }
        
        /* --- PROFILE CARD --- */
        .profile-card { background: white; border-radius: 32px; padding: 40px; border: 1px solid var(--border); box-shadow: 0 10px 30px rgba(0,0,0,0.03); text-align: center; margin-bottom: 30px; }
        .avatar-container { position: relative; width: 100px; height: 100px; margin: 0 auto 20px auto; }
        .avatar-large { width: 100%; height: 100%; border-radius: 30px; object-fit: cover; background: linear-gradient(135deg, #10b981, #3b82f6); box-shadow: 0 10px 20px rgba(0,0,0,0.1); border: 4px solid white; }
        
        .bio-text { color: var(--text-muted); font-size: 0.95rem; margin: 15px auto; max-width: 450px; line-height: 1.6; }

        /* --- TABS --- */
        .tab-nav { display: flex; justify-content: center; gap: 30px; margin-bottom: 30px; border-bottom: 2px solid var(--border); }
        .tab-btn { background: none; border: none; padding: 15px 10px; font-weight: 800; font-size: 0.9rem; cursor: pointer; color: var(--text-muted); border-bottom: 3px solid transparent; transition: 0.3s; }
        .tab-btn.active { color: var(--accent); border-bottom-color: var(--accent); }

        .tab-content { display: none; }
        .tab-content.active { display: block; animation: slideUp 0.4s var(--spring) forwards; }

        /* --- STATS & POSTS --- */
        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 30px; }
        .stat-item { background: #f8fafc; padding: 20px; border-radius: 20px; border: 1px solid var(--border); transition: 0.3s; }
        .stat-item:hover { transform: translateY(-5px); border-color: var(--accent); }
        
        .post-card { background: white; border-radius: 20px; padding: 25px; border: 1px solid var(--border); margin-bottom: 15px; text-decoration: none; color: inherit; display: block; text-align: left; transition: 0.3s var(--spring); }
        .post-card:hover { transform: scale(1.02); border-color: var(--accent); box-shadow: 0 10px 25px rgba(0,0,0,0.05); }

        /* --- FORM --- */
        .edit-form { text-align: left; }
        .input-group { margin-bottom: 20px; }
        .input-group label { display: block; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; margin-bottom: 8px; color: var(--text-muted); letter-spacing: 0.05em; }
        .input-field { width: 100%; padding: 15px; border-radius: 14px; border: 2px solid var(--border); font-family: inherit; box-sizing: border-box; transition: 0.3s; }
        .input-field:focus { outline: none; border-color: var(--accent); background: #f0fdf4; }
        
        .btn-update { width: 100%; padding: 18px; background: var(--primary); color: white; border: none; border-radius: 16px; font-weight: 800; cursor: pointer; margin-top: 10px; transition: 0.3s var(--spring); }
        .btn-update:hover { background: var(--accent); transform: translateY(-3px); box-shadow: 0 10px 20px rgba(16, 185, 129, 0.2); }
        
        .file-input-wrapper { display: flex; align-items: center; gap: 15px; background: #f8fafc; padding: 10px; border-radius: 14px; border: 1px dashed var(--border); }
    </style>
</head>
<body>

    <?php include 'nav.php'; ?>

    <div class="container">
        <div class="profile-card animate-in">
            <div class="avatar-container">
                <img src="uploads/avatars/<?php echo htmlspecialchars($user['profile_pic'] ?: 'default_avatar.png'); ?>" class="avatar-large" id="headerAvatar">
            </div>
            <h1 style="margin:0; font-weight:800; font-size: 2rem;"><?php echo htmlspecialchars($user['username']); ?></h1>
            <p class="bio-text"><?php echo !empty($user['bio']) ? htmlspecialchars($user['bio']) : "Gemini explorer. Vocabulary master. No bio yet."; ?></p>
            <div style="font-size: 0.75rem; font-weight: 900; color: var(--accent); letter-spacing: 1px;">LEVEL <?php echo $level; ?> MASTER</div>
        </div>

        <div class="tab-nav animate-in" style="animation-delay: 0.1s;">
            <button class="tab-btn active" onclick="openTab(event, 'overview')">Overview & Edit</button>
            <button class="tab-btn" onclick="openTab(event, 'history')">My Discussions (<?php echo $my_posts_query->num_rows; ?>)</button>
        </div>

        <div id="overview" class="tab-content active">
            <div class="stats-grid">
                <div class="stat-item"><span style="display:block; font-size:1.6rem; font-weight:800;"><?php echo $total_words; ?></span><span style="font-size:0.65rem; font-weight:700; color:var(--text-muted);">WORDS SAVED</span></div>
                <div class="stat-item"><span style="display:block; font-size:1.6rem; font-weight:800;">#<?php echo htmlspecialchars($rank); ?></span><span style="font-size:0.65rem; font-weight:700; color:var(--text-muted);">GLOBAL RANK</span></div>
                <div class="stat-item"><span style="display:block; font-size:1.6rem; font-weight:800;"><?php echo $xp; ?></span><span style="font-size:0.65rem; font-weight:700; color:var(--text-muted);">TOTAL XP</span></div>
            </div>

            <div class="profile-card edit-form">
                <h3 style="margin-top:0; margin-bottom: 25px;">Update Profile Details</h3>
                <form action="update_profile.php" method="POST" enctype="multipart/form-data">
                    
                    <div class="input-group">
                        <label>Profile Picture</label>
                        <div class="file-input-wrapper">
                            <img src="uploads/avatars/<?php echo htmlspecialchars($user['profile_pic'] ?: 'default_avatar.png'); ?>" id="previewImg" style="width: 50px; height: 50px; border-radius: 12px; object-fit: cover;">
                            <input type="file" name="profile_pic" accept="image/*" onchange="preview(this)">
                        </div>
                    </div>

                    <div class="input-group">
                        <label>Display Name</label>
                        <input type="text" name="new_username" class="input-field" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                    </div>

                    <div class="input-group">
                        <label>Email Address</label>
                        <input type="email" name="new_email" class="input-field" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>

                    <div class="input-group">
                        <label>Personal Bio</label>
                        <textarea name="bio" class="input-field" rows="3"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                    </div>

                    <div class="input-group">
                        <label>New Password (Optional)</label>
                        <input type="password" name="new_password" class="input-field" placeholder="Leave blank to keep current">
                    </div>

                    <div class="input-group" style="margin-top: 30px; padding: 20px; background: #fffbeb; border-radius: 20px; border: 1px solid #fef3c7;">
                        <label style="color: #92400e;">Verify Identity (Current Password)</label>
                        <input type="password" name="current_password" class="input-field" style="border-color: #fde68a;" placeholder="Required to save changes" required>
                    </div>

                    <button type="submit" class="btn-update">✨ Save All Changes</button>
                </form>
            </div>
            
            <div style="text-align: center;">
                <a href="logout.php" style="color: #ef4444; text-decoration: none; font-weight: 700; font-size: 0.9rem;">Logout of Vocab AI Pro</a>
            </div>
        </div>

        <div id="history" class="tab-content">
            <?php if ($my_posts_query->num_rows > 0): ?>
                <?php while($post = $my_posts_query->fetch_assoc()): ?>
                    <a href="view_question.php?id=<?php echo $post['id']; ?>" class="post-card">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <h4 style="margin:0;"><?php echo htmlspecialchars($post['title']); ?></h4>
                            <span style="font-size: 0.7rem; background: #f1f5f9; padding: 4px 8px; border-radius: 6px; font-weight: 800;">POSTED</span>
                        </div>
                        <div style="margin-top: 10px; font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">
                            <span>📅 <?php echo date("M d, Y", strtotime($post['created_at'])); ?></span>
                            <span style="margin-left: 20px;">💬 <?php echo $post['reply_count']; ?> Comments</span>
                        </div>
                    </a>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="text-align:center; padding: 60px; color: var(--text-muted); background: white; border-radius: 30px; border: 1px solid var(--border);">
                    <p style="font-weight: 600;">You haven't joined the conversation yet.</p>
                    <a href="ask_question.php" style="color: var(--accent); font-weight: 800; text-decoration: none;">Ask your first question!</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

<script>
    // Tab Switching Logic
    function openTab(evt, tabName) {
        const contents = document.getElementsByClassName("tab-content");
        for (let i = 0; i < contents.length; i++) contents[i].classList.remove("active");
        
        const buttons = document.getElementsByClassName("tab-btn");
        for (let i = 0; i < buttons.length; i++) buttons[i].classList.remove("active");
        
        document.getElementById(tabName).classList.add("active");
        evt.currentTarget.classList.add("active");
    }

    // Image Preview Logic
    function preview(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('previewImg').src = e.target.result;
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>
</body>
</html>
