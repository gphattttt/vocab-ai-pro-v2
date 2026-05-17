<?php
// ============================================================
// quiz_proxy.php
// Proxy bảo vệ giữa quiz.php và quiz-api.py
// Mục tiêu:
// - Chỉ user đã đăng nhập mới tạo quiz được
// - Ẩn Python API khỏi frontend
// - Giới hạn mỗi user 10 lần tạo quiz/ngày
// - 1 request = 1 quiz session, không phải 1 câu
// ============================================================

include 'db.php';
session_start();

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        "status" => "error",
        "message" => "Unauthorized"
    ]);
    exit();
}

$user_id = (int) $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        "status" => "error",
        "message" => "Method not allowed"
    ]);
    exit();
}

$raw_input = file_get_contents('php://input');
$payload = json_decode($raw_input, true);

if (!is_array($payload) || !isset($payload['words']) || !is_array($payload['words'])) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "Invalid payload"
    ]);
    exit();
}

$words = array_slice($payload['words'], 0, 10);

$clean_words = [];

foreach ($words as $item) {
    if (!is_array($item)) {
        continue;
    }

    $id = isset($item['id']) ? (int) $item['id'] : null;
    $word = trim($item['word'] ?? '');

    if (!$id || $word === '') {
        continue;
    }

    if (mb_strlen($word) > 100) {
        continue;
    }

    $clean_words[] = [
        "id" => $id,
        "word" => $word
    ];
}

if (count($clean_words) === 0) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "No valid words"
    ]);
    exit();
}

// Giới hạn 10 lần tạo quiz mỗi ngày cho mỗi user
$daily_limit = 10;
$today = date('Y-m-d');

$usage_sql = "
    SELECT request_count
    FROM quiz_api_usage
    WHERE user_id = ? AND usage_date = ?
    LIMIT 1
";

$usage_stmt = $conn->prepare($usage_sql);
$usage_stmt->bind_param("is", $user_id, $today);
$usage_stmt->execute();
$usage_result = $usage_stmt->get_result();
$usage_row = $usage_result->fetch_assoc();

$current_count = $usage_row ? (int) $usage_row['request_count'] : 0;

if ($current_count >= $daily_limit) {
    http_response_code(429);
    echo json_encode([
        "status" => "error",
        "message" => "Bạn đã dùng hết 10 lần tạo quiz hôm nay. Vui lòng quay lại vào ngày mai.",
        "limit" => $daily_limit,
        "used" => $current_count,
        "remaining" => 0
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// Gọi Python API nội bộ qua localhost
$python_api_url = "http://127.0.0.1:5002/generate-quiz";

$python_payload = json_encode([
    "words" => $clean_words
], JSON_UNESCAPED_UNICODE);

$ch = curl_init();

curl_setopt_array($ch, [
    CURLOPT_URL => $python_api_url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $python_payload,
    CURLOPT_TIMEOUT => 45,
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_HTTPHEADER => [
        "Content-Type: application/json",
        "Accept: application/json"
    ]
]);

$response = curl_exec($ch);
$curl_error = curl_error($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

curl_close($ch);

if ($response === false || $curl_error) {
    http_response_code(502);
    echo json_encode([
        "status" => "error",
        "message" => "Quiz API connection failed",
        "debug" => $curl_error
    ]);
    exit();
}

if ($http_code < 200 || $http_code >= 300) {
    http_response_code(502);
    echo json_encode([
        "status" => "error",
        "message" => "Quiz API returned error",
        "http_code" => $http_code,
        "raw" => $response
    ]);
    exit();
}

// Chỉ tăng quota sau khi Python API trả thành công
$update_sql = "
    INSERT INTO quiz_api_usage (user_id, usage_date, request_count)
    VALUES (?, ?, 1)
    ON DUPLICATE KEY UPDATE request_count = request_count + 1
";

$update_stmt = $conn->prepare($update_sql);
$update_stmt->bind_param("is", $user_id, $today);
$update_stmt->execute();

$new_count = $current_count + 1;

$data = json_decode($response, true);

if (!is_array($data)) {
    http_response_code(502);
    echo json_encode([
        "status" => "error",
        "message" => "Invalid JSON from Quiz API",
        "raw" => $response
    ]);
    exit();
}

$data["quota"] = [
    "used" => $new_count,
    "limit" => $daily_limit,
    "remaining" => max(0, $daily_limit - $new_count)
];

echo json_encode($data, JSON_UNESCAPED_UNICODE);
exit();
?>
