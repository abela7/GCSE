<?php
header('Content-Type: application/json');

require_once '../config/db_connect.php';

try {
    // Get current date
    $today = date('Y-m-d');
    
    // Query to get all upcoming exams
    $query = "SELECT e.subject, e.exam_date,
             DATEDIFF(e.exam_date, ?) as days_remaining
             FROM exams e
             WHERE e.exam_date >= ?
             ORDER BY e.exam_date ASC";
             
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ss', $today, $today);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $exams = [];
    
    while ($row = $result->fetch_assoc()) {
        $exams[] = [
            'subject' => $row['subject'],
            'exam_date' => $row['exam_date'],
            'days_remaining' => $row['days_remaining']
        ];
    }
    
    echo json_encode([
        'success' => true, 
        'exams' => $exams,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'error' => $e->getMessage()
    ]);
}

$conn->close(); 