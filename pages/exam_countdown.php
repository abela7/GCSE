<?php
// Set page title
$page_title = "Exam Countdown";

// Set breadcrumbs
$breadcrumbs = [
    'Dashboard' => 'dashboard.php',
    'Exam Countdown' => null
];

// Set page actions
$page_actions = '
<a href="exams/create.php" class="btn btn-primary btn-sm">
    <i class="fas fa-plus me-1"></i> Add Exam
</a>
';

// Include database connection
require_once '../config/db_connect.php';

// Check if exams table exists, create if not
$check_table_query = "SHOW TABLES LIKE 'exams'";
$table_result = $conn->query($check_table_query);

if ($table_result->num_rows == 0) {
    // Create exams table if it doesn't exist
    $create_table_query = "CREATE TABLE exams (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        subject_id INT NOT NULL,
        exam_date DATE NOT NULL,
        exam_time TIME NOT NULL,
        description TEXT,
        location VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (subject_id) REFERENCES subjects(id)
    )";
    $conn->query($create_table_query);
}

// Get all exams ordered by date
$exams_query = "SELECT e.*, s.name as subject_name, s.color as subject_color 
                FROM exams e 
                JOIN subjects s ON e.subject_id = s.id 
                ORDER BY e.exam_date ASC, e.exam_time ASC";
$exams_result = $conn->query($exams_query);

// Include header
include '../includes/header.php';

// Define the accent color
$accent_color = "#cdaf56";
?>

<style>
.exam-card {
    border: none;
    border-radius: 10px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    margin-bottom: 1.5rem;
    overflow: hidden;
}

.exam-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.exam-header {
    padding: 1rem;
    border-bottom: 1px solid rgba(0,0,0,0.05);
    background-color: white;
}

.exam-body {
    padding: 1.5rem;
}

.exam-title {
    font-size: 1.2rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: #333;
}

.countdown-time {
    font-size: 2.5rem;
    font-weight: 700;
    text-align: center;
    margin-bottom: 0.5rem;
    color: #333;
}

.countdown-label {
    font-size: 0.875rem;
    color: #6c757d;
    text-align: center;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.exam-details {
    margin-top: 1.5rem;
}

.detail-item {
    display: flex;
    margin-bottom: 0.75rem;
}

.detail-icon {
    width: 24px;
    margin-right: 1rem;
    color: <?php echo $accent_color; ?>;
}

.detail-text {
    flex: 1;
}

.badge-upcoming {
    background-color: <?php echo $accent_color; ?>;
    color: white;
}

.badge-today {
    background-color: #dc3545;
    color: white;
}

.badge-past {
    background-color: #6c757d;
    color: white;
}

.no-exams-container {
    text-align: center;
    padding: 3rem 0;
}

.no-exams-icon {
    font-size: 3rem;
    color: #e9ecef;
    margin-bottom: 1rem;
}

.countdown-container {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 10px;
    text-align: center;
    margin: 1.5rem 0;
}

.countdown-item {
    background-color: #f8f9fa;
    border-radius: 8px;
    padding: 1rem 0.5rem;
}

@media (max-width: 767.98px) {
    .countdown-time {
        font-size: 2rem;
    }
    
    .countdown-label {
        font-size: 0.75rem;
    }
}
</style>

<div class="container py-4">
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['success']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['error']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
    
    <?php if ($exams_result && $exams_result->num_rows > 0): ?>
        <h4 class="mb-4">Upcoming Exams</h4>
        
        <div class="row">
            <?php 
            $now = new DateTime();
            $today_date = $now->format('Y-m-d');
            
            while ($exam = $exams_result->fetch_assoc()): 
                // Create DateTime object for the exam date and time
                $exam_datetime = new DateTime($exam['exam_date'] . ' ' . $exam['exam_time']);
                $interval = $now->diff($exam_datetime);
                
                // Calculate days, hours, minutes remaining
                $days_remaining = $interval->format('%a');
                $hours_remaining = $interval->format('%h');
                $minutes_remaining = $interval->format('%i');
                
                // Set status badge class
                if ($exam['exam_date'] < $today_date) {
                    $status_class = "badge-past";
                    $status_text = "Past";
                } elseif ($exam['exam_date'] == $today_date) {
                    $status_class = "badge-today";
                    $status_text = "Today";
                } else {
                    $status_class = "badge-upcoming";
                    $status_text = "Upcoming";
                }
            ?>
            <div class="col-md-6 col-lg-4">
                <div class="exam-card">
                    <div class="exam-header d-flex justify-content-between align-items-center">
                        <span class="badge me-2" style="background-color: <?php echo $exam['subject_color']; ?>">
                            <?php echo htmlspecialchars($exam['subject_name']); ?>
                        </span>
                        <span class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                    </div>
                    <div class="exam-body">
                        <div class="exam-title"><?php echo htmlspecialchars($exam['title']); ?></div>
                        
                        <?php if ($exam['exam_date'] >= $today_date): ?>
                            <div class="countdown-container">
                                <div class="countdown-item">
                                    <div class="countdown-time"><?php echo $days_remaining; ?></div>
                                    <div class="countdown-label">Days</div>
                                </div>
                                <div class="countdown-item">
                                    <div class="countdown-time"><?php echo $hours_remaining; ?></div>
                                    <div class="countdown-label">Hours</div>
                                </div>
                                <div class="countdown-item">
                                    <div class="countdown-time"><?php echo $minutes_remaining; ?></div>
                                    <div class="countdown-label">Mins</div>
                                </div>
                                <div class="countdown-item">
                                    <div class="countdown-time">
                                        <?php echo $interval->invert ? "" : "⏰"; ?>
                                    </div>
                                    <div class="countdown-label">Remaining</div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="exam-details">
                            <div class="detail-item">
                                <div class="detail-icon">
                                    <i class="fas fa-calendar-day"></i>
                                </div>
                                <div class="detail-text">
                                    <?php echo date('l, j F Y', strtotime($exam['exam_date'])); ?>
                                </div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="detail-text">
                                    <?php echo date('g:i A', strtotime($exam['exam_time'])); ?>
                                </div>
                            </div>
                            <?php if (!empty($exam['location'])): ?>
                                <div class="detail-item">
                                    <div class="detail-icon">
                                        <i class="fas fa-map-marker-alt"></i>
                                    </div>
                                    <div class="detail-text">
                                        <?php echo htmlspecialchars($exam['location']); ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($exam['description'])): ?>
                                <div class="detail-item">
                                    <div class="detail-icon">
                                        <i class="fas fa-info-circle"></i>
                                    </div>
                                    <div class="detail-text">
                                        <?php echo htmlspecialchars($exam['description']); ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="d-flex justify-content-end mt-3">
                            <a href="exams/edit.php?id=<?php echo $exam['id']; ?>" class="btn btn-sm btn-outline-secondary me-2">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="exams/delete.php?id=<?php echo $exam['id']; ?>" class="btn btn-sm btn-outline-danger" 
                               onclick="return confirm('Are you sure you want to delete this exam?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="no-exams-container">
            <div class="no-exams-icon">
                <i class="fas fa-calendar-times"></i>
            </div>
            <h4>No Exams Found</h4>
            <p class="text-muted mb-4">Add your upcoming exams to keep track of them.</p>
            <a href="exams/create.php" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i> Add Your First Exam
            </a>
        </div>
    <?php endif; ?>
</div>

<?php
include '../includes/footer.php';
close_connection($conn);
?> 