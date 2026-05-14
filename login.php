<?php
include 'db.php';
session_start();

$error = "";

// --- 1. TRADITIONAL LOGIN LOGIC ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['username'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, password FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $username;
            header("Location: index.php");
            exit();
        } else {
            $error = "Invalid password. Try again!";
        }
    } else {
        $error = "User not found. Want to register?";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Vocab AI Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <style>
        :root {
            --primary: #1e293b; --accent: #10b981; --bg: #f8fafc;
            --border: #e2e8f0; --text-muted: #64748b;
            --spring: cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        body { 
            font-family: 'Inter', sans-serif; background: var(--bg); 
            display: flex; align-items: center; justify-content: center; 
            min-height: 100vh; margin: 0; 
        }

        @keyframes springIn {
            from { opacity: 0; transform: scale(0.9) translateY(30px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }

        .login-card {
            background: white; padding: 50px; border-radius: 40px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.05); border: 1px solid var(--border);
            width: 100%; max-width: 420px; text-align: center;
            animation: springIn 0.8s var(--spring) forwards;
        }

        .logo-mark {
            width: 60px; height: 60px; background: var(--primary); color: white;
            border-radius: 18px; display: flex; align-items: center; justify-content: center;
            font-weight: 900; font-size: 1.8rem; margin: 0 auto 25px auto;
        }

        h1 { font-weight: 800; margin-bottom: 8px; font-size: 1.8rem; }
        p { color: var(--text-muted); font-weight: 600; margin-bottom: 35px; }

        .input-group { text-align: left; margin-bottom: 20px; }
        .input-group label { display: block; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; margin-bottom: 8px; color: var(--text-muted); }
        
        input {
            width: 100%; padding: 15px; border: 2px solid var(--border);
            border-radius: 14px; font-size: 1rem; box-sizing: border-box;
            font-family: inherit; transition: 0.3s;
        }
        input:focus { border-color: var(--accent); outline: none; background: #f0fdf4; }

        .btn-login {
            width: 100%; padding: 18px; background: var(--primary); color: white;
            border: none; border-radius: 14px; font-weight: 800; font-size: 1rem;
            cursor: pointer; transition: 0.3s var(--spring); margin-top: 10px;
        }
        .btn-login:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); background: #334155; }

        .divider { display: flex; align-items: center; margin: 25px 0; color: var(--text-muted); font-size: 0.8rem; font-weight: 700; text-transform: uppercase; }
        .divider::before, .divider::after { content: ""; flex: 1; height: 1px; background: var(--border); }
        .divider::before { margin-right: 15px; }
        .divider::after { margin-left: 15px; }

        .google-btn-wrapper { display: flex; justify-content: center; margin-bottom: 20px; }

        .error-msg {
            background: #fef2f2; color: #ef4444; padding: 12px; border-radius: 12px;
            font-size: 0.85rem; font-weight: 600; margin-bottom: 25px; border: 1px solid #fee2e2;
        }

        .footer-link { margin-top: 30px; font-size: 0.9rem; font-weight: 600; color: var(--text-muted); }
        .footer-link a { color: var(--accent); text-decoration: none; font-weight: 800; }
    </style>
</head>
<body>

    <div class="login-card">
        <div class="logo-mark">V</div>
        <h1>Welcome Back</h1>
        <p>Your vault is waiting for you.</p>

        <?php if($error): ?>
            <div class="error-msg"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="google-btn-wrapper">
            <div id="g_id_onload"
                 data-client_id="75314981645-jkjcti4hh2rf0vkubcnb1h6nca8h2a2i.apps.googleusercontent.com"
                 data-context="signin"
                 data-ux_mode="redirect"
                 data-login_uri="https://study4ever.site/google-callback.php"
                 data-auto_prompt="false">
            </div>

            <div class="g_id_signin"
                 data-type="standard"
                 data-shape="rectangular"
                 data-theme="outline"
                 data-text="signin_with"
                 data-size="large"
                 data-logo_alignment="left"
                 data-width="320">
            </div>
        </div>

        <div class="divider">or use email</div>

        <form method="POST">
            <div class="input-group">
                <label>Username</label>
                <input type="text" name="username" placeholder="Enter your username" required autofocus>
            </div>

            <div class="input-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn-login">Open Vault</button>
        </form>

        <div class="footer-link">
            New here? <a href="register.php">Create an Account</a>
        </div>
    </div>

</body>
</html>
