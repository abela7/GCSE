<?php
// Include database connection
require_once '../config/db_connect.php';

// Set content type to JSON
header('Content-Type: application/json');

// Get date parameter, default to today
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Validate date format
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid date format. Use YYYY-MM-DD.'
    ]);
    exit;
}

// Get habits with completion status for the specified date
$query = "SELECT h.*, c.name as category_name, c.color as category_color,
             (SELECT hc.status 
              FROM habit_completions hc 
              WHERE hc.habit_id = h.id 
              AND hc.completion_date = ?) as status
          FROM habits h
          LEFT JOIN habit_categories c ON h.category_id = c.id
          WHERE h.is_active = 1
          ORDER BY 
            CASE WHEN (
                SELECT hc.status 
                FROM habit_completions hc 
                WHERE hc.habit_id = h.id 
                AND hc.completion_date = ?
            ) = 'completed' THEN 1 ELSE 0 END,
            h.name ASC";

$stmt = $conn->prepare($query);
$stmt->bind_param('ss', $date, $date);
$stmt->execute();
$result = $stmt->get_result();

if ($result) {
    $habits = [];
    while ($habit = $result->fetch_assoc()) {
        // Set default status if null
        if (!$habit['status']) {
            $habit['status'] = 'pending';
        }
        $habits[] = $habit;
    }
    
    echo json_encode([
        'success' => true,
        'habits' => $habits,
        'date' => $date
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to retrieve habits: ' . $conn->error
    ]);
}

// Close database connection
close_connection($conn);
?> 