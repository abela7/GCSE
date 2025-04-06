<?php
require_once '../config/db_connect.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if task ID is provided
if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Task ID is required']);
    exit;
}

$task_id = (int)$_GET['id'];

// Prepare and execute query
$query = "SELECT t.*, c.name as category_name, c.color as category_color 
          FROM tasks t 
          JOIN task_categories c ON t.category_id = c.id 
          WHERE t.id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param('i', $task_id);

try {
    $stmt->execute();
    $result = $stmt->get_result();
    $task = $result->fetch_assoc();
    
    if ($task) {
        echo json_encode(['success' => true, 'task' => $task]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Task not found']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error fetching task details: ' . $e->getMessage()]);
}

// Close database connection
close_connection($conn);
?> 