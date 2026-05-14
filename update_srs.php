<?php
include 'db.php';
session_start();
if (!isset($_SESSION['user_id'])) die("Unauthorized");

$id = intval($_POST['id']);
$is_correct = $_POST['is_correct'] === 'true';

// Fetch current SRS data
$res = $conn->query("SELECT srs_interval, srs_ease_factor FROM vocabularies WHERE id = $id");
$data = $res->fetch_assoc();

$interval = $data['srs_interval'];
$ease = $data['srs_ease_factor'];

if ($is_correct) {
    // Correct answer: Increase interval
    if ($interval == 0) $interval = 1;
    elseif ($interval == 1) $interval = 4;
    else $interval = round($interval * $ease);
    
    // Slightly increase ease
    $ease = min(3.0, $ease + 0.1);
} else {
    // Wrong answer: Reset interval but keep the word 'harder'
    $interval = 1;
    $ease = max(1.3, $ease - 0.2);
}

// Calculate next date
$next_date = date('Y-m-d', strtotime("+$interval days"));

$stmt = $conn->prepare("UPDATE vocabularies SET srs_interval = ?, srs_ease_factor = ?, next_review_date = ? WHERE id = ?");
$stmt->bind_param("idsi", $interval, $ease, $next_date, $id);
$stmt->execute();

echo "Success";