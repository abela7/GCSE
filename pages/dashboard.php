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
                            
                            <!-- Age Counter Carousel -->
                            <div id="ageCounterCarousel" class="carousel slide" data-bs-ride="false" data-bs-touch="true" data-bs-interval="false">
                                <div class="carousel-inner">
                                    <!-- First Slide: Years and Months -->
                                    <div class="carousel-item active">
                                        <div class="age-card d-flex flex-column align-items-center p-4">
                                            <div class="d-flex justify-content-center align-items-center mb-3">
                                                <div class="text-center px-3">
                                                    <div id="years-primary" class="display-1 fw-bold" style="color: var(--accent-color);">--</div>
                                                    <div class="fs-5 text-muted">Years</div>
                                                </div>
                                                <div class="text-center px-3">
                                                    <div id="months-remainder" class="display-1 fw-bold" style="color: var(--accent-color);">--</div>
                                                    <div class="fs-5 text-muted">Months</div>
                                                </div>
                                            </div>
                                            <div class="card-indicator mb-4">
                                                <span class="active"></span>
                                                <span></span>
                                                <span></span>
                                                <span></span>
                                            </div>
                                            <div class="text-center">
                                                <h4 id="age-text-primary" class="mb-1">--</h4>
                                                <p class="fst-italic" id="motivation-message-primary">Are you using your time properly?</p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Second Slide: Total Months -->
                                    <div class="carousel-item">
                                        <div class="age-card d-flex flex-column align-items-center p-4">
                                            <div class="d-flex justify-content-center align-items-center mb-3">
                                                <div class="text-center px-4">
                                                    <div id="total-months" class="display-1 fw-bold" style="color: var(--accent-color);">--</div>
                                                    <div class="fs-5 text-muted">Total Months</div>
                                                </div>
                                            </div>
                                            <div class="card-indicator mb-4">
                                                <span></span>
                                                <span class="active"></span>
                                                <span></span>
                                                <span></span>
                                            </div>
                                            <div class="text-center">
                                                <h4 id="age-text-secondary" class="mb-1">--</h4>
                                                <p class="fst-italic" id="motivation-message-secondary">Make every day count!</p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Third Slide: Total Weeks -->
                                    <div class="carousel-item">
                                        <div class="age-card d-flex flex-column align-items-center p-4">
                                            <div class="d-flex justify-content-center align-items-center mb-3">
                                                <div class="text-center px-4">
                                                    <div id="total-weeks" class="display-1 fw-bold" style="color: var(--accent-color);">--</div>
                                                    <div class="fs-5 text-muted">Total Weeks</div>
                                                </div>
                                            </div>
                                            <div class="card-indicator mb-4">
                                                <span></span>
                                                <span></span>
                                                <span class="active"></span>
                                                <span></span>
                                            </div>
                                            <div class="text-center">
                                                <h4 id="age-text-tertiary" class="mb-1">--</h4>
                                                <p class="fst-italic" id="motivation-message-tertiary">Your future is created by what you do today.</p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Fourth Slide: Total Days and Hours -->
                                    <div class="carousel-item">
                                        <div class="age-card d-flex flex-column align-items-center p-4">
                                            <div class="time-counter d-flex justify-content-center mb-3">
                                                <div class="time-unit text-center">
                                                    <div id="total-days" class="display-2 fw-bold" style="color: var(--accent-color);">--</div>
                                                    <div class="fs-5 text-muted">Days</div>
                                                </div>
                                            </div>
                                            <div class="live-counter d-flex justify-content-center mb-3">
                                                <div class="time-box">
                                                    <span id="hours">--</span>
                                                    <div class="small">HOURS</div>
                                                </div>
                                                <div class="time-separator">:</div>
                                                <div class="time-box">
                                                    <span id="minutes">--</span>
                                                    <div class="small">MINUTES</div>
                                                </div>
                                                <div class="time-separator">:</div>
                                                <div class="time-box">
                                                    <span id="seconds">--</span>
                                                    <div class="small">SECONDS</div>
                                                </div>
                                            </div>
                                            <div class="card-indicator mb-4">
                                                <span></span>
                                                <span></span>
                                                <span></span>
                                                <span class="active"></span>
                                            </div>
                                            <div class="text-center">
                                                <h4 id="age-text-quaternary" class="mb-1">--</h4>
                                                <p class="fst-italic" id="motivation-message-quaternary">Today is a gift. That's why it's called the present.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="p-3 bg-light d-flex justify-content-between align-items-center">
                                <div class="small text-muted">
                                    <i class="fas fa-birthday-cake me-1"></i> Born on <?php echo date('F j, Y', strtotime($birthday_data['birthday'])); ?>
                                </div>
                                <a href="settings/birthday.php" class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-edit me-1"></i> Edit Birthday
                                </a>
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
                                    transition: transform 0.6s ease-in-out;
                                }
                                .time-counter {
                                    width: 100%;
                                }
                                .time-unit {
                                    padding: 0 1rem;
                                }
                                .live-counter {
                                    background-color: var(--accent-color-dark);
                                    color: white;
                                    padding: 1rem;
                                    border-radius: 8px;
                                    margin-top: 0.5rem;
                                }
                                .time-box {
                                    text-align: center;
                                    padding: 0 0.5rem;
                                }
                                .time-box span {
                                    font-size: 2rem;
                                    font-weight: bold;
                                    display: block;
                                }
                                .time-separator {
                                    font-size: 2rem;
                                    font-weight: bold;
                                    padding: 0 0.25rem;
                                    align-self: flex-start;
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
        <div class="col-md-3 col-sm-6 mb-4">
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
        <div class="col-md-3 col-sm-6 mb-4">
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
        <div class="col-md-3 col-sm-6 mb-4">
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
        <div class="col-md-3 col-sm-6 mb-4">
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
        
        // Function to update age counter
        function updateAgeCounter(birthDate) {
            const now = new Date();
            
            // Calculate difference in milliseconds
            const diffMs = now - birthDate;
            
            // Convert to relevant units
            const years = Math.floor(diffMs / (1000 * 60 * 60 * 24 * 365.25));
            
            let months = (now.getFullYear() - birthDate.getFullYear()) * 12;
            months -= birthDate.getMonth();
            months += now.getMonth();
            if (now.getDate() < birthDate.getDate()) {
                months--;
            }
            
            const totalDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));
            const weeks = Math.floor(totalDays / 7);
            
            // Calculate hours, minutes, seconds
            const hours = Math.floor((diffMs % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((diffMs % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((diffMs % (1000 * 60)) / 1000);
            
            // Calculate remaining months and days for readable text
            const remainingMonths = months % 12;
            const remainingDays = Math.floor((now - new Date(now.getFullYear(), now.getMonth() - remainingMonths, birthDate.getDate())) / (1000 * 60 * 60 * 24));
            
            // Format with leading zeros
            const formattedHours = hours.toString().padStart(2, '0');
            const formattedMinutes = minutes.toString().padStart(2, '0');
            const formattedSeconds = seconds.toString().padStart(2, '0');
            
            // Update first slide: Years and Months
            document.getElementById('years-primary').textContent = years;
            document.getElementById('months-remainder').textContent = remainingMonths;
            document.getElementById('age-text-primary').textContent = 
                `You are ${years} years, ${remainingMonths} months, and ${remainingDays} days old`;
            
            // Update second slide: Total Months
            document.getElementById('total-months').textContent = months;
            document.getElementById('age-text-secondary').textContent = 
                `You have lived for ${months} months`;
            
            // Update third slide: Total Weeks
            document.getElementById('total-weeks').textContent = weeks;
            document.getElementById('age-text-tertiary').textContent = 
                `You have lived for ${weeks} weeks`;
            
            // Update fourth slide: Total Days and live counter
            document.getElementById('total-days').textContent = totalDays;
            document.getElementById('hours').textContent = formattedHours;
            document.getElementById('minutes').textContent = formattedMinutes;
            document.getElementById('seconds').textContent = formattedSeconds;
            document.getElementById('age-text-quaternary').textContent = 
                `You have lived for ${totalDays} days, ${formattedHours} hours, ${formattedMinutes} minutes`;
            
            // Update motivation messages
            const messages = [
                "Are you using your time properly?",
                "Make every day count!",
                "Today is a gift. That's why it's called the present.",
                "Focus on what matters most today.",
                "Your future is created by what you do today.",
                "Excellence is not a skill. It's an attitude."
            ];
            
            // Different message for each slide (or use index to cycle through on a timer)
            document.getElementById('motivation-message-primary').textContent = messages[0];
            document.getElementById('motivation-message-secondary').textContent = messages[1];
            document.getElementById('motivation-message-tertiary').textContent = messages[4];
            document.getElementById('motivation-message-quaternary').textContent = messages[2];
        }
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
