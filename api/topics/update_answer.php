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
    
    if (!isset($data['question_id'])) {
        throw new Exception('Missing question ID');
    }

    $db = getDBConnection();
    
    // Start transaction
    $db->beginTransaction();
    
    // If we're updating the answer content
    if (isset($data['answer'])) {
        $stmt = $db->prepare("
            UPDATE topic_questions 
            SET answer = :answer,
                edited_at = NOW()
            WHERE id = :question_id
        ");
        
        $stmt->execute([
            ':question_id' => $data['question_id'],
            ':answer' => $data['answer']
        ]);
    }
    
    // If we're updating the is_correct status
    if (isset($data['is_correct'])) {
        $stmt = $db->prepare("
            UPDATE topic_questions 
            SET is_correct = :is_correct,
                edited_at = NOW()
            WHERE id = :question_id
        ");
        
        $stmt->execute([
            ':question_id' => $data['question_id'],
            ':is_correct' => $data['is_correct'] ? 1 : 0
        ]);
    }
    
    // Commit transaction
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Answer updated successfully'
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($db)) {
        $db->rollBack();
    }
    
    error_log('Error updating answer: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update answer'
    ]);
} 