<?php
// Include required files
require_once __DIR__ . '/includes/functions.php';

// Set page title
$page_title = "Mood Analytics";

// Include header
require_once __DIR__ . '/../../includes/header.php';

// Initialize filter variables
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$tag_ids = isset($_GET['tags']) && !empty($_GET['tags']) ? explode(',', $_GET['tags']) : [];

// Get mood statistics
$stats = getMoodStatistics($start_date, $end_date, $tag_ids);

// Get all tags for filtering
$all_tags = getMoodTags();

// Get filtered mood data for charts
$mood_over_time = getMoodByDay($start_date, $end_date, $tag_ids);
$mood_by_time = getMoodByTimeOfDay($start_date, $end_date, $tag_ids);
$mood_by_tag = getMoodByTag($start_date, $end_date);

// Get the date range type (day, week, month)
$date_range_type = 'month'; // default
if ($start_date === $end_date) {
    $date_range_type = 'day';
} else if (strtotime($end_date) - strtotime($start_date) <= 7*24*60*60) {
    $date_range_type = 'week';
}

// Format labels and data based on range type
$formatted_labels = [];
$formatted_data = [];

if ($date_range_type === 'day') {
    // Group data by hour for day view
    $query = "SELECT 
                DATE_FORMAT(date, '%H:00') as time_label,
                AVG(mood_level) as avg_mood,
                GROUP_CONCAT(notes SEPARATOR '|') as notes
              FROM mood_entries 
              WHERE DATE(date) = ?
              GROUP BY DATE_FORMAT(date, '%H')
              ORDER BY time_label";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $start_date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $formatted_labels[] = $row['time_label'];
        $formatted_data[] = [
            'avg_mood' => round($row['avg_mood'], 1),
            'notes' => $row['notes']
        ];
    }
} else if ($date_range_type === 'week') {
    // Group data by day for week view
    $query = "SELECT 
                DATE_FORMAT(date, '%W') as day_label,
                DATE(date) as full_date,
                AVG(mood_level) as avg_mood,
                GROUP_CONCAT(notes SEPARATOR '|') as notes
              FROM mood_entries 
              WHERE date BETWEEN ? AND ?
              GROUP BY DATE(date)
              ORDER BY date";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $formatted_labels[] = $row['day_label'];
        $formatted_data[] = [
            'avg_mood' => round($row['avg_mood'], 1),
            'notes' => $row['notes']
        ];
    }
} else {
    // Group data by week for month view
    $query = "SELECT 
                CONCAT('Week ', WEEK(date, 1) - WEEK(DATE_SUB(date, INTERVAL DAYOFMONTH(date)-1 DAY), 1) + 1) as week_label,
                AVG(mood_level) as avg_mood,
                GROUP_CONCAT(notes SEPARATOR '|') as notes
              FROM mood_entries 
              WHERE date BETWEEN ? AND ?
              GROUP BY WEEK(date, 1)
              ORDER BY date";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $formatted_labels[] = $row['week_label'];
        $formatted_data[] = [
            'avg_mood' => round($row['avg_mood'], 1),
            'notes' => $row['notes']
        ];
    }
}

// Pass the formatted data to JavaScript
$chart_labels = $formatted_labels;
$chart_data = array_column($formatted_data, 'avg_mood');
$chart_notes = array_column($formatted_data, 'notes');

// Prepare data for Mood by Time of Day Chart
$times = array_column($mood_by_time, 'time_of_day');
$time_avg_moods = array_column($mood_by_time, 'avg_mood');
$time_entry_counts = array_column($mood_by_time, 'entry_count');

// Prepare data for Mood by Tag Chart
$tag_names = array_column($mood_by_tag, 'tag_name');
$tag_colors = array_column($mood_by_tag, 'color');
$tag_avg_moods = array_column($mood_by_tag, 'avg_mood');
$tag_entry_counts = array_column($mood_by_tag, 'entry_count');
?>

<style>
:root {
    --accent-color: #cdaf56;
    --accent-color-light: #e0cb8c;
    --accent-color-dark: #b09339;
}

/* Card Styles */
.analytics-card {
    border: none;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    margin-bottom: 1.5rem;
}

/* Chart Container */
.chart-container {
    position: relative;
    height: 300px;
    width: 100%;
}

/* Stat Card */
.stat-card {
    text-align: center;
    padding: 1.5rem;
}
.stat-icon {
    font-size: 2.5rem;
    margin-bottom: 1rem;
    color: var(--accent-color);
}
.stat-value {
    font-size: 2rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
}
.stat-label {
    color: #6c757d;
    font-size: 1rem;
}

/* Mood Badge */
.mood-badge {
    display: inline-block;
    padding: 0.4rem 0.8rem;
    border-radius: 50rem;
    font-weight: 500;
    color: #fff;
    margin-right: 0.5rem;
    margin-bottom: 0.5rem;
}

/* Tag Badge */
.tag-badge {
    display: inline-block;
    padding: 0.3rem 0.6rem;
    border-radius: 50rem;
    font-size: 0.85rem;
    font-weight: 500;
    color: #fff;
    margin-right: 0.3rem;
    margin-bottom: 0.3rem;
}

/* Button Styles */
.btn-accent {
    background-color: var(--accent-color);
    border-color: var(--accent-color);
    color: #fff;
}
.btn-accent:hover {
    background-color: var(--accent-color-dark);
    border-color: var(--accent-color-dark);
    color: #fff;
}
.btn-outline-accent {
    color: var(--accent-color);
    border-color: var(--accent-color);
}
.btn-outline-accent:hover {
    background-color: var(--accent-color);
    color: #fff;
}

/* Mobile Optimizations */
@media (max-width: 767.98px) {
    .chart-container {
        height: 250px;
    }
    .stat-icon {
        font-size: 2rem;
    }
    .stat-value {
        font-size: 1.5rem;
    }
    .stat-label {
        font-size: 0.9rem;
    }
    .btn {
        padding: 0.5rem 1rem;
        font-size: 1rem;
    }
    .form-control {
        font-size: 1rem;
        padding: 0.75rem;
        height: auto;
    }
}
</style>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h3 mb-0" style="color: var(--accent-color);">
                <i class="fas fa-chart-line me-2"></i>Mood Analytics
            </h1>
            <p class="text-muted">Visualize and analyze your mood patterns</p>
        </div>
        <div class="col-md-4 text-md-end text-center mt-3 mt-md-0">
            <a href="index.php" class="btn btn-outline-accent">
                <i class="fas fa-arrow-left me-1"></i>Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="analytics-card">
        <div class="card-body">
                    <h5 class="card-title mb-3" style="color: var(--accent-color);">
                        <i class="fas fa-filter me-2"></i>Filter Analytics
            </h5>
            
                    <form id="filter_form" method="GET" action="analytics.php" class="row g-3">
                        <!-- Quick Date Range Buttons -->
                        <div class="col-12 mb-3">
                            <div class="btn-group w-100" role="group" aria-label="Date range options">
                                <button type="button" class="btn btn-outline-accent" data-range="today">Today</button>
                                <button type="button" class="btn btn-outline-accent" data-range="week">This Week</button>
                                <button type="button" class="btn btn-outline-accent" data-range="month">This Month</button>
                                <button type="button" class="btn btn-outline-accent" data-range="custom">Custom Range</button>
                            </div>
                        </div>

                        <div class="col-md-4">
                    <label for="start_date" class="form-label">Start Date</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                </div>
                        
                        <div class="col-md-4">
                    <label for="end_date" class="form-label">End Date</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                </div>
                
                        <div class="col-md-4">
                            <label class="form-label">Tags (Optional)</label>
                            <div class="dropdown">
                                <button class="btn btn-outline-secondary dropdown-toggle w-100" type="button" id="tagDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                    <?php echo !empty($tag_ids) ? count($tag_ids) . ' tags selected' : 'Select Tags'; ?>
                                </button>
                                <ul class="dropdown-menu w-100" aria-labelledby="tagDropdown">
                        <?php foreach ($all_tags as $tag): ?>
                                        <li>
                                            <div class="dropdown-item">
                                                <div class="form-check">
                                                    <input class="form-check-input tag-checkbox" type="checkbox" 
                                                           id="tag_<?php echo $tag['id']; ?>" 
                                                           value="<?php echo $tag['id']; ?>"
                                                           <?php echo in_array($tag['id'], $tag_ids) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="tag_<?php echo $tag['id']; ?>">
                                                        <span class="tag-badge" style="background-color: <?php echo $tag['color']; ?>">
                                <?php echo htmlspecialchars($tag['name']); ?>
                                                        </span>
                                                    </label>
                                                </div>
                                            </div>
                                        </li>
                        <?php endforeach; ?>
                                </ul>
                            </div>
                            <input type="hidden" id="tags" name="tags" value="<?php echo implode(',', $tag_ids); ?>">
                </div>
                
                        <div class="col-12 text-center mt-4">
                            <button type="submit" class="btn btn-accent me-2">
                                <i class="fas fa-filter me-1"></i>Apply Filters
                            </button>
                            <button type="button" class="btn btn-outline-secondary" onclick="resetFilters()">
                                <i class="fas fa-undo me-1"></i>Reset Filters
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php if (isset($stats['total_entries']) && $stats['total_entries'] > 0): ?>
        <!-- Summary Stats -->
    <div class="row mb-4">
            <div class="col-md-3 col-sm-6 mb-4 mb-md-0">
                <div class="analytics-card stat-card">
                    <div class="stat-icon">
                            <?php 
                        $avg_mood = $stats['avg_mood'];
                        if ($avg_mood >= 4.5) echo '😄';
                        else if ($avg_mood >= 3.5) echo '🙂';
                        else if ($avg_mood >= 2.5) echo '😐';
                        else if ($avg_mood >= 1.5) echo '😕';
                        else echo '😢';
                            ?>
                        </div>
                    <div class="stat-value"><?php echo number_format($avg_mood, 1); ?></div>
                    <div class="stat-label">How I Feel</div>
                </div>
            </div>
            
            <div class="col-md-3 col-sm-6 mb-4 mb-md-0">
                <div class="analytics-card stat-card">
                    <div class="stat-icon">
                        <?php
                        $mood_trend = 0;
                        if (count($mood_over_time) > 1) {
                            $first_mood = $mood_over_time[0]['avg_mood'];
                            $last_mood = $mood_over_time[count($mood_over_time)-1]['avg_mood'];
                            $mood_trend = $last_mood - $first_mood;
                        }
                        if ($mood_trend > 0.5) echo '📈';
                        else if ($mood_trend < -0.5) echo '📉';
                        else echo '➡️';
                        ?>
                    </div>
                    <div class="stat-value"><?php echo $mood_trend > 0 ? '+' . number_format($mood_trend, 1) : number_format($mood_trend, 1); ?></div>
                    <div class="stat-label">Mood Trend</div>
                </div>
            </div>
            
            <div class="col-md-3 col-sm-6 mb-4 mb-md-0">
                <div class="analytics-card stat-card">
                    <div class="stat-icon">
                        <?php
                        $best_time = '';
                        $best_mood = 0;
                        foreach ($mood_by_time as $time) {
                            if ($time['avg_mood'] > $best_mood) {
                                $best_mood = $time['avg_mood'];
                                $best_time = $time['time_of_day'];
                            }
                        }
                        if ($best_time === 'Morning') echo '🌅';
                        else if ($best_time === 'Afternoon') echo '☀️';
                        else if ($best_time === 'Evening') echo '🌆';
                        else echo '🌙';
                        ?>
                    </div>
                    <div class="stat-value"><?php echo $best_time; ?></div>
                    <div class="stat-label">Best Time of Day</div>
                </div>
            </div>
            
            <div class="col-md-3 col-sm-6 mb-4 mb-md-0">
                <div class="analytics-card stat-card">
                    <div class="stat-icon">
                        <?php
                        $best_tag = '';
                        $best_tag_mood = 0;
                        foreach ($stats['top_tags'] as $tag) {
                            if ($tag['avg_mood'] > $best_tag_mood) {
                                $best_tag_mood = $tag['avg_mood'];
                                $best_tag = $tag['name'];
                            }
                        }
                        echo '🏷️';
                        ?>
                    </div>
                    <div class="stat-value"><?php echo $best_tag ?: 'N/A'; ?></div>
                    <div class="stat-label">Most Positive Activity</div>
                </div>
            </div>
        </div>
        
        <!-- Charts -->
        <div class="row">
            <!-- Mood Over Time -->
            <div class="col-lg-8 mb-4">
                <div class="analytics-card">
                <div class="card-body">
                        <h5 class="card-title mb-3" style="color: var(--accent-color);">
                            <i class="fas fa-chart-line me-2"></i>Mood Over Time
                        </h5>
                    <div class="chart-container">
                            <canvas id="moodOverTimeChart"></canvas>
                </div>
            </div>
        </div>
    </div>

            <!-- Mood Distribution -->
            <div class="col-lg-4 mb-4">
                <div class="analytics-card">
        <div class="card-body">
                        <h5 class="card-title mb-3" style="color: var(--accent-color);">
                            <i class="fas fa-chart-pie me-2"></i>Mood Distribution
                        </h5>
            <div class="chart-container">
                            <canvas id="moodDistributionChart"></canvas>
                        </div>
            </div>
        </div>
    </div>

    <!-- Mood by Time of Day -->
            <div class="col-lg-6 mb-4">
                <div class="analytics-card">
                <div class="card-body">
                        <h5 class="card-title mb-3" style="color: var(--accent-color);">
                            <i class="fas fa-clock me-2"></i>Mood by Time of Day
                        </h5>
                    <div class="chart-container">
                        <canvas id="moodByTimeChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Mood by Tag -->
            <div class="col-lg-6 mb-4">
                <div class="analytics-card">
                    <div class="card-body">
                        <h5 class="card-title mb-3" style="color: var(--accent-color);">
                            <i class="fas fa-tags me-2"></i>Mood by Tag
                        </h5>
                        <?php if (!empty($mood_by_tag)): ?>
                            <div class="chart-container">
                                <canvas id="moodByTagChart"></canvas>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <p class="text-muted">No tag data available</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Top Tags -->
        <?php if (!empty($stats['top_tags'])): ?>
            <div class="row">
                <div class="col-12 mb-4">
                    <div class="analytics-card">
                <div class="card-body">
                            <h5 class="card-title mb-3" style="color: var(--accent-color);">
                                <i class="fas fa-tags me-2"></i>Top Tags
                            </h5>
                            <div class="row">
                                <?php foreach ($stats['top_tags'] as $tag): ?>
                                    <div class="col-lg-3 col-md-4 col-sm-6 mb-3">
                                        <div class="d-flex align-items-center">
                                            <span class="mood-badge me-2" style="background-color: <?php echo $tag['color']; ?>">
                                        <?php echo htmlspecialchars($tag['name']); ?>
                                    </span>
                                            <span class="text-muted">
                                        <?php echo $tag['count']; ?> entries
                                                <?php if (isset($tag['avg_mood'])): ?>
                                                    (Avg: <?php echo number_format($tag['avg_mood'], 1); ?>)
                                                <?php endif; ?>
                                    </span>
                                        </div>
                                    </div>
                            <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
    <?php else: ?>
        <!-- No Data Message -->
        <div class="row">
            <div class="col-12">
                <div class="analytics-card">
                    <div class="card-body text-center py-5">
                        <div class="mb-3">
                            <i class="fas fa-chart-bar fa-4x text-muted"></i>
        </div>
                        <h4 class="mb-3">No mood data available</h4>
                        <p class="text-muted mb-4">Start tracking your mood to see analytics and insights</p>
                        <a href="entry.php" class="btn btn-accent">
                            <i class="fas fa-plus me-1"></i>Create Your First Entry
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// Function to handle tag checkboxes
document.addEventListener('DOMContentLoaded', function() {
    const tagCheckboxes = document.querySelectorAll('.tag-checkbox');
    const tagsInput = document.getElementById('tags');
    const tagDropdown = document.getElementById('tagDropdown');
    
    tagCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const selectedTags = [];
            tagCheckboxes.forEach(cb => {
                if (cb.checked) {
                    selectedTags.push(cb.value);
                }
            });
            tagsInput.value = selectedTags.join(',');
            tagDropdown.textContent = selectedTags.length > 0 ? selectedTags.length + ' tags selected' : 'Select Tags';
        });
    });
    
    // Date range buttons functionality
    const dateRangeButtons = document.querySelectorAll('[data-range]');
    dateRangeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const range = this.dataset.range;
            const today = new Date();
            let startDate, endDate;

            switch(range) {
                case 'today':
                    startDate = today;
                    endDate = today;
                    break;
                case 'week':
                    startDate = new Date(today);
                    startDate.setDate(today.getDate() - today.getDay()); // Start of week (Sunday)
                    endDate = new Date(today);
                    break;
                case 'month':
                    startDate = new Date(today.getFullYear(), today.getMonth(), 1);
                    endDate = new Date(today);
                    break;
                case 'custom':
                    // Just activate the date inputs
                    document.getElementById('start_date').focus();
                    return;
            }

            // Format dates for input fields (YYYY-MM-DD)
            document.getElementById('start_date').value = startDate.toISOString().split('T')[0];
            document.getElementById('end_date').value = endDate.toISOString().split('T')[0];

            // Update active button state
            dateRangeButtons.forEach(btn => btn.classList.remove('active'));
            if (range !== 'custom') {
                this.classList.add('active');
            }
        });
    });

    // Initialize charts if data is available
    <?php if (isset($stats['total_entries']) && $stats['total_entries'] > 0): ?>
        initCharts();
    <?php endif; ?>
});

// Function to reset filters
function resetFilters() {
    document.getElementById('start_date').value = '';
    document.getElementById('end_date').value = '';
    
    // Reset tag checkboxes
    document.querySelectorAll('.tag-checkbox').forEach(checkbox => {
        checkbox.checked = false;
    });
    document.getElementById('tags').value = '';
    document.getElementById('tagDropdown').textContent = 'Select Tags';
    
    // Submit form
    document.getElementById('filter_form').submit();
}

// Function to initialize charts
function initCharts() {
    // Chart colors
    const chartColors = {
        red: '#dc3545',
        orange: '#fd7e14',
        yellow: '#ffc107',
        green: '#28a745',
        blue: '#17a2b8',
        accent: '#cdaf56',
        accentLight: '#e0cb8c',
        gray: '#6c757d'
    };
    
    // Mood Over Time Chart
    const moodOverTimeCtx = document.getElementById('moodOverTimeChart').getContext('2d');
    new Chart(moodOverTimeCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($chart_labels); ?>,
            datasets: [{
                label: 'Mood Level',
                data: <?php echo json_encode($chart_data); ?>,
                borderColor: '#4CAF50',
                backgroundColor: 'rgba(76, 175, 80, 0.1)',
                tension: 0.1,
                fill: true,
                pointBackgroundColor: function(context) {
                    const value = context.raw;
                    if (value >= 4.5) return '#2E7D32';
                    if (value >= 3.5) return '#4CAF50';
                    if (value >= 2.5) return '#FFC107';
                    if (value >= 1.5) return '#FF9800';
                    return '#F44336';
                },
                pointRadius: 8,
                pointHoverRadius: 10,
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 5,
                    ticks: {
                        stepSize: 1,
                        callback: function(value) {
                            const emojis = ['😢', '😕', '😐', '🙂', '😊'];
                            const index = value - 1;
                            return index >= 0 && index < 5 ? emojis[index] : '';
                        },
                        font: {
                            size: 20
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
                        maxRotation: 45,
                        minRotation: 45,
                        font: {
                            size: 12
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const moodValue = context.raw;
                            const emoji = ['😢', '😕', '😐', '🙂', '😊'][Math.floor(moodValue) - 1] || '';
                            return `Mood: ${emoji} (${moodValue.toFixed(1)})`;
                        },
                        afterLabel: function(context) {
                            const notes = <?php echo json_encode($chart_notes); ?>[context.dataIndex];
                            if (notes) {
                                const notesList = notes.split('|');
                                return notesList.map(note => `Note: ${note.trim()}`);
                            }
                            return '';
                        }
                    }
                },
                legend: {
                    display: false
                }
            }
        }
    });
    
    // Mood Distribution Chart
    const moodDistributionCtx = document.getElementById('moodDistributionChart').getContext('2d');
    const moodCounts = <?php echo json_encode($stats['mood_counts']); ?>;
    const totalEntries = Object.values(moodCounts).reduce((a, b) => a + b, 0);
    
    new Chart(moodDistributionCtx, {
        type: 'doughnut',
        data: {
            labels: ['Very Bad (1)', 'Bad (2)', 'Neutral (3)', 'Good (4)', 'Very Good (5)'],
            datasets: [{
                data: Object.values(moodCounts),
                backgroundColor: [
                    chartColors.red,
                    chartColors.orange,
                    chartColors.yellow,
                    chartColors.green,
                    chartColors.blue
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const value = context.raw;
                            const percentage = ((value / totalEntries) * 100).toFixed(1);
                            return context.label + ': ' + value + ' entries (' + percentage + '%)';
                        }
                    }
                }
            }
        }
    });
    
    // Mood by Time of Day Chart
    const moodByTimeCtx = document.getElementById('moodByTimeChart').getContext('2d');
    const moodByTimeData = <?php echo json_encode($mood_by_time); ?>;

    new Chart(moodByTimeCtx, {
        type: 'bar',
        data: {
            labels: moodByTimeData.map(item => item.time_of_day),
            datasets: [{
                label: 'How I Feel',
                data: moodByTimeData.map(item => item.avg_mood),
                backgroundColor: moodByTimeData.map(item => {
                    const mood = item.avg_mood;
                    if (mood >= 4.5) return chartColors.blue;
                    else if (mood >= 3.5) return chartColors.green;
                    else if (mood >= 2.5) return chartColors.yellow;
                    else if (mood >= 1.5) return chartColors.orange;
                    else return chartColors.red;
                }),
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 5,
                    ticks: {
                        callback: function(value) {
                            const emojis = ['😢', '😕', '😐', '🙂', '😄'];
                            return emojis[Math.floor(value) - 1] || '';
                        },
                        font: {
                            size: 20
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const moodValue = context.raw;
                            const emoji = ['😢', '😕', '😐', '🙂', '😄'][Math.floor(moodValue) - 1] || '';
                            return `Mood: ${emoji} (${moodValue.toFixed(1)})`;
                        }
                    }
                },
                legend: {
                    display: false
                }
            }
        }
    });
    
    // Mood by Tag Chart
    const moodByTagCtx = document.getElementById('moodByTagChart').getContext('2d');
    const moodByTagData = <?php echo json_encode($mood_by_tag); ?>;

    new Chart(moodByTagCtx, {
        type: 'bar',
        data: {
            labels: moodByTagData.map(item => item.tag_name),
            datasets: [{
                label: 'Impact on Mood',
                data: moodByTagData.map(item => item.avg_mood),
                backgroundColor: moodByTagData.map(item => item.color),
                borderWidth: 1
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    beginAtZero: true,
                    max: 5,
                    ticks: {
                        callback: function(value) {
                            const emojis = ['😢', '😕', '😐', '🙂', '😄'];
                            return emojis[Math.floor(value) - 1] || '';
                        },
                        font: {
                            size: 20
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const moodValue = context.raw;
                            const emoji = ['😢', '😕', '😐', '🙂', '😄'][Math.floor(moodValue) - 1] || '';
                            let impact = '';
                            if (moodValue >= 4.5) impact = 'Greatly improves mood';
                            else if (moodValue >= 3.5) impact = 'Positively affects mood';
                            else if (moodValue >= 2.5) impact = 'Neutral impact';
                            else if (moodValue >= 1.5) impact = 'Slightly lowers mood';
                            else impact = 'Negatively affects mood';
                            return `${impact}: ${emoji} (${moodValue.toFixed(1)})`;
                        }
                    }
                },
                legend: {
                    display: false
                }
            }
        }
    });
}

// Improve mobile experience
document.addEventListener('DOMContentLoaded', function() {
    // Fix iOS zoom on input focus
    document.querySelectorAll('input[type="text"], input[type="email"], input[type="tel"], input[type="date"]').forEach(input => {
        input.style.fontSize = '16px';
    });
});

// Helper function to get mood text
function getMoodText(value) {
    if (value >= 4.5) return 'Very Good 😊';
    if (value >= 3.5) return 'Good 🙂';
    if (value >= 2.5) return 'Neutral 😐';
    if (value >= 1.5) return 'Bad 😕';
    return 'Very Bad 😢';
}
</script>

<?php
// Include footer
require_once __DIR__ . '/../../includes/footer.php';
?>
