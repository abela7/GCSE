<?php
require_once '../config/db_connect.php';
include '../includes/header.php';

// Handle unit operations
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_unit'])) {
        $unit_code = mysqli_real_escape_string($conn, $_POST['unit_code']);
        $unit_name = mysqli_real_escape_string($conn, $_POST['unit_name']);
        $description = mysqli_real_escape_string($conn, $_POST['description']);
        $credits = mysqli_real_escape_string($conn, $_POST['credits']);
        $is_graded = isset($_POST['is_graded']) ? 1 : 0;

        $sql = "INSERT INTO units (unit_code, unit_name, description, credits, is_graded) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssis", $unit_code, $unit_name, $description, $credits, $is_graded);
        
        if ($stmt->execute()) {
            echo "<div class='alert alert-success'>Unit added successfully!</div>";
        } else {
            echo "<div class='alert alert-danger'>Error adding unit: " . $conn->error . "</div>";
        }
    }

    if (isset($_POST['edit_unit'])) {
        $unit_id = mysqli_real_escape_string($conn, $_POST['unit_id']);
        $unit_code = mysqli_real_escape_string($conn, $_POST['unit_code']);
        $unit_name = mysqli_real_escape_string($conn, $_POST['unit_name']);
        $description = mysqli_real_escape_string($conn, $_POST['description']);
        $credits = mysqli_real_escape_string($conn, $_POST['credits']);
        $is_graded = isset($_POST['is_graded']) ? 1 : 0;

        $sql = "UPDATE units SET unit_code=?, unit_name=?, description=?, credits=?, is_graded=? 
                WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssiii", $unit_code, $unit_name, $description, $credits, $is_graded, $unit_id);
        
        if ($stmt->execute()) {
            echo "<div class='alert alert-success'>Unit updated successfully!</div>";
        } else {
            echo "<div class='alert alert-danger'>Error updating unit: " . $conn->error . "</div>";
        }
    }
}

// Fetch existing units
$units_query = "SELECT * FROM units ORDER BY unit_code";
$units_result = $conn->query($units_query);
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Add New Unit</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label">Unit Code</label>
                            <input type="text" name="unit_code" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Unit Name</label>
                            <input type="text" name="unit_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Credits</label>
                            <input type="number" name="credits" class="form-control" required>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" name="is_graded" class="form-check-input" id="isGraded">
                            <label class="form-check-label" for="isGraded">Is Graded</label>
                        </div>
                        <button type="submit" name="add_unit" class="btn btn-primary">Add Unit</button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Existing Units</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Name</th>
                                    <th>Credits</th>
                                    <th>Graded</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($unit = $units_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($unit['unit_code']); ?></td>
                                        <td><?php echo htmlspecialchars($unit['unit_name']); ?></td>
                                        <td><?php echo htmlspecialchars($unit['credits']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $unit['is_graded'] ? 'success' : 'secondary'; ?>">
                                                <?php echo $unit['is_graded'] ? 'Yes' : 'No'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary edit-unit" 
                                                    data-unit='<?php echo json_encode($unit); ?>'>
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        </td>
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

<!-- Edit Unit Modal -->
<div class="modal fade" id="editUnitModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Edit Unit</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="unit_id" id="edit_unit_id">
                    <div class="mb-3">
                        <label class="form-label">Unit Code</label>
                        <input type="text" name="unit_code" id="edit_unit_code" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Unit Name</label>
                        <input type="text" name="unit_name" id="edit_unit_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="edit_description" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Credits</label>
                        <input type="number" name="credits" id="edit_credits" class="form-control" required>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" name="is_graded" class="form-check-input" id="edit_is_graded">
                        <label class="form-check-label" for="edit_is_graded">Is Graded</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="edit_unit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('edit-unit') || 
        e.target.parentElement.classList.contains('edit-unit')) {
        const button = e.target.classList.contains('edit-unit') ? e.target : e.target.parentElement;
        const unit = JSON.parse(button.dataset.unit);
        
        document.getElementById('edit_unit_id').value = unit.id;
        document.getElementById('edit_unit_code').value = unit.unit_code;
        document.getElementById('edit_unit_name').value = unit.unit_name;
        document.getElementById('edit_description').value = unit.description;
        document.getElementById('edit_credits').value = unit.credits;
        document.getElementById('edit_is_graded').checked = unit.is_graded == 1;
        
        new bootstrap.Modal(document.getElementById('editUnitModal')).show();
    }
});
</script>

<?php include '../includes/footer.php'; ?> 