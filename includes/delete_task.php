<?php
require_once '../config/db_connect.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if task ID is provided
if (!isset($_POST['task_id'])) {
    echo json_encode(['success' => false, 'message' => 'Task ID is required']);
    exit;
}

$task_id = (int)$_POST['task_id'];

try {
    // Begin transaction
    $conn->begin_transaction();

    // First, check if the task exists and get its type
    $check_stmt = $conn->prepare("SELECT task_type FROM tasks WHERE id = ?");
    $check_stmt->bind_param('i', $task_id);
    $check_stmt->execute();
    $task = $check_stmt->get_result()->fetch_assoc();

    if (!$task) {
        throw new Exception("Task not found");
    }

    // Delete task instances if it's a recurring task
    if ($task['task_type'] === 'recurring') {
        $delete_instances = $conn->prepare("DELETE FROM task_instances WHERE task_id = ?");
        $delete_instances->bind_param('i', $task_id);
        $delete_instances->execute();

        // Delete recurrence rules
        $delete_rules = $conn->prepare("DELETE FROM task_recurrence_rules WHERE task_id = ?");
        $delete_rules->bind_param('i', $task_id);
        $delete_rules->execute();
    }

    // Delete task checklist items if they exist
    $delete_checklist = $conn->prepare("DELETE FROM task_checklist_items WHERE task_id = ?");
    $delete_checklist->bind_param('i', $task_id);
    $delete_checklist->execute();

    // Delete task time logs if they exist
    $delete_time_logs = $conn->prepare("DELETE FROM task_time_logs WHERE task_instance_id IN (SELECT id FROM task_instances WHERE task_id = ?)");
    $delete_time_logs->bind_param('i', $task_id);
    $delete_time_logs->execute();

    // Finally, delete the task itself
    $delete_task = $conn->prepare("DELETE FROM tasks WHERE id = ?");
    $delete_task->bind_param('i', $task_id);
    $delete_task->execute();

    // Commit transaction
    $conn->commit();
    
    echo json_encode(['success' => true, 'message' => 'Task deleted successfully']);

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error deleting task: ' . $e->getMessage()]);
}

// Close database connection
close_connection($conn);
?> 