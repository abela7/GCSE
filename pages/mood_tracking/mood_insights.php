<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/mood_analysis.php';

// Set page title
$page_title = "Mood Insights";

// Include header
require_once __DIR__ . '/../../includes/header.php';

// Initialize date range with validation
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Validate dates
if (!validateDate($start_date) || !validateDate($end_date)) {
    $start_date = date('Y-m-d', strtotime('-30 days'));
    $end_date = date('Y-m-d');
}

// Function to validate date format
function validateDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

// Initialize mood analyzer with error handling
try {
    $analyzer = new MoodAnalyzer($conn);
    $analysis = $analyzer->analyzeMoodPatterns($start_date, $end_date);
    
    // Check if analysis was successful
    if (isset($analysis['status']) && $analysis['status'] === 'error') {
        throw new Exception($analysis['message']);
    }
} catch (Exception $e) {
    $error_message = "Error analyzing mood data: " . $e->getMessage();
    $analysis = null;
}
?>

<style>
.insight-card {
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    margin-bottom: 1.5rem;
    border: none;
    background: white;
}

.insight-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

.mood-emoji {
    font-size: 2.5rem;
    margin-bottom: 1rem;
}

.insight-description {
    font-size: 1.1rem;
    line-height: 1.6;
    color: #495057;
}

.suggestion-box {
    background-color: #f8f9fa;
    border-radius: 8px;
    padding: 1rem;
    margin-top: 1rem;
}

.time-period-content {
    display: none;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.time-period-content.active {
    display: block;
    opacity: 1;
}

.time-period-selector {
    display: flex;
    gap: 1rem;
    margin-bottom: 2rem;
    flex-wrap: wrap;
}

.time-period-btn {
    flex: 1;
    min-width: 120px;
    padding: 0.75rem;
    border: 2px solid var(--accent-color);
    border-radius: 8px;
    background: none;
    color: var(--accent-color);
    font-weight: 500;
    transition: all 0.3s ease;
    cursor: pointer;
}

.time-period-btn.active {
    background: var(--accent-color);
    color: white;
}

.time-period-btn:hover {
    background: var(--accent-color);
    color: white;
}

.pattern-card {
    background: white;
    border: 2px solid var(--accent-color);
    color: var(--text-color);
    padding: 1.5rem;
    border-radius: 12px;
    margin-bottom: 1.5rem;
    transition: all 0.3s ease;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.pattern-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

.pattern-title {
    font-size: 1.2rem;
    font-weight: 600;
    margin-bottom: 1rem;
    color: var(--accent-color);
}

.pattern-description {
    font-size: 1.1rem;
    line-height: 1.6;
    color: var(--text-color);
}

.pattern-metrics {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid var(--border-color);
    color: var(--text-muted);
    font-size: 0.9rem;
}

.improvement-card {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
}

.improvement-title {
    color: var(--accent-color);
    font-weight: 600;
    margin-bottom: 1rem;
}

.improvement-list {
    list-style: none;
    padding: 0;
}

.improvement-item {
    display: flex;
    align-items: center;
    margin-bottom: 0.75rem;
}

.improvement-item i {
    color: var(--accent-color);
    margin-right: 0.75rem;
}

.error-message {
    background-color: #fff3f3;
    border-left: 4px solid #dc3545;
    padding: 1rem;
    margin-bottom: 1.5rem;
    border-radius: 4px;
}

.loading-spinner {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 200px;
}

.no-data-message {
    text-align: center;
    padding: 3rem 1.5rem;
    background-color: #f8f9fa;
    border-radius: 12px;
    margin-bottom: 1.5rem;
}

.create-entry-btn {
    margin-top: 1rem;
    padding: 0.5rem 1.5rem;
}

@media (max-width: 767.98px) {
    .time-period-selector {
        flex-direction: column;
    }
    
    .time-period-btn {
        width: 100%;
    }
    
    .mood-emoji {
        font-size: 2rem;
    }
    
    .insight-description {
        font-size: 1rem;
    }
    
    .pattern-card {
        padding: 1.25rem;
        margin-bottom: 1rem;
    }
    
    .pattern-title {
        font-size: 1.1rem;
    }
    
    .pattern-description {
        font-size: 1rem;
    }
    
    .col-md-6 {
        margin-bottom: 1.5rem;
    }
}
</style>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h3 mb-0" style="color: var(--accent-color);">
                <i class="fas fa-brain me-2"></i>Mood Insights
            </h1>
            <p class="text-muted">Deep analysis of your emotional patterns and well-being</p>
        </div>
        <div class="col-md-4 text-md-end text-center mt-3 mt-md-0">
            <a href="analytics.php" class="btn btn-outline-accent me-2">
                <i class="fas fa-chart-line me-1"></i>View Analytics
            </a>
            <a href="index.php" class="btn btn-outline-accent">
                <i class="fas fa-arrow-left me-1"></i>Back to Dashboard
            </a>
        </div>
    </div>

    <?php if (isset($error_message)): ?>
        <div class="error-message">
            <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>

    <!-- Date Range Selector -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="insight-card">
                <div class="card-body">
                    <form id="dateRangeForm" class="row g-3">
                        <div class="col-md-4">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" 
                                   value="<?php echo htmlspecialchars($start_date); ?>" max="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" 
                                   value="<?php echo htmlspecialchars($end_date); ?>" max="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-accent w-100">
                                <i class="fas fa-filter me-1"></i>Apply Date Range
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php if ($analysis && isset($analysis['status']) && $analysis['status'] === 'success'): ?>
        <?php $hl = $analysis['highlights']; ?>
        <!-- Highlights Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="insight-card p-4" style="background: #f7f9fa;">
                    <h3 class="h4 mb-3" style="color: var(--accent-color);"><i class="fas fa-star me-2"></i>Highlights</h3>
                    <div class="row g-3">
                        <div class="col-md-3 col-6">
                            <div class="text-center">
                                <div class="fw-bold">Best Day (Feeling)</div>
                                <div class="fs-4">
                                    <?php if ($hl['best_day']): ?>
                                        <?php echo date('M j, Y', strtotime($hl['best_day']['date'])); ?>
                                        <span class="ms-1">(<?php echo $hl['best_day']['avg_mood']; ?>)</span>
                                    <?php else: ?>-
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="text-center">
                                <div class="fw-bold">Lowest Day (Feeling)</div>
                                <div class="fs-4">
                                    <?php if ($hl['worst_day']): ?>
                                        <?php echo date('M j, Y', strtotime($hl['worst_day']['date'])); ?>
                                        <span class="ms-1">(<?php echo $hl['worst_day']['avg_mood']; ?>)</span>
                                    <?php else: ?>-
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="text-center">
                                <div class="fw-bold">Positive Streak</div>
                                <div class="fs-4"> <?php echo $hl['positive_streak']; ?> days </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="text-center">
                                <div class="fw-bold">Negative Streak</div>
                                <div class="fs-4"> <?php echo $hl['negative_streak']; ?> days </div>
                            </div>
                        </div>
                    </div>
                    <div class="row g-3 mt-3">
                        <div class="col-md-4 col-12">
                            <div class="fw-bold">Progress</div>
                            <div><?php echo ($hl['progress'] >= 0 ? '+' : '') . $hl['progress']; ?> (from first to last day)</div>
                        </div>
                        <div class="col-md-4 col-12">
                            <div class="fw-bold">Top Positive Activities</div>
                            <div>
                                <?php foreach ($hl['top_positive_tags'] as $tag): ?>
                                    <span class="badge bg-success me-1 mb-1"><?php echo htmlspecialchars($tag); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="col-md-4 col-12">
                            <div class="fw-bold">Top Negative Activities</div>
                            <div>
                                <?php foreach ($hl['top_negative_tags'] as $tag): ?>
                                    <span class="badge bg-danger me-1 mb-1"><?php echo htmlspecialchars($tag); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mood Trend Chart -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="insight-card p-4">
                    <h3 class="h5 mb-3"><i class="fas fa-chart-line me-2"></i>Mood Trend</h3>
                    <canvas id="moodTrendChart" height="80"></canvas>
                </div>
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var ctx = document.getElementById('moodTrendChart').getContext('2d');
            var trendData = <?php echo json_encode($hl['trend_data']); ?>;
            var labels = trendData.map(function(d) { return d.date; });
            var data = trendData.map(function(d) { return d.avg_mood; });
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Mood',
                        data: data,
                        borderColor: '#cdaf56',
                        backgroundColor: 'rgba(205,175,86,0.1)',
                        fill: true,
                        tension: 0.3,
                        pointRadius: 3
                    }]
                },
                options: {
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
                        legend: { display: false }
                    }
                }
            });
        });
        </script>

        <!-- Time of Day Impact Bar Chart -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="insight-card p-4">
                    <h3 class="h5 mb-3"><i class="fas fa-clock me-2"></i>Time of Day Impact</h3>
                    <canvas id="timeImpactChart" height="60"></canvas>
                </div>
            </div>
        </div>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var ctx = document.getElementById('timeImpactChart').getContext('2d');
            var periods = <?php echo json_encode(array_keys($hl['time_impact'])); ?>;
            var moods = <?php echo json_encode(array_values($hl['time_impact'])); ?>;
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: periods,
                    datasets: [{
                        label: 'Avg Mood',
                        data: moods,
                        backgroundColor: '#ffc107'
                    }]
                },
                options: {
                    scales: {
                        y: {
                            min: 1,
                            max: 5,
                            ticks: { stepSize: 1 }
                        }
                    },
                    plugins: { legend: { display: false } }
                }
            });
        });
        </script>
    <?php else: ?>
        <div class="no-data-message">
            <i class="fas fa-chart-bar fa-4x text-muted mb-3"></i>
            <h4>No mood data available for the selected time period</h4>
            <p class="text-muted">Try changing the date range or start tracking your mood</p>
            <a href="entry.php" class="btn btn-accent create-entry-btn">
                <i class="fas fa-plus me-1"></i>Create Mood Entry
            </a>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Time period selector
    const timePeriodBtns = document.querySelectorAll('.time-period-btn');
    const timePeriodContents = document.querySelectorAll('.time-period-content');

    // Show daily insights by default
    document.getElementById('daily-insights').classList.add('active');

    timePeriodBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            // Remove active class from all buttons
            timePeriodBtns.forEach(b => b.classList.remove('active'));
            // Add active class to clicked button
            this.classList.add('active');

            // Hide all content
            timePeriodContents.forEach(content => {
                content.classList.remove('active');
                content.style.display = 'none';
            });

            // Show selected content with transition
            const period = this.dataset.period;
            const selectedContent = document.getElementById(`${period}-insights`);
            selectedContent.style.display = 'block';
            // Force a reflow to enable the transition
            selectedContent.offsetHeight;
            selectedContent.classList.add('active');
        });
    });

    // Date range form validation
    const dateRangeForm = document.getElementById('dateRangeForm');
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');

    if (dateRangeForm) {
        dateRangeForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const startDate = new Date(startDateInput.value);
            const endDate = new Date(endDateInput.value);
            
            if (startDate > endDate) {
                alert('Start date cannot be after end date');
                return;
            }
            
            if (endDate > new Date()) {
                alert('End date cannot be in the future');
                return;
            }
            
            window.location.href = `mood_insights.php?start_date=${startDateInput.value}&end_date=${endDateInput.value}`;
        });
    }
});
</script>

<?php
// Include footer
require_once __DIR__ . '/../../includes/footer.php';
?> 