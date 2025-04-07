<?php
session_start();
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/task_functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

if (!isset($_POST['task_id'])) {
    echo json_encode(['success' => false, 'message' => 'Task ID is required']);
    exit;
}

$task_id = intval($_POST['task_id']);
$user_id = $_SESSION['user_id'];

try {
    $result = complete_task($conn, $task_id, $user_id);
    echo json_encode([
        'success' => $result,
        'message' => $result ? 'Task completed successfully' : 'Error completing task'
    ]);
} catch (Exception $e) {
    error_log("Error in complete_task.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error completing task'
    ]);
} 