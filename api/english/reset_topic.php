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

    // Delete topic progress instead of updating
    $delete_progress_sql = "
        DELETE FROM eng_topic_progress 
        WHERE topic_id = ?
    ";
    
    $stmt = $conn->prepare($delete_progress_sql);
    $stmt->bind_param('i', $topic_id);
    $stmt->execute();

    // Delete study time tracking records
    $delete_time_sql = "
        DELETE FROM eng_study_time_tracking 
        WHERE topic_id = ?
    ";
    
    $stmt = $conn->prepare($delete_time_sql);
    $stmt->bind_param('i', $topic_id);
    $stmt->execute();

    // Update section progress
    $update_progress_sql = "
        UPDATE eng_section_progress sp
        JOIN eng_sections es ON sp.section_id = es.id
        JOIN eng_subsections esub ON es.id = esub.section_id
        JOIN eng_topics et ON esub.id = et.subsection_id
        SET sp.completed_topics = (
            SELECT COUNT(*)
            FROM eng_topic_progress tp2
            JOIN eng_topics et2 ON tp2.topic_id = et2.id
            JOIN eng_subsections esub2 ON et2.subsection_id = esub2.id
            WHERE esub2.section_id = es.id
            AND tp2.status = 'completed'
        )
        WHERE et.id = ?
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