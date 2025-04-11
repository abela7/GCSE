<?php
require_once '../../includes/db_connect.php';

// Get parameters
$habit_id = isset($_GET['habit_id']) ? intval($_GET['habit_id']) : 0;
$days = isset($_GET['days']) ? intval($_GET['days']) : 14;

// Set maximum to prevent abuse
if ($days > 90) $days = 90;

// Validate inputs
if (!$habit_id) {
    echo json_encode(['error' => 'Invalid habit ID']);
    exit;
}

try {
    // Calculate date range
    $end_date = date('Y-m-d');
    $start_date = date('Y-m-d', strtotime("-$days days"));
    
    // Query to get all completion statuses for this habit in the date range
    $query = "SELECT completion_date, status
              FROM habit_completions
              WHERE habit_id = ? AND completion_date BETWEEN ? AND ?
              ORDER BY completion_date ASC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('iss', $habit_id, $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Prepare array of dates for all days in the range
    $dates = [];
    $completed = [];
    $procrastinated = [];
    $skipped = [];
    
    // Initialize all days with zeros
    $current_date = new DateTime($start_date);
    $end_date_obj = new DateTime($end_date);
    
    while ($current_date <= $end_date_obj) {
        $date_string = $current_date->format('Y-m-d');
        $display_date = $current_date->format('M j');
        
        $dates[] = $display_date;
        $completed[$date_string] = 0;
        $procrastinated[$date_string] = 0;
        $skipped[$date_string] = 0;
        
        $current_date->modify('+1 day');
    }
    
    // Fill in the actual data
    while ($row = $result->fetch_assoc()) {
        $date = $row['completion_date'];
        
        if (isset($completed[$date]) && $row['status'] === 'completed') {
            $completed[$date]++;
        } else if (isset($procrastinated[$date]) && $row['status'] === 'procrastinated') {
            $procrastinated[$date]++;
        } else if (isset($skipped[$date]) && $row['status'] === 'skipped') {
            $skipped[$date]++;
        }
    }
    
    // Create response data
    $response = [
        'dates' => $dates,
        'completed' => array_values($completed),
        'procrastinated' => array_values($procrastinated),
        'skipped' => array_values($skipped)
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
} 