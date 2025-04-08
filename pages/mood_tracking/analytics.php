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

// Get mood data for charts
$mood_by_day = getMoodByDay($start_date, $end_date, $tag_ids);
$mood_by_time = getMoodByTimeOfDay($start_date, $end_date, $tag_ids);
$mood_by_tag = getMoodByTag($start_date, $end_date);
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
                        $avg_mood = $stats['average_mood'];
                        if ($avg_mood >= 4.5) echo '😄';
                        else if ($avg_mood >= 3.5) echo '🙂';
                        else if ($avg_mood >= 2.5) echo '😐';
                        else if ($avg_mood >= 1.5) echo '😕';
                        else echo '😢';
                        ?>
                    </div>
                    <div class="stat-value"><?php echo number_format($avg_mood, 1); ?></div>
                    <div class="stat-label">Average Mood</div>
                </div>
            </div>
            
            <div class="col-md-3 col-sm-6 mb-4 mb-md-0">
                <div class="analytics-card stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="stat-value"><?php echo $stats['total_entries']; ?></div>
                    <div class="stat-label">Total Entries</div>
                </div>
            </div>
            
            <div class="col-md-3 col-sm-6 mb-4 mb-md-0">
                <div class="analytics-card stat-card">
                    <div class="stat-icon">
                        <?php
                        $most_common_mood = $stats['most_common_mood'];
                        if ($most_common_mood == 5) echo '😄';
                        else if ($most_common_mood == 4) echo '🙂';
                        else if ($most_common_mood == 3) echo '😐';
                        else if ($most_common_mood == 2) echo '😕';
                        else echo '😢';
                        ?>
                    </div>
                    <div class="stat-value"><?php echo $most_common_mood; ?>/5</div>
                    <div class="stat-label">Most Common Mood</div>
                </div>
            </div>
            
            <div class="col-md-3 col-sm-6 mb-4 mb-md-0">
                <div class="analytics-card stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-tags"></i>
                    </div>
                    <div class="stat-value"><?php echo count($stats['top_tags']); ?></div>
                    <div class="stat-label">Tags Used</div>
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
            labels: <?php echo json_encode(array_keys($mood_by_day)); ?>,
            datasets: [{
                label: 'Average Mood',
                data: <?php echo json_encode(array_values($mood_by_day)); ?>,
                backgroundColor: chartColors.accentLight,
                borderColor: chartColors.accent,
                borderWidth: 2,
                tension: 0.3,
                fill: true,
                pointBackgroundColor: chartColors.accent,
                pointRadius: 4,
                pointHoverRadius: 6
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
                        stepSize: 1
                    },
                    title: {
                        display: true,
                        text: 'Mood Level'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Date'
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
                            return 'Mood: ' + context.raw + '/5';
                        }
                    }
                }
            }
        }
    });
    
    // Mood Distribution Chart
    const moodDistributionCtx = document.getElementById('moodDistributionChart').getContext('2d');
    new Chart(moodDistributionCtx, {
        type: 'doughnut',
        data: {
            labels: ['Very Bad (1)', 'Bad (2)', 'Neutral (3)', 'Good (4)', 'Very Good (5)'],
            datasets: [{
                data: [
                    <?php echo isset($stats['mood_counts'][1]) ? $stats['mood_counts'][1] : 0; ?>,
                    <?php echo isset($stats['mood_counts'][2]) ? $stats['mood_counts'][2] : 0; ?>,
                    <?php echo isset($stats['mood_counts'][3]) ? $stats['mood_counts'][3] : 0; ?>,
                    <?php echo isset($stats['mood_counts'][4]) ? $stats['mood_counts'][4] : 0; ?>,
                    <?php echo isset($stats['mood_counts'][5]) ? $stats['mood_counts'][5] : 0; ?>
                ],
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
                    position: 'bottom'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.raw || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = Math.round((value / total) * 100);
                            return label + ': ' + value + ' entries (' + percentage + '%)';
                        }
                    }
                }
            }
        }
    });
    
    // Mood by Time of Day Chart
    const moodByTimeCtx = document.getElementById('moodByTimeChart').getContext('2d');
    new Chart(moodByTimeCtx, {
        type: 'bar',
        data: {
            labels: ['Morning', 'Afternoon', 'Evening', 'Night'],
            datasets: [{
                label: 'Average Mood',
                data: [
                    <?php echo isset($mood_by_time['morning']) ? $mood_by_time['morning'] : 0; ?>,
                    <?php echo isset($mood_by_time['afternoon']) ? $mood_by_time['afternoon'] : 0; ?>,
                    <?php echo isset($mood_by_time['evening']) ? $mood_by_time['evening'] : 0; ?>,
                    <?php echo isset($mood_by_time['night']) ? $mood_by_time['night'] : 0; ?>
                ],
                backgroundColor: [
                    chartColors.yellow,
                    chartColors.orange,
                    chartColors.blue,
                    chartColors.gray
                ],
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
                        stepSize: 1
                    },
                    title: {
                        display: true,
                        text: 'Average Mood Level'
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
                            return 'Average Mood: ' + context.raw.toFixed(1) + '/5';
                        }
                    }
                }
            }
        }
    });
    
    <?php if (!empty($mood_by_tag)): ?>
    // Mood by Tag Chart
    const moodByTagCtx = document.getElementById('moodByTagChart').getContext('2d');
    new Chart(moodByTagCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_column($mood_by_tag, 'name')); ?>,
            datasets: [{
                label: 'Average Mood',
                data: <?php echo json_encode(array_column($mood_by_tag, 'avg_mood')); ?>,
                backgroundColor: <?php echo json_encode(array_column($mood_by_tag, 'color')); ?>,
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            scales: {
                x: {
                    beginAtZero: true,
                    max: 5,
                    ticks: {
                        stepSize: 1
                    },
                    title: {
                        display: true,
                        text: 'Average Mood Level'
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
                            return 'Average Mood: ' + context.raw.toFixed(1) + '/5';
                        }
                    }
                }
            }
        }
    });
    <?php endif; ?>
}

// Improve mobile experience
document.addEventListener('DOMContentLoaded', function() {
    // Fix iOS zoom on input focus
    document.querySelectorAll('input[type="text"], input[type="email"], input[type="tel"], input[type="date"]').forEach(input => {
        input.style.fontSize = '16px';
    });
});
</script>

<?php
// Include footer
require_once __DIR__ . '/../../includes/footer.php';
?>
