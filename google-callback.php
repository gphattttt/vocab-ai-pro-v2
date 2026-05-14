<?php
include 'db.php';
session_start();

/**
 * Google Identity Services sends the credential (a JWT token) 
 * via POST to this URL.
 */
if (!isset($_POST['credential'])) {
    header("Location: login.php");
    exit();
}

$id_token = $_POST['credential'];

// --- 1. VERIFY THE TOKEN WITH GOOGLE ---
/**
 * For local testing without complex libraries, we call Google's 
 * tokeninfo API. In production, using the Google API Client 
 * Library for PHP is recommended.
 */
$url = "https://oauth2.googleapis.com/tokeninfo?id_token=" . $id_token;
$response = file_get_contents($url);
$payload = json_decode($response, true);

if (!$payload || isset($payload['error'])) {
    die("Invalid Google Token. Please try again.");
}

// Extract User Info from Google Payload
$google_id = $payload['sub']; // Unique ID for the user
$email = $payload['email'];
$full_name = $payload['name'];
$profile_pic = $payload['picture'];

// --- 2. CHECK IF USER EXISTS ---
$stmt = $conn->prepare("SELECT id, username FROM users WHERE google_id = ? OR email = ? LIMIT 1");
$stmt->bind_param("ss", $google_id, $email);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    // USER EXISTS
    $user_id = $row['id'];
    
    // If they logged in via email before, link their Google ID now
    $update_stmt = $conn->prepare("UPDATE users SET google_id = ? WHERE id = ?");
    $update_stmt->bind_param("si", $google_id, $user_id);
    $update_stmt->execute();

    $_SESSION['user_id'] = $user_id;
    $_SESSION['username'] = $row['username'];
} else {
    // NEW USER: Auto-register them
    // Generate a default username from their name
    $base_username = strtolower(str_replace(' ', '', $full_name));
    $username = $base_username . rand(100, 999);

    /**
     * We leave the password empty since they use Google, 
     * but we ensure XP starts at 0.
     */
    $insert_stmt = $conn->prepare("INSERT INTO users (username, email, google_id, xp, profile_pic) VALUES (?, ?, ?, 0, ?)");
    $insert_stmt->bind_param("ssss", $username, $email, $google_id, $profile_pic);
    
    if ($insert_stmt->execute()) {
        $_SESSION['user_id'] = $insert_stmt->insert_id;
        $_SESSION['username'] = $username;
    } else {
        die("Error creating your account. Please contact support.");
    }
}

// --- 3. FINAL REDIRECT ---
header("Location: index.php");
exit();
?>