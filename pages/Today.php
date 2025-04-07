<?php
session_start();
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../functions/stats_functions.php';

// Initialize variables with default values
$stats = [
    'total_items' => 0,
    'favorite_items' => 0,
    'items_added_today' => 0,
    'categories_practiced' => 0
];
$assignments = [];
$recent_items = [];

try {
    // Get stats using the function
    $stats = get_daily_stats($conn);
    
    // Get assignments using the function
    $assignments = get_upcoming_assignments($conn);
    
    // Get recent items using the function
    $recent_items = get_recent_practice_items($conn);

// Get habits for today
    $habits = get_todays_habits($conn);
    
    // Get tasks for today
    $tasks = get_todays_tasks($conn);
    
    // Get upcoming exams
    $exams = get_upcoming_exams($conn);
    
    // Get today's exam reports
    $exam_reports = get_todays_exam_reports($conn);
    
} catch (Exception $e) {
    error_log("Error in Today.php: " . $e->getMessage());
    // We'll continue with default values if there's an error
}

$page_title = "Today's Overview";
require_once __DIR__ . '/../includes/header.php';
?>

<style>
:root {
    --primary-gradient: linear-gradient(135deg, #cdaf56 0%, #e6ce89 100%);
    --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    --transition-speed: 0.3s;
}

.hero-section {
    background: var(--primary-gradient);
    padding: 3rem 0;
    margin-bottom: 2rem;
    color: white;
    position: relative;
    overflow: hidden;
}

.hero-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('/assets/images/pattern.png');
    opacity: 0.1;
}

.stat-card {
    background: white;
    border-radius: 1rem;
    padding: 1.5rem;
    box-shadow: var(--card-shadow);
    transition: transform var(--transition-speed);
    height: 100%;
}

.stat-card:hover {
    transform: translateY(-5px);
}

.stat-number {
    font-size: 2.5rem;
    font-weight: bold;
    color: #cdaf56;
    margin-bottom: 0.5rem;
}

.quick-action {
    padding: 1rem;
    border-radius: 0.75rem;
    background: white;
    box-shadow: var(--card-shadow);
    transition: all var(--transition-speed);
    text-decoration: none;
    color: inherit;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.quick-action:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 8px -1px rgba(0, 0, 0, 0.1);
    color: #cdaf56;
}

.quick-action i {
    font-size: 1.5rem;
    color: #cdaf56;
}

.assignment-card {
    background: white;
    border-radius: 1rem;
    padding: 1.5rem;
    margin-bottom: 1rem;
    box-shadow: var(--card-shadow);
    transition: all var(--transition-speed);
}

.assignment-card:hover {
    transform: translateY(-3px);
}

.progress-ring {
    width: 60px;
    height: 60px;
}

.practice-item {
    padding: 1rem;
    border-left: 4px solid #cdaf56;
    background: white;
    margin-bottom: 1rem;
    border-radius: 0 0.5rem 0.5rem 0;
    transition: all var(--transition-speed);
}

.practice-item:hover {
    transform: translateX(5px);
    box-shadow: var(--card-shadow);
}

.category-badge {
    background: rgba(205, 175, 86, 0.1);
    color: #cdaf56;
    padding: 0.25rem 0.75rem;
    border-radius: 1rem;
    font-size: 0.875rem;
}

@media (max-width: 768px) {
    .stat-card {
        margin-bottom: 1rem;
    }
    
    .quick-actions {
        grid-template-columns: 1fr;
    }
}

.priority-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    display: inline-block;
}

.habits-timeline {
    position: relative;
}

.habits-timeline::before {
    content: '';
    position: absolute;
    top: 0;
    bottom: 0;
    left: 24px;
    width: 2px;
    background-color: #e9ecef;
    z-index: 0;
}

.habit-item {
    background: white;
    position: relative;
    z-index: 1;
    transition: transform 0.2s;
}

.habit-item:hover {
    transform: translateX(5px);
}

.exam-item {
    transition: transform 0.2s;
}

.exam-item:hover {
    transform: translateY(-3px);
}

.countdown-badge {
    font-size: 0.75rem;
    padding: 0.25em 0.75em;
    border-radius: 1rem;
    color: white;
}

.exam-report {
    padding: 1rem;
    border-radius: 0.5rem;
    background-color: #f8f9fa;
    transition: transform 0.2s;
}

.exam-report:hover {
    transform: translateY(-2px);
    background-color: #fff;
    box-shadow: var(--card-shadow);
}

/* Enhanced Animations and Effects */
@keyframes slideIn {
    from { transform: translateX(-20px); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}

/* Enhanced Hero Section */
.hero-section {
    background: linear-gradient(135deg, #cdaf56 0%, #e6ce89 100%);
    padding: 4rem 0;
    position: relative;
    overflow: hidden;
}

.hero-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: 
        radial-gradient(circle at 20% 20%, rgba(255,255,255,0.1) 0%, transparent 20%),
        radial-gradient(circle at 80% 80%, rgba(255,255,255,0.1) 0%, transparent 20%),
        url('/assets/images/pattern.png');
    opacity: 0.1;
    animation: pulse 8s infinite;
}

.hero-content {
    animation: fadeIn 1s ease-out;
}

/* Enhanced Stats Cards */
.stat-card {
    position: relative;
    overflow: hidden;
    border: none;
    background: linear-gradient(135deg, #fff 0%, #f8f9fa 100%);
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #cdaf56, #e6ce89);
    opacity: 0;
    transition: opacity 0.3s;
}

.stat-card:hover::before {
    opacity: 1;
}

.stat-icon {
    position: absolute;
    right: -20px;
    bottom: -20px;
    font-size: 5rem;
    opacity: 0.05;
    transform: rotate(-15deg);
    transition: all 0.3s;
}

.stat-card:hover .stat-icon {
    transform: rotate(0);
    opacity: 0.08;
}

/* Enhanced Task and Habit Items */
.task-item, .habit-item {
    position: relative;
    overflow: hidden;
}

.task-item::after, .habit-item::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, rgba(205,175,86,0.1) 0%, rgba(230,206,137,0.1) 100%);
    opacity: 0;
    transition: opacity 0.3s;
}

.task-item:hover::after, .habit-item:hover::after {
    opacity: 1;
}

/* Enhanced Progress Indicators */
.circular-progress {
    position: relative;
    width: 60px;
    height: 60px;
}

.progress-ring {
    transform: rotate(-90deg);
}

.progress-ring-circle {
    transition: stroke-dashoffset 0.3s;
    transform-origin: 50% 50%;
}

/* Enhanced Quick Actions */
.quick-action {
    position: relative;
    overflow: hidden;
    z-index: 1;
}

.quick-action::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, #cdaf56 0%, #e6ce89 100%);
    opacity: 0;
    z-index: -1;
    transition: opacity 0.3s;
}

.quick-action:hover::before {
    opacity: 0.1;
}

.quick-action:hover i {
    animation: pulse 1s infinite;
}

/* Responsive Enhancements */
@media (max-width: 768px) {
    .hero-section {
        padding: 2rem 0;
    }
    
    .stat-card {
    margin-bottom: 1rem;
}
    
    .quick-actions {
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    }
}

/* Loading Skeleton Animation */
@keyframes shimmer {
    0% { background-position: -1000px 0; }
    100% { background-position: 1000px 0; }
}

.skeleton {
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 1000px 100%;
    animation: shimmer 2s infinite;
}

/* Toast Notifications */
.feedback-message {
    position: fixed;
    bottom: -100px;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(255, 255, 255, 0.95);
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    z-index: 1000;
    transition: bottom 0.3s ease-in-out;
    font-size: 0.9rem;
    border-left: 4px solid #cdaf56;
}

.feedback-message.show {
    bottom: 20px;
}

.feedback-message.success {
    border-color: #28a745;
}

.feedback-message.error {
    border-color: #dc3545;
}
</style>

<div class="hero-section">
    <div class="container position-relative">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="display-4 mb-3">Welcome Back!</h1>
                <p class="lead mb-0">Here's your learning progress for today</p>
            </div>
            <div class="col-md-4 text-md-end">
                <div class="d-flex justify-content-md-end align-items-center">
                    <div class="me-3">
                        <div class="text-sm opacity-75">Today's Date</div>
                        <div class="h4 mb-0"><?php echo date('j M Y'); ?></div>
                    </div>
                    <div class="h1 mb-0">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container mb-5">
    <!-- Stats Section -->
    <div class="row mb-5">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-number"><?php echo (int)$stats['items_added_today']; ?></div>
                <div class="text-muted">Items Added Today</div>
                <div class="mt-3">
                    <i class="fas fa-plus-circle text-success"></i>
                    <span class="ms-2 small">New Entries</span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-number"><?php echo (int)$stats['categories_practiced']; ?></div>
                <div class="text-muted">Categories Practiced</div>
                <div class="mt-3">
                    <i class="fas fa-layer-group text-primary"></i>
                    <span class="ms-2 small">Topics Covered</span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-number"><?php echo (int)$stats['total_items']; ?></div>
                <div class="text-muted">Total Practice Items</div>
                <div class="mt-3">
                    <i class="fas fa-book text-info"></i>
                    <span class="ms-2 small">Learning Material</span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-number"><?php echo (int)$stats['favorite_items']; ?></div>
                <div class="text-muted">Favorite Items</div>
                <div class="mt-3">
                    <i class="fas fa-star text-warning"></i>
                    <span class="ms-2 small">Saved for Review</span>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Left Column -->
        <div class="col-md-8">
            <!-- Habits Section -->
            <div class="card mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-clock text-primary me-2"></i>
                        Today's Habits
                    </h5>
                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addHabitModal">
                        <i class="fas fa-plus"></i> Add Habit
                        </button>
                </div>
                                        <div class="card-body">
                    <?php if (!empty($habits)): ?>
                        <div class="habits-timeline">
                            <?php foreach ($habits as $habit): ?>
                                <div class="habit-item mb-3 p-3 border-start border-4 rounded-end" 
                                     style="border-color: <?php echo htmlspecialchars($habit['category_color']); ?>">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1">
                                                <i class="<?php echo htmlspecialchars($habit['category_icon']); ?> me-2"></i>
                                                <?php echo htmlspecialchars($habit['name']); ?>
                                            </h6>
                                            <div class="text-muted small">
                                                <i class="far fa-clock me-1"></i>
                                                <?php echo date('g:i A', strtotime($habit['target_time'])); ?>
                                            </div>
                                                        </div>
                                        <div>
                                            <?php if ($habit['today_status'] == 'completed'): ?>
                                                <span class="badge bg-success">Completed</span>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-outline-success complete-habit" 
                                                        data-habit-id="<?php echo $habit['id']; ?>">
                                                    Complete
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                                            </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-tasks fa-2x text-muted mb-3"></i>
                            <p class="text-muted mb-0">No habits scheduled for today</p>
                                                        </div>
                    <?php endif; ?>
                                                        </div>
                                                    </div>

            <!-- Tasks Section -->
            <div class="card mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-tasks text-warning me-2"></i>
                        Today's Tasks
                    </h5>
                    <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#addTaskModal">
                        <i class="fas fa-plus"></i> Add Task
                    </button>
                </div>
                <div class="card-body">
                    <?php if (!empty($tasks)): ?>
                        <?php foreach ($tasks as $task): ?>
                            <div class="task-item mb-3 p-3 border rounded shadow-sm">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="d-flex align-items-center mb-2">
                                            <span class="priority-dot me-2" 
                                                  style="background-color: <?php 
                                                    echo $task['priority'] == 'high' ? '#dc3545' : 
                                                        ($task['priority'] == 'medium' ? '#ffc107' : '#28a745'); 
                                                    ?>">
                                            </span>
                                            <h6 class="mb-0"><?php echo htmlspecialchars($task['title']); ?></h6>
                                        </div>
                                        <?php if ($task['description']): ?>
                                            <p class="text-muted small mb-2"><?php echo htmlspecialchars($task['description']); ?></p>
                                            <?php endif; ?>
                                        <div class="d-flex align-items-center">
                                            <span class="badge" style="background-color: <?php echo htmlspecialchars($task['category_color']); ?>">
                                                <i class="<?php echo htmlspecialchars($task['category_icon']); ?> me-1"></i>
                                                <?php echo htmlspecialchars($task['category_name']); ?>
                                            </span>
                                            <span class="text-muted small ms-2">
                                                <i class="far fa-clock me-1"></i>
                                                <?php echo date('g:i A', strtotime($task['due_date'])); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="ms-3">
                                        <button class="btn btn-sm btn-success complete-task" data-task-id="<?php echo $task['id']; ?>">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-check-circle fa-2x text-muted mb-3"></i>
                            <p class="text-muted mb-0">No tasks for today</p>
                                            </div>
                    <?php endif; ?>
                                                    </div>
                                                </div>

            <!-- Quick Actions -->
            <div class="col-md-4 mb-4">
                <h2 class="h4 mb-4">Quick Actions</h2>
                <div class="d-grid gap-3">
                    <a href="/pages/EnglishPractice/practice.php" class="quick-action">
                        <i class="fas fa-play-circle"></i>
                        <div>
                            <div class="fw-bold">Start Practice</div>
                            <div class="small text-muted">Review your flashcards</div>
                        </div>
                    </a>
                    <a href="/pages/EnglishPractice/daily_entry.php" class="quick-action">
                        <i class="fas fa-plus-circle"></i>
                        <div>
                            <div class="fw-bold">Add New Entry</div>
                            <div class="small text-muted">Create practice items</div>
                        </div>
                    </a>
                    <a href="/pages/EnglishPractice/review.php?favorites=1" class="quick-action">
                        <i class="fas fa-star"></i>
                        <div>
                            <div class="fw-bold">Review Favorites</div>
                            <div class="small text-muted">Practice saved items</div>
                                                        </div>
                    </a>
                                                    </div>
            </div>

            <!-- Upcoming Assignments -->
            <div class="col-md-4 mb-4">
                <h2 class="h4 mb-4">Upcoming Assignments</h2>
                <?php if (!empty($assignments)): ?>
                    <?php foreach ($assignments as $assignment): 
                        $progress = $assignment['total_criteria'] > 0 ? 
                            ($assignment['completed_criteria'] / $assignment['total_criteria']) * 100 : 0;
                        $days_left = (strtotime($assignment['due_date']) - time()) / (60 * 60 * 24);
                    ?>
                        <div class="assignment-card">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h3 class="h6 mb-1"><?php echo htmlspecialchars($assignment['title']); ?></h3>
                                    <div class="text-muted small">
                                        Due in <?php echo ceil($days_left); ?> days
                                                </div>
                                            </div>
                                <div class="ms-3">
                                    <div class="progress" style="width: 60px; height: 60px;">
                                        <div class="progress-bar bg-success" role="progressbar" 
                                             style="width: <?php echo $progress; ?>%" 
                                             aria-valuenow="<?php echo $progress; ?>" 
                                             aria-valuemin="0" 
                                             aria-valuemax="100">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="small">
                                    <span class="text-success">
                                        <?php echo $assignment['completed_criteria']; ?>/<?php echo $assignment['total_criteria']; ?>
                                    </span> criteria completed
                                </div>
                                <a href="#" class="btn btn-sm btn-outline-primary">Continue</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-check-circle fa-2x mb-3"></i>
                        <p class="mb-0">No upcoming assignments!</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Recent Practice Items -->
            <div class="col-md-4 mb-4">
                <h2 class="h4 mb-4">Recent Practice Items</h2>
                <?php if (!empty($recent_items)): ?>
                    <?php foreach ($recent_items as $item): ?>
                        <div class="practice-item">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h3 class="h6 mb-1"><?php echo htmlspecialchars($item['item_title']); ?></h3>
                                <button class="btn btn-link p-0 toggle-favorite" data-item-id="<?php echo $item['id']; ?>">
                                    <i class="<?php echo $item['is_favorite'] ? 'fas' : 'far'; ?> fa-star text-warning"></i>
                        </button>
                            </div>
                            <span class="category-badge">
                                <?php echo htmlspecialchars($item['category_name']); ?>
                                                        </span>
                            <div class="mt-2 small text-muted">
                                Added <?php echo date('j M', strtotime($item['created_at'])); ?>
                                                </div>
                                            </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-book fa-2x mb-3"></i>
                        <p class="mb-0">No practice items yet!</p>
                                    </div>
                            <?php endif; ?>
                        </div>
                    </div>

        <!-- Right Column -->
        <div class="col-md-4">
            <!-- Exam Countdown -->
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="fas fa-graduation-cap text-info me-2"></i>
                        Upcoming Exams
                    </h5>
                </div>
                                        <div class="card-body">
                    <?php if (!empty($exams)): ?>
                        <?php foreach ($exams as $exam): ?>
                            <div class="exam-item mb-3 p-3 border rounded">
                                <h6 class="mb-2"><?php echo htmlspecialchars($exam['title']); ?></h6>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="badge" style="background-color: <?php echo htmlspecialchars($exam['subject_color']); ?>">
                                        <?php echo htmlspecialchars($exam['subject_name']); ?>
                                    </span>
                                    <span class="countdown-badge <?php 
                                        echo $exam['days_remaining'] <= 7 ? 'bg-danger' : 
                                            ($exam['days_remaining'] <= 14 ? 'bg-warning' : 'bg-success'); 
                                    ?>">
                                        <?php echo $exam['days_remaining']; ?> days left
                                                        </span>
                                                </div>
                                <div class="progress" style="height: 4px;">
                                    <div class="progress-bar bg-info" role="progressbar" 
                                         style="width: <?php echo (30 - $exam['days_remaining']) / 30 * 100; ?>%">
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                            <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-book fa-2x text-muted mb-3"></i>
                            <p class="text-muted mb-0">No upcoming exams</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Today's Exam Reports -->
            <?php if (!empty($exam_reports)): ?>
                <div class="card mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-chart-bar text-success me-2"></i>
                            Today's Exam Results
                        </h5>
                    </div>
                                        <div class="card-body">
                        <?php foreach ($exam_reports as $report): ?>
                            <div class="exam-report mb-3">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($report['exam_title']); ?></h6>
                                                    <span class="badge" style="background-color: <?php echo htmlspecialchars($report['subject_color']); ?>">
                                                        <?php echo htmlspecialchars($report['subject_name']); ?>
                                                    </span>
                                                </div>
                                                    <span class="badge bg-<?php echo $report['score'] >= 70 ? 'success' : ($report['score'] >= 50 ? 'warning' : 'danger'); ?>">
                                        <?php echo $report['score']; ?>%
                                                    </span>
                                            </div>
                                            <?php if ($report['notes']): ?>
                                    <p class="text-muted small mt-2 mb-0"><?php echo htmlspecialchars($report['notes']); ?></p>
                                            <?php endif; ?>
                                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Enhanced Stats Animation
    function animateValue(element, start, end, duration) {
        let startTimestamp = null;
        const step = (timestamp) => {
            if (!startTimestamp) startTimestamp = timestamp;
            const progress = Math.min((timestamp - startTimestamp) / duration, 1);
            const value = Math.floor(progress * (end - start) + start);
            element.textContent = value;
            if (progress < 1) {
                window.requestAnimationFrame(step);
            }
        };
        window.requestAnimationFrame(step);
    }

    // Circular Progress Animation
    function createCircularProgress(percentage, element) {
        const radius = 24;
        const circumference = radius * 2 * Math.PI;
        const html = `
            <svg class="progress-ring" width="60" height="60">
                <circle
                    class="progress-ring-circle-bg"
                    stroke="#e9ecef"
                    stroke-width="4"
                    fill="transparent"
                    r="${radius}"
                    cx="30"
                    cy="30"
                />
                <circle
                    class="progress-ring-circle"
                    stroke="#cdaf56"
                    stroke-width="4"
                    fill="transparent"
                    r="${radius}"
                    cx="30"
                    cy="30"
                    style="stroke-dasharray: ${circumference} ${circumference};
                           stroke-dashoffset: ${circumference - (percentage / 100) * circumference}"
                />
                <text x="30" y="30" text-anchor="middle" dy=".3em" fill="#cdaf56">
                    ${percentage}%
                </text>
            </svg>
        `;
        element.innerHTML = html;
    }

    // Subtle Feedback Message
    function showFeedback(message, type = 'success') {
        // Remove any existing feedback messages
        const existingMessages = document.querySelectorAll('.feedback-message');
        existingMessages.forEach(msg => msg.remove());

        // Create new feedback message
        const feedback = document.createElement('div');
        feedback.className = `feedback-message ${type}`;
        feedback.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="fas fa-${type === 'success' ? 'check' : 'exclamation'} me-2"></i>
                <span>${message}</span>
            </div>
        `;
        document.body.appendChild(feedback);

        // Show the message
        setTimeout(() => feedback.classList.add('show'), 100);

        // Hide and remove the message
        setTimeout(() => {
            feedback.classList.remove('show');
            setTimeout(() => feedback.remove(), 300);
        }, 2000);
    }

    // Enhanced Task and Habit Completion
    function handleCompletion(endpoint, id, type) {
        const button = event.currentTarget;
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        
        fetch(endpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `${type}_id=${id}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                button.innerHTML = '<i class="fas fa-check"></i>';
                showFeedback(`${type.charAt(0).toUpperCase() + type.slice(1)} completed`);
                setTimeout(() => location.reload(), 1000);
            } else {
                throw new Error(data.message || 'Something went wrong');
            }
        })
        .catch(error => {
            button.disabled = false;
            button.innerHTML = '<i class="fas fa-check"></i>';
            showFeedback(error.message, 'error');
        });
    }

    // Initialize all progress rings
    document.querySelectorAll('[data-progress]').forEach(el => {
        createCircularProgress(parseInt(el.dataset.progress), el);
    });

    // Initialize tooltips with enhanced options
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltipTriggerList.forEach(el => {
        new bootstrap.Tooltip(el, {
            animation: true,
            delay: { show: 100, hide: 100 },
            html: true
        });
    });

    // Enhanced favorite toggle with animation
    document.querySelectorAll('.toggle-favorite').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const icon = this.querySelector('i');
            icon.style.transform = 'scale(1.2)';
            
            const itemId = this.dataset.itemId;
            
            fetch('EnglishPractice/toggle_favorite.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `item_id=${itemId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    icon.classList.toggle('far');
                    icon.classList.toggle('fas');
                    showFeedback(data.isFavorite ? 'Added to favorites' : 'Removed from favorites');
                }
                icon.style.transform = 'scale(1)';
            })
            .catch(error => {
                showFeedback('Could not update favorite status', 'error');
                icon.style.transform = 'scale(1)';
            });
        });
    });
});
</script>

<!-- Add loading state templates -->
<template id="loading-task">
    <div class="task-item mb-3 p-3 border rounded shadow-sm skeleton">
        <div class="d-flex justify-content-between">
            <div class="w-75">
                <div class="h6 mb-2" style="width: 60%; height: 20px;"></div>
                <div style="width: 40%; height: 16px;"></div>
            </div>
            <div style="width: 40px; height: 40px;"></div>
        </div>
    </div>
</template>

<template id="loading-habit">
    <div class="habit-item mb-3 p-3 border rounded skeleton">
        <div class="d-flex justify-content-between">
            <div class="w-75">
                <div class="h6 mb-2" style="width: 70%; height: 20px;"></div>
                <div style="width: 30%; height: 16px;"></div>
            </div>
            <div style="width: 60px; height: 30px;"></div>
        </div>
    </div>
</template>

<?php require_once __DIR__ . '/../includes/footer.php'; ?> 