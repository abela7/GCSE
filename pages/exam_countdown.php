<?php
// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
require_once '../config/db_connect.php';

// Set timezone to London
date_default_timezone_set('Europe/London');

// Hide the page title
$hide_page_title = true;

// Get all upcoming exams
$exams_query = "SELECT e.*, s.name as subject_name, s.color as subject_color 
                FROM exams e 
                JOIN subjects s ON e.subject_id = s.id 
                WHERE e.exam_date > NOW() 
                ORDER BY e.exam_date ASC";
$exams_result = $conn->query($exams_query);

// Include header
include '../includes/header.php';
?>

<style>
/* Tab Navigation */
.nav-container {
    max-width: 1400px;
    margin: 1rem auto 0;
    padding: 0 1rem;
}

.nav-tabs {
    display: flex;
    gap: 0.75rem;
    border: none;
    margin-bottom: 1.5rem;
}

.nav-link {
    padding: 0.5rem 1.5rem;
    border-radius: 2rem;
    font-weight: 600;
    font-size: 0.9rem;
    color: #333;
    border: none;
    transition: transform 0.2s ease;
}

.nav-link:hover {
    transform: translateY(-2px);
}

.nav-link.active.maths-tab {
    background: #DAA520;
    color: #333;
}

.nav-link.active.english-tab {
    background: #28a745;
    color: white;
}

/* Grid Layout */
.countdown-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.25rem;
    padding: 1rem;
    max-width: 1400px;
    margin: 0 auto;
}

.countdown-card {
    background: #ffffff;
    border-radius: 1rem;
    box-shadow: 0 2px 15px rgba(0,0,0,0.06);
    padding: 1.5rem;
    transition: transform 0.2s ease;
    border: 1px solid #ff4444;
    position: relative;
}

.countdown-card:hover {
    transform: translateY(-3px);
}

/* Header Section */
.subject-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1.5rem;
}

.subject-badge {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: #DAA520;
    padding: 0.5rem 1rem;
    border-radius: 2rem;
    font-weight: 600;
    font-size: 0.9rem;
    color: #333;
}

.subject-badge i {
    font-size: 0.9rem;
}

.paper-code {
    background: #f5f5f5;
    padding: 0.5rem 1rem;
    border-radius: 2rem;
    font-weight: 500;
    font-size: 0.9rem;
    color: #666;
}

.exam-title {
    font-size: 1.1rem;
    line-height: 1.4;
    margin: 1rem 0 1.5rem;
    color: #333;
    font-weight: 600;
}

/* Countdown Grid */
.countdown-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 0.5rem;
    margin: 1.5rem 0;
    text-align: center;
    padding: 1rem 0;
    border-bottom: 1px solid rgba(0,0,0,0.08);
}

.countdown-unit {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.countdown-value {
    font-size: 2.25rem;
    font-weight: 700;
    color: #ff4444;
    line-height: 1;
    margin-bottom: 0.4rem;
    font-feature-settings: "tnum";
    font-variant-numeric: tabular-nums;
}

.countdown-label {
    font-size: 0.7rem;
    color: #666;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 500;
}

/* Progress bar section */
.progress-section {
    margin: 1.5rem 0;
}

.progress-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.75rem;
}

.progress-label {
    font-size: 0.9rem;
    color: #666;
}

.progress-dates {
    display: flex;
    justify-content: space-between;
    font-size: 0.85rem;
    color: #666;
    margin-bottom: 0.5rem;
}

.progress-dates .start-date {
    color: #666;
}

.progress-dates .end-date {
    color: #ff4444;
}

.progress-bar-container {
    height: 0.5rem;
    background: #f5f5f5;
    border-radius: 1rem;
    overflow: hidden;
    position: relative;
}

.progress-bar-container::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(90deg, 
        rgba(255,255,255,0) 0%, 
        rgba(255,255,255,0.3) 50%, 
        rgba(255,255,255,0) 100%);
    transform: translateX(-100%);
    animation: shimmer 2s infinite;
}

.progress-bar {
    height: 100%;
    background: linear-gradient(90deg, #DAA520, #FFD700);
    border-radius: 1rem;
    transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}

@keyframes shimmer {
    100% {
        transform: translateX(100%);
    }
}

/* Update English progress bar color */
#english .progress-bar {
    background: linear-gradient(90deg, #28a745, #34ce57);
}

/* Totals Section */
.totals-section {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    margin-top: 1.5rem;
}

.total-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    background: #f8f9fa;
    border-radius: 0.75rem;
    border: 1px solid rgba(0,0,0,0.05);
}

.total-item i {
    color: #666;
    font-size: 1rem;
    width: 20px;
}

.total-item span {
    color: #333;
    font-weight: 500;
    font-size: 0.9rem;
}

/* Responsive Design */
@media (max-width: 768px) {
    .countdown-container {
        grid-template-columns: 1fr;
        padding: 1rem;
    }

    .countdown-card {
        padding: 1rem;
    }

    .countdown-grid {
        gap: 0.25rem;
    }

    .countdown-value {
        font-size: clamp(1.75rem, 4vw, 2rem);
    }

    .countdown-label {
        font-size: 0.65rem;
    }

    .exam-title {
        font-size: 1rem;
        margin: 0.75rem 0 1.25rem;
    }

    .total-item {
        padding: 0.75rem;
    }

    .total-item span {
        font-size: 0.85rem;
    }
}

@media (min-width: 769px) and (max-width: 1200px) {
    .countdown-container {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (min-width: 1201px) {
    .countdown-container {
        grid-template-columns: repeat(3, 1fr);
    }
}

/* Dark Mode Support */
@media (prefers-color-scheme: dark) {
    .countdown-card {
        background: #ffffff;
    }

    .exam-title, .paper-code {
        color: #333;
    }

    .countdown-label {
        color: #666;
    }

    .total-item {
        background: #f8f9fa;
    }

    .total-item span, .total-item i {
        color: #333;
    }
}
</style>

<div class="nav-container">
    <ul class="nav nav-tabs" role="tablist">
        <li class="nav-item">
            <a class="nav-link active maths-tab" data-bs-toggle="tab" href="#maths">Mathematics</a>
        </li>
        <li class="nav-item">
            <a class="nav-link english-tab" data-bs-toggle="tab" href="#english">English</a>
        </li>
    </ul>
</div>

<div class="tab-content">
    <div class="tab-pane fade show active" id="maths">
        <div class="countdown-container">
            <?php
            if ($exams_result->num_rows > 0) {
                $exams_result->data_seek(0);
                while ($exam = $exams_result->fetch_assoc()) {
                    if ($exam['subject_name'] == 'Math') {
                        $exam_date = new DateTime($exam['exam_date']);
                        $now = new DateTime();
            ?>
            <div class="countdown-card">
                <div class="subject-header">
                    <span class="subject-badge">
                        <i class="fas fa-calculator"></i>
                        Mathematics
                    </span>
                    <span class="paper-code"><?php echo htmlspecialchars($exam['paper_code']); ?></span>
                </div>
                
                <h2 class="exam-title"><?php echo htmlspecialchars($exam['title']); ?></h2>
                
                <div class="countdown-grid">
                    <div class="countdown-unit">
                        <span class="countdown-value weeks">-</span>
                        <span class="countdown-label">Weeks</span>
                    </div>
                    <div class="countdown-unit">
                        <span class="countdown-value days">-</span>
                        <span class="countdown-label">Days</span>
                    </div>
                    <div class="countdown-unit">
                        <span class="countdown-value hours">-</span>
                        <span class="countdown-label">Hours</span>
                    </div>
                    <div class="countdown-unit">
                        <span class="countdown-value minutes">-</span>
                        <span class="countdown-label">Minutes</span>
                    </div>
                    <div class="countdown-unit">
                        <span class="countdown-value seconds">-</span>
                        <span class="countdown-label">Seconds</span>
                    </div>
                </div>
                
                <div class="progress-section">
                    <div class="progress-header">
                        <span class="progress-label">Countdown Progress</span>
                    </div>
                    <div class="progress-dates">
                        <span class="start-date">28/03/2025</span>
                        <span class="end-date"><?php echo $exam_date->format('d/m/Y'); ?></span>
                    </div>
                    <div class="progress-bar-container">
                        <div class="progress-bar" data-start-date="2025-03-28 00:00:00" data-end-date="<?php echo $exam_date->format('Y-m-d H:i:s'); ?>"></div>
                    </div>
                </div>
                
                <div class="totals-section">
                    <div class="total-item">
                        <i class="fas fa-calendar"></i>
                        <span><?php echo $exam_date->format('l, F j, Y'); ?></span>
                    </div>
                    <div class="total-item">
                        <i class="fas fa-clock"></i>
                        <span><?php echo $exam_date->format('g:i A'); ?></span>
                    </div>
                    <div class="total-item">
                        <i class="fas fa-calendar-day"></i>
                        <span><span class="total-days">-</span> total days</span>
                    </div>
                    <div class="total-item">
                        <i class="fas fa-hourglass"></i>
                        <span><span class="total-hours">-</span> total hours</span>
                    </div>
                </div>
                
                <div class="exam-datetime" data-exam-date="<?php echo $exam_date->format('Y-m-d H:i:s'); ?>"></div>
            </div>
            <?php 
                    }
                }
            } 
            ?>
        </div>
    </div>
    
    <div class="tab-pane fade" id="english">
        <div class="countdown-container">
            <?php
            if ($exams_result->num_rows > 0) {
                $exams_result->data_seek(0);
                while ($exam = $exams_result->fetch_assoc()) {
                    if ($exam['subject_name'] == 'English') {
                        $exam_date = new DateTime($exam['exam_date']);
                        $now = new DateTime();
            ?>
            <div class="countdown-card">
                <div class="subject-header">
                    <span class="subject-badge">
                        <i class="fas fa-book"></i>
                        English
                    </span>
                    <span class="paper-code"><?php echo htmlspecialchars($exam['paper_code']); ?></span>
                </div>
                
                <h2 class="exam-title"><?php echo htmlspecialchars($exam['title']); ?></h2>
                
                <div class="countdown-grid">
                    <div class="countdown-unit">
                        <span class="countdown-value weeks">-</span>
                        <span class="countdown-label">Weeks</span>
                    </div>
                    <div class="countdown-unit">
                        <span class="countdown-value days">-</span>
                        <span class="countdown-label">Days</span>
                    </div>
                    <div class="countdown-unit">
                        <span class="countdown-value hours">-</span>
                        <span class="countdown-label">Hours</span>
                    </div>
                    <div class="countdown-unit">
                        <span class="countdown-value minutes">-</span>
                        <span class="countdown-label">Minutes</span>
                    </div>
                    <div class="countdown-unit">
                        <span class="countdown-value seconds">-</span>
                        <span class="countdown-label">Seconds</span>
                    </div>
                </div>
                
                <div class="progress-section">
                    <div class="progress-header">
                        <span class="progress-label">Countdown Progress</span>
                    </div>
                    <div class="progress-dates">
                        <span class="start-date">28/03/2025</span>
                        <span class="end-date"><?php echo $exam_date->format('d/m/Y'); ?></span>
                    </div>
                    <div class="progress-bar-container">
                        <div class="progress-bar" data-start-date="2025-03-28 00:00:00" data-end-date="<?php echo $exam_date->format('Y-m-d H:i:s'); ?>"></div>
                    </div>
                </div>
                
                <div class="totals-section">
                    <div class="total-item">
                        <i class="fas fa-calendar"></i>
                        <span><?php echo $exam_date->format('l, F j, Y'); ?></span>
                    </div>
                    <div class="total-item">
                        <i class="fas fa-clock"></i>
                        <span><?php echo $exam_date->format('g:i A'); ?></span>
                    </div>
                    <div class="total-item">
                        <i class="fas fa-calendar-day"></i>
                        <span><span class="total-days">-</span> total days</span>
                    </div>
                    <div class="total-item">
                        <i class="fas fa-hourglass"></i>
                        <span><span class="total-hours">-</span> total hours</span>
                    </div>
                </div>
                
                <div class="exam-datetime" data-exam-date="<?php echo $exam_date->format('Y-m-d H:i:s'); ?>"></div>
            </div>
            <?php 
                    }
                }
            } 
            ?>
        </div>
    </div>
</div>

<script>
function updateCountdowns() {
    document.querySelectorAll('.countdown-card').forEach(card => {
        const dateStr = card.querySelector('.exam-datetime').dataset.examDate;
        const examDate = new Date(dateStr);
        const now = new Date();
        const diff = examDate - now;
        
        if (diff > 0) {
            // Calculate total values
            const totalMinutes = Math.floor(diff / (1000 * 60));
            const totalHours = Math.floor(diff / (1000 * 60 * 60));
            const totalDays = Math.floor(diff / (1000 * 60 * 60 * 24));
            
            // Calculate display values
            const weeks = Math.floor(totalDays / 7);
            const remainingDays = totalDays;
            const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((diff % (1000 * 60)) / 1000);
            
            // Update main countdown display
            card.querySelector('.weeks').textContent = weeks;
            card.querySelector('.days').textContent = remainingDays;
            card.querySelector('.hours').textContent = padZero(hours);
            card.querySelector('.minutes').textContent = padZero(minutes);
            card.querySelector('.seconds').textContent = padZero(seconds);
            
            // Update totals
            const totalDaysSpan = card.querySelector('.total-item .total-days');
            const totalHoursSpan = card.querySelector('.total-item .total-hours');
            
            totalDaysSpan.textContent = totalDays.toLocaleString().replace(/,/g, '');
            totalHoursSpan.textContent = totalHours.toLocaleString().replace(/,/g, '');
            
            // Improved progress bar calculation
            const progressBar = card.querySelector('.progress-bar');
            const startDate = new Date(progressBar.dataset.startDate);
            const endDate = new Date(progressBar.dataset.endDate);
            const totalDuration = endDate - startDate;
            const elapsed = now - startDate;
            
            // Calculate progress as percentage of time remaining
            let progress;
            if (now < startDate) {
                // If current date is before start date, show 100%
                progress = 100;
            } else if (now > endDate) {
                // If current date is after end date, show 0%
                progress = 0;
            } else {
                // Calculate remaining percentage
                progress = ((endDate - now) / totalDuration) * 100;
            }
            
            // Ensure progress stays within 0-100 range
            progress = Math.min(100, Math.max(0, progress));
            progressBar.style.width = `${progress}%`;
            
            // Add data attribute for debugging
            progressBar.setAttribute('data-progress', progress.toFixed(2) + '%');
        } else {
            // If exam date has passed
            const values = card.querySelectorAll('.countdown-value, .total-days, .total-hours');
            values.forEach(value => value.textContent = '0');
            card.querySelector('.progress-bar').style.width = '0%';
        }
    });
}

// Initial update
updateCountdowns();

// Update every second
setInterval(updateCountdowns, 1000);

// Add leading zeros function
function padZero(num) {
    return num < 10 ? '0' + num : num;
}

// Add a debug function to check dates
function debugDates() {
    document.querySelectorAll('.countdown-card').forEach(card => {
        const progressBar = card.querySelector('.progress-bar');
        const startDate = new Date(progressBar.dataset.startDate);
        const endDate = new Date(progressBar.dataset.endDate);
        console.log('Start Date:', startDate);
        console.log('End Date:', endDate);
        console.log('Current Progress:', progressBar.getAttribute('data-progress'));
    });
}

// Call debug function after initial load
setTimeout(debugDates, 1000);
</script>

<?php
include '../includes/footer.php';
close_connection($conn);
?> 