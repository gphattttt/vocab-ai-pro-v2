<?php
include 'db.php';
session_start();

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // 1. Check if username or email already exists
    $check = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $check->bind_param("ss", $username, $email);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        $error = "Username or Email already taken!";
    } else {
        // 2. Hash password and insert
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, xp) VALUES (?, ?, ?, 0)");
        $stmt->bind_param("sss", $username, $email, $hashed_password);
        
        if ($stmt->execute()) {
            $success = "Account created! Redirecting to login...";
            header("refresh:2;url=login.php");
        } else {
            $error = "Something went wrong. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join the Vault | Vocab AI Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #1e293b; --accent: #10b981; --bg: #f1f5f9;
            --border: #e2e8f0; --text-muted: #64748b;
            --spring: cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        body { 
            font-family: 'Inter', sans-serif; background: var(--bg); 
            margin: 0; display: flex; align-items: center; justify-content: center; 
            min-height: 100vh; color: var(--primary); padding: 20px;
        }

        @keyframes popIn {
            from { opacity: 0; transform: scale(0.9) translateY(20px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }

        .auth-card {
            background: white; padding: 40px; border-radius: 35px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.05); border: 1px solid var(--border);
            width: 100%; max-width: 420px; text-align: center;
            animation: popIn 0.6s var(--spring) forwards;
        }

        .logo-mark {
            width: 50px; height: 50px; background: var(--accent); color: white;
            border-radius: 15px; display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem; font-weight: 800; margin: 0 auto 20px auto;
        }

        h1 { font-size: 1.8rem; font-weight: 800; margin: 0 0 8px 0; }
        p { color: var(--text-muted); font-weight: 500; margin-bottom: 30px; font-size: 0.9rem; }

        .input-group { text-align: left; margin-bottom: 18px; }
        .input-group label { display: block; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; margin-bottom: 6px; color: var(--text-muted); }
        
        input {
            width: 100%; padding: 14px; border: 2px solid var(--border);
            border-radius: 14px; font-size: 0.95rem; box-sizing: border-box;
            font-family: inherit; transition: 0.3s;
        }
        input:focus { outline: none; border-color: var(--accent); background: #f0fdf4; }

        .btn-reg {
            width: 100%; padding: 16px; background: var(--primary); color: white;
            border: none; border-radius: 14px; font-weight: 800; font-size: 1rem;
            cursor: pointer; transition: 0.3s var(--spring); margin-top: 10px;
        }
        .btn-reg:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); background: #334155; }

        .msg { padding: 12px; border-radius: 12px; font-size: 0.85rem; font-weight: 600; margin-bottom: 20px; border: 1px solid; }
        .error { background: #fef2f2; color: #ef4444; border-color: #fee2e2; }
        .success { background: #f0fdf4; color: #10b981; border-color: #dcfce7; }

        .footer-link { margin-top: 25px; font-size: 0.85rem; font-weight: 600; color: var(--text-muted); }
        .footer-link a { color: var(--primary); text-decoration: none; font-weight: 800; border-bottom: 2px solid var(--accent); }
    </style>
</head>
<body>

    <div class="auth-card">
        <div class="logo-mark">V+</div>
        <h1>Create Account</h1>
        <p>Start your journey to vocabulary mastery.</p>

        <?php if($error): ?>
            <div class="msg error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if($success): ?>
            <div class="msg success"><?php echo $success; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="input-group">
                <label>Username</label>
                <input type="text" name="username" placeholder="Pick a unique name" required>
            </div>

            <div class="input-group">
                <label>Email Address</label>
                <input type="email" name="email" placeholder="name@example.com" required>
            </div>

            <div class="input-group">
                <label>Choose Password</label>
                <input type="password" name="password" placeholder="Min. 8 characters" required>
            </div>

            <button type="submit" class="btn-reg">Join the Vault</button>
        </form>

        <div class="footer-link">
            Already a member? <a href="login.php">Sign in here</a>
        </div>
    </div>

</body>
</html>