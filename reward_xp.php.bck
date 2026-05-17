<?php
include 'db.php';
session_start();
if (!isset($_SESSION['user_id'])) die("Unauthorized");

$user_id = $_SESSION['user_id'];
$amount = intval($_POST['amount']);

// Security: Only allow specific XP amounts to prevent cheating
if ($amount === 50) {
    $conn->query("UPDATE users SET xp = xp + $amount WHERE id = $user_id");
    echo "Success";
}
?>