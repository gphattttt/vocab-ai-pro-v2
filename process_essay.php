<?php
include 'db.php';
session_start();

// Thiết lập phản hồi luôn là JSON
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit();
}

$user_id = $_SESSION['user_id'];

// Đọc dữ liệu JSON từ yêu cầu POST
$json_input = file_get_contents('php://input');
$data = json_decode($json_input, true);
$words = $data['words'] ?? [];
$saved_count = 0;

if (empty($words)) {
    echo json_encode(["success" => false, "message" => "Không có từ vựng nào để lưu."]);
    exit();
}

foreach ($words as $w) {
    $word = trim($w['word'] ?? '');
    if (empty($word)) continue;
    
    $cat_name = trim($w['suggested_category'] ?? 'Chung');

    // 1. Xử lý Chuyên mục (Category)
    $cat_stmt = $conn->prepare("SELECT id FROM categories WHERE name = ? AND user_id = ?");
    $cat_stmt->bind_param("si", $cat_name, $user_id);
    $cat_stmt->execute();
    $cat_res = $cat_stmt->get_result();

    if ($row = $cat_res->fetch_assoc()) {
        $category_id = $row['id'];
    } else {
        $create_cat = $conn->prepare("INSERT INTO categories (user_id, name) VALUES (?, ?)");
        $create_cat->bind_param("is", $user_id, $cat_name);
        $create_cat->execute();
        $category_id = $create_cat->insert_id;
    }

    // 2. Kiểm tra trùng lặp (Duplicate Check)
    $check = $conn->prepare("SELECT id FROM vocabularies WHERE word = ? AND user_id = ?");
    $check->bind_param("si", $word, $user_id);
    $check->execute();
    if ($check->get_result()->num_rows > 0) continue;

    // 3. Lưu từ vựng mới
    $sql = "INSERT INTO vocabularies (user_id, word, ipa, word_form, level, definition_en, definition_vi, example_sentence, category_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    // Gán các biến để tránh lỗi bind_param
    $ipa = $w['ipa'] ?? '';
    $word_form = $w['word_form'] ?? '';
    $level = $w['level'] ?? '';
    $def_en = $w['definition_en'] ?? '';
    $def_vi = $w['definition_vi'] ?? '';
    $ex = $w['example_sentence'] ?? '';

    $stmt->bind_param("isssssssi", 
        $user_id, $word, $ipa, $word_form, $level, $def_en, $def_vi, $ex, $category_id
    );
    
    if ($stmt->execute()) {
        $saved_count++;
    }
}

echo json_encode(["success" => true, "count" => $saved_count]);
exit();
?>
