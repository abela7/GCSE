<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/mood_analysis.php';

// Set page title
$page_title = "Mood Insights";

// Include header
require_once __DIR__ . '/../../includes/header.php';

// Initialize date range
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Initialize mood analyzer
$analyzer = new MoodAnalyzer($conn);
$analysis = $analyzer->analyzeMoodPatterns($start_date, $end_date);
?>

<style>
.insight-card {
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    margin-bottom: 1.5rem;
    border: none;
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

.time-period-selector {
    display: flex;
    gap: 1rem;
    margin-bottom: 2rem;
}

.time-period-btn {
    flex: 1;
    padding: 0.75rem;
    border: 2px solid var(--accent-color);
    border-radius: 8px;
    background: none;
    color: var(--accent-color);
    font-weight: 500;
    transition: all 0.3s ease;
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
    background: linear-gradient(135deg, var(--accent-color-light), var(--accent-color));
    color: white;
    padding: 1.5rem;
    border-radius: 12px;
    margin-bottom: 1.5rem;
}

.pattern-title {
    font-size: 1.2rem;
    font-weight: 600;
    margin-bottom: 1rem;
}

.pattern-description {
    font-size: 1.1rem;
    line-height: 1.6;
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

@media (max-width: 767.98px) {
    .time-period-selector {
        flex-direction: column;
    }
    
    .mood-emoji {
        font-size: 2rem;
    }
    
    .insight-description {
        font-size: 1rem;
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

    <!-- Date Range Selector -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="insight-card">
                <div class="card-body">
                    <form id="dateRangeForm" class="row g-3">
                        <div class="col-md-4">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" 
                                   value="<?php echo $start_date; ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" 
                                   value="<?php echo $end_date; ?>">
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

    <!-- Time Period Selector -->
    <div class="time-period-selector">
        <button class="time-period-btn active" data-period="daily">Daily Insights</button>
        <button class="time-period-btn" data-period="weekly">Weekly Insights</button>
        <button class="time-period-btn" data-period="monthly">Monthly Insights</button>
    </div>

    <!-- Daily Insights -->
    <div class="time-period-content" id="daily-insights">
        <?php if (!empty($analysis['insights']['daily'])): ?>
            <div class="row">
                <div class="col-md-6">
                    <div class="insight-card">
                        <div class="card-body">
                            <div class="mood-emoji">
                                <?php 
                                $mood_level = $analysis['insights']['daily']['mood'] ?? null;
                                $mood_scale = $analyzer->getMoodScale();
                                if ($mood_level !== null && isset($mood_scale[$mood_level])) {
                                    echo $mood_scale[$mood_level]['emoji'];
                                } else {
                                    echo '❓'; // Default emoji for unknown mood
                                }
                                ?>
                            </div>
                            <h3 class="h4 mb-3">Today's Mood: <?php echo $analysis['insights']['daily']['mood']; ?></h3>
                            <p class="insight-description">
                                <?php echo $analysis['insights']['daily']['description']; ?>
                            </p>
                            <div class="suggestion-box">
                                <h4 class="h5 mb-3">Suggestions</h4>
                                <p><?php echo $analysis['insights']['daily']['suggestions']; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="insight-card">
                        <div class="card-body">
                            <h3 class="h4 mb-3">Pattern Analysis</h3>
                            <?php if (!empty($analysis['insights']['patterns'])): ?>
                                <?php foreach ($analysis['insights']['patterns'] as $pattern): ?>
                                    <div class="pattern-card">
                                        <div class="pattern-title"><?php echo $pattern['title']; ?></div>
                                        <div class="pattern-description"><?php echo $pattern['description']; ?></div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-chart-bar fa-4x text-muted mb-3"></i>
                <h4>No mood data available for today</h4>
                <p class="text-muted">Start tracking your mood to see insights</p>
                <a href="entry.php" class="btn btn-accent">
                    <i class="fas fa-plus me-1"></i>Create Mood Entry
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Weekly Insights -->
    <div class="time-period-content" id="weekly-insights" style="display: none;">
        <?php if (!empty($analysis['insights']['weekly'])): ?>
            <div class="row">
                <div class="col-md-6">
                    <div class="insight-card">
                        <div class="card-body">
                            <div class="mood-emoji">
                                <?php 
                                $mood_level = $analysis['insights']['weekly']['mood'] ?? null;
                                $mood_scale = $analyzer->getMoodScale();
                                if ($mood_level !== null && isset($mood_scale[$mood_level])) {
                                    echo $mood_scale[$mood_level]['emoji'];
                                } else {
                                    echo '❓'; // Default emoji for unknown mood
                                }
                                ?>
                            </div>
                            <h3 class="h4 mb-3">This Week's Mood: <?php echo $analysis['insights']['weekly']['mood']; ?></h3>
                            <p class="insight-description">
                                <?php echo $analysis['insights']['weekly']['description']; ?>
                            </p>
                            <div class="suggestion-box">
                                <h4 class="h5 mb-3">Suggestions</h4>
                                <p><?php echo $analysis['insights']['weekly']['suggestions']; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="insight-card">
                        <div class="card-body">
                            <h3 class="h4 mb-3">Weekly Patterns</h3>
                            <?php if (!empty($analysis['insights']['patterns'])): ?>
                                <?php foreach ($analysis['insights']['patterns'] as $pattern): ?>
                                    <div class="pattern-card">
                                        <div class="pattern-title"><?php echo $pattern['title']; ?></div>
                                        <div class="pattern-description"><?php echo $pattern['description']; ?></div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-chart-bar fa-4x text-muted mb-3"></i>
                <h4>No mood data available for this week</h4>
                <p class="text-muted">Start tracking your mood to see insights</p>
                <a href="entry.php" class="btn btn-accent">
                    <i class="fas fa-plus me-1"></i>Create Mood Entry
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Monthly Insights -->
    <div class="time-period-content" id="monthly-insights" style="display: none;">
        <?php if (!empty($analysis['insights']['monthly'])): ?>
            <div class="row">
                <div class="col-md-6">
                    <div class="insight-card">
                        <div class="card-body">
                            <div class="mood-emoji">
                                <?php 
                                $mood_level = $analysis['insights']['monthly']['mood'] ?? null;
                                $mood_scale = $analyzer->getMoodScale();
                                if ($mood_level !== null && isset($mood_scale[$mood_level])) {
                                    echo $mood_scale[$mood_level]['emoji'];
                                } else {
                                    echo '❓'; // Default emoji for unknown mood
                                }
                                ?>
                            </div>
                            <h3 class="h4 mb-3">This Month's Mood: <?php echo $analysis['insights']['monthly']['mood']; ?></h3>
                            <p class="insight-description">
                                <?php echo $analysis['insights']['monthly']['description']; ?>
                            </p>
                            <div class="suggestion-box">
                                <h4 class="h5 mb-3">Suggestions</h4>
                                <p><?php echo $analysis['insights']['monthly']['suggestions']; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="insight-card">
                        <div class="card-body">
                            <h3 class="h4 mb-3">Monthly Patterns</h3>
                            <?php if (!empty($analysis['insights']['patterns'])): ?>
                                <?php foreach ($analysis['insights']['patterns'] as $pattern): ?>
                                    <div class="pattern-card">
                                        <div class="pattern-title"><?php echo $pattern['title']; ?></div>
                                        <div class="pattern-description"><?php echo $pattern['description']; ?></div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-chart-bar fa-4x text-muted mb-3"></i>
                <h4>No mood data available for this month</h4>
                <p class="text-muted">Start tracking your mood to see insights</p>
                <a href="entry.php" class="btn btn-accent">
                    <i class="fas fa-plus me-1"></i>Create Mood Entry
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Improvement Areas -->
    <?php if (!empty($analysis['insights']['patterns']['improvement_areas'])): ?>
        <div class="row mt-4">
            <div class="col-12">
                <div class="improvement-card">
                    <h3 class="improvement-title">Areas for Improvement</h3>
                    <ul class="improvement-list">
                        <?php foreach ($analysis['insights']['patterns']['improvement_areas']['areas'] as $area): ?>
                            <li class="improvement-item">
                                <i class="fas fa-lightbulb"></i>
                                <span><?php echo $area; ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Time period selector
    const timePeriodBtns = document.querySelectorAll('.time-period-btn');
    const timePeriodContents = document.querySelectorAll('.time-period-content');

    timePeriodBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            // Remove active class from all buttons
            timePeriodBtns.forEach(b => b.classList.remove('active'));
            // Add active class to clicked button
            this.classList.add('active');

            // Hide all content
            timePeriodContents.forEach(content => content.style.display = 'none');
            // Show selected content
            const period = this.dataset.period;
            document.getElementById(`${period}-insights`).style.display = 'block';
        });
    });

    // Date range form
    const dateRangeForm = document.getElementById('dateRangeForm');
    dateRangeForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const startDate = document.getElementById('start_date').value;
        const endDate = document.getElementById('end_date').value;
        
        if (startDate && endDate) {
            window.location.href = `mood_insights.php?start_date=${startDate}&end_date=${endDate}`;
        }
    });
});
</script>

<?php
// Include footer
require_once __DIR__ . '/../../includes/footer.php';
?> 