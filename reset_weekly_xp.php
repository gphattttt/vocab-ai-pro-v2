<?php
// File này dùng để reset XP tuần cho leaderboard.
// Nên chạy bằng cron job mỗi thứ Hai lúc 00:00.
// Lưu ý: file này chỉ reset weekly_xp, không reset total_xp và không reset xp.

// Chỉ cho phép chạy file này bằng PHP CLI.
// Điều này giúp tránh việc người khác mở URL trên trình duyệt để reset XP.
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die("Forbidden");
}

// Nạp file kết nối database.
include __DIR__ . '/db.php';

// Lấy thời gian hiện tại để ghi log.
$now = date('Y-m-d H:i:s');

// Câu SQL reset toàn bộ XP tuần về 0.
$sql = "
    UPDATE users
    SET weekly_xp = 0
";

// Chạy câu SQL reset.
if ($conn->query($sql)) {
    // In log thành công ra terminal hoặc file log cron.
    echo "[" . $now . "] Weekly XP reset successfully.\n";
    echo "Affected users: " . $conn->affected_rows . "\n";
} else {
    // In log lỗi nếu reset thất bại.
    echo "[" . $now . "] Weekly XP reset failed.\n";
    echo "Error: " . $conn->error . "\n";

    // Trả exit code 1 để cron/log biết là có lỗi.
    exit(1);
}

// Đóng kết nối database sau khi chạy xong.
$conn->close();

// Trả exit code 0 nghĩa là chạy thành công.
exit(0);
?>
