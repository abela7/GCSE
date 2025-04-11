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
            <div class="card feature-card mt-4">
                <div class="card-header bg-white">
                    <h4 class="mb-0"><i class="fas fa-chart-line me-2" style="color: var(--accent-color);"></i>Your Life Metrics</h4>
                </div>
                <div class="card-body">
                    <!-- Live Time Counter -->
                    <div class="live-counter-wrapper text-center p-3 mb-4">
                        <div id="time-counter" class="display-5 fw-bold"></div>
                        <div class="mt-2 text-muted">
                            <span class="badge bg-primary">London Time</span>
                            <small id="counter-label">days in current year : hours : minutes : seconds</small>
                        </div>
                    </div>
                    
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
                    
                    <!-- Time Unit Comparison -->
                    <div class="time-comparison-wrapper mb-4">
                        <h5 class="mb-3">Your Life in Different Time Units</h5>
                        <div class="time-comparison-chart">
                            <div class="chart-bar-container">
                                <div class="chart-label">Decades</div>
                                <div class="chart-bar-bg">
                                    <div class="chart-bar decades-bar" id="decades-bar" style="width: 0%"></div>
                                </div>
                                <div class="chart-value" id="decades-value">-</div>
                            </div>
                            <div class="chart-bar-container">
                                <div class="chart-label">Years</div>
                                <div class="chart-bar-bg">
                                    <div class="chart-bar years-bar" id="years-bar" style="width: 0%"></div>
                                </div>
                                <div class="chart-value" id="years-value">-</div>
                            </div>
                            <div class="chart-bar-container">
                                <div class="chart-label">Months</div>
                                <div class="chart-bar-bg">
                                    <div class="chart-bar months-bar" id="months-bar" style="width: 0%"></div>
                                </div>
                                <div class="chart-value" id="months-value">-</div>
                            </div>
                            <div class="chart-bar-container">
                                <div class="chart-label">Weeks</div>
                                <div class="chart-bar-bg">
                                    <div class="chart-bar weeks-bar" id="weeks-bar" style="width: 0%"></div>
                                </div>
                                <div class="chart-value" id="weeks-value">-</div>
                            </div>
                            <div class="chart-bar-container">
                                <div class="chart-label">Days</div>
                                <div class="chart-bar-bg">
                                    <div class="chart-bar days-bar" id="days-bar" style="width: 0%"></div>
                                </div>
                                <div class="chart-value" id="days-value">-</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Life Percentage -->
                    <div class="row mb-4">
                        <div class="col-md-8 mx-auto mb-3">
                            <div class="card metric-card h-100">
                                <div class="card-body">
                                    <h5 class="card-title">Life Percentage</h5>
                                    <p class="text-muted small">Based on average life expectancy of 80 years</p>
                                    <div class="progress mt-2 mb-2" style="height: 30px;">
                                        <div class="progress-bar bg-accent progress-bar-striped" id="life-progress" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                                    </div>
                                    <div class="text-center" id="life-percentage-text">-</div>
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
                
                .time-comparison-chart {
                    margin: 20px 0;
                }
                
                .chart-bar-container {
                    display: flex;
                    align-items: center;
                    margin-bottom: 10px;
                }
                
                .chart-label {
                    width: 80px;
                    font-size: 14px;
                    text-align: right;
                    padding-right: 10px;
                }
                
                .chart-bar-bg {
                    flex-grow: 1;
                    height: 25px;
                    background-color: #f1f1f1;
                    border-radius: 4px;
                    overflow: hidden;
                }
                
                .chart-bar {
                    height: 100%;
                    transition: width 1s ease-in-out;
                }
                
                .decades-bar { background-color: #9b59b6; }
                .years-bar { background-color: #3498db; }
                .months-bar { background-color: #e74c3c; }
                .weeks-bar { background-color: #2ecc71; }
                .days-bar { background-color: #f39c12; }
                
                .chart-value {
                    width: 60px;
                    text-align: right;
                    padding-left: 10px;
                    font-weight: bold;
                }
                
                .time-value-row {
                    display: flex;
                    text-align: center;
                    margin-top: 20px;
                }
                
                .time-value {
                    font-size: 24px;
                    font-weight: bold;
                    color: #3498db;
                }
                
                .time-label {
                    font-size: 12px;
                    color: #7f8c8d;
                }
                
                @media (max-width: 767px) {
                    .metric-circle {
                        width: 100px;
                        height: 100px;
                    }
                    
                    .metric-number {
                        font-size: 20px;
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
                        const decades = Math.floor(years / 10);
                        
                        // For the live counter display
                        const days = Math.floor(totalDays % 365.25);
                        const hours = Math.floor(totalHours % 24);
                        const minutes = Math.floor(totalMinutes % 60);
                        const seconds = Math.floor(totalSeconds % 60);
                        
                        // Format with leading zeros
                        const formattedDays = String(days).padStart(3, '0');
                        const formattedHours = String(hours).padStart(2, '0');
                        const formattedMinutes = String(minutes).padStart(2, '0');
                        const formattedSeconds = String(seconds).padStart(2, '0');
                        
                        // Update live counter
                        document.getElementById('time-counter').textContent = 
                            `${formattedDays}:${formattedHours}:${formattedMinutes}:${formattedSeconds}`;
                        
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
                        
                        // Update time unit comparison
                        document.getElementById('decades-value').textContent = formatNumber(decades);
                        document.getElementById('decades-bar').style.width = `${Math.min(decades*10, 100)}%`;
                        
                        document.getElementById('years-value').textContent = formatNumber(years);
                        document.getElementById('years-bar').style.width = `${Math.min(years, 100)}%`;
                        
                        document.getElementById('months-value').textContent = formatNumber(totalMonths);
                        document.getElementById('months-bar').style.width = `${Math.min(totalMonths/12, 100)}%`;
                        
                        document.getElementById('weeks-value').textContent = formatNumber(totalWeeks);
                        document.getElementById('weeks-bar').style.width = `${Math.min(totalWeeks/520, 100)}%`;
                        
                        document.getElementById('days-value').textContent = formatNumber(totalDays);
                        document.getElementById('days-bar').style.width = `${Math.min(totalDays/3650, 100)}%`;
                        
                        // Life percentage (based on 80 years)
                        const lifePercentage = (years / 80) * 100;
                        document.getElementById('life-progress').style.width = `${lifePercentage}%`;
                        document.getElementById('life-progress').textContent = `${lifePercentage.toFixed(2)}%`;
                        document.getElementById('life-percentage-text').textContent = 
                            `You've lived ${lifePercentage.toFixed(2)}% of an 80-year life expectancy`;
                        
                        // GCSE focus time values
                        document.getElementById('hours-per-day').textContent = '8';
                        document.getElementById('days-per-month').textContent = '30';
                        document.getElementById('precious-hours').textContent = formatNumber(totalDays * 8);
                    }
                    
                    // Helper for formatting large numbers with commas
                    function formatNumber(num) {
                        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                    }
                });
            </script>
            <?php else: ?>
            <div class="alert alert-warning mt-4">
                <i class="fas fa-exclamation-triangle me-2"></i>
                Please set your birthday above to see your life metrics.
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
include '../../includes/footer.php';
close_connection($conn);
?> 