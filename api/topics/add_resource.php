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
    if (!isset($_FILES['image'])) {
        echo json_encode(['success' => false, 'message' => 'No file was uploaded']);
        exit;
    }

    $file = $_FILES['image'];
    
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $message = 'Upload failed: ';
        switch ($file['error']) {
            case UPLOAD_ERR_INI_SIZE:
                $message .= 'File exceeds upload_max_filesize';
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $message .= 'File exceeds MAX_FILE_SIZE';
                break;
            case UPLOAD_ERR_PARTIAL:
                $message .= 'File was only partially uploaded';
                break;
            case UPLOAD_ERR_NO_FILE:
                $message .= 'No file was uploaded';
                break;
            default:
                $message .= 'Unknown error';
        }
        echo json_encode(['success' => false, 'message' => $message]);
        exit;
    }

    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    
    // Validate file type
    if (!in_array($file['type'], $allowed_types)) {
        echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPEG, PNG, and GIF are allowed']);
        exit;
    }

    // Validate file size (max 5MB)
    $max_size = 5 * 1024 * 1024; // 5MB in bytes
    if ($file['size'] > $max_size) {
        echo json_encode(['success' => false, 'message' => 'File is too large. Maximum size is 5MB']);
        exit;
    }

    // Create upload directory if it doesn't exist
    $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/GCSE/uploads/topic_resources/';
    if (!file_exists($upload_dir)) {
        if (!@mkdir($upload_dir, 0777, true)) {
            echo json_encode(['success' => false, 'message' => 'Failed to create upload directory']);
            exit;
        }
    }

    // Ensure directory is writable
    if (!is_writable($upload_dir)) {
        echo json_encode(['success' => false, 'message' => 'Upload directory is not writable']);
        exit;
    }

    // Generate unique filename
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $filepath = $upload_dir . $filename;

    // Move uploaded file
    if (!@move_uploaded_file($file['tmp_name'], $filepath)) {
        echo json_encode(['success' => false, 'message' => 'Failed to move uploaded file']);
        exit;
    }

    // Set proper permissions
    chmod($filepath, 0644);

    // Insert image resource
    $relative_path = '/GCSE/uploads/topic_resources/' . $filename;
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