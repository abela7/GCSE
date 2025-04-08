<?php
// Set page title
$page_title = "Mood Tracker";

// Include database connection and functions
require_once '../../../config/db_connect.php';
require_once '../includes/functions.php';

// Get current year and month for calendar view
$current_year_month = isset($_GET['year_month']) ? $_GET['year_month'] : date('Y-m');
$current_month_name = date('F Y', strtotime($current_year_month . '-01'));

// Get mood entries by day for calendar view
$mood_entries_by_day = getMoodEntriesByDay($current_year_month);

// Get first day of month and total days in month
$first_day_of_month = date('N', strtotime($current_year_month . '-01'));
$days_in_month = date('t', strtotime($current_year_month . '-01'));

// Get previous and next month
$prev_month = date('Y-m', strtotime($current_year_month . '-01 -1 month'));
$next_month = date('Y-m', strtotime($current_year_month . '-01 +1 month'));

// Get mood statistics for the current month
$start_date = $current_year_month . '-01';
$end_date = date('Y-m-t', strtotime($start_date));
$mood_stats = getMoodStatistics($start_date, $end_date);

// Get all tags for filtering
$all_tags = getMoodTags();
$tag_categories = getMoodTagCategories();

// Include header
include '../../../includes/header.php';
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>

<style>
/* General Styles */
.mood-card {
    border: none;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: transform 0.2s;
    margin-bottom: 1rem;
}
.mood-card:hover {
    transform: translateY(-2px);
}

/* Mood Level Indicators */
.mood-level {
    font-size: 1.5rem;
    font-weight: bold;
    text-align: center;
    width: 50px;
    height: 50px;
    line-height: 50px;
    border-radius: 50%;
    margin-right: 1rem;
}
.mood-level-1 { background-color: #ff6b6b; color: white; }
.mood-level-2 { background-color: #ffa06b; color: white; }
.mood-level-3 { background-color: #ffd56b; color: black; }
.mood-level-4 { background-color: #c2e06b; color: black; }
.mood-level-5 { background-color: #6be07b; color: white; }
.mood-emoji { font-size: 2rem; }

/* Tag Styles */
.tag-badge {
    margin-right: 0.5rem;
    margin-bottom: 0.5rem;
    padding: 0.4rem 0.6rem;
    border-radius: 50rem;
}

/* Calendar Styles */
.calendar-day {
    aspect-ratio: 1;
    position: relative;
    border: 1px solid #dee2e6;
    border-radius: 0.25rem;
    padding: 0.25rem;
    min-height: 60px;
}
.calendar-day-header {
    font-size: 0.875rem;
    font-weight: bold;
    position: absolute;
    top: 0.25rem;
    left: 0.25rem;
}
.calendar-day-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100%;
    padding-top: 0.5rem;
}
.calendar-day-mood {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    margin-bottom: 0.25rem;
}
.calendar-day-entries {
    font-size: 0.75rem;
    color: #6c757d;
}
.calendar-day.inactive {
    background-color: #f8f9fa;
    color: #adb5bd;
}
.calendar-day:hover:not(.inactive) {
    background-color: #f8f9fa;
    cursor: pointer;
}

/* Chart Containers */
.chart-container {
    height: 250px;
    position: relative;
}

/* Mobile Optimizations */
@media (max-width: 767.98px) {
    .mood-level {
        width: 40px;
        height: 40px;
        line-height: 40px;
        font-size: 1.25rem;
    }
    .calendar-day {
        min-height: 50px;
    }
    .calendar-day-header {
        font-size: 0.75rem;
    }
    .calendar-day-mood {
        width: 25px;
        height: 25px;
        font-size: 0.875rem;
    }
    .calendar-day-entries {
        font-size: 0.7rem;
    }
    .chart-container {
        height: 200px;
    }
}
</style>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h3 mb-0"><i class="fas fa-smile me-2"></i>Mood Tracker</h1>
            <p class="text-muted">Track your mood to identify patterns and improve your well-being</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="entry.php" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Add Mood Entry
            </a>
        </div>
    </div>

    <!-- Mood Calendar -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="card-title mb-0"><i class="fas fa-calendar-alt me-2"></i>Mood Calendar</h5>
                <div>
                    <a href="?year_month=<?php echo $prev_month; ?>" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                    <span class="mx-2"><?php echo $current_month_name; ?></span>
                    <a href="?year_month=<?php echo $next_month; ?>" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </div>
            </div>
            
            <div class="row g-2 text-center">
                <div class="col">Mon</div>
                <div class="col">Tue</div>
                <div class="col">Wed</div>
                <div class="col">Thu</div>
                <div class="col">Fri</div>
                <div class="col">Sat</div>
                <div class="col">Sun</div>
            </div>
            
            <div class="row g-2 mt-1">
                <?php
                // Add empty cells for days before the first day of month
                for ($i = 1; $i < $first_day_of_month; $i++) {
                    echo '<div class="col"><div class="calendar-day inactive"></div></div>';
                }
                
                // Add cells for each day of the month
                for ($day = 1; $day <= $days_in_month; $day++) {
                    $has_entries = isset($mood_entries_by_day[$day]);
                    $avg_mood = $has_entries ? $mood_entries_by_day[$day]['avg_mood'] : 0;
                    $entry_count = $has_entries ? $mood_entries_by_day[$day]['entry_count'] : 0;
                    $mood_class = $avg_mood > 0 ? 'mood-level-' . round($avg_mood) : '';
                    $day_url = "history.php?date=" . $current_year_month . "-" . sprintf("%02d", $day);
                    
                    echo '<div class="col">';
                    echo '<div class="calendar-day' . ($has_entries ? '' : ' inactive') . '" ' . ($has_entries ? 'onclick="window.location=\'' . $day_url . '\'"' : '') . '>';
                    echo '<div class="calendar-day-header">' . $day . '</div>';
                    
                    if ($has_entries) {
                        echo '<div class="calendar-day-content">';
                        echo '<div class="calendar-day-mood ' . $mood_class . '">' . $avg_mood . '</div>';
                        echo '<div class="calendar-day-entries">' . $entry_count . ' ' . ($entry_count == 1 ? 'entry' : 'entries') . '</div>';
                        echo '</div>';
                    }
                    
                    echo '</div>';
                    echo '</div>';
                    
                    // Start a new row after Sunday
                    if (($first_day_of_month + $day - 1) % 7 == 0) {
                        echo '</div><div class="row g-2 mt-1">';
                    }
                }
                
                // Add empty cells for days after the last day of month
                $remaining_cells = 7 - (($first_day_of_month + $days_in_month - 1) % 7);
                if ($remaining_cells < 7) {
                    for ($i = 0; $i < $remaining_cells; $i++) {
                        echo '<div class="col"><div class="calendar-day inactive"></div></div>';
                    }
                }
                ?>
            </div>
            
            <div class="mt-3 text-center">
                <div class="d-inline-block me-3">
                    <span class="badge rounded-pill mood-level-1">1</span> Very Low
                </div>
                <div class="d-inline-block me-3">
                    <span class="badge rounded-pill mood-level-2">2</span> Low
                </div>
                <div class="d-inline-block me-3">
                    <span class="badge rounded-pill mood-level-3">3</span> Neutral
                </div>
                <div class="d-inline-block me-3">
                    <span class="badge rounded-pill mood-level-4">4</span> Good
                </div>
                <div class="d-inline-block">
                    <span class="badge rounded-pill mood-level-5">5</span> Excellent
                </div>
            </div>
        </div>
    </div>

    <!-- Mood Statistics -->
    <div class="row mb-4">
        <div class="col-md-4 mb-4 mb-md-0">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">Average Mood</h5>
                    <div class="d-flex align-items-center justify-content-center py-4">
                        <div class="mood-level mood-level-<?php echo round($mood_stats['average_mood']); ?> me-3">
                            <?php echo number_format($mood_stats['average_mood'], 1); ?>
                        </div>
                        <div class="mood-emoji">
                            <?php 
                            $avg_mood = round($mood_stats['average_mood']);
                            $emoji = '';
                            switch ($avg_mood) {
                                case 1: $emoji = '😢'; break;
                                case 2: $emoji = '😕'; break;
                                case 3: $emoji = '😐'; break;
                                case 4: $emoji = '🙂'; break;
                                case 5: $emoji = '😄'; break;
                                default: $emoji = '❓'; break;
                            }
                            echo $emoji;
                            ?>
                        </div>
                    </div>
                    <div class="text-center text-muted">
                        This Month (<?php echo date('F Y', strtotime($current_year_month)); ?>)
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4 mb-md-0">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">Mood Distribution</h5>
                    <div class="chart-container">
                        <canvas id="moodDistributionChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">Mood by Time of Day</h5>
                    <div class="chart-container">
                        <canvas id="moodByTimeChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-3">Quick Actions</h5>
                    <div class="row g-2">
                        <div class="col-6 col-md-3">
                            <a href="entry.php" class="btn btn-primary w-100">
                                <i class="fas fa-plus me-2"></i>New Entry
                            </a>
                        </div>
                        <div class="col-6 col-md-3">
                            <a href="history.php" class="btn btn-outline-primary w-100">
                                <i class="fas fa-history me-2"></i>View History
                            </a>
                        </div>
                        <div class="col-6 col-md-3">
                            <a href="analytics.php" class="btn btn-outline-primary w-100">
                                <i class="fas fa-chart-line me-2"></i>Analytics
                            </a>
                        </div>
                        <div class="col-6 col-md-3">
                            <a href="settings.php" class="btn btn-outline-primary w-100">
                                <i class="fas fa-cog me-2"></i>Settings
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Entries -->
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="card-title mb-0">Recent Entries</h5>
                <a href="history.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            
            <div id="recentEntries">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted">Loading recent entries...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Fetch recent entries
    fetch('ajax/get_recent_entries.php')
        .then(response => response.json())
        .then(data => {
            const entriesContainer = document.getElementById('recentEntries');
            
            if (data.length === 0) {
                entriesContainer.innerHTML = `
                    <div class="text-center py-5">
                        <p class="text-muted mb-3">No mood entries found</p>
                        <a href="entry.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Add Your First Mood Entry
                        </a>
                    </div>
                `;
                return;
            }
            
            let entriesHTML = '';
            
            data.forEach(entry => {
                const entryDate = new Date(entry.date);
                const formattedDate = entryDate.toLocaleDateString('en-US', { 
                    weekday: 'short', 
                    year: 'numeric', 
                    month: 'short', 
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
                
                let tagsHTML = '';
                if (entry.tags && entry.tags.length > 0) {
                    entry.tags.forEach(tag => {
                        tagsHTML += `<span class="badge tag-badge" style="background-color: ${tag.color}">${tag.name}</span>`;
                    });
                }
                
                entriesHTML += `
                    <div class="card mood-card">
                        <div class="card-body">
                            <div class="d-flex">
                                <div class="mood-level mood-level-${entry.mood_level}">
                                    ${entry.mood_level}
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1">${formattedDate}</h6>
                                            ${entry.subject_name ? `<span class="badge bg-primary me-2">${entry.subject_name}</span>` : ''}
                                            ${entry.topic_name ? `<span class="badge bg-secondary">${entry.topic_name}</span>` : ''}
                                        </div>
                                        <div>
                                            <a href="entry.php?id=${entry.id}" class="btn btn-sm btn-outline-primary me-1">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteMoodEntry(${entry.id})">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    ${entry.notes ? `<p class="mt-2 mb-2">${entry.notes.replace(/\n/g, '<br>')}</p>` : ''}
                                    
                                    ${tagsHTML ? `<div class="mt-2">${tagsHTML}</div>` : ''}
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            entriesContainer.innerHTML = entriesHTML;
        })
        .catch(error => {
            console.error('Error fetching recent entries:', error);
            document.getElementById('recentEntries').innerHTML = `
                <div class="alert alert-danger" role="alert">
                    Error loading recent entries. Please try again later.
                </div>
            `;
        });
    
    // Initialize Mood Distribution Chart
    const distributionCtx = document.getElementById('moodDistributionChart').getContext('2d');
    const distributionData = <?php echo json_encode(array_map(function($item) {
        return ['mood_level' => $item['mood_level'], 'count' => $item['count']];
    }, $mood_stats['mood_distribution'] ?? [])); ?>;
    
    const moodLabels = ['Very Low', 'Low', 'Neutral', 'Good', 'Excellent'];
    const moodColors = ['#ff6b6b', '#ffa06b', '#ffd56b', '#c2e06b', '#6be07b'];
    
    // Prepare data for chart
    const chartData = [0, 0, 0, 0, 0]; // Initialize with zeros
    distributionData.forEach(item => {
        if (item.mood_level >= 1 && item.mood_level <= 5) {
            chartData[item.mood_level - 1] = parseInt(item.count);
        }
    });
    
    new Chart(distributionCtx, {
        type: 'bar',
        data: {
            labels: moodLabels,
            datasets: [{
                label: 'Number of Entries',
                data: chartData,
                backgroundColor: moodColors,
                borderColor: moodColors,
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
    
    // Initialize Mood by Time of Day Chart
    const timeCtx = document.getElementById('moodByTimeChart').getContext('2d');
    const timeData = <?php echo json_encode($mood_stats['mood_by_time'] ?? []); ?>;
    
    const timeLabels = [];
    const timeValues = [];
    const counts = [];
    
    timeData.forEach(item => {
        timeLabels.push(item.time_of_day);
        timeValues.push(parseFloat(item.average_mood).toFixed(1));
        counts.push(parseInt(item.count));
    });
    
    new Chart(timeCtx, {
        type: 'line',
        data: {
            labels: timeLabels,
            datasets: [{
                label: 'Average Mood',
                data: timeValues,
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 2,
                tension: 0.3,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    min: 1,
                    max: 5,
                    ticks: {
                        stepSize: 1
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        afterLabel: function(context) {
                            return `Entries: ${counts[context.dataIndex]}`;
                        }
                    }
                }
            }
        }
    });
});

// Function to delete mood entry
function deleteMoodEntry(entryId) {
    if (confirm('Are you sure you want to delete this mood entry?')) {
        fetch('ajax/delete_entry.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${entryId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Reload the page to refresh the data
                window.location.reload();
            } else {
                alert('Error deleting mood entry: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while deleting the mood entry.');
        });
    }
}
</script>

<?php include '../../../includes/footer.php'; ?>
