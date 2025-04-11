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
            <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <div class="card feature-card shadow">
                <div class="card-header bg-gradient" style="background: linear-gradient(to right, var(--accent-color), var(--accent-color-light));">
                    <h3 class="mb-0 text-white"><i class="fas fa-hourglass-half me-2"></i>Your Life in Time</h3>
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
                        
                        <div class="mb-4">
                            <div class="form-text">
                                <?php if ($birthday_data): ?>
                                Current birthday: <strong><?php echo date('F j, Y', strtotime($birthday_data['birthday'])); ?></strong>
                                <?php else: ?>
                                No birthday set.
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 d-sm-flex justify-content-sm-end">
                            <a href="../dashboard.php" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-accent">Save Birthday</button>
                        </div>
                    </form>
                    
                    <hr class="my-4">
                </div>
            </div>
            
            <?php if ($birthday_data): ?>
            <!-- Life Metrics Visualization -->
            <div class="card feature-card">
                <div class="card-header bg-gradient" style="background: linear-gradient(to right, var(--accent-color), var(--accent-color-light));">
                    <h3 class="mb-0 text-white"><i class="fas fa-hourglass-half me-2"></i>Your Life in Time</h3>
                </div>
                <div class="card-body">
                    <!-- Number Visualizations -->
                    <div class="row mb-4">
                        <div class="col-md-3 col-6 mb-3">
                            <div class="metric-circle years-circle">
                                <div class="metric-number" id="years-lived">-</div>
                                <div class="metric-label">Years</div>
                            </div>
                        </div>
                        <div class="col-md-3 col-6 mb-3">
                            <div class="metric-circle months-circle">
                                <div class="metric-number" id="months-lived">-</div>
                                <div class="metric-label">Months</div>
                            </div>
                        </div>
                        <div class="col-md-3 col-6 mb-3">
                            <div class="metric-circle weeks-circle">
                                <div class="metric-number" id="weeks-lived">-</div>
                                <div class="metric-label">Weeks</div>
                            </div>
                        </div>
                        <div class="col-md-3 col-6 mb-3">
                            <div class="metric-circle days-circle">
                                <div class="metric-number" id="days-lived">-</div>
                                <div class="metric-label">Days</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Hours & Minutes Progress -->
                    <div class="row mb-4">
                        <div class="col-md-6 mb-3">
                            <div class="metric-card">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h5 class="mb-0">Hours</h5>
                                    <div class="badge bg-accent" id="hours-lived">-</div>
                                </div>
                                <div class="progress hours-progress" style="height: 20px;">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated" id="hours-progress" role="progressbar" style="width: 0"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="metric-card">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h5 class="mb-0">Minutes</h5>
                                    <div class="badge bg-accent" id="minutes-lived">-</div>
                                </div>
                                <div class="progress minutes-progress" style="height: 20px;">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated" id="minutes-progress" role="progressbar" style="width: 0"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Today's Focus -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card metric-card today-card">
                                <div class="card-body text-center">
                                    <h5 class="card-title mb-3">This Day Is a Gift</h5>
                                    <div class="time-pulse" id="beating-heart">
                                        <i class="fas fa-heartbeat"></i>
                                    </div>
                                    <div id="today-date" class="display-6 mb-3">-</div>
                                    <div class="current-moment-box p-3 mb-3">
                                        <div class="row">
                                            <div class="col">
                                                <div class="moment-value" id="today-number">-</div>
                                                <div class="moment-label">Day of Year</div>
                                            </div>
                                            <div class="col">
                                                <div class="moment-value" id="today-hour">-</div>
                                                <div class="moment-label">Hour of Day</div>
                                            </div>
                                            <div class="col">
                                                <div class="moment-value" id="heartbeats-minute">-</div>
                                                <div class="moment-label">Heartbeats/min</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div id="memento-mori" class="memento-text">Each moment is precious. Act now.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Urgent Time Reminder -->
                    <div class="row mb-4">
                        <div class="col-md-6 mb-3">
                            <div class="card metric-card present-moment-card">
                                <div class="card-body">
                                    <h5 class="mb-2">The Present Moment</h5>
                                    <div class="present-moment-container">
                                        <div class="progress present-moment-progress mb-2">
                                            <div class="progress-bar present-second-bar" id="present-second-progress" role="progressbar"></div>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span>Seconds of this minute: <span id="present-second-count">-</span></span>
                                        </div>
                                    </div>
                                    <div class="present-moment-quote mt-3" id="present-quote">This second will never return.</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="card metric-card h-100 urgent-reminder-card">
                                <div class="card-body">
                                    <h5 class="mb-2">Your Time Is Now</h5>
                                    <div class="timer-container">
                                        <div class="d-flex justify-content-center mb-3">
                                            <div class="time-value-box mx-2">
                                                <div class="time-value" id="urgent-hours">-</div>
                                                <div class="time-label">Hours</div>
                                            </div>
                                            <div class="time-value-box mx-2">
                                                <div class="time-value" id="urgent-minutes">-</div>
                                                <div class="time-label">Minutes</div>
                                            </div>
                                            <div class="time-value-box mx-2">
                                                <div class="time-value" id="urgent-seconds">-</div>
                                                <div class="time-label">Seconds</div>
                                            </div>
                                        </div>
                                        <div class="urgent-message">
                                            What if this was all the time you had left?
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- CSS for visualizations -->
            <style>
                .live-counter-wrapper {
                    background-color: #343a40;
                    color: white;
                    border-radius: 10px;
                    margin-bottom: 15px;
                }
                
                #time-counter {
                    font-family: 'Courier New', monospace;
                    letter-spacing: 2px;
                }
                
                .metric-circle {
                    width: 120px;
                    height: 120px;
                    border-radius: 50%;
                    display: flex;
                    flex-direction: column;
                    justify-content: center;
                    align-items: center;
                    margin: 0 auto;
                    color: white;
                    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
                    transition: transform 0.3s;
                }
                
                .metric-circle:hover {
                    transform: scale(1.05);
                }
                
                .years-circle { background: linear-gradient(135deg, #3498db, #2980b9); }
                .months-circle { background: linear-gradient(135deg, #e74c3c, #c0392b); }
                .weeks-circle { background: linear-gradient(135deg, #2ecc71, #27ae60); }
                .days-circle { background: linear-gradient(135deg, #f39c12, #d35400); }
                
                .metric-number {
                    font-size: 24px;
                    font-weight: bold;
                    line-height: 1.2;
                }
                
                .metric-label {
                    font-size: 14px;
                    opacity: 0.9;
                }
                
                .metric-card {
                    padding: 15px;
                    border-radius: 10px;
                    background-color: white;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
                    height: 100%;
                }
                
                .hours-progress .progress-bar {
                    background-color: #3498db;
                }
                
                .minutes-progress .progress-bar {
                    background-color: #e74c3c;
                }
                
                /* New styles for urgency-focused metrics */
                .today-card {
                    background: linear-gradient(135deg, #2c3e50, #34495e);
                    color: white;
                    padding: 20px;
                }
                
                .time-pulse {
                    font-size: 48px;
                    color: #e74c3c;
                    animation: pulse 1s infinite;
                    margin-bottom: 15px;
                }
                
                @keyframes pulse {
                    0% { transform: scale(1); }
                    50% { transform: scale(1.1); }
                    100% { transform: scale(1); }
                }
                
                .current-moment-box {
                    background-color: rgba(255, 255, 255, 0.1);
                    border-radius: 10px;
                }
                
                .moment-value {
                    font-size: 28px;
                    font-weight: bold;
                    color: #ecf0f1;
                }
                
                .moment-label {
                    font-size: 12px;
                    color: rgba(255, 255, 255, 0.7);
                }
                
                .memento-text {
                    font-size: 18px;
                    font-style: italic;
                    margin-top: 15px;
                    padding: 10px;
                    border-top: 1px solid rgba(255, 255, 255, 0.2);
                }
                
                .present-moment-card {
                    background: linear-gradient(135deg, #8e44ad, #9b59b6);
                    color: white;
                }
                
                .present-moment-progress {
                    height: 15px;
                    background-color: rgba(255, 255, 255, 0.2);
                    border-radius: 5px;
                    overflow: hidden;
                }
                
                .present-second-bar {
                    background-color: #f1c40f;
                    transition: width 0.1s linear;
                }
                
                .present-moment-quote {
                    font-style: italic;
                    text-align: center;
                    padding-top: 10px;
                    border-top: 1px solid rgba(255, 255, 255, 0.2);
                }
                
                .urgent-reminder-card {
                    background: linear-gradient(135deg, #c0392b, #e74c3c);
                    color: white;
                }
                
                .time-value-box {
                    background-color: rgba(0, 0, 0, 0.2);
                    border-radius: 5px;
                    padding: 10px 15px;
                    min-width: 80px;
                    text-align: center;
                }
                
                .urgent-message {
                    text-align: center;
                    font-style: italic;
                    font-size: 18px;
                    margin-top: 10px;
                    padding-top: 10px;
                    border-top: 1px solid rgba(255, 255, 255, 0.2);
                }
                
                @media (max-width: 767px) {
                    .metric-circle {
                        width: 100px;
                        height: 100px;
                    }
                    
                    .metric-number {
                        font-size: 20px;
                    }
                    
                    .time-value-box {
                        min-width: 60px;
                        padding: 8px 10px;
                    }
                    
                    .moment-value {
                        font-size: 22px;
                    }
                }
            </style>
            
            <!-- JavaScript for calculations and visualizations -->
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    // Get birth date
                    const birthDate = new Date('<?php echo $birthday_data['birthday']; ?>');
                    
                    // Update metrics initially
                    updateLifeMetrics(birthDate);
                    
                    // Update every second
                    setInterval(function() {
                        updateLifeMetrics(birthDate);
                    }, 1000);
                    
                    // Life metrics calculation and visualization
                    function updateLifeMetrics(birthDate) {
                        // Get current date/time in London time zone
                        const now = new Date();
                        const londonOptions = { timeZone: 'Europe/London' };
                        
                        // Get London time components as strings
                        const londonTimeStr = now.toLocaleString('en-US', londonOptions);
                        // Parse London time back to Date object
                        const londonTime = new Date(londonTimeStr);
                        
                        // Calculate diff in milliseconds
                        const diffMs = londonTime - birthDate;
                        
                        // Calculate various time units
                        const totalSeconds = Math.floor(diffMs / 1000);
                        const totalMinutes = Math.floor(totalSeconds / 60);
                        const totalHours = Math.floor(totalMinutes / 60);
                        const totalDays = Math.floor(totalHours / 24);
                        const totalWeeks = Math.floor(totalDays / 7);
                        const totalMonths = Math.floor(totalDays / 30.4375);
                        const years = Math.floor(totalDays / 365.25);
                        
                        // For time calculations
                        const hours = Math.floor(totalHours % 24);
                        const minutes = Math.floor(totalMinutes % 60);
                        const seconds = Math.floor(totalSeconds % 60);
                        
                        // Format with leading zeros
                        const formattedHours = String(hours).padStart(2, '0');
                        const formattedMinutes = String(minutes).padStart(2, '0');
                        const formattedSeconds = String(seconds).padStart(2, '0');
                        
                        // Update main metrics
                        document.getElementById('years-lived').textContent = formatNumber(years);
                        document.getElementById('months-lived').textContent = formatNumber(totalMonths);
                        document.getElementById('weeks-lived').textContent = formatNumber(totalWeeks);
                        document.getElementById('days-lived').textContent = formatNumber(totalDays);
                        
                        // Update hours and minutes with progress
                        document.getElementById('hours-lived').textContent = formatNumber(totalHours);
                        document.getElementById('hours-progress').style.width = `${(hours/24)*100}%`;
                        
                        document.getElementById('minutes-lived').textContent = formatNumber(totalMinutes);
                        document.getElementById('minutes-progress').style.width = `${(minutes/60)*100}%`;
                        
                        // Update Today's focus section
                        const currentDate = londonTime.toLocaleDateString('en-US', { 
                            weekday: 'long', 
                            year: 'numeric', 
                            month: 'long', 
                            day: 'numeric' 
                        });
                        document.getElementById('today-date').textContent = currentDate;
                        
                        // Calculate day of year
                        const startOfYear = new Date(londonTime.getFullYear(), 0, 0);
                        const diff = londonTime - startOfYear;
                        const dayOfYear = Math.floor(diff / 86400000);
                        document.getElementById('today-number').textContent = dayOfYear;
                        
                        // Current hour
                        document.getElementById('today-hour').textContent = hours;
                        
                        // Heartbeats per minute (simulation, average 70-75)
                        const heartbeats = Math.floor(70 + Math.random() * 5);
                        document.getElementById('heartbeats-minute').textContent = heartbeats;
                        
                        // Update present moment section
                        document.getElementById('present-second-count').textContent = seconds;
                        const secondsProgress = (seconds / 60) * 100;
                        document.getElementById('present-second-progress').style.width = `${secondsProgress}%`;
                        
                        // Update urgent reminder section
                        document.getElementById('urgent-hours').textContent = formattedHours;
                        document.getElementById('urgent-minutes').textContent = formattedMinutes;
                        document.getElementById('urgent-seconds').textContent = formattedSeconds;
                        
                        // Rotate through memento mori messages
                        const mementoMessages = [
                            "Remember, you will die. Use this moment wisely.",
                            "Every second is precious and unrepeatable.",
                            "What will you do with the time given to you today?",
                            "If this was your last day, how would you spend it?",
                            "Today is a gift. That's why it's called the present.",
                            "Act now. Tomorrow is not guaranteed."
                        ];
                        
                        // Change message every minute
                        if (seconds === 0) {
                            const randomIndex = Math.floor(Math.random() * mementoMessages.length);
                            document.getElementById('memento-mori').textContent = mementoMessages[randomIndex];
                            
                            // Present moment quotes that rotate every minute
                            const presentQuotes = [
                                "This moment will never come again.",
                                "Now is all we have.",
                                "Be present and attentive right now.",
                                "Each second is irreplaceable.",
                                "The present moment is your point of power."
                            ];
                            const quoteIndex = Math.floor(Math.random() * presentQuotes.length);
                            document.getElementById('present-quote').textContent = presentQuotes[quoteIndex];
                        }
                    }
                    
                    // Helper for formatting large numbers with commas
                    function formatNumber(num) {
                        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                    }
                });
            </script>
            <?php else: ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i>
                Please set your birthday to see your life metrics.
            </div>
            <?php endif; ?>
            
            <!-- Birthday Form Accordion -->
            <div class="accordion mt-4" id="birthdayAccordion">
                <div class="accordion-item">
                    <h2 class="accordion-header" id="birthdayHeader">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#birthdayCollapse" aria-expanded="false" aria-controls="birthdayCollapse">
                            <i class="fas fa-birthday-cake me-2"></i> Set or Update Your Birthday
                        </button>
                    </h2>
                    <div id="birthdayCollapse" class="accordion-collapse collapse" aria-labelledby="birthdayHeader" data-bs-parent="#birthdayAccordion">
                        <div class="accordion-body">
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
                                
                                <div class="mb-4">
                                    <div class="form-text">
                                        <?php if ($birthday_data): ?>
                                        Current birthday: <strong><?php echo date('F j, Y', strtotime($birthday_data['birthday'])); ?></strong>
                                        <?php else: ?>
                                        No birthday set.
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="d-grid gap-2 d-sm-flex justify-content-sm-end">
                                    <a href="../dashboard.php" class="btn btn-secondary">Cancel</a>
                                    <button type="submit" class="btn btn-accent">Save Birthday</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include '../../includes/footer.php';
close_connection($conn);
?> 