<?php
require_once '../includes/db_connect.php';
require_once '../includes/task_functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

if (!isset($_POST['task_id'])) {
    echo json_encode(['success' => false, 'message' => 'Task ID is required']);
    exit;
}

$task_id = intval($_POST['task_id']);
$user_id = 1; // Assuming user_id = 1 for now

$result = complete_task($conn, $task_id, $user_id);

echo json_encode([
    'success' => $result,
    'message' => $result ? 'Task completed successfully' : 'Error completing task'
]); 