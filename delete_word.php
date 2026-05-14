<?php
include 'db.php';
session_start();

// Security check: must be logged in
if (!isset($_SESSION['user_id'])) {
    die("Unauthorized");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $ids_raw = $_POST['id'] ?? ''; // This will be "12,13,14"

    if (empty($ids_raw)) {
        die("No IDs provided");
    }

    // 1. Convert the string "12,13,14" into an array of integers
    $ids_array = explode(',', $ids_raw);
    $ids_array = array_map('intval', $ids_array);
    
    // 2. Create the necessary placeholders (?,?,?) for the query
    $placeholders = implode(',', array_fill(0, count($ids_array), '?'));
    
    // 3. Prepare the query using the IN operator
    // We still check user_id to ensure a user can't delete someone else's words
    $sql = "DELETE FROM vocabularies WHERE id IN ($placeholders) AND user_id = ?";
    $stmt = $conn->prepare($sql);

    // 4. Bind parameters dynamically
    // We need "i" for each ID plus one "i" for the user_id
    $types = str_repeat('i', count($ids_array)) . 'i';
    $params = array_merge($ids_array, [$user_id]);
    
    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        echo "Success";
    } else {
        echo "Error: " . $conn->error;
    }
    
    $stmt->close();
    $conn->close();
}
?>