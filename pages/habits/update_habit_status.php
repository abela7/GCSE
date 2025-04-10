<?php
require_once '../../includes/db_connect.php';
require_once '../../functions/habit_calculations.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Debug log function
function debug_log($message, $data = null) {
    $log = date('Y-m-d H:i:s') . " - " . $message;
    if ($data !== null) {
        $log .= " - Data: " . print_r($data, true);
    }
    error_log($log);
}

// Set timezone to London
date_default_timezone_set('Europe/London');

// Get POST data
$habit_id = $_POST['habit_id'] ?? null;
$status = $_POST['status'] ?? null;
$action = $_POST['action'] ?? null;
$scroll_position = $_POST['scroll_position'] ?? 0;
$reason_id = $_POST['reason_id'] ?? null;
$notes = $_POST['notes'] ?? null;

debug_log("Received POST data", $_POST);

if (!$habit_id) {
    debug_log("No habit_id provided");
    header('Location: index.php?scroll_to=' . $scroll_position . '&error=no_habit_id');
    exit;
}

try {
    // Handle reset action
    if ($action === 'reset') {
        debug_log("Processing reset action for habit_id: " . $habit_id);
        
        // Delete from habit_completions
        $stmt = $conn->prepare("DELETE FROM habit_completions WHERE habit_id = ? AND completion_date = CURDATE()");
        $stmt->bind_param("i", $habit_id);
        $stmt->execute();
        
        updateHabitStats($habit_id);
        header("Location: index.php?scroll_to=" . $scroll_position);
        exit;
    }

    // Handle status updates
    if (!$status) {
        debug_log("No status provided");
        header("Location: index.php?scroll_to=" . $scroll_position . '&error=no_status');
        exit;
    }

    debug_log("Processing status update", ['habit_id' => $habit_id, 'status' => $status, 'reason_id' => $reason_id]);

    $conn->begin_transaction();

    try {
        // Delete any existing completion for today
        $stmt = $conn->prepare("DELETE FROM habit_completions WHERE habit_id = ? AND completion_date = CURDATE()");
        $stmt->bind_param("i", $habit_id);
        $stmt->execute();

        // Get point rule for this habit
        $stmt = $conn->prepare("SELECT point_rule_id FROM habits WHERE id = ?");
        $stmt->bind_param("i", $habit_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $habit = $result->fetch_assoc();
        
        // Calculate points
        $points = calculatePoints($habit['point_rule_id'], $status);
        
        debug_log("Calculated points", ['point_rule_id' => $habit['point_rule_id'], 'points' => $points]);

        // Get reason text if reason_id is provided
        $reason_text = null;
        if ($reason_id) {
            $stmt = $conn->prepare("SELECT reason_text FROM habit_reasons WHERE id = ?");
            $stmt->bind_param("i", $reason_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $reason = $result->fetch_assoc();
            $reason_text = $reason['reason_text'];
        }

        // Insert new completion with reason text
        $stmt = $conn->prepare("INSERT INTO habit_completions 
                              (habit_id, completion_date, completion_time, status, reason, points_earned, notes) 
                              VALUES (?, CURDATE(), CURRENT_TIME(), ?, ?, ?, ?)");
        $stmt->bind_param("issis", $habit_id, $status, $reason_text, $points, $notes);
        $stmt->execute();

        // Update habit statistics
        updateHabitStats($habit_id);

        $conn->commit();
        debug_log("Successfully completed transaction");
        
        header("Location: index.php?scroll_to=" . $scroll_position);
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }

} catch (Exception $e) {
    debug_log("Error occurred: " . $e->getMessage());
    $error_message = urlencode($e->getMessage());
    header("Location: index.php?scroll_to=" . $scroll_position . "&error=" . $error_message);
    exit;
} 