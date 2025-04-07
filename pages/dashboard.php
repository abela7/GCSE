<?php
session_start();
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../functions/includes/task_functions.php';
require_once __DIR__ . '/../functions/includes/habit_functions.php';
require_once __DIR__ . '/../functions/includes/exam_functions.php';
require_once __DIR__ . '/../functions/includes/vocabulary_functions.php';

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

try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception("User not logged in");
    }
    
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
    
} catch (Exception $e) {
    error_log("Error in dashboard.php: " . $e->getMessage());
    // You might want to show a user-friendly error message here
}

$page_title = "Dashboard";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <!-- Exam Countdown Section -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Upcoming Exams</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($upcoming_exams)): ?>
                        <?php foreach ($upcoming_exams as $exam): ?>
                            <div class="exam-countdown mb-3">
                                <h6><?php echo htmlspecialchars($exam['title']); ?></h6>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="countdown-ring" data-days="<?php echo $exam['days_remaining']; ?>">
                                        <svg viewBox="0 0 36 36">
                                            <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                                                fill="none" stroke="#eee" stroke-width="3" />
                                            <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                                                fill="none" stroke="<?php echo $exam['urgency_color']; ?>" stroke-width="3"
                                                stroke-dasharray="<?php echo $exam['progress']; ?>, 100" />
                                        </svg>
                                        <span class="countdown-text"><?php echo $exam['days_remaining']; ?>d</span>
                                    </div>
                                    <div>
                                        <small class="text-muted">Date: <?php echo date('M d, Y', strtotime($exam['exam_date'])); ?></small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted">No upcoming exams</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Today's Tasks Section -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Today's Tasks</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($today_tasks)): ?>
                        <?php foreach ($today_tasks as $task): ?>
                            <div class="task-item mb-2 <?php echo $task['status'] === 'completed' ? 'completed' : ''; ?>">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0"><?php echo htmlspecialchars($task['title']); ?></h6>
                                        <small class="text-muted"><?php echo htmlspecialchars($task['category_name']); ?></small>
                                    </div>
                                    <?php if ($task['status'] !== 'completed'): ?>
                                        <button class="btn btn-sm btn-outline-success complete-task" 
                                                data-task-id="<?php echo $task['id']; ?>">
                                            Complete
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted">No tasks for today</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Subject Progress Section -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Subject Progress</h5>
                </div>
                <div class="card-body">
                    <div class="subject-card mb-3">
                        <h6>Mathematics</h6>
                        <div class="progress">
                            <div class="progress-bar" role="progressbar" 
                                 style="width: <?php echo $math_stats['progress_percentage']; ?>%"
                                 aria-valuenow="<?php echo $math_stats['progress_percentage']; ?>" 
                                 aria-valuemin="0" 
                                 aria-valuemax="100">
                                <?php echo $math_stats['progress_percentage']; ?>%
                            </div>
                        </div>
                        <small class="text-muted">
                            <?php echo $math_stats['completed_topics']; ?> of <?php echo $math_stats['total_topics']; ?> topics completed
                        </small>
                    </div>
                    <div class="subject-card">
                        <h6>English</h6>
                        <div class="progress">
                            <div class="progress-bar" role="progressbar" 
                                 style="width: <?php echo $english_stats['progress_percentage']; ?>%"
                                 aria-valuenow="<?php echo $english_stats['progress_percentage']; ?>" 
                                 aria-valuemin="0" 
                                 aria-valuemax="100">
                                <?php echo $english_stats['progress_percentage']; ?>%
                            </div>
                        </div>
                        <small class="text-muted">
                            <?php echo $english_stats['completed_topics']; ?> of <?php echo $english_stats['total_topics']; ?> topics completed
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Words of the Day Section -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-warning text-white">
                    <h5 class="mb-0">Words of the Day</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($daily_words)): ?>
                        <?php foreach ($daily_words as $word): ?>
                            <div class="word-card mb-2 <?php echo $word['is_practiced'] ? 'practiced' : ''; ?>">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0"><?php echo htmlspecialchars($word['word']); ?></h6>
                                        <small class="text-muted"><?php echo htmlspecialchars($word['definition']); ?></small>
                                    </div>
                                    <?php if (!$word['is_practiced']): ?>
                                        <button class="btn btn-sm btn-outline-primary mark-practiced" 
                                                data-word-id="<?php echo $word['id']; ?>">
                                            Mark as Practiced
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted">No words for today</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Task completion handler
    document.querySelectorAll('.complete-task').forEach(button => {
        button.addEventListener('click', function() {
            const taskId = this.dataset.taskId;
            fetch('/GCSE/functions/ajax/complete_task.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `task_id=${taskId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const taskItem = this.closest('.task-item');
                    taskItem.classList.add('completed');
                    this.remove();
                    showFeedback('Task completed successfully!', 'success');
                } else {
                    showFeedback(data.message, 'error');
                }
            })
            .catch(error => {
                showFeedback('Error completing task', 'error');
                console.error('Error:', error);
            });
        });
    });

    // Word practice handler
    document.querySelectorAll('.mark-practiced').forEach(button => {
        button.addEventListener('click', function() {
            const wordId = this.dataset.wordId;
            fetch('/GCSE/functions/ajax/mark_word_practiced.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `word_id=${wordId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const wordCard = this.closest('.word-card');
                    wordCard.classList.add('practiced');
                    this.remove();
                    showFeedback('Word marked as practiced!', 'success');
                } else {
                    showFeedback(data.message, 'error');
                }
            })
            .catch(error => {
                showFeedback('Error marking word as practiced', 'error');
                console.error('Error:', error);
            });
        });
    });

    // Feedback message system
    function showFeedback(message, type = 'success') {
        const feedback = document.createElement('div');
        feedback.className = `feedback-message ${type}`;
        feedback.textContent = message;
        document.body.appendChild(feedback);
        
        setTimeout(() => {
            feedback.classList.add('show');
            setTimeout(() => {
                feedback.classList.remove('show');
                setTimeout(() => feedback.remove(), 300);
            }, 2000);
        }, 100);
    }

    // Real-time exam countdown
    function updateExamCountdowns() {
        document.querySelectorAll('.countdown-ring').forEach(ring => {
            const days = parseInt(ring.dataset.days);
            const progress = Math.min(100, Math.max(0, (30 - days) * (100/30)));
            const color = days <= 7 ? '#dc3545' : (days <= 14 ? '#ffc107' : '#28a745');
            
            ring.querySelector('path:last-child').setAttribute('stroke', color);
            ring.querySelector('path:last-child').setAttribute('stroke-dasharray', `${progress}, 100`);
            ring.querySelector('.countdown-text').textContent = `${days}d`;
        });
    }

    // Update countdown every minute
    setInterval(updateExamCountdowns, 60000);
    updateExamCountdowns();
});
</script>

<style>
.feedback-message {
    position: fixed;
    bottom: 20px;
    left: 50%;
    transform: translateX(-50%) translateY(100%);
    padding: 12px 24px;
    border-radius: 8px;
    background: white;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    z-index: 1000;
    opacity: 0;
    transition: all 0.3s ease;
}

.feedback-message.show {
    transform: translateX(-50%) translateY(0);
    opacity: 1;
}

.feedback-message.success {
    border-left: 4px solid #28a745;
}

.feedback-message.error {
    border-left: 4px solid #dc3545;
}

.countdown-ring {
    width: 60px;
    height: 60px;
    position: relative;
}

.countdown-ring svg {
    transform: rotate(-90deg);
}

.countdown-ring path {
    fill: none;
    stroke-width: 3;
    stroke-linecap: round;
}

.countdown-text {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 14px;
    font-weight: bold;
}

.task-item.completed {
    opacity: 0.7;
    text-decoration: line-through;
}

.word-card.practiced {
    border-left: 4px solid #28a745;
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>