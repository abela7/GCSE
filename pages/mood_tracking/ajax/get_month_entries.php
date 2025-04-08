<?php
// Include required files
require_once __DIR__ . '/../includes/functions.php';

// Get month parameter
$month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');

// Validate month format (YYYY-MM)
if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
    http_response_code(400);
    exit('Invalid month format');
}

// Get entries for the month
$month_entries = getMoodEntriesByDay($month);

// Get calendar data
$first_day_of_month = date('w', strtotime($month . '-01'));
$total_days = date('t', strtotime($month . '-01'));
$today = date('j');
$current_month_year = date('Y-m');

// Output calendar headers
$days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
foreach ($days as $day) {
    echo '<div class="calendar-header">' . $day . '</div>';
}

// Add empty cells for days before the 1st
for ($i = 0; $i < $first_day_of_month; $i++) {
    echo '<div class="calendar-day empty"></div>';
}

// Add days of the month
for ($day = 1; $day <= $total_days; $day++) {
    $date = $month . '-' . sprintf('%02d', $day);
    $is_today = ($day == $today && $month == $current_month_year);
    $has_entries = isset($month_entries[$day]);
    $avg_mood = $has_entries ? $month_entries[$day]['avg_mood'] : 0;
    $entry_count = $has_entries ? $month_entries[$day]['entry_count'] : 0;
    
    $class = 'calendar-day';
    if ($is_today) $class .= ' today';
    if ($has_entries) $class .= ' has-entries';
    
    echo '<div class="' . $class . '" onclick="viewDayEntries(\'' . $date . '\')">';
    echo '<span class="day-number">' . $day . '</span>';
    if ($has_entries) {
        $emoji = '';
        if ($avg_mood >= 4.5) $emoji = '😄';
        else if ($avg_mood >= 3.5) $emoji = '🙂';
        else if ($avg_mood >= 2.5) $emoji = '😐';
        else if ($avg_mood >= 1.5) $emoji = '😕';
        else $emoji = '😢';
        
        echo '<span class="entry-indicator">' . $emoji . '</span>';
    }
    echo '</div>';
}

// Add empty cells for days after the last day
$remaining_cells = 7 - (($first_day_of_month + $total_days) % 7);
if ($remaining_cells < 7) {
    for ($i = 0; $i < $remaining_cells; $i++) {
        echo '<div class="calendar-day empty"></div>';
    }
} 