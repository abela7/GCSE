<?php
// Set page title
$page_title = "Birthday Settings";

// Set breadcrumbs
$breadcrumbs = [
    'Settings' => 'settings/index.php',
    'Birthday' => null
];

// Include database connection
require_once '../../config/db_connect.php';

// Initialize message variables
$message = '';
$message_type = '';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $day = $_POST['day'];
    $month = $_POST['month'];
    $year = $_POST['year'];
    
    // Validate input
    if (!checkdate($month, $day, $year)) {
        $message = "Invalid date. Please check your input.";
        $message_type = "danger";
    } else {
        // Format the birthday
        $birthday = sprintf("%04d-%02d-%02d", $year, $month, $day);
        
        // Check if record exists
        $check_query = "SELECT id FROM birthday LIMIT 1";
        $check_result = $conn->query($check_query);
        
        if ($check_result && $check_result->num_rows > 0) {
            // Update existing record
            $id = $check_result->fetch_assoc()['id'];
            $update_query = "UPDATE birthday SET day = ?, month = ?, year = ?, birthday = ? WHERE id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("iiisi", $day, $month, $year, $birthday, $id);
        } else {
            // Insert new record
            $insert_query = "INSERT INTO birthday (day, month, year, birthday) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($insert_query);
            $stmt->bind_param("iiis", $day, $month, $year, $birthday);
        }
        
        // Execute query
        if ($stmt->execute()) {
            $message = "Birthday updated successfully.";
            $message_type = "success";
        } else {
            $message = "Error updating birthday: " . $conn->error;
            $message_type = "danger";
        }
        
        $stmt->close();
    }
}

// Get existing birthday data
$birthday_query = "SELECT * FROM birthday LIMIT 1";
$birthday_result = $conn->query($birthday_query);
$birthday_data = ($birthday_result && $birthday_result->num_rows > 0) ? $birthday_result->fetch_assoc() : null;

// Include header
include '../../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-lg-10 mx-auto">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="index.php">Settings</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Your Time Report</li>
                </ol>
            </nav>
            
            <div class="card feature-card shadow">
                <div class="card-header bg-gradient" style="background: linear-gradient(to right, var(--accent-color), var(--accent-color-light));">
                    <h3 class="mb-0 text-white"><i class="fas fa-hourglass-half me-2"></i>Your Life in Time</h3>
                </div>
                <div class="card-body">
                    <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!$birthday_data): ?>
                    <div class="text-center py-5">
                        <div class="display-1 text-muted mb-3"><i class="fas fa-calendar-plus"></i></div>
                        <h3>Let's start your time journey</h3>
                        <p class="lead mb-4">Set your birthday to unlock powerful insights about your most precious resource: time.</p>
                    </div>
                    <?php endif; ?>
                    
                    <div class="row">
                        <div class="col-md-6 <?php echo (!$birthday_data) ? 'mx-auto' : ''; ?>">
                            <div class="card shadow-sm mb-4">
                                <div class="card-header bg-white">
                                    <h5 class="mb-0"><i class="fas fa-birthday-cake me-2" style="color: var(--accent-color);"></i>Set Your Birthday</h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST" action="">
                                        <div class="row g-3 mb-4">
                                            <div class="col-md-4">
                                                <label for="day" class="form-label">Day</label>
                                                <select class="form-select" id="day" name="day" required>
                                                    <?php for ($i = 1; $i <= 31; $i++): ?>
                                                    <option value="<?php echo $i; ?>" <?php echo ($birthday_data && $birthday_data['day'] == $i) ? 'selected' : ''; ?>>
                                                        <?php echo $i; ?>
                                                    </option>
                                                    <?php endfor; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <label for="month" class="form-label">Month</label>
                                                <select class="form-select" id="month" name="month" required>
                                                    <?php 
                                                    $months = [
                                                        1 => 'January', 2 => 'February', 3 => 'March', 
                                                        4 => 'April', 5 => 'May', 6 => 'June',
                                                        7 => 'July', 8 => 'August', 9 => 'September',
                                                        10 => 'October', 11 => 'November', 12 => 'December'
                                                    ];
                                                    
                                                    foreach ($months as $num => $name): 
                                                    ?>
                                                    <option value="<?php echo $num; ?>" <?php echo ($birthday_data && $birthday_data['month'] == $num) ? 'selected' : ''; ?>>
                                                        <?php echo $name; ?>
                                                    </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <label for="year" class="form-label">Year</label>
                                                <select class="form-select" id="year" name="year" required>
                                                    <?php 
                                                    $current_year = date('Y');
                                                    for ($i = $current_year; $i >= $current_year - 100; $i--): 
                                                    ?>
                                                    <option value="<?php echo $i; ?>" <?php echo ($birthday_data && $birthday_data['year'] == $i) ? 'selected' : ''; ?>>
                                                        <?php echo $i; ?>
                                                    </option>
                                                    <?php endfor; ?>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <div class="form-text">
                                                <?php if ($birthday_data): ?>
                                                Your current birthday is set to: <strong><?php echo date('F j, Y', strtotime($birthday_data['birthday'])); ?></strong>
                                                <?php else: ?>
                                                You haven't set your birthday yet.
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <div class="d-grid">
                                            <button type="submit" class="btn btn-accent">
                                                <i class="fas fa-save me-2"></i>Save Birthday
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            
                            <?php if ($birthday_data): ?>
                            <div class="card shadow-sm mb-4 mb-md-0">
                                <div class="card-header bg-white">
                                    <h5 class="mb-0"><i class="fas fa-quote-left me-2" style="color: var(--accent-color);"></i>Words of Wisdom</h5>
                                </div>
                                <div class="card-body">
                                    <div id="wisdom-carousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="7000">
                                        <div class="carousel-inner">
                                            <div class="carousel-item active">
                                                <blockquote class="blockquote">
                                                    <p>"So teach us to number our days, that we may gain a heart of wisdom."</p>
                                                    <footer class="blockquote-footer">Psalm 90:12</footer>
                                                </blockquote>
                                            </div>
                                            <div class="carousel-item">
                                                <blockquote class="blockquote">
                                                    <p>"Look carefully then how you walk, not as unwise but as wise, making the best use of the time, because the days are evil."</p>
                                                    <footer class="blockquote-footer">Ephesians 5:15-16</footer>
                                                </blockquote>
                                            </div>
                                            <div class="carousel-item">
                                                <blockquote class="blockquote">
                                                    <p>"But do not overlook this one fact, beloved, that with the Lord one day is as a thousand years, and a thousand years as one day."</p>
                                                    <footer class="blockquote-footer">2 Peter 3:8</footer>
                                                </blockquote>
                                            </div>
                                            <div class="carousel-item">
                                                <blockquote class="blockquote">
                                                    <p>"Yet you do not know what tomorrow will bring. What is your life? For you are a mist that appears for a little time and then vanishes."</p>
                                                    <footer class="blockquote-footer">James 4:14</footer>
                                                </blockquote>
                                            </div>
                                            <div class="carousel-item">
                                                <blockquote class="blockquote">
                                                    <p>"For everything there is a season, and a time for every matter under heaven."</p>
                                                    <footer class="blockquote-footer">Ecclesiastes 3:1</footer>
                                                </blockquote>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($birthday_data): ?>
                        <div class="col-md-6">
                            <div class="card shadow-sm mb-4">
                                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0"><i class="fas fa-chart-pie me-2" style="color: var(--accent-color);"></i>Your Life Metrics</h5>
                                    <button class="btn btn-sm btn-outline-accent" id="refreshTimeMetrics">
                                        <i class="fas fa-sync-alt"></i>
                                    </button>
                                </div>
                                <div class="card-body">
                                    <!-- Time counters with visual representation -->
                                    <div class="row mb-4">
                                        <div class="col-6 col-lg-3 mb-3">
                                            <div class="time-metric-container text-center">
                                                <div class="time-metric-circle bg-primary">
                                                    <span id="years-lived">0</span>
                                                </div>
                                                <h5 class="mt-2 mb-0">Years</h5>
                                            </div>
                                        </div>
                                        <div class="col-6 col-lg-3 mb-3">
                                            <div class="time-metric-container text-center">
                                                <div class="time-metric-circle bg-success">
                                                    <span id="months-lived">0</span>
                                                </div>
                                                <h5 class="mt-2 mb-0">Months</h5>
                                            </div>
                                        </div>
                                        <div class="col-6 col-lg-3 mb-3">
                                            <div class="time-metric-container text-center">
                                                <div class="time-metric-circle bg-warning">
                                                    <span id="weeks-lived">0</span>
                                                </div>
                                                <h5 class="mt-2 mb-0">Weeks</h5>
                                            </div>
                                        </div>
                                        <div class="col-6 col-lg-3 mb-3">
                                            <div class="time-metric-container text-center">
                                                <div class="time-metric-circle bg-danger">
                                                    <span id="days-lived">0</span>
                                                </div>
                                                <h5 class="mt-2 mb-0">Days</h5>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Progress bars -->
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between mb-1">
                                            <span>Hours Lived</span>
                                            <span id="hours-lived">0</span>
                                        </div>
                                        <div class="progress" style="height: 10px;">
                                            <div id="hours-progress" class="progress-bar progress-bar-striped progress-bar-animated bg-info" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between mb-1">
                                            <span>Minutes Lived</span>
                                            <span id="minutes-lived">0</span>
                                        </div>
                                        <div class="progress" style="height: 10px;">
                                            <div id="minutes-progress" class="progress-bar progress-bar-striped progress-bar-animated bg-secondary" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                    </div>
                                    
                                    <!-- Life visualization graph -->
                                    <div class="mt-4">
                                        <h5 class="mb-3">Your Life Visualization</h5>
                                        <canvas id="lifeChart" width="400" height="200"></canvas>
                                    </div>
                                    
                                    <!-- Real-time counter -->
                                    <div class="text-center mt-4">
                                        <h3>Your Life Counter</h3>
                                        <div class="time-counter-display">
                                            <div class="counter-unit">
                                                <span id="counter-days">0</span>
                                                <small>days</small>
                                            </div>
                                            <div class="counter-separator">:</div>
                                            <div class="counter-unit">
                                                <span id="counter-hours">00</span>
                                                <small>hours</small>
                                            </div>
                                            <div class="counter-separator">:</div>
                                            <div class="counter-unit">
                                                <span id="counter-minutes">00</span>
                                                <small>min</small>
                                            </div>
                                            <div class="counter-separator">:</div>
                                            <div class="counter-unit">
                                                <span id="counter-seconds">00</span>
                                                <small>sec</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($birthday_data): ?>
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card shadow-sm">
                                <div class="card-header bg-white">
                                    <h5 class="mb-0"><i class="fas fa-lightbulb me-2" style="color: var(--accent-color);"></i>The Value of Your Time</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h4 class="mb-3">Your Time is Precious</h4>
                                            <p>Time is the most valuable resource God has given you. Unlike money, once time is spent, it cannot be earned back. Each second that passes is a gift and an opportunity.</p>
                                            
                                            <h5 class="mt-4">What Can You Do Today?</h5>
                                            <ul class="timeline">
                                                <li class="timeline-item">
                                                    <div class="timeline-marker"></div>
                                                    <div class="timeline-content">
                                                        <h6 class="mb-1">Prioritize what matters</h6>
                                                        <p>Make a list of your priorities, with God at the center. Ask yourself if your daily activities align with these priorities.</p>
                                                    </div>
                                                </li>
                                                <li class="timeline-item">
                                                    <div class="timeline-marker"></div>
                                                    <div class="timeline-content">
                                                        <h6 class="mb-1">Eliminate time wasters</h6>
                                                        <p>Identify activities that drain your time without adding value. Replace them with activities that draw you closer to God and your goals.</p>
                                                    </div>
                                                </li>
                                                <li class="timeline-item">
                                                    <div class="timeline-marker"></div>
                                                    <div class="timeline-content">
                                                        <h6 class="mb-1">Start with prayer</h6>
                                                        <p>Begin each day by dedicating your time to God. Ask for wisdom to use your hours in a way that honors Him.</p>
                                                    </div>
                                                </li>
                                                <li class="timeline-item">
                                                    <div class="timeline-marker"></div>
                                                    <div class="timeline-content">
                                                        <h6 class="mb-1">End with reflection</h6>
                                                        <p>Before sleeping, reflect on how you used your time. Thank God for the day and seek His guidance for tomorrow.</p>
                                                    </div>
                                                </li>
                                            </ul>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="card mb-4 border-0 bg-light">
                                                <div class="card-body">
                                                    <h5 class="card-title">Drawing Closer to God</h5>
                                                    <p class="card-text">Time spent with God is never wasted. Here are ways to deepen your relationship with Christ:</p>
                                                    <div class="d-flex mb-3">
                                                        <div class="flex-shrink-0">
                                                            <i class="fas fa-bible text-primary fa-2x"></i>
                                                        </div>
                                                        <div class="flex-grow-1 ms-3">
                                                            <h5>Daily Scripture</h5>
                                                            <p class="mb-0">Dedicate time each day to read and meditate on God's Word. Start with 10 minutes and increase gradually.</p>
                                                        </div>
                                                    </div>
                                                    <div class="d-flex mb-3">
                                                        <div class="flex-shrink-0">
                                                            <i class="fas fa-praying-hands text-success fa-2x"></i>
                                                        </div>
                                                        <div class="flex-grow-1 ms-3">
                                                            <h5>Consistent Prayer</h5>
                                                            <p class="mb-0">Develop a regular prayer routine. Keep a prayer journal to track God's answers and your spiritual growth.</p>
                                                        </div>
                                                    </div>
                                                    <div class="d-flex mb-3">
                                                        <div class="flex-shrink-0">
                                                            <i class="fas fa-church text-danger fa-2x"></i>
                                                        </div>
                                                        <div class="flex-grow-1 ms-3">
                                                            <h5>Community</h5>
                                                            <p class="mb-0">Join a Bible study or youth group. Surrounding yourself with fellow believers strengthens your faith.</p>
                                                        </div>
                                                    </div>
                                                    <div class="d-flex">
                                                        <div class="flex-shrink-0">
                                                            <i class="fas fa-hands-helping text-warning fa-2x"></i>
                                                        </div>
                                                        <div class="flex-grow-1 ms-3">
                                                            <h5>Service</h5>
                                                            <p class="mb-0">Use your time to serve others. Jesus taught that the greatest among you will be your servant (Matthew 23:11).</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="card border-0 bg-primary text-white">
                                                <div class="card-body">
                                                    <h4 class="card-title">Remember</h4>
                                                    <p class="card-text">"But seek first the kingdom of God and his righteousness, and all these things will be added to you." - Matthew 6:33</p>
                                                    <p class="card-text mb-0">When you prioritize your relationship with God, everything else—including your studies—falls into proper perspective.</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer text-center">
                    <a href="../dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Custom styles for the time report page -->
<style>
/* Time Metrics Circles */
.time-metric-circle {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    color: white;
    font-size: 1.5rem;
    font-weight: bold;
    position: relative;
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    transition: all 0.3s ease;
}

.time-metric-circle:hover {
    transform: scale(1.05);
    box-shadow: 0 6px 12px rgba(0,0,0,0.2);
}

/* Counter Display */
.time-counter-display {
    display: flex;
    justify-content: center;
    align-items: center;
    background-color: #f8f9fa;
    border-radius: 10px;
    padding: 15px;
    margin-top: 15px;
    box-shadow: inset 0 0 8px rgba(0,0,0,0.1);
}

.counter-unit {
    display: flex;
    flex-direction: column;
    align-items: center;
    min-width: 60px;
}

.counter-unit span {
    font-size: 2rem;
    font-weight: bold;
    color: var(--accent-color);
}

.counter-unit small {
    font-size: 0.75rem;
    color: var(--text-muted);
}

.counter-separator {
    font-size: 2rem;
    font-weight: bold;
    color: var(--accent-color);
    margin: 0 5px;
    align-self: flex-start;
}

/* Timeline styles */
.timeline {
    position: relative;
    padding-left: 35px;
    list-style: none;
}

.timeline-item {
    position: relative;
    padding-bottom: 1.5rem;
}

.timeline-item:last-child {
    padding-bottom: 0;
}

.timeline-marker {
    position: absolute;
    left: -35px;
    top: 4px;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    background-color: var(--accent-color);
    box-shadow: 0 0 0 4px rgba(205, 175, 86, 0.2);
}

.timeline:before {
    content: '';
    position: absolute;
    left: -27px;
    top: 0;
    height: 100%;
    width: 2px;
    background-color: rgba(205, 175, 86, 0.2);
}

/* Blockquote styles */
.blockquote {
    border-left: 4px solid var(--accent-color);
    padding-left: 1rem;
}

.blockquote p {
    font-style: italic;
    font-size: 1.1rem;
}

.blockquote-footer {
    font-weight: bold;
    color: var(--accent-color);
}

/* Card hover effect */
.card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.1);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle validating days in month when month changes
    const monthSelect = document.getElementById('month');
    const daySelect = document.getElementById('day');
    const yearSelect = document.getElementById('year');
    
    function updateDays() {
        const month = parseInt(monthSelect.value);
        const year = parseInt(yearSelect.value);
        const day = parseInt(daySelect.value);
        
        // Get number of days in selected month/year
        const daysInMonth = new Date(year, month, 0).getDate();
        
        // Store current selection
        const currentDay = daySelect.value;
        
        // Clear and rebuild day options
        daySelect.innerHTML = '';
        
        for (let i = 1; i <= daysInMonth; i++) {
            const option = document.createElement('option');
            option.value = i;
            option.textContent = i;
            // Restore selection if valid, otherwise default to last day of month
            if (i === parseInt(currentDay) && i <= daysInMonth) {
                option.selected = true;
            }
            daySelect.appendChild(option);
        }
        
        // If selected day is more than days in month, select the last day
        if (day > daysInMonth) {
            daySelect.value = daysInMonth;
        }
    }
    
    monthSelect.addEventListener('change', updateDays);
    yearSelect.addEventListener('change', updateDays);
    
    // Initialize days for current month/year
    updateDays();
    
    // Life metrics calculator
    <?php if ($birthday_data): ?>
    // Get birth date from PHP data
    const birthDate = new Date('<?php echo $birthday_data['birthday']; ?>');
    
    // Function to calculate and update time metrics
    function updateTimeMetrics() {
        // Get current date/time in London time zone
        const now = new Date();
        const londonOptions = { timeZone: 'Europe/London' };
        const londonTimeStr = now.toLocaleString('en-US', londonOptions);
        const londonTime = new Date(londonTimeStr);
        
        // Calculate difference in milliseconds
        const diffMs = londonTime - birthDate;
        
        // Convert to different time units
        const years = Math.floor(diffMs / (1000 * 60 * 60 * 24 * 365.25));
        const months = Math.floor(diffMs / (1000 * 60 * 60 * 24 * 30.44));
        const weeks = Math.floor(diffMs / (1000 * 60 * 60 * 24 * 7));
        const days = Math.floor(diffMs / (1000 * 60 * 60 * 24));
        const hours = Math.floor(diffMs / (1000 * 60 * 60));
        const minutes = Math.floor(diffMs / (1000 * 60));
        const seconds = Math.floor(diffMs / 1000);
        
        // Update DOM elements with calculated values
        document.getElementById('years-lived').textContent = years.toLocaleString();
        document.getElementById('months-lived').textContent = months.toLocaleString();
        document.getElementById('weeks-lived').textContent = weeks.toLocaleString();
        document.getElementById('days-lived').textContent = days.toLocaleString();
        document.getElementById('hours-lived').textContent = hours.toLocaleString();
        document.getElementById('minutes-lived').textContent = minutes.toLocaleString();
        
        // Update progress bars (using modulus to show current progress within the larger unit)
        const hoursProgress = (hours % 24) / 24 * 100;
        const minutesProgress = (minutes % 60) / 60 * 100;
        
        document.getElementById('hours-progress').style.width = `${hoursProgress}%`;
        document.getElementById('hours-progress').setAttribute('aria-valuenow', hoursProgress);
        
        document.getElementById('minutes-progress').style.width = `${minutesProgress}%`;
        document.getElementById('minutes-progress').setAttribute('aria-valuenow', minutesProgress);
        
        // Update live counter
        const currentDays = days;
        const currentHours = londonTime.getHours();
        const currentMinutes = londonTime.getMinutes();
        const currentSeconds = londonTime.getSeconds();
        
        document.getElementById('counter-days').textContent = currentDays.toLocaleString();
        document.getElementById('counter-hours').textContent = currentHours.toString().padStart(2, '0');
        document.getElementById('counter-minutes').textContent = currentMinutes.toString().padStart(2, '0');
        document.getElementById('counter-seconds').textContent = currentSeconds.toString().padStart(2, '0');
        
        // Update chart data
        if (window.lifeChart) {
            window.lifeChart.data.datasets[0].data = [years, months - (years * 12), weeks - (months * 4.348), days - (weeks * 7)];
            window.lifeChart.update();
        }
    }
    
    // Initialize Chart.js visualization
    if (document.getElementById('lifeChart')) {
        const ctx = document.getElementById('lifeChart').getContext('2d');
        window.lifeChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Years', 'Extra Months', 'Extra Weeks', 'Extra Days'],
                datasets: [{
                    label: 'Your Life in Numbers',
                    data: [0, 0, 0, 0], // Will be updated by updateTimeMetrics
                    backgroundColor: [
                        'rgba(54, 162, 235, 0.7)',
                        'rgba(40, 167, 69, 0.7)',
                        'rgba(255, 193, 7, 0.7)',
                        'rgba(220, 53, 69, 0.7)'
                    ],
                    borderColor: [
                        'rgba(54, 162, 235, 1)',
                        'rgba(40, 167, 69, 1)',
                        'rgba(255, 193, 7, 1)',
                        'rgba(220, 53, 69, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                animation: {
                    duration: 1000,
                    easing: 'easeInOutQuart'
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const value = context.raw;
                                const label = context.dataset.label || '';
                                return `${label}: ${value}`;
                            }
                        }
                    }
                }
            }
        });
    }
    
    // Initial update
    updateTimeMetrics();
    
    // Update every second
    setInterval(updateTimeMetrics, 1000);
    
    // Manual refresh button
    const refreshButton = document.getElementById('refreshTimeMetrics');
    if (refreshButton) {
        refreshButton.addEventListener('click', function() {
            // Add rotation animation
            this.classList.add('animate-refresh');
            
            // Trigger update
            updateTimeMetrics();
            
            // Remove animation class after animation completes
            setTimeout(() => {
                this.classList.remove('animate-refresh');
            }, 1000);
        });
    }
    
    // Add animation class for refresh button
    const styleElement = document.createElement('style');
    styleElement.textContent = `
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .animate-refresh {
            animation: spin 1s linear;
        }
    `;
    document.head.appendChild(styleElement);
    <?php endif; ?>
});
</script>

<?php
include '../../includes/footer.php';
close_connection($conn);
?> 