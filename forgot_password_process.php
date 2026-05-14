<?php
include 'db.php';
include 'mail_helper.php'; // Nạp thư viện gửi mail đã tạo ở Bước 3
session_start();

// Chỉ chấp nhận yêu cầu gửi từ Form (POST)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);

    // Kiểm tra dữ liệu đầu vào
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: forgot_password.php?status=invalid_email");
        exit();
    }

    // 1. Kiểm tra xem email có tồn tại trong hệ thống Vocab AI Pro không
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // 2. Tạo Token bảo mật ngẫu nhiên (64 ký tự)
        $token = bin2hex(random_bytes(32));

        // 3. Quản lý Token trong Database
        // Xóa các yêu cầu cũ của email này để tránh rác dữ liệu
        $del = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
        $del->bind_param("s", $email);
        $del->execute();

        // Lưu token mới vào bảng password_resets
        $ins = $conn->prepare("INSERT INTO password_resets (email, token) VALUES (?, ?)");
        $ins->bind_param("ss", $email, $token);
        
        if ($ins->execute()) {
            // 4. GỌI HÀM GỬI EMAIL TỪ THƯ VIỆN mail_helper.php
            if (sendResetEmail($email, $token)) {
                // Gửi thành công
                header("Location: forgot_password.php?status=success");
            } else {
                // Lỗi cấu hình SMTP (như sai mật khẩu email gửi hoặc port bị chặn)
                header("Location: forgot_password.php?status=mail_error");
            }
        } else {
            header("Location: forgot_password.php?status=error");
        }
    } else {
        // Về mặt bảo mật: Vẫn báo success để tránh bị kẻ xấu dò tìm email người dùng
        header("Location: forgot_password.php?status=success");
    }
    
    $stmt->close();
    $conn->close();
    exit();
} else {
    // Nếu truy cập trực tiếp file này mà không qua Form, đẩy về trang quên mật khẩu
    header("Location: forgot_password.php");
    exit();
}
