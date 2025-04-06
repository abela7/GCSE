<?php
require_once '../config/db_connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$assignment_id = $_POST['assignment_id'] ?? null;
$criteria_id = $_POST['criteria_id'] ?? null;
$status = $_POST['status'] ?? null;
$notes = $_POST['notes'] ?? '';

if (!$assignment_id || !$criteria_id || !$status) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

try {
    $conn->begin_transaction();

    // Insert or update criteria progress
    $sql = "INSERT INTO assignment_criteria_progress 
            (assignment_id, criteria_id, status, notes, completed_at) 
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            status = VALUES(status),
            notes = VALUES(notes),
            completed_at = CASE 
                WHEN VALUES(status) = 'completed' AND completed_at IS NULL THEN NOW()
                WHEN VALUES(status) != 'completed' THEN NULL
                ELSE completed_at 
            END";
    
    $stmt = $conn->prepare($sql);
    $completed_at = $status === 'completed' ? date('Y-m-d H:i:s') : null;
    $stmt->bind_param("iisss", $assignment_id, $criteria_id, $status, $notes, $completed_at);
    $stmt->execute();

    // Calculate new progress
    $sql = "SELECT 
                (SELECT COUNT(*) FROM assignment_criteria WHERE assignment_id = ?) as total,
                COALESCE((SELECT COUNT(*) FROM assignment_criteria_progress 
                 WHERE assignment_id = ? AND status = 'completed'), 0) as completed,
                EXISTS(SELECT 1 FROM assignment_criteria_progress 
                 WHERE assignment_id = ? AND criteria_id = ? AND status = 'completed') as is_completed";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiii", $assignment_id, $assignment_id, $assignment_id, $criteria_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    $total = $result['total'];
    $completed = $result['completed'];
    $is_completed = $result['is_completed'];
    $progress = $total > 0 ? round(($completed / $total) * 100, 1) : 0;
    
    // Debug information
    error_log("Progress Calculation - Assignment ID: $assignment_id");
    error_log("Total Criteria: $total");
    error_log("Completed Criteria: $completed");
    error_log("Current Criteria Status: " . ($is_completed ? 'completed' : 'not completed'));
    error_log("Progress Percentage: $progress%");
    
    // Update assignment progress
    $sql = "UPDATE access_assignments 
            SET progress_percentage = ?,
                completed_criteria = ?,
                total_criteria = ?,
                status = CASE 
                    WHEN ? = 100 THEN 'completed'
                    WHEN ? > 0 THEN 'in_progress'
                    ELSE 'not_started'
                END
            WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiiiii", $progress, $completed, $total, $progress, $progress, $assignment_id);
    $stmt->execute();

    // Log the progress update with more detail
    $sql = "INSERT INTO assignment_progress_log 
            (assignment_id, action_type, description, logged_at) 
            VALUES (?, 'criteria_update', ?, NOW())";
    
    $description = sprintf(
        "Criteria %d updated: %s. Progress: %d/%d (%.1f%%)",
        $criteria_id,
        $status === 'completed' ? 'Marked as complete' : 'Marked as not started',
        $completed,
        $total,
        $progress
    );
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $assignment_id, $description);
    $stmt->execute();

    $conn->commit();

    echo json_encode([
        'success' => true,
        'progress' => $progress,
        'completed' => $completed,
        'total' => $total,
        'is_completed' => (bool)$is_completed
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => 'Error updating criteria: ' . $e->getMessage()
    ]);
}
?> 