<?php
// GCSE/pages/EnglishPractice/manage_actions_ep.php
// Handles actions like toggling favorites for the English Practice module

session_start();
require_once __DIR__ . '/../../config/db_connect.php';
require_once __DIR__ . '/_functions.php'; // Include shared functions

// Default redirect back to practice page or referrer
$redirect_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'practice.php'; // Sensible default

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action'])) {
    $_SESSION['error_ep'] = "Invalid request.";
    header('Location: ' . $redirect_url);
    exit;
}

$action = $_POST['action'];
$result = null;

try {
    switch ($action) {
        case 'toggle_favorite':
            if (!isset($_POST['item_id'])) {
                throw new Exception("Item ID is missing for favorite toggle.");
            }
            $item_id = filter_input(INPUT_POST, 'item_id', FILTER_VALIDATE_INT);
            if (!$item_id || $item_id <= 0) {
                 throw new Exception("Invalid Item ID provided.");
            }
            // Call the function from _functions.php
            $result = toggle_favorite_status($conn, $item_id);
            break;

        // Add more actions here later if needed
        // case 'mark_learned':
        //     // ...
        //     break;

        default:
            throw new Exception("Unknown action requested.");
            break;
    }

    // Set session message based on result
    if (isset($result) && is_array($result) && array_key_exists('success', $result)) {
        if ($result['success']) {
            $_SESSION['success_ep'] = isset($result['message']) ? $result['message'] : 'Action successful.';
        } else {
            $_SESSION['error_ep'] = isset($result['message']) ? $result['message'] : 'Action failed.';
        }
    } else {
        $_SESSION['warning_ep'] = "Action may not have completed as expected."; // Or handle more specifically
    }

} catch (Exception $e) {
    $_SESSION['error_ep'] = "Error processing action: " . $e->getMessage();
    error_log("manage_actions_ep Error: Action='$action' Err=" . $e->getMessage());
    // Potentially rollback transaction if function didn't handle it
}

// Redirect back
header('Location: ' . $redirect_url);
exit;

?>