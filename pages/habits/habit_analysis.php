<?php
require_once '../../includes/header.php';
require_once '../../includes/db_connect.php';

// Get filter parameters
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$habit_id = $_GET['habit_id'] ?? 'all';
$category_id = $_GET['category_id'] ?? 'all';
$status = $_GET['status'] ?? 'all';

// Get categories for filtering
$categories_query = "SELECT id, name FROM habit_categories ORDER BY name";
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

// Fetch habit completion data
$completions_query = "
    SELECT 
        h.id as habit_id,
        h.name as habit_name,
        h.category_id,
        hc.completion_date,
        hc.status,
        hc.reason,
        YEARWEEK(hc.completion_date, 1) as year_week,
        DATE_FORMAT(hc.completion_date, '%Y-%m-%d') as date,
        DATE_FORMAT(hc.completion_date, '%W') as weekday
    FROM habits h
    LEFT JOIN habit_completions hc ON h.id = hc.habit_id 
    WHERE h.is_active = 1 
    AND hc.completion_date BETWEEN ? AND ?";

$params = [$start_date, $end_date];
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

if ($status !== 'all') {
    $completions_query .= " AND hc.status = ?";
    $params[] = $status;
    $types .= "s";
}

$stmt = $conn->prepare($completions_query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$completions_result = $stmt->get_result();

// Process data for analysis
$weekly_data = [];
$daily_data = [];
$reason_data = [];
$weekday_data = [];
$habit_status_counts = [];

while ($row = $completions_result->fetch_assoc()) {
    // Weekly analysis
    $week_key = $row['year_week'];
    if (!isset($weekly_data[$week_key])) {
        $weekly_data[$week_key] = [
            'completed' => 0,
            'procrastinated' => 0,
            'skipped' => 0,
            'total' => 0
        ];
    }
    $weekly_data[$week_key][$row['status']]++;
    $weekly_data[$week_key]['total']++;

    // Daily analysis
    $date_key = $row['date'];
    if (!isset($daily_data[$date_key])) {
        $daily_data[$date_key] = [
            'completed' => 0,
            'procrastinated' => 0,
            'skipped' => 0,
            'total' => 0
        ];
    }
    $daily_data[$date_key][$row['status']]++;
    $daily_data[$date_key]['total']++;

    // Reason analysis
    if ($row['reason']) {
        if (!isset($reason_data[$row['reason']])) {
            $reason_data[$row['reason']] = [
                'count' => 0,
                'habits' => []
            ];
        }
        $reason_data[$row['reason']]['count']++;
        if (!in_array($row['habit_name'], $reason_data[$row['reason']]['habits'])) {
            $reason_data[$row['reason']]['habits'][] = $row['habit_name'];
        }
    }

    // Weekday analysis
    $weekday = $row['weekday'];
    if (!isset($weekday_data[$weekday])) {
        $weekday_data[$weekday] = [
            'completed' => 0,
            'procrastinated' => 0,
            'skipped' => 0,
            'total' => 0
        ];
    }
    $weekday_data[$weekday][$row['status']]++;
    $weekday_data[$weekday]['total']++;

    // Habit status counts
    $habit_key = $row['habit_name'];
    if (!isset($habit_status_counts[$habit_key])) {
        $habit_status_counts[$habit_key] = [
            'completed' => 0,
            'procrastinated' => 0,
            'skipped' => 0,
            'total' => 0
        ];
    }
    $habit_status_counts[$habit_key][$row['status']]++;
    $habit_status_counts[$habit_key]['total']++;
}

// Sort reason data by count
arsort($reason_data);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Habit Analysis</title>
    <!-- Add Chart.js library -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .analysis-container {
            margin-bottom: 2rem;
        }
        .chart-container {
            margin-bottom: 1.5rem;
            background: white;
            border-radius: 0.5rem;
            padding: 1rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            min-height: 250px;
            position: relative;
            aspect-ratio: 16/9;
            max-height: 350px;
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
        .chart-container canvas {
            width: 100% !important;
            height: 100% !important;
        }
        #reasonChart {
            aspect-ratio: 4/3;
            max-height: 300px;
        }
        #weekdayChart, #habitChart {
            aspect-ratio: 1/1;
            min-height: 200px;
            max-height: 250px;
        }
        .reason-card {
            background: white;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .reason-count {
            font-size: 1.1rem;
            font-weight: 500;
            color: #0d6efd;
        }
        .habit-list {
            margin-top: 0.5rem;
            font-size: 0.9rem;
            color: #6c757d;
        }
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.875rem;
            font-weight: 500;
        }
        .status-completed {
            background-color: rgba(40, 167, 69, 0.1);
            color: #28a745;
        }
        .status-procrastinated {
            background-color: rgba(255, 193, 7, 0.1);
            color: #ffc107;
        }
        .status-skipped {
            background-color: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }
        .progress {
            height: 0.5rem;
        }
        .nav-pills {
            display: flex;
            flex-wrap: nowrap;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            gap: 0.5rem;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }
        .nav-pills::-webkit-scrollbar {
            display: none;
        }
        .nav-pills .nav-link {
            white-space: nowrap;
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
            border-radius: 0.5rem;
            color: #cdaf56;
        }
        .nav-pills .nav-link.active {
            background-color: #cdaf56;
            color: white;
        }
        .table-responsive {
            margin-top: 1rem;
            font-size: 0.9rem;
        }
        @media (max-width: 768px) {
            .container {
                padding-left: 0.5rem;
                padding-right: 0.5rem;
            }
            .chart-container {
                padding: 0.75rem;
                min-height: 200px;
                margin-bottom: 1rem;
            }
            .nav-pills {
                margin-bottom: 1rem;
            }
            .nav-pills .nav-link {
                padding: 0.4rem 0.8rem;
                font-size: 0.85rem;
            }
            .btn {
                padding: 0.4rem 0.8rem;
                font-size: 0.85rem;
            }
            .table-responsive {
                font-size: 0.85rem;
            }
            .status-badge {
                font-size: 0.75rem;
                padding: 0.2rem 0.4rem;
            }
        }
        @media (max-width: 576px) {
            .chart-container {
                aspect-ratio: 4/3;
                padding: 0.5rem;
            }
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
            .table th, .table td {
                padding: 0.4rem;
                font-size: 0.8rem;
            }
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <!-- Navigation -->
        <div class="nav-top">
            <div class="nav-buttons">
                <a href="../habits/index.php" class="btn btn-custom-outline">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
                <a href="reports.php" class="btn btn-custom">
                    <i class="fas fa-chart-bar"></i> Reports
                </a>
                <a href="visual_report.php" class="btn btn-custom">
                    <i class="fas fa-chart-pie"></i> Visual Report
                </a>
                <a href="habit_calendar.php" class="btn btn-custom-outline">
                    <i class="fas fa-calendar"></i> Calendar
                </a>
            </div>
            <h4 class="mb-0">Habit Analysis</h4>
        </div>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-2" id="filterForm">
                    <div class="col-12 col-sm-6 col-md-2">
                        <label class="form-label">Start Date</label>
                        <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
                    </div>
                    <div class="col-12 col-sm-6 col-md-2">
                        <label class="form-label">End Date</label>
                        <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
                    </div>
                    <div class="col-12 col-sm-6 col-md-2">
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
                    <div class="col-12 col-sm-6 col-md-2">
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
                    <div class="col-12 col-sm-6 col-md-2">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All Status</option>
                            <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="procrastinated" <?php echo $status === 'procrastinated' ? 'selected' : ''; ?>>Later</option>
                            <option value="skipped" <?php echo $status === 'skipped' ? 'selected' : ''; ?>>Skipped</option>
                        </select>
                    </div>
                    <div class="col-12 col-sm-6 col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-grow-1">Update</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Analysis Tabs -->
        <ul class="nav nav-pills mb-4" id="analysisTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="weekly-tab" data-bs-toggle="tab" data-bs-target="#weekly" type="button" role="tab">
                    Weekly Analysis
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="daily-tab" data-bs-toggle="tab" data-bs-target="#daily" type="button" role="tab">
                    Daily Analysis
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="reasons-tab" data-bs-toggle="tab" data-bs-target="#reasons" type="button" role="tab">
                    Reason Analysis
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="patterns-tab" data-bs-toggle="tab" data-bs-target="#patterns" type="button" role="tab">
                    Pattern Analysis
                </button>
            </li>
        </ul>

        <div class="tab-content">
            <!-- Weekly Analysis -->
            <div class="tab-pane fade show active" id="weekly">
                <div class="analysis-container">
                    <div class="chart-container">
                        <canvas id="weeklyChart"></canvas>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Week</th>
                                    <th>Completed</th>
                                    <th>Later</th>
                                    <th>Skipped</th>
                                    <th>Success Rate</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($weekly_data as $week => $data): 
                                    $success_rate = $data['total'] > 0 ? 
                                        round(($data['completed'] / $data['total']) * 100) : 0;
                                ?>
                                <tr>
                                    <td>Week <?php echo substr($week, -2); ?></td>
                                    <td>
                                        <span class="status-badge status-completed">
                                            <?php echo $data['completed']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge status-procrastinated">
                                            <?php echo $data['procrastinated']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge status-skipped">
                                            <?php echo $data['skipped']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="progress">
                                            <div class="progress-bar bg-success" 
                                                 role="progressbar" 
                                                 style="width: <?php echo $success_rate; ?>%"
                                                 aria-valuenow="<?php echo $success_rate; ?>" 
                                                 aria-valuemin="0" 
                                                 aria-valuemax="100">
                                            </div>
                                        </div>
                                        <small class="text-muted"><?php echo $success_rate; ?>%</small>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Daily Analysis -->
            <div class="tab-pane fade" id="daily">
                <div class="analysis-container">
                    <div class="chart-container">
                        <canvas id="dailyChart"></canvas>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Completed</th>
                                    <th>Later</th>
                                    <th>Skipped</th>
                                    <th>Success Rate</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($daily_data as $date => $data): 
                                    $success_rate = $data['total'] > 0 ? 
                                        round(($data['completed'] / $data['total']) * 100) : 0;
                                ?>
                                <tr>
                                    <td><?php echo date('M j, Y', strtotime($date)); ?></td>
                                    <td>
                                        <span class="status-badge status-completed">
                                            <?php echo $data['completed']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge status-procrastinated">
                                            <?php echo $data['procrastinated']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge status-skipped">
                                            <?php echo $data['skipped']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="progress">
                                            <div class="progress-bar bg-success" 
                                                 role="progressbar" 
                                                 style="width: <?php echo $success_rate; ?>%"
                                                 aria-valuenow="<?php echo $success_rate; ?>" 
                                                 aria-valuemin="0" 
                                                 aria-valuemax="100">
                                            </div>
                                        </div>
                                        <small class="text-muted"><?php echo $success_rate; ?>%</small>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Reason Analysis -->
            <div class="tab-pane fade" id="reasons">
                <div class="analysis-container">
                    <div class="chart-container">
                        <canvas id="reasonChart"></canvas>
                    </div>
                    <div class="row">
                        <?php foreach ($reason_data as $reason => $data): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="reason-card">
                                <h5 class="mb-2"><?php echo htmlspecialchars($reason); ?></h5>
                                <div class="reason-count">
                                    <?php echo $data['count']; ?> times
                                </div>
                                <div class="habit-list">
                                    Affected habits: <?php echo implode(', ', $data['habits']); ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Pattern Analysis -->
            <div class="tab-pane fade" id="patterns">
                <div class="analysis-container">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="chart-container">
                                <h5 class="text-center mb-3">Weekday Performance</h5>
                                <canvas id="weekdayChart"></canvas>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="chart-container">
                                <h5 class="text-center mb-3">Habit Performance</h5>
                                <canvas id="habitChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Update habits dropdown based on category selection
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

    document.addEventListener('DOMContentLoaded', function() {
        // Initialize Bootstrap tabs
        const triggerTabList = document.querySelectorAll('#analysisTab button');
        triggerTabList.forEach(function(triggerEl) {
            triggerEl.addEventListener('click', function(event) {
                event.preventDefault();
                
                // Remove active class from all tabs and panes
                triggerTabList.forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.tab-pane').forEach(p => {
                    p.classList.remove('show', 'active');
                    p.style.display = 'none';
                });
                
                // Add active class to clicked tab and corresponding pane
                this.classList.add('active');
                const target = document.querySelector(this.getAttribute('data-bs-target'));
                target.classList.add('show', 'active');
                target.style.display = 'block';

                // Trigger window resize to fix chart rendering
                window.dispatchEvent(new Event('resize'));
            });
        });

        // Show initial tab
        document.querySelector('.tab-pane.show.active').style.display = 'block';

        // Initialize charts
        const weeklyData = <?php echo json_encode($weekly_data); ?>;
        const dailyData = <?php echo json_encode($daily_data); ?>;
        const reasonData = <?php echo json_encode($reason_data); ?>;
        const weekdayData = <?php echo json_encode($weekday_data); ?>;
        const habitData = <?php echo json_encode($habit_status_counts); ?>;

        // Weekly Chart
        new Chart(document.getElementById('weeklyChart'), {
            type: 'bar',
            data: {
                labels: Object.keys(weeklyData).map(week => 'Week ' + week.substr(-2)),
                datasets: [{
                    label: 'Completed',
                    data: Object.values(weeklyData).map(d => d.completed),
                    backgroundColor: 'rgba(40, 167, 69, 0.5)',
                    borderColor: 'rgb(40, 167, 69)',
                    borderWidth: 1
                }, {
                    label: 'Later',
                    data: Object.values(weeklyData).map(d => d.procrastinated),
                    backgroundColor: 'rgba(255, 193, 7, 0.5)',
                    borderColor: 'rgb(255, 193, 7)',
                    borderWidth: 1
                }, {
                    label: 'Skipped',
                    data: Object.values(weeklyData).map(d => d.skipped),
                    backgroundColor: 'rgba(220, 53, 69, 0.5)',
                    borderColor: 'rgb(220, 53, 69)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        stacked: true
                    },
                    x: {
                        stacked: true
                    }
                }
            }
        });

        // Daily Chart
        new Chart(document.getElementById('dailyChart'), {
            type: 'line',
            data: {
                labels: Object.keys(dailyData).map(date => {
                    const d = new Date(date);
                    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                }),
                datasets: [{
                    label: 'Completed',
                    data: Object.values(dailyData).map(d => d.completed),
                    borderColor: 'rgb(40, 167, 69)',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    fill: true
                }, {
                    label: 'Later',
                    data: Object.values(dailyData).map(d => d.procrastinated),
                    borderColor: 'rgb(255, 193, 7)',
                    backgroundColor: 'rgba(255, 193, 7, 0.1)',
                    fill: true
                }, {
                    label: 'Skipped',
                    data: Object.values(dailyData).map(d => d.skipped),
                    borderColor: 'rgb(220, 53, 69)',
                    backgroundColor: 'rgba(220, 53, 69, 0.1)',
                    fill: true
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Reason Chart
        new Chart(document.getElementById('reasonChart'), {
            type: 'doughnut',
            data: {
                labels: Object.keys(reasonData),
                datasets: [{
                    data: Object.values(reasonData).map(d => d.count),
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.8)',    // Pink - Being careless
                        'rgba(54, 162, 235, 0.8)',    // Blue - Using social media
                        'rgba(255, 206, 86, 0.8)',    // Yellow - Being lazy
                        'rgba(75, 192, 192, 0.8)',    // Teal - Chatting with people
                        'rgba(153, 102, 255, 0.8)',   // Purple - Super busy
                        'rgba(255, 159, 64, 0.8)',    // Orange - Being moody
                        'rgba(255, 99, 71, 0.8)',     // Tomato - Tired of this habit
                        'rgba(106, 90, 205, 0.8)'     // Slate blue - Being stressed
                    ],
                    borderColor: [
                        'rgb(255, 99, 132)',    // Pink
                        'rgb(54, 162, 235)',    // Blue
                        'rgb(255, 206, 86)',    // Yellow
                        'rgb(75, 192, 192)',    // Teal
                        'rgb(153, 102, 255)',   // Purple
                        'rgb(255, 159, 64)',    // Orange
                        'rgb(255, 99, 71)',     // Tomato
                        'rgb(106, 90, 205)'     // Slate blue
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            boxWidth: 12,
                            font: {
                                size: 11
                            }
                        }
                    }
                }
            }
        });

        // Weekday Chart
        new Chart(document.getElementById('weekdayChart'), {
            type: 'radar',
            data: {
                labels: Object.keys(weekdayData),
                datasets: [{
                    label: 'Completed',
                    data: Object.values(weekdayData).map(d => d.completed),
                    backgroundColor: 'rgba(40, 167, 69, 0.2)',
                    borderColor: 'rgb(40, 167, 69)',
                    borderWidth: 1
                }, {
                    label: 'Later',
                    data: Object.values(weekdayData).map(d => d.procrastinated),
                    backgroundColor: 'rgba(255, 193, 7, 0.2)',
                    borderColor: 'rgb(255, 193, 7)',
                    borderWidth: 1
                }, {
                    label: 'Skipped',
                    data: Object.values(weekdayData).map(d => d.skipped),
                    backgroundColor: 'rgba(220, 53, 69, 0.2)',
                    borderColor: 'rgb(220, 53, 69)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    r: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Habit Chart
        new Chart(document.getElementById('habitChart'), {
            type: 'bar',
            data: {
                labels: Object.keys(habitData),
                datasets: [{
                    label: 'Completed',
                    data: Object.values(habitData).map(d => d.completed),
                    backgroundColor: 'rgba(40, 167, 69, 0.5)',
                    borderColor: 'rgb(40, 167, 69)',
                    borderWidth: 1
                }, {
                    label: 'Later',
                    data: Object.values(habitData).map(d => d.procrastinated),
                    backgroundColor: 'rgba(255, 193, 7, 0.5)',
                    borderColor: 'rgb(255, 193, 7)',
                    borderWidth: 1
                }, {
                    label: 'Skipped',
                    data: Object.values(habitData).map(d => d.skipped),
                    backgroundColor: 'rgba(220, 53, 69, 0.5)',
                    borderColor: 'rgb(220, 53, 69)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        stacked: true
                    },
                    x: {
                        stacked: true
                    }
                }
            }
        });
    });
    </script>
</body>
</html>

<?php require_once '../../includes/footer.php'; ?> 