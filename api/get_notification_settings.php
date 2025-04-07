<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

try {
    // Get notification settings for the current user
    $stmt = $pdo->prepare("SELECT task_reminders, exam_reminders, daily_motivation 
                          FROM notification_settings 
                          WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);

    // If no settings exist, create default settings
    if (!$settings) {
        $stmt = $pdo->prepare("INSERT INTO notification_settings (user_id, task_reminders, exam_reminders, daily_motivation) 
                              VALUES (?, 1, 1, 1)");
        $stmt->execute([$_SESSION['user_id']]);
        $settings = [
            'task_reminders' => 1,
            'exam_reminders' => 1,
            'daily_motivation' => 1
        ];
    }

    echo json_encode([
        'success' => true,
        'settings' => $settings
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} 