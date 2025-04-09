<?php
// Start the session if it hasn't been started already
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is logged in via the session variable
// If the session variable doesn't exist or is not true, redirect to login page
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    // Redirect to the login page. Adjust path if login.php is not in the root.
    // Using absolute path from web root for clarity
    header('Location: /login.php'); 
    exit; // Important to stop script execution after redirection
}

// If the script reaches this point, the user is authenticated.
// The page including this file can now continue loading its content.
?> 