<?php
include 'db.php';
session_start();

// --- TỰ ĐỘNG ĐIỀU HƯỚNG NẾU ĐÃ ĐĂNG NHẬP ---
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = "";

// --- LOGIC ĐĂNG NHẬP TRUYỀN THỐNG ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['username'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, password FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($user = $result->fetch_assoc()) {
        if (password_verify($password, $user['password'])) {
            
            // Bảo mật: Chống tấn công Session Fixation
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $username;
            
            $stmt->close();
            header("Location: index.php");
            exit();
        } else {
            $error = "Mật khẩu không chính xác. Vui lòng thử lại!";
        }
    } else {
        $error = "Không tìm thấy tên người dùng này.";
    }
    
    if (isset($stmt)) {
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập | Vocab AI Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://accounts.google.com/gsi/client" async defer></script>
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
            display: flex; 
            align-items: center; 
            justify-content: center; 
            min-height: 100vh; 
            margin: 0;
            color: var(--primary);
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .login-card {
            background: white;
            padding: 50px; 
            border-radius: 40px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.03); 
            border: 1px solid var(--border);
            width: 100%; 
            max-width: 420px; 
            text-align: center;
            animation: slideUp 0.6s var(--transition) forwards;
        }

        .logo-mark {
            width: 65px;
            height: 65px; 
            background: var(--primary); 
            color: white;
            border-radius: 20px; 
            display: flex; 
            align-items: center; 
            justify-content: center;
            font-weight: 900; 
            font-size: 2rem;
            margin: 0 auto 30px auto;
            box-shadow: 0 10px 20px rgba(30, 41, 59, 0.2);
        }

        h1 { 
            font-weight: 800; 
            margin-bottom: 10px;
            font-size: 2rem; 
            letter-spacing: -1px;
        }
        
        p { 
            color: var(--text-muted); 
            font-weight: 500; 
            margin-bottom: 40px; 
            font-size: 1rem;
        }

        .input-group { 
            text-align: left; 
            margin-bottom: 25px; 
            position: relative;
        }
        
        .input-group label { 
            display: block; 
            font-size: 0.75rem; 
            font-weight: 800; 
            text-transform: uppercase;
            margin-bottom: 10px; 
            color: var(--text-muted); 
            letter-spacing: 0.05em;
        }
        
        input {
            width: 100%;
            padding: 16px; 
            border: 2px solid var(--border);
            border-radius: 16px; 
            font-size: 1rem; 
            box-sizing: border-box;
            font-family: inherit; 
            transition: 0.3s var(--transition);
        }
        
        input:focus { 
            border-color: var(--accent); 
            outline: none; 
            background: #f0fdf4; 
        }

        /* --- QUÊN MẬT KHẨU --- */
        .forgot-pass {
            display: block;
            text-align: right;
            margin-top: 8px;
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--text-muted);
            text-decoration: none;
            transition: 0.2s;
        }
        .forgot-pass:hover {
            color: var(--accent);
        }

        .btn-login {
            width: 100%;
            padding: 20px; 
            background: var(--primary); 
            color: white;
            border: none; 
            border-radius: 16px; 
            font-weight: 800; 
            font-size: 1.05rem;
            cursor: pointer; 
            transition: 0.3s var(--transition);
            margin-top: 15px;
        }
        
        .btn-login:hover { 
            transform: translateY(-3px); 
            box-shadow: 0 15px 30px rgba(30, 41, 59, 0.15);
            background: #0f172a; 
        }

        .divider { 
            display: flex; 
            align-items: center; 
            margin: 30px 0;
            color: var(--text-muted); 
            font-size: 0.75rem; 
            font-weight: 800; 
            text-transform: uppercase; 
            letter-spacing: 0.1em;
        }
        
        .divider::before, .divider::after { 
            content: "";
            flex: 1; 
            height: 1px; 
            background: var(--border); 
        }
        
        .divider::before { margin-right: 20px; }
        .divider::after { margin-left: 20px; }

        .google-btn-wrapper { 
            display: flex; 
            justify-content: center; 
            margin-bottom: 10px; 
        }

        .error-msg {
            background: #fef2f2;
            color: #ef4444; 
            padding: 14px; 
            border-radius: 14px;
            font-size: 0.9rem; 
            font-weight: 600; 
            margin-bottom: 30px; 
            border: 1px solid #fee2e2;
            animation: slideUp 0.4s var(--transition);
        }

        .footer-link { 
            margin-top: 40px; 
            font-size: 0.95rem; 
            font-weight: 600; 
            color: var(--text-muted); 
        }
        
        .footer-link a { 
            color: var(--accent); 
            text-decoration: none; 
            font-weight: 800; 
            transition: 0.2s;
        }
        
        .footer-link a:hover {
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            .login-card { padding: 35px 25px; border-radius: 0; height: 100vh; max-width: 100%; border: none; }
        }
    </style>
</head>
<body>

    <div class="login-card">
        <div class="logo-mark">V</div>
        <h1>Chào mừng trở lại</h1>
        <p>Kho từ vựng của bạn đang chờ đợi.</p>

        <?php if($error): ?>
            <div class="error-msg"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="google-btn-wrapper">
            <div id="g_id_onload"
                 data-client_id="75314981645-vrtmtb1odk0k1btcvgv3gofg8udd8bsc.apps.googleusercontent.com"
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

        <div class="divider">hoặc dùng Email</div>

        <form method="POST">
            <div class="input-group">
                <label>Tên đăng nhập</label>
                <input type="text" name="username" placeholder="Nhập tên đăng nhập của bạn" required autofocus>
            </div>

            <div class="input-group">
                <label>Mật khẩu</label>
                <input type="password" name="password" placeholder="••••••••" required>
                <a href="forgot_password.php" class="forgot-pass">Quên mật khẩu?</a>
            </div>

            <button type="submit" class="btn-login">Mở kho từ vựng</button>
        </form>

        <div class="footer-link">
            Bạn mới đến đây? <a href="register.php">Tạo tài khoản ngay</a>
        </div>
    </div>

</body>
</html>
