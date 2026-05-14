<?php
include 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch top 10 players by XP
$sql = "SELECT username, xp FROM users ORDER BY xp DESC LIMIT 10";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hall of Fame | Vocab AI Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #1e293b; --accent: #10b981; --bg: #f8fafc;
            --border: #e2e8f0; --text-muted: #64748b;
            --spring: cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        body { font-family: 'Inter', sans-serif; background: var(--bg); margin: 0; padding: 40px; color: var(--primary); }

        /* --- STAGGERED ENTRANCE --- */
        @keyframes slideIn {
            from { opacity: 0; transform: translateX(-30px); }
            to { opacity: 1; transform: translateX(0); }
        }
        .animate-in { animation: slideIn 0.5s var(--spring) forwards; opacity: 0; }

        .container { max-width: 600px; margin: 60px auto 0 auto; }

        h1 { font-size: 2.5rem; font-weight: 800; text-align: center; margin-bottom: 40px; }

        /* --- RANK CARDS --- */
        .rank-card {
            background: white; border-radius: 20px; padding: 20px 30px;
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 12px; border: 1px solid var(--border);
            transition: 0.3s var(--spring);
        }
        .rank-card:hover { transform: scale(1.02); border-color: var(--accent); box-shadow: 0 10px 20px rgba(0,0,0,0.03); }

        .rank-info { display: flex; align-items: center; gap: 20px; }
        .rank-number { font-size: 1.2rem; font-weight: 800; color: var(--text-muted); width: 30px; }
        .avatar { 
            width: 45px; height: 45px; border-radius: 14px; 
            background: #e2e8f0; display: flex; align-items: center; justify-content: center;
            font-weight: 800; color: var(--primary);
        }

        .username { font-weight: 700; font-size: 1.1rem; }
        .xp-pill { 
            background: #f1f5f9; color: var(--primary); padding: 6px 12px; 
            border-radius: 10px; font-size: 0.85rem; font-weight: 800;
        }

        /* --- TOP 3 SPECIAL STYLING --- */
        .rank-1 { background: linear-gradient(to right, #fffdf2, #ffffff); border-color: #fde047; }
        .rank-1 .rank-number { color: #eab308; }
        .rank-1 .avatar { background: #fde047; color: #854d0e; }
        .rank-1 .xp-pill { background: #fef9c3; color: #854d0e; }

        .rank-2 { border-color: #cbd5e1; }
        .rank-2 .rank-number { color: #64748b; }
        .rank-2 .avatar { background: #cbd5e1; color: #1e293b; }

        .rank-3 { border-color: #f9731633; }
        .rank-3 .rank-number { color: #ea580c; }
        .rank-3 .avatar { background: #ffedd5; color: #ea580c; }
    </style>
</head>
<body>

    <?php include 'nav.php'; ?>

    <div class="container">
        <h1>Hall of Fame 🏆</h1>

        <div id="leaderboardList">
            <?php 
            $i = 1;
            $delay = 0.1;
            while($row = $result->fetch_assoc()): 
                $rank_class = ($i <= 3) ? "rank-$i" : "";
            ?>
                <div class="rank-card animate-in <?php echo $rank_class; ?>" style="animation-delay: <?php echo $delay; ?>s;">
                    <div class="rank-info">
                        <span class="rank-number"><?php echo $i; ?></span>
                        <div class="avatar"><?php echo strtoupper(substr($row['username'], 0, 1)); ?></div>
                        <span class="username"><?php echo htmlspecialchars($row['username']); ?></span>
                    </div>
                    <span class="xp-pill"><?php echo number_format($row['xp']); ?> XP</span>
                </div>
            <?php 
                $i++; 
                $delay += 0.08; 
                endwhile; 
            ?>
        </div>
    </div>

</body>
</html>