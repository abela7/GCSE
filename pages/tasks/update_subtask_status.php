<?php
require_once '../../includes/db_connect.php';

header('Content-Type: application/json');

// Validate input
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$status = isset($_POST['status']) ? $_POST['status'] : '';

if ($id < 1 || !in_array($status, ['pending', 'in_progress', 'completed'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

// Update subtask status
$update_query = "UPDATE tasks SET status = ? WHERE id = ? AND parent_task_id IS NOT NULL";
$stmt = $conn->prepare($update_query);
$stmt->bind_param("si", $status, $id);

try {
    $success = $stmt->execute();
    if ($success && $stmt->affected_rows > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Subtask not found or no changes made']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?> 