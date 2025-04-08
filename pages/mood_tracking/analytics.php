<?php
// Set page title
$page_title = "Mood Analytics";

// Include database connection and functions
require_once '../../../config/db_connect.php';
require_once '../includes/functions.php';

// Get filter parameters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$filter_subject = isset($_GET['subject_id']) ? intval($_GET['subject_id']) : null;
$filter_tag = isset($_GET['tag_id']) ? intval($_GET['tag_id']) : null;

// Prepare tag IDs array for filtering
$tag_ids = [];
if ($filter_tag) {
    $tag_ids[] = $filter_tag;
}

// Get mood statistics with filters
$mood_stats = getMoodStatistics($start_date, $end_date, $filter_subject, $tag_ids);

// Get subjects for filter
$subjects_query = "SELECT * FROM subjects ORDER BY name";
$subjects_result = $conn->query($subjects_query);

// Get all tags for filter
$all_tags = getMoodTags();

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

/* Chart Containers */
.chart-container {
    height: 300px;
    position: relative;
}

/* Mobile Optimizations */
@media (max-width: 767.98px) {
    .chart-container {
        height: 250px;
    }
    .filter-section {
        margin-bottom: 1rem;
    }
}
</style>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h3 mb-0">
                <i class="fas fa-chart-line me-2"></i>Mood Analytics
            </h1>
            <p class="text-muted">Analyze your mood patterns and trends</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="index.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>Dashboard
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title mb-3">
                <i class="fas fa-filter me-2"></i>Filters
            </h5>
            
            <form method="GET" class="row g-3">
                <!-- Date Range -->
                <div class="col-md-3 filter-section">
                    <label for="start_date" class="form-label">Start Date</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                </div>
                <div class="col-md-3 filter-section">
                    <label for="end_date" class="form-label">End Date</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                </div>
                
                <!-- Subject -->
                <div class="col-md-3 filter-section">
                    <label for="subject_id" class="form-label">Subject</label>
                    <select class="form-select" id="subject_id" name="subject_id">
                        <option value="">All Subjects</option>
                        <?php while ($subject = $subjects_result->fetch_assoc()): ?>
                            <option value="<?php echo $subject['id']; ?>" <?php echo ($filter_subject == $subject['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($subject['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <!-- Tag -->
                <div class="col-md-3 filter-section">
                    <label for="tag_id" class="form-label">Tag</label>
                    <select class="form-select" id="tag_id" name="tag_id">
                        <option value="">All Tags</option>
                        <?php foreach ($all_tags as $tag): ?>
                            <option value="<?php echo $tag['id']; ?>" <?php echo ($filter_tag == $tag['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($tag['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Submit and Reset -->
                <div class="col-md-12 text-end">
                    <a href="analytics.php" class="btn btn-outline-secondary me-2">Reset Filters</a>
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Average Mood -->
    <div class="row mb-4">
        <div class="col-md-4 mb-4 mb-md-0">
            <div class="card h-100">
                <div class="card-body text-center">
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
                    <div class="text-muted">
                        <?php echo date('M j, Y', strtotime($start_date)); ?> - <?php echo date('M j, Y', strtotime($end_date)); ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">Mood Distribution</h5>
                    <div class="chart-container">
                        <canvas id="moodDistributionChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Mood Trend -->
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">Mood Trend Over Time</h5>
            <div class="chart-container">
                <canvas id="moodTrendChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Mood by Time of Day -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">Mood by Time of Day</h5>
                    <div class="chart-container">
                        <canvas id="moodByTimeChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">Common Tags</h5>
                    <?php if (!empty($mood_stats['common_tags'])): ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($mood_stats['common_tags'] as $tag): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span class="tag-badge" style="background-color: <?php echo $tag['color']; ?>">
                                        <?php echo htmlspecialchars($tag['name']); ?>
                                    </span>
                                    <span class="badge bg-secondary rounded-pill">
                                        <?php echo $tag['count']; ?> entries
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-muted text-center py-4">No tags recorded for this period</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Insights -->
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Mood Insights</h5>
            
            <div id="moodInsights">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted">Analyzing your mood data...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
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
    
    // Initialize Mood Trend Chart
    const trendCtx = document.getElementById('moodTrendChart').getContext('2d');
    const trendData = <?php echo json_encode($mood_stats['mood_trend'] ?? []); ?>;
    
    const trendDates = [];
    const trendValues = [];
    
    trendData.forEach(item => {
        const date = new Date(item.day);
        trendDates.push(date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }));
        trendValues.push(parseFloat(item.average_mood).toFixed(1));
    });
    
    new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: trendDates,
            datasets: [{
                label: 'Average Mood',
                data: trendValues,
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 2,
                tension: 0.3,
                fill: true,
                pointRadius: 4,
                pointHoverRadius: 6
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
                },
                x: {
                    ticks: {
                        maxRotation: 45,
                        minRotation: 45
                    }
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
        type: 'bar',
        data: {
            labels: timeLabels,
            datasets: [{
                label: 'Average Mood',
                data: timeValues,
                backgroundColor: [
                    'rgba(255, 159, 64, 0.7)',  // Morning
                    'rgba(255, 205, 86, 0.7)',  // Afternoon
                    'rgba(54, 162, 235, 0.7)',  // Evening
                    'rgba(153, 102, 255, 0.7)'  // Night
                ],
                borderColor: [
                    'rgb(255, 159, 64)',
                    'rgb(255, 205, 86)',
                    'rgb(54, 162, 235)',
                    'rgb(153, 102, 255)'
                ],
                borderWidth: 1
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
    
    // Generate mood insights
    generateMoodInsights();
});

// Function to generate mood insights
function generateMoodInsights() {
    const distributionData = <?php echo json_encode(array_map(function($item) {
        return ['mood_level' => $item['mood_level'], 'count' => $item['count']];
    }, $mood_stats['mood_distribution'] ?? [])); ?>;
    
    const timeData = <?php echo json_encode($mood_stats['mood_by_time'] ?? []); ?>;
    const trendData = <?php echo json_encode($mood_stats['mood_trend'] ?? []); ?>;
    const tagData = <?php echo json_encode($mood_stats['common_tags'] ?? []); ?>;
    const avgMood = <?php echo $mood_stats['average_mood'] ?? 0; ?>;
    
    let insights = [];
    
    // Average mood insight
    if (avgMood > 0) {
        let moodDescription = '';
        if (avgMood >= 4.5) moodDescription = 'excellent';
        else if (avgMood >= 3.5) moodDescription = 'good';
        else if (avgMood >= 2.5) moodDescription = 'neutral';
        else if (avgMood >= 1.5) moodDescription = 'low';
        else moodDescription = 'very low';
        
        insights.push(`Your average mood during this period was <strong>${moodDescription}</strong> (${avgMood.toFixed(1)}/5).`);
    }
    
    // Distribution insights
    if (distributionData.length > 0) {
        // Find most common mood
        let maxCount = 0;
        let maxMood = 0;
        distributionData.forEach(item => {
            if (parseInt(item.count) > maxCount) {
                maxCount = parseInt(item.count);
                maxMood = parseInt(item.mood_level);
            }
        });
        
        if (maxMood > 0) {
            let moodText = '';
            switch (maxMood) {
                case 1: moodText = 'very low (1/5)'; break;
                case 2: moodText = 'low (2/5)'; break;
                case 3: moodText = 'neutral (3/5)'; break;
                case 4: moodText = 'good (4/5)'; break;
                case 5: moodText = 'excellent (5/5)'; break;
            }
            
            insights.push(`Your most frequent mood was <strong>${moodText}</strong> with ${maxCount} entries.`);
        }
        
        // Check for mood extremes
        const lowMoods = distributionData.filter(item => item.mood_level <= 2).reduce((sum, item) => sum + parseInt(item.count), 0);
        const highMoods = distributionData.filter(item => item.mood_level >= 4).reduce((sum, item) => sum + parseInt(item.count), 0);
        const totalMoods = distributionData.reduce((sum, item) => sum + parseInt(item.count), 0);
        
        if (lowMoods > 0 && totalMoods > 0) {
            const lowPercentage = Math.round((lowMoods / totalMoods) * 100);
            if (lowPercentage >= 50) {
                insights.push(`<strong>${lowPercentage}%</strong> of your mood entries were in the low range (1-2). Consider checking the common factors affecting these low moods.`);
            }
        }
        
        if (highMoods > 0 && totalMoods > 0) {
            const highPercentage = Math.round((highMoods / totalMoods) * 100);
            if (highPercentage >= 50) {
                insights.push(`<strong>${highPercentage}%</strong> of your mood entries were in the high range (4-5). Great job maintaining positive moods!`);
            }
        }
    }
    
    // Time of day insights
    if (timeData.length > 0) {
        // Find best and worst times
        let bestTime = '';
        let worstTime = '';
        let bestMood = 0;
        let worstMood = 6;
        
        timeData.forEach(item => {
            const mood = parseFloat(item.average_mood);
            if (mood > bestMood) {
                bestMood = mood;
                bestTime = item.time_of_day;
            }
            if (mood < worstMood) {
                worstMood = mood;
                worstTime = item.time_of_day;
            }
        });
        
        if (bestTime && worstTime && bestTime !== worstTime) {
            insights.push(`Your mood tends to be highest during the <strong>${bestTime.toLowerCase()}</strong> (${bestMood.toFixed(1)}/5) and lowest during the <strong>${worstTime.toLowerCase()}</strong> (${worstMood.toFixed(1)}/5).`);
        }
    }
    
    // Trend insights
    if (trendData.length >= 7) {
        // Check for improving or declining trend
        const firstWeek = trendData.slice(0, 7);
        const lastWeek = trendData.slice(-7);
        
        const firstWeekAvg = firstWeek.reduce((sum, item) => sum + parseFloat(item.average_mood), 0) / firstWeek.length;
        const lastWeekAvg = lastWeek.reduce((sum, item) => sum + parseFloat(item.average_mood), 0) / lastWeek.length;
        
        const difference = lastWeekAvg - firstWeekAvg;
        
        if (Math.abs(difference) >= 0.5) {
            if (difference > 0) {
                insights.push(`Your mood has been <strong>improving</strong> over this period, with an average increase of ${difference.toFixed(1)} points.`);
            } else {
                insights.push(`Your mood has been <strong>declining</strong> over this period, with an average decrease of ${Math.abs(difference).toFixed(1)} points.`);
            }
        }
    }
    
    // Tag insights
    if (tagData.length > 0) {
        const topTag = tagData[0];
        insights.push(`The tag <strong>${topTag.name}</strong> appears most frequently in your mood entries (${topTag.count} times).`);
    }
    
    // Display insights
    const insightsContainer = document.getElementById('moodInsights');
    
    if (insights.length > 0) {
        let insightsHTML = '<div class="list-group">';
        insights.forEach(insight => {
            insightsHTML += `
                <div class="list-group-item">
                    <div class="d-flex">
                        <div class="me-3">
                            <i class="fas fa-lightbulb text-warning fs-4"></i>
                        </div>
                        <div>
                            ${insight}
                        </div>
                    </div>
                </div>
            `;
        });
        insightsHTML += '</div>';
        
        insightsContainer.innerHTML = insightsHTML;
    } else {
        insightsContainer.innerHTML = `
            <div class="text-center py-4">
                <p class="text-muted">Not enough data to generate insights. Continue tracking your mood to see patterns.</p>
            </div>
        `;
    }
}
</script>

<?php include '../../../includes/footer.php'; ?>
