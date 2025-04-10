<?php
require_once '../../includes/header.php';
require_once '../../includes/db_connect.php';  // Database connection

// Set timezone to London
date_default_timezone_set('Europe/London');

// Get today's date
$today = date('Y-m-d');
$today_day_of_week = date('w'); // 0 (Sunday) through 6 (Saturday)

// Get week bounds for frequency-based habits
function getWeekBounds($start_day = 0) {
    $today = date('Y-m-d');
    $today_day = date('w');
    
    // Calculate days to subtract to get to start of week
    $days_to_start = ($today_day - $start_day + 7) % 7;
    
    $week_start = date('Y-m-d', strtotime("-{$days_to_start} days", strtotime($today)));
    $week_end = date('Y-m-d', strtotime("+6 days", strtotime($week_start)));
    
    return ['start' => $week_start, 'end' => $week_end];
}

// Get all habits with their categories and point rules
$habits_query = "SELECT h.*, hc.name as category_name, hc.color as category_color, hc.icon as category_icon,
                hpr.name as point_rule_name, hpr.completion_points, hpr.procrastinated_points,
                
                -- Get habit completion status
                (SELECT status FROM habit_completions 
                 WHERE habit_id = h.id AND completion_date = ?) as today_status,
                (SELECT completion_time FROM habit_completions 
                 WHERE habit_id = h.id AND completion_date = ?) as completion_time,
                (SELECT points_earned FROM habit_completions 
                 WHERE habit_id = h.id AND completion_date = ?) as today_points,
                 
                -- Get frequency data if available
                (SELECT hf.times_per_week FROM habit_frequency hf WHERE hf.habit_id = h.id) as times_per_week,
                (SELECT hf.week_starts_on FROM habit_frequency hf WHERE hf.habit_id = h.id) as week_starts_on,
                
                -- Count completions this week for frequency-based habits
                (SELECT COUNT(*) FROM habit_completions hc 
                 JOIN habit_frequency hf ON h.id = hf.habit_id
                 WHERE hc.habit_id = h.id 
                 AND hc.status = 'completed'
                 AND hc.completion_date BETWEEN 
                    (SELECT DATE_SUB(CURRENT_DATE, INTERVAL (DAYOFWEEK(CURRENT_DATE) - 1 + 7 - hf.week_starts_on) % 7 DAY))
                    AND 
                    (SELECT DATE_ADD(DATE_SUB(CURRENT_DATE, INTERVAL (DAYOFWEEK(CURRENT_DATE) - 1 + 7 - hf.week_starts_on) % 7 DAY), INTERVAL 6 DAY))
                 ) as completions_this_week
                 
                FROM habits h
                LEFT JOIN habit_categories hc ON h.category_id = hc.id
                LEFT JOIN habit_point_rules hpr ON h.point_rule_id = hpr.id
                WHERE h.is_active = 1
                AND (
                    -- Daily habits (no schedule entries)
                    (NOT EXISTS (SELECT 1 FROM habit_schedule hs WHERE hs.habit_id = h.id) 
                     AND NOT EXISTS (SELECT 1 FROM habit_frequency hf WHERE hf.habit_id = h.id))
                    
                    -- OR specific day habits scheduled for today
                    OR EXISTS (SELECT 1 FROM habit_schedule hs WHERE hs.habit_id = h.id AND hs.day_of_week = ?)
                    
                    -- OR frequency-based habits that haven't met their weekly quota
                    OR EXISTS (
                        SELECT 1 FROM habit_frequency hf 
                        WHERE hf.habit_id = h.id
                        AND hf.times_per_week > (
                            SELECT COUNT(*) FROM habit_completions hc 
                            WHERE hc.habit_id = h.id 
                            AND hc.status = 'completed'
                            AND hc.completion_date BETWEEN 
                                (SELECT DATE_SUB(CURRENT_DATE, INTERVAL (DAYOFWEEK(CURRENT_DATE) - 1 + 7 - hf.week_starts_on) % 7 DAY))
                                AND 
                                (SELECT DATE_ADD(DATE_SUB(CURRENT_DATE, INTERVAL (DAYOFWEEK(CURRENT_DATE) - 1 + 7 - hf.week_starts_on) % 7 DAY), INTERVAL 6 DAY))
                        )
                    )
                )
                ORDER BY h.target_time";

$stmt = $conn->prepare($habits_query);
$stmt->bind_param("sssi", $today, $today, $today, $today_day_of_week);
$stmt->execute();
$habits_result = $stmt->get_result();

// Separate habits into morning and evening
$morning_habits = [];
$evening_habits = [];
while ($habit = $habits_result->fetch_assoc()) {
    // Set default icon if none is set
    if (empty($habit['category_icon'])) {
        $habit['category_icon'] = 'fas fa-check-circle';
    }
    
    $time = strtotime($habit['target_time']);
    if ($time < strtotime('12:00:00')) {
        $morning_habits[] = $habit;
    } else {
        $evening_habits[] = $habit;
    }
}

// Get selected filter (default to today's habits)
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'today';

// Get time frame (default to today)
$time_frame = isset($_GET['time']) ? $_GET['time'] : 'today';
if ($time_frame == 'today') {
    $date_for_habits = $today;
} else if ($time_frame == 'yesterday') {
    $date_for_habits = date('Y-m-d', strtotime('-1 day'));
} else {
    $date_for_habits = $today;
}

// Get the current day of the week (0 = Sunday, 6 = Saturday)
$today_day_of_week = date('w');

// Get week boundaries
function getWeekBounds($start_day = 0) {
    // 0 = Sunday, 1 = Monday, 6 = Saturday
    $today = new DateTime();
    $day_of_week = (int)$today->format('w');
    
    // Calculate the difference between current day and start day
    $diff = $day_of_week - $start_day;
    if ($diff < 0) $diff += 7;
    
    $week_start = clone $today;
    $week_start->sub(new DateInterval('P'.$diff.'D'));
    $week_start->setTime(0, 0, 0);
    
    $week_end = clone $week_start;
    $week_end->add(new DateInterval('P6D'));
    $week_end->setTime(23, 59, 59);
    
    return [
        'start' => $week_start->format('Y-m-d'),
        'end' => $week_end->format('Y-m-d')
    ];
}

// Retrieve categories for the current user
$categories_query = "SELECT * FROM categories WHERE user_id = ?";
$categories_stmt = $conn->prepare($categories_query);
$categories_stmt->bind_param('i', $user_id);
$categories_stmt->execute();
$categories_result = $categories_stmt->get_result();

$categories = [];
while ($row = $categories_result->fetch_assoc()) {
    $categories[$row['id']] = $row;
}

$categories_stmt->close();

// Display greeting based on time of day
$hour = date('H');
if ($hour < 12) {
    $greeting = "Good Morning";
} elseif ($hour < 18) {
    $greeting = "Good Afternoon";
} else {
    $greeting = "Good Evening";
}

$user_query = "SELECT username FROM users WHERE id = ?";
$user_stmt = $conn->prepare($user_query);
$user_stmt->bind_param('i', $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();
$user_stmt->close();

$username = htmlspecialchars($user['username']);
?>

<div class="container-fluid pb-5 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3"><?php echo $greeting; ?>, <?php echo $username; ?>!</h1>
            <p class="lead">Track your habits and build consistency</p>
        </div>
        <a href="manage_habits.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add Habit</a>
    </div>

    <!-- Filter options -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <div class="btn-group" role="group">
                        <a href="?filter=all" class="btn btn-outline-primary <?php echo $filter == 'all' ? 'active' : ''; ?>">
                            <i class="bi bi-calendar-range"></i> All Habits
                        </a>
                        <a href="?filter=today" class="btn btn-outline-primary <?php echo $filter == 'today' ? 'active' : ''; ?>">
                            <i class="bi bi-calendar-check"></i> Today's Habits
                        </a>
                        <a href="?filter=weekly" class="btn btn-outline-primary <?php echo $filter == 'weekly' ? 'active' : ''; ?>">
                            <i class="bi bi-calendar-week"></i> Weekly Progress
                        </a>
                    </div>
                </div>
                <div class="col-md-6 text-md-end mt-3 mt-md-0">
                    <div class="btn-group" role="group">
                        <a href="?filter=<?php echo $filter; ?>&time=yesterday" class="btn btn-sm btn-outline-secondary <?php echo $time_frame == 'yesterday' ? 'active' : ''; ?>">
                            Yesterday
                        </a>
                        <a href="?filter=<?php echo $filter; ?>&time=today" class="btn btn-sm btn-outline-secondary <?php echo $time_frame == 'today' ? 'active' : ''; ?>">
                            Today
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php
    // Define habit query based on filter
    $week_bounds = getWeekBounds(0); // Default to Sunday start

    if ($filter == 'all') {
        // Show all habits regardless of schedule
        $habits_query = "SELECT h.*, c.name as category_name, c.color as category_color, c.icon as category_icon,
            COALESCE(hc.status, 'pending') as status,
            (SELECT COUNT(*) FROM habit_completions WHERE habit_id = h.id AND status = 'completed' AND completion_date BETWEEN ? AND ?) as week_completed
            FROM habits h
            LEFT JOIN categories c ON h.category_id = c.id
            LEFT JOIN habit_completions hc ON h.id = hc.habit_id AND hc.completion_date = ?
            WHERE h.user_id = ?
            ORDER BY h.target_time ASC";
        $stmt = $conn->prepare($habits_query);
        $stmt->bind_param('sssi', $week_bounds['start'], $week_bounds['end'], $date_for_habits, $user_id);
    } elseif ($filter == 'weekly') {
        // Show all habits with weekly progress
        $habits_query = "SELECT h.*, c.name as category_name, c.color as category_color, c.icon as category_icon,
            COALESCE(hc.status, 'pending') as status,
            (SELECT COUNT(*) FROM habit_completions WHERE habit_id = h.id AND status = 'completed' AND completion_date BETWEEN ? AND ?) as week_completed,
            (SELECT times_per_week FROM habit_frequency WHERE habit_id = h.id) as times_per_week
            FROM habits h
            LEFT JOIN categories c ON h.category_id = c.id
            LEFT JOIN habit_completions hc ON h.id = hc.habit_id AND hc.completion_date = ?
            WHERE h.user_id = ?
            ORDER BY h.target_time ASC";
        $stmt = $conn->prepare($habits_query);
        $stmt->bind_param('sssi', $week_bounds['start'], $week_bounds['end'], $date_for_habits, $user_id);
    } else {
        // Show only today's habits
        $habits_query = "SELECT h.*, c.name as category_name, c.color as category_color, c.icon as category_icon,
            COALESCE(hc.status, 'pending') as status,
            (SELECT COUNT(*) FROM habit_completions WHERE habit_id = h.id AND status = 'completed' AND completion_date BETWEEN ? AND ?) as week_completed,
            (SELECT times_per_week FROM habit_frequency WHERE habit_id = h.id) as times_per_week
            FROM habits h
            LEFT JOIN categories c ON h.category_id = c.id
            LEFT JOIN habit_completions hc ON h.id = hc.habit_id AND hc.completion_date = ?
            WHERE h.user_id = ? AND (
                /* Daily habits (no schedule entries) */
                NOT EXISTS (SELECT 1 FROM habit_schedule WHERE habit_id = h.id) 
                AND NOT EXISTS (SELECT 1 FROM habit_frequency WHERE habit_id = h.id)
                
                /* OR habits scheduled for specific days including today */
                OR EXISTS (SELECT 1 FROM habit_schedule WHERE habit_id = h.id AND day_of_week = ?)
                
                /* OR frequency-based habits that haven't met their weekly quota */
                OR (
                    EXISTS (SELECT 1 FROM habit_frequency hf WHERE habit_id = h.id)
                    AND (
                        SELECT COUNT(*) FROM habit_completions 
                        WHERE habit_id = h.id 
                        AND status = 'completed' 
                        AND completion_date BETWEEN ? AND ?
                    ) < (
                        SELECT times_per_week FROM habit_frequency WHERE habit_id = h.id
                    )
                )
            )
            ORDER BY h.target_time ASC";
        $stmt = $conn->prepare($habits_query);
        $stmt->bind_param('sssiiss', $week_bounds['start'], $week_bounds['end'], $date_for_habits, $user_id, $today_day_of_week, $week_bounds['start'], $week_bounds['end']);
    }
    
    $stmt->execute();
    $habits_result = $stmt->get_result();

    // Separate habits into morning and evening
    $morning_habits = [];
    $evening_habits = [];
    while ($habit = $habits_result->fetch_assoc()) {
        // Set default icon if none is set
        if (empty($habit['category_icon'])) {
            $habit['category_icon'] = 'fas fa-check-circle';
        }
        
        $time = strtotime($habit['target_time']);
        if ($time < strtotime('12:00:00')) {
            $morning_habits[] = $habit;
        } else {
            $evening_habits[] = $habit;
        }
    }
    ?>

    <div class="row g-4">
        <!-- Morning Habits Section -->
        <div class="col-lg-6">
            <div class="section-header">
                <i class="fas fa-sun" style="color: #f39c12;"></i>
                <span>Morning Habits</span>
            </div>
            <div class="d-flex flex-column gap-3">
                <?php foreach ($morning_habits as $habit): ?>
                <div class="card border-0 <?php 
                    if ($habit['today_status'] === 'completed') echo 'border-success border-2';
                    else if ($habit['today_status'] === 'procrastinated') echo 'border-warning border-2';
                    else if ($habit['today_status'] === 'skipped') echo 'border-danger border-2';
                ?>" data-habit-id="<?php echo $habit['id']; ?>">
                    <div class="card-body">
                        <!-- Main Habit Info -->
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="habit-icon" style="color: <?php echo $habit['category_color']; ?>">
                                <i class="<?php echo $habit['category_icon']; ?> fa-lg"></i>
                            </div>
                            <div class="flex-grow-1 min-width-0">
                                <h3 class="mb-1 text-truncate fw-bold" style="font-size: 1.1rem;">
                                    <?php echo htmlspecialchars($habit['name']); ?>
                                </h3>
                                
                                <?php if (isset($habit['times_per_week']) && $habit['times_per_week'] > 0): ?>
                                <div class="frequency-badge mb-1">
                                    <span class="badge bg-info">
                                        <?php echo $habit['completions_this_week'] ?? 0; ?>/<?php echo $habit['times_per_week']; ?> this week
                                    </span>
                                </div>
                                <?php endif; ?>
                                
                                <div class="text-muted text-truncate" style="font-size: 0.9rem;">
                                    <?php echo htmlspecialchars($habit['category_name']); ?>
                                    <span class="mx-2">•</span>
                                    <span class="fw-medium"><?php echo date('g:i A', strtotime($habit['target_time'])); ?></span>
                                    
                                    <?php
                                    // Show schedule information
                                    $showScheduleInfo = false;
                                    $scheduleText = '';
                                    
                                    // For fixed day schedules, query their days
                                    $days_query = "SELECT day_of_week FROM habit_schedule WHERE habit_id = ? ORDER BY day_of_week";
                                    $days_stmt = $conn->prepare($days_query);
                                    $days_stmt->bind_param('i', $habit['id']);
                                    $days_stmt->execute();
                                    $days_result = $days_stmt->get_result();
                                    
                                    if ($days_result->num_rows > 0) {
                                        $showScheduleInfo = true;
                                        $day_names = ['Su', 'M', 'Tu', 'W', 'Th', 'F', 'Sa'];
                                        $days = [];
                                        while ($day = $days_result->fetch_assoc()) {
                                            $days[] = $day_names[$day['day_of_week']];
                                        }
                                        $scheduleText = implode(', ', $days);
                                    }
                                    
                                    if ($showScheduleInfo):
                                    ?>
                                    <span class="mx-2">•</span>
                                    <span class="schedule-info"><?php echo $scheduleText; ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <?php if ($habit['today_status']): ?>
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="status-area">
                                <?php 
                                $completion_time = $habit['completion_time'] ? date('g:i A', strtotime($habit['completion_time'])) : date('g:i A');
                                switch($habit['today_status']) {
                                    case 'completed':
                                        echo '<div class="status-message text-success d-flex align-items-center gap-2">
                                            <i class="fas fa-check-circle"></i>Completed at ' . $completion_time . '</div>';
                                        break;
                                    case 'procrastinated':
                                        echo '<div class="status-message text-warning d-flex align-items-center gap-2">
                                            <i class="fas fa-clock"></i>Procrastinated at ' . $completion_time . '</div>';
                                        break;
                                    case 'skipped':
                                        echo '<div class="status-message text-danger d-flex align-items-center gap-2">
                                            <i class="fas fa-times-circle"></i>Skipped at ' . $completion_time . '</div>';
                                        break;
                                }
                                ?>
                            </div>
                            <form method="POST" action="update_habit_status.php">
                                <input type="hidden" name="habit_id" value="<?php echo $habit['id']; ?>">
                                <input type="hidden" name="action" value="reset">
                                <button type="submit" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-2" 
                                        title="Reset status">
                                    <i class="fas fa-undo"></i>
                                    <span class="d-none d-sm-inline">Reset</span>
                                </button>
                            </form>
                        </div>
                        <?php else: ?>
                        <div class="d-flex gap-2">
                            <form method="POST" action="update_habit_status.php" style="flex: 1;">
                                <input type="hidden" name="habit_id" value="<?php echo $habit['id']; ?>">
                                <input type="hidden" name="status" value="completed">
                                <button type="submit" class="btn w-100 d-flex align-items-center justify-content-center gap-2 btn-outline-success">
                                    <i class="fas fa-check"></i>
                                    <span class="d-none d-sm-inline">Done</span>
                                </button>
                            </form>
                            <form method="POST" action="update_habit_status.php" style="flex: 1;">
                                <input type="hidden" name="habit_id" value="<?php echo $habit['id']; ?>">
                                <input type="hidden" name="status" value="procrastinated">
                                <button type="button" class="btn-later btn w-100 d-flex align-items-center justify-content-center gap-2 btn-outline-warning">
                                    <i class="fas fa-clock"></i>
                                    <span class="d-none d-sm-inline">Later</span>
                                </button>
                            </form>
                            <form method="POST" action="update_habit_status.php" style="flex: 1;">
                                <input type="hidden" name="habit_id" value="<?php echo $habit['id']; ?>">
                                <input type="hidden" name="status" value="skipped">
                                <button type="button" class="btn-skip btn w-100 d-flex align-items-center justify-content-center gap-2 btn-outline-danger">
                                    <i class="fas fa-times"></i>
                                    <span class="d-none d-sm-inline">Skip</span>
                                </button>
                            </form>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Evening Habits Section -->
        <div class="col-lg-6">
            <div class="section-header">
                <i class="fas fa-moon" style="color: #2c3e50;"></i>
                <span>Evening Habits</span>
            </div>
            <div class="d-flex flex-column gap-3">
                <?php foreach ($evening_habits as $habit): ?>
                <div class="card border-0 <?php 
                    if ($habit['today_status'] === 'completed') echo 'border-success border-2';
                    else if ($habit['today_status'] === 'procrastinated') echo 'border-warning border-2';
                    else if ($habit['today_status'] === 'skipped') echo 'border-danger border-2';
                ?>" data-habit-id="<?php echo $habit['id']; ?>">
                    <div class="card-body">
                        <!-- Main Habit Info -->
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="habit-icon" style="color: <?php echo $habit['category_color']; ?>">
                                <i class="<?php echo $habit['category_icon']; ?> fa-lg"></i>
                            </div>
                            <div class="flex-grow-1 min-width-0">
                                <h3 class="mb-1 text-truncate fw-bold" style="font-size: 1.1rem;">
                                    <?php echo htmlspecialchars($habit['name']); ?>
                                </h3>
                                
                                <?php if (isset($habit['times_per_week']) && $habit['times_per_week'] > 0): ?>
                                <div class="frequency-badge mb-1">
                                    <span class="badge bg-info">
                                        <?php echo $habit['completions_this_week'] ?? 0; ?>/<?php echo $habit['times_per_week']; ?> this week
                                    </span>
                                </div>
                                <?php endif; ?>
                                
                                <div class="text-muted text-truncate" style="font-size: 0.9rem;">
                                    <?php echo htmlspecialchars($habit['category_name']); ?>
                                    <span class="mx-2">•</span>
                                    <span class="fw-medium"><?php echo date('g:i A', strtotime($habit['target_time'])); ?></span>
                                    
                                    <?php
                                    // Show schedule information
                                    $showScheduleInfo = false;
                                    $scheduleText = '';
                                    
                                    // For fixed day schedules, query their days
                                    $days_query = "SELECT day_of_week FROM habit_schedule WHERE habit_id = ? ORDER BY day_of_week";
                                    $days_stmt = $conn->prepare($days_query);
                                    $days_stmt->bind_param('i', $habit['id']);
                                    $days_stmt->execute();
                                    $days_result = $days_stmt->get_result();
                                    
                                    if ($days_result->num_rows > 0) {
                                        $showScheduleInfo = true;
                                        $day_names = ['Su', 'M', 'Tu', 'W', 'Th', 'F', 'Sa'];
                                        $days = [];
                                        while ($day = $days_result->fetch_assoc()) {
                                            $days[] = $day_names[$day['day_of_week']];
                                        }
                                        $scheduleText = implode(', ', $days);
                                    }
                                    
                                    if ($showScheduleInfo):
                                    ?>
                                    <span class="mx-2">•</span>
                                    <span class="schedule-info"><?php echo $scheduleText; ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <?php if ($habit['today_status']): ?>
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="status-area">
                                <?php 
                                $completion_time = $habit['completion_time'] ? date('g:i A', strtotime($habit['completion_time'])) : date('g:i A');
                                switch($habit['today_status']) {
                                    case 'completed':
                                        echo '<div class="status-message text-success d-flex align-items-center gap-2">
                                            <i class="fas fa-check-circle"></i>Completed at ' . $completion_time . '</div>';
                                        break;
                                    case 'procrastinated':
                                        echo '<div class="status-message text-warning d-flex align-items-center gap-2">
                                            <i class="fas fa-clock"></i>Procrastinated at ' . $completion_time . '</div>';
                                        break;
                                    case 'skipped':
                                        echo '<div class="status-message text-danger d-flex align-items-center gap-2">
                                            <i class="fas fa-times-circle"></i>Skipped at ' . $completion_time . '</div>';
                                        break;
                                }
                                ?>
                            </div>
                            <form method="POST" action="update_habit_status.php">
                                <input type="hidden" name="habit_id" value="<?php echo $habit['id']; ?>">
                                <input type="hidden" name="action" value="reset">
                                <button type="submit" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-2" 
                                        title="Reset status">
                                    <i class="fas fa-undo"></i>
                                    <span class="d-none d-sm-inline">Reset</span>
                                </button>
                            </form>
                        </div>
                        <?php else: ?>
                        <div class="d-flex gap-2">
                            <form method="POST" action="update_habit_status.php" style="flex: 1;">
                                <input type="hidden" name="habit_id" value="<?php echo $habit['id']; ?>">
                                <input type="hidden" name="status" value="completed">
                                <button type="submit" class="btn w-100 d-flex align-items-center justify-content-center gap-2 btn-outline-success">
                                    <i class="fas fa-check"></i>
                                    <span class="d-none d-sm-inline">Done</span>
                                </button>
                            </form>
                            <form method="POST" action="update_habit_status.php" style="flex: 1;">
                                <input type="hidden" name="habit_id" value="<?php echo $habit['id']; ?>">
                                <input type="hidden" name="status" value="procrastinated">
                                <button type="button" class="btn-later btn w-100 d-flex align-items-center justify-content-center gap-2 btn-outline-warning">
                                    <i class="fas fa-clock"></i>
                                    <span class="d-none d-sm-inline">Later</span>
                                </button>
                            </form>
                            <form method="POST" action="update_habit_status.php" style="flex: 1;">
                                <input type="hidden" name="habit_id" value="<?php echo $habit['id']; ?>">
                                <input type="hidden" name="status" value="skipped">
                                <button type="button" class="btn-skip btn w-100 d-flex align-items-center justify-content-center gap-2 btn-outline-danger">
                                    <i class="fas fa-times"></i>
                                    <span class="d-none d-sm-inline">Skip</span>
                                </button>
                            </form>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Reason Modal -->
<div class="modal fade" id="reasonModal" tabindex="-1" aria-labelledby="reasonModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reasonModalLabel">Select a Reason</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="reasonForm" method="POST" action="update_habit_status.php">
                <div class="modal-body">
                    <input type="hidden" name="habit_id" id="modalHabitId">
                    <input type="hidden" name="status" id="modalStatus">
                    <input type="hidden" name="scroll_position" id="modalScrollPosition">
                    <div class="mb-3">
                        <label for="reason_id" class="form-label">Why are you choosing this option?</label>
                        <select class="form-select" name="reason_id" id="reason_id" required>
                            <option value="">Choose a reason...</option>
                            <?php
                            // Get reasons from database
                            $reasons_query = "SELECT id, reason_text FROM habit_reasons WHERE is_default = 1";
                            $reasons_result = $conn->query($reasons_query);
                            while ($reason = $reasons_result->fetch_assoc()) {
                                echo '<option value="' . $reason['id'] . '">' . htmlspecialchars($reason['reason_text']) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="notes" class="form-label">Additional Notes (Optional)</label>
                        <textarea class="form-control" name="notes" id="notes" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize the modal
    const reasonModal = new bootstrap.Modal(document.getElementById('reasonModal'));
    
    // Handle Later and Skip buttons
    document.querySelectorAll('.btn-later, .btn-skip').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const form = this.closest('form');
            const habitId = form.querySelector('input[name="habit_id"]').value;
            const status = form.querySelector('input[name="status"]').value;
            
            // Set modal form values
            document.getElementById('modalHabitId').value = habitId;
            document.getElementById('modalStatus').value = status;
            document.getElementById('modalScrollPosition').value = window.scrollY;
            
            // Update modal title based on status
            const modalTitle = document.getElementById('reasonModalLabel');
            modalTitle.textContent = status === 'procrastinated' ? 'Why are you procrastinating?' : 'Why are you skipping?';
            
            // Show the modal
            reasonModal.show();
        });
    });
    
    // Handle Done and Reset buttons (maintain scroll position)
    document.querySelectorAll('form:not(#reasonForm)').forEach(form => {
        form.addEventListener('submit', function(e) {
            const scrollPosition = window.scrollY;
            const scrollInput = document.createElement('input');
            scrollInput.type = 'hidden';
            scrollInput.name = 'scroll_position';
            scrollInput.value = scrollPosition;
            this.appendChild(scrollInput);
        });
    });

    // Handle modal form submission
    const reasonForm = document.getElementById('reasonForm');
    if (reasonForm) {
        reasonForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Get the selected reason
            const reasonSelect = document.getElementById('reason_id');
            if (!reasonSelect.value) {
                alert('Please select a reason');
                return;
            }
            
            // Add scroll position to form
            const scrollInput = document.createElement('input');
            scrollInput.type = 'hidden';
            scrollInput.name = 'scroll_position';
            scrollInput.value = window.scrollY;
            this.appendChild(scrollInput);
            
            // Submit the form
            this.submit();
        });
    }
});
</script>

<style>
/* Base Styles */
:root {
    --primary-color: #cdaf56;
    --success-color: #28a745;
    --warning-color: #ffc107;
    --danger-color: #dc3545;
    --text-color: #2d3436;
    --text-muted: #636e72;
    --border-radius: 16px;
    --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    --transition-speed: 0.3s;
}

body {
    color: var(--text-color);
    background-color: #f8f9fa;
    margin: 0;
    padding: 0;
}

.container-fluid {
    padding: 0 1rem;
}

/* Card Styles */
.card {
    transition: all var(--transition-speed) ease;
    border-radius: var(--border-radius) !important;
    box-shadow: var(--card-shadow);
    background: white;
    overflow: hidden;
}

.card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
}

.card.border-success {
    border-color: var(--success-color) !important;
    background: linear-gradient(to right, rgba(40, 167, 69, 0.05), white) !important;
}

.card.border-warning {
    border-color: var(--warning-color) !important;
    background: linear-gradient(to right, rgba(255, 193, 7, 0.05), white) !important;
}

.card.border-danger {
    border-color: var(--danger-color) !important;
    background: linear-gradient(to right, rgba(220, 53, 69, 0.05), white) !important;
}

/* Habit Icon */
.habit-icon {
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, rgba(0, 0, 0, 0.03), rgba(0, 0, 0, 0.06));
    border-radius: 14px;
    transition: all var(--transition-speed);
}

.habit-icon i {
    transition: all var(--transition-speed);
}

.card:hover .habit-icon {
    transform: scale(1.1);
}

/* Button Styles */
.btn {
    border-radius: 12px;
    transition: all var(--transition-speed);
    font-weight: 500;
}

.btn:hover {
    transform: translateY(-2px);
}

.btn-outline-success {
    border-width: 2px;
}

.btn-outline-warning {
    border-width: 2px;
}

.btn-outline-danger {
    border-width: 2px;
}

/* Status Message Styles */
.status-message {
    font-weight: 500;
    padding: 8px 16px;
    border-radius: 10px;
    background-color: rgba(0, 0, 0, 0.03);
}

/* Section Headers */
.section-header {
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--text-muted);
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.section-header i {
    font-size: 1.25rem;
}

/* Responsive Styles */
@media (min-width: 992px) {
    .container-fluid {
        max-width: 1400px;
        margin: 0 auto;
        padding: 0 2rem;
    }
    
    .greeting-section {
        padding: 1rem 0;
    }
    
    .card-body {
        padding: 1.5rem;
    }
    
    .habit-icon {
        width: 56px;
        height: 56px;
    }
    
    .btn {
        padding: 0.75rem 1.5rem;
    }
    
    .row {
        margin-left: -1rem;
        margin-right: -1rem;
    }
    
    .col-lg-6 {
        padding-left: 1rem;
        padding-right: 1rem;
    }
}

@media (max-width: 576px) {
    .container-fluid {
        padding: 0 1rem;
    }
    
    .greeting-section {
        padding: 0.75rem 1rem;
    }
    
    .card-body {
        padding: 1rem;
    }
    
    .habit-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
    }
    
    .btn {
        padding: 0.5rem;
        font-size: 0.9rem;
    }
    
    .btn i {
        font-size: 1rem;
    }
    
    h1.h4 {
        font-size: 1.25rem;
    }
    
    .section-header {
        font-size: 1rem;
        margin-bottom: 1rem;
    }
}

/* Animation Keyframes */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.card {
    animation: fadeIn 0.3s ease-out;
}

/* Enhanced Greeting Styles */
.greeting-section {
    background: white;
    padding: 0.75rem 1rem;
    margin-bottom: 1.5rem;
}

.greeting-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.greeting-left {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.greeting-icon {
    width: 2rem;
    height: 2rem;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: rgba(0, 0, 0, 0.05);
}

.greeting-icon i {
    font-size: 1rem;
    color: #cdaf56;
}

.greeting-text {
    font-size: 1rem;
    color: #2d3436;
    font-weight: 500;
}

.greeting-text span {
    color: #636e72;
    font-weight: normal;
}

.greeting-actions {
    display: flex;
    gap: 0.5rem;
}

.action-btn {
    width: 2rem;
    height: 2rem;
    border: none;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: white;
    cursor: pointer;
    transition: all 0.2s ease;
    text-decoration: none;
    color: inherit;
}

.action-btn i {
    font-size: 0.9rem;
    color: #2d3436;
}

.action-btn:hover {
    background: rgba(0, 0, 0, 0.05);
    text-decoration: none;
    color: inherit;
}

.settings-btn {
    background: #cdaf56;
}

.settings-btn i {
    color: #2d3436;
}

.settings-btn:hover {
    background: #c4a64d;
}

@media (max-width: 576px) {
    .greeting-section {
        padding: 0.5rem 0.75rem;
    }

    .greeting-icon {
        width: 1.75rem;
        height: 1.75rem;
    }

    .greeting-icon i {
        font-size: 0.9rem;
    }

    .greeting-text {
        font-size: 0.9rem;
    }

    .action-btn {
        width: 1.75rem;
        height: 1.75rem;
    }

    .action-btn i {
        font-size: 0.8rem;
    }
}

/* Modal Styles */
.modal-content {
    border-radius: var(--border-radius);
    border: none;
}

.modal-header {
    border-bottom: 1px solid rgba(0,0,0,0.1);
    padding: 1rem 1.5rem;
}

.modal-body {
    padding: 1.5rem;
}

.modal-footer {
    border-top: 1px solid rgba(0,0,0,0.1);
    padding: 1rem 1.5rem;
}

.form-select {
    border-radius: 12px;
    border: 2px solid #e9ecef;
    padding: 0.75rem 1rem;
    font-size: 1rem;
    transition: all var(--transition-speed);
}

.form-select:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 0.25rem rgba(205, 175, 86, 0.25);
}

.form-control {
    border-radius: 12px;
    border: 2px solid #e9ecef;
    padding: 0.75rem 1rem;
    font-size: 1rem;
    transition: all var(--transition-speed);
}

.form-control:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 0.25rem rgba(205, 175, 86, 0.25);
}

/* Mobile Responsive Modal */
@media (max-width: 576px) {
    .modal-dialog {
        margin: 0.5rem;
    }
    
    .modal-content {
        border-radius: 16px;
    }
    
    .modal-header {
        padding: 1rem;
    }
    
    .modal-body {
        padding: 1rem;
    }
    
    .modal-footer {
        padding: 1rem;
    }
    
    .form-select,
    .form-control {
        padding: 0.5rem 0.75rem;
        font-size: 0.9rem;
    }
    
    .btn {
        padding: 0.5rem 1rem;
        font-size: 0.9rem;
    }
}
</style>

<?php require_once '../../includes/footer.php'; ?> 