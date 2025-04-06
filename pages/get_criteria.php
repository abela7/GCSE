<?php
include '../config/db_connect.php';

if (isset($_GET['assignment_id'])) {
    $assignment_id = (int)$_GET['assignment_id'];
    
    $sql = "SELECT id, criteria_text, grade_required, status, notes 
            FROM assessment_criteria 
            WHERE assignment_id = $assignment_id 
            ORDER BY id ASC";
    
    $result = mysqli_query($conn, $sql);
    
    $criteria = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $criteria[] = $row;
    }
    
    header('Content-Type: application/json');
    echo json_encode($criteria);
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Assignment ID not provided']);
}
?> 