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
            
            <!-- Orthodox Mortality Reminders -->
            <div class="card feature-card mt-4">
                <div class="card-header bg-gradient" style="background: linear-gradient(to right, #2c3e50, #1a1a1a);">
                    <h3 class="mb-0 text-white"><i class="fas fa-cross me-2"></i>Orthodox Reminders</h3>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <!-- Memento Mori -->
                        <div class="col-md-6 mb-4">
                            <div class="orthodox-reminder memento-mori-card">
                                <div class="icon-wrapper">
                                    <div class="orthodox-icon">
                                        <i class="fas fa-skull"></i>
                                        <div class="candle-flame"></div>
                                    </div>
                                </div>
                                <h4>Memento Mori</h4>
                                <p class="reminder-text">"Death may come at any moment. Is your soul ready now?"</p>
                            </div>
                        </div>
                        
                        <!-- Running Hourglass -->
                        <div class="col-md-6 mb-4">
                            <div class="orthodox-reminder hourglass-card">
                                <div class="hourglass-wrapper">
                                    <div class="hourglass">
                                        <div class="hourglass-top">
                                            <div class="sand"></div>
                                        </div>
                                        <div class="hourglass-middle"></div>
                                        <div class="hourglass-bottom">
                                            <div class="sand-pile"></div>
                                        </div>
                                    </div>
                                </div>
                                <h4>Running Sand</h4>
                                <p class="reminder-text">"You don't know how much sand remains. Act now."</p>
                            </div>
                        </div>
                        
                        <!-- Last Hour Clock -->
                        <div class="col-md-6 mb-4">
                            <div class="orthodox-reminder last-hour-card">
                                <div class="clock-wrapper">
                                    <div class="clock">
                                        <div class="clock-face">
                                            <div class="hand hour-hand"></div>
                                            <div class="hand minute-hand"></div>
                                            <div class="hand second-hand"></div>
                                        </div>
                                    </div>
                                </div>
                                <h4>This Might Be My Last Hour</h4>
                                <p id="current-time" class="time-display"></p>
                                <p class="reminder-text">"This hour could be my last. Am I right with God now?"</p>
                            </div>
                        </div>
                        
                        <!-- Orthodox Vigil Lamp -->
                        <div class="col-md-6 mb-4">
                            <div class="orthodox-reminder vigil-lamp-card">
                                <div class="lamp-wrapper">
                                    <div class="vigil-lamp">
                                        <div class="lamp-chain"></div>
                                        <div class="lamp-body">
                                            <div class="lamp-glass">
                                                <div class="flame"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <h4>Orthodox Vigil Lamp</h4>
                                <p class="reminder-text">"Life is short and uncertain. Keep your lamp burning bright today."</p>
                            </div>
                        </div>
                        
                        <!-- Heartbeat Line -->
                        <div class="col-md-6 mb-4">
                            <div class="orthodox-reminder heartbeat-card">
                                <div class="heartbeat-wrapper">
                                    <svg id="heartbeat-svg" viewBox="0 0 600 120" preserveAspectRatio="none">
                                        <path class="heartbeat-line" d="M0,60 L0,60" stroke="#e74c3c" stroke-width="2" fill="none" />
                                    </svg>
                                </div>
                                <h4>Heartbeat</h4>
                                <p class="reminder-text">"Every heartbeat is a gift from God. Use it wisely."</p>
                            </div>
                        </div>
                        
                        <!-- The Ripple of Eternity -->
                        <div class="col-md-6 mb-4">
                            <div class="orthodox-reminder ripple-card">
                                <div class="ripple-wrapper" id="ripple-container">
                                    <div class="ripple-text">Click anywhere in this area</div>
                                </div>
                                <h4>The Ripple of Eternity</h4>
                                <p class="reminder-text">"Every moment now ripples into eternity. Choose wisely."</p>
                            </div>
                        </div>
                        
                        <!-- Final Sunset -->
                        <div class="col-md-6 mb-4">
                            <div class="orthodox-reminder sunset-card">
                                <div class="sunset-wrapper">
                                    <div class="sun"></div>
                                    <div class="horizon"></div>
                                </div>
                                <h4>Final Sunset</h4>
                                <p class="reminder-text">"Today's sunset may be your last. Act with eternity in mind."</p>
                            </div>
                        </div>
                        
                        <!-- Daily Judgment Checklist -->
                        <div class="col-md-6 mb-4">
                            <div class="orthodox-reminder checklist-card">
                                <h4>Daily Judgment Checklist</h4>
                                <div class="checklist">
                                    <div class="checklist-item">
                                        <input type="checkbox" id="prayer" class="judgment-check">
                                        <label for="prayer">Prayer</label>
                                    </div>
                                    <div class="checklist-item">
                                        <input type="checkbox" id="repentance" class="judgment-check">
                                        <label for="repentance">Repentance</label>
                                    </div>
                                    <div class="checklist-item">
                                        <input type="checkbox" id="kindness" class="judgment-check">
                                        <label for="kindness">Kindness</label>
                                    </div>
                                    <div class="checklist-item">
                                        <input type="checkbox" id="forgiveness" class="judgment-check">
                                        <label for="forgiveness">Forgiveness</label>
                                    </div>
                                </div>
                                <p class="reminder-text">"If today were your final day, how many boxes could you check?"</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- CSS for Orthodox reminders -->
            <style>
                /* Orthodox Reminder Cards */
                .orthodox-reminder {
                    background: #f9f6f1;
                    border: 1px solid #e0d5c5;
                    border-radius: 10px;
                    padding: 20px;
                    text-align: center;
                    box-shadow: 0 3px 10px rgba(0,0,0,0.1);
                    height: 100%;
                    position: relative;
                    overflow: hidden;
                    transition: transform 0.3s;
                }
                
                .orthodox-reminder:hover {
                    transform: translateY(-5px);
                }
                
                .orthodox-reminder h4 {
                    font-family: 'Georgia', serif;
                    margin-top: 15px;
                    color: #4a4a4a;
                    border-bottom: 1px solid #e0d5c5;
                    padding-bottom: 10px;
                }
                
                .reminder-text {
                    font-style: italic;
                    color: #8b6e52;
                    font-size: 16px;
                    margin-top: 15px;
                }
                
                /* Memento Mori */
                .memento-mori-card {
                    background: linear-gradient(to bottom, #2c3e50, #1a1a1a);
                    color: #e0d5c5;
                }
                
                .memento-mori-card h4 {
                    color: #e0d5c5;
                    border-color: #4a4a4a;
                }
                
                .orthodox-icon {
                    width: 80px;
                    height: 120px;
                    margin: 0 auto;
                    position: relative;
                }
                
                .orthodox-icon i {
                    font-size: 45px;
                    color: #e0d5c5;
                    position: absolute;
                    top: 50%;
                    left: 50%;
                    transform: translate(-50%, -50%);
                }
                
                .candle-flame {
                    position: absolute;
                    bottom: 0;
                    left: 50%;
                    width: 20px;
                    height: 30px;
                    background: linear-gradient(to top, #ff9d00, #ffde59);
                    border-radius: 50% 50% 20% 20%;
                    transform: translateX(-50%);
                    animation: flicker 2s infinite alternate;
                    box-shadow: 0 0 15px rgba(255, 157, 0, 0.7);
                }
                
                @keyframes flicker {
                    0%, 100% { opacity: 1; height: 30px; }
                    25% { opacity: 0.8; height: 28px; }
                    50% { opacity: 1; height: 32px; }
                    75% { opacity: 0.9; height: 27px; }
                }
                
                /* Hourglass */
                .hourglass-card {
                    background: linear-gradient(to bottom, #3a6186, #1f3a60);
                    color: #fff;
                }
                
                .hourglass-card h4 {
                    color: #fff;
                    border-color: #5d82ac;
                }
                
                .hourglass {
                    width: 80px;
                    height: 140px;
                    margin: 0 auto;
                    position: relative;
                }
                
                .hourglass-top, .hourglass-bottom {
                    width: 80px;
                    height: 60px;
                    background: rgba(255, 255, 255, 0.2);
                    position: relative;
                    overflow: hidden;
                }
                
                .hourglass-top {
                    border-radius: 40px 40px 0 0;
                    transform: rotate(180deg);
                }
                
                .hourglass-bottom {
                    border-radius: 0 0 40px 40px;
                }
                
                .hourglass-middle {
                    width: 15px;
                    height: 20px;
                    background: rgba(255, 255, 255, 0.2);
                    margin: 0 auto;
                }
                
                .sand {
                    width: 80%;
                    height: 80%;
                    background: #ffde59;
                    position: absolute;
                    top: 10%;
                    left: 10%;
                    animation: sandFall 20s linear infinite;
                    clip-path: polygon(0% 0%, 100% 0%, 100% 100%, 50% 60%, 0% 100%);
                }
                
                .sand-pile {
                    width: 50%;
                    height: 15%;
                    background: #ffde59;
                    position: absolute;
                    bottom: 5px;
                    left: 25%;
                    border-radius: 50%;
                    animation: sandPile 20s linear infinite;
                }
                
                @keyframes sandFall {
                    0% { height: 80%; }
                    100% { height: 0%; }
                }
                
                @keyframes sandPile {
                    0% { height: 5%; }
                    100% { height: 65%; }
                }
                
                /* Last Hour Clock */
                .last-hour-card {
                    background: linear-gradient(to bottom, #2c3e50, #1a1a1a);
                    color: #fff;
                }
                
                .last-hour-card h4 {
                    color: #fff;
                    border-color: #4a4a4a;
                }
                
                .clock {
                    width: 120px;
                    height: 120px;
                    border: 4px solid #e0d5c5;
                    border-radius: 50%;
                    margin: 0 auto;
                    position: relative;
                    background: #fff;
                }
                
                .clock-face {
                    width: 100%;
                    height: 100%;
                    position: relative;
                }
                
                .hand {
                    position: absolute;
                    top: 50%;
                    left: 50%;
                    background: #333;
                    transform-origin: 0% 50%;
                    transform: rotate(90deg);
                }
                
                .hour-hand {
                    width: 35%;
                    height: 4px;
                    border-radius: 4px;
                }
                
                .minute-hand {
                    width: 45%;
                    height: 3px;
                    border-radius: 3px;
                }
                
                .second-hand {
                    width: 45%;
                    height: 2px;
                    background: #e74c3c;
                    border-radius: 2px;
                }
                
                .time-display {
                    font-family: 'Courier New', monospace;
                    font-size: 20px;
                    margin: 10px 0;
                    color: #fff;
                }
                
                /* Orthodox Vigil Lamp */
                .vigil-lamp-card {
                    background: linear-gradient(to bottom, #1a1a1a, #000);
                    color: #e0d5c5;
                }
                
                .vigil-lamp-card h4 {
                    color: #e0d5c5;
                    border-color: #4a4a4a;
                }
                
                .vigil-lamp {
                    width: 80px;
                    height: 150px;
                    margin: 0 auto;
                    position: relative;
                }
                
                .lamp-chain {
                    width: 2px;
                    height: 50px;
                    background: #c0c0c0;
                    margin: 0 auto;
                }
                
                .lamp-body {
                    width: 60px;
                    height: 80px;
                    background: #c0c0c0;
                    border-radius: 30px 30px 10px 10px;
                    margin: 0 auto;
                    position: relative;
                }
                
                .lamp-glass {
                    width: 40px;
                    height: 60px;
                    background: rgba(255, 255, 255, 0.1);
                    border-radius: 20px 20px 5px 5px;
                    position: absolute;
                    top: 10px;
                    left: 10px;
                    overflow: hidden;
                }
                
                .flame {
                    width: 16px;
                    height: 25px;
                    background: linear-gradient(to top, #ff9d00, #ffde59);
                    border-radius: 50% 50% 20% 20%;
                    position: absolute;
                    bottom: 10px;
                    left: 12px;
                    animation: flicker 3s infinite alternate;
                    box-shadow: 0 0 20px rgba(255, 157, 0, 0.9);
                }
                
                /* Heartbeat */
                .heartbeat-card {
                    background: linear-gradient(to bottom, #2c3e50, #1a1a1a);
                    color: #fff;
                    padding-bottom: 10px;
                }
                
                .heartbeat-card h4 {
                    color: #fff;
                    border-color: #4a4a4a;
                }
                
                .heartbeat-wrapper {
                    width: 100%;
                    height: 120px;
                    position: relative;
                    background: rgba(0, 0, 0, 0.2);
                    border-radius: 5px;
                    margin-bottom: 10px;
                }
                
                .heartbeat-line {
                    stroke-dasharray: 600;
                    stroke-dashoffset: 600;
                    animation: heartbeatDraw 3s linear forwards infinite;
                }
                
                @keyframes heartbeatDraw {
                    to {
                        stroke-dashoffset: 0;
                    }
                }
                
                /* Ripple of Eternity */
                .ripple-card {
                    background: linear-gradient(to bottom, #2c3e50, #1a1a1a);
                    color: #fff;
                }
                
                .ripple-card h4 {
                    color: #fff;
                    border-color: #4a4a4a;
                }
                
                .ripple-wrapper {
                    width: 100%;
                    height: 150px;
                    background: rgba(0, 0, 0, 0.3);
                    border-radius: 5px;
                    position: relative;
                    cursor: pointer;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    overflow: hidden;
                }
                
                .ripple-text {
                    color: rgba(255, 255, 255, 0.7);
                    font-size: 14px;
                    z-index: 2;
                }
                
                .ripple {
                    position: absolute;
                    border-radius: 50%;
                    background: rgba(255, 255, 255, 0.4);
                    transform: scale(0);
                    animation: rippleEffect 2s linear;
                    pointer-events: none;
                }
                
                @keyframes rippleEffect {
                    to {
                        transform: scale(4);
                        opacity: 0;
                    }
                }
                
                /* Final Sunset */
                .sunset-card {
                    background: linear-gradient(to bottom, #2c3e50, #1a1a1a);
                    color: #fff;
                }
                
                .sunset-card h4 {
                    color: #fff;
                    border-color: #4a4a4a;
                }
                
                .sunset-wrapper {
                    width: 100%;
                    height: 150px;
                    background: linear-gradient(to bottom, #00111e 0%, #033872 80%, #064b8f 100%);
                    position: relative;
                    border-radius: 5px;
                    overflow: hidden;
                }
                
                .sun {
                    width: 50px;
                    height: 50px;
                    background: linear-gradient(to bottom, #ffee00, #ff9900);
                    border-radius: 50%;
                    position: absolute;
                    bottom: 20px;
                    left: 50%;
                    transform: translateX(-50%);
                    box-shadow: 0 0 40px rgba(255, 157, 0, 0.8);
                    animation: sunset 24s linear infinite;
                }
                
                .horizon {
                    width: 100%;
                    height: 15px;
                    background: #111;
                    position: absolute;
                    bottom: 0;
                    border-radius: 50% 50% 0 0 / 10px;
                }
                
                @keyframes sunset {
                    0% { transform: translateX(-50%) translateY(-25px); opacity: 1; }
                    50% { transform: translateX(-50%) translateY(0); opacity: 1; }
                    100% { transform: translateX(-50%) translateY(50px); opacity: 0.3; }
                }
                
                /* Daily Judgment Checklist */
                .checklist-card {
                    background: linear-gradient(to bottom, #2c3e50, #1a1a1a);
                    color: #fff;
                }
                
                .checklist-card h4 {
                    color: #fff;
                    border-color: #4a4a4a;
                }
                
                .checklist {
                    text-align: left;
                    max-width: 200px;
                    margin: 0 auto 15px;
                }
                
                .checklist-item {
                    margin-bottom: 15px;
                    display: flex;
                    align-items: center;
                }
                
                .judgment-check {
                    margin-right: 10px;
                    width: 20px;
                    height: 20px;
                    cursor: pointer;
                }
            </style>
            
            <!-- JavaScript for Orthodox reminders -->
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    // Previous updateLifeMetrics and other code...
                    
                    // Clock animation
                    function updateClock() {
                        const now = new Date();
                        const seconds = now.getSeconds();
                        const minutes = now.getMinutes();
                        const hours = now.getHours();
                        
                        const secondsDegrees = (seconds / 60) * 360 + 90;
                        const minutesDegrees = (minutes / 60) * 360 + 90;
                        const hoursDegrees = (hours / 12) * 360 + ((minutes / 60) * 30) + 90;
                        
                        document.querySelector('.second-hand').style.transform = `rotate(${secondsDegrees}deg)`;
                        document.querySelector('.minute-hand').style.transform = `rotate(${minutesDegrees}deg)`;
                        document.querySelector('.hour-hand').style.transform = `rotate(${hoursDegrees}deg)`;
                        
                        // Update time display
                        document.getElementById('current-time').textContent = 
                            `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                    }
                    
                    setInterval(updateClock, 1000);
                    updateClock(); // Initialize clock
                    
                    // Heartbeat animation
                    function updateHeartbeat() {
                        const heartbeatSvg = document.getElementById('heartbeat-svg');
                        if (!heartbeatSvg) return;
                        
                        const heartbeatLine = heartbeatSvg.querySelector('.heartbeat-line');
                        if (!heartbeatLine) return;
                        
                        // Create ECG pattern with occasional irregularity
                        let pathData = "M0,60 ";
                        const svgWidth = 600;
                        const irregularity = Math.random() > 0.85;
                        
                        for (let i = 0; i < svgWidth; i += 40) {
                            if (irregularity && i > svgWidth / 2 && i < svgWidth / 2 + 80) {
                                // Create irregular pattern (pause or erratic)
                                pathData += `L${i},60 ${i+5},60 ${i+10},60 `;
                                i += 20; // Skip ahead
                            } else {
                                // Normal heartbeat pattern
                                pathData += `L${i},60 ${i+10},20 ${i+15},100 ${i+20},60 `;
                            }
                        }
                        
                        heartbeatLine.setAttribute('d', pathData);
                        heartbeatLine.style.animation = 'none';
                        void heartbeatLine.offsetWidth; // Trigger reflow
                        heartbeatLine.style.animation = 'heartbeatDraw 3s linear forwards';
                    }
                    
                    setInterval(updateHeartbeat, 3000);
                    updateHeartbeat(); // Initialize heartbeat
                    
                    // Ripple effect
                    const rippleContainer = document.getElementById('ripple-container');
                    if (rippleContainer) {
                        rippleContainer.addEventListener('click', function(e) {
                            const rect = rippleContainer.getBoundingClientRect();
                            const x = e.clientX - rect.left;
                            const y = e.clientY - rect.top;
                            
                            const ripple = document.createElement('div');
                            ripple.classList.add('ripple');
                            ripple.style.left = `${x}px`;
                            ripple.style.top = `${y}px`;
                            
                            rippleContainer.appendChild(ripple);
                            
                            // Remove ripple after animation completes
                            setTimeout(() => {
                                ripple.remove();
                            }, 2000);
                        });
                    }
                    
                    // Save checklist state
                    document.querySelectorAll('.judgment-check').forEach(checkbox => {
                        checkbox.addEventListener('change', function() {
                            // Could save to localStorage if desired
                            console.log(`${this.id}: ${this.checked}`);
                        });
                    });
                    
                    // Reset checklist at midnight
                    function resetChecklistAtMidnight() {
                        const now = new Date();
                        const hours = now.getHours();
                        const minutes = now.getMinutes();
                        
                        if (hours === 0 && minutes === 0) {
                            document.querySelectorAll('.judgment-check').forEach(checkbox => {
                                checkbox.checked = false;
                            });
                        }
                    }
                    
                    // Check every minute if it's midnight
                    setInterval(resetChecklistAtMidnight, 60000);
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