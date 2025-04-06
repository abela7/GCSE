<?php
require_once '../../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['question_id']) || !isset($data['question'])) {
        throw new Exception('Missing required fields');
    }

    $db = getDBConnection();
    
    // Start transaction
    $db->beginTransaction();
    
    $stmt = $db->prepare("
        UPDATE topic_questions 
        SET question = :question,
            edited_at = NOW()
        WHERE id = :question_id
    ");
    
    $stmt->execute([
        ':question_id' => $data['question_id'],
        ':question' => $data['question']
    ]);
    
    // Commit transaction
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Question updated successfully'
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($db)) {
        $db->rollBack();
    }
    
    error_log('Error updating question: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update question'
    ]);
} 