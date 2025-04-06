<?php
// GCSE/pages/EnglishPractice/daily_entry.php
// Updated Version - No inline script tag

session_start();
require_once __DIR__ . '/../../includes/db_connect.php';
require_once __DIR__ . '/_functions.php';

// Get current date
$current_date = date('Y-m-d');
$selected_date = isset($_GET['date']) ? $_GET['date'] : $current_date;

// Get practice day ID
$practice_day_id = get_or_create_practice_day($conn, $selected_date);

// Get categories
$categories = [];
$cat_result = $conn->query("SELECT id, name FROM practice_categories ORDER BY name ASC");
if ($cat_result) {
    while ($row = $cat_result->fetch_assoc()) {
        $categories[] = $row;
    }
    $cat_result->free();
}

$page_title = "Daily Entry - English";
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container py-4">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h3 mb-2">Daily Practice Entry</h1>
            <p class="text-muted">Add your English practice items for today</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="review.php" class="btn btn-outline-secondary">
                <i class="fas fa-list-alt me-1"></i> Back to Review
            </a>
        </div>
    </div>

    <!-- Date Selection -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Select Date</label>
                    <input type="date" class="form-control" name="date" value="<?php echo $selected_date; ?>" 
                           max="<?php echo $current_date; ?>" onchange="this.form.submit()">
                </div>
            </form>
        </div>
    </div>

    <!-- Entry Form -->
    <div class="card">
        <div class="card-body">
            <form id="practiceForm" method="POST" action="save_entry.php">
                <input type="hidden" name="practice_day_id" value="<?php echo $practice_day_id; ?>">
                <input type="hidden" name="date" value="<?php echo $selected_date; ?>">

                <!-- Category Selection -->
                <div class="mb-4">
                    <label class="form-label">Category</label>
                    <select name="category_id" class="form-select" required>
                        <option value="">Select a category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>">
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Item Title -->
                <div class="mb-4">
                    <label class="form-label">Item Title</label>
                    <input type="text" class="form-control" name="item_title" required 
                           placeholder="e.g., Present Perfect Tense">
                </div>

                <!-- Meaning/Rule -->
                <div class="mb-4">
                    <label class="form-label">Meaning/Rule</label>
                    <textarea class="form-control" name="item_meaning" rows="3" required 
                              placeholder="Explain the meaning or rule..."></textarea>
                </div>

                <!-- Example -->
                <div class="mb-4">
                    <label class="form-label">Example</label>
                    <textarea class="form-control" name="item_example" rows="3" required 
                              placeholder="Provide an example..."></textarea>
                </div>

                <!-- Submit Button -->
                <div class="text-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Save Entry
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Recent Entries -->
    <?php
    // Get recent entries for the selected date
    $recent_entries = [];
    $stmt = $conn->prepare("
        SELECT pi.id, pi.item_title, pi.item_meaning, pi.item_example, pc.name as category_name
        FROM practice_items pi
        JOIN practice_categories pc ON pi.category_id = pc.id
        WHERE pi.practice_day_id = ?
        ORDER BY pi.id DESC
    ");
    
    if ($stmt) {
        $stmt->bind_param("i", $practice_day_id);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $recent_entries[] = $row;
            }
            $result->free();
        }
        $stmt->close();
    }
    ?>

    <?php if (!empty($recent_entries)): ?>
        <div class="card mt-4">
            <div class="card-header bg-light">
                <h5 class="mb-0">Today's Entries</h5>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <?php foreach ($recent_entries as $entry): ?>
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1"><?php echo htmlspecialchars($entry['item_title']); ?></h6>
                                    <small class="text-muted"><?php echo htmlspecialchars($entry['category_name']); ?></small>
                                </div>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-sm btn-outline-primary edit-entry" 
                                            data-id="<?php echo $entry['id']; ?>"
                                            data-title="<?php echo htmlspecialchars($entry['item_title']); ?>"
                                            data-meaning="<?php echo htmlspecialchars($entry['item_meaning']); ?>"
                                            data-example="<?php echo htmlspecialchars($entry['item_example']); ?>"
                                            data-category="<?php echo $entry['category_id']; ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger delete-entry" 
                                            data-id="<?php echo $entry['id']; ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Edit Entry Modal -->
<div class="modal fade" id="editEntryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Entry</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editEntryForm">
                    <input type="hidden" name="entry_id" id="editEntryId">
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select name="category_id" class="form-select" id="editCategory" required>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>">
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Item Title</label>
                        <input type="text" class="form-control" name="item_title" id="editTitle" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Meaning/Rule</label>
                        <textarea class="form-control" name="item_meaning" id="editMeaning" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Example</label>
                        <textarea class="form-control" name="item_example" id="editExample" rows="3" required></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveEdit">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this entry? This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">Delete</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Edit Entry
    const editModal = new bootstrap.Modal(document.getElementById('editEntryModal'));
    const editForm = document.getElementById('editEntryForm');
    let currentEntryId = null;

    document.querySelectorAll('.edit-entry').forEach(button => {
        button.addEventListener('click', function() {
            currentEntryId = this.dataset.id;
            document.getElementById('editEntryId').value = currentEntryId;
            document.getElementById('editCategory').value = this.dataset.category;
            document.getElementById('editTitle').value = this.dataset.title;
            document.getElementById('editMeaning').value = this.dataset.meaning;
            document.getElementById('editExample').value = this.dataset.example;
            editModal.show();
        });
    });

    document.getElementById('saveEdit').addEventListener('click', function() {
        const formData = new FormData(editForm);
        formData.append('entry_id', currentEntryId);

        fetch('update_entry.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error updating entry: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while updating the entry.');
        });
    });

    // Delete Entry
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    let entryToDelete = null;

    document.querySelectorAll('.delete-entry').forEach(button => {
        button.addEventListener('click', function() {
            entryToDelete = this.dataset.id;
            deleteModal.show();
        });
    });

    document.getElementById('confirmDelete').addEventListener('click', function() {
        if (!entryToDelete) return;

        fetch('delete_entry.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'entry_id=' + entryToDelete
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error deleting entry: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while deleting the entry.');
        });
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?> 