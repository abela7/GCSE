<?php
header('Content-Type: application/json');
require_once '../../config/db_connect.php';

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get POST data
$topic_id = isset($_POST['topic_id']) ? intval($_POST['topic_id']) : 0;
$resource_type = isset($_POST['resource_type']) ? $_POST['resource_type'] : '';
$title = isset($_POST['title']) ? trim($_POST['title']) : '';

if (!$topic_id || !$resource_type || !$title) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Handle resource based on type
if ($resource_type === 'youtube') {
    $youtube_url = isset($_POST['youtube_url']) ? trim($_POST['youtube_url']) : '';
    if (!$youtube_url) {
        echo json_encode(['success' => false, 'message' => 'YouTube URL is required']);
        exit;
    }

    // Insert YouTube resource
    $stmt = $conn->prepare("INSERT INTO topic_resources (topic_id, title, resource_type, youtube_url) VALUES (?, ?, 'youtube', ?)");
    $stmt->bind_param("iss", $topic_id, $title, $youtube_url);

} else if ($resource_type === 'image') {
    // Check if file was uploaded
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'Image file is required']);
        exit;
    }

    $file = $_FILES['image'];
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    
    // Validate file type
    if (!in_array($file['type'], $allowed_types)) {
        echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPEG, PNG, and GIF are allowed']);
        exit;
    }

    // Create upload directory if it doesn't exist
    $upload_dir = '../../uploads/topic_resources/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $filepath = $upload_dir . $filename;

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        echo json_encode(['success' => false, 'message' => 'Failed to upload file']);
        exit;
    }

    // Insert image resource
    $relative_path = '/uploads/topic_resources/' . $filename;
    $stmt = $conn->prepare("INSERT INTO topic_resources (topic_id, title, resource_type, image_path) VALUES (?, ?, 'image', ?)");
    $stmt->bind_param("iss", $topic_id, $title, $relative_path);

} else {
    echo json_encode(['success' => false, 'message' => 'Invalid resource type']);
    exit;
}

// Execute the prepared statement
if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Resource added successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to add resource: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?> 