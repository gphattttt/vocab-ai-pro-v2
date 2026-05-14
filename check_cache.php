<?php
include 'db.php';
$word = $_GET['word'] ?? '';

// Check if anyone has ever searched this word before
$stmt = $conn->prepare("SELECT * FROM vocabularies WHERE word = ? LIMIT 1");
$stmt->bind_param("s", $word);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode(['exists' => true, 'data' => $row]);
} else {
    echo json_encode(['exists' => false]);
}
?>