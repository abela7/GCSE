<?php
// Include database connection
require_once '../../../config/db_connect.php';

// Check if entry ID is provided
if (!isset($_POST['entry_id']) || empty($_POST['entry_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Entry ID is required']);
    exit;
}

$entry_id = intval($_POST['entry_id']);

// Begin transaction
$conn->begin_transaction();

try {
    // Delete associated tags
    $tag_stmt = $conn->prepare("DELETE FROM mood_entry_tags WHERE mood_entry_id = ?");
    $tag_stmt->bind_param("i", $entry_id);
    $tag_stmt->execute();
    
    // Delete associated factors
    $factor_stmt = $conn->prepare("DELETE FROM mood_entry_factors WHERE mood_entry_id = ?");
    $factor_stmt->bind_param("i", $entry_id);
    $factor_stmt->execute();
    
    // Delete the entry
    $entry_stmt = $conn->prepare("DELETE FROM mood_entries WHERE id = ?");
    $entry_stmt->bind_param("i", $entry_id);
    $entry_stmt->execute();
    
    // Check if entry was deleted
    if ($entry_stmt->affected_rows === 0) {
        throw new Exception("Entry not found or already deleted");
    }
    
    // Commit transaction
    $conn->commit();
    
    // Return success response
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    // Return error response
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
