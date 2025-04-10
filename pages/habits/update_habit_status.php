<?php
// Include database connection
include '../../includes/db_connection.php';
include '../../includes/session.php';

// Initialize response array
$response = ['success' => false, 'message' => ''];

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'User not logged in';
    echo json_encode($response);
    exit;
}

// Check if required parameters are provided
if (!isset($_POST['habit_id']) || !isset($_POST['status'])) {
    $response['message'] = 'Missing required parameters';
    echo json_encode($response);
    exit;
}

$habit_id = $_POST['habit_id'];
$status = $_POST['status'];
$user_id = $_SESSION['user_id'];
$current_date = date('Y-m-d');

// Validate status
if ($status !== 'completed' && $status !== 'procrastinated') {
    $response['message'] = 'Invalid status';
    echo json_encode($response);
    exit;
}

// Check if habit belongs to the user
$check_query = "SELECT h.id, h.points FROM habits h WHERE h.id = ? AND h.user_id = ?";
$check_stmt = $conn->prepare($check_query);
$check_stmt->bind_param("ii", $habit_id, $user_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    $response['message'] = 'Habit not found or does not belong to user';
    echo json_encode($response);
    exit;
}

$habit_data = $check_result->fetch_assoc();
$points = $habit_data['points'];

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
        $insert_query = "INSERT INTO habit_completions 
                         (habit_id, user_id, completion_date, status, created_at, updated_at) 
                         VALUES (?, ?, ?, ?, NOW(), NOW())";
        $insert_stmt = $conn->prepare($insert_query);
        $insert_stmt->bind_param("iiss", $habit_id, $user_id, $current_date, $status);
        $insert_stmt->execute();
    }
    
    // Award points to the user
    $points_to_award = ($status === 'completed') ? $points : ($points / 2);
    
    $update_points = "UPDATE users SET points = points + ? WHERE id = ?";
    $points_stmt = $conn->prepare($update_points);
    $points_stmt->bind_param("di", $points_to_award, $user_id);
    $points_stmt->execute();
    
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