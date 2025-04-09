<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/pages/mood_tracking/includes/functions.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/pages/mood_tracking/includes/mood_insights.php';

// Get date range from URL parameters or default to last 7 days
$end_date = date('Y-m-d');
$start_date = date('Y-m-d', strtotime('-7 days'));

if (isset($_GET['start_date']) && isset($_GET['end_date'])) {
    $start_date = $_GET['start_date'];
    $end_date = $_GET['end_date'];
}

// Get mood insights
$insights = getMoodInsights($start_date, $end_date);
?>

<div class="container mt-4">
    <h1 class="mb-4">Mood Insights</h1>
    
    <!-- Date Range Selector -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label for="start_date" class="form-label">Start Date</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" 
                           value="<?php echo $start_date; ?>" max="<?php echo $end_date; ?>">
                </div>
                <div class="col-md-4">
                    <label for="end_date" class="form-label">End Date</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" 
                           value="<?php echo $end_date; ?>" min="<?php echo $start_date; ?>">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary">Update Analysis</button>
                </div>
            </form>
        </div>
    </div>

    <?php if ($insights['status'] === 'no_data'): ?>
        <div class="alert alert-info">
            No mood entries found for the selected period. Please add some mood entries to see insights.
        </div>
    <?php elseif ($insights['status'] === 'error'): ?>
        <div class="alert alert-danger">
            <?php echo $insights['message']; ?>
        </div>
    <?php else: ?>
        <!-- Overall Summary -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Overall Summary</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-chart-line fa-2x text-primary"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="mb-0">Average Mood</h6>
                                <h3 class="mb-0"><?php echo $insights['period']['avg_mood']; ?>/5</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-calendar-alt fa-2x text-primary"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="mb-0">Total Entries</h6>
                                <h3 class="mb-0"><?php echo $insights['period']['total_entries']; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-clock fa-2x text-primary"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="mb-0">Period</h6>
                                <h3 class="mb-0">
                                    <?php 
                                    echo date('M j', strtotime($insights['period']['start_date'])) . ' - ' . 
                                         date('M j', strtotime($insights['period']['end_date'])); 
                                    ?>
                                </h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mood Trends -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Mood Trends</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Overall Trend</h6>
                        <p><?php echo $insights['insights']['trend']; ?></p>
                        
                        <h6>Consistency</h6>
                        <p><?php echo $insights['insights']['consistency']; ?></p>
                    </div>
                    <div class="col-md-6">
                        <h6>Time Patterns</h6>
                        <ul class="list-unstyled">
                            <?php foreach ($insights['insights']['time'] as $time_insight): ?>
                                <li><i class="fas fa-clock text-primary me-2"></i><?php echo $time_insight; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tag Analysis -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Tag Analysis</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($insights['tag_analysis'] as $tag => $analysis): ?>
                        <div class="col-md-6 mb-3">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h6 class="card-title"><?php echo $tag; ?></h6>
                                    <div class="d-flex align-items-center mb-2">
                                        <div class="progress flex-grow-1" style="height: 20px;">
                                            <div class="progress-bar <?php 
                                                echo $analysis['impact'] === 'very_positive' ? 'bg-success' : 
                                                    ($analysis['impact'] === 'positive' ? 'bg-info' : 
                                                    ($analysis['impact'] === 'negative' ? 'bg-warning' : 'bg-danger')); 
                                            ?>" 
                                            role="progressbar" 
                                            style="width: <?php echo ($analysis['avg_mood'] / 5) * 100; ?>%">
                                                <?php echo $analysis['avg_mood']; ?>/5
                                            </div>
                                        </div>
                                    </div>
                                    <p class="card-text small">
                                        <?php 
                                        if ($analysis['avg_mood'] >= 4) {
                                            echo "This activity consistently improves your mood.";
                                        } elseif ($analysis['avg_mood'] >= 3) {
                                            echo "This activity generally has a positive effect on your mood.";
                                        } elseif ($analysis['avg_mood'] >= 2) {
                                            echo "This activity sometimes affects your mood negatively.";
                                        } else {
                                            echo "This activity tends to lower your mood.";
                                        }
                                        ?>
                                    </p>
                                    <?php if (!empty($analysis['common_factors'])): ?>
                                        <p class="card-text small text-muted">
                                            Common factors: 
                                            <?php echo implode(', ', array_keys($analysis['common_factors'])); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Daily Mood Chart -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Daily Mood</h5>
            </div>
            <div class="card-body">
                <canvas id="dailyMoodChart"></canvas>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($insights['status'] === 'success'): ?>
    // Daily Mood Chart
    const dailyMoodCtx = document.getElementById('dailyMoodChart').getContext('2d');
    const dailyMoodChart = new Chart(dailyMoodCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_keys($insights['daily_data'])); ?>,
            datasets: [{
                label: 'Average Mood',
                data: <?php echo json_encode(array_column($insights['daily_data'], 'avg_mood')); ?>,
                borderColor: 'rgb(75, 192, 192)',
                tension: 0.1,
                fill: false
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 5,
                    ticks: {
                        stepSize: 1
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `Mood: ${context.raw}/5`;
                        }
                    }
                }
            }
        }
    });
    <?php endif; ?>

    // Date range validation
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');

    startDateInput.addEventListener('change', function() {
        endDateInput.min = this.value;
    });

    endDateInput.addEventListener('change', function() {
        startDateInput.max = this.value;
    });
});
</script>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?> 