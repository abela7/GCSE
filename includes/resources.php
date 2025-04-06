<?php
// Set page title
$page_title = "Resources";

// Set breadcrumbs
$breadcrumbs = [
    'Resources' => null
];

// Set page actions
$page_actions = '
<button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addResourceModal">
    <i class="fas fa-plus me-1"></i> Add Resource
</button>
';

// Include database connection
require_once '../config/db_connect.php';

// Filter by subject if provided
$subject_filter = '';
$subject_id = isset($_GET['subject']) ? (int)$_GET['subject'] : 0;

if ($subject_id > 0) {
    $subject_filter = "WHERE r.subject_id = $subject_id";
    
    // Get subject name for title
    $subject_query = "SELECT name FROM subjects WHERE id = $subject_id";
    $subject_result = $conn->query($subject_query);
    
    if ($subject_result && $subject_result->num_rows > 0) {
        $subject_name = $subject_result->fetch_assoc()['name'];
        $page_title = "$subject_name Resources";
        $breadcrumbs = [
            'Subjects' => '/GCSE/pages/subjects.php',
            $subject_name => "/GCSE/pages/subjects/" . strtolower($subject_name) . ".php",
            'Resources' => null
        ];
    }
}

// Get resources
$resources_query = "SELECT r.*, s.name as subject_name, s.color as subject_color 
                   FROM resources r 
                   JOIN subjects s ON r.subject_id = s.id 
                   $subject_filter
                   ORDER BY r.title ASC";
$resources_result = $conn->query($resources_query);

// Get subjects for dropdown
$subjects_query = "SELECT * FROM subjects ORDER BY name ASC";
$subjects_result = $conn->query($subjects_query);

// Include header
include '../includes/header.php';
?>

<!-- Add Resource Modal -->
<div class="modal fade" id="addResourceModal" tabindex="-1" aria-labelledby="addResourceModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addResourceModalLabel">Add Resource</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addResourceForm" action="../includes/add_resource.php" method="post">
                    <div class="mb-3">
                        <label for="resourceTitle" class="form-label">Title</label>
                        <input type="text" class="form-control" id="resourceTitle" name="title" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="resourceSubject" class="form-label">Subject</label>
                        <select class="form-select" id="resourceSubject" name="subject_id" required>
                            <option value="">-- Select Subject --</option>
                            <?php 
                            if ($subjects_result->num_rows > 0) {
                                $subjects_result->data_seek(0);
                                while ($subject = $subjects_result->fetch_assoc()) {
                                    $selected = ($subject_id == $subject['id']) ? 'selected' : '';
                                    echo '<option value="' . $subject['id'] . '" ' . $selected . '>' . htmlspecialchars($subject['name']) . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="resourceType" class="form-label">Type</label>
                        <select class="form-select" id="resourceType" name="type" required>
                            <option value="book">Book</option>
                            <option value="website">Website</option>
                            <option value="video">Video</option>
                            <option value="document">Document</option>
                            <option value="app">App</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="resourceLink" class="form-label">Link (optional)</label>
                        <input type="url" class="form-control" id="resourceLink" name="link" placeholder="https://example.com">
                    </div>
                    
                    <div class="mb-3">
                        <label for="resourceNotes" class="form-label">Notes (optional)</label>
                        <textarea class="form-control" id="resourceNotes" name="notes" rows="3"></textarea>
                    </div>
                    
                    <div class="text-end">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Resource</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Alert Container for JavaScript notifications -->
<div id="alert-container" class="position-fixed top-0 end-0 p-3" style="z-index: 1050;"></div>

<!-- Resources List -->
<div class="card">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-book me-2"></i>Resources</h5>
        <div class="btn-group btn-group-sm" role="group">
            <button type="button" class="btn btn-outline-secondary active" id="showAllResources">All</button>
            <button type="button" class="btn btn-outline-secondary" id="filterBooks">Books</button>
            <button type="button" class="btn btn-outline-secondary" id="filterWebsites">Websites</button>
            <button type="button" class="btn btn-outline-secondary" id="filterVideos">Videos</button>
            <button type="button" class="btn btn-outline-secondary" id="filterDocuments">Documents</button>
        </div>
    </div>
    <div class="card-body">
        <?php if ($resources_result->num_rows > 0): ?>
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4" id="resourcesContainer">
                <?php while ($resource = $resources_result->fetch_assoc()): ?>
                    <div class="col resource-item" data-type="<?php echo $resource['type']; ?>">
                        <div class="card h-100">
                            <div class="card-header bg-white">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="badge" style="background-color: <?php echo $resource['subject_color']; ?>">
                                        <?php echo htmlspecialchars($resource['subject_name']); ?>
                                    </span>
                                    <span class="badge bg-secondary">
                                        <?php echo ucfirst($resource['type']); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($resource['title']); ?></h5>
                                <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($resource['title']); ?></h5>
                                <?php if ($resource['notes']): ?>
                                    <p class="card-text"><?php echo htmlspecialchars($resource['notes']); ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="card-footer bg-white">
                                <div class="d-flex justify-content-between align-items-center">
                                    <?php if ($resource['link']): ?>
                                        <a href="<?php echo htmlspecialchars($resource['link']); ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                                            <i class="fas fa-external-link-alt me-1"></i> Open
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted small">No link available</span>
                                    <?php endif; ?>
                                    
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-secondary edit-resource-btn" data-resource-id="<?php echo $resource['id']; ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-danger delete-resource-btn" data-resource-id="<?php echo $resource['id']; ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p class="text-muted">No resources found. Add a new resource to get started.</p>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Resource filtering
    const showAllBtn = document.getElementById('showAllResources');
    const filterBooksBtn = document.getElementById('filterBooks');
    const filterWebsitesBtn = document.getElementById('filterWebsites');
    const filterVideosBtn = document.getElementById('filterVideos');
    const filterDocumentsBtn = document.getElementById('filterDocuments');
    const resourceItems = document.querySelectorAll('.resource-item');
    
    showAllBtn.addEventListener('click', function() {
        this.classList.add('active');
        filterBooksBtn.classList.remove('active');
        filterWebsitesBtn.classList.remove('active');
        filterVideosBtn.classList.remove('active');
        filterDocumentsBtn.classList.remove('active');
        
        resourceItems.forEach(item => {
            item.style.display = '';
        });
    });
    
    filterBooksBtn.addEventListener('click', function() {
        this.classList.add('active');
        showAllBtn.classList.remove('active');
        filterWebsitesBtn.classList.remove('active');
        filterVideosBtn.classList.remove('active');
        filterDocumentsBtn.classList.remove('active');
        
        resourceItems.forEach(item => {
            if (item.getAttribute('data-type') === 'book') {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
    });
    
    filterWebsitesBtn.addEventListener('click', function() {
        this.classList.add('active');
        showAllBtn.classList.remove('active');
        filterBooksBtn.classList.remove('active');
        filterVideosBtn.classList.remove('active');
        filterDocumentsBtn.classList.remove('active');
        
        resourceItems.forEach(item => {
            if (item.getAttribute('data-type') === 'website') {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
    });
    
    filterVideosBtn.addEventListener('click', function() {
        this.classList.add('active');
        showAllBtn.classList.remove('active');
        filterBooksBtn.classList.remove('active');
        filterWebsitesBtn.classList.remove('active');
        filterDocumentsBtn.classList.remove('active');
        
        resourceItems.forEach(item => {
            if (item.getAttribute('data-type') === 'video') {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
    });
    
    filterDocumentsBtn.addEventListener('click', function() {
        this.classList.add('active');
        showAllBtn.classList.remove('active');
        filterBooksBtn.classList.remove('active');
        filterWebsitesBtn.classList.remove('active');
        filterVideosBtn.classList.remove('active');
        
        resourceItems.forEach(item => {
            if (item.getAttribute('data-type') === 'document') {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
    });
    
    // Add resource form submission
    const addResourceForm = document.getElementById('addResourceForm');
    
    if (addResourceForm) {
        addResourceForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch(this.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    showAlert('Resource added successfully!', 'success');
                    
                    // Close modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('addResourceModal'));
                    modal.hide();
                    
                    // Reload page to show new resource
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    // Show error message
                    showAlert('Error adding resource: ' + data.message, 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('An error occurred while adding the resource.', 'danger');
            });
        });
    }
    
    // Delete resource buttons
    const deleteResourceBtns = document.querySelectorAll('.delete-resource-btn');
    
    deleteResourceBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const resourceId = this.getAttribute('data-resource-id');
            
            if (confirm('Are you sure you want to delete this resource?')) {
                fetch('../includes/delete_resource.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `resource_id=${resourceId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show success message
                        showAlert('Resource deleted successfully!', 'success');
                        
                        // Remove card from container
                        this.closest('.resource-item').remove();
                    } else {
                        // Show error message
                        showAlert('Error deleting resource: ' + data.message, 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('An error occurred while deleting the resource.', 'danger');
                });
            }
        });
    });
    
    // Function to show alerts
    function showAlert(message, type = 'info') {
        const alertContainer = document.getElementById('alert-container');
        if (!alertContainer) return;
        
        const alert = document.createElement('div');
        alert.className = `alert alert-${type} alert-dismissible fade show`;
        alert.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        
        alertContainer.appendChild(alert);
        
        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            alert.classList.remove('show');
            setTimeout(() => {
                alertContainer.removeChild(alert);
            }, 150);
        }, 5000);
    }
});
</script>

<?php
// Include footer
include '../includes/footer.php';

// Close database connection
close_connection($conn);
?>