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

try {
    // Handle resource based on type
    if ($resource_type === 'youtube') {
        $youtube_url = isset($_POST['youtube_url']) ? trim($_POST['youtube_url']) : '';
        if (!$youtube_url) {
            throw new Exception('YouTube URL is required');
        }

        // Insert YouTube resource
        $stmt = $conn->prepare("INSERT INTO topic_resources (topic_id, title, resource_type, youtube_url) VALUES (?, ?, 'youtube', ?)");
        $stmt->bind_param("iss", $topic_id, $title, $youtube_url);

    } else if ($resource_type === 'image') {
        // Check if file was uploaded
        if (!isset($_FILES['image'])) {
            throw new Exception('No file was uploaded');
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
            throw new Exception($message);
        }

        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        
        // Validate file type
        if (!in_array($file['type'], $allowed_types)) {
            throw new Exception('Invalid file type. Only JPEG, PNG, and GIF are allowed');
        }

        // Validate file size (max 5MB)
        $max_size = 5 * 1024 * 1024; // 5MB in bytes
        if ($file['size'] > $max_size) {
            throw new Exception('File is too large. Maximum size is 5MB');
        }

        // Define upload directory path
        $base_path = dirname(dirname(dirname(__FILE__))); // Get the GCSE root directory
        $upload_dir = $base_path . '/uploads/topic_resources/';
        
        // Create upload directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            if (!@mkdir($upload_dir, 0755, true)) {
                throw new Exception('Failed to create upload directory');
            }
        }

        // Generate unique filename
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = uniqid() . '_' . time() . '.' . $extension;
        $filepath = $upload_dir . $filename;

        // Move uploaded file
        if (!@move_uploaded_file($file['tmp_name'], $filepath)) {
            error_log("Upload failed. Error details: " . print_r(error_get_last(), true));
            throw new Exception('Failed to move uploaded file. Please check server permissions.');
        }

        // Set proper permissions
        @chmod($filepath, 0644);

        // Insert image resource with relative path
        $relative_path = '/uploads/topic_resources/' . $filename;
        $stmt = $conn->prepare("INSERT INTO topic_resources (topic_id, title, resource_type, image_path) VALUES (?, ?, 'image', ?)");
        $stmt->bind_param("iss", $topic_id, $title, $relative_path);

    } else {
        throw new Exception('Invalid resource type');
    }

    // Execute the prepared statement
    if (!$stmt->execute()) {
        throw new Exception('Failed to add resource: ' . $conn->error);
    }

    echo json_encode(['success' => true, 'message' => 'Resource added successfully']);

} catch (Exception $e) {
    error_log("Resource upload error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    $conn->close();
}
?> 