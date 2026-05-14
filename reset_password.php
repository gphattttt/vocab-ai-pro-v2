<?php
include 'db.php';
session_start();

$message = "";
$message_type = "";
$token = isset($_GET['token']) ? $_GET['token'] : "";

// 1. KIỂM TRA TOKEN HỢP LỆ
$valid_token = false;
$email = "";

if (!empty($token)) {
    $stmt = $conn->prepare("SELECT email, created_at FROM password_resets WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($row = $res->fetch_assoc()) {
        // Kiểm tra thời hạn token (ví dụ: 60 phút)
        $created_at = strtotime($row['created_at']);
        if (time() - $created_at < 3600) {
            $valid_token = true;
            $email = $row['email'];
        } else {
            $message = "Liên kết đã hết hạn. Vui lòng yêu cầu lại.";
            $message_type = "error";
        }
    } else {
        $message = "Liên kết không hợp lệ.";
        $message_type = "error";
    }
    $stmt->close();
}

// 2. XỬ LÝ CẬP NHẬT MẬT KHẨU
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $valid_token) {
    $new_pass = $_POST['password'];
    $confirm_pass = $_POST['confirm_password'];

    if ($new_pass === $confirm_pass) {
        if (strlen($new_pass) >= 8) {
            $hashed_password = password_hash($new_pass, PASSWORD_BCRYPT); // 
            
            // Cập nhật vào bảng users
            $upd = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
            $upd->bind_param("ss", $hashed_password, $email);
            
            if ($upd->execute()) {
                // Xóa token đã sử dụng
                $del = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
                $del->bind_param("s", $email);
                $del->execute();

                $message = "Mật khẩu đã được cập nhật thành công! Đang chuyển hướng...";
                $message_type = "success";
                header("refresh:3;url=login.php");
            } else {
                $message = "Đã có lỗi xảy ra. Thử lại sau.";
                $message_type = "error";
            }
        } else {
            $message = "Mật khẩu phải có ít nhất 8 ký tự.";
            $message_type = "error";
        }
    } else {
        $message = "Mật khẩu xác nhận không khớp.";
        $message_type = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt lại mật khẩu | Vocab AI Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #1e293b; --accent: #10b981; --bg: #f8fafc;
            --border: #e2e8f0; --text-muted: #64748b;
            --transition: cubic-bezier(0.4, 0, 0.2, 1);
        }
        body { font-family: 'Be Vietnam Pro', sans-serif; background: var(--bg); display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
        @keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .auth-card { background: white; padding: 50px; border-radius: 40px; box-shadow: 0 20px 50px rgba(0,0,0,0.03); border: 1px solid var(--border); width: 100%; max-width: 420px; text-align: center; animation: slideUp 0.6s var(--transition) forwards; }
        h1 { font-weight: 800; margin-bottom: 10px; font-size: 1.8rem; letter-spacing: -1px; }
        p { color: var(--text-muted); font-weight: 500; margin-bottom: 35px; font-size: 0.95rem; }
        .input-group { text-align: left; margin-bottom: 25px; }
        .input-group label { display: block; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; margin-bottom: 10px; color: var(--text-muted); letter-spacing: 0.05em; }
        input { width: 100%; padding: 16px; border: 2px solid var(--border); border-radius: 16px; font-size: 1rem; box-sizing: border-box; font-family: inherit; transition: 0.3s; }
        input:focus { border-color: var(--accent); outline: none; background: #f0fdf4; }
        .btn-submit { width: 100%; padding: 20px; background: var(--primary); color: white; border: none; border-radius: 16px; font-weight: 800; cursor: pointer; transition: 0.3s; margin-top: 10px; }
        .btn-submit:hover { transform: translateY(-3px); box-shadow: 0 15px 30px rgba(30, 41, 59, 0.15); background: #0f172a; }
        .msg { padding: 15px; border-radius: 16px; font-size: 0.9rem; font-weight: 600; margin-bottom: 25px; border: 1px solid; animation: slideUp 0.4s var(--transition); }
        .msg.success { background: #f0fdf4; color: #10b981; border-color: #dcfce7; }
        .msg.error { background: #fef2f2; color: #ef4444; border-color: #fee2e2; }
    </style>
</head>
<body>
    <div class="auth-card">
        <h1>Mật khẩu mới</h1>
        <p>Vui lòng nhập mật khẩu mới cho tài khoản của bạn.</p>

        <?php if($message): ?>
            <div class="msg <?php echo $message_type; ?>"><?php echo $message; ?></div>
        <?php endif; ?>

        <?php if($valid_token): ?>
            <form method="POST">
                <div class="input-group">
                    <label>Mật khẩu mới</label>
                    <input type="password" name="password" placeholder="Tối thiểu 8 ký tự" required autofocus>
                </div>
                <div class="input-group">
                    <label>Xác nhận mật khẩu</label>
                    <input type="password" name="confirm_password" placeholder="••••••••" required>
                </div>
                <button type="submit" class="btn-submit">Cập nhật mật khẩu</button>
            </form>
        <?php else: ?>
            <a href="forgot_password.php" style="color: var(--accent); font-weight: 800; text-decoration: none;">Quay lại yêu cầu mới</a>
        <?php endif; ?>
    </div>
</body>
</html>
