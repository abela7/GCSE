<?php
// Include database connection
require_once '../config/db_connect.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get and validate input
$task_id = isset($_POST['task_id']) ? (int)$_POST['task_id'] : 0;
$status = isset($_POST['status']) ? clean_input($conn, $_POST['status']) : '';

// Validate input
// Get and validate input
$task_id = isset($_POST['task_id']) ? (int)$_POST['task_id'] : 0;
$status = isset($_POST['status']) ? clean_input($conn, $_POST['status']) : '';

// Validate input
if ($task_id <= 0 || !in_array($status, ['pending', 'completed'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid input parameters']);
    exit;
}

// Update task status
$update_query = "UPDATE tasks SET status = ? WHERE id = ?";
$update_stmt = $conn->prepare($update_query);
$update_stmt->bind_param('si', $status, $task_id);
$success = $update_stmt->execute();

if ($success) {
    echo json_encode(['success' => true, 'message' => 'Task status updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update task status: ' . $update_stmt->error]);
}

// Close database connection
close_connection($conn);
?>