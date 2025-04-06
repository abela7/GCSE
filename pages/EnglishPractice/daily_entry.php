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
    <!-- Header Section with improved styling -->
    <div class="row mb-4 align-items-center">
        <div class="col-md-8">
            <h1 class="h2 mb-2 fw-bold text-primary">Daily Practice Entry</h1>
            <p class="text-muted lead">Add your English practice items for today</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="review.php" class="btn btn-outline-primary btn-lg">
                <i class="fas fa-list-alt me-2"></i> Review Entries
            </a>
        </div>
    </div>

    <!-- Date Selection with improved styling -->
    <div class="card shadow-sm border-0 rounded-3 mb-4">
        <div class="card-body bg-light">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label fw-bold">
                        <i class="fas fa-calendar-alt me-2"></i>Select Date
                    </label>
                    <input type="date" class="form-control form-control-lg" name="date" 
                           value="<?php echo $selected_date; ?>" 
                           max="<?php echo $current_date; ?>" 
                           onchange="this.form.submit()">
                </div>
            </form>
        </div>
    </div>

    <div class="row">
        <!-- Entry Form with enhanced styling -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-header bg-primary text-white py-3">
                    <h3 class="h5 mb-0">
                        <i class="fas fa-plus-circle me-2"></i>New Entry
                    </h3>
                </div>
                <div class="card-body">
                    <form id="practiceForm" method="POST" action="save_entry.php" class="needs-validation" novalidate>
                        <input type="hidden" name="practice_day_id" value="<?php echo $practice_day_id; ?>">
                        <input type="hidden" name="date" value="<?php echo $selected_date; ?>">

                        <!-- Category Selection with floating label -->
                        <div class="form-floating mb-4">
                            <select name="category_id" class="form-select" id="categorySelect" required>
                                <option value="">Select a category</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>">
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <label for="categorySelect">Category</label>
                        </div>

                        <!-- Item Title with floating label -->
                        <div class="form-floating mb-4">
                            <input type="text" class="form-control" id="itemTitle" name="item_title" 
                                   placeholder="Enter title" required>
                            <label for="itemTitle">Item Title</label>
                        </div>

                        <!-- Meaning/Rule with floating label -->
                        <div class="form-floating mb-4">
                            <textarea class="form-control" id="itemMeaning" name="item_meaning" 
                                      style="height: 100px" placeholder="Enter meaning" required></textarea>
                            <label for="itemMeaning">Meaning/Rule</label>
                        </div>

                        <!-- Example with floating label -->
                        <div class="form-floating mb-4">
                            <textarea class="form-control" id="itemExample" name="item_example" 
                                      style="height: 100px" placeholder="Enter example" required></textarea>
                            <label for="itemExample">Example</label>
                        </div>

                        <!-- Submit Button -->
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-save me-2"></i>Save Entry
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Recent Entries in Accordion -->
        <div class="col-lg-6">
            <?php
            // Get entries grouped by category
            $entries_by_category = [];
            $stmt = $conn->prepare("
                SELECT pi.id, pi.item_title, pi.item_meaning, pi.item_example, 
                       pc.id as category_id, pc.name as category_name
                FROM practice_items pi
                JOIN practice_categories pc ON pi.category_id = pc.id
                WHERE pi.practice_day_id = ?
                ORDER BY pc.name ASC, pi.id DESC
            ");
            
            if ($stmt) {
                $stmt->bind_param("i", $practice_day_id);
                if ($stmt->execute()) {
                    $result = $stmt->get_result();
                    while ($row = $result->fetch_assoc()) {
                        $cat_id = $row['category_id'];
                        if (!isset($entries_by_category[$cat_id])) {
                            $entries_by_category[$cat_id] = [
                                'name' => $row['category_name'],
                                'entries' => []
                            ];
                        }
                        $entries_by_category[$cat_id]['entries'][] = $row;
                    }
                    $result->free();
                }
                $stmt->close();
            }
            ?>

            <?php if (!empty($entries_by_category)): ?>
                <div class="card shadow-sm border-0 rounded-3">
                    <div class="card-header bg-primary text-white py-3">
                        <h3 class="h5 mb-0">
                            <i class="fas fa-list me-2"></i>Today's Entries
                        </h3>
                    </div>
                    <div class="accordion" id="entriesAccordion">
                        <?php foreach ($entries_by_category as $cat_id => $category): ?>
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button" type="button" 
                                            data-bs-toggle="collapse" 
                                            data-bs-target="#category-<?php echo $cat_id; ?>">
                                        <span class="fw-bold"><?php echo htmlspecialchars($category['name']); ?></span>
                                        <span class="badge bg-primary rounded-pill ms-2">
                                            <?php echo count($category['entries']); ?>
                                        </span>
                                    </button>
                                </h2>
                                <div id="category-<?php echo $cat_id; ?>" 
                                     class="accordion-collapse collapse show" 
                                     data-bs-parent="#entriesAccordion">
                                    <div class="accordion-body p-0">
                                        <div class="list-group list-group-flush">
                                            <?php foreach ($category['entries'] as $entry): ?>
                                                <div class="list-group-item">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <div class="me-3">
                                                            <h6 class="mb-1 text-primary">
                                                                <?php echo htmlspecialchars($entry['item_title']); ?>
                                                            </h6>
                                                            <p class="mb-1 small text-muted">
                                                                <?php echo htmlspecialchars($entry['item_meaning']); ?>
                                                            </p>
                                                            <p class="mb-0 small">
                                                                <em><?php echo htmlspecialchars($entry['item_example']); ?></em>
                                                            </p>
                                                        </div>
                                                        <div class="btn-group btn-group-sm">
                                                            <button type="button" class="btn btn-outline-primary edit-entry" 
                                                                    data-id="<?php echo $entry['id']; ?>"
                                                                    data-title="<?php echo htmlspecialchars($entry['item_title']); ?>"
                                                                    data-meaning="<?php echo htmlspecialchars($entry['item_meaning']); ?>"
                                                                    data-example="<?php echo htmlspecialchars($entry['item_example']); ?>"
                                                                    data-category="<?php echo $entry['category_id']; ?>">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-outline-danger delete-entry" 
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
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="card shadow-sm border-0 rounded-3">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                        <h4 class="text-muted">No entries yet</h4>
                        <p class="text-muted mb-0">Start adding your practice items for today!</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Edit Entry Modal with improved styling -->
<div class="modal fade" id="editEntryModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-edit me-2"></i>Edit Entry
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editEntryForm">
                    <input type="hidden" name="entry_id" id="editEntryId">
                    
                    <!-- Category Selection -->
                    <div class="form-floating mb-3">
                        <select name="category_id" class="form-select" id="editCategory" required>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>">
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <label>Category</label>
                    </div>

                    <!-- Item Title -->
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" name="item_title" id="editTitle" required>
                        <label>Item Title</label>
                    </div>

                    <!-- Meaning/Rule -->
                    <div class="form-floating mb-3">
                        <textarea class="form-control" name="item_meaning" id="editMeaning" 
                                  style="height: 100px" required></textarea>
                        <label>Meaning/Rule</label>
                    </div>

                    <!-- Example -->
                    <div class="form-floating mb-3">
                        <textarea class="form-control" name="item_example" id="editExample" 
                                  style="height: 100px" required></textarea>
                        <label>Example</label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Cancel
                </button>
                <button type="button" class="btn btn-primary" id="saveEdit">
                    <i class="fas fa-save me-2"></i>Save Changes
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal with improved styling -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle me-2"></i>Confirm Delete
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0">Are you sure you want to delete this entry? This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Cancel
                </button>
                <button type="button" class="btn btn-danger" id="confirmDelete">
                    <i class="fas fa-trash me-2"></i>Delete
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Form validation
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });

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