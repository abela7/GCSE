<?php
require_once __DIR__ . '/../../../includes/auth_check.php';

// Include required files
require_once '../../config/db_connect.php';

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
            'notes' => $row['notes']
        ];
        
        switch ($row['status']) {
            case 'completed':
                $habit_details[$habit_id]['completions'][] = $completion_data;
                $habit_details[$habit_id]['total_completions']++;
                $daily_summaries[$date]['completions'][] = [
                    'habit' => $row['habit_name'],
                    'time' => $row['completion_time']
                ];
                break;
            case 'procrastinated':
                $habit_details[$habit_id]['procrastinations'][] = $completion_data;
                $habit_details[$habit_id]['total_procrastinations']++;
                $daily_summaries[$date]['procrastinations'][] = [
                    'habit' => $row['habit_name'],
                    'reason' => $row['reason']
                ];
                break;
            case 'skipped':
                $habit_details[$habit_id]['skips'][] = $completion_data;
                $habit_details[$habit_id]['total_skips']++;
                $daily_summaries[$date]['skips'][] = [
                    'habit' => $row['habit_name'],
                    'reason' => $row['reason']
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
                                                                    <span><?php echo htmlspecialchars($procrastination['habit']); ?></span>
                                                                    <small class="text-muted"><?php echo htmlspecialchars($procrastination['reason']); ?></small>
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
                                                                    <span><?php echo htmlspecialchars($skip['habit']); ?></span>
                                                                    <small class="text-muted"><?php echo htmlspecialchars($skip['reason']); ?></small>
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
                                                                            <span><?php echo date('M j', strtotime($procrastination['date'])); ?></span>
                                                                            <small class="text-muted"><?php echo htmlspecialchars($procrastination['reason']); ?></small>
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
                                                                            <span><?php echo date('M j', strtotime($skip['date'])); ?></span>
                                                                            <small class="text-muted"><?php echo htmlspecialchars($skip['reason']); ?></small>
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
</script>

<?php require_once '../../includes/footer.php'; ?> 