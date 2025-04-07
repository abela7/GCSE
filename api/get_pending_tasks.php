<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

try {
    // Get today's date in Y-m-d format
    $today = date('Y-m-d');
    
    // Get pending tasks for today
    $query = "SELECT title, subject, due_date 
             FROM tasks 
             WHERE DATE(due_date) = ? 
             AND status = 'Pending' 
             ORDER BY due_date ASC";
             
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $today);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $tasks = [];
    while ($row = $result->fetch_assoc()) {
        $tasks[] = [
            'title' => $row['title'],
            'subject' => $row['subject'],
            'due_date' => $row['due_date']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'tasks' => $tasks,
        'count' => count($tasks)
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 