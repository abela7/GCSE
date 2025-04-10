<?php
require_once '../../includes/header.php';
require_once '../../includes/db_connect.php';

// Get filter parameters
$selected_month = $_GET['month'] ?? date('m');
$selected_year = $_GET['year'] ?? date('Y');
$habit_id = $_GET['habit_id'] ?? 'all';
$category_id = $_GET['category_id'] ?? 'all';

// Get categories for filtering
$categories_query = "SELECT id, name, color FROM habit_categories ORDER BY name";
$categories_result = $conn->query($categories_query);
$categories = [];
while ($cat = $categories_result->fetch_assoc()) {
    $categories[$cat['id']] = $cat;
}

// Get active habits for filtering
$habits_query = "SELECT id, name, category_id FROM habits WHERE is_active = 1 ORDER BY name";
$habits_result = $conn->query($habits_query);
$habits = [];
while ($habit = $habits_result->fetch_assoc()) {
    $habits[$habit['id']] = $habit;
}

// Calculate the first and last day of the selected month
$first_day = date('Y-m-01', strtotime("$selected_year-$selected_month-01"));
$last_day = date('Y-m-t', strtotime("$selected_year-$selected_month-01"));

// NEW FEATURE: Habit Frequency - Get schedule information for habits
// Get specific day schedule information
$schedule_query = "SELECT 
    h.id as habit_id, 
    h.name as habit_name,
    hs.day_of_week
    FROM habits h
    JOIN habit_schedule hs ON h.id = hs.habit_id
    WHERE h.is_active = 1";
    
if ($habit_id !== 'all') {
    $schedule_query .= " AND h.id = " . intval($habit_id);
}
if ($category_id !== 'all') {
    $schedule_query .= " AND h.category_id = " . intval($category_id);
}

$schedule_result = $conn->query($schedule_query);
$habit_schedules = [];
while ($schedule = $schedule_result->fetch_assoc()) {
    if (!isset($habit_schedules[$schedule['habit_id']])) {
        $habit_schedules[$schedule['habit_id']] = [
            'name' => $schedule['habit_name'],
            'days' => []
        ];
    }
    $habit_schedules[$schedule['habit_id']]['days'][] = (int)$schedule['day_of_week'];
}

// Get frequency-based habit information
$frequency_query = "SELECT 
    h.id as habit_id, 
    h.name as habit_name,
    hf.times_per_week,
    hf.week_starts_on
    FROM habits h
    JOIN habit_frequency hf ON h.id = hf.habit_id
    WHERE h.is_active = 1";
    
if ($habit_id !== 'all') {
    $frequency_query .= " AND h.id = " . intval($habit_id);
}
if ($category_id !== 'all') {
    $frequency_query .= " AND h.category_id = " . intval($category_id);
}

$frequency_result = $conn->query($frequency_query);
$habit_frequencies = [];
while ($frequency = $frequency_result->fetch_assoc()) {
    $habit_frequencies[$frequency['habit_id']] = [
        'name' => $frequency['habit_name'],
        'times_per_week' => (int)$frequency['times_per_week'],
        'week_starts_on' => (int)$frequency['week_starts_on']
    ];
}

// Fetch habit completion data for the month
$completions_query = "
    SELECT 
        h.id as habit_id,
        h.name as habit_name,
        h.category_id,
        DATE(hc.completion_date) as completion_date,
        TIME(hc.completion_date) as completion_time,
        hc.status,
        hc.notes,
        hc.reason
    FROM habits h
    LEFT JOIN habit_completions hc ON h.id = hc.habit_id 
        AND hc.completion_date BETWEEN ? AND ?
    WHERE h.is_active = 1 ";

$params = [$first_day, $last_day];
$types = "ss";

if ($habit_id !== 'all') {
    $completions_query .= " AND h.id = ?";
    $params[] = $habit_id;
    $types .= "i";
}

if ($category_id !== 'all') {
    $completions_query .= " AND h.category_id = ?";
    $params[] = $category_id;
    $types .= "i";
}

$stmt = $conn->prepare($completions_query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$completions_result = $stmt->get_result();

// Process completion data
$habit_completions = [];
$all_habit_completions = []; // Track all completions by habit and date for frequency calculation

while ($row = $completions_result->fetch_assoc()) {
    if ($row['completion_date']) {
        $key = $row['habit_id'] . '_' . $row['completion_date'];
        $habit_completions[$key] = [
            'status' => $row['status'],
            'time' => $row['completion_time'],
            'notes' => $row['notes'],
            'reason' => $row['reason'],
            'habit_name' => $row['habit_name']
        ];
        
        // NEW FEATURE: Habit Frequency - Track completions for frequency habits
        if (!isset($all_habit_completions[$row['habit_id']])) {
            $all_habit_completions[$row['habit_id']] = [];
        }
        if ($row['status'] === 'completed') {
            $all_habit_completions[$row['habit_id']][] = $row['completion_date'];
        }
    }
}

// Get month name and year for display
$month_name = date('F Y', strtotime("$selected_year-$selected_month-01"));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Habit Calendar View</title>
    <style>
        .calendar-container {
            margin-bottom: 1rem;
            overflow-x: auto;
        }
        .calendar {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            min-width: 280px;
            background: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .calendar th {
            background-color: #f8f9fa;
            padding: 0.3rem;
            text-align: center;
            border: 1px solid #dee2e6;
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
        }
        @media (max-width: 768px) {
            .calendar th {
                font-size: 0.7rem;
                padding: 0.2rem;
            }
        }
        .calendar td {
            height: 35px;
            border: 2px solid #dee2e6;
            padding: 0.15rem;
            text-align: right;
            position: relative;
            vertical-align: top;
            cursor: pointer;
        }
        .calendar td .date {
            font-size: 0.8rem;
            color: #495057;
            position: absolute;
            top: 2px;
            right: 3px;
            font-weight: 500;
        }
        .calendar td.current-day {
            border: 2px solid #0d6efd;
        }
        .calendar td.other-month {
            border: 2px solid #f8f9fa;
            background-color: #f8f9fa;
        }
        @media (max-width: 768px) {
            .calendar td {
                height: 25px;
                padding: 0.1rem;
            }
            .calendar td .date {
                font-size: 0.8rem;
                top: 2px;
                right: 3px;
            }
        }
        .calendar td .habit-status {
            display: none;
        }
        .legend {
            display: flex;
            gap: 0.5rem;
            margin: 0.5rem 0;
            justify-content: center;
            flex-wrap: wrap;
        }
        .legend-item {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            font-size: 0.75rem;
            color: #495057;
            padding: 0.15rem 0.5rem;
            border-radius: 3px;
            background-color: #f8f9fa;
        }
        .legend-item i {
            font-size: 0.75rem;
        }
        .month-title {
            text-align: center;
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
            font-weight: 700;
            color: #212529;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
        }
        .month-nav-btn {
            background: none;
            border: none;
            color: #495057;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 0.25rem;
            transition: all 0.2s;
        }
        .month-nav-btn:hover {
            background-color: #f8f9fa;
            color: #0d6efd;
        }
        @media (max-width: 768px) {
            .month-title {
                font-size: 1.1rem;
            }
        }
        /* Add styles for the modal */
        .habit-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1050;
        }
        .habit-modal-content {
            position: relative;
            background-color: #fff;
            margin: 15% auto;
            padding: 1.25rem;
            border-radius: 0.5rem;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.16);
        }
        .habit-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid #dee2e6;
        }
        .habit-modal-title {
            font-size: 1.1rem;
            font-weight: 500;
            margin: 0;
            color: #212529;
        }
        .habit-modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            line-height: 1;
            padding: 0.25rem;
            cursor: pointer;
            color: #6c757d;
        }
        .habit-modal-close:hover {
            color: #343a40;
        }
        .habit-details {
            margin-bottom: 0.75rem;
        }
        .habit-detail-item {
            margin-bottom: 0.5rem;
        }
        .habit-detail-label {
            font-weight: 500;
            color: #495057;
            margin-bottom: 0.25rem;
        }
        .habit-detail-value {
            color: #212529;
        }
        .calendar td {
            cursor: pointer;
        }
        .calendar td:hover {
            background-color: rgba(13, 110, 253, 0.05);
        }
        .modal-content {
            max-height: 85vh;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .modal-body {
            max-height: calc(85vh - 120px);
            overflow-y: auto;
            padding: 0;
            background-color: #fff;
        }
        .modal-header {
            border-bottom: 1px solid #dee2e6;
            padding: 1rem 1.5rem;
            background-color: #f8f9fa;
            border-top-left-radius: 12px;
            border-top-right-radius: 12px;
        }
        .modal-title {
            font-size: 1.25rem;
            font-weight: 500;
            color: #333;
        }
        .habit-entry {
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            padding: 1.25rem 1rem;
            margin-bottom: 0;
            transition: background-color 0.2s;
        }
        .habit-entry:last-child {
            border-bottom: none;
        }
        .habit-entry:not(:last-child) {
            position: relative;
        }
        .habit-entry:not(:last-child)::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 1rem;
            right: 1rem;
            height: 1px;
            background: linear-gradient(to right, transparent, rgba(0, 0, 0, 0.1), transparent);
        }
        .habit-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 0.75rem;
        }
        .habit-status {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0;
            font-size: 1rem;
            padding: 0.5rem 0.75rem;
            border-radius: 6px;
            font-weight: 500;
        }
        .habit-status.completed {
            background-color: rgba(40, 167, 69, 0.1);
            color: #1e7e34;
        }
        .habit-status.later {
            background-color: rgba(255, 193, 7, 0.1);
            color: #d39e00;
        }
        .habit-status.skipped {
            background-color: rgba(220, 53, 69, 0.1);
            color: #c82333;
        }
        .habit-notes {
            margin-top: 0.75rem;
            padding: 0.75rem;
            background-color: #f8f9fa;
            border-radius: 6px;
            color: #666;
            font-size: 0.95rem;
            line-height: 1.5;
        }
        .modal-close {
            position: absolute;
            right: 1rem;
            top: 1rem;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #666;
            cursor: pointer;
            padding: 0.25rem;
            line-height: 1;
            transition: color 0.2s;
        }
        .modal-close:hover {
            color: #333;
        }
        @media (max-width: 768px) {
            .modal-content {
                max-height: 90vh;
                margin: 1rem;
            }
            .modal-body {
                max-height: calc(90vh - 100px);
                padding: 1rem;
            }
            .habit-entry {
                padding: 1rem 0.75rem;
            }
            .habit-name {
                font-size: 1rem;
            }
            .habit-status {
                font-size: 0.9rem;
                padding: 0.4rem 0.6rem;
            }
            .habit-notes {
                padding: 0.5rem;
                font-size: 0.9rem;
            }
        }
        .habit-notes-container {
            position: relative;
            overflow: hidden;
            margin-top: 0.75rem;
        }
        .habit-notes-wrapper {
            display: flex;
            transition: transform 0.3s ease;
            width: 200%;
        }
        .habit-reason, .habit-notes {
            width: 50%;
            padding: 0.75rem;
            background-color: #f8f9fa;
            border-radius: 6px;
            color: #666;
            font-size: 0.95rem;
            line-height: 1.5;
            flex-shrink: 0;
        }
        .habit-notes-wrapper.show-notes {
            transform: translateX(-50%);
        }
        .notes-label {
            font-size: 0.8rem;
            color: #999;
            margin-bottom: 0.25rem;
            font-weight: 500;
        }
        
        @media (max-width: 768px) {
            .habit-reason, .habit-notes {
                padding: 0.5rem;
                font-size: 0.9rem;
            }
        }
        /* Status styles for all devices */
        .calendar td.completed {
            background-color: rgba(40, 167, 69, 0.1);
            border-bottom: 2px solid #28a745;
        }
        
        .calendar td.procrastinated {
            background-color: rgba(255, 193, 7, 0.1);
            border-bottom: 2px solid #ffc107;
        }
        
        .calendar td.skipped {
            background-color: rgba(220, 53, 69, 0.1);
            border-bottom: 2px solid #dc3545;
        }
        
        .visually-hidden {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }
        
        /* Update modal styles for better accessibility */
        .modal:focus {
            outline: none;
        }
        
        .modal-dialog {
            outline: none;
        }
        
        .modal-content {
            outline: none;
        }
        
        .modal-close:focus {
            outline: 2px solid #0d6efd;
            outline-offset: 2px;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3" id="filterForm">
                    <div class="col-md-3">
                        <label class="form-label">Month</label>
                        <input type="month" name="month_year" class="form-control" 
                               value="<?php echo "$selected_year-$selected_month"; ?>"
                               onchange="updateMonthYear(this.value)">
                        <input type="hidden" name="month" id="month_input" value="<?php echo $selected_month; ?>">
                        <input type="hidden" name="year" id="year_input" value="<?php echo $selected_year; ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Category</label>
                        <select name="category_id" class="form-select" onchange="updateHabits()">
                            <option value="all">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" 
                                        <?php echo $category_id == $cat['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Habit</label>
                        <select name="habit_id" class="form-select" id="habitSelect">
                            <option value="all">All Habits</option>
                            <?php foreach ($habits as $h): ?>
                                <option value="<?php echo $h['id']; ?>" 
                                        data-category="<?php echo $h['category_id']; ?>"
                                        <?php echo $habit_id == $h['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($h['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-grow-1">Update</button>
                            <a href="visual_report.php<?php 
                                $params = $_GET;
                                echo !empty($params) ? '?' . http_build_query($params) : ''; 
                            ?>" class="btn btn-secondary">
                                <i class="fas fa-chart-line"></i>
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Legend -->
        <div class="legend">
            <div class="legend-item">
                <i class="fas fa-check" style="color: #28a745;"></i>
                <span>Completed</span>
            </div>
            <div class="legend-item">
                <i class="fas fa-clock" style="color: #ffc107;"></i>
                <span>Later</span>
            </div>
            <div class="legend-item">
                <i class="fas fa-times" style="color: #dc3545;"></i>
                <span>Skipped</span>
            </div>
        </div>

        <!-- Calendar -->
        <div class="calendar-container">
            <div class="month-title">
                <button type="button" class="month-nav-btn" onclick="changeMonth(-1)">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <span><?php echo $month_name; ?></span>
                <button type="button" class="month-nav-btn" onclick="changeMonth(1)">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
            <table class="calendar">
                <thead>
                    <tr>
                        <th>Sun</th>
                        <th>Mon</th>
                        <th>Tue</th>
                        <th>Wed</th>
                        <th>Thu</th>
                        <th>Fri</th>
                        <th>Sat</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $first_day_of_week = date('w', strtotime($first_day));
                    $last_day_of_month = date('d', strtotime($last_day));
                    $day_count = 1;
                    $current_date = new DateTime($first_day);

                    // Previous month days
                    echo "<tr>";
                    for ($i = 0; $i < $first_day_of_week; $i++) {
                        $prev_date = clone $current_date;
                        $prev_date->modify('-' . ($first_day_of_week - $i) . ' days');
                        echo "<td class='other-month'><div class='date'>" . $prev_date->format('d') . "</div></td>";
                    }

                    // Current month days
                    while ($day_count <= $last_day_of_month) {
                        if (($day_count + $first_day_of_week - 1) % 7 == 0) {
                            echo "</tr><tr>";
                        }

                        $is_current_day = $current_date->format('Y-m-d') === date('Y-m-d');
                        $current_date_str = $current_date->format('Y-m-d');
                        $td_classes = [];
                        
                        if ($is_current_day) {
                            $td_classes[] = 'current-day';
                        }

                        if ($habit_id !== 'all') {
                            $key = $habit_id . '_' . $current_date_str;
                            $status = $habit_completions[$key] ?? null;
                            if ($status) {
                                $td_classes[] = $status['status'];
                            }
                        } else {
                            // For "All Habits", determine the dominant status
                            $day_statuses = [];
                            foreach ($habits as $h) {
                                if ($category_id === 'all' || $h['category_id'] == $category_id) {
                                    $key = $h['id'] . '_' . $current_date_str;
                                    if (isset($habit_completions[$key])) {
                                        $day_statuses[] = $habit_completions[$key];
                                    }
                                }
                            }
                            
                            if (!empty($day_statuses)) {
                                $completed = count(array_filter($day_statuses, fn($s) => $s['status'] === 'completed'));
                                $procrastinated = count(array_filter($day_statuses, fn($s) => $s['status'] === 'procrastinated'));
                                $skipped = count(array_filter($day_statuses, fn($s) => $s['status'] === 'skipped'));
                                
                                // Set background based on dominant status
                                if ($completed > $procrastinated && $completed > $skipped) {
                                    $td_classes[] = 'completed';
                                } elseif ($procrastinated > $completed && $procrastinated > $skipped) {
                                    $td_classes[] = 'procrastinated';
                                } elseif ($skipped > $completed && $skipped > $procrastinated) {
                                    $td_classes[] = 'skipped';
                                }
                            }
                        }

                        echo "<td class='" . implode(' ', $td_classes) . "' data-date='" . $current_date_str . "'>";
                        echo "<div class='date'>$day_count</div>";
                        echo "</td>";
                        
                        $current_date->modify('+1 day');
                        $day_count++;
                    }

                    // Next month days
                    $remaining_days = 7 - (($day_count + $first_day_of_week - 1) % 7);
                    if ($remaining_days < 7) {
                        for ($i = 1; $i <= $remaining_days; $i++) {
                            echo "<td class='other-month'><div class='date'>$i</div></td>";
                        }
                    }
                    echo "</tr>";
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="habitModal" tabindex="-1" role="dialog" aria-labelledby="habitModalLabel">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="habitModalLabel"></h5>
                    <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="Close">
                        <span class="visually-hidden">Close</span>
                        <i class="fas fa-times" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="modal-body" id="habitModalBody" role="main">
                </div>
            </div>
        </div>
    </div>

    <script>
    function updateMonthYear(value) {
        const [year, month] = value.split('-');
        document.getElementById('month_input').value = month;
        document.getElementById('year_input').value = year;
    }

    function updateHabits() {
        const categorySelect = document.querySelector('select[name="category_id"]');
        const habitSelect = document.getElementById('habitSelect');
        const selectedCategory = categorySelect.value;
        const currentHabitValue = habitSelect.value;
        
        Array.from(habitSelect.options).forEach(option => {
            if (option.value === 'all') {
                option.style.display = '';
                return;
            }
            
            const habitCategory = option.getAttribute('data-category');
            if (selectedCategory === 'all' || selectedCategory === habitCategory) {
                option.style.display = '';
            } else {
                option.style.display = 'none';
            }
        });

        const selectedOption = habitSelect.options[habitSelect.selectedIndex];
        if (selectedOption && selectedOption.style.display === 'none') {
            habitSelect.value = 'all';
        }
    }

    document.addEventListener('DOMContentLoaded', updateHabits);

    function showHabitDetails(date, habits) {
        const modalTitle = document.getElementById('habitModalLabel');
        const modalBody = document.getElementById('habitModalBody');
        
        // Format the date
        const dateObj = new Date(date);
        const options = { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' };
        modalTitle.textContent = dateObj.toLocaleDateString('en-US', options);
        
        // Clear previous content
        modalBody.innerHTML = '';
        
        // Add each habit's details
        habits.forEach(habit => {
            const habitDiv = document.createElement('div');
            habitDiv.className = 'habit-entry';
            
            let statusClass, statusIcon, statusText;
            
            if (habit.status === 'No data') {
                statusClass = 'text-muted';
                statusIcon = '•';
                statusText = 'No data';
            } else {
                statusClass = habit.status === 'completed' ? 'completed' : 
                            habit.status === 'procrastinated' ? 'later' : 'skipped';
                statusIcon = habit.status === 'completed' ? '✓' : 
                           habit.status === 'procrastinated' ? '⏰' : '✕';
                statusText = habit.status === 'completed' ? 'Completed' : 
                           habit.status === 'procrastinated' ? 'Later' : 'Skipped';
            }
            
            const hasReason = habit.status !== 'No data' && habit.reason;
            const hasNotes = habit.status !== 'No data' && habit.notes;
            
            habitDiv.innerHTML = `
                <div class="habit-name">${habit.name}</div>
                <div class="habit-status ${statusClass}">
                    <span>${statusIcon}</span>
                    <span>${statusText}</span>
                </div>
                ${(hasReason || hasNotes) ? `
                <div class="habit-notes-container">
                    <div class="habit-notes-wrapper">
                        <div class="habit-reason">
                            <div class="notes-label">Reason</div>
                            ${hasReason ? habit.reason : 'No reason provided'}
                        </div>
                        <div class="habit-notes">
                            <div class="notes-label">Notes</div>
                            ${hasNotes ? habit.notes : 'No notes added'}
                        </div>
                    </div>
                </div>` : ''}
            `;
            
            modalBody.appendChild(habitDiv);

            // Add swipe functionality
            if (hasReason || hasNotes) {
                const notesContainer = habitDiv.querySelector('.habit-notes-container');
                const notesWrapper = habitDiv.querySelector('.habit-notes-wrapper');
                let startX = 0;
                let currentX = 0;
                let isShowingNotes = false;

                // Touch events for mobile
                notesContainer.addEventListener('touchstart', (e) => {
                    startX = e.touches[0].clientX;
                });

                notesContainer.addEventListener('touchmove', (e) => {
                    e.preventDefault();
                    const diffX = e.touches[0].clientX - startX;
                    if ((diffX < 0 && !isShowingNotes) || (diffX > 0 && isShowingNotes)) {
                        currentX = diffX;
                        notesWrapper.style.transform = `translateX(${isShowingNotes ? -50 + (currentX / notesContainer.offsetWidth) * 100 : (currentX / notesContainer.offsetWidth) * 100}%)`;
                    }
                });

                notesContainer.addEventListener('touchend', (e) => {
                    const diffX = currentX;
                    const threshold = notesContainer.offsetWidth * 0.2;
                    
                    if (Math.abs(diffX) > threshold) {
                        isShowingNotes = !isShowingNotes;
                    }
                    
                    notesWrapper.style.transform = isShowingNotes ? 'translateX(-50%)' : 'translateX(0)';
                    currentX = 0;
                });

                // Click event for desktop
                notesContainer.addEventListener('click', () => {
                    isShowingNotes = !isShowingNotes;
                    notesWrapper.style.transform = isShowingNotes ? 'translateX(-50%)' : 'translateX(0)';
                });
            }
        });
        
        // Show the modal using Bootstrap's modal
        const modal = new bootstrap.Modal(document.getElementById('habitModal'));
        modal.show();
    }

    function changeMonth(offset) {
        const currentDate = new Date(<?php echo $selected_year; ?>, <?php echo $selected_month - 1; ?>, 1);
        currentDate.setMonth(currentDate.getMonth() + offset);
        
        const month = (currentDate.getMonth() + 1).toString().padStart(2, '0');
        const year = currentDate.getFullYear();
        
        document.getElementById('month_input').value = month;
        document.getElementById('year_input').value = year;
        document.getElementById('filterForm').submit();
    }

    // Add click event handlers to calendar cells
    document.addEventListener('DOMContentLoaded', function() {
        const habitData = <?php echo json_encode($habit_completions); ?>;
        const allHabits = <?php echo json_encode($habits); ?>;
        const selectedHabit = '<?php echo $habit_id; ?>';
        const selectedCategory = '<?php echo $category_id; ?>';

        document.querySelectorAll('.calendar td[data-date]').forEach(td => {
            td.addEventListener('click', function() {
                const date = this.getAttribute('data-date');
                if (!date) return;

                const habitsForDay = [];
                
                if (selectedHabit !== 'all') {
                    // Single habit view
                    const key = selectedHabit + '_' + date;
                    if (habitData[key]) {
                        habitsForDay.push({
                            name: habitData[key].habit_name,
                            status: habitData[key].status,
                            time: habitData[key].time,
                            notes: habitData[key].notes,
                            reason: habitData[key].reason
                        });
                    }
                } else {
                    // All habits view
                    Object.entries(allHabits).forEach(([habitId, habit]) => {
                        // Check category filter
                        if (selectedCategory === 'all' || habit.category_id == selectedCategory) {
                            const key = habitId + '_' + date;
                            if (habitData[key]) {
                                habitsForDay.push({
                                    name: habitData[key].habit_name,
                                    status: habitData[key].status,
                                    time: habitData[key].time,
                                    notes: habitData[key].notes,
                                    reason: habitData[key].reason
                                });
                            }
                        }
                    });
                }

                if (habitsForDay.length > 0) {
                    showHabitDetails(date, habitsForDay);
                } else {
                    showHabitDetails(date, [{
                        name: selectedHabit !== 'all' ? allHabits[selectedHabit].name : 'No habits',
                        status: 'No data',
                        time: '-',
                        notes: 'No completion data for this day',
                        reason: 'No completion data for this day'
                    }]);
                }
            });
        });
    });

    // Add focus management for the modal
    document.getElementById('habitModal').addEventListener('shown.bs.modal', function () {
        const closeButton = this.querySelector('.modal-close');
        if (closeButton) {
            closeButton.focus();
        }
    });

    document.getElementById('habitModal').addEventListener('hidden.bs.modal', function () {
        const lastClickedCell = document.querySelector('.calendar td:focus');
        if (lastClickedCell) {
            lastClickedCell.focus();
        }
    });
    </script>
</body>
</html>

<?php require_once '../../includes/footer.php'; ?> 