<?php
session_start();
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../functions/stats_functions.php';

// Initialize variables with default values
$today_tasks = [];
$today_habits = [];
$math_stats = [
    'total_topics' => 134,
    'completed_topics' => 0,
    'progress_percentage' => 0
];
$english_stats = [
    'total_topics' => 74,
    'completed_topics' => 0,
    'progress_percentage' => 0
];
$upcoming_exams = [];
$daily_words = [];
$assignments = [];

try {
    // Fetch today's tasks and habits
    $today_tasks = get_todays_tasks($conn);
    $today_habits = get_todays_habits($conn);
    
    // Fetch subject progress
    $stmt = $conn->prepare("
        SELECT subject_id, COUNT(DISTINCT topic_id) as completed_topics
        FROM topic_completion
        WHERE user_id = ? AND completion_date IS NOT NULL
        GROUP BY subject_id
    ");
    $user_id = $_SESSION['user_id'];
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        if ($row['subject_id'] == 1) { // Math
            $math_stats['completed_topics'] = $row['completed_topics'];
            $math_stats['progress_percentage'] = round(($row['completed_topics'] / $math_stats['total_topics']) * 100);
        } else if ($row['subject_id'] == 2) { // English
            $english_stats['completed_topics'] = $row['completed_topics'];
            $english_stats['progress_percentage'] = round(($row['completed_topics'] / $english_stats['total_topics']) * 100);
        }
    }
    
    // Fetch upcoming exams
    $stmt = $conn->prepare("
        SELECT e.*, s.name as subject_name, s.color as subject_color,
               DATEDIFF(exam_date, CURRENT_DATE) as days_remaining
        FROM exams e
        JOIN subjects s ON e.subject_id = s.id
        WHERE e.user_id = ? AND exam_date >= CURRENT_DATE
        ORDER BY exam_date ASC
        LIMIT 5
    ");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $upcoming_exams = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Fetch daily words
    $stmt = $conn->prepare("
        SELECT w.*, 
               CASE WHEN wp.word_id IS NOT NULL THEN 1 ELSE 0 END as is_practiced
        FROM daily_words w
        LEFT JOIN word_practice wp ON w.id = wp.word_id AND wp.practice_date = CURRENT_DATE
        WHERE w.assigned_date = CURRENT_DATE
        LIMIT 5
    ");
    $stmt->execute();
    $daily_words = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Fetch assignments
    $assignments = get_upcoming_assignments($conn);
    
} catch (Exception $e) {
    error_log("Error in dashboard.php: " . $e->getMessage());
}

$page_title = "Dashboard";
require_once __DIR__ . '/../includes/header.php';
?>

<style>
:root {
    --primary-color: #cdaf56;
    --primary-light: #e6ce89;
    --primary-dark: #b49339;
    --accent-gradient: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
    --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    --transition-speed: 0.3s;
}

/* Hero Section */
.dashboard-hero {
    background: var(--accent-gradient);
    padding: 2rem 0;
    margin-bottom: 2rem;
    position: relative;
    overflow: hidden;
}

.dashboard-hero::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: 
        radial-gradient(circle at 20% 20%, rgba(255,255,255,0.1) 0%, transparent 20%),
        radial-gradient(circle at 80% 80%, rgba(255,255,255,0.1) 0%, transparent 20%);
    opacity: 0.1;
}

/* Subject Progress Cards */
.subject-card {
    background: white;
    border-radius: 1rem;
    padding: 1.5rem;
    margin-bottom: 1rem;
    box-shadow: var(--card-shadow);
    transition: transform var(--transition-speed);
}

.subject-card:hover {
    transform: translateY(-5px);
}

.progress-circle {
    width: 80px;
    height: 80px;
    position: relative;
}

.progress-circle svg {
    transform: rotate(-90deg);
}

.progress-circle-bg {
    stroke: #f0f0f0;
}

.progress-circle-value {
    stroke: var(--primary-color);
    transition: stroke-dashoffset var(--transition-speed);
}

/* Task Items */
.task-item {
    background: white;
    border-radius: 0.5rem;
    padding: 1rem;
    margin-bottom: 0.75rem;
    border-left: 4px solid var(--primary-color);
    box-shadow: var(--card-shadow);
    transition: all var(--transition-speed);
}

.task-item:hover {
    transform: translateX(5px);
}

.task-item.completed {
    opacity: 0.7;
    border-left-color: #28a745;
}

/* Word of the Day */
.word-card {
    background: white;
    border-radius: 0.75rem;
    padding: 1.25rem;
    margin-bottom: 1rem;
    box-shadow: var(--card-shadow);
    transition: all var(--transition-speed);
}

.word-card:hover {
    transform: translateY(-3px);
}

.word-card.practiced {
    border: 1px solid var(--primary-color);
}

/* Exam Countdown */
.exam-countdown {
    background: white;
    border-radius: 0.75rem;
    padding: 1rem;
    margin-bottom: 1rem;
    box-shadow: var(--card-shadow);
}

.countdown-ring {
    width: 60px;
    height: 60px;
    position: relative;
}

/* Mobile Optimizations */
@media (max-width: 768px) {
    .dashboard-hero {
        padding: 1.5rem 0;
    }
    
    .subject-card {
        margin-bottom: 1rem;
    }
    
    .task-item {
        margin-bottom: 0.5rem;
    }
}

/* Animations */
@keyframes slideIn {
    from { transform: translateX(-20px); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.animate-slide-in {
    animation: slideIn 0.5s ease-out forwards;
}

.animate-fade-in {
    animation: fadeIn 0.5s ease-out forwards;
}

/* Accordion Styles */
.dashboard-accordion .accordion-button {
    background: white;
    border: none;
    box-shadow: none;
    padding: 1rem 1.5rem;
}

.dashboard-accordion .accordion-button:not(.collapsed) {
    background: var(--accent-gradient);
    color: white;
}

.dashboard-accordion .accordion-button:focus {
    box-shadow: none;
    border-color: var(--primary-color);
}

.dashboard-accordion .accordion-body {
    background: #fff;
    padding: 1.5rem;
}

/* Word Card Accordion */
.word-accordion .accordion-button {
    padding: 1rem;
    background: white;
    border: none;
}

.word-accordion .accordion-button:not(.collapsed) {
    background: var(--primary-light);
    color: #000;
}

.word-example {
    border-left: 3px solid var(--primary-color);
    padding-left: 1rem;
    margin-top: 0.5rem;
}

/* Full Width Cards */
.dashboard-section {
    width: 100%;
    margin-bottom: 1.5rem;
}

/* Add Task Modal */
.task-modal .modal-content {
    border-radius: 1rem;
    border: none;
}

.task-modal .modal-header {
    background: var(--accent-gradient);
    color: white;
    border-radius: 1rem 1rem 0 0;
}
</style>

<!-- Dashboard Hero -->
<div class="dashboard-hero text-white">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="display-4 mb-2">Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
                <p class="lead mb-0">Here's your learning progress for today</p>
            </div>
            <div class="col-md-4 text-md-end">
                <div class="d-flex justify-content-md-end align-items-center">
                    <div class="me-3">
                        <div class="text-sm opacity-75">Today's Date</div>
                        <div class="h4 mb-0"><?php echo date('j M Y'); ?></div>
                    </div>
                    <i class="fas fa-calendar-day fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container mb-5">
    <div class="dashboard-accordion" id="dashboardAccordion">
        <!-- Tasks Section -->
        <div class="dashboard-section">
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#taskSection">
                        <i class="fas fa-tasks text-primary me-2"></i>
                        Today's Tasks
                    </button>
                </h2>
                <div id="taskSection" class="accordion-collapse collapse show" data-bs-parent="#dashboardAccordion">
                    <div class="accordion-body">
                        <div class="d-flex justify-content-end mb-3">
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTaskModal">
                                <i class="fas fa-plus me-2"></i> Add New Task
                            </button>
                        </div>
                        <?php if (!empty($today_tasks)): ?>
                            <?php foreach ($today_tasks as $task): ?>
                                <div class="task-item <?php echo $task['status'] === 'completed' ? 'completed' : ''; ?>">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($task['title']); ?></h6>
                                            <div class="text-muted small">
                                                <i class="far fa-clock me-1"></i>
                                                <?php echo date('g:i A', strtotime($task['due_time'])); ?>
                                            </div>
                                        </div>
                                        <?php if ($task['status'] !== 'completed'): ?>
                                            <button class="btn btn-sm btn-success complete-task" 
                                                    data-task-id="<?php echo $task['id']; ?>">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        <?php else: ?>
                                            <span class="badge bg-success">
                                                <i class="fas fa-check"></i> Done
                                            </span>
                                        <?php endif; ?>
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
            </div>
        </div>

        <!-- Habits Section -->
        <div class="dashboard-section">
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#habitSection">
                        <i class="fas fa-clock text-primary me-2"></i>
                        Daily Habits
                    </button>
                </h2>
                <div id="habitSection" class="accordion-collapse collapse" data-bs-parent="#dashboardAccordion">
                    <div class="accordion-body">
                        <div class="d-flex justify-content-end mb-3">
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addHabitModal">
                                <i class="fas fa-plus me-2"></i> Add New Habit
                            </button>
                        </div>
                        <?php if (!empty($today_habits)): ?>
                            <?php foreach ($today_habits as $habit): ?>
                                <div class="habit-item <?php echo $habit['today_status'] === 'completed' ? 'completed' : ''; ?>">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($habit['name']); ?></h6>
                                            <div class="text-muted small">
                                                <i class="far fa-clock me-1"></i>
                                                <?php echo date('g:i A', strtotime($habit['target_time'])); ?>
                                            </div>
                                        </div>
                                        <?php if ($habit['today_status'] !== 'completed'): ?>
                                            <button class="btn btn-sm btn-success complete-habit" 
                                                    data-habit-id="<?php echo $habit['id']; ?>">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        <?php else: ?>
                                            <span class="badge bg-success">
                                                <i class="fas fa-check"></i> Done
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-clock fa-2x text-muted mb-3"></i>
                                <p class="text-muted mb-0">No habits scheduled for today</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Subject Progress Section -->
        <div class="dashboard-section">
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#progressSection">
                        <i class="fas fa-chart-line text-primary me-2"></i>
                        Subject Progress
                    </button>
                </h2>
                <div id="progressSection" class="accordion-collapse collapse" data-bs-parent="#dashboardAccordion">
                    <div class="accordion-body">
                        <div class="subject-card mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <h5 class="mb-1">Mathematics</h5>
                                    <p class="text-muted mb-0">
                                        <?php echo $math_stats['completed_topics']; ?> of <?php echo $math_stats['total_topics']; ?> topics completed
                                    </p>
                                </div>
                                <div class="progress-circle" data-progress="<?php echo $math_stats['progress_percentage']; ?>"></div>
                            </div>
                        </div>
                        <div class="subject-card">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <h5 class="mb-1">English</h5>
                                    <p class="text-muted mb-0">
                                        <?php echo $english_stats['completed_topics']; ?> of <?php echo $english_stats['total_topics']; ?> topics completed
                                    </p>
                                </div>
                                <div class="progress-circle" data-progress="<?php echo $english_stats['progress_percentage']; ?>"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Upcoming Exams Section -->
        <div class="dashboard-section">
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#examSection">
                        <i class="fas fa-graduation-cap text-primary me-2"></i>
                        Upcoming Exams
                    </button>
                </h2>
                <div id="examSection" class="accordion-collapse collapse" data-bs-parent="#dashboardAccordion">
                    <div class="accordion-body">
                        <?php if (!empty($upcoming_exams)): ?>
                            <?php foreach ($upcoming_exams as $exam): ?>
                                <div class="exam-countdown mb-3">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($exam['title']); ?></h6>
                                            <span class="badge" 
                                                  style="background-color: <?php echo htmlspecialchars($exam['subject_color']); ?>">
                                                <?php echo htmlspecialchars($exam['subject_name']); ?>
                                            </span>
                                            <div class="mt-2">
                                                <div class="countdown-timer" 
                                                     data-exam-date="<?php echo $exam['exam_date']; ?>"
                                                     data-exam-id="<?php echo $exam['id']; ?>">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge <?php 
                                                echo $exam['days_remaining'] <= 7 ? 'bg-danger' : 
                                                    ($exam['days_remaining'] <= 14 ? 'bg-warning' : 'bg-success'); 
                                            ?>">
                                                <?php echo $exam['days_remaining']; ?> days left
                                            </span>
                                            <div class="text-muted small mt-1">
                                                <?php echo date('j M Y', strtotime($exam['exam_date'])); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-calendar-check fa-2x text-muted mb-3"></i>
                                <p class="text-muted mb-0">No upcoming exams</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Words of the Day Section -->
        <div class="dashboard-section">
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#wordsSection">
                        <i class="fas fa-book-reader text-primary me-2"></i>
                        Words of the Day
                    </button>
                </h2>
                <div id="wordsSection" class="accordion-collapse collapse" data-bs-parent="#dashboardAccordion">
                    <div class="accordion-body">
                        <?php if (!empty($daily_words)): ?>
                            <div class="word-accordion" id="wordAccordion">
                                <?php foreach ($daily_words as $index => $word): ?>
                                    <div class="accordion-item">
                                        <h2 class="accordion-header">
                                            <button class="accordion-button <?php echo $index !== 0 ? 'collapsed' : ''; ?>" 
                                                    type="button" 
                                                    data-bs-toggle="collapse" 
                                                    data-bs-target="#word<?php echo $word['id']; ?>">
                                                <div class="d-flex justify-content-between align-items-center w-100">
                                                    <span><?php echo htmlspecialchars($word['word']); ?></span>
                                                    <?php if ($word['is_practiced']): ?>
                                                        <span class="badge bg-success ms-2">
                                                            <i class="fas fa-check"></i> Practiced
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </button>
                                        </h2>
                                        <div id="word<?php echo $word['id']; ?>" 
                                             class="accordion-collapse collapse <?php echo $index === 0 ? 'show' : ''; ?>" 
                                             data-bs-parent="#wordAccordion">
                                            <div class="accordion-body">
                                                <p class="mb-2"><?php echo htmlspecialchars($word['definition']); ?></p>
                                                <?php if ($word['example']): ?>
                                                    <div class="word-example">
                                                        <i class="fas fa-quote-left text-muted me-2"></i>
                                                        <?php echo htmlspecialchars($word['example']); ?>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if (!$word['is_practiced']): ?>
                                                    <div class="mt-3">
                                                        <button class="btn btn-sm btn-outline-primary mark-practiced" 
                                                                data-word-id="<?php echo $word['id']; ?>">
                                                            Mark as Practiced
                                                        </button>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-book fa-2x text-muted mb-3"></i>
                                <p class="text-muted mb-0">No words for today</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Task Modal -->
<div class="modal fade task-modal" id="addTaskModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Task</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addTaskForm" action="/includes/add_task.php" method="POST">
                    <div class="mb-3">
                        <label for="taskTitle" class="form-label">Task Title</label>
                        <input type="text" class="form-control" id="taskTitle" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label for="taskDueTime" class="form-label">Due Time</label>
                        <input type="time" class="form-control" id="taskDueTime" name="due_time" required>
                    </div>
                    <div class="mb-3">
                        <label for="taskPriority" class="form-label">Priority</label>
                        <select class="form-select" id="taskPriority" name="priority">
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>
                    <div class="text-end">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Task</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Add Habit Modal -->
<div class="modal fade task-modal" id="addHabitModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Habit</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addHabitForm" action="/includes/add_habit.php" method="POST">
                    <div class="mb-3">
                        <label for="habitName" class="form-label">Habit Name</label>
                        <input type="text" class="form-control" id="habitName" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="habitTime" class="form-label">Target Time</label>
                        <input type="time" class="form-control" id="habitTime" name="target_time" required>
                    </div>
                    <div class="mb-3">
                        <label for="habitCategory" class="form-label">Category</label>
                        <select class="form-select" id="habitCategory" name="category_id" required>
                            <option value="">Select Category</option>
                            <option value="1">Study</option>
                            <option value="2">Exercise</option>
                            <option value="3">Reading</option>
                        </select>
                    </div>
                    <div class="text-end">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Habit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Progress Circles
    function createProgressCircle(element) {
        const percentage = element.dataset.progress;
        const radius = 36;
        const circumference = radius * 2 * Math.PI;
        const html = `
            <svg width="80" height="80" viewBox="0 0 80 80">
                <circle class="progress-circle-bg"
                    stroke="#f0f0f0"
                    stroke-width="8"
                    fill="none"
                    r="${radius}"
                    cx="40"
                    cy="40"/>
                <circle class="progress-circle-value"
                    stroke="var(--primary-color)"
                    stroke-width="8"
                    stroke-linecap="round"
                    fill="none"
                    r="${radius}"
                    cx="40"
                    cy="40"
                    style="stroke-dasharray: ${circumference};
                           stroke-dashoffset: ${circumference - (percentage / 100) * circumference}"/>
                <text x="40" y="40"
                    text-anchor="middle"
                    dominant-baseline="middle"
                    fill="var(--primary-color)"
                    font-size="16"
                    font-weight="bold">${percentage}%</text>
            </svg>
        `;
        element.innerHTML = html;
    }

    document.querySelectorAll('.progress-circle').forEach(createProgressCircle);

    // Initialize Countdown Rings
    function createCountdownRing(element) {
        const days = parseInt(element.dataset.days);
        const maxDays = 30;
        const percentage = Math.min(((maxDays - days) / maxDays) * 100, 100);
        const radius = 24;
        const circumference = radius * 2 * Math.PI;
        const color = days <= 7 ? '#dc3545' : (days <= 14 ? '#ffc107' : '#28a745');
        
        const html = `
            <svg width="60" height="60" viewBox="0 0 60 60">
                <circle
                    stroke="#f0f0f0"
                    stroke-width="4"
                    fill="none"
                    r="${radius}"
                    cx="30"
                    cy="30"/>
                <circle
                    stroke="${color}"
                    stroke-width="4"
                    stroke-linecap="round"
                    fill="none"
                    r="${radius}"
                    cx="30"
                    cy="30"
                    style="stroke-dasharray: ${circumference};
                           stroke-dashoffset: ${circumference - (percentage / 100) * circumference}"/>
                <text x="30" y="30"
                    text-anchor="middle"
                    dominant-baseline="middle"
                    fill="${color}"
                    font-size="12"
                    font-weight="bold">${days}d</text>
            </svg>
        `;
        element.innerHTML = html;
    }

    document.querySelectorAll('.countdown-timer').forEach(createCountdownRing);

    // Handle Task Completion
    document.querySelectorAll('.complete-task').forEach(button => {
        button.addEventListener('click', function() {
            const taskId = this.dataset.taskId;
            const taskItem = this.closest('.task-item');
            
            fetch('/includes/complete_task.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `task_id=${taskId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    taskItem.classList.add('completed');
                    setTimeout(() => {
                        taskItem.style.height = taskItem.offsetHeight + 'px';
                        taskItem.style.opacity = '0';
                        setTimeout(() => {
                            taskItem.remove();
                            if (document.querySelectorAll('.task-item').length === 0) {
                                location.reload();
                            }
                        }, 300);
                    }, 500);
                }
            });
        });
    });

    // Handle Word Practice
    document.querySelectorAll('.mark-practiced').forEach(button => {
        button.addEventListener('click', function() {
            const wordId = this.dataset.wordId;
            const wordCard = this.closest('.word-card');
            
            fetch('/includes/mark_word_practiced.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `word_id=${wordId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    wordCard.classList.add('practiced');
                    this.outerHTML = `
                        <span class="badge bg-success">
                            <i class="fas fa-check"></i> Practiced
                        </span>
                    `;
                }
            });
        });
    });

    // Add animation classes on scroll
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-fade-in');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 });

    document.querySelectorAll('.subject-card, .task-item, .exam-countdown, .word-card').forEach(el => {
        observer.observe(el);
    });

    // Add Task Form Handler
    document.getElementById('addTaskForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        
        fetch(this.action, {
            method: 'POST',
            body: new URLSearchParams(formData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Error adding task');
            }
        });
    });

    // Add Habit Form Handler
    document.getElementById('addHabitForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        
        fetch(this.action, {
            method: 'POST',
            body: new URLSearchParams(formData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Error adding habit');
            }
        });
    });

    // Real-time Exam Countdown
    function updateExamCountdowns() {
        document.querySelectorAll('.countdown-timer').forEach(timer => {
            const examDate = new Date(timer.dataset.examDate);
            const now = new Date();
            
            const diff = examDate - now;
            const days = Math.floor(diff / (1000 * 60 * 60 * 24));
            const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            
            timer.innerHTML = `
                <div class="d-flex align-items-center">
                    <span class="badge bg-primary me-2">${days}d</span>
                    <span class="badge bg-secondary me-2">${hours}h</span>
                    <span class="badge bg-info">${minutes}m</span>
                </div>
            `;
        });
    }

    // Update countdown every minute
    setInterval(updateExamCountdowns, 60000);
    updateExamCountdowns();
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>