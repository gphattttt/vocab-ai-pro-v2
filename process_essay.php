<?php
include 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) { die("Unauthorized"); }
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $words = $data['words'] ?? [];
    $saved_count = 0;

    foreach ($words as $w) {
        $word = trim($w['word']);
        $cat_name = trim($w['suggested_category']);

        // 1. Handle Category Creation/Selection
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

        // 2. Duplicate Check
        $check = $conn->prepare("SELECT id FROM vocabularies WHERE word = ? AND user_id = ?");
        $check->bind_param("si", $word, $user_id);
        $check->execute();
        if ($check->get_result()->num_rows > 0) continue;

        // 3. Insert Word
        $sql = "INSERT INTO vocabularies (user_id, word, ipa, word_form, level, definition_en, definition_vi, example_sentence, category_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isssssssi", $user_id, $word, $w['ipa'], $w['word_form'], $w['level'], $w['definition_en'], $w['definition_vi'], $w['example_sentence'], $category_id);
        
        if ($stmt->execute()) {
            $saved_count++;
            // Reward XP per word
            $conn->query("UPDATE users SET xp = xp + 5 WHERE id = $user_id");
        }
    }
    echo json_encode(["success" => true, "count" => $saved_count]);
}
?>