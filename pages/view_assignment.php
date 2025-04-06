<?php
// Start the session and buffer
session_start();
ob_start();

require_once '../config/db_connect.php';
include '../includes/header.php';

// Handle form submission before any output
$redirect = false;
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_criteria'])) {
    if (!isset($_POST['id'])) {
        $message = 'Invalid assignment ID';
    } else {
        $assignment_id = mysqli_real_escape_string($conn, $_POST['id']);
        
        // Get total number of criteria
        $sql = "SELECT COUNT(*) as total FROM assessment_criteria WHERE assignment_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $assignment_id);
        $stmt->execute();
        $total_result = $stmt->get_result()->fetch_assoc();
        $total_criteria = $total_result['total'];

        // Clear existing progress
        $sql = "DELETE FROM assignment_criteria_progress WHERE assignment_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $assignment_id);
        $stmt->execute();

        // Insert new progress and count completed
        $completed_count = 0;
        if (isset($_POST['completed_criteria']) && is_array($_POST['completed_criteria'])) {
            foreach ($_POST['completed_criteria'] as $criteria_id) {
                $sql = "INSERT INTO assignment_criteria_progress (assignment_id, criteria_id, status, completed_at) 
                        VALUES (?, ?, 'completed', NOW())";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ii", $assignment_id, $criteria_id);
                $stmt->execute();
                $completed_count++;
            }
        }

        // Calculate progress percentage
        if ($total_criteria > 0) {
            // Each criterion is worth exactly (100 / total_criteria) percent
            $each_criteria_value = 100 / $total_criteria;
            // Calculate progress and round to 2 decimal places
            $progress_percentage = round($completed_count * $each_criteria_value, 2);
            
            // Ensure 100% only when all criteria are completed
            if ($completed_count == $total_criteria) {
                $progress_percentage = 100;
            }
        } else {
            $progress_percentage = 0;
        }
        
        // Debug information
        error_log("Progress Calculation for Assignment ID: $assignment_id");
        error_log("Total Criteria: $total_criteria");
        error_log("Completed Criteria: $completed_count");
        error_log("Value per Criteria: $each_criteria_value%");
        error_log("Final Progress: $progress_percentage%");
        
        // Update assignment progress
        $sql = "UPDATE access_assignments 
                SET progress_percentage = ?,
                    completed_criteria = ?,
                    total_criteria = ?,
                    status = CASE 
                        WHEN ? = 100 THEN 'completed'
                        WHEN ? > 0 THEN 'in_progress'
                        ELSE 'not_started'
                    END
                WHERE id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiiiii", $progress_percentage, $completed_count, $total_criteria, 
                          $progress_percentage, $progress_percentage, $assignment_id);
        $stmt->execute();

        $_SESSION['success_message'] = sprintf(
            'Progress updated successfully! Completed %d of %d criteria (%.1f%%)',
            $completed_count,
            $total_criteria,
            $progress_percentage
        );
        $redirect = true;
    }
}

// If redirect is needed, use JavaScript
if ($redirect) {
    echo "<script>window.location.href = 'view_assignment.php?id=" . $_POST['id'] . "';</script>";
    exit;
}

if (!isset($_GET['id'])) {
    echo "<script>window.location.href = 'assignments.php';</script>";
    exit;
}

$assignment_id = mysqli_real_escape_string($conn, $_GET['id']);

// Fetch assignment details with unit information
$sql = "SELECT a.*, u.unit_code, u.unit_name 
        FROM access_assignments a 
        LEFT JOIN access_course_units u ON a.unit_id = u.id 
        WHERE a.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $assignment_id);
$stmt->execute();
$result = $stmt->get_result();
$assignment = $result->fetch_assoc();

if (!$assignment) {
    header('Location: assignments.php');
    exit;
}

// Fetch criteria
$sql = "SELECT * FROM assessment_criteria WHERE assignment_id = ? ORDER BY criteria_code";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $assignment_id);
$stmt->execute();
$criteria_result = $stmt->get_result();

// Fetch guidance items
$sql = "SELECT * FROM assignment_guidance WHERE assignment_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $assignment_id);
$stmt->execute();
$guidance_result = $stmt->get_result();

// Fetch progress logs (most recent first)
$sql = "SELECT * FROM assignment_progress_log WHERE assignment_id = ? ORDER BY logged_at DESC LIMIT 5";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $assignment_id);
$stmt->execute();
$logs_result = $stmt->get_result();
?>

<!-- Add this near the top of your HTML -->
<?php if (isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php 
        echo $_SESSION['success_message'];
        unset($_SESSION['success_message']);
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="container-fluid mt-4">
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php 
            echo $_SESSION['success_message'];
            unset($_SESSION['success_message']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Header Section -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center gap-3">
            <h2 class="mb-0">
                <span class="badge bg-primary me-2">
                    <i class="fas fa-book me-1"></i>
                    <?php echo htmlspecialchars($assignment['unit_code']); ?>
                </span>
                <?php echo htmlspecialchars($assignment['title']); ?>
            </h2>
        </div>
        <a href="assignments.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Back
        </a>
    </div>

    <!-- Status Card -->
    <div class="card mb-4">
        <div class="card-body p-4">
            <div class="row g-4">
                <div class="col-md-8">
                    <div class="d-flex align-items-center gap-4 mb-3">
                        <div class="badge-group">
                            <span class="badge bg-<?php echo $assignment['priority'] === 'high' ? 'danger bg-opacity-10 text-danger' : 'primary bg-opacity-10 text-primary'; ?> me-2">
                                <i class="fas fa-flag me-1"></i> <?php echo ucfirst($assignment['priority']); ?>
                            </span>
                            <span class="badge bg-opacity-25 <?php 
                                echo $assignment['status'] === 'completed' ? 'text-success' : 
                                    ($assignment['status'] === 'in_progress' ? 'text-warning' : 'text-secondary'); 
                            ?> fw-bold">
                                <i class="fas fa-clock me-1"></i>
                                <?php echo ucwords(str_replace('_', ' ', $assignment['status'])); ?>
                            </span>
                        </div>
                        <div class="text-muted">
                            <i class="far fa-calendar-alt me-1"></i>
                            Due: <?php echo date('d M Y', strtotime($assignment['due_date'])); ?>
                        </div>
                    </div>
                    
                    <div class="progress-section mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="text-muted">Progress</div>
                            <div class="fw-bold"><?php echo $assignment['progress_percentage']; ?>%</div>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar" role="progressbar" 
                                 style="width: <?php echo $assignment['progress_percentage']; ?>%"
                                 aria-valuenow="<?php echo $assignment['progress_percentage']; ?>" 
                                 aria-valuemin="0" aria-valuemax="100">
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="stat-card p-3 rounded-3 bg-light">
                                <div class="stat-label text-muted mb-1">Credits</div>
                                <div class="stat-value fw-bold"><?php echo $assignment['credits']; ?></div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stat-card p-3 rounded-3 bg-light">
                                <div class="stat-label text-muted mb-1">Criteria</div>
                                <div class="stat-value fw-bold">
                                    <?php echo $assignment['completed_criteria']; ?>/<?php echo $assignment['total_criteria']; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Content Tabs -->
    <ul class="nav nav-tabs mb-4" id="assignmentTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="overview-tab" data-bs-toggle="tab" 
                    data-bs-target="#overview" type="button" role="tab" aria-selected="true">
                <i class="fas fa-info-circle me-1"></i> Overview
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="criteria-tab" data-bs-toggle="tab" 
                    data-bs-target="#criteria" type="button" role="tab">
                <i class="fas fa-tasks me-1"></i> Assessment Criteria
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="guidance-tab" data-bs-toggle="tab" 
                    data-bs-target="#guidance" type="button" role="tab">
                <i class="fas fa-book-reader me-1"></i> Guidance
            </button>
        </li>
    </ul>

    <div class="tab-content" id="assignmentTabContent">
        <!-- Overview Tab -->
        <div class="tab-pane fade show active" id="overview" role="tabpanel">
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title mb-3">
                                <i class="fas fa-graduation-cap me-2"></i>Unit Overview
                            </h5>
                            <div class="content-area">
                                <?php echo $assignment['unit_overview']; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title mb-3">
                                <i class="fas fa-file-alt me-2"></i>Assignment Overview
                            </h5>
                            <div class="content-area">
                                <?php echo $assignment['overview']; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Criteria Tab -->
        <div class="tab-pane fade" id="criteria" role="tabpanel">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="id" value="<?php echo $assignment_id; ?>">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th style="width: 120px">Code</th>
                                        <th>Description</th>
                                        <th style="width: 100px" class="text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $completed_criteria_ids = [];
                                    $sql = "SELECT criteria_id FROM assignment_criteria_progress 
                                           WHERE assignment_id = ? AND status = 'completed'";
                                    $stmt = $conn->prepare($sql);
                                    $stmt->bind_param("i", $assignment_id);
                                    $stmt->execute();
                                    $progress_result = $stmt->get_result();
                                    while ($row = $progress_result->fetch_assoc()) {
                                        $completed_criteria_ids[] = $row['criteria_id'];
                                    }
                                    
                                    while ($criteria = $criteria_result->fetch_assoc()): 
                                    ?>
                                    <tr>
                                        <td class="align-middle">
                                            <strong><?php echo htmlspecialchars($criteria['criteria_code']); ?></strong>
                                        </td>
                                        <td class="align-middle">
                                            <?php echo htmlspecialchars($criteria['criteria_text']); ?>
                                        </td>
                                        <td class="text-center">
                                            <div class="form-check d-flex justify-content-center">
                                                <input type="checkbox" 
                                                       class="form-check-input" 
                                                       name="completed_criteria[]" 
                                                       value="<?php echo $criteria['id']; ?>"
                                                       <?php echo in_array($criteria['id'], $completed_criteria_ids) ? 'checked' : ''; ?>>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="text-end mt-3">
                            <button type="submit" name="save_criteria" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Save Progress
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Guidance Tab -->
        <div class="tab-pane fade" id="guidance" role="tabpanel">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th style="width: 120px">Type</th>
                                    <th>Content</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($guidance = $guidance_result->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-info bg-opacity-10 text-info">
                                            <?php echo ucfirst($guidance['guidance_type']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($guidance['guidance_text']); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Modern styling */
.card {
    border: 1px solid #e6e6e6;
    border-radius: 12px;
    overflow: hidden;
}

.progress {
    background-color: #e9ecef;
    border-radius: 4px;
}

.progress-bar {
    background-color: #ffb300;
}

.badge {
    padding: 0.5em 1em;
    font-weight: 500;
}

.badge.bg-primary {
    background-color: #ffb300 !important;
    color: #000;
}

.stat-card {
    border: 1px solid #e6e6e6;
    transition: all 0.2s ease-in-out;
}

.stat-card:hover {
    border-color: #ffb300;
}

.stat-value {
    font-size: 1.5rem;
    color: #2c3e50;
}

.nav-tabs {
    border-bottom: 1px solid #e6e6e6;
}

.nav-tabs .nav-link {
    color: #6c757d;
    border: none;
    padding: 1rem 1.5rem;
    font-weight: 500;
}

.nav-tabs .nav-link:hover {
    border: none;
    color: #2c3e50;
}

.nav-tabs .nav-link.active {
    color: #2c3e50;
    border: none;
    border-bottom: 2px solid #ffb300;
}

.table {
    margin-bottom: 0;
}

.table th {
    border-bottom-width: 1px;
    font-weight: 600;
    color: #2c3e50;
}

.table td {
    vertical-align: middle;
}

.form-check-input {
    width: 1.2em;
    height: 1.2em;
    cursor: pointer;
}

.form-check-input:checked {
    background-color: #ffb300;
    border-color: #ffb300;
}

.content-area {
    color: #2c3e50;
    line-height: 1.6;
}

.btn-primary {
    background-color: #2c3e50;
    border-color: #2c3e50;
}

.btn-primary:hover {
    background-color: #34495e;
    border-color: #34495e;
}

.btn-outline-secondary {
    color: #6c757d;
    border-color: #e6e6e6;
}

.btn-outline-secondary:hover {
    background-color: #f8f9fa;
    color: #2c3e50;
    border-color: #e6e6e6;
}
</style>

<script>
// Initialize toast
const criteriaToast = new bootstrap.Toast(document.getElementById('criteriaToast'));

// Debounce function
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Update progress bar with animation
function animateProgressBar(currentProgress, newProgress, duration = 500) {
    const progressBar = document.querySelector('.progress-bar');
    let start = null;
    
    function animate(currentTime) {
        if (!start) start = currentTime;
        const progress = Math.min((currentTime - start) / duration, 1);
        
        const currentWidth = currentProgress + (newProgress - currentProgress) * progress;
        progressBar.style.width = `${currentWidth}%`;
        progressBar.setAttribute('aria-valuenow', currentWidth);
        progressBar.textContent = `${Math.round(currentWidth)}% Complete`;
        
        if (progress < 1) {
            requestAnimationFrame(animate);
        }
    }
    
    requestAnimationFrame(animate);
}

// Update status badge
function updateStatusBadge(progress) {
    const statusBadge = document.querySelector('.badge.bg-success, .badge.bg-warning, .badge.bg-secondary');
    if (progress === 100) {
        statusBadge.className = 'badge bg-success';
        statusBadge.textContent = 'Completed';
    } else if (progress > 0) {
        statusBadge.className = 'badge bg-warning';
        statusBadge.textContent = 'In Progress';
    } else {
        statusBadge.className = 'badge bg-secondary';
        statusBadge.textContent = 'Not Started';
    }
}

// Update criteria count
function updateCriteriaCount(completed, total) {
    const criteriaCount = document.querySelector('.criteria-count');
    if (criteriaCount) {
        criteriaCount.textContent = `${completed}/${total} Criteria`;
    }
}

// Criteria functions
function updateCriteriaStatus(criteriaId, isCompleted) {
    const status = isCompleted ? 'completed' : 'not_started';
    const notes = document.getElementById(`notes_${criteriaId}`).value;
    const criteriaCode = document.getElementById(`criteria_${criteriaId}`).dataset.criteriaCode;
    const checkbox = document.getElementById(`criteria_${criteriaId}`);
    
    // Store original state in case of error
    const originalState = checkbox.checked;
    
    // Show confirmation dialog for marking as complete
    if (isCompleted && !confirm(`Are you sure you want to mark "${criteriaCode}" as complete?`)) {
        checkbox.checked = false;
        return;
    }
    
    // Show loading state
    checkbox.disabled = true;
    
    fetch('update_criteria.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `assignment_id=<?php echo $assignment_id; ?>&criteria_id=${criteriaId}&status=${status}&notes=${encodeURIComponent(notes)}`
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Update progress bar with animation
            const progressBar = document.querySelector('.progress-bar');
            const currentProgress = parseFloat(progressBar.style.width);
            animateProgressBar(currentProgress, data.progress);
            
            // Update status badge
            updateStatusBadge(data.progress);
            
            // Update criteria count
            updateCriteriaCount(data.completed, data.total);
            
            // Update checkbox state based on server response
            checkbox.checked = data.is_completed;
            
            // Show success toast
            document.querySelector('.toast-body').textContent = 
                `Progress updated: ${data.completed}/${data.total} criteria complete (${data.progress}%)`;
            criteriaToast.show();
        } else {
            throw new Error(data.message || 'Failed to update criteria');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while updating the criteria: ' + error.message);
        // Revert checkbox to original state
        checkbox.checked = originalState;
    })
    .finally(() => {
        checkbox.disabled = false;
    });
}

// Add event listeners for checkboxes
document.querySelectorAll('.criteria-checkbox').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        updateCriteriaStatus(this.dataset.criteriaId, this.checked);
    });
});

// Add event listeners for notes with debounce
const debouncedUpdateNotes = debounce((criteriaId, notes, status) => {
    fetch('update_criteria.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `assignment_id=<?php echo $assignment_id; ?>&criteria_id=${criteriaId}&status=${status}&notes=${encodeURIComponent(notes)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update progress bar with animation
            const progressBar = document.querySelector('.progress-bar');
            const currentProgress = parseFloat(progressBar.style.width);
            animateProgressBar(currentProgress, data.progress);
            
            // Update status badge
            updateStatusBadge(data.progress);
            
            // Update criteria count
            updateCriteriaCount(data.completed, data.total);
        }
    })
    .catch(error => console.error('Error updating notes:', error));
}, 500);

document.querySelectorAll('.criteria-notes').forEach(textarea => {
    textarea.addEventListener('input', function() {
        const criteriaId = this.id.split('_')[1];
        const checkbox = document.getElementById(`criteria_${criteriaId}`);
        const status = checkbox.checked ? 'completed' : 'not_started';
        
        debouncedUpdateNotes(criteriaId, this.value, status);
    });
});
</script>

<?php include '../includes/footer.php'; ?> 