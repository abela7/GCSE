<?php
require_once '../../config/db_connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get JSON input
$json = file_get_contents('php://input');
$data = json_decode($json, true);
$topic_id = isset($data['topic_id']) ? intval($data['topic_id']) : 0;

if (!$topic_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Topic ID is required']);
    exit;
}

try {
    // Start transaction
    $conn->begin_transaction();

    // Reset topic_progress
    $reset_progress_sql = "
        UPDATE topic_progress 
        SET status = 'not_started',
            total_time_spent = 0,
            confidence_level = 0,
            last_studied = NULL,
            completion_date = NULL
        WHERE topic_id = ?
    ";
    
    $stmt = $conn->prepare($reset_progress_sql);
    $stmt->bind_param('i', $topic_id);
    $stmt->execute();

    // Delete study time tracking records
    $delete_time_sql = "
        DELETE FROM study_time_tracking 
        WHERE topic_id = ?
    ";
    
    $stmt = $conn->prepare($delete_time_sql);
    $stmt->bind_param('i', $topic_id);
    $stmt->execute();

    // Update section and subsection progress
    // This will be handled by database triggers, but we need to ensure the progress is recalculated
    $update_progress_sql = "
        UPDATE section_progress sp
        JOIN math_sections ms ON sp.section_id = ms.id
        JOIN math_subsections msub ON ms.id = msub.section_id
        JOIN math_topics mt ON msub.id = mt.subsection_id
        SET sp.completed_topics = (
            SELECT COUNT(*)
            FROM topic_progress tp2
            JOIN math_topics mt2 ON tp2.topic_id = mt2.id
            JOIN math_subsections msub2 ON mt2.subsection_id = msub2.id
            WHERE msub2.section_id = ms.id
            AND tp2.status = 'completed'
        )
        WHERE mt.id = ?
    ";
    
    $stmt = $conn->prepare($update_progress_sql);
    $stmt->bind_param('i', $topic_id);
    $stmt->execute();

    // Commit transaction
    $conn->commit();

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Topic progress has been reset successfully'
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to reset topic progress',
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?> 