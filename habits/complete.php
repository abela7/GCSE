<?php
// Include database connection
require_once '../../config/db_connect.php';

// Check if habit ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "No habit specified.";
    header('Location: index.php');
    exit;
}

// Get habit ID and redirect parameter
$habit_id = (int)$_GET['id'];
$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'index';

// Validate habit ID
if ($habit_id <= 0) {
    $_SESSION['error'] = "Invalid habit ID.";
    header('Location: index.php');
    exit;
}

// Check if habit exists
$check_query = "SELECT id, name FROM habits WHERE id = ? AND is_active = 1";
$check_stmt = $conn->prepare($check_query);
$check_stmt->bind_param('i', $habit_id);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "Habit not found or inactive.";
    header('Location: index.php');
    exit;
}

// Get habit name for success message
$habit = $result->fetch_assoc();
$habit_name = $habit['name'];

// Insert habit completion record for today
$today = date('Y-m-d');
$status = 'completed';

// First check if there's already a record for today
$check_completion_query = "SELECT id FROM habit_completions 
                         WHERE habit_id = ? AND completion_date = ?";
$check_completion_stmt = $conn->prepare($check_completion_query);
$check_completion_stmt->bind_param('is', $habit_id, $today);
$check_completion_stmt->execute();
$completion_result = $check_completion_stmt->get_result();

if ($completion_result->num_rows > 0) {
    // Update existing record
    $completion_id = $completion_result->fetch_assoc()['id'];
    $update_query = "UPDATE habit_completions SET status = ?, updated_at = CURRENT_TIMESTAMP 
                   WHERE id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param('si', $status, $completion_id);
    $success = $update_stmt->execute();
} else {
    // Insert new record
    $insert_query = "INSERT INTO habit_completions (habit_id, completion_date, status, created_at, updated_at) 
                   VALUES (?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";
    $insert_stmt = $conn->prepare($insert_query);
    $insert_stmt->bind_param('iss', $habit_id, $today, $status);
    $success = $insert_stmt->execute();
}

// Set success or error message
if ($success) {
    $_SESSION['success'] = "'{$habit_name}' marked as completed for today!";
} else {
    $_SESSION['error'] = "Failed to mark habit as completed: " . $conn->error;
}

// Redirect based on the redirect parameter
switch ($redirect) {
    case 'dashboard':
        header('Location: ../dashboard.php');
        break;
    default:
        header('Location: index.php');
}

// Close the database connection
close_connection($conn);
exit;
?> 