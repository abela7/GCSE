<?php
require_once '../../includes/db_connect.php';

header('Content-Type: application/json');

try {
    // Get JSON data
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['resource_id'])) {
        throw new Exception('Resource ID is required');
    }
    
    $resource_id = (int)$data['resource_id'];
    
    // Get resource info before deletion (to delete file if it's an image)
    $stmt = $conn->prepare("SELECT resource_type, image_path FROM topic_resources WHERE id = ?");
    $stmt->bind_param("i", $resource_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $resource = $result->fetch_assoc();
    
    if (!$resource) {
        throw new Exception('Resource not found');
    }
    
    // If it's an image, delete the file
    if ($resource['resource_type'] === 'image' && $resource['image_path']) {
        $file_path = '../../' . ltrim($resource['image_path'], '/');
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }
    
    // Soft delete the resource
    $stmt = $conn->prepare("UPDATE topic_resources SET is_deleted = 1 WHERE id = ?");
    $stmt->bind_param("i", $resource_id);
    $stmt->execute();
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?> 