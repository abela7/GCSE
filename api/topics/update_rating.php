<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Get the POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['topic_id']) || !isset($data['rating'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

$topic_id = $data['topic_id'];
$rating = intval($data['rating']);
$user_id = $_SESSION['user_id'];

// Validate rating value (1-5)
if ($rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'Invalid rating value']);
    exit;
}

try {
    // Check if user has already rated this topic
    $stmt = $pdo->prepare("SELECT id FROM topic_ratings WHERE topic_id = ? AND user_id = ?");
    $stmt->execute([$topic_id, $user_id]);
    $existing_rating = $stmt->fetch();

    if ($existing_rating) {
        // Update existing rating
        $stmt = $pdo->prepare("UPDATE topic_ratings SET rating = ?, updated_at = NOW() WHERE topic_id = ? AND user_id = ?");
        $stmt->execute([$rating, $topic_id, $user_id]);
    } else {
        // Insert new rating
        $stmt = $pdo->prepare("INSERT INTO topic_ratings (topic_id, user_id, rating, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$topic_id, $user_id, $rating]);
    }

    // Calculate and update average rating
    $stmt = $pdo->prepare("SELECT AVG(rating) as avg_rating FROM topic_ratings WHERE topic_id = ?");
    $stmt->execute([$topic_id]);
    $avg_rating = $stmt->fetch()['avg_rating'];

    // Update topic's average rating
    $stmt = $pdo->prepare("UPDATE topics SET average_rating = ? WHERE id = ?");
    $stmt->execute([$avg_rating, $topic_id]);

    echo json_encode(['success' => true, 'average_rating' => round($avg_rating, 1)]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?> 