<?php
include 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) { exit("Unauthorized"); }
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_username = trim($_POST['new_username']);
    $new_email = trim($_POST['new_email']); // NEW: Retrieve email
    $bio = trim($_POST['bio']);
    $current_password = $_POST['current_password'];

    // Basic server-side email validation
    if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        die("<script>alert('Invalid email format!'); window.location.href='profile.php';</script>");
    }

    // 1. Verify Password
    $stmt = $conn->prepare("SELECT password, profile_pic FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!password_verify($current_password, $user['password'])) {
        die("<script>alert('Incorrect current password!'); window.location.href='profile.php';</script>");
    }

    // 2. NEW: Check if Email is already taken by another user
    $email_check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $email_check_stmt->bind_param("si", $new_email, $user_id);
    $email_check_stmt->execute();
    if ($email_check_stmt->get_result()->num_rows > 0) {
        $email_check_stmt->close();
        die("<script>alert('That email address is already in use by another account!'); window.location.href='profile.php';</script>");
    }
    $email_check_stmt->close();

    // 3. Handle Profile Picture Upload
    $pic_name = $user['profile_pic'];
    if (!empty($_FILES['profile_pic']['name'])) {
        $ext = pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION);
        $new_name = "user_" . $user_id . "_" . time() . "." . $ext;
        $target = "uploads/avatars/" . $new_name;

        if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $target)) {
            $pic_name = $new_name;
        }
    }

    // 4. Update Database (Added new_email to the query)
    $update_stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, bio = ?, profile_pic = ? WHERE id = ?");
    $update_stmt->bind_param("ssssi", $new_username, $new_email, $bio, $pic_name, $user_id);
    $update_stmt->execute();
    $update_stmt->close();

    // 5. Optional Password Change
    if (!empty($_POST['new_password'])) {
        $hashed = password_hash($_POST['new_password'], PASSWORD_BCRYPT);
        $pass_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $pass_stmt->bind_param("si", $hashed, $user_id);
        $pass_stmt->execute();
        $pass_stmt->close();
    }

    header("Location: profile.php?success=1");
    exit();
}
?>
