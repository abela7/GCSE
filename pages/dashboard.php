<?php
session_start();
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../functions/stats_functions.php';
require_once __DIR__ . '/../functions/task_functions.php';
require_once __DIR__ . '/../functions/habit_functions.php';
require_once __DIR__ . '/../functions/exam_functions.php';
require_once __DIR__ . '/../functions/vocabulary_functions.php';

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
    $user_id = $_SESSION['user_id'];

    // Fetch today's tasks with full details
    $today_tasks = get_todays_tasks_with_details($conn, $user_id);
    
    // Fetch today's habits with progress
    $today_habits = get_todays_habits_with_progress($conn, $user_id);
    
    // Fetch upcoming exams with countdown
    $upcoming_exams = get_upcoming_exams_with_countdown($conn, $user_id);
    
    // Fetch daily words with practice status
    $daily_words = get_daily_words_with_status($conn, $user_id);
    
    // Fetch subject progress
    $stmt = $conn->prepare("
        SELECT subject_id, COUNT(DISTINCT topic_id) as completed_topics
        FROM topic_completion
        WHERE user_id = ? AND completion_date IS NOT NULL
        GROUP BY subject_id
    ");
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
    
    // Fetch assignments with progress
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
    --glass-bg: rgba(255, 255, 255, 0.8);
    --glass-border: rgba(255, 255, 255, 0.2);
}

/* Modern Glassmorphism Design */
.glass-card {
    background: var(--glass-bg);
    backdrop-filter: blur(10px);
    border: 1px solid var(--glass-border);
    border-radius: 1rem;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: var(--card-shadow);
    transition: all var(--transition-speed);
}

.glass-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
}

/* Hero Section with Parallax Effect */
.dashboard-hero {
    background: var(--accent-gradient);
    padding: 3rem 0;
    margin-bottom: 2rem;
    position: relative;
    overflow: hidden;
    min-height: 200px;
}

.dashboard-hero::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: 
        radial-gradient(circle at 20% 20%, rgba(255,255,255,0.2) 0%, transparent 20%),
        radial-gradient(circle at 80% 80%, rgba(255,255,255,0.2) 0%, transparent 20%);
    opacity: 0.1;
}

.hero-content {
    position: relative;
    z-index: 1;
    text-align: center;
    color: white;
    text-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

/* Exam Countdown Cards */
.exam-card {
    background: var(--glass-bg);
    border-radius: 1rem;
    padding: 1.5rem;
    margin-bottom: 1rem;
    position: relative;
    overflow: hidden;
}

.exam-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 4px;
    height: 100%;
    background: var(--primary-color);
}

.countdown-badge {
    position: absolute;
    top: 1rem;
    right: 1rem;
    padding: 0.5rem 1rem;
    border-radius: 2rem;
    font-weight: bold;
    font-size: 0.9rem;
}

.countdown-badge.urgent {
    background: #ff4444;
    color: white;
}

.countdown-badge.warning {
    background: #ffbb33;
    color: white;
}

.countdown-badge.safe {
    background: #00C851;
    color: white;
}

/* Task and Habit Cards */
.task-habit-card {
    background: var(--glass-bg);
    border-radius: 1rem;
    padding: 1.5rem;
    margin-bottom: 1rem;
    transition: all var(--transition-speed);
}

.task-habit-card:hover {
    transform: translateX(5px);
}

.task-item, .habit-item {
    display: flex;
    align-items: center;
    padding: 1rem;
    margin-bottom: 0.5rem;
    border-radius: 0.5rem;
    background: rgba(255,255,255,0.5);
    transition: all var(--transition-speed);
}

.task-item:hover, .habit-item:hover {
    background: rgba(255,255,255,0.8);
    transform: translateX(5px);
}

/* Word of the Day Accordion */
.word-accordion {
    background: var(--glass-bg);
    border-radius: 1rem;
    overflow: hidden;
}

.word-item {
    padding: 1.5rem;
    border-bottom: 1px solid var(--glass-border);
    transition: all var(--transition-speed);
}

.word-item:last-child {
    border-bottom: none;
}

.word-item:hover {
    background: rgba(255,255,255,0.9);
}

.word-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    cursor: pointer;
}

.word-content {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid var(--glass-border);
}

/* Progress Circles */
.progress-circle {
    width: 100px;
    height: 100px;
    position: relative;
}

.progress-circle svg {
    transform: rotate(-90deg);
}

.progress-circle-bg {
    stroke: rgba(0,0,0,0.1);
}

.progress-circle-value {
    stroke: var(--primary-color);
    transition: stroke-dashoffset var(--transition-speed);
}

/* Mobile Optimizations */
@media (max-width: 768px) {
    .dashboard-hero {
        padding: 2rem 0;
    }
    
    .glass-card {
        margin-bottom: 1rem;
    }
    
    .progress-circle {
        width: 80px;
        height: 80px;
    }
}
</style>

<div class="dashboard-hero">
    <div class="hero-content">
        <h1 class="display-4">Welcome Back!</h1>
        <p class="lead">Let's make today productive</p>
    </div>
</div>

<div class="container">
    <div class="row">
        <!-- Exam Countdowns -->
        <div class="col-md-6">
            <div class="glass-card">
                <h3 class="mb-4">Upcoming Exams</h3>
                <?php foreach ($upcoming_exams as $exam): ?>
                    <div class="exam-card">
                        <div class="countdown-badge <?php 
                            echo $exam['days_remaining'] <= 7 ? 'urgent' : 
                                ($exam['days_remaining'] <= 14 ? 'warning' : 'safe'); 
                        ?>">
                            <?php echo $exam['days_remaining']; ?> days
                        </div>
                        <h4><?php echo htmlspecialchars($exam['title']); ?></h4>
                        <p class="text-muted"><?php echo date('F j, Y', strtotime($exam['exam_date'])); ?></p>
                        <div class="progress mt-3">
                            <div class="progress-bar" role="progressbar" 
                                 style="width: <?php echo $exam['readiness_percentage']; ?>%">
                                <?php echo $exam['readiness_percentage']; ?>% Ready
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Tasks and Habits -->
        <div class="col-md-6">
            <div class="glass-card">
                <h3 class="mb-4">Today's Tasks</h3>
                <?php foreach ($today_tasks as $task): ?>
                    <div class="task-item">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" 
                                   id="task-<?php echo $task['id']; ?>"
                                   <?php echo $task['is_completed'] ? 'checked' : ''; ?>
                                   onchange="completeTask(<?php echo $task['id']; ?>)">
                            <label class="form-check-label" for="task-<?php echo $task['id']; ?>">
                                <?php echo htmlspecialchars($task['title']); ?>
                            </label>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="glass-card">
                <h3 class="mb-4">Today's Habits</h3>
                <?php foreach ($today_habits as $habit): ?>
                    <div class="habit-item">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" 
                                   id="habit-<?php echo $habit['id']; ?>"
                                   <?php echo $habit['is_completed'] ? 'checked' : ''; ?>
                                   onchange="completeHabit(<?php echo $habit['id']; ?>)">
                            <label class="form-check-label" for="habit-<?php echo $habit['id']; ?>">
                                <?php echo htmlspecialchars($habit['name']); ?>
                            </label>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Words of the Day -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="glass-card">
                <h3 class="mb-4">Words of the Day</h3>
                <div class="word-accordion">
                    <?php foreach ($daily_words as $word): ?>
                        <div class="word-item">
                            <div class="word-header" onclick="toggleWord(this)">
                                <h4 class="mb-0"><?php echo htmlspecialchars($word['word']); ?></h4>
                                <span class="badge <?php echo $word['is_practiced'] ? 'bg-success' : 'bg-secondary'; ?>">
                                    <?php echo $word['is_practiced'] ? 'Practiced' : 'Not Practiced'; ?>
                                </span>
                            </div>
                            <div class="word-content" style="display: none;">
                                <p><strong>Definition:</strong> <?php echo htmlspecialchars($word['definition']); ?></p>
                                <p><strong>Example:</strong> <?php echo htmlspecialchars($word['example']); ?></p>
                                <button class="btn btn-primary btn-sm" 
                                        onclick="markWordAsPracticed(<?php echo $word['id']; ?>)"
                                        <?php echo $word['is_practiced'] ? 'disabled' : ''; ?>>
                                    Mark as Practiced
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleWord(element) {
    const content = element.nextElementSibling;
    content.style.display = content.style.display === 'none' ? 'block' : 'none';
}

function completeTask(taskId) {
    fetch(`/includes/complete_task.php?task_id=${taskId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showFeedback('Task completed successfully!', 'success');
        } else {
            showFeedback('Error completing task', 'error');
        }
    });
}

function completeHabit(habitId) {
    fetch(`/includes/complete_habit.php?habit_id=${habitId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showFeedback('Habit completed successfully!', 'success');
        } else {
            showFeedback('Error completing habit', 'error');
        }
    });
}

function markWordAsPracticed(wordId) {
    fetch(`/includes/mark_word_practiced.php?word_id=${wordId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showFeedback('Word marked as practiced!', 'success');
            location.reload();
        } else {
            showFeedback('Error marking word', 'error');
        }
    });
}

function showFeedback(message, type) {
    const feedback = document.createElement('div');
    feedback.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show`;
    feedback.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(feedback);
    setTimeout(() => feedback.remove(), 3000);
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>