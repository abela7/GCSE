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
    
    if (!isset($data['topic_id']) || !isset($data['content'])) {
        throw new Exception('Missing required fields');
    }

    $db = getDBConnection();
    
    // Start transaction
    $db->beginTransaction();
    
    // Check if a note exists for this topic
    $check_stmt = $db->prepare("SELECT id FROM topic_notes WHERE topic_id = ? LIMIT 1");
    $check_stmt->execute([$data['topic_id']]);
    $existing_note = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing_note) {
        // Update existing note
        $stmt = $db->prepare("
            UPDATE topic_notes 
            SET content = :content,
                edited_at = NOW()
            WHERE topic_id = :topic_id
        ");
    } else {
        // Insert new note
        $stmt = $db->prepare("
            INSERT INTO topic_notes (topic_id, content, created_at, edited_at)
            VALUES (:topic_id, :content, NOW(), NOW())
        ");
    }
    
    $stmt->execute([
        ':topic_id' => $data['topic_id'],
        ':content' => $data['content']
    ]);
    
    // Commit transaction
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Notes updated successfully'
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($db)) {
        $db->rollBack();
    }
    
    error_log('Error updating notes: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update notes'
    ]);
} 