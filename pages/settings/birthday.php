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

<div class="container py-4">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card feature-card">
                <div class="card-header bg-white">
                    <h4 class="mb-0"><i class="fas fa-birthday-cake me-2" style="color: var(--accent-color);"></i>Birthday Settings</h4>
                </div>
                <div class="card-body">
                    <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>
                    
                    <p class="mb-4">Set your birthday to track your life counter on the dashboard. This information helps you stay motivated by showing how much time you've lived and the value of each day.</p>
                    
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
                                Your current birthday is set to: <strong><?php echo date('F j, Y', strtotime($birthday_data['birthday'])); ?></strong>
                                <?php else: ?>
                                You haven't set your birthday yet.
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 d-sm-flex justify-content-sm-end">
                            <a href="../dashboard.php" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-accent">Save Birthday</button>
                        </div>
                    </form>
                    
                    <hr class="my-4">
                    
                    <div class="alert alert-info mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Why is this important?</strong> Your birthday helps calculate how much time you've lived - in years, months, weeks, and days. This creates awareness of time's value and encourages you to use each day meaningfully for your GCSE studies.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

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
});
</script>

<?php
include '../../includes/footer.php';
close_connection($conn);
?> 