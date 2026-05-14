<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

function sendResetEmail($toEmail, $token) {
    $mail = new PHPMailer(true);

    try {
        // --- CẤU HÌNH SERVER SMTP ---
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; // Hoặc SMTP server của bạn
        $mail->SMTPAuth   = true;
        $mail->Username   = 'gphat025@gmail.com'; // Email gửi thư
        $mail->Password   = 'dziq fkvq ppey ubei';     // Mật khẩu ứng dụng (App Password)
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        // --- NGƯỜI GỬI & NGƯỜI NHẬN ---
        $mail->setFrom('noreply@study4ever.site', 'Vocab AI Pro Support');
        $mail->addAddress($toEmail);

        // --- NỘI DUNG EMAIL ---
        $resetLink = "https://study4ever.site/reset_password.php?token=" . $token;
        
        $mail->isHTML(true);
        $mail->Subject = 'Khôi phục mật khẩu - Vocab AI Pro';
        $mail->Body    = "
            <div style='font-family: Arial, sans-serif; padding: 20px; border: 1px solid #e2e8f0; border-radius: 10px;'>
                <h2 style='color: #10b981;'>Yêu cầu khôi phục mật khẩu</h2>
                <p>Chào bạn, chúng tôi nhận được yêu cầu khôi phục mật khẩu cho tài khoản của bạn tại <b>Vocab AI Pro</b>.</p>
                <p>Vui lòng nhấn vào nút dưới đây để thiết lập mật khẩu mới (Liên kết có hiệu lực trong 60 phút):</p>
                <a href='$resetLink' style='display: inline-block; padding: 12px 25px; background: #1e293b; color: #ffffff; text-decoration: none; border-radius: 8px; font-weight: bold;'>Đổi mật khẩu ngay</a>
                <p style='margin-top: 20px; color: #64748b; font-size: 0.8rem;'>Nếu bạn không thực hiện yêu cầu này, hãy bỏ qua email này.</p>
            </div>";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Lỗi gửi mail: {$mail->ErrorInfo}");
        return false;
    }
}
