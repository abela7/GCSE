<?php
require_once '../../includes/header.php';
require_once '../../includes/db_connect.php';

// Get filter parameters with proper defaults
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-7 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$habit_id = isset($_GET['habit_id']) ? $_GET['habit_id'] : 'all';
$view_type = $_GET['view_type'] ?? 'weekly';
$category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;

// Validate dates
if (strtotime($end_date) < strtotime($start_date)) {
    $end_date = $start_date;
}

// Get all habits for filter dropdown with proper category filtering
$habits_query = "SELECT h.id, h.name, h.category_id, c.name as category_name 
                 FROM habits h 
                 LEFT JOIN habit_categories c ON h.category_id = c.id 
                 WHERE h.is_active = 1";
if ($category_id > 0) {
    $habits_query .= " AND h.category_id = " . (int)$category_id;
}
$habits_query .= " ORDER BY c.name, h.name";
$habits_result = $conn->query($habits_query);

// Get categories for grouping
$categories_query = "SELECT id, name, color FROM habit_categories ORDER BY name";
$categories_result = $conn->query($categories_query);
$categories = [];
while ($cat = $categories_result->fetch_assoc()) {
    $categories[$cat['id']] = $cat;
}

// Fetch detailed habit completion data with proper filtering
$completions_query = "
    SELECT 
        h.id as habit_id,
        h.name as habit_name,
        h.category_id,
        h.target_time,
        'standard' as habit_type,
        hc.completion_date,
        hc.completion_time,
        hc.status,
        hc.reason,
        hc.points_earned,
        hc.notes,
        hpr.completion_points,
        hpr.procrastinated_points,
        hpr.skip_points
    FROM habits h
    LEFT JOIN habit_completions hc ON h.id = hc.habit_id 
        AND hc.completion_date BETWEEN ? AND ?
    LEFT JOIN habit_point_rules hpr ON h.point_rule_id = hpr.id
    WHERE h.is_active = 1";

$params = [$start_date, $end_date];
$types = "ss";

if ($habit_id !== 'all') {
    $completions_query .= " AND h.id = ?";
    $params[] = (int)$habit_id;
    $types .= "i";
}

if ($category_id > 0) {
    $completions_query .= " AND h.category_id = ?";
    $params[] = $category_id;
    $types .= "i";
}

$completions_query .= " ORDER BY h.category_id, h.name, hc.completion_date DESC, hc.completion_time DESC";

$stmt = $conn->prepare($completions_query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$completions_result = $stmt->get_result();

// Process data for detailed reports
$habit_details = [];
$daily_summaries = [];

while ($row = $completions_result->fetch_assoc()) {
    $habit_id = $row['habit_id'];
    $date = $row['completion_date'];
    
    // Initialize habit details if not exists
    if (!isset($habit_details[$habit_id])) {
        $habit_details[$habit_id] = [
            'name' => $row['habit_name'],
            'category_id' => $row['category_id'],
            'target_time' => $row['target_time'],
            'habit_type' => $row['habit_type'],
            'completions' => [],
            'procrastinations' => [],
            'skips' => [],
            'total_completions' => 0,
            'total_procrastinations' => 0,
            'total_skips' => 0
        ];
    }
    
    // Initialize daily summary if not exists
    if ($date && !isset($daily_summaries[$date])) {
        $daily_summaries[$date] = [
            'completions' => [],
            'procrastinations' => [],
            'skips' => []
        ];
    }
    
    // Add completion details
    if ($date) {
        $completion_data = [
            'date' => $date,
            'time' => $row['completion_time'],
            'reason' => $row['reason'],
            'notes' => $row['notes'],
            'habit_id' => $row['habit_id'],
            'habit_type' => $row['habit_type']
        ];
        
        switch ($row['status']) {
            case 'completed':
                $habit_details[$habit_id]['completions'][] = $completion_data;
                $habit_details[$habit_id]['total_completions']++;
                $daily_summaries[$date]['completions'][] = [
                    'habit' => $row['habit_name'],
                    'habit_id' => $row['habit_id'],
                    'time' => $row['completion_time'],
                    'habit_type' => $row['habit_type']
                ];
                break;
            case 'procrastinated':
                $habit_details[$habit_id]['procrastinations'][] = $completion_data;
                $habit_details[$habit_id]['total_procrastinations']++;
                $daily_summaries[$date]['procrastinations'][] = [
                    'habit' => $row['habit_name'],
                    'habit_id' => $row['habit_id'],
                    'reason' => $row['reason'],
                    'notes' => $row['notes'],
                    'habit_type' => $row['habit_type']
                ];
                break;
            case 'skipped':
                $habit_details[$habit_id]['skips'][] = $completion_data;
                $habit_details[$habit_id]['total_skips']++;
                $daily_summaries[$date]['skips'][] = [
                    'habit' => $row['habit_name'],
                    'habit_id' => $row['habit_id'],
                    'reason' => $row['reason'],
                    'notes' => $row['notes'],
                    'habit_type' => $row['habit_type']
                ];
                break;
        }
    }
}

// Sort daily summaries by date
krsort($daily_summaries);

// Sort habit details by category and name
uasort($habit_details, function($a, $b) use ($categories) {
    if ($a['category_id'] != $b['category_id']) {
        return strcmp($categories[$a['category_id']]['name'], $categories[$b['category_id']]['name']);
    }
    return strcmp($a['name'], $b['name']);
});
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Habit Reports</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container py-4">
        <!-- Navigation -->
        <div class="nav-top">
            <div class="nav-buttons">
                <a href="../habits/index.php" class="btn btn-custom-outline">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
                <a href="habit_analysis.php" class="btn btn-custom">
                    <i class="fas fa-chart-line"></i> Analysis
                </a>
                <a href="visual_report.php" class="btn btn-custom">
                    <i class="fas fa-chart-pie"></i> Visual Report
                </a>
                <a href="habit_calendar.php" class="btn btn-custom-outline">
                    <i class="fas fa-calendar"></i> Calendar
                </a>
            </div>
            <h4 class="mb-0">Habit Reports</h4>
        </div>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3" id="filterForm">
                    <div class="col-md-2">
                        <label class="form-label">View Type</label>
                        <select name="view_type" class="form-select" onchange="this.form.submit()">
                            <option value="daily" <?php echo $view_type == 'daily' ? 'selected' : ''; ?>>Daily</option>
                            <option value="weekly" <?php echo $view_type == 'weekly' ? 'selected' : ''; ?>>Weekly</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Start Date</label>
                        <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>" onchange="validateDates()">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">End Date</label>
                        <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>" onchange="validateDates()">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Category</label>
                        <select name="category_id" class="form-select" onchange="updateHabits()">
                            <option value="0">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo $category_id == $cat['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Habit</label>
                        <select name="habit_id" class="form-select" id="habitSelect">
                            <option value="all" <?php echo $habit_id === 'all' ? 'selected' : ''; ?>>All Habits</option>
                            <?php 
                            mysqli_data_seek($habits_result, 0);
                            while ($habit = $habits_result->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $habit['id']; ?>" 
                                        data-category="<?php echo $habit['category_id']; ?>"
                                        <?php echo $habit_id === $habit['id'] ? 'selected' : ''; ?>
                                        <?php echo $category_id > 0 && $category_id != $habit['category_id'] ? 'style="display:none;"' : ''; ?>>
                                    <?php echo htmlspecialchars($habit['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary w-100">Update</button>
                    </div>
                </form>
            </div>
        </div>

        <?php if (empty($habit_details) && empty($daily_summaries)): ?>
            <div class="alert alert-info">
                No data found for the selected filters. Please try different date range or filters.
            </div>
        <?php else: ?>
            <?php if ($view_type == 'daily'): ?>
                <!-- Daily Detailed Report -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Daily Detailed Report</h5>
                        <div class="accordion" id="dailyAccordion">
                            <?php foreach ($daily_summaries as $date => $summary): ?>
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button" 
                                                type="button" 
                                                data-bs-toggle="collapse" 
                                                data-bs-target="#collapse<?php echo str_replace('-', '', $date); ?>">
                                            <?php echo date('F j, Y', strtotime($date)); ?>
                                        </button>
                                    </h2>
                                    <div id="collapse<?php echo str_replace('-', '', $date); ?>" 
                                         class="accordion-collapse collapse show" 
                                         data-bs-parent="#dailyAccordion">
                                        <div class="accordion-body p-2">
                                            <!-- Completions -->
                                            <?php if (!empty($summary['completions'])): ?>
                                                <div class="mb-2">
                                                    <h6 class="text-success mb-2">
                                                        <i class="fas fa-check-circle"></i> Completed Habits
                                                    </h6>
                                                    <ul class="list-group list-group-flush">
                                                        <?php foreach ($summary['completions'] as $completion): ?>
                                                            <li class="list-group-item py-2">
                                                                <div class="d-flex justify-content-between align-items-center">
                                                                    <span><?php echo htmlspecialchars($completion['habit']); ?></span>
                                                                    <small class="text-muted"><?php echo date('g:i A', strtotime($completion['time'])); ?></small>
                                                                </div>
                                                            </li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                </div>
                                            <?php endif; ?>

                                            <!-- Procrastinations -->
                                            <?php if (!empty($summary['procrastinations'])): ?>
                                                <div class="mb-2">
                                                    <h6 class="text-warning mb-2">
                                                        <i class="fas fa-clock"></i> Procrastinated Habits
                                                    </h6>
                                                    <ul class="list-group list-group-flush">
                                                        <?php foreach ($summary['procrastinations'] as $procrastination): ?>
                                                            <li class="list-group-item py-2">
                                                                <div class="d-flex flex-column">
                                                                    <div class="d-flex justify-content-between align-items-start">
                                                                        <span>
                                                                            <?php echo htmlspecialchars($procrastination['habit']); ?>
                                                                            <?php 
                                                                            // Display habit type badge
                                                                            $badge_class = 'standard';
                                                                            $badge_text = 'Daily';
                                                                            ?>
                                                                            <span class="habit-badge <?php echo $badge_class; ?>"><?php echo $badge_text; ?></span>
                                                                        </span>
                                                                    </div>
                                                                    <small class="text-muted"><?php echo htmlspecialchars($procrastination['reason']); ?></small>
                                                                    <button type="button" class="btn btn-link btn-sm text-muted p-0 mt-1 text-start" 
                                                                            data-bs-toggle="modal" 
                                                                            data-bs-target="#procrastinationDetailModal" 
                                                                            data-date="<?php echo $date; ?>"
                                                                            data-habit="<?php echo htmlspecialchars($procrastination['habit']); ?>"
                                                                            data-reason="<?php echo htmlspecialchars($procrastination['reason']); ?>"
                                                                            data-notes="<?php echo htmlspecialchars($procrastination['notes'] ?? ''); ?>"
                                                                            data-habit-id="<?php echo $procrastination['habit_id']; ?>"
                                                                            data-habit-type="<?php echo htmlspecialchars($procrastination['habit_type']); ?>">
                                                                        <i class="fas fa-info-circle"></i> View details
                                                                    </button>
                                                                </div>
                                                            </li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                </div>
                                            <?php endif; ?>

                                            <!-- Skips -->
                                            <?php if (!empty($summary['skips'])): ?>
                                                <div class="mb-2">
                                                    <h6 class="text-danger mb-2">
                                                        <i class="fas fa-times-circle"></i> Skipped Habits
                                                    </h6>
                                                    <ul class="list-group list-group-flush">
                                                        <?php foreach ($summary['skips'] as $skip): ?>
                                                            <li class="list-group-item py-2">
                                                                <div class="d-flex flex-column">
                                                                    <div class="d-flex justify-content-between align-items-start">
                                                                        <span>
                                                                            <?php echo htmlspecialchars($skip['habit']); ?>
                                                                            <?php 
                                                                            // Display habit type badge
                                                                            $badge_class = 'standard';
                                                                            $badge_text = 'Daily';
                                                                            ?>
                                                                            <span class="habit-badge <?php echo $badge_class; ?>"><?php echo $badge_text; ?></span>
                                                                        </span>
                                                                    </div>
                                                                    <small class="text-muted"><?php echo htmlspecialchars($skip['reason']); ?></small>
                                                                    <button type="button" class="btn btn-link btn-sm text-muted p-0 mt-1 text-start" 
                                                                            data-bs-toggle="modal" 
                                                                            data-bs-target="#skipDetailModal" 
                                                                            data-date="<?php echo $date; ?>"
                                                                            data-habit="<?php echo htmlspecialchars($skip['habit']); ?>"
                                                                            data-reason="<?php echo htmlspecialchars($skip['reason']); ?>"
                                                                            data-notes="<?php echo htmlspecialchars($skip['notes'] ?? ''); ?>"
                                                                            data-habit-id="<?php echo $skip['habit_id']; ?>"
                                                                            data-habit-type="<?php echo htmlspecialchars($skip['habit_type']); ?>">
                                                                        <i class="fas fa-info-circle"></i> View details
                                                                    </button>
                                                                </div>
                                                            </li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Weekly Detailed Report -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Weekly Detailed Report</h5>
                        <div class="accordion" id="weeklyAccordion">
                            <?php foreach ($habit_details as $id => $habit): ?>
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button" 
                                                type="button" 
                                                data-bs-toggle="collapse" 
                                                data-bs-target="#collapse<?php echo $id; ?>">
                                            <div class="d-flex align-items-center">
                                                <div class="color-dot" style="background-color: <?php echo isset($categories[$habit['category_id']]) ? $categories[$habit['category_id']]['color'] : '#ccc'; ?>"></div>
                                                <?php echo htmlspecialchars($habit['name']); ?>
                                                <?php 
                                                // Display habit type badge
                                                $badge_class = 'standard';
                                                $badge_text = 'Daily';
                                                ?>
                                                <span class="habit-badge <?php echo $badge_class; ?>"><?php echo $badge_text; ?></span>
                                            </div>
                                        </button>
                                    </h2>
                                    <div id="collapse<?php echo $id; ?>" 
                                         class="accordion-collapse collapse show" 
                                         data-bs-parent="#weeklyAccordion">
                                        <div class="accordion-body p-2">
                                            <div class="row g-2">
                                                <!-- Completions -->
                                                <div class="col-12 col-md-4">
                                                    <div class="card h-100">
                                                        <div class="card-body p-2">
                                                            <h6 class="text-success mb-2">
                                                                <i class="fas fa-check-circle"></i> Completions (<?php echo $habit['total_completions']; ?>)
                                                            </h6>
                                                            <ul class="list-group list-group-flush">
                                                                <?php foreach ($habit['completions'] as $completion): ?>
                                                                    <li class="list-group-item py-2">
                                                                        <div class="d-flex justify-content-between align-items-center">
                                                                            <span><?php echo date('M j', strtotime($completion['date'])); ?></span>
                                                                            <small class="text-muted"><?php echo date('g:i A', strtotime($completion['time'])); ?></small>
                                                                        </div>
                                                                    </li>
                                                                <?php endforeach; ?>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Procrastinations -->
                                                <div class="col-12 col-md-4">
                                                    <div class="card h-100">
                                                        <div class="card-body p-2">
                                                            <h6 class="text-warning mb-2">
                                                                <i class="fas fa-clock"></i> Procrastinations (<?php echo $habit['total_procrastinations']; ?>)
                                                            </h6>
                                                            <ul class="list-group list-group-flush">
                                                                <?php foreach ($habit['procrastinations'] as $procrastination): ?>
                                                                    <li class="list-group-item py-2">
                                                                        <div class="d-flex flex-column">
                                                                            <div class="d-flex justify-content-between align-items-start">
                                                                                <span>
                                                                                    <?php echo date('M j', strtotime($procrastination['date'])); ?>
                                                                                    <?php 
                                                                                    // Display habit type badge
                                                                                    $badge_class = 'standard';
                                                                                    $badge_text = 'Daily';
                                                                                    ?>
                                                                                    <span class="habit-badge <?php echo $badge_class; ?>"><?php echo $badge_text; ?></span>
                                                                                </span>
                                                                            </div>
                                                                            <small class="text-muted"><?php echo htmlspecialchars($procrastination['reason']); ?></small>
                                                                            <button type="button" class="btn btn-link btn-sm text-muted p-0 mt-1 text-start" 
                                                                                    data-bs-toggle="modal" 
                                                                                    data-bs-target="#procrastinationDetailModal" 
                                                                                    data-date="<?php echo $procrastination['date']; ?>"
                                                                                    data-habit="<?php echo htmlspecialchars($habit['name']); ?>"
                                                                                    data-reason="<?php echo htmlspecialchars($procrastination['reason']); ?>"
                                                                                    data-notes="<?php echo htmlspecialchars($procrastination['notes'] ?? ''); ?>"
                                                                                    data-habit-id="<?php echo $procrastination['habit_id']; ?>"
                                                                                    data-habit-type="<?php echo htmlspecialchars($procrastination['habit_type']); ?>">
                                                                                <i class="fas fa-info-circle"></i> View details
                                                                            </button>
                                                                        </div>
                                                                    </li>
                                                                <?php endforeach; ?>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Skips -->
                                                <div class="col-12 col-md-4">
                                                    <div class="card h-100">
                                                        <div class="card-body p-2">
                                                            <h6 class="text-danger mb-2">
                                                                <i class="fas fa-times-circle"></i> Skips (<?php echo $habit['total_skips']; ?>)
                                                            </h6>
                                                            <ul class="list-group list-group-flush">
                                                                <?php foreach ($habit['skips'] as $skip): ?>
                                                                    <li class="list-group-item py-2">
                                                                        <div class="d-flex flex-column">
                                                                            <div class="d-flex justify-content-between align-items-start">
                                                                                <span>
                                                                                    <?php echo date('M j', strtotime($skip['date'])); ?>
                                                                                    <?php 
                                                                                    // Display habit type badge
                                                                                    $badge_class = 'standard';
                                                                                    $badge_text = 'Daily';
                                                                                    ?>
                                                                                    <span class="habit-badge <?php echo $badge_class; ?>"><?php echo $badge_text; ?></span>
                                                                                </span>
                                                                            </div>
                                                                            <small class="text-muted"><?php echo htmlspecialchars($skip['reason']); ?></small>
                                                                            <button type="button" class="btn btn-link btn-sm text-muted p-0 mt-1 text-start" 
                                                                                    data-bs-toggle="modal" 
                                                                                    data-bs-target="#skipDetailModal" 
                                                                                    data-date="<?php echo $skip['date']; ?>"
                                                                                    data-habit="<?php echo htmlspecialchars($habit['name']); ?>"
                                                                                    data-reason="<?php echo htmlspecialchars($skip['reason']); ?>"
                                                                                    data-notes="<?php echo htmlspecialchars($skip['notes'] ?? ''); ?>"
                                                                                    data-habit-id="<?php echo $skip['habit_id']; ?>"
                                                                                    data-habit-type="<?php echo htmlspecialchars($skip['habit_type']); ?>">
                                                                                <i class="fas fa-info-circle"></i> View details
                                                                            </button>
                                                                        </div>
                                                                    </li>
                                                                <?php endforeach; ?>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Skip Detail Modal -->
    <div class="modal fade" id="skipDetailModal" tabindex="-1" aria-labelledby="skipDetailModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="skipDetailModalLabel">Skipped Habit Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <h6>Habit</h6>
                        <p id="modalHabitName" class="fw-bold"></p>
                    </div>
                    <div class="row mb-3">
                        <div class="col-6">
                            <h6>Date</h6>
                            <p id="modalDate"></p>
                        </div>
                        <div class="col-6">
                            <h6>Habit Type</h6>
                            <p id="modalHabitType"></p>
                        </div>
                    </div>
                    <div class="mb-3">
                        <h6>Reason for Skipping</h6>
                        <p id="modalReason" class="text-danger"></p>
                    </div>
                    <div class="mb-3">
                        <h6>Additional Notes</h6>
                        <p id="modalNotes" class="fst-italic"></p>
                    </div>
                    
                    <!-- Completion History Chart -->
                    <div class="mb-3">
                        <h6>Completion History (Last 14 Days)</h6>
                        <div class="history-chart-container">
                            <canvas id="completionHistoryChart" height="100"></canvas>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Procrastination Detail Modal -->
    <div class="modal fade" id="procrastinationDetailModal" tabindex="-1" aria-labelledby="procrastinationDetailModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="procrastinationDetailModalLabel">Procrastinated Habit Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <h6>Habit</h6>
                        <p id="prModalHabitName" class="fw-bold"></p>
                    </div>
                    <div class="row mb-3">
                        <div class="col-6">
                            <h6>Date</h6>
                            <p id="prModalDate"></p>
                        </div>
                        <div class="col-6">
                            <h6>Habit Type</h6>
                            <p id="prModalHabitType"></p>
                        </div>
                    </div>
                    <div class="mb-3">
                        <h6>Reason for Procrastinating</h6>
                        <p id="prModalReason" class="text-warning"></p>
                    </div>
                    <div class="mb-3">
                        <h6>Additional Notes</h6>
                        <p id="prModalNotes" class="fst-italic"></p>
                    </div>
                    
                    <!-- Completion History Chart -->
                    <div class="mb-3">
                        <h6>Completion History (Last 14 Days)</h6>
                        <div class="history-chart-container">
                            <canvas id="prCompletionHistoryChart" height="100"></canvas>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

<style>
.color-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    margin-right: 8px;
}
.list-group-item {
    padding: 0.5rem;
    border-left: none;
    border-right: none;
}
.card-header {
    padding: 0.5rem;
}
.accordion-button {
    padding: 0.75rem;
    font-size: 0.95rem;
}
.accordion-button:not(.collapsed) {
    background-color: #f8f9fa;
}
.accordion-button:focus {
    box-shadow: none;
}
.accordion-item {
    border: 1px solid rgba(0,0,0,.125);
    margin-bottom: 0.5rem;
}
.accordion-body {
    padding: 0.75rem;
}
.card {
    border: none;
    box-shadow: none;
}
.card-body {
    padding: 0.5rem;
}
@media (max-width: 768px) {
    .accordion-button {
        padding: 0.5rem;
        font-size: 0.9rem;
    }
    .list-group-item {
        padding: 0.4rem;
    }
    .card-body {
        padding: 0.4rem;
    }
    h6 {
        font-size: 0.9rem;
        margin-bottom: 0.5rem;
    }
    .text-muted {
        font-size: 0.8rem;
    }
}
.nav-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
    gap: 0.5rem;
}
.nav-buttons {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}
.btn-custom {
    background-color: #cdaf56;
    border-color: #cdaf56;
    color: white;
}
.btn-custom:hover {
    background-color: #b89c4a;
    border-color: #b89c4a;
    color: white;
}
.btn-custom-outline {
    border-color: #cdaf56;
    color: #cdaf56;
    background-color: transparent;
}
.btn-custom-outline:hover {
    background-color: #cdaf56;
    color: white;
}
@media (max-width: 768px) {
    .container {
        padding-left: 0.5rem;
        padding-right: 0.5rem;
    }
    .btn {
        padding: 0.4rem 0.8rem;
        font-size: 0.85rem;
    }
}
@media (max-width: 576px) {
    .nav-top {
        flex-direction: column;
        align-items: stretch;
    }
    .nav-buttons {
        justify-content: space-between;
    }
    .nav-buttons .btn {
        flex: 1;
    }
}
.modal-title {
    font-size: 1.1rem;
}
.modal h6 {
    font-size: 0.85rem;
    color: #666;
    margin-bottom: 0.25rem;
}
.modal p {
    margin-bottom: 0.5rem;
}
/* Hover effect for list items */
.list-group-item:hover {
    background-color: rgba(0,0,0,0.02);
}
.btn-link {
    text-decoration: none;
}
/* Habit type badges */
.habit-badge {
    font-size: 0.7rem;
    padding: 0.2rem 0.4rem;
    border-radius: 4px;
    background-color: #f0f0f0;
    color: #666;
    margin-left: 0.5rem;
}
.habit-badge.mini {
    font-size: 0.6rem;
    padding: 0.1rem 0.25rem;
    margin-left: 0.25rem;
    display: inline-block;
    vertical-align: text-top;
}
.habit-badge.frequency {
    background-color: #e6f7ff;
    color: #0099cc;
}
.habit-badge.days {
    background-color: #fff0e6;
    color: #ff8533;
}
.habit-badge.standard {
    background-color: #e6f2ff;
    color: #3377ff;
}
.history-chart-container {
    height: 120px;
    margin-bottom: 1rem;
    background-color: #f9f9f9;
    border-radius: 4px;
    padding: 8px;
}
</style>

<script>
function validateDates() {
    const startDate = new Date(document.querySelector('input[name="start_date"]').value);
    const endDate = new Date(document.querySelector('input[name="end_date"]').value);
    
    if (endDate < startDate) {
        alert('End date cannot be before start date');
        document.querySelector('input[name="end_date"]').value = document.querySelector('input[name="start_date"]').value;
    }
}

function updateHabits() {
    const categoryId = document.querySelector('select[name="category_id"]').value;
    const habitSelect = document.getElementById('habitSelect');
    const currentSelection = habitSelect.value;
    
    // Update habit options based on category
    Array.from(habitSelect.options).forEach(option => {
        if (option.value === 'all') {
            option.style.display = '';
            return;
        }
        
        const habitCategory = option.getAttribute('data-category');
        if (categoryId === '0' || habitCategory === categoryId) {
            option.style.display = '';
        } else {
            option.style.display = 'none';
        }
    });

    // If current selection is not visible, reset to "All Habits"
    const currentOption = habitSelect.options[habitSelect.selectedIndex];
    if (currentOption.style.display === 'none') {
        habitSelect.value = 'all';
    }
}

// Add a function to fetch real completion history data via AJAX
function fetchHabitHistory(habitId, date, callback) {
    // Create a form data object to send the request
    const formData = new FormData();
    formData.append('habit_id', habitId);
    formData.append('date', date);
    
    // Create and send the AJAX request
    fetch('fetch_habit_history.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        callback(data);
    })
    .catch(error => {
        console.error('Error fetching habit history:', error);
        // Fall back to generated data if the AJAX request fails
        const generatedData = generateCompletionHistoryData(date, 'standard');
        callback(generatedData);
    });
}

// Initialize the habit select on page load
document.addEventListener('DOMContentLoaded', function() {
    const categoryId = document.querySelector('select[name="category_id"]').value;
    const habitSelect = document.getElementById('habitSelect');
    
    // Update visibility of options based on initial category
    Array.from(habitSelect.options).forEach(option => {
        if (option.value === 'all') {
            option.style.display = '';
            return;
        }
        
        const habitCategory = option.getAttribute('data-category');
        if (categoryId === '0' || habitCategory === categoryId) {
            option.style.display = '';
        } else {
            option.style.display = 'none';
        }
    });

    // Ensure "All Habits" is selected if habit_id is 'all'
    if (habitSelect.value === 'all') {
        habitSelect.selectedIndex = 0;
    }
    
    // Initialize skip detail modal
    const skipDetailModal = document.getElementById('skipDetailModal');
    if (skipDetailModal) {
        skipDetailModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const date = button.getAttribute('data-date');
            const habit = button.getAttribute('data-habit');
            const reason = button.getAttribute('data-reason');
            const notes = button.getAttribute('data-notes');
            const habitType = button.getAttribute('data-habit-type');
            const habitId = button.getAttribute('data-habit-id');
            
            // Format date for display
            const formattedDate = new Date(date).toLocaleDateString('en-US', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
            
            // Update modal content
            document.getElementById('modalHabitName').textContent = habit;
            document.getElementById('modalDate').textContent = formattedDate;
            document.getElementById('modalReason').textContent = reason || 'No reason provided';
            document.getElementById('modalNotes').textContent = notes || 'No additional notes';
            document.getElementById('modalHabitType').textContent = 'Standard (Daily)';
            
            // Try to fetch real history data, fall back to generated data if needed
            if (habitId) {
                fetchHabitHistory(habitId, date, function(historyData) {
                    renderCompletionHistoryChart(historyData);
                });
            } else {
                // Fall back to generated data if no habit ID is available
                const generatedData = generateCompletionHistoryData(date, habitType);
                renderCompletionHistoryChart(generatedData);
            }
        });
    }
    
    // Initialize procrastination modal
    const procrastinationDetailModal = document.getElementById('procrastinationDetailModal');
    if (procrastinationDetailModal) {
        procrastinationDetailModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const date = button.getAttribute('data-date');
            const habit = button.getAttribute('data-habit');
            const reason = button.getAttribute('data-reason');
            const notes = button.getAttribute('data-notes');
            const habitId = button.getAttribute('data-habit-id');
            
            // Format date for display
            const formattedDate = new Date(date).toLocaleDateString('en-US', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
            
            // Update modal content
            document.getElementById('prModalHabitName').textContent = habit;
            document.getElementById('prModalDate').textContent = formattedDate;
            document.getElementById('prModalReason').textContent = reason || 'No reason provided';
            document.getElementById('prModalNotes').textContent = notes || 'No additional notes';
            document.getElementById('prModalHabitType').textContent = 'Standard (Daily)';
            
            // Try to fetch real history data, fall back to generated data if needed
            if (habitId) {
                fetchHabitHistory(habitId, date, function(historyData) {
                    renderProcrastinationHistoryChart(historyData);
                });
            } else {
                // Fall back to generated data if no habit ID is available
                const generatedData = generateCompletionHistoryData(date, 'standard');
                renderProcrastinationHistoryChart(generatedData);
            }
        });
    }
});

// Handle form submission
document.getElementById('filterForm').addEventListener('submit', function(e) {
    const habitSelect = document.getElementById('habitSelect');
    const selectedOption = habitSelect.options[habitSelect.selectedIndex];
    
    // If the selected option is hidden, reset to "All Habits"
    if (selectedOption.style.display === 'none') {
        habitSelect.value = 'all';
        habitSelect.selectedIndex = 0;
    }
});

// Add click handlers for list items to make them more interactive
document.querySelectorAll('.list-group-item').forEach(item => {
    item.addEventListener('mouseenter', function() {
        this.style.cursor = 'pointer';
    });
});

// Generate some sample completion history data
// In production, this would be replaced with an AJAX call to get real data
function generateCompletionHistoryData(skipDate, habitType) {
    const data = [];
    const skipDateObj = new Date(skipDate);
    
    // Generate data for 14 days before the skip date
    for (let i = 13; i >= 0; i--) {
        const currentDate = new Date(skipDateObj);
        currentDate.setDate(currentDate.getDate() - i);
        
        // Format date as YYYY-MM-DD
        const formattedDate = currentDate.toISOString().split('T')[0];
        
        // Generate a random status for the history (for demo purposes)
        // In production, this would use real data from the server
        let status;
        if (formattedDate === skipDate) {
            status = 'skipped'; // The date we're viewing details for
        } else {
            // For demo: generate a weighted random status
            const rand = Math.random();
            if (rand < 0.6) {
                status = 'completed';
            } else if (rand < 0.8) {
                status = 'procrastinated';
            } else {
                status = 'skipped';
            }
        }
        
        data.push({
            date: formattedDate,
            displayDate: currentDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }),
            status: status
        });
    }
    
    return data;
}

// Render the completion history chart
function renderCompletionHistoryChart(historyData) {
    const ctx = document.getElementById('completionHistoryChart').getContext('2d');
    
    // Clear any existing chart
    if (window.completionHistoryChart) {
        window.completionHistoryChart.destroy();
    }
    
    // Prepare data for the chart
    const labels = historyData.map(d => d.displayDate);
    const statusColors = historyData.map(d => {
        switch (d.status) {
            case 'completed': return 'rgba(40, 167, 69, 0.8)';
            case 'procrastinated': return 'rgba(255, 193, 7, 0.8)';
            case 'skipped': return 'rgba(220, 53, 69, 0.8)';
            case 'not_tracked': return 'rgba(200, 200, 200, 0.3)';
            default: return 'rgba(200, 200, 200, 0.3)';
        }
    });
    
    // Create the chart
    window.completionHistoryChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Completion Status',
                data: historyData.map(d => 1), // Each day has equal height
                backgroundColor: statusColors,
                borderColor: statusColors.map(c => c.replace('0.8', '1').replace('0.3', '0.5')),
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    display: false,
                    beginAtZero: true,
                    max: 1
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const index = context.dataIndex;
                            const status = historyData[index].status;
                            if (status === 'not_tracked') {
                                return 'Not Tracked';
                            }
                            return status.charAt(0).toUpperCase() + status.slice(1);
                        }
                    }
                }
            }
        }
    });
}

// Render the procrastination history chart
function renderProcrastinationHistoryChart(historyData) {
    const ctx = document.getElementById('prCompletionHistoryChart').getContext('2d');
    
    // Clear any existing chart
    if (window.procrastinationHistoryChart) {
        window.procrastinationHistoryChart.destroy();
    }
    
    // Prepare data for the chart
    const labels = historyData.map(d => d.displayDate);
    const statusColors = historyData.map(d => {
        switch (d.status) {
            case 'completed': return 'rgba(40, 167, 69, 0.8)';
            case 'procrastinated': return 'rgba(255, 193, 7, 0.8)';
            case 'skipped': return 'rgba(220, 53, 69, 0.8)';
            case 'not_tracked': return 'rgba(200, 200, 200, 0.3)';
            default: return 'rgba(200, 200, 200, 0.3)';
        }
    });
    
    // Create the chart
    window.procrastinationHistoryChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Completion Status',
                data: historyData.map(d => 1), // Each day has equal height
                backgroundColor: statusColors,
                borderColor: statusColors.map(c => c.replace('0.8', '1').replace('0.3', '0.5')),
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    display: false,
                    beginAtZero: true,
                    max: 1
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const index = context.dataIndex;
                            const status = historyData[index].status;
                            if (status === 'not_tracked') {
                                return 'Not Tracked';
                            }
                            return status.charAt(0).toUpperCase() + status.slice(1);
                        }
                    }
                }
            }
        }
    });
}
</script>

<?php require_once '../../includes/footer.php'; ?> 