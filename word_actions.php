<?php
include 'db.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

/**
 * ACTION: UPDATE WORD
 */
if ($action === 'update') {
    $id = intval($_POST['id'] ?? 0);
    $word = trim($_POST['word'] ?? '');
    $ipa = trim($_POST['ipa'] ?? '');
    $nuance = trim($_POST['nuance'] ?? '');
    $collocations = trim($_POST['collocations'] ?? '');
    $context_sentence = trim($_POST['context_sentence'] ?? '');
    $form = trim($_POST['form'] ?? '');
    $level = trim($_POST['level'] ?? '');
    $defEn = trim($_POST['defEn'] ?? '');
    $defVi = trim($_POST['defVi'] ?? '');
    $sentence = trim($_POST['sentence'] ?? '');
    $category_raw = $_POST['category_id'] ?? 'null';
    $category_id = ($category_raw === 'null' || $category_raw === '') ? null : intval($category_raw);

    if ($id <= 0 || empty($word)) {
        echo json_encode(['status' => 'error', 'message' => 'Missing data']);
        exit;
    }

    $sql = "UPDATE vocabularies 
            SET word=?, ipa=?, nuance=?, collocations=?, context_sentence=?, word_form=?, level=?, 
                definition_en=?, definition_vi=?, example_sentence=?, category_id=?
            WHERE id=? AND user_id=?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "ssssssssssiii",
        $word, $ipa, $nuance, $collocations, $context_sentence,
        $form, $level, $defEn, $defVi, $sentence, $category_id,
        $id, $user_id
    );

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Cập nhật thành công']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'DB Error: ' . $conn->error]);
    }

    $stmt->close();
    $conn->close();
    exit;
}

/**
 * ACTION: DELETE WORDS (bulk)
 */
if ($action === 'delete') {
    $ids_raw = $_POST['id'] ?? '';
    if (empty($ids_raw)) {
        echo json_encode(['status' => 'error', 'message' => 'No IDs provided']);
        exit;
    }

    $ids_array = array_map('intval', explode(',', $ids_raw));
    $placeholders = implode(',', array_fill(0, count($ids_array), '?'));

    $sql = "DELETE FROM vocabularies WHERE id IN ($placeholders) AND user_id = ?";
    $stmt = $conn->prepare($sql);

    $types = str_repeat('i', count($ids_array)) . 'i';
    $params = array_merge($ids_array, [$user_id]);
    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Đã xóa']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'DB Error: ' . $conn->error]);
    }

    $stmt->close();
    $conn->close();
    exit;
}

/**
 * ACTION: MOVE WORDS (bulk)
 */
if ($action === 'move') {
    $ids_raw = $_POST['id'] ?? '';
    $category_raw = $_POST['category_id'] ?? 'null';
    $category_id = ($category_raw === 'null' || $category_raw === '') ? null : intval($category_raw);

    if (empty($ids_raw)) {
        echo json_encode(['status' => 'error', 'message' => 'No IDs provided']);
        exit;
    }

    $ids_array = array_map('intval', explode(',', $ids_raw));
    $placeholders = implode(',', array_fill(0, count($ids_array), '?'));

    $sql = "UPDATE vocabularies SET category_id = ? WHERE id IN ($placeholders) AND user_id = ?";
    $stmt = $conn->prepare($sql);

    $types = 'i' . str_repeat('i', count($ids_array)) . 'i';
    $params = array_merge([$category_id], $ids_array, [$user_id]);
    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Đã di chuyển']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'DB Error: ' . $conn->error]);
    }

    $stmt->close();
    $conn->close();
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Unknown action']);
