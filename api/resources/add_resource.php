<?php
require_once '../../config/db_connect.php';

// Set headers
header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (!isset($input['title']) || !isset($input['subject_id']) || !isset($input['type'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Prepare the query
$query = "
    INSERT INTO resources (title, subject_id, type, link, notes)
    VALUES (?, ?, ?, ?, ?)
";

try {
    $stmt = $conn->prepare($query);
    $stmt->bind_param('sisss', 
        $input['title'],
        $input['subject_id'],
        $input['type'],
        $input['link'] ?? null,
        $input['notes'] ?? null
    );
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Resource added successfully',
            'id' => $conn->insert_id
        ]);
    } else {
        throw new Exception('Failed to add resource');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error adding resource: ' . $e->getMessage()
    ]);
}
?> 