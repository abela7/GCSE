<?php
require_once __DIR__ . '/../../includes/auth_check.php';

ob_start(); // Start output buffering
require_once '../config/db_connect.php';
include_once __DIR__ . '/../../includes/header.php';

if (!isset($_GET['id'])) {
    header('Location: assignments.php');
    exit;
}

$assignment_id = mysqli_real_escape_string($conn, $_GET['id']);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $unit_id = mysqli_real_escape_string($conn, $_POST['unit_id']);
    $credits = mysqli_real_escape_string($conn, $_POST['credits']);
    $due_date = mysqli_real_escape_string($conn, $_POST['due_date']);
    $priority = mysqli_real_escape_string($conn, $_POST['priority']);
    $overview = mysqli_real_escape_string($conn, $_POST['overview']);
    $question_text = mysqli_real_escape_string($conn, $_POST['question_text']);
    $word_limit = mysqli_real_escape_string($conn, $_POST['word_limit']);
    $estimated_hours = mysqli_real_escape_string($conn, $_POST['estimated_hours']);

    try {
        $conn->begin_transaction();

        // Update assignment
        $sql = "UPDATE access_assignments SET 
                title = ?, unit_id = ?, credits = ?, due_date = ?, 
                priority = ?, overview = ?, question_text = ?,
                word_limit = ?, estimated_hours = ?
                WHERE id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("siissssiis", 
            $title, $unit_id, $credits, $due_date, 
            $priority, $overview, $question_text,
            $word_limit, $estimated_hours, $assignment_id
        );
        $stmt->execute();

        // Update existing criteria if provided
        if (isset($_POST['criteria']) && is_array($_POST['criteria'])) {
            foreach ($_POST['criteria'] as $criteria_id => $criteria) {
                if (isset($criteria['criteria_text']) && isset($criteria['grade_required'])) {
                    $criteria_text = mysqli_real_escape_string($conn, $criteria['criteria_text']);
                    $grade_required = mysqli_real_escape_string($conn, $criteria['grade_required']);
                    
                    $sql = "UPDATE assessment_criteria SET 
                            criteria_text = ?, grade_required = ?
                            WHERE id = ? AND assignment_id = ?";
                    
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ssii", 
                        $criteria_text, $grade_required, $criteria_id, $assignment_id
                    );
                    $stmt->execute();
                }
            }
        }

        // Handle new criteria
        if (isset($_POST['new_criteria']) && is_array($_POST['new_criteria'])) {
            foreach ($_POST['new_criteria'] as $new_criteria) {
                if (!empty($new_criteria['criteria_code']) && !empty($new_criteria['criteria_text'])) {
                    $criteria_code = mysqli_real_escape_string($conn, $new_criteria['criteria_code']);
                    $criteria_text = mysqli_real_escape_string($conn, $new_criteria['criteria_text']);
                    $grade_required = mysqli_real_escape_string($conn, $new_criteria['grade_required']);
                    
                    $sql = "INSERT INTO assessment_criteria (assignment_id, criteria_code, criteria_text, grade_required) 
                            VALUES (?, ?, ?, ?)";
                    
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("isss", 
                        $assignment_id, $criteria_code, $criteria_text, $grade_required
                    );
                    $stmt->execute();
                }
            }
        }

        // Update existing guidance if provided
        if (isset($_POST['guidance']) && is_array($_POST['guidance'])) {
            foreach ($_POST['guidance'] as $guidance_id => $guidance) {
                if (isset($guidance['guidance_text']) && isset($guidance['guidance_type'])) {
                    $guidance_text = mysqli_real_escape_string($conn, $guidance['guidance_text']);
                    $guidance_type = mysqli_real_escape_string($conn, $guidance['guidance_type']);
                    
                    $sql = "UPDATE assignment_guidance SET 
                            guidance_text = ?, guidance_type = ?
                            WHERE id = ? AND assignment_id = ?";
                    
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ssii", 
                        $guidance_text, $guidance_type, $guidance_id, $assignment_id
                    );
                    $stmt->execute();
                }
            }
        }

        // Handle new guidance items
        if (isset($_POST['new_guidance']) && is_array($_POST['new_guidance'])) {
            foreach ($_POST['new_guidance'] as $new_guidance) {
                if (!empty($new_guidance['guidance_text'])) {
                    $guidance_text = mysqli_real_escape_string($conn, $new_guidance['guidance_text']);
                    $guidance_type = mysqli_real_escape_string($conn, $new_guidance['guidance_type']);
                    
                    $sql = "INSERT INTO assignment_guidance (assignment_id, guidance_type, guidance_text) 
                            VALUES (?, ?, ?)";
                    
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("iss", 
                        $assignment_id, $guidance_type, $guidance_text
                    );
                    $stmt->execute();
                }
            }
        }

        // Handle guidance deletions
        if (isset($_POST['delete_guidance']) && is_array($_POST['delete_guidance'])) {
            foreach ($_POST['delete_guidance'] as $guidance_id) {
                $guidance_id = mysqli_real_escape_string($conn, $guidance_id);
                $sql = "DELETE FROM assignment_guidance WHERE id = ? AND assignment_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ii", $guidance_id, $assignment_id);
                $stmt->execute();
            }
        }

        $conn->commit();
        $_SESSION['success_message'] = "Assignment updated successfully!";
        
        // Use JavaScript for redirection instead of header()
        echo "<script>
            window.location.href = 'view_assignment.php?id=" . $assignment_id . "';
        </script>";
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = "Error updating assignment: " . $e->getMessage();
    }
}

// Fetch assignment details
$sql = "SELECT a.*, u.unit_code, u.unit_name 
        FROM access_assignments a 
        LEFT JOIN access_course_units u ON a.unit_id = u.id 
        WHERE a.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $assignment_id);
$stmt->execute();
$assignment = $stmt->get_result()->fetch_assoc();

if (!$assignment) {
    header('Location: assignments.php');
    exit;
}

// Fetch all units for dropdown
$sql = "SELECT id, unit_code, unit_name FROM access_course_units ORDER BY unit_code";
$units_result = $conn->query($sql);

// Fetch criteria
$sql = "SELECT * FROM assessment_criteria WHERE assignment_id = ? ORDER BY criteria_code";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $assignment_id);
$stmt->execute();
$criteria_result = $stmt->get_result();

// After fetching criteria, add this to fetch guidance
$sql = "SELECT * FROM assignment_guidance WHERE assignment_id = ? ORDER BY id";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $assignment_id);
$stmt->execute();
$guidance_result = $stmt->get_result();
?>

<div class="container-fluid mt-4">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php 
                    echo $_SESSION['error_message'];
                    unset($_SESSION['error_message']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="card shadow-lg border-0 rounded-lg">
                <div class="card-header bg-gradient-primary-to-secondary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-edit me-2"></i>Edit Assignment
                        </h5>
                        <a href="assignments.php" class="btn btn-light btn-sm">
                            <i class="fas fa-arrow-left me-1"></i>Back to Assignments
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <form method="POST" action="" class="needs-validation" novalidate>
                        <!-- Basic Information Section -->
                        <div class="row g-4 mb-4">
                            <div class="col-md-8">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" id="title" name="title" 
                                           value="<?php echo htmlspecialchars($assignment['title']); ?>" required>
                                    <label for="title">Assignment Title</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <select class="form-select" id="unit_id" name="unit_id" required>
                                        <?php while($unit = $units_result->fetch_assoc()): ?>
                                            <option value="<?php echo $unit['id']; ?>" 
                                                    <?php echo $unit['id'] == $assignment['unit_id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($unit['unit_code'] . ' - ' . $unit['unit_name']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                    <label for="unit_id">Unit</label>
                                </div>
                            </div>
                        </div>

                        <!-- Details Section -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <div class="form-floating">
                                    <input type="number" class="form-control" id="credits" name="credits" 
                                           value="<?php echo $assignment['credits']; ?>" required>
                                    <label for="credits">Credits</label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-floating">
                                    <input type="number" class="form-control" id="word_limit" name="word_limit" 
                                           value="<?php echo $assignment['word_limit']; ?>">
                                    <label for="word_limit">Word Limit</label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-floating">
                                    <input type="date" class="form-control" id="due_date" name="due_date" 
                                           value="<?php echo date('Y-m-d', strtotime($assignment['due_date'])); ?>" required>
                                    <label for="due_date">Due Date</label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-floating">
                                    <select class="form-select" id="priority" name="priority" required>
                                        <option value="low" <?php echo $assignment['priority'] == 'low' ? 'selected' : ''; ?>>Low</option>
                                        <option value="medium" <?php echo $assignment['priority'] == 'medium' ? 'selected' : ''; ?>>Medium</option>
                                        <option value="high" <?php echo $assignment['priority'] == 'high' ? 'selected' : ''; ?>>High</option>
                                    </select>
                                    <label for="priority">Priority</label>
                                </div>
                            </div>
                        </div>

                        <!-- Content Section -->
                        <div class="mb-4">
                            <!-- Overview Section -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0">Assignment Overview</h5>
                                </div>
                                <div class="card-body">
                                    <label class="form-label">Overview</label>
                                    <div class="editor-wrapper">
                                        <textarea id="overview" name="overview"><?php echo htmlspecialchars($assignment['overview']); ?></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Question/Task Section -->
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">Question/Task</h5>
                                </div>
                                <div class="card-body">
                                    <label class="form-label">Question or Task Description</label>
                                    <div class="editor-wrapper">
                                        <textarea id="question_text" name="question_text"><?php echo htmlspecialchars($assignment['question_text']); ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Assessment Criteria Section -->
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">Assessment Criteria</h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="border-0">Code</th>
                                                <th class="border-0">Criteria</th>
                                                <th class="border-0" style="width: 200px;">Grade Required</th>
                                            </tr>
                                        </thead>
                                        <tbody id="criteriaTableBody">
                                            <?php 
                                            $hasCriteria = false;
                                            while($criteria = $criteria_result->fetch_assoc()): 
                                                $hasCriteria = true;
                                            ?>
                                                <tr>
                                                    <td class="align-middle"><?php echo htmlspecialchars($criteria['criteria_code']); ?></td>
                                                    <td>
                                                        <textarea class="form-control criteria-textarea" 
                                                                  name="criteria[<?php echo $criteria['id']; ?>][criteria_text]" 
                                                                  rows="3"><?php echo htmlspecialchars($criteria['criteria_text']); ?></textarea>
                                                    </td>
                                                    <td>
                                                        <select class="form-select" 
                                                                name="criteria[<?php echo $criteria['id']; ?>][grade_required]">
                                                            <option value="pass" <?php echo $criteria['grade_required'] == 'pass' ? 'selected' : ''; ?>>Pass</option>
                                                            <option value="merit" <?php echo $criteria['grade_required'] == 'merit' ? 'selected' : ''; ?>>Merit</option>
                                                            <option value="distinction" <?php echo $criteria['grade_required'] == 'distinction' ? 'selected' : ''; ?>>Distinction</option>
                                                        </select>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                            
                                            <?php if (!$hasCriteria): ?>
                                                <tr>
                                                    <td colspan="3" class="text-center py-4">
                                                        <p class="text-muted mb-0">No assessment criteria found.</p>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="card-footer bg-light">
                                <button type="button" class="btn btn-success" id="addCriteriaBtn">
                                    <i class="fas fa-plus me-1"></i>Add New Criteria
                                </button>
                            </div>
                        </div>

                        <!-- Guidance Section -->
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">Assignment Guidance</h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="border-0">Type</th>
                                                <th class="border-0">Guidance Text</th>
                                                <th class="border-0" style="width: 100px;">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="guidanceTableBody">
                                            <?php 
                                            $hasGuidance = false;
                                            while($guidance = $guidance_result->fetch_assoc()): 
                                                $hasGuidance = true;
                                            ?>
                                                <tr>
                                                    <td>
                                                        <select class="form-select" 
                                                                name="guidance[<?php echo $guidance['id']; ?>][guidance_type]">
                                                            <option value="general" <?php echo $guidance['guidance_type'] == 'general' ? 'selected' : ''; ?>>General</option>
                                                            <option value="technical" <?php echo $guidance['guidance_type'] == 'technical' ? 'selected' : ''; ?>>Technical</option>
                                                            <option value="academic" <?php echo $guidance['guidance_type'] == 'academic' ? 'selected' : ''; ?>>Academic</option>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <textarea class="form-control guidance-textarea" 
                                                                  name="guidance[<?php echo $guidance['id']; ?>][guidance_text]" 
                                                                  rows="2"><?php echo htmlspecialchars($guidance['guidance_text']); ?></textarea>
                                                    </td>
                                                    <td>
                                                        <button type="button" class="btn btn-danger btn-sm w-100 remove-existing-guidance" 
                                                                data-guidance-id="<?php echo $guidance['id']; ?>">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                            
                                            <?php if (!$hasGuidance): ?>
                                                <tr>
                                                    <td colspan="3" class="text-center py-4">
                                                        <p class="text-muted mb-0">No guidance items found.</p>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="card-footer bg-light">
                                <button type="button" class="btn btn-success" id="addGuidanceBtn">
                                    <i class="fas fa-plus me-1"></i>Add New Guidance
                                </button>
                            </div>
                        </div>

                        <!-- Time Estimation -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="form-floating">
                                    <input type="number" class="form-control" id="estimated_hours" name="estimated_hours" 
                                           value="<?php echo $assignment['estimated_hours']; ?>">
                                    <label for="estimated_hours">Estimated Hours</label>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-flex justify-content-end gap-2">
                            <a href="view_assignment.php?id=<?php echo $assignment_id; ?>" 
                               class="btn btn-light btn-lg px-4">
                                <i class="fas fa-times me-2"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-primary btn-lg px-4">
                                <i class="fas fa-save me-2"></i>Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.bg-gradient-primary-to-secondary {
    background: linear-gradient(45deg, #1a73e8, #34a853);
}
.card {
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    background: #fff;
}
.card-header {
    background: #f8f9fa;
    border-bottom: 1px solid #e0e0e0;
    padding: 1rem 1.25rem;
}
.card-header h5 {
    font-size: 1.1rem;
    font-weight: 500;
    color: #333;
    margin: 0;
}
.card-body {
    padding: 1.25rem;
}
.form-label {
    font-size: 0.9rem;
    font-weight: 500;
    color: #666;
    margin-bottom: 0.5rem;
}
.editor-wrapper {
    border: 1px solid #e0e0e0;
    border-radius: 4px;
    overflow: hidden;
}
.tox-tinymce {
    border: none !important;
    border-radius: 4px !important;
}
.tox .tox-toolbar {
    background: #f8f9fa !important;
    border-bottom: 1px solid #e0e0e0 !important;
    padding: 4px !important;
}
.tox .tox-toolbar__group {
    border: none !important;
    padding: 0 4px !important;
}
.tox .tox-tbtn {
    border-radius: 4px !important;
    padding: 4px !important;
    margin: 0 1px !important;
}
.tox .tox-tbtn:hover {
    background: #e9ecef !important;
}
.tox .tox-tbtn--select {
    padding: 4px 8px !important;
    margin: 0 1px !important;
}
.tox .tox-split-button {
    border-radius: 4px !important;
    margin: 0 1px !important;
}
.tox .tox-tbtn svg {
    fill: #444 !important;
}
.tox .tox-statusbar {
    border-top: 1px solid #e0e0e0 !important;
}
.criteria-textarea {
    border: 1px solid #e9ecef;
    resize: vertical;
    min-height: 80px;
    transition: all 0.2s ease-in-out;
}
.criteria-textarea:focus {
    border-color: #80bdff;
    box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
}
.tox.tox-tinymce {
    width: 100% !important;
    min-height: 300px !important;
}
.editor-wrapper {
    margin-bottom: 20px;
}
.tox.tox-tinymce {
    border: none !important;
    box-shadow: none !important;
}
.tox .tox-toolbar__primary {
    background-color: #f8f9fa !important;
    border-bottom: 1px solid #e0e0e0 !important;
    padding: 4px !important;
}
.tox .tox-tbtn:hover {
    background-color: #e9ecef !important;
}
.guidance-textarea {
    border: 1px solid #e9ecef;
    resize: vertical;
    min-height: 60px;
    transition: all 0.2s ease-in-out;
}
.guidance-textarea:focus {
    border-color: #80bdff;
    box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
}
</style>

<script src="https://cdn.tiny.cloud/1/qagffr3pkuv17a8on1afax661irst1hbr4e6tbv888sz91jc/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>

<script>
// Initialize TinyMCE
tinymce.init({
    selector: '#overview, #question_text',
    plugins: 'lists link image table code help wordcount',
    toolbar: 'undo redo | blocks | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image | table | code',
    height: 300,
    menubar: false,
    statusbar: true,
    resize: false,
    skin: 'oxide',
    content_css: 'default',
    body_class: 'custom-editor-body',
    content_style: `
        .custom-editor-body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            font-size: 14px;
            line-height: 1.6;
            color: #333;
            margin: 1rem;
            padding: 0;
        }
    `,
    setup: function (editor) {
        editor.on('init', function () {
            editor.getContainer().style.transition = 'border-color .15s ease-in-out';
        });
    }
});

// Form validation
(function () {
    'use strict'
    var forms = document.querySelectorAll('.needs-validation')
    Array.prototype.slice.call(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault()
                event.stopPropagation()
            }
            form.classList.add('was-validated')
        }, false)
    })
})()

document.addEventListener('DOMContentLoaded', function() {
    const addCriteriaBtn = document.getElementById('addCriteriaBtn');
    const criteriaTableBody = document.getElementById('criteriaTableBody');
    let newCriteriaCount = 0;

    addCriteriaBtn.addEventListener('click', function() {
        // Remove "No criteria found" message if it exists
        const noDataRow = criteriaTableBody.querySelector('td[colspan="3"]');
        if (noDataRow) {
            noDataRow.parentElement.remove();
        }

        const newRow = document.createElement('tr');
        newRow.innerHTML = `
            <td>
                <input type="text" class="form-control" 
                       name="new_criteria[${newCriteriaCount}][criteria_code]" 
                       placeholder="AC X.X" required>
            </td>
            <td>
                <textarea class="form-control criteria-textarea" 
                          name="new_criteria[${newCriteriaCount}][criteria_text]" 
                          rows="3" placeholder="Enter criteria description" required></textarea>
            </td>
            <td>
                <select class="form-select" 
                        name="new_criteria[${newCriteriaCount}][grade_required]">
                    <option value="pass">Pass</option>
                    <option value="merit">Merit</option>
                    <option value="distinction">Distinction</option>
                </select>
                <button type="button" class="btn btn-danger btn-sm mt-2 w-100 remove-criteria">
                    <i class="fas fa-trash me-1"></i>Remove
                </button>
            </td>
        `;
        criteriaTableBody.appendChild(newRow);

        // Add event listener to remove button
        newRow.querySelector('.remove-criteria').addEventListener('click', function() {
            newRow.remove();
            // If no criteria left, show the "No criteria found" message
            if (criteriaTableBody.children.length === 0) {
                const emptyRow = document.createElement('tr');
                emptyRow.innerHTML = `
                    <td colspan="3" class="text-center py-4">
                        <p class="text-muted mb-0">No assessment criteria found.</p>
                    </td>
                `;
                criteriaTableBody.appendChild(emptyRow);
            }
        });

        newCriteriaCount++;
    });

    // Guidance functionality
    const addGuidanceBtn = document.getElementById('addGuidanceBtn');
    const guidanceTableBody = document.getElementById('guidanceTableBody');
    let newGuidanceCount = 0;

    addGuidanceBtn.addEventListener('click', function() {
        // Remove "No guidance found" message if it exists
        const noDataRow = guidanceTableBody.querySelector('td[colspan="3"]');
        if (noDataRow) {
            noDataRow.parentElement.remove();
        }

        const newRow = document.createElement('tr');
        newRow.innerHTML = `
            <td>
                <select class="form-select" name="new_guidance[${newGuidanceCount}][guidance_type]">
                    <option value="general">General</option>
                    <option value="technical">Technical</option>
                    <option value="academic">Academic</option>
                </select>
            </td>
            <td>
                <textarea class="form-control guidance-textarea" 
                          name="new_guidance[${newGuidanceCount}][guidance_text]" 
                          rows="2" placeholder="Enter guidance text" required></textarea>
            </td>
            <td>
                <button type="button" class="btn btn-danger btn-sm w-100 remove-guidance">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
        guidanceTableBody.appendChild(newRow);

        // Add event listener to remove button
        newRow.querySelector('.remove-guidance').addEventListener('click', function() {
            newRow.remove();
            // If no guidance left, show the "No guidance found" message
            if (guidanceTableBody.children.length === 0) {
                const emptyRow = document.createElement('tr');
                emptyRow.innerHTML = `
                    <td colspan="3" class="text-center py-4">
                        <p class="text-muted mb-0">No guidance items found.</p>
                    </td>
                `;
                guidanceTableBody.appendChild(emptyRow);
            }
        });

        newGuidanceCount++;
    });

    // Handle removal of existing guidance items
    document.querySelectorAll('.remove-existing-guidance').forEach(button => {
        button.addEventListener('click', function() {
            const guidanceId = this.dataset.guidanceId;
            const row = this.closest('tr');
            
            // Add hidden input to mark this guidance for deletion
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'delete_guidance[]';
            hiddenInput.value = guidanceId;
            document.querySelector('form').appendChild(hiddenInput);
            
            row.remove();
            
            // If no guidance left, show the "No guidance found" message
            if (guidanceTableBody.children.length === 0) {
                const emptyRow = document.createElement('tr');
                emptyRow.innerHTML = `
                    <td colspan="3" class="text-center py-4">
                        <p class="text-muted mb-0">No guidance items found.</p>
                    </td>
                `;
                guidanceTableBody.appendChild(emptyRow);
            }
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?> 