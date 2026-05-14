<?php
session_start(); // Access the current session

// Clear all session variables
session_unset();

// Destroy the session entirely
session_destroy();

// Redirect the user back to the login page
header("Location: login.php");
exit();
?>