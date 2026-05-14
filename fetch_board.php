<?php
include 'db.php';
session_start();

// Security: Ensure only the logged-in user's data is fetched
if (!isset($_SESSION['user_id'])) {
    die(json_encode(["error" => "Unauthorized"]));
}

$user_id = $_SESSION['user_id'];

/**
 * SRS LOGIC UPDATED:
 * We now order by next_review_date so that words due today (or overdue) 
 * appear at the top. We then use RAND() as a secondary sort to keep 
 * the variety fresh.
 */
$sql = "SELECT v.*, c.name AS category_name 
        FROM vocabularies v 
        LEFT JOIN categories c ON v.category_id = c.id 
        WHERE v.user_id = $user_id 
        ORDER BY v.next_review_date ASC, RAND() 
        LIMIT 100";

$result = $conn->query($sql);

$words = [];

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $words[] = [
            "id" => $row['id'],
            "word" => $row['word'],
            "ipa" => $row['ipa'],
            "word_form" => $row['word_form'],
            "level" => $row['level'],
            "definition_en" => $row['definition_en'],
            "definition_vi" => $row['definition_vi'],
            "example_sentence" => $row['example_sentence'],
            "category_id" => $row['category_id'],
            "category_name" => $row['category_name'],
            // SRS specific data for the frontend if needed
            "next_review_date" => $row['next_review_date'],
            "srs_interval" => $row['srs_interval']
        ];
    }
}

header('Content-Type: application/json');
echo json_encode($words);

$conn->close();
?>