<?php
// Nạp file kết nối database
include 'db.php';

// Khởi động session để lấy user đang đăng nhập
session_start();

// Nếu user chưa đăng nhập thì chặn request
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die("Unauthorized");
}

// Ép user_id về số nguyên để tránh lỗi bảo mật
$user_id = (int) $_SESSION['user_id'];

// Lấy số XP được gửi từ frontend
$amount = isset($_POST['amount']) ? (int) $_POST['amount'] : 0;

// Danh sách XP hợp lệ được phép cộng
// Hiện tại hệ thống chỉ cho cộng 50 XP để chống gian lận
$allowed_amounts = [5,10,15,50];

// Nếu amount không nằm trong danh sách hợp lệ thì từ chối
if (!in_array($amount, $allowed_amounts, true)) {
    http_response_code(400);
    die("Invalid XP amount");
}

// Chuẩn bị câu SQL cộng XP
// xp: giữ tương thích với code cũ
// weekly_xp: dùng cho leaderboard tuần
// total_xp: tổng XP lâu dài của user
$sql = "
    UPDATE users
    SET 
        xp = xp + ?,
        weekly_xp = weekly_xp + ?,
        total_xp = total_xp + ?
    WHERE id = ?
";

// Chuẩn bị statement để tránh SQL injection
$stmt = $conn->prepare($sql);

// Nếu prepare thất bại thì báo lỗi server
if (!$stmt) {
    http_response_code(500);
    die("Prepare failed");
}

// Gắn dữ liệu vào SQL
// Có 4 số nguyên: amount, amount, amount, user_id
$stmt->bind_param("iiii", $amount, $amount, $amount, $user_id);

// Chạy câu lệnh cập nhật XP
if ($stmt->execute()) {
    echo "Success";
} else {
    http_response_code(500);
    echo "Failed";
}

// Đóng statement sau khi dùng xong
$stmt->close();
?>
