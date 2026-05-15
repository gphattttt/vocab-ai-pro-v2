<?php
include 'db.php';
session_start();
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];

    if ($action == 'create') {
        $name = $_POST['name'];
        $stmt = $conn->prepare("INSERT INTO categories (user_id, name) VALUES (?, ?)");
        $stmt->bind_param("is", $user_id, $name);
        echo $stmt->execute() ? "Success" : "Error";
    }

    if ($action == 'delete') {
        $cat_id = $_POST['id'];
        // Trả các từ vựng thuộc category này về trạng thái "Chưa có category (NULL)" để không bị mất từ
        $stmt_update = $conn->prepare("UPDATE vocabularies SET category_id = NULL WHERE category_id = ? AND user_id = ?");
        $stmt_update->bind_param("ii", $cat_id, $user_id);
        $stmt_update->execute();

        // Tiến hành xóa category
        $stmt = $conn->prepare("DELETE FROM categories WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $cat_id, $user_id);
        echo $stmt->execute() ? "Success" : "Error";
    }

    if ($action == 'assign') {
        $word_id = $_POST['word_id'];
        $cat_id = $_POST['cat_id'] == 'null' ? null : $_POST['cat_id'];
        $stmt = $conn->prepare("UPDATE vocabularies SET category_id = ? WHERE id = ? AND user_id = ?");
        $stmt->bind_param("iii", $cat_id, $word_id, $user_id);
        echo $stmt->execute() ? "Success" : "Error";
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $res = $conn->query("SELECT * FROM categories WHERE user_id = $user_id");
    $cats = [];
    while($row = $res->fetch_assoc()) { $cats[] = $row; }
    echo json_encode($cats);
}
?>
