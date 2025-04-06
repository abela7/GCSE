<?php
require_once '../config/db_connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

if (!isset($_POST['assignment_id']) || !isset($_POST['minutes'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

$assignment_id = mysqli_real_escape_string($conn, $_POST['assignment_id']);
$minutes = mysqli_real_escape_string($conn, $_POST['minutes']);
$hours = $minutes / 60;

// Start transaction
$conn->begin_transaction();

try {
    // Update actual hours in assignment
    $sql = "UPDATE access_assignments 
            SET actual_hours = actual_hours + ?, 
                status = CASE 
                    WHEN status = 'not_started' THEN 'in_progress'
                    ELSE status 
                END
            WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("di", $hours, $assignment_id);
    $stmt->execute();

    // Add to progress log
    $sql = "INSERT INTO assignment_progress_log 
            (assignment_id, action_type, description, time_spent) 
            VALUES (?, 'time_logged', ?, ?)";
    $description = "Worked on assignment for " . number_format($hours, 1) . " hours";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isd", $assignment_id, $description, $hours);
    $stmt->execute();

    // Commit transaction
    $conn->commit();
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?> 