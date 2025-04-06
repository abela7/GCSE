<?php
// Include database connection
require_once '../config/db_connect.php';

// Get subjects
$subjects_query = "SELECT * FROM subjects ORDER BY name ASC";
$subjects_result = $conn->query($subjects_query);

// Include header
include '../includes/header.php';
?>

<div class="container-fluid px-4 pt-2 pb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">My Subjects</h2>
            <p class="text-muted mb-0">Track your GCSE progress</p>
        </div>
        <div class="d-flex align-items-center">
            <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-2">
                <i class="fas fa-graduation-cap me-2"></i>GCSE 2025
            </span>
        </div>
    </div>

    <div class="row g-4">
        <?php if ($subjects_result->num_rows > 0): 
            while ($subject = $subjects_result->fetch_assoc()): 
                // Get progress statistics
                $total_subtopics = 0;
                $completed_subtopics = 0;
                $in_progress_subtopics = 0;
                
                if ($subject['name'] == 'English') {
                    $progress_query = "SELECT COUNT(*) as total FROM eng_topics";
                    $completed_query = "SELECT COUNT(*) as completed FROM eng_topic_progress WHERE status = 'completed'";
                    $in_progress_query = "SELECT COUNT(*) as in_progress FROM eng_topic_progress WHERE status = 'in_progress'";
                } else if ($subject['name'] == 'Math') {
                    $progress_query = "SELECT COUNT(*) as total FROM math_topics";
                    $completed_query = "SELECT COUNT(*) as completed FROM topic_progress WHERE status = 'completed'";
                    $in_progress_query = "SELECT COUNT(*) as in_progress FROM topic_progress WHERE status = 'in_progress'";
                }
                
                $total_result = $conn->query($progress_query);
                $completed_result = $conn->query($completed_query);
                $in_progress_result = $conn->query($in_progress_query);
                
                if ($total_result && $completed_result && $in_progress_result) {
                    $total_subtopics = $total_result->fetch_assoc()['total'];
                    $completed_subtopics = $completed_result->fetch_assoc()['completed'];
                    $in_progress_subtopics = $in_progress_result->fetch_assoc()['in_progress'];
                }
                
                $progress_percentage = ($total_subtopics > 0) ? round(($completed_subtopics / $total_subtopics) * 100) : 0;
                
                // Get all exams for this subject
                $exams_query = "SELECT * FROM exams WHERE subject_id = " . $subject['id'] . " ORDER BY exam_date ASC";
                $exams_result = $conn->query($exams_query);

                // Get recent study sessions
                $sessions_query = "SELECT * FROM sessions WHERE subject_id = " . $subject['id'] . " ORDER BY date DESC LIMIT 3";
                $sessions_result = $conn->query($sessions_query);
        ?>
        <div class="col-md-6">
            <div class="card h-100 shadow-hover">
                <div class="card-header position-relative p-4" 
                     style="background: linear-gradient(135deg, <?php echo $subject['color']; ?>, <?php echo adjustBrightness($subject['color'], -30); ?>);">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <i class="fas <?php echo $subject['name'] == 'English' ? 'fa-book' : 'fa-calculator'; ?> fa-2x text-white me-3"></i>
                            <div>
                                <h3 class="h4 text-white mb-0"><?php echo htmlspecialchars($subject['name']); ?></h3>
                                <span class="text-white-50"><?php echo $total_subtopics; ?> topics</span>
                            </div>
                        </div>
                        <div class="progress-circle" data-value="<?php echo $progress_percentage; ?>">
                            <span class="progress-circle-value"><?php echo $progress_percentage; ?>%</span>
                        </div>
                    </div>
                </div>
                
                <div class="card-body p-4">
                    <!-- Progress Section -->
                    <div class="progress-details mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="small">
                                <span class="text-success"><i class="fas fa-check-circle me-1"></i><?php echo $completed_subtopics; ?> completed</span>
                                <span class="mx-2">•</span>
                                <span class="text-warning"><i class="fas fa-clock me-1"></i><?php echo $in_progress_subtopics; ?> in progress</span>
                            </div>
                        </div>
                        <div class="progress rounded-pill" style="height: 6px;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                 style="width: <?php echo $progress_percentage; ?>%; background-color: <?php echo $subject['color']; ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Exams Section -->
                    <?php if ($exams_result->num_rows > 0): ?>
                    <div class="exams-timeline mb-4">
                        <h6 class="text-uppercase text-muted small mb-3">Upcoming Exams</h6>
                        <div class="timeline">
                            <?php 
                            $now = new DateTime();
                            while ($exam = $exams_result->fetch_assoc()): 
                                $exam_date = new DateTime($exam['exam_date']);
                                $interval = $now->diff($exam_date);
                                $days_remaining = $interval->days;
                                $hours = floor($exam['duration'] / 60);
                                $minutes = $exam['duration'] % 60;
                                $duration = ($hours > 0 ? $hours . 'h ' : '') . ($minutes > 0 ? $minutes . 'm' : '');
                            ?>
                            <a href="/GCSE/pages/exam_details.php?id=<?php echo $exam['id']; ?>" class="text-decoration-none">
                                <div class="timeline-item">
                                    <div class="d-flex align-items-start mb-3">
                                        <div class="calendar-icon me-3 text-center">
                                            <div class="small text-uppercase text-primary fw-bold"><?php echo date('M', strtotime($exam['exam_date'])); ?></div>
                                            <div class="h4 mb-0"><?php echo date('d', strtotime($exam['exam_date'])); ?></div>
                                            <div class="small text-muted"><?php echo date('D', strtotime($exam['exam_date'])); ?></div>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1 fw-bold text-dark"><?php echo htmlspecialchars($exam['title']); ?></h6>
                                            <div class="d-flex align-items-center text-muted small mb-2">
                                                <i class="far fa-clock me-2"></i><?php echo date('g:i A', strtotime($exam['exam_date'])); ?>
                                                <span class="mx-2">•</span>
                                                <i class="fas fa-hourglass-half me-2"></i><?php echo $duration; ?>
                                                <?php if ($exam['location']): ?>
                                                    <span class="mx-2">•</span>
                                                    <i class="fas fa-map-marker-alt me-2"></i><?php echo htmlspecialchars($exam['location']); ?>
                                                <?php endif; ?>
                                            </div>
                                            <div class="d-flex align-items-center">
                                                <span class="badge bg-primary-subtle text-primary me-2"><?php echo htmlspecialchars($exam['paper_code']); ?></span>
                                                <span class="badge bg-secondary-subtle text-secondary me-2"><?php echo htmlspecialchars($exam['exam_board']); ?></span>
                                                <span class="badge bg-<?php echo $days_remaining <= 7 ? 'danger' : ($days_remaining <= 14 ? 'warning' : 'info'); ?>-subtle text-<?php echo $days_remaining <= 7 ? 'danger' : ($days_remaining <= 14 ? 'warning' : 'info'); ?>"><?php echo $days_remaining; ?> days left</span>
                                            </div>
                                        </div>
                                        <div class="ms-2">
                                            <i class="fas fa-chevron-right text-muted"></i>
                                        </div>
                                    </div>
                                </div>
                            </a>
                            <?php endwhile; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Recent Sessions -->
                    <?php if ($sessions_result->num_rows > 0): ?>
                    <div class="recent-sessions">
                        <h6 class="text-uppercase text-muted small mb-3">Recent Sessions</h6>
                        <div class="timeline">
                            <?php while ($session = $sessions_result->fetch_assoc()): ?>
                            <div class="timeline-item">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="small text-muted"><?php echo date('M d', strtotime($session['date'])); ?></span>
                                    <span class="badge bg-light text-dark rounded-pill"><?php echo $session['duration']; ?>m</span>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="card-footer bg-transparent border-0 p-4">
                    <a href="/GCSE/pages/subjects/<?php echo strtolower($subject['name']); ?>.php" 
                       class="btn btn-primary w-100 rounded-pill">
                        View Details <i class="fas fa-arrow-right ms-2"></i>
                    </a>
                </div>
            </div>
        </div>
        <?php 
            endwhile;
        else: ?>
        <div class="col-12">
            <div class="alert alert-info d-flex align-items-center">
                <i class="fas fa-info-circle me-3"></i>
                No subjects found. Please contact your administrator.
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
.shadow-hover {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border: none;
    border-radius: 20px;
    overflow: hidden;
}

.shadow-hover:hover {
    transform: translateY(-5px);
    box-shadow: 0 1rem 3rem rgba(0,0,0,.175);
}

.card-header {
    border-bottom: none;
    border-radius: 20px 20px 0 0 !important;
}

.progress-circle {
    position: relative;
    width: 60px;
    height: 60px;
}

.progress-circle-value {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 14px;
    font-weight: bold;
    color: white;
}

.calendar-icon {
    background: white;
    border-radius: 12px;
    padding: 8px 12px;
    min-width: 60px;
    text-align: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.timeline {
    position: relative;
}

.timeline-item {
    position: relative;
    padding-bottom: 1rem;
    border-left: 2px solid #e9ecef;
    padding-left: 1.5rem;
}

.timeline-item:last-child {
    padding-bottom: 0;
    border-left: none;
}

.timeline-item::before {
    content: '';
    position: absolute;
    left: -6px;
    top: 0;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: #e9ecef;
}

.exams-timeline .timeline {
    position: relative;
    padding-left: 0;
}

.exams-timeline .timeline-item {
    position: relative;
    padding: 1rem;
    background: #fff;
    border-radius: 0.5rem;
    margin-bottom: 1rem;
    border: 1px solid rgba(0,0,0,.05);
    transition: all 0.3s ease;
}

.exams-timeline .timeline-item:hover {
    transform: translateX(5px);
    box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,.075);
    background-color: rgba(var(--bs-primary-rgb), 0.01);
}

.exams-timeline .calendar-icon {
    background: rgba(var(--bs-primary-rgb), 0.1);
    padding: 0.5rem;
    border-radius: 0.5rem;
    min-width: 70px;
    transition: all 0.3s ease;
}

.exams-timeline .timeline-item:hover .calendar-icon {
    background: rgba(var(--bs-primary-rgb), 0.15);
}

.exams-timeline .calendar-icon .h4 {
    color: var(--bs-primary);
    line-height: 1;
}

.exams-timeline a:hover {
    text-decoration: none;
}

.exams-timeline .timeline-item:hover .fa-chevron-right {
    transform: translateX(3px);
}

.exams-timeline .fa-chevron-right {
    transition: transform 0.3s ease;
}

.btn-primary {
    padding: 0.8rem 1.5rem;
    font-weight: 500;
}

.badge {
    font-weight: 500;
}

/* Badge Styles */
.badge.bg-primary-subtle {
    background-color: rgba(var(--bs-primary-rgb), 0.1) !important;
}

.badge.bg-secondary-subtle {
    background-color: rgba(var(--bs-secondary-rgb), 0.1) !important;
}

.badge.bg-danger-subtle {
    background-color: rgba(var(--bs-danger-rgb), 0.1) !important;
}

.badge.bg-warning-subtle {
    background-color: rgba(var(--bs-warning-rgb), 0.1) !important;
}

.badge.bg-info-subtle {
    background-color: rgba(var(--bs-info-rgb), 0.1) !important;
}

/* Progress Circle Animation */
@keyframes progress-circle {
    from { stroke-dashoffset: 0; }
}
</style>

<?php
// Include footer
include '../includes/footer.php';

// Close database connection
close_connection($conn);

// Helper function to adjust color brightness
function adjustBrightness($hex, $steps) {
    $hex = ltrim($hex, '#');
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));

    $r = max(0, min(255, $r + $steps));
    $g = max(0, min(255, $g + $steps));
    $b = max(0, min(255, $b + $steps));

    return sprintf("#%02x%02x%02x", $r, $g, $b);
}
?>