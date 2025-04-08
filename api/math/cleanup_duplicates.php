<?php
require_once '../../config/db_connect.php';

header('Content-Type: application/json');

try {
    // Start transaction
    $conn->begin_transaction();

    // First, identify and keep only the most recent progress record for each topic
    $cleanup_sql = "
        DELETE tp1 FROM topic_progress tp1
        INNER JOIN topic_progress tp2
        WHERE tp1.topic_id = tp2.topic_id
        AND tp1.id < tp2.id;
    ";
    
    $stmt = $conn->prepare($cleanup_sql);
    $stmt->execute();

    // Get the number of deleted records
    $deleted_count = $stmt->affected_rows;

    // Update section progress after cleanup
    $update_progress_sql = "
        UPDATE section_progress sp
        JOIN math_sections ms ON sp.section_id = ms.id
        SET sp.completed_topics = (
            SELECT COUNT(*)
            FROM topic_progress tp
            JOIN math_topics mt ON tp.topic_id = mt.id
            JOIN math_subsections msub ON mt.subsection_id = msub.id
            WHERE msub.section_id = ms.id
            AND tp.status = 'completed'
        )
    ";
    
    $stmt = $conn->prepare($update_progress_sql);
    $stmt->execute();

    // Commit transaction
    $conn->commit();

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => "Successfully removed $deleted_count duplicate records",
        'deleted_count' => $deleted_count
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to clean up duplicate records',
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?> 