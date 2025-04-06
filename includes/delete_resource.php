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
$resource_id = isset($_POST['resource_id']) ? (int)$_POST['resource_id'] : 0;

// Validate input
if ($resource_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid resource ID']);
    exit;
}

// Delete resource
$delete_query = "DELETE FROM resources WHERE id = ?";
$delete_stmt = $conn->prepare($delete_query);
$delete_stmt->bind_param('i', $resource_id);
$success = $delete_stmt->execute();

if ($success) {
    echo json_encode(['success' => true, 'message' => 'Resource deleted successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete resource: ' . $delete_stmt->error]);
}

// Close database connection
close_connection($conn);
?>