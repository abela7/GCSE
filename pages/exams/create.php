<?php
// Set page title
$page_title = "Add Exam";

// Set breadcrumbs
$breadcrumbs = [
    'Dashboard' => '../dashboard.php',
    'Exams' => '../exam_countdown.php',
    'Add Exam' => null
];

// Include database connection
require_once '../../config/db_connect.php';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $subject_id = isset($_POST['subject_id']) ? (int)$_POST['subject_id'] : 0;
    $exam_date = isset($_POST['exam_date']) ? trim($_POST['exam_date']) : '';
    $exam_time = isset($_POST['exam_time']) ? trim($_POST['exam_time']) : '09:00:00';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $location = isset($_POST['location']) ? trim($_POST['location']) : '';
    
    // Validate input
    $errors = [];
    
    if (empty($title)) {
        $errors[] = "Title is required";
    }
    
    if ($subject_id <= 0) {
        $errors[] = "Please select a valid subject";
    }
    
    if (empty($exam_date)) {
        $errors[] = "Exam date is required";
    } else {
        // Validate date format (YYYY-MM-DD)
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $exam_date)) {
            $errors[] = "Invalid date format. Please use YYYY-MM-DD";
        }
    }
    
    // If no errors, insert into database
    if (empty($errors)) {
        try {
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
            
            // Insert exam data
            $insert_query = "INSERT INTO exams (title, subject_id, exam_date, exam_time, description, location) 
                           VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($insert_query);
            $stmt->bind_param('sissss', $title, $subject_id, $exam_date, $exam_time, $description, $location);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "Exam added successfully!";
                header("Location: ../exam_countdown.php");
                exit;
            } else {
                $errors[] = "Failed to add exam: " . $stmt->error;
            }
        } catch (Exception $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

// Get subjects for dropdown
$subjects_query = "SELECT * FROM subjects ORDER BY name ASC";
$subjects_result = $conn->query($subjects_query);

// Include header
include '../../includes/header.php';
?>

<div class="container py-4">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card shadow">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-calendar-plus me-2" style="color: #cdaf56;"></i>Add New Exam</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <form action="" method="POST">
                        <div class="mb-3">
                            <label for="title" class="form-label">Exam Title</label>
                            <input type="text" class="form-control" id="title" name="title" required 
                                   value="<?php echo isset($title) ? htmlspecialchars($title) : ''; ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="subject_id" class="form-label">Subject</label>
                            <select class="form-select" id="subject_id" name="subject_id" required>
                                <option value="">-- Select Subject --</option>
                                <?php 
                                if ($subjects_result && $subjects_result->num_rows > 0) {
                                    while ($subject = $subjects_result->fetch_assoc()): 
                                        $selected = isset($subject_id) && $subject_id == $subject['id'] ? 'selected' : '';
                                ?>
                                    <option value="<?php echo $subject['id']; ?>" <?php echo $selected; ?>>
                                        <?php echo htmlspecialchars($subject['name']); ?>
                                    </option>
                                <?php 
                                    endwhile;
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="exam_date" class="form-label">Exam Date</label>
                                <input type="date" class="form-control" id="exam_date" name="exam_date" required
                                       value="<?php echo isset($exam_date) ? htmlspecialchars($exam_date) : date('Y-m-d'); ?>">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="exam_time" class="form-label">Exam Time</label>
                                <input type="time" class="form-control" id="exam_time" name="exam_time"
                                       value="<?php echo isset($exam_time) ? htmlspecialchars($exam_time) : '09:00'; ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="location" class="form-label">Location</label>
                            <input type="text" class="form-control" id="location" name="location"
                                   value="<?php echo isset($location) ? htmlspecialchars($location) : ''; ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"><?php echo isset($description) ? htmlspecialchars($description) : ''; ?></textarea>
                        </div>
                        
                        <div class="d-flex justify-content-between mt-4">
                            <a href="../exam_countdown.php" class="btn btn-outline-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Add Exam</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include '../../includes/footer.php';
close_connection($conn);
?> 