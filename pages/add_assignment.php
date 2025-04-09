<?php
require_once __DIR__ . '/../../includes/auth_check.php';

require_once '../config/db_connect.php';
require_once '../includes/header.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Start transaction
        $conn->begin_transaction();

        // Prepare default values
        $unit_id = !empty($_POST['unit_id']) ? $_POST['unit_id'] : NULL;
        $title = !empty($_POST['title']) ? $_POST['title'] : 'Untitled Assignment';
        $unit_overview = !empty($_POST['unit_overview']) ? $_POST['unit_overview'] : '';
        $overview = !empty($_POST['overview']) ? $_POST['overview'] : '';
        $question_text = !empty($_POST['question_text']) ? $_POST['question_text'] : '';
        $word_limit = !empty($_POST['word_limit']) ? $_POST['word_limit'] : 0;
        $credits = !empty($_POST['credits']) ? $_POST['credits'] : 0;
        $due_date = !empty($_POST['due_date']) ? $_POST['due_date'] : NULL;
        $priority = !empty($_POST['priority']) ? $_POST['priority'] : 'medium';
        $estimated_hours = !empty($_POST['estimated_hours']) ? $_POST['estimated_hours'] : 0;

        // Insert assignment
        $stmt = $conn->prepare("INSERT INTO access_assignments (
            unit_id, title, unit_overview, overview, question_text, 
            word_limit, credits, due_date, priority, estimated_hours,
            status, progress_percentage, completed_criteria, total_criteria
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'not_started', 0, 0, 0)");

        $stmt->bind_param("issssiisss", 
            $unit_id,
            $title,
            $unit_overview,
            $overview,
            $question_text,
            $word_limit,
            $credits,
            $due_date,
            $priority,
            $estimated_hours
        );

        $stmt->execute();
        $assignment_id = $conn->insert_id;

        // Insert assessment criteria if provided
        if (isset($_POST['criteria']) && is_array($_POST['criteria'])) {
            $stmt = $conn->prepare("INSERT INTO assessment_criteria (assignment_id, criteria_code, criteria_text, grade_required) VALUES (?, ?, ?, ?)");
            
            foreach ($_POST['criteria'] as $criterion) {
                if (!empty($criterion['text'])) {
                    $code = !empty($criterion['code']) ? $criterion['code'] : 'AC';
                    $grade = !empty($criterion['grade']) ? $criterion['grade'] : 'pass';
                    
                    $stmt->bind_param("isss", 
                        $assignment_id,
                        $code,
                        $criterion['text'],
                        $grade
                    );
                    $stmt->execute();
                }
            }
        }

        // Insert guidance items if provided
        if (isset($_POST['guidance']) && is_array($_POST['guidance'])) {
            $stmt = $conn->prepare("INSERT INTO assignment_guidance (assignment_id, guidance_text, guidance_type) VALUES (?, ?, ?)");
            
            foreach ($_POST['guidance'] as $guidance) {
                if (!empty($guidance['text'])) {
                    $type = !empty($guidance['type']) ? $guidance['type'] : 'general';
                    
                    $stmt->bind_param("iss", 
                        $assignment_id,
                        $guidance['text'],
                        $type
                    );
                    $stmt->execute();
                }
            }
        }

        // Commit transaction
        $conn->commit();
        
        $_SESSION['success_message'] = "Assignment created successfully!";
        header("Location: assignments.php");
        exit();
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $error = "Error: " . $e->getMessage();
    }
}

// Fetch units for dropdown
$units_query = "SELECT * FROM access_course_units ORDER BY unit_code";
$units_result = $conn->query($units_query);

if (!$units_result) {
    $error = "Error fetching units: " . $conn->error;
}
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="fas fa-plus-circle"></i> Add New Assignment</h2>
        </div>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST" class="needs-validation">
        <!-- Basic Information -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Basic Information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="unit_id" class="form-label">Unit</label>
                        <select class="form-select" id="unit_id" name="unit_id">
                            <option value="">Select a unit</option>
                            <?php while($unit = $units_result->fetch_assoc()): ?>
                                <option value="<?php echo $unit['id']; ?>">
                                    <?php echo htmlspecialchars($unit['unit_code'] . ' - ' . $unit['unit_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="title" class="form-label">Assignment Title</label>
                        <input type="text" class="form-control" id="title" name="title" placeholder="Enter title or leave empty">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="credits" class="form-label">Credits</label>
                        <input type="number" class="form-control" id="credits" name="credits" min="0" value="0">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="due_date" class="form-label">Due Date</label>
                        <input type="date" class="form-control" id="due_date" name="due_date">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="priority" class="form-label">Priority</label>
                        <select class="form-select" id="priority" name="priority">
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="word_limit" class="form-label">Word Limit</label>
                        <input type="number" class="form-control" id="word_limit" name="word_limit" min="0" value="0">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="estimated_hours" class="form-label">Estimated Hours</label>
                        <input type="number" class="form-control" id="estimated_hours" name="estimated_hours" min="0" step="0.5" value="0">
                    </div>
                </div>
            </div>
        </div>

        <!-- Unit Overview -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Unit Overview</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="unit_overview" class="form-label">Unit Overview (Optional)</label>
                    <textarea class="form-control rich-editor" id="unit_overview" name="unit_overview" rows="5"></textarea>
                </div>
            </div>
        </div>

        <!-- Assignment Overview -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Assignment Overview</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="overview" class="form-label">Overview (Optional)</label>
                    <textarea class="form-control rich-editor" id="overview" name="overview" rows="5"></textarea>
                </div>
            </div>
        </div>

        <!-- Question/Task -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Question/Task</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="question_text" class="form-label">Question or Task Description (Optional)</label>
                    <textarea class="form-control rich-editor" id="question_text" name="question_text" rows="5"></textarea>
                </div>
            </div>
        </div>

        <!-- Assessment Criteria -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Assessment Criteria (Optional)</h5>
                <button type="button" class="btn btn-sm btn-primary" onclick="addCriteria()">
                    <i class="fas fa-plus"></i> Add Criterion
                </button>
            </div>
            <div class="card-body">
                <div id="criteriaContainer">
                    <!-- Criteria items will be added here -->
                </div>
            </div>
        </div>

        <!-- Guidance -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Guidance (Optional)</h5>
                <button type="button" class="btn btn-sm btn-primary" onclick="addGuidance()">
                    <i class="fas fa-plus"></i> Add Guidance
                </button>
            </div>
            <div class="card-body">
                <div id="guidanceContainer">
                    <!-- Guidance items will be added here -->
                </div>
            </div>
        </div>

        <div class="mb-4">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Save Assignment
            </button>
            <a href="assignments.php" class="btn btn-secondary">
                <i class="fas fa-times"></i> Cancel
            </a>
        </div>
    </form>
</div>

<script>
    // Function to add new criteria
    function addCriteria() {
        const container = document.getElementById('criteriaContainer');
        const criteriaCount = container.children.length;
        
        const criteriaDiv = document.createElement('div');
        criteriaDiv.className = 'row mb-3 criteria-item';
        criteriaDiv.innerHTML = `
            <div class="col-md-2">
                <input type="text" class="form-control" name="criteria[${criteriaCount}][code]" placeholder="AC 1.1">
            </div>
            <div class="col-md-7">
                <input type="text" class="form-control" name="criteria[${criteriaCount}][text]" placeholder="Criteria description">
            </div>
            <div class="col-md-2">
                <select class="form-select" name="criteria[${criteriaCount}][grade]">
                    <option value="pass">Pass</option>
                    <option value="merit">Merit</option>
                    <option value="distinction">Distinction</option>
                </select>
            </div>
            <div class="col-md-1">
                <button type="button" class="btn btn-danger btn-sm" onclick="this.closest('.criteria-item').remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        container.appendChild(criteriaDiv);
    }

    // Function to add new guidance
    function addGuidance() {
        const container = document.getElementById('guidanceContainer');
        const guidanceCount = container.children.length;
        
        const guidanceDiv = document.createElement('div');
        guidanceDiv.className = 'row mb-3 guidance-item';
        guidanceDiv.innerHTML = `
            <div class="col-md-9">
                <input type="text" class="form-control" name="guidance[${guidanceCount}][text]" placeholder="Guidance text">
            </div>
            <div class="col-md-2">
                <select class="form-select" name="guidance[${guidanceCount}][type]">
                    <option value="general">General</option>
                    <option value="research">Research</option>
                    <option value="reference">Reference</option>
                    <option value="technical">Technical</option>
                </select>
            </div>
            <div class="col-md-1">
                <button type="button" class="btn btn-danger btn-sm" onclick="this.closest('.guidance-item').remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        container.appendChild(guidanceDiv);
    }
</script>

<?php include '../includes/footer.php'; ?> 