<?php
require_once '../../includes/db_connect.php';

// Set headers for JSON response
header('Content-Type: application/json');

// Input validation
if (!isset($_POST['habit_id']) || !isset($_POST['date'])) {
    echo json_encode(['error' => 'Missing required parameters']);
    exit;
}

$habit_id = (int)$_POST['habit_id'];
$reference_date = $_POST['date'];

// Validate date format
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $reference_date)) {
    echo json_encode(['error' => 'Invalid date format']);
    exit;
}

try {
    // Calculate the date range (14 days before the reference date)
    $end_date = $reference_date;
    $start_date = date('Y-m-d', strtotime($reference_date . ' -13 days'));
    
    // Query to get completion history for the habit
    $history_query = "
        SELECT 
            DATE_FORMAT(completion_date, '%Y-%m-%d') as date,
            status
        FROM habit_completions
        WHERE habit_id = ?
        AND completion_date BETWEEN ? AND ?
        ORDER BY completion_date ASC";
    
    $stmt = $conn->prepare($history_query);
    $stmt->bind_param("iss", $habit_id, $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Process query results
    $completions = [];
    while ($row = $result->fetch_assoc()) {
        $completions[$row['date']] = $row['status'];
    }
    
    // Generate the complete 14-day dataset
    $history_data = [];
    $current_date = new DateTime($start_date);
    $end_date_obj = new DateTime($end_date);
    
    while ($current_date <= $end_date_obj) {
        $formatted_date = $current_date->format('Y-m-d');
        $display_date = $current_date->format('M j'); // Short month name and day
        
        // Determine status for this date
        $status = $completions[$formatted_date] ?? null;
        
        // If no completion record exists, the status is null (not tracked)
        $history_data[] = [
            'date' => $formatted_date,
            'displayDate' => $display_date,
            'status' => $status ?: 'not_tracked'
        ];
        
        // Move to next day
        $current_date->modify('+1 day');
    }
    
    // Return the data as JSON
    echo json_encode($history_data);
    
} catch (Exception $e) {
    // Handle any errors
    echo json_encode([
        'error' => 'An error occurred: ' . $e->getMessage(),
        'fallback_data' => generateFallbackData($reference_date)
    ]);
}

// Generate fallback data in case of error
function generateFallbackData($reference_date) {
    $data = [];
    $current_date = new DateTime($reference_date);
    $current_date->modify('-13 days');
    
    for ($i = 0; $i < 14; $i++) {
        $formatted_date = $current_date->format('Y-m-d');
        $display_date = $current_date->format('M j');
        
        // Generate random status (for fallback only)
        $rand = mt_rand(1, 10);
        if ($i == 13) { // Last day (reference date)
            $status = 'skipped';
        } elseif ($rand <= 6) {
            $status = 'completed';
        } elseif ($rand <= 8) {
            $status = 'procrastinated';
        } else {
            $status = 'skipped';
        }
        
        $data[] = [
            'date' => $formatted_date,
            'displayDate' => $display_date,
            'status' => $status
        ];
        
        $current_date->modify('+1 day');
    }
    
    return $data;
} 