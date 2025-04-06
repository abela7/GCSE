<?php
require_once '../../includes/header.php';
require_once '../../includes/db_connect.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id < 1) {
    $_SESSION['error'] = "Invalid task ID.";
    header('Location: index.php');
    exit;
}

// Start transaction
$conn->begin_transaction();

try {
    // Delete all subtasks first
    $delete_subtasks = "DELETE FROM tasks WHERE parent_task_id = ?";
    $stmt = $conn->prepare($delete_subtasks);
    $stmt->bind_param("i", $id);
    $stmt->execute();

    // Delete the main task
    $delete_task = "DELETE FROM tasks WHERE id = ?";
    $stmt = $conn->prepare($delete_task);
    $stmt->bind_param("i", $id);
    $stmt->execute();

    $conn->commit();
    $_SESSION['success'] = "Task and its subtasks deleted successfully.";
    header('Location: index.php');
    exit;

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error'] = "Error deleting task: " . $e->getMessage();
    header('Location: index.php');
    exit;
}
?> 