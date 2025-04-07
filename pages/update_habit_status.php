<?php
session_start();
require_once '../includes/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $habit_id = $_POST['habit_id'] ?? null;
    $status = $_POST['status'] ?? null;

    if ($habit_id && $status) {
        // Check if habit progress exists for today
        $check_query = "SELECT id FROM habit_progress WHERE habit_id = ? AND date = CURRENT_DATE";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param("i", $habit_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Update existing progress
            $update_query = "UPDATE habit_progress SET status = ? WHERE habit_id = ? AND date = CURRENT_DATE";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("si", $status, $habit_id);
        } else {
            // Insert new progress
            $insert_query = "INSERT INTO habit_progress (habit_id, date, status) VALUES (?, CURRENT_DATE, ?)";
            $stmt = $conn->prepare($insert_query);
            $stmt->bind_param("is", $habit_id, $status);
        }

        if ($stmt->execute()) {
            // Update habit stats
            $update_stats_query = "
                UPDATE habits 
                SET total_completions = total_completions + 1,
                    current_streak = current_streak + 1,
                    success_rate = (total_completions + 1) / (total_completions + total_procrastinated + total_skips + 1) * 100
                WHERE id = ?
            ";
            $stmt = $conn->prepare($update_stats_query);
            $stmt->bind_param("i", $habit_id);
            $stmt->execute();

            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error updating habit status']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?> 