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
    <!-- Today's Tasks Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-tasks text-primary me-2"></i>
                        Today's Tasks
                    </h5>
                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addTaskModal">
                        <i class="fas fa-plus"></i> Add Task
                    </button>
                </div>
                <div class="card-body">
                    <?php if (!empty($today_tasks)): ?>
                        <div class="row">
                            <?php foreach ($today_tasks as $task): ?>
                                <div class="col-md-6 mb-3">
                                    <div class="task-item <?php echo $task['status'] === 'completed' ? 'completed' : ''; ?>">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="mb-1">
                                                    <?php echo htmlspecialchars($task['title']); ?>
                                                </h6>
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
                                </div>
                            <?php endforeach; ?>
                        </div>
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

    <!-- Subject Progress Section -->
    <div class="row mb-4">
        <!-- Math Progress -->
        <div class="col-md-6 mb-4">
            <div class="subject-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="mb-1">Mathematics</h5>
                        <p class="text-muted mb-0">
                            <?php echo $math_stats['completed_topics']; ?> of <?php echo $math_stats['total_topics']; ?> topics completed
                        </p>
                    </div>
                    <div class="progress-circle" data-progress="<?php echo $math_stats['progress_percentage']; ?>"></div>
                </div>
                <div class="progress" style="height: 8px;">
                    <div class="progress-bar bg-primary" role="progressbar" 
                         style="width: <?php echo $math_stats['progress_percentage']; ?>%" 
                         aria-valuenow="<?php echo $math_stats['progress_percentage']; ?>" 
                         aria-valuemin="0" 
                         aria-valuemax="100">
                    </div>
                </div>
            </div>
        </div>
        
        <!-- English Progress -->
        <div class="col-md-6 mb-4">
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
                <div class="progress" style="height: 8px;">
                    <div class="progress-bar bg-success" role="progressbar" 
                         style="width: <?php echo $english_stats['progress_percentage']; ?>%" 
                         aria-valuenow="<?php echo $english_stats['progress_percentage']; ?>" 
                         aria-valuemin="0" 
                         aria-valuemax="100">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Left Column -->
        <div class="col-md-8">
            <!-- Upcoming Exams -->
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="fas fa-graduation-cap text-primary me-2"></i>
                        Upcoming Exams
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($upcoming_exams)): ?>
                        <div class="row">
                            <?php foreach ($upcoming_exams as $exam): ?>
                                <div class="col-md-6 mb-3">
                                    <div class="exam-countdown">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="mb-1"><?php echo htmlspecialchars($exam['title']); ?></h6>
                                                <span class="badge" 
                                                      style="background-color: <?php echo htmlspecialchars($exam['subject_color']); ?>">
                                                    <?php echo htmlspecialchars($exam['subject_name']); ?>
                                                </span>
                                            </div>
                                            <div class="countdown-ring" 
                                                 data-days="<?php echo $exam['days_remaining']; ?>">
                                            </div>
                                        </div>
                                        <div class="mt-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <small class="text-muted">
                                                    <?php echo date('j M Y', strtotime($exam['exam_date'])); ?>
                                                </small>
                                                <span class="badge <?php 
                                                    echo $exam['days_remaining'] <= 7 ? 'bg-danger' : 
                                                        ($exam['days_remaining'] <= 14 ? 'bg-warning' : 'bg-success'); 
                                                ?>">
                                                    <?php echo $exam['days_remaining']; ?> days left
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-calendar-check fa-2x text-muted mb-3"></i>
                            <p class="text-muted mb-0">No upcoming exams</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Assignments Progress -->
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="fas fa-book text-primary me-2"></i>
                        Assignment Progress
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($assignments)): ?>
                        <?php foreach ($assignments as $assignment): 
                            $progress = $assignment['total_criteria'] > 0 ? 
                                ($assignment['completed_criteria'] / $assignment['total_criteria']) * 100 : 0;
                        ?>
                            <div class="assignment-item mb-3">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($assignment['title']); ?></h6>
                                        <small class="text-muted">
                                            Due <?php echo date('j M', strtotime($assignment['due_date'])); ?>
                                        </small>
                                    </div>
                                    <span class="badge bg-primary">
                                        <?php echo $assignment['completed_criteria']; ?>/<?php echo $assignment['total_criteria']; ?>
                                    </span>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-success" role="progressbar" 
                                         style="width: <?php echo $progress; ?>%" 
                                         aria-valuenow="<?php echo $progress; ?>" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100">
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-check-circle fa-2x text-muted mb-3"></i>
                            <p class="text-muted mb-0">No pending assignments</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Right Column -->
        <div class="col-md-4">
            <!-- Words of the Day -->
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="fas fa-book-reader text-primary me-2"></i>
                        Words of the Day
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($daily_words)): ?>
                        <?php foreach ($daily_words as $word): ?>
                            <div class="word-card <?php echo $word['is_practiced'] ? 'practiced' : ''; ?>">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($word['word']); ?></h6>
                                        <p class="text-muted small mb-2">
                                            <?php echo htmlspecialchars($word['definition']); ?>
                                        </p>
                                        <?php if ($word['example']): ?>
                                            <p class="small mb-0">
                                                <i class="fas fa-quote-left text-muted me-1"></i>
                                                <?php echo htmlspecialchars($word['example']); ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!$word['is_practiced']): ?>
                                        <button class="btn btn-sm btn-outline-primary mark-practiced" 
                                                data-word-id="<?php echo $word['id']; ?>">
                                            Practice
                                        </button>
                                    <?php else: ?>
                                        <span class="badge bg-success">
                                            <i class="fas fa-check"></i> Practiced
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-book fa-2x text-muted mb-3"></i>
                            <p class="text-muted mb-0">No words for today</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="fas fa-bolt text-primary me-2"></i>
                        Quick Actions
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="/pages/EnglishPractice/practice.php" class="btn btn-outline-primary">
                            <i class="fas fa-play-circle me-2"></i>
                            Start Practice
                        </a>
                        <a href="/pages/EnglishPractice/daily_entry.php" class="btn btn-outline-success">
                            <i class="fas fa-plus-circle me-2"></i>
                            Add New Entry
                        </a>
                        <a href="/pages/EnglishPractice/review.php?favorites=1" class="btn btn-outline-warning">
                            <i class="fas fa-star me-2"></i>
                            Review Favorites
                        </a>
                    </div>
                </div>
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

    document.querySelectorAll('.countdown-ring').forEach(createCountdownRing);

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
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>