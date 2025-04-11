<?php
require_once '../../includes/db_connect.php';

// Get parameters
$habit_id = isset($_GET['habit_id']) ? intval($_GET['habit_id']) : 0;
$status = isset($_GET['status']) ? $_GET['status'] : '';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Validate inputs
if (!$habit_id || !in_array($status, ['completed', 'procrastinated', 'skipped'])) {
    echo '<div class="alert alert-danger">Invalid parameters</div>';
    exit;
}

try {
    // Get habit information
    $query = "SELECT h.name, hc.name as category_name, hc.color as category_color
              FROM habits h
              LEFT JOIN habit_categories hc ON h.category_id = hc.id
              WHERE h.id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $habit_id);
    $stmt->execute();
    $habit_result = $stmt->get_result();
    $habit = $habit_result->fetch_assoc();
    
    if (!$habit) {
        echo '<div class="alert alert-warning">Habit not found</div>';
        exit;
    }
    
    // Get completions with this status
    $query = "SELECT completion_date, completion_time, reason, notes
              FROM habit_completions
              WHERE habit_id = ? AND status = ? AND completion_date BETWEEN ? AND ?
              ORDER BY completion_date DESC, completion_time DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('isss', $habit_id, $status, $start_date, $end_date);
    $stmt->execute();
    $completions_result = $stmt->get_result();
    
    // Format output
    $output = '';
    
    // Habit info
    $output .= '<div class="habit-header mb-4">';
    $output .= '<h5 class="mb-1">' . htmlspecialchars($habit['name']) . '</h5>';
    $output .= '<span class="badge" style="background-color: ' . $habit['category_color'] . '">' . 
               htmlspecialchars($habit['category_name']) . '</span>';
    $output .= '</div>';
    
    // Status specific title
    $title = '';
    $icon = '';
    $color = '';
    
    switch ($status) {
        case 'completed':
            $title = 'Completions';
            $icon = 'check-circle';
            $color = 'success';
            break;
        case 'procrastinated':
            $title = 'Procrastinated Instances';
            $icon = 'clock';
            $color = 'warning';
            break;
        case 'skipped':
            $title = 'Skipped Instances';
            $icon = 'times-circle';
            $color = 'danger';
            break;
    }
    
    $output .= '<h6 class="mb-3 text-' . $color . '"><i class="fas fa-' . $icon . ' me-2"></i>' . $title . '</h6>';
    
    // Stats
    $completions_count = $completions_result->num_rows;
    
    if ($completions_count > 0) {
        $output .= '<div class="alert alert-' . $color . ' bg-opacity-10 mb-4">';
        $output .= $completions_count . ' ' . strtolower($title) . ' between ' . date('M j, Y', strtotime($start_date)) . 
                   ' and ' . date('M j, Y', strtotime($end_date));
        $output .= '</div>';
        
        // List of completions
        $output .= '<div class="list-group mb-4">';
        
        while ($completion = $completions_result->fetch_assoc()) {
            $date = date('l, F j, Y', strtotime($completion['completion_date']));
            $time = $completion['completion_time'] ? date('g:i A', strtotime($completion['completion_time'])) : 'N/A';
            
            $output .= '<div class="list-group-item border-' . $color . '">';
            $output .= '<div class="d-flex justify-content-between">';
            $output .= '<div><strong>' . $date . '</strong> at ' . $time . '</div>';
            $output .= '</div>';
            
            if ($completion['reason']) {
                $output .= '<div class="mt-2 small">';
                $output .= '<strong>Reason:</strong> ' . htmlspecialchars($completion['reason']);
                $output .= '</div>';
            }
            
            if ($completion['notes']) {
                $output .= '<div class="mt-1 small fst-italic">';
                $output .= htmlspecialchars($completion['notes']);
                $output .= '</div>';
            }
            
            $output .= '</div>';
        }
        
        $output .= '</div>';
    } else {
        $output .= '<div class="alert alert-info">No ' . strtolower($title) . ' found in the selected date range.</div>';
    }
    
    echo $output;
    
} catch (Exception $e) {
    echo '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
} 