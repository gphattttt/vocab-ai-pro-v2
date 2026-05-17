<?php

// =========================================================
// essay_extract_proxy.php
// Vocab AI Pro - Secure Proxy Layer
//
// Chức năng:
// - Kiểm tra đăng nhập
// - Giới hạn 15 requests/ngày
// - Validate dữ liệu
// - Forward request tới Flask AI service
// - Ẩn AI backend khỏi public internet
//
// Flask service:
// http://127.0.0.1:5003/extract
// =========================================================

// =========================================================
// LOAD DATABASE
// =========================================================

include 'db.php';

session_start();
// DEBUG: kiểm tra proxy có được gọi không
// DEBUG: kiểm tra proxy được gọi bằng method nào
error_log("DEBUG essay_extract_proxy.php called with method: " . $_SERVER['REQUEST_METHOD']);
// Luôn trả JSON
header('Content-Type: application/json');

// =========================================================
// CHECK LOGIN
// =========================================================

if (!isset($_SESSION['user_id'])) {

    echo json_encode([
        "success" => false,
        "error" => "Unauthorized"
    ]);

    exit();
}

// =========================================================
// CHỈ CHO PHÉP POST
// =========================================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    echo json_encode([
        "success" => false,
        "error" => "Method not allowed"
    ]);

    exit();
}

// =========================================================
// USER INFO
// =========================================================

$user_id = (int) $_SESSION['user_id'];

// =========================================================
// ĐỌC RAW JSON INPUT
// =========================================================

$raw_input = file_get_contents('php://input');

$data = json_decode($raw_input, true);

// =========================================================
// VALIDATE JSON
// =========================================================

if (!$data || !is_array($data)) {

    echo json_encode([
        "success" => false,
        "error" => "Invalid JSON payload"
    ]);

    exit();
}

// =========================================================
// LẤY DỮ LIỆU
// =========================================================

$essay = trim($data['essay'] ?? '');

$requirement = trim(
    $data['requirement'] ?? 'Advanced academic vocabulary'
);

// =========================================================
// VALIDATE ESSAY
// =========================================================

// Không cho essay rỗng
if ($essay === '') {

    echo json_encode([
        "success" => false,
        "error" => "Essay is empty"
    ]);

    exit();
}

// Giới hạn độ dài essay
if (mb_strlen($essay) > 15000) {

    echo json_encode([
        "success" => false,
        "error" => "Essay too long"
    ]);

    exit();
}

// Essay quá ngắn thì AI extract vô nghĩa
if (mb_strlen($essay) < 30) {

    echo json_encode([
        "success" => false,
        "error" => "Bài văn quá ngắn."
    ]);

    exit();
}

// =========================================================
// DAILY RATE LIMIT
// =========================================================

/*
|--------------------------------------------------------------------------
| Mỗi user chỉ được:
| 15 requests / ngày
|--------------------------------------------------------------------------
*/

$limit_sql = "
    SELECT COUNT(*) AS total
    FROM essay_extract_logs
    WHERE user_id = ?
    AND DATE(created_at) = CURDATE()
";

$limit_stmt = $conn->prepare($limit_sql);

$limit_stmt->bind_param("i", $user_id);

$limit_stmt->execute();

$limit_result = $limit_stmt->get_result();

$limit_row = $limit_result->fetch_assoc();

$today_requests = (int) ($limit_row['total'] ?? 0);

// =========================================================
// BLOCK NẾU QUÁ LIMIT
// =========================================================

if ($today_requests >= 15) {

    echo json_encode([
        "success" => false,
        "error" => "Daily limit reached",
        "message" => "Bạn đã dùng hết 15 lượt extract hôm nay."
    ]);

    exit();
}

// =========================================================
// GHI LOG REQUEST MỚI
// =========================================================

/*
|--------------------------------------------------------------------------
| Chỉ log khi request hợp lệ
|--------------------------------------------------------------------------
*/

$log_sql = "
    INSERT INTO essay_extract_logs (user_id)
    VALUES (?)
";

$log_stmt = $conn->prepare($log_sql);

$log_stmt->bind_param("i", $user_id);

$log_stmt->execute();

// =========================================================
// PREPARE PAYLOAD CHO FLASK
// =========================================================

$payload = [
    "essay" => $essay,
    "requirement" => $requirement
];

// =========================================================
// FLASK API URL
// =========================================================

$flask_url = "http://127.0.0.1:5003/extract";

// =========================================================
// CURL REQUEST
// =========================================================
// DEBUG: proxy đã vượt qua validate + rate limit, chuẩn bị gọi Flask
error_log("DEBUG essay_extract_proxy.php is calling Flask at: " . $flask_url);
$ch = curl_init($flask_url);

curl_setopt_array($ch, [

    // POST request
    CURLOPT_POST => true,

    // Trả response dưới dạng string
    CURLOPT_RETURNTRANSFER => true,

    // Timeout để tránh treo request PHP
    CURLOPT_TIMEOUT => 90,

    // JSON payload
    CURLOPT_POSTFIELDS => json_encode($payload),

    // Headers
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json'
    ]
]);

// =========================================================
// EXECUTE CURL
// =========================================================

$response = curl_exec($ch);
// DEBUG: xem Flask trả gì về proxy
error_log("DEBUG Flask raw response: " . substr((string)$response, 0, 1000));
// =========================================================
// CURL ERROR
// =========================================================

if (curl_errno($ch)) {

    $curl_error = curl_error($ch);

    curl_close($ch);

    echo json_encode([
        "success" => false,
        "error" => "AI connection failed",
        "message" => $curl_error
    ]);

    exit();
}

// =========================================================
// HTTP STATUS
// =========================================================

$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
// DEBUG: xem HTTP status từ Flask
error_log("DEBUG Flask HTTP code: " . $http_code);
curl_close($ch);

// =========================================================
// INVALID RESPONSE
// =========================================================

if ($http_code !== 200) {

    echo json_encode([
        "success" => false,
        "error" => "AI service error",
        "status_code" => $http_code
    ]);

    exit();
}

// =========================================================
// VALIDATE AI RESPONSE
// =========================================================

$decoded_response = json_decode($response, true);

if (!$decoded_response) {

    echo json_encode([
        "success" => false,
        "error" => "Invalid AI response"
    ]);

    exit();
}

// =========================================================
// THÊM THÔNG TIN RATE LIMIT
// =========================================================

$decoded_response['daily_limit'] = 15;

$decoded_response['requests_used_today'] = $today_requests + 1;

$decoded_response['requests_remaining'] =
    15 - ($today_requests + 1);

// =========================================================
// RETURN FINAL RESPONSE
// =========================================================

echo json_encode($decoded_response);

exit();

?>
```
