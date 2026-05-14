<?php
include 'db.php';
session_start();

// Điều hướng nếu người dùng đã đăng nhập
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Lấy thông báo dựa trên tham số 'status' từ URL
$message = "";
$message_type = ""; 

if (isset($_GET['status'])) {
    switch ($_GET['status']) {
        case 'success':
            $message = "Yêu cầu thành công! Nếu email tồn tại, bạn sẽ nhận được hướng dẫn khôi phục.";
            $message_type = "success";
            break;
        case 'invalid_email':
            $message = "Vui lòng nhập địa chỉ email hợp lệ.";
            $message_type = "error";
            break;
        case 'mail_error':
            $message = "Lỗi hệ thống gửi thư. Vui lòng thử lại sau.";
            $message_type = "error";
            break;
        case 'error':
            $message = "Đã có lỗi xảy ra trong quá trình xử lý.";
            $message_type = "error";
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Khôi phục mật khẩu | Vocab AI Pro</title>
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

        .auth-card {
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

        .icon-box {
            width: 65px;
            height: 65px; 
            background: #f1f5f9; 
            color: var(--primary);
            border-radius: 20px; 
            display: flex; 
            align-items: center; 
            justify-content: center;
            font-size: 1.8rem;
            margin: 0 auto 30px auto;
        }

        h1 { 
            font-weight: 800; 
            margin-bottom: 10px;
            font-size: 1.8rem; 
            letter-spacing: -1px;
        }
        
        p { 
            color: var(--text-muted); 
            font-weight: 500; 
            margin-bottom: 35px; 
            font-size: 0.95rem;
            line-height: 1.6;
        }

        .input-group { 
            text-align: left; 
            margin-bottom: 25px; 
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

        .btn-submit {
            width: 100%;
            padding: 20px; 
            background: var(--primary); 
            color: white;
            border: none; 
            border-radius: 16px; 
            font-weight: 800; 
            font-size: 1rem;
            cursor: pointer; 
            transition: 0.3s var(--transition);
            margin-top: 10px;
        }
        
        .btn-submit:hover { 
            transform: translateY(-3px); 
            box-shadow: 0 15px 30px rgba(30, 41, 59, 0.15);
            background: #0f172a; 
        }

        .msg { 
            padding: 15px; 
            border-radius: 16px; 
            font-size: 0.9rem; 
            font-weight: 600;
            margin-bottom: 25px; 
            border: 1px solid;
            animation: slideUp 0.4s var(--transition);
        }
        .msg.success { background: #f0fdf4; color: #10b981; border-color: #dcfce7; }
        .msg.error { background: #fef2f2; color: #ef4444; border-color: #fee2e2; }

        .footer-link { 
            margin-top: 35px; 
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
            .auth-card { padding: 35px 25px; border-radius: 0; height: 100vh; max-width: 100%; border: none; }
        }
    </style>
</head>
<body>

    <div class="auth-card">
        <div class="icon-box">🔑</div>
        <h1>Quên mật khẩu?</h1>
        <p>Nhập email của bạn và chúng tôi sẽ gửi hướng dẫn khôi phục mật khẩu vào hòm thư.</p>

        <?php if($message): ?>
            <div class="msg <?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form action="forgot_password_process.php" method="POST">
            <div class="input-group">
                <label>Địa chỉ Email</label>
                <input type="email" name="email" placeholder="name@example.com" required autofocus>
            </div>

            <button type="submit" class="btn-submit">Gửi yêu cầu khôi phục</button>
        </form>

        <div class="footer-link">
            Nhớ ra mật khẩu rồi? <a href="login.php">Quay lại Đăng nhập</a>
        </div>
    </div>

</body>
</html>
