<?php
// Set page title
$page_title = "Dashboard";

// Include database connection
require_once '../config/db_connect.php';

// Get subjects with their progress
$subjects_query = "SELECT s.*, 
    (SELECT COUNT(DISTINCT t.id) 
     FROM math_topics t 
     JOIN math_subsections sub ON t.subsection_id = sub.id 
     JOIN math_sections sec ON sub.section_id = sec.id 
     WHERE s.id = 2) + 
    (SELECT COUNT(DISTINCT t.id) 
     FROM eng_topics t 
     JOIN eng_subsections sub ON t.subsection_id = sub.id 
     JOIN eng_sections sec ON sub.section_id = sec.id 
     WHERE s.id = 1) as total_topics,
    (SELECT COUNT(DISTINCT t.id) 
     FROM math_topics t 
     JOIN math_subsections sub ON t.subsection_id = sub.id 
     JOIN math_sections sec ON sub.section_id = sec.id 
     JOIN topic_progress tp ON t.id = tp.topic_id 
     WHERE s.id = 2 AND tp.status = 'completed') +
    (SELECT COUNT(DISTINCT t.id) 
     FROM eng_topics t 
     JOIN eng_subsections sub ON t.subsection_id = sub.id 
     JOIN eng_sections sec ON sub.section_id = sec.id 
     JOIN eng_topic_progress tp ON t.id = tp.topic_id 
     WHERE s.id = 1 AND tp.status = 'completed') as completed_topics
FROM subjects s";

$subjects_result = $conn->query($subjects_query);

// Get upcoming exams (next 30 days)
$exams_query = "SELECT e.*, s.name as subject_name, s.color as subject_color 
                FROM exams e 
                JOIN subjects s ON e.subject_id = s.id 
                WHERE e.exam_date > NOW() AND e.exam_date <= DATE_ADD(NOW(), INTERVAL 30 DAY)
                ORDER BY e.exam_date ASC 
                LIMIT 3";
$exams_result = $conn->query($exams_query);

// Get recent tasks - fixed query to match existing database structure
$tasks_query = "SELECT t.*, c.name as category_name, c.color as category_color 
                FROM tasks t 
                LEFT JOIN task_categories c ON t.category_id = c.id 
                WHERE t.status != 'completed'
                ORDER BY t.due_date ASC 
                LIMIT 5";
$tasks_result = $conn->query($tasks_query);

// Get habit completion stats - fixed query to match existing database structure
$habits_query = "SELECT COUNT(*) as total_habits, 
                SUM(CASE WHEN EXISTS (
                    SELECT 1 FROM habit_completions hc 
                    WHERE hc.habit_id = h.id 
                    AND hc.completion_date = CURDATE() 
                    AND hc.status = 'completed'
                ) THEN 1 ELSE 0 END) as completed_today
                FROM habits h
                WHERE h.is_active = 1";
$habits_result = $conn->query($habits_query);
$habits_stats = $habits_result ? $habits_result->fetch_assoc() : ['total_habits' => 0, 'completed_today' => 0];

// Get today's uncompleted habits
$uncompleted_habits_query = "SELECT h.*, c.name as category_name, c.color as category_color
                           FROM habits h
                           LEFT JOIN habit_categories c ON h.category_id = c.id
                           WHERE h.is_active = 1
                           AND NOT EXISTS (
                               SELECT 1 FROM habit_completions hc 
                               WHERE hc.habit_id = h.id 
                               AND hc.completion_date = CURDATE() 
                               AND hc.status = 'completed'
                           )
                           ORDER BY h.name ASC
                           LIMIT 5";
$uncompleted_habits_result = $conn->query($uncompleted_habits_query);

// Get birthday data for life counter
$birthday_query = "SELECT * FROM birthday LIMIT 1";
$birthday_result = $conn->query($birthday_query);
$birthday_data = ($birthday_result && $birthday_result->num_rows > 0) ? $birthday_result->fetch_assoc() : null;

// Include header
include '../includes/header.php';

// Define the accent color
$accent_color = "#cdaf56";
?>

<style>
/* Color Variables */
:root {
    --accent-color: <?php echo $accent_color; ?>;
    --accent-color-light: #dbc77a;
    --accent-color-dark: #b99b3e;
    --text-color: #333333;
    --text-muted: #6c757d;
    --bg-light: #f8f9fa;
    --border-color: #e9ecef;
}

/* Card Styles */
.feature-card {
    border: none;
    border-radius: 10px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    height: 100%;
    overflow: hidden;
}
.feature-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 15px rgba(0,0,0,0.1);
}
.feature-card .card-body {
    padding: 1.5rem;
}
.feature-card .icon-bg {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1rem;
    background-color: var(--accent-color);
}
.feature-card .icon-bg i {
    font-size: 1.5rem;
    color: white;
}
.feature-card h5 {
    font-weight: 600;
    margin-bottom: 0.75rem;
    color: var(--text-color);
}
.feature-card p {
    color: var(--text-muted);
    margin-bottom: 1rem;
}
.feature-card .card-footer {
    background: transparent;
    border-top: 1px solid var(--border-color);
    padding: 0.75rem 1.5rem;
}

/* Button Styles */
.btn-accent {
    background-color: var(--accent-color);
    border-color: var(--accent-color);
    color: white;
}
.btn-accent:hover {
    background-color: var(--accent-color-dark);
    border-color: var(--accent-color-dark);
    color: white;
}
.btn-outline-accent {
    color: var(--accent-color);
    border-color: var(--accent-color);
}
.btn-outline-accent:hover {
    background-color: var(--accent-color);
    color: white;
}

/* Progress Styles */
.progress {
    height: 8px;
    border-radius: 4px;
    overflow: hidden;
}
.progress-label {
    font-size: 0.875rem;
    font-weight: 500;
}
.progress-bar-accent {
    background-color: var(--accent-color);
}

/* Stats Card */
.stats-card {
    border: none;
    border-radius: 10px;
    background: var(--bg-light);
    padding: 1rem;
    height: 100%;
}
.stats-card .stat-value {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    color: var(--accent-color);
}
.stats-card .stat-label {
    color: var(--text-muted);
    font-size: 0.875rem;
}

/* Section Headings */
.section-heading {
    display: flex;
    align-items: center;
    margin-bottom: 1.5rem;
}
.section-heading h4 {
    font-weight: 600;
    margin-bottom: 0;
    color: var(--text-color);
}
.section-heading i {
    color: var(--accent-color);
}
.section-heading .line {
    flex-grow: 1;
    height: 1px;
    background-color: var(--border-color);
    margin-left: 1rem;
}

/* Responsive Adjustments */
@media (max-width: 767.98px) {
    .feature-card .icon-bg {
        width: 50px;
        height: 50px;
    }
    .feature-card .icon-bg i {
        font-size: 1.25rem;
    }
    .stats-card .stat-value {
        font-size: 1.5rem;
    }
}

/* Floating Action Button */
.fab-container {
    position: fixed;
    bottom: 2rem;
    right: 2rem;
    z-index: 999;
}

.fab-button {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background-color: var(--accent-color);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 22px;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.fab-button:hover {
    background-color: var(--accent-color-dark);
    transform: scale(1.05);
    box-shadow: 0 6px 14px rgba(0, 0, 0, 0.3);
}

.fab-options {
    position: absolute;
    bottom: 70px;
    right: 0;
    display: none;
    flex-direction: column;
    align-items: flex-end;
    gap: 0.75rem;
    transition: all 0.3s ease;
}

.fab-options.show {
    display: flex;
    animation: fadeInUp 0.3s ease forwards;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.fab-item {
    display: flex;
    align-items: center;
    background-color: white;
    padding: 0.6rem 0.9rem;
    border-radius: 24px;
    box-shadow: 0 3px 8px rgba(0, 0, 0, 0.12);
    cursor: pointer;
    transition: all 0.2s ease;
    text-decoration: none;
    color: var(--text-color);
    font-size: 0.9rem;
}

.fab-item:hover {
    background-color: var(--bg-light);
    transform: translateX(-5px);
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
}

.fab-item i {
    margin-right: 0.5rem;
    color: var(--accent-color);
    font-size: 0.95rem;
}

/* New notification-style counter */
.age-notification {
    min-height: auto;
    border-radius: 8px;
    background-color: #fff;
}
.age-simple-text {
    font-size: 1.3rem;
    color: var(--accent-color);
}
.card-indicator {
    display: flex;
    justify-content: center;
    gap: 8px;
}
.card-indicator span {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background-color: #ddd;
    display: inline-block;
}
.card-indicator span.active {
    background-color: var(--accent-color);
    width: 24px;
    border-radius: 4px;
}
.carousel-item {
    transition: transform 0.4s ease-in-out;
}
.totals-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}
.total-item {
    padding: 5px;
    text-align: center;
    color: var(--text-color);
    font-size: 0.9rem;
}
.total-item span {
    font-weight: bold;
    color: var(--accent-color);
    margin-right: 3px;
}
.time-counter {
    grid-column: span 2;
    font-weight: bold;
    color: var(--accent-color);
}

/* Styles for the small live counter on first slide */
.live-counter-small {
    background-color: rgba(185, 155, 62, 0.15);
    padding: 10px;
    border-radius: 8px;
}

.time-unit-small {
    display: inline-flex;
    align-items: center;
}

.time-unit-small span:first-child {
    font-weight: bold;
    color: var(--accent-color);
    font-size: 1.2rem;
    min-width: 28px;
    text-align: right;
}

.time-label {
    font-size: 0.8rem;
    color: var(--text-muted);
    margin-left: 2px;
}

.time-separator-small {
    margin: 0 3px;
    color: var(--accent-color);
    font-weight: bold;
}
</style>

<div class="container-fluid py-4">
    <!-- Age Counter Section - Accordion with Carousel -->
    <div class="accordion mb-4" id="ageCounterAccordion">
        <div class="accordion-item border-0 shadow-sm rounded">
            <h2 class="accordion-header" id="ageCounterHeading">
                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#ageCounterCollapse" aria-expanded="true" aria-controls="ageCounterCollapse" style="background-color: var(--accent-color); color: white; border-radius: 10px;">
                    <i class="fas fa-hourglass-half me-2"></i> Your Life Counter
                </button>
            </h2>
            <div id="ageCounterCollapse" class="accordion-collapse collapse show" aria-labelledby="ageCounterHeading">
                <div class="accordion-body p-0">
                    <div class="card feature-card border-0">
                        <div class="card-body p-0">
                            <?php if ($birthday_data): ?>
                            
                            <!-- Age Counter Carousel - Simple Notification Style -->
                            <div id="ageCounterCarousel" class="carousel slide" data-bs-ride="false" data-bs-touch="true" data-bs-interval="false">
                                <div class="carousel-inner">
                                    <!-- First Slide: Simple Age Display with Live Counter -->
                                    <div class="carousel-item active">
                                        <div class="age-notification p-3">
                                            <div class="mb-2 text-center">
                                                <div class="age-simple-text fw-bold" id="age-text-simple">
                                                    25 years, 6 months
                                                </div>
                                                <div class="live-counter-small mt-2 d-flex justify-content-center align-items-center">
                                                    <div class="time-unit-small">
                                                        <span id="days-small">27</span><span class="time-label">days</span>
                                                    </div>
                                                    <div class="time-separator-small">:</div>
                                                    <div class="time-unit-small">
                                                        <span id="hours-small">03</span><span class="time-label">hr</span>
                                                    </div>
                                                    <div class="time-separator-small">:</div>
                                                    <div class="time-unit-small">
                                                        <span id="minutes-small">10</span><span class="time-label">min</span>
                                                    </div>
                                                    <div class="time-separator-small">:</div>
                                                    <div class="time-unit-small">
                                                        <span id="seconds-small">42</span><span class="time-label">sec</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="card-indicator my-2">
                                                <span class="active"></span>
                                                <span></span>
                                            </div>
                                            <div class="text-center">
                                                <p class="mb-0 fst-italic small" id="motivation-message-primary">Are you using your time properly?</p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Second Slide: All Totals Together -->
                                    <div class="carousel-item">
                                        <div class="age-notification p-3">
                                            <div class="totals-grid mb-2">
                                                <div class="total-item">
                                                    <span id="total-years">25</span> years
                                                </div>
                                                <div class="total-item">
                                                    <span id="total-months">305</span> months
                                                </div>
                                                <div class="total-item">
                                                    <span id="total-weeks">1330</span> weeks
                                                </div>
                                                <div class="total-item">
                                                    <span id="total-days">9310</span> days
                                                </div>
                                                <div class="total-item time-counter" colspan="2">
                                                    <span id="hours">04</span>:<span id="minutes">34</span>:<span id="seconds">06</span>
                                                </div>
                                            </div>
                                            <div class="card-indicator my-2">
                                                <span></span>
                                                <span class="active"></span>
                                            </div>
                                            <div class="text-center">
                                                <p class="mb-0 fst-italic small" id="motivation-message-secondary">Make every day count!</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            
                            <!-- Custom styles for the counter -->
                            <style>
                                .age-card {
                                    min-height: 280px;
                                }
                                .card-indicator {
                                    display: flex;
                                    justify-content: center;
                                    gap: 8px;
                                }
                                .card-indicator span {
                                    width: 8px;
                                    height: 8px;
                                    border-radius: 50%;
                                    background-color: #ddd;
                                    display: inline-block;
                                }
                                .card-indicator span.active {
                                    background-color: var(--accent-color);
                                    width: 24px;
                                    border-radius: 4px;
                                }
                                .carousel-item {
                                    transition: transform 0.4s ease-in-out;
                                }
                                .totals-grid {
                                    display: grid;
                                    grid-template-columns: 1fr 1fr;
                                    gap: 10px;
                                }
                                .total-item {
                                    padding: 5px;
                                    text-align: center;
                                    color: var(--text-color);
                                    font-size: 0.9rem;
                                }
                                .total-item span {
                                    font-weight: bold;
                                    color: var(--accent-color);
                                    margin-right: 3px;
                                }
                                .time-counter {
                                    grid-column: span 2;
                                    font-weight: bold;
                                    color: var(--accent-color);
                                }
                            </style>
                            
                            <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-birthday-cake fa-3x text-muted mb-3"></i>
                                <p class="mb-3">You haven't set your birthday yet. Set it to track your life counter.</p>
                                <a href="settings/birthday.php" class="btn btn-accent">
                                    <i class="fas fa-calendar-plus me-1"></i> Set Birthday
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="section-heading">
        <h4><i class="fas fa-chart-line me-2"></i>Your Progress</h4>
        <div class="line"></div>
    </div>
    
    <div class="row mb-4">
        <?php 
        // Reset the result pointer
        if ($subjects_result && $subjects_result->num_rows > 0) {
            $subjects_result->data_seek(0);
            while($subject = $subjects_result->fetch_assoc()): 
                $progress = $subject['total_topics'] > 0 ? 
                    round(($subject['completed_topics'] / $subject['total_topics']) * 100) : 0;
                // Use accent color for all subjects
                $subject_color = $accent_color;
        ?>
        <div class="col-md-6 mb-4">
            <div class="card feature-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">
                            <span class="badge me-2" style="background-color: <?php echo $subject_color; ?>">
                                <?php echo $subject['name']; ?>
                            </span>
                            Progress
                        </h5>
                        <span class="progress-label"><?php echo $progress; ?>% Complete</span>
                    </div>
                    <div class="progress mb-3">
                        <div class="progress-bar progress-bar-accent" role="progressbar" 
                             style="width: <?php echo $progress; ?>%; background-color: <?php echo $subject_color; ?>" 
                             aria-valuenow="<?php echo $progress; ?>" aria-valuemin="0" aria-valuemax="100">
                        </div>
                    </div>
                    <div class="d-flex justify-content-between text-muted small">
                        <span><?php echo $subject['completed_topics']; ?> topics completed</span>
                        <span><?php echo $subject['total_topics']; ?> total topics</span>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="subjects/<?php echo strtolower($subject['name']); ?>.php" class="btn btn-sm btn-outline-accent">View Details</a>
                </div>
            </div>
        </div>
        <?php 
            endwhile;
        } else {
        ?>
        <div class="col-12">
            <div class="alert alert-info">
                No subject progress data available.
            </div>
        </div>
        <?php } ?>
    </div>

    <!-- Features -->
    <div class="section-heading">
        <h4><i class="fas fa-tools me-2"></i>Features</h4>
        <div class="line"></div>
    </div>
    
    <div class="row mb-4">
        <!-- Resources -->
        <div class="col-sm-6 col-md-3 mb-4">
            <div class="card feature-card">
                <div class="card-body">
                    <div class="icon-bg">
                        <i class="fas fa-folder"></i>
                    </div>
                    <h5>Resources</h5>
                    <p>Access study materials, guides, and references.</p>
                </div>
                <div class="card-footer">
                    <a href="resources.php" class="btn btn-sm btn-outline-accent">View Resources</a>
                </div>
            </div>
        </div>
        
        <!-- Exams -->
        <div class="col-sm-6 col-md-3 mb-4">
            <div class="card feature-card">
                <div class="card-body">
                    <div class="icon-bg">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <h5>Exams</h5>
                    <p>Manage exam schedules and preparation plans.</p>
                </div>
                <div class="card-footer">
                    <a href="exam_countdown.php" class="btn btn-sm btn-outline-accent">View Exams</a>
                </div>
            </div>
        </div>
        
        <!-- Today's Plan -->
        <div class="col-sm-6 col-md-3 mb-4">
            <div class="card feature-card">
                <div class="card-body">
                    <div class="icon-bg">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <h5>Today</h5>
                    <p>See your schedule and tasks for today.</p>
                </div>
                <div class="card-footer">
                    <a href="Today.php" class="btn btn-sm btn-outline-accent">View Today</a>
                </div>
            </div>
        </div>
        
        <!-- Assignments -->
        <div class="col-sm-6 col-md-3 mb-4">
            <div class="card feature-card">
                <div class="card-body">
                    <div class="icon-bg">
                        <i class="fas fa-book-open"></i>
                    </div>
                    <h5>Assignments</h5>
                    <p>Manage your homework and assignments.</p>
                </div>
                <div class="card-footer">
                    <a href="assignments.php" class="btn btn-sm btn-outline-accent">View Assignments</a>
                </div>
                </div>
            </div>
        </div>
    </div>

<!-- Floating Action Button (FAB) -->
<div class="fab-container">
    <div class="fab-options" id="fabOptions">
        <a href="#" class="fab-item" data-bs-toggle="modal" data-bs-target="#addTaskModal">
            <i class="fas fa-plus-circle"></i>
            <span>Add New Task</span>
        </a>
        <a href="mood_tracking/entry.php" class="fab-item">
            <i class="fas fa-smile"></i>
            <span>Record Mood</span>
        </a>
        <a href="tasks/index.php" class="fab-item">
            <i class="fas fa-tasks"></i>
            <span>View Tasks</span>
        </a>
        <a href="habits/index.php" class="fab-item">
            <i class="fas fa-check-circle"></i>
            <span>View Habits</span>
        </a>
        <a href="settings/birthday.php" class="fab-item">
            <i class="fas fa-hourglass-half"></i>
            <span>TIME!</span>
        </a>
                                </div>
    <div class="fab-button" id="fabButton">
        <i class="fas fa-plus"></i>
                                </div>
                            </div>

<!-- Add Task Modal -->
<div class="modal fade" id="addTaskModal" tabindex="-1" aria-labelledby="addTaskModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addTaskModalLabel">Add New Task</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="/pages/tasks/save_task.php" method="POST" id="addTaskForm">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="title" class="form-label">Title</label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>
                        <div class="col-md-6">
                            <label for="category_id" class="form-label">Category</label>
                            <select class="form-select" id="category_id" name="category_id" required>
                                <?php
                                $categories_query = "SELECT * FROM task_categories ORDER BY name";
                                $categories_result = $conn->query($categories_query);
                                if ($categories_result) {
                                    while ($category = $categories_result->fetch_assoc()):
                                    ?>
                                    <option value="<?php echo $category['id']; ?>">
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                    <?php endwhile;
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label for="due_date" class="form-label">Due Date</label>
                            <input type="date" class="form-control" id="due_date" name="due_date" required 
                                value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="due_time" class="form-label">Due Time</label>
                            <input type="time" class="form-control" id="due_time" name="due_time">
                        </div>
                        <div class="col-md-6">
                            <label for="task_type" class="form-label">Task Type</label>
                            <select class="form-select" id="task_type" name="task_type" required>
                                <option value="one-time">One-time</option>
                                <option value="recurring">Recurring</option>
                            </select>
                </div>
                        <div class="col-md-6">
                            <label for="priority" class="form-label">Priority</label>
                            <select class="form-select" id="priority" name="priority" required>
                                <option value="low">Low</option>
                                <option value="medium" selected>Medium</option>
                                <option value="high">High</option>
                            </select>
            </div>
                        <div class="col-md-6">
                            <label for="estimated_duration" class="form-label">Estimated Duration (minutes)</label>
                            <input type="number" class="form-control" id="estimated_duration" name="estimated_duration" min="1" value="30" required>
        </div>
                        <!-- Add a hidden field to indicate this is from the dashboard -->
                        <input type="hidden" name="from_dashboard" value="1">
                        <!-- Add a return URL so we can redirect back to the dashboard -->
                        <input type="hidden" name="return_url" value="/pages/dashboard.php">
                                </div>
                    <div class="mt-4">
                        <div id="alert-container">
                            <?php if (isset($_SESSION['task_message'])): ?>
                                <div class="alert alert-<?php echo $_SESSION['task_message_type']; ?> alert-dismissible fade show" role="alert">
                                    <?php echo $_SESSION['task_message']; ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                                <?php 
                                    unset($_SESSION['task_message']);
                                    unset($_SESSION['task_message_type']);
                                ?>
                            <?php endif; ?>
                        </div>
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Add Task</button>
                        </div>
                </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        <?php if ($birthday_data): ?>
        // Initialize carousel
        const ageCarousel = new bootstrap.Carousel(document.getElementById('ageCounterCarousel'), {
            interval: false,
            touch: true
        });
        
        // Add swipe functionality for mobile
        let touchStartX = 0;
        let touchEndX = 0;
        
        const carousel = document.getElementById('ageCounterCarousel');
        carousel.addEventListener('touchstart', e => {
            touchStartX = e.changedTouches[0].screenX;
        });
        
        carousel.addEventListener('touchend', e => {
            touchEndX = e.changedTouches[0].screenX;
            handleSwipe();
        });
        
        function handleSwipe() {
            if (touchEndX < touchStartX - 50) {
                // Swipe left, go to next slide
                ageCarousel.next();
            }
            if (touchEndX > touchStartX + 50) {
                // Swipe right, go to previous slide
                ageCarousel.prev();
            }
        }
        
        // Also make clicking on the card navigate to next slide
        const carouselItems = document.querySelectorAll('.carousel-item .age-card');
        carouselItems.forEach(item => {
            item.addEventListener('click', function() {
                ageCarousel.next();
            });
        });
        
        // Initialize life counter
        const birthDate = new Date('<?php echo $birthday_data['birthday']; ?>');
        updateAgeCounter(birthDate);
        
        // Update counter every second
        setInterval(function() {
            updateAgeCounter(birthDate);
        }, 1000);
        
        // Initialize motivational messages
        updateMotivationalMessages();
        
        // Update messages every 5 seconds (separate from counter)
        setInterval(function() {
            updateMotivationalMessages();
        }, 5000);
        
        // Function to update age counter
        function updateAgeCounter(birthDate) {
            // Get current date/time in London time zone
            const now = new Date();
            const londonOptions = { timeZone: 'Europe/London' };
            
            // Get London time components as strings
            const londonTimeStr = now.toLocaleString('en-US', londonOptions);
            // Parse London time back to Date object
            const londonTime = new Date(londonTimeStr);
            
            // Calculate difference in milliseconds from birth date to now (London time)
            const diffMs = londonTime - birthDate;
            
            // Convert to relevant units
            const years = Math.floor(diffMs / (1000 * 60 * 60 * 24 * 365.25));
            
            // Calculate total months lived
            let totalMonths = (londonTime.getFullYear() - birthDate.getFullYear()) * 12;
            totalMonths -= birthDate.getMonth();
            totalMonths += londonTime.getMonth();
            
            // Adjust if we haven't reached the same day of month yet
            if (londonTime.getDate() < birthDate.getDate()) {
                totalMonths--;
            }
            
            // Calculate years and remaining months
            const remainingMonths = totalMonths % 12;
            
            // Calculate days since last "month birthday"
            // Create a date for when the person turned exactly X years and Y months old
            let monthBirthday;
            if (birthDate.getDate() > 28) {
                // Handle edge cases for month end dates (28/29/30/31)
                // Find the last day of the target month
                const targetMonth = new Date(londonTime.getFullYear(), londonTime.getMonth() + (londonTime.getDate() < birthDate.getDate() ? 0 : 1), 0);
                const lastDayOfMonth = targetMonth.getDate();
                const birthDay = Math.min(birthDate.getDate(), lastDayOfMonth);
                
                monthBirthday = new Date(londonTime.getFullYear(), londonTime.getMonth() + (londonTime.getDate() < birthDate.getDate() ? -1 : 0), birthDay);
            } else {
                // Normal case - use exact day of birth
                monthBirthday = new Date(londonTime.getFullYear(), londonTime.getMonth() + (londonTime.getDate() < birthDate.getDate() ? -1 : 0), birthDate.getDate());
            }
            
            // Calculate days since that date
            const daysSinceMonthBirthday = Math.floor((londonTime - monthBirthday) / (1000 * 60 * 60 * 24));
            
            // Calculate hours, minutes, seconds since midnight
            const seconds = londonTime.getSeconds();
            const minutes = londonTime.getMinutes();
            const hours = londonTime.getHours();
            
            // Calculate total days, weeks for second slide
            const totalDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));
            const weeks = Math.floor(totalDays / 7);
            
            // Format with leading zeros
            const formattedHours = hours.toString().padStart(2, '0');
            const formattedMinutes = minutes.toString().padStart(2, '0');
            const formattedSeconds = seconds.toString().padStart(2, '0');
            const formattedDays = String(daysSinceMonthBirthday).padStart(2, '0');
            
            // Update first slide: Simple age format with live counter
            document.getElementById('age-text-simple').textContent = 
                `${years} years, ${remainingMonths} months`;
            document.getElementById('days-small').textContent = formattedDays;
            document.getElementById('hours-small').textContent = formattedHours;
            document.getElementById('minutes-small').textContent = formattedMinutes;
            document.getElementById('seconds-small').textContent = formattedSeconds;
            
            // Update second slide: All totals
            document.getElementById('total-years').textContent = years;
            document.getElementById('total-months').textContent = totalMonths;
            document.getElementById('total-weeks').textContent = weeks;
            document.getElementById('total-days').textContent = totalDays;
            document.getElementById('hours').textContent = formattedHours;
            document.getElementById('minutes').textContent = formattedMinutes;
            document.getElementById('seconds').textContent = formattedSeconds;
        }
        
        // Function to update motivational messages - separate from counter updates
        function updateMotivationalMessages() {
            // Messages array with Bible verses and inspirational quotes
            const messages = [
                // Bible verses
                "Ephesians 5:16 - Making the most of every opportunity, because the days are evil.",
                "Psalm 90:12 - Teach us to number our days, that we may gain a heart of wisdom.",
                "Colossians 4:5 - Be wise in the way you act toward outsiders; make the most of every opportunity.",
                "Proverbs 27:1 - Do not boast about tomorrow, for you do not know what a day may bring.",
                "James 4:14 - What is your life? You are a mist that appears for a little while and then vanishes.",
                "2 Corinthians 6:2 - Now is the time of God's favor, now is the day of salvation.",
                "Hebrews 3:15 - Today, if you hear his voice, do not harden your hearts.",
                "Matthew 6:34 - Do not worry about tomorrow, for tomorrow will worry about itself.",
                "Psalm 118:24 - This is the day the Lord has made; let us rejoice and be glad in it.",
                "Proverbs 6:4 - Don't put it off; do it now! Don't rest until you do.",
                "John 9:4 - As long as it is day, we must do the works of him who sent me.",
                "Ecclesiastes 9:10 - Whatever your hand finds to do, do it with all your might.",
                "Luke 12:40 - You also must be ready, because the Son of Man will come at an hour you do not expect.",
                
                // Inspirational quotes without attribution
                "Lost time is never found again.",
                "Time is what we want most, but what we use worst.",
                "Time waits for no one.",
                "The way we spend our time defines who we are.",
                "Either you run the day or the day runs you.",
                "Time is precious. Waste it wisely.",
                "You may delay, but time will not.",
                "Don't wait. The time will never be just right.",
                "The future depends on what you do today.",
                "Do it now. Sometimes 'later' becomes 'never'.",
                "Yesterday is gone. Tomorrow has not yet come. We have only today.",
                "Act now. There is never any time but now.",
                "Your life is happening right now: act accordingly.",
                "Be here now.",
                "Don't count the days, make the days count.",
                "Life is available only in the present moment.",
                "The best time to start was yesterday. The next best time is now.",
                "One day, or day one. You decide.",
                "If not now, then when?",
                "Action is the foundational key to all success.",
                "Seize the day.",
                "Live now, procrastinate later.",
                "Today is the first day of the rest of your life.",
                "The time for action is now. It's never too late to do something.",
                "Now is the only time you own.",
                "You are what you do, not what you'll say you'll do.",
                "Life is short. Do stuff that matters.",
                "Someday is not a day of the week."
            ];
            
            // Get two random indices
            let randomIndex1 = Math.floor(Math.random() * messages.length);
            let randomIndex2 = Math.floor(Math.random() * messages.length);
            // Make sure the second message is different from the first
            while (randomIndex2 === randomIndex1) {
                randomIndex2 = Math.floor(Math.random() * messages.length);
            }
            
            // Helper function to format messages (separate Bible verses from references)
            const formatMessage = (message) => {
                // Check if this is a Bible verse by looking for the pattern "Book Chapter:Verse -"
                const bibleVersePattern = /^([0-9]?\s?[A-Za-z]+\s?[A-Za-z]*\s[0-9]+:[0-9]+)\s-\s(.+)$/;
                const match = message.match(bibleVersePattern);
                
                if (match) {
                    // This is a Bible verse, format with reference on new line
                    const reference = match[1]; // The Bible book, chapter and verse
                    const verseText = match[2]; // The actual verse text
                    return `${verseText}<br><span class="verse-reference">${reference}</span>`;
                } else {
                    // This is a regular quote, return as is
                    return message;
                }
            };
            
            // Set messages for the two slides with formatted text
            const messageElement1 = document.getElementById('motivation-message-primary');
            const messageElement2 = document.getElementById('motivation-message-secondary');
            
            messageElement1.innerHTML = `<span class="motivational-text">${formatMessage(messages[randomIndex1])}</span>`;
            messageElement2.innerHTML = `<span class="motivational-text">${formatMessage(messages[randomIndex2])}</span>`;
        }
        
        // Add styles for the motivational messages
        const styleElement = document.createElement('style');
        styleElement.textContent = `
            .motivational-text {
                font-weight: bold;
                transition: color 0.5s ease;
                line-height: 1.4;
            }
            
            .verse-reference {
                font-size: 0.9em;
                opacity: 0.9;
                display: inline-block;
                margin-top: 4px;
            }
            
            /* Different colors for different message types */
            .motivational-text:nth-of-type(4n+1) {
                color: #e67e22; /* Orange */
            }
            .motivational-text:nth-of-type(4n+2) {
                color: #3498db; /* Blue */
            }
            .motivational-text:nth-of-type(4n+3) {
                color: #2ecc71; /* Green */
            }
            .motivational-text:nth-of-type(4n+4) {
                color: #9b59b6; /* Purple */
            }
        `;
        document.head.appendChild(styleElement);
        <?php endif; ?>
        
        // Toggle FAB options
        document.getElementById('fabButton').addEventListener('click', function() {
            const fabOptions = document.getElementById('fabOptions');
            fabOptions.classList.toggle('show');
            
            // Change icon based on state
            const icon = this.querySelector('i');
            if (fabOptions.classList.contains('show')) {
                icon.classList.remove('fa-plus');
                icon.classList.add('fa-times');
            } else {
                icon.classList.remove('fa-times');
                icon.classList.add('fa-plus');
            }
        });
        
        // Close FAB options when clicking outside
        document.addEventListener('click', function(event) {
            const fabButton = document.getElementById('fabButton');
            const fabOptions = document.getElementById('fabOptions');
            
            if (!fabButton.contains(event.target) && !fabOptions.contains(event.target) && fabOptions.classList.contains('show')) {
                fabOptions.classList.remove('show');
                const icon = fabButton.querySelector('i');
                icon.classList.remove('fa-times');
                icon.classList.add('fa-plus');
            }
        });
        
        // Task form submission handling
        document.addEventListener('DOMContentLoaded', function() {
            // Set default due date to today
            document.getElementById('due_date').valueAsDate = new Date();
            
            // No AJAX submission - letting the form submit normally
        });
    });
</script>

<?php
include '../includes/footer.php';
close_connection($conn);
?>
