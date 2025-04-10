<?php
// Include database connection
require_once '../../config/db_connect.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if exam ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "No exam ID provided";
    header("Location: ../exam_countdown.php");
    exit;
}

$exam_id = (int)$_GET['id'];

// Validate exam ID
if ($exam_id <= 0) {
    $_SESSION['error'] = "Invalid exam ID";
    header("Location: ../exam_countdown.php");
    exit;
}

// Get all subjects for dropdown
$subjects_query = "SELECT id, name, color FROM subjects ORDER BY name ASC";
$subjects_result = $conn->query($subjects_query);
$subjects = [];
if ($subjects_result->num_rows > 0) {
    while ($row = $subjects_result->fetch_assoc()) {
        $subjects[] = $row;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate form data
    $title = trim($_POST['title'] ?? '');
    $subject_id = (int)($_POST['subject_id'] ?? 0);
    $exam_date = trim($_POST['exam_date'] ?? '');
    $exam_time = trim($_POST['exam_time'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $location = trim($_POST['location'] ?? '');
    
    $errors = [];
    
    if (empty($title)) {
        $errors[] = "Exam title is required";
    }
    
    if ($subject_id <= 0) {
        $errors[] = "Please select a valid subject";
    }
    
    if (empty($exam_date)) {
        $errors[] = "Exam date is required";
    }
    
    // If there are no errors, update the exam
    if (empty($errors)) {
        // Combine date and time
        $datetime = $exam_date;
        if (!empty($exam_time)) {
            $datetime .= ' ' . $exam_time;
        } else {
            $datetime .= ' 00:00:00'; // Default time
        }
        
        $update_query = "UPDATE exams SET 
                        title = ?, 
                        subject_id = ?, 
                        exam_date = ?, 
                        description = ?, 
                        location = ? 
                        WHERE id = ?";
                        
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param('sisssi', $title, $subject_id, $datetime, $description, $location, $exam_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Exam updated successfully";
            header("Location: ../exam_countdown.php");
            exit;
        } else {
            $errors[] = "Failed to update exam: " . $stmt->error;
        }
    }
    
    // If there are errors, they will be displayed in the form
    $_SESSION['errors'] = $errors;
}

// Get exam details
$exam_query = "SELECT * FROM exams WHERE id = ?";
$stmt = $conn->prepare($exam_query);
$stmt->bind_param('i', $exam_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "Exam not found";
    header("Location: ../exam_countdown.php");
    exit;
}

$exam = $result->fetch_assoc();

// Format date and time for form inputs
$exam_datetime = new DateTime($exam['exam_date']);
$exam_date = $exam_datetime->format('Y-m-d');
$exam_time = $exam_datetime->format('H:i');

// Page title
$title = "Edit Exam";

// Include header
include_once '../../includes/header.php';
?>

<div class="container-fluid">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="../../index.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="../exam_countdown.php">Exams</a></li>
            <li class="breadcrumb-item active" aria-current="page">Edit Exam</li>
        </ol>
    </nav>
    
    <div class="row">
        <div class="col-md-6 mx-auto">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Edit Exam</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['errors'])): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($_SESSION['errors'] as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php unset($_SESSION['errors']); ?>
                    <?php endif; ?>
                    
                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) . '?id=' . $exam_id; ?>" method="post">
                        <div class="mb-3">
                            <label for="title" class="form-label">Exam Title*</label>
                            <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($exam['title']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="subject_id" class="form-label">Subject*</label>
                            <select class="form-select" id="subject_id" name="subject_id" required>
                                <option value="">Select Subject</option>
                                <?php foreach ($subjects as $subject): ?>
                                    <option value="<?php echo $subject['id']; ?>" 
                                        <?php echo ($subject['id'] == $exam['subject_id']) ? 'selected' : ''; ?>
                                        data-color="<?php echo htmlspecialchars($subject['color']); ?>">
                                        <?php echo htmlspecialchars($subject['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="exam_date" class="form-label">Exam Date*</label>
                            <input type="date" class="form-control" id="exam_date" name="exam_date" value="<?php echo $exam_date; ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="exam_time" class="form-label">Exam Time</label>
                            <input type="time" class="form-control" id="exam_time" name="exam_time" value="<?php echo $exam_time; ?>">
                            <small class="text-muted">Optional. Leave blank if time is not specified.</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="location" class="form-label">Location</label>
                            <input type="text" class="form-control" id="location" name="location" value="<?php echo htmlspecialchars($exam['location'] ?? ''); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($exam['description'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="../exam_countdown.php" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Update Exam</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include_once '../../includes/footer.php';
?> 