<?php
require_once '../../includes/header.php';
require_once '../../includes/db_connect.php';

// Get filter parameters with proper defaults
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-7 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$habit_id = $_GET['habit_id'] ?? 'all';
$category_id = $_GET['category_id'] ?? 'all';

// Validate dates
if (strtotime($end_date) < strtotime($start_date)) {
    $end_date = $start_date;
}

// Get categories for color coding and filtering
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

// Initialize habit points array with all active habits
$habit_points = [];
foreach ($habits as $id => $habit) {
    $habit_points[$id] = [
        'name' => $habit['name'],
        'category_id' => $habit['category_id'],
        'total' => 0,
        'completion' => 0,
        'procrastination' => 0,
        'skip' => 0
    ];
}

// Fetch points data with proper filtering
$points_query = "
    SELECT 
        h.id as habit_id,
        h.name as habit_name,
        h.category_id,
        hc.completion_date,
        hc.status,
        hc.points_earned,
        hpr.completion_points,
        hpr.procrastinated_points,
        hpr.skip_points
    FROM habits h
    LEFT JOIN habit_completions hc ON h.id = hc.habit_id 
        AND hc.completion_date BETWEEN ? AND ?
    LEFT JOIN habit_point_rules hpr ON h.point_rule_id = hpr.id
    WHERE h.is_active = 1 ";

$params = [$start_date, $end_date];
$types = "ss";

if ($habit_id !== 'all') {
    $points_query .= " AND h.id = ?";
    $params[] = $habit_id;
    $types .= "i";
}

if ($category_id !== 'all') {
    $points_query .= " AND h.category_id = ?";
    $params[] = $category_id;
    $types .= "i";
}

$points_query .= " ORDER BY h.category_id, h.name, hc.completion_date";

$stmt = $conn->prepare($points_query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$points_result = $stmt->get_result();

// Process data for visualizations
$daily_points = [];
$category_points = [];
$total_points = 0;

// Initialize category points
foreach ($categories as $cat_id => $category) {
    $category_points[$cat_id] = [
        'name' => $category['name'],
        'color' => $category['color'],
        'total' => 0
    ];
}

while ($row = $points_result->fetch_assoc()) {
    $date = $row['completion_date'];
    $habit_id = $row['habit_id'];
    $category_id = $row['category_id'];
    $points = $row['points_earned'] ?? 0;
    
    // Daily points
    if ($date) {
        if (!isset($daily_points[$date])) {
            $daily_points[$date] = 0;
        }
        $daily_points[$date] += $points;
    }
    
    // Update habit points
    if (isset($habit_points[$habit_id])) {
        $habit_points[$habit_id]['total'] += $points;
        
        // Status counts
        if ($row['status']) {
            // Convert status names to match our array keys
            $status_map = [
                'completed' => 'completion',
                'procrastinated' => 'procrastination',
                'skipped' => 'skip'
            ];
            $status = $status_map[$row['status']] ?? $row['status'];
            $habit_points[$habit_id][$status]++;
        }
    }
    
    // Category points
    if (isset($category_points[$category_id])) {
        $category_points[$category_id]['total'] += $points;
    }
    
    $total_points += $points;
}

// Sort data for display
ksort($daily_points);
uasort($habit_points, function($a, $b) {
    return $b['total'] - $a['total'];
});
uasort($category_points, function($a, $b) {
    return $b['total'] - $a['total'];
});
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Habit Visual Report</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container py-4">
        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3" id="filterForm">
                    <div class="col-md-3">
                        <label class="form-label">Start Date</label>
                        <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>" onchange="validateDates()">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">End Date</label>
                        <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>" onchange="validateDates()">
                    </div>
                    <div class="col-md-2">
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
                    <div class="col-md-2">
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
                            <a href="habit_calendar.php<?php 
                                $params = $_GET;
                                echo !empty($params) ? '?' . http_build_query($params) : ''; 
                            ?>" class="btn btn-secondary">
                                <i class="fas fa-calendar"></i>
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <?php if (empty($daily_points)): ?>
            <div class="alert alert-info">
                No data found for the selected date range. Please try different dates.
            </div>
        <?php else: ?>
            <!-- Summary Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <h6 class="card-title">Total Points</h6>
                            <h3 class="mb-0"><?php echo $total_points; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h6 class="card-title">Completion Rate</h6>
                            <h3 class="mb-0"><?php 
                                $total_entries = array_sum(array_column($habit_points, 'total'));
                                echo $total_entries > 0 ? round(($total_points / $total_entries) * 100) : 0;
                            ?>%</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <h6 class="card-title">Average Daily Points</h6>
                            <h3 class="mb-0"><?php 
                                $days = count($daily_points);
                                echo $days > 0 ? round($total_points / $days, 1) : 0;
                            ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <h6 class="card-title">Active Habits</h6>
                            <h3 class="mb-0"><?php echo count($habit_points); ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts -->
            <div class="row">
                <!-- Daily Points Trend -->
                <div class="col-12 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Daily Points Trend</h5>
                            <canvas id="dailyPointsChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Top Habits -->
                <div class="col-12 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Top Performing Habits</h5>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Habit</th>
                                            <th>Category</th>
                                            <th>Total Points</th>
                                            <th>Completion Rate</th>
                                            <th>Status Distribution</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($habit_points as $id => $habit): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($habit['name']); ?></td>
                                                <td>
                                                    <span class="badge" style="background-color: <?php echo $categories[$habit['category_id']]['color'] ?? '#ccc'; ?>">
                                                        <?php echo htmlspecialchars($categories[$habit['category_id']]['name'] ?? 'Uncategorized'); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo $habit['total']; ?></td>
                                                <td>
                                                    <?php 
                                                    $total = ($habit['completion'] ?? 0) + ($habit['procrastination'] ?? 0) + ($habit['skip'] ?? 0);
                                                    $completion_rate = $total > 0 ? round((($habit['completion'] ?? 0) / $total) * 100) : 0;
                                                    ?>
                                                    <div class="progress" style="height: 20px;">
                                                        <div class="progress-bar bg-success" 
                                                             role="progressbar" 
                                                             style="width: <?php echo $completion_rate; ?>%"
                                                             aria-valuenow="<?php echo $completion_rate; ?>" 
                                                             aria-valuemin="0" 
                                                             aria-valuemax="100">
                                                            <?php echo $completion_rate; ?>%
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="d-flex gap-1">
                                                        <span class="badge bg-success" title="Completed"><?php echo $habit['completion'] ?? 0; ?></span>
                                                        <span class="badge bg-warning" title="Procrastinated"><?php echo $habit['procrastination'] ?? 0; ?></span>
                                                        <span class="badge bg-danger" title="Skipped"><?php echo $habit['skip'] ?? 0; ?></span>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Category Points Chart -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Points by Category</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="categoryChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- NEW FEATURE: Habit Frequency - Weekly Habits Performance -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Weekly Habit Performance</h5>
                </div>
                <div class="card-body">
                    <?php
                    // Get frequency-based habits with progress
                    $frequency_query = "
                        SELECT 
                            h.id,
                            h.name,
                            hf.times_per_week,
                            (SELECT COUNT(*) FROM habit_completions hc 
                             WHERE hc.habit_id = h.id 
                             AND hc.completion_date BETWEEN ? AND ?
                             AND hc.status = 'completed') as actual_completions
                        FROM habits h
                        JOIN habit_frequency hf ON h.id = hf.habit_id
                        WHERE h.is_active = 1";
                    
                    if ($category_id !== 'all') {
                        $frequency_query .= " AND h.category_id = ?";
                        $params = [$start_date, $end_date, $category_id];
                        $types = "ssi";
                    } else {
                        $params = [$start_date, $end_date];
                        $types = "ss";
                    }
                    
                    $frequency_query .= " ORDER BY h.name";
                    
                    $stmt = $conn->prepare($frequency_query);
                    $stmt->bind_param($types, ...$params);
                    $stmt->execute();
                    $frequency_result = $stmt->get_result();
                    
                    if ($frequency_result->num_rows > 0):
                        // Calculate date range in weeks
                        $start = new DateTime($start_date);
                        $end = new DateTime($end_date);
                        $interval = $start->diff($end);
                        $days = $interval->days + 1;
                        $weeks = max(1, ceil($days / 7));
                    ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Habit</th>
                                    <th>Target</th>
                                    <th>Progress</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($habit = $frequency_result->fetch_assoc()): 
                                    $total_expected = $habit['times_per_week'] * $weeks;
                                    $completion_rate = $total_expected > 0 ? 
                                        min(100, ($habit['actual_completions'] / $total_expected) * 100) : 0;
                                    
                                    // Determine color based on completion rate
                                    if ($completion_rate >= 90) {
                                        $color_class = 'bg-success';
                                    } elseif ($completion_rate >= 70) {
                                        $color_class = 'bg-info';
                                    } elseif ($completion_rate >= 50) {
                                        $color_class = 'bg-warning';
                                    } else {
                                        $color_class = 'bg-danger';
                                    }
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($habit['name']); ?></td>
                                    <td><?php echo $habit['times_per_week']; ?> times/week</td>
                                    <td class="w-50">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="progress w-100" style="height: 10px;">
                                                <div class="progress-bar <?php echo $color_class; ?>" role="progressbar" 
                                                     style="width: <?php echo $completion_rate; ?>%" 
                                                     aria-valuenow="<?php echo $completion_rate; ?>" 
                                                     aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                            <span class="text-nowrap">
                                                <?php echo $habit['actual_completions']; ?>/<?php echo $total_expected; ?>
                                                (<?php echo round($completion_rate); ?>%)
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-info">
                        No frequency-based habits found in this date range.
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Day-Specific Habits Performance -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Day-Specific Habits</h5>
                </div>
                <div class="card-body">
                    <?php
                    // Get day-specific habits with progress
                    $specific_days_query = "
                        SELECT 
                            h.id,
                            h.name,
                            GROUP_CONCAT(hs.day_of_week ORDER BY hs.day_of_week) as scheduled_days,
                            COUNT(hs.id) as day_count,
                            (SELECT COUNT(*) FROM habit_completions hc 
                             WHERE hc.habit_id = h.id 
                             AND hc.completion_date BETWEEN ? AND ?
                             AND hc.status = 'completed') as completions
                        FROM habits h
                        JOIN habit_schedule hs ON h.id = hs.habit_id
                        WHERE h.is_active = 1";
                    
                    if ($category_id !== 'all') {
                        $specific_days_query .= " AND h.category_id = ?";
                        $params = [$start_date, $end_date, $category_id];
                        $types = "ssi";
                    } else {
                        $params = [$start_date, $end_date];
                        $types = "ss";
                    }
                    
                    $specific_days_query .= " GROUP BY h.id, h.name ORDER BY h.name";
                    
                    $stmt = $conn->prepare($specific_days_query);
                    $stmt->bind_param($types, ...$params);
                    $stmt->execute();
                    $specific_days_result = $stmt->get_result();
                    
                    if ($specific_days_result->num_rows > 0):
                        $days_of_week = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                    ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Habit</th>
                                    <th>Scheduled On</th>
                                    <th>Completions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($habit = $specific_days_result->fetch_assoc()): 
                                    $day_numbers = explode(',', $habit['scheduled_days']);
                                    $day_names = array_map(function($day) use ($days_of_week) {
                                        return $days_of_week[(int)$day];
                                    }, $day_numbers);
                                    $day_display = implode(', ', $day_names);
                                    
                                    // Calculate expected occurrences in the date range
                                    $expected = 0;
                                    $current_date = new DateTime($start_date);
                                    $end_date_obj = new DateTime($end_date);
                                    
                                    while ($current_date <= $end_date_obj) {
                                        $current_day = $current_date->format('w'); // 0=Sunday, 6=Saturday
                                        if (in_array($current_day, $day_numbers)) {
                                            $expected++;
                                        }
                                        $current_date->modify('+1 day');
                                    }
                                    
                                    // Calculate completion rate
                                    $completion_rate = $expected > 0 ? 
                                        min(100, ($habit['completions'] / $expected) * 100) : 0;
                                    
                                    // Determine color based on completion rate
                                    if ($completion_rate >= 90) {
                                        $color_class = 'bg-success';
                                    } elseif ($completion_rate >= 70) {
                                        $color_class = 'bg-info';
                                    } elseif ($completion_rate >= 50) {
                                        $color_class = 'bg-warning';
                                    } else {
                                        $color_class = 'bg-danger';
                                    }
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($habit['name']); ?></td>
                                    <td><?php echo $day_display; ?></td>
                                    <td class="w-50">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="progress w-100" style="height: 10px;">
                                                <div class="progress-bar <?php echo $color_class; ?>" role="progressbar" 
                                                     style="width: <?php echo $completion_rate; ?>%" 
                                                     aria-valuenow="<?php echo $completion_rate; ?>" 
                                                     aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                            <span class="text-nowrap">
                                                <?php echo $habit['completions']; ?>/<?php echo $expected; ?>
                                                (<?php echo round($completion_rate); ?>%)
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-info">
                        No day-specific habits found in this date range.
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

<style>
.card {
    border: none;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}
.badge {
    font-size: 0.8rem;
    padding: 0.4em 0.6em;
}
.table th {
    font-weight: 600;
}
canvas {
    max-height: 300px;
    width: 100% !important;
}
.progress {
    background-color: #e9ecef;
    border-radius: 0.25rem;
}
.progress-bar {
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 0.875rem;
}
[title] {
    cursor: help;
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

// Daily Points Chart
const dailyPointsCtx = document.getElementById('dailyPointsChart').getContext('2d');
new Chart(dailyPointsCtx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode(array_keys($daily_points)); ?>,
        datasets: [{
            label: 'Daily Points',
            data: <?php echo json_encode(array_values($daily_points)); ?>,
            borderColor: '#0d6efd',
            backgroundColor: 'rgba(13, 110, 253, 0.1)',
            tension: 0.4,
            fill: true,
            pointRadius: 4,
            pointHoverRadius: 6,
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                mode: 'index',
                intersect: false,
                callbacks: {
                    label: function(context) {
                        return `Points: ${context.parsed.y}`;
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1,
                    font: {
                        size: 12
                    }
                },
                grid: {
                    color: 'rgba(0, 0, 0, 0.1)'
                }
            },
            x: {
                grid: {
                    display: false
                },
                ticks: {
                    font: {
                        size: 12
                    }
                }
            }
        }
    }
});

function updateHabits() {
    const categorySelect = document.querySelector('select[name="category_id"]');
    const habitSelect = document.getElementById('habitSelect');
    const selectedCategory = categorySelect.value;
    const currentHabitValue = habitSelect.value;
    
    // Show/hide options based on category
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

    // Check if the currently selected habit should be visible
    const selectedOption = habitSelect.options[habitSelect.selectedIndex];
    if (selectedOption && selectedOption.style.display === 'none') {
        habitSelect.value = 'all';
    } else if (currentHabitValue === 'all') {
        habitSelect.value = 'all';
    }

    // Ensure "All Habits" remains selected when appropriate
    if (selectedCategory === 'all' && currentHabitValue === 'all') {
        habitSelect.value = 'all';
    }
}

// Call updateHabits on page load and when form is submitted
document.addEventListener('DOMContentLoaded', updateHabits);
document.getElementById('filterForm').addEventListener('submit', function() {
    // Set a small timeout to ensure the form values are updated
    setTimeout(updateHabits, 0);
});
</script>

<?php require_once '../../includes/footer.php'; ?> 