<?php
// Include database connection
include '../../includes/db_connect.php';

// Start session
session_start();

// Initialize response array
$response = ['success' => false, 'message' => ''];

// Check if required parameters are provided
if (!isset($_POST['habit_id']) || !isset($_POST['status'])) {
    $response['message'] = 'Missing required parameters';
    echo json_encode($response);
    exit;
}

$habit_id = $_POST['habit_id'];
$status = $_POST['status'];
$current_date = date('Y-m-d');

// Validate status
if ($status !== 'completed' && $status !== 'procrastinated') {
    $response['message'] = 'Invalid status';
    echo json_encode($response);
    exit;
}

// Check if habit belongs to the user
$check_query = "SELECT h.id, h.point_rule_id FROM habits h WHERE h.id = ?";
$check_stmt = $conn->prepare($check_query);
$check_stmt->bind_param("i", $habit_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    $response['message'] = 'Habit not found';
    echo json_encode($response);
    exit;
}

// Get point rule information
$habit_data = $check_result->fetch_assoc();
$point_rule_id = $habit_data['point_rule_id'];

// Get points from point rule
$points_query = "SELECT completion_points, procrastinated_points FROM habit_point_rules WHERE id = ?";
$points_stmt = $conn->prepare($points_query);
$points_stmt->bind_param("i", $point_rule_id);
$points_stmt->execute();
$points_result = $points_stmt->get_result();
$points_data = $points_result->fetch_assoc();

$completion_points = $points_data['completion_points'];
$procrastinated_points = $points_data['procrastinated_points'];

// Begin transaction
$conn->begin_transaction();

try {
    // Check if a record already exists for today
    $check_completion = "SELECT id, status FROM habit_completions 
                         WHERE habit_id = ? AND completion_date = ?";
    $check_comp_stmt = $conn->prepare($check_completion);
    $check_comp_stmt->bind_param("is", $habit_id, $current_date);
    $check_comp_stmt->execute();
    $completion_result = $check_comp_stmt->get_result();
    
    if ($completion_result->num_rows > 0) {
        // Update existing record
        $completion_data = $completion_result->fetch_assoc();
        $completion_id = $completion_data['id'];
        $old_status = $completion_data['status'];
        
        $update_query = "UPDATE habit_completions 
                         SET status = ?, updated_at = NOW() 
                         WHERE id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("si", $status, $completion_id);
        $update_stmt->execute();
    } else {
        // Insert new record
        $current_time = date('H:i:s');
        $insert_query = "INSERT INTO habit_completions 
                         (habit_id, completion_date, completion_time, status, created_at, updated_at) 
                         VALUES (?, ?, ?, ?, NOW(), NOW())";
        $insert_stmt = $conn->prepare($insert_query);
        $insert_stmt->bind_param("isss", $habit_id, $current_date, $current_time, $status);
        $insert_stmt->execute();
    }
    
    // Award points to the user
    $points_to_award = ($status === 'completed') ? $completion_points : $procrastinated_points;
    
    // Record points as part of completion
    $update_points_query = "UPDATE habit_completions SET points_earned = ? 
                           WHERE habit_id = ? AND completion_date = ?";
    $points_update_stmt = $conn->prepare($update_points_query);
    $points_update_stmt->bind_param("dis", $points_to_award, $habit_id, $current_date);
    $points_update_stmt->execute();
    
    // Commit transaction
    $conn->commit();
    
    $response['success'] = true;
    $response['message'] = ($status === 'completed') ? 'Habit marked as completed' : 'Habit procrastinated';
    $response['points'] = $points_to_award;
    
} catch (Exception $e) {
    // Roll back transaction on error
    $conn->rollback();
    $response['message'] = 'Database error: ' . $e->getMessage();
}

// Send JSON response
header('Content-Type: application/json');
echo json_encode($response);
exit; 