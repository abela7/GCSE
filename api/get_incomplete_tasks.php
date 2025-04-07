<?php
header('Content-Type: application/json');

require_once '../config/db_connect.php';

try {
    // Get current date in Y-m-d format
    $today = date('Y-m-d');
    
    // Prepare query to get incomplete tasks for today
    $query = "SELECT t.id, t.title 
             FROM tasks t 
             WHERE t.due_date = ? 
             AND (t.status = 'pending' OR t.status = 'in_progress')
             AND t.is_active = 1
             UNION ALL
             SELECT t.id, t.title
             FROM tasks t
             INNER JOIN task_instances ti ON t.id = ti.task_id
             WHERE ti.due_date = ?
             AND (ti.status = 'pending' OR ti.status = 'in_progress')
             AND t.is_active = 1
             ORDER BY title";
             
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ss', $today, $today);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $tasks = [];
    
    while ($row = $result->fetch_assoc()) {
        $tasks[] = [
            'id' => $row['id'],
            'title' => $row['title']
        ];
    }
    
    echo json_encode(['success' => true, 'tasks' => $tasks]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close(); 