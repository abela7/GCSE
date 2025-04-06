<?php
// Set page title
$page_title = "Settings";

// Set breadcrumbs
$breadcrumbs = [
    'Settings' => null
];

// Include database connection
require_once '../config/db_connect.php';

// Get current settings
$settings_query = "SELECT * FROM settings WHERE id = 1";
$settings_result = $conn->query($settings_query);
$settings = $settings_result->fetch_assoc();

// Process form submission
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $theme = isset($_POST['theme']) ? clean_input($conn, $_POST['theme']) : 'light';
    
    // Validate theme
    if (!in_array($theme, ['light', 'dark'])) {
        $theme = 'light';
    }
    
    // Update settings
    $update_query = "UPDATE settings SET theme = ?, last_updated = NOW() WHERE id = 1";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param('s', $theme);
    $success = $update_stmt->execute();
    
    if ($success) {
        $message = 'Settings updated successfully!';
        $message_type = 'success';
        
        // Update settings variable
        $settings['theme'] = $theme;
    } else {
        $message = 'Failed to update settings: ' . $update_stmt->error;
        $message_type = 'danger';
    }
}

// Include header
include '../includes/header.php';
?>

<!-- Settings Form -->
<div class="card">
    <div class="card-header bg-white">
        <h5 class="mb-0"><i class="fas fa-cog me-2"></i>Application Settings</h5>
    </div>
    <div class="card-body">
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
            <div class="mb-3">
                <label for="theme" class="form-label">Theme</label>
                <select class="form-select" id="theme" name="theme">
                    <option value="light" <?php echo $settings['theme'] === 'light' ? 'selected' : ''; ?>>Light</option>
                    <option value="dark" <?php echo $settings['theme'] === 'dark' ? 'selected' : ''; ?>>Dark</option>
                </select>
                <div class="form-text">Choose the application theme.</div>
            </div>
            
            <div class="mb-3">
                <button type="submit" class="btn btn-primary">Save Settings</button>
            </div>
        </form>
    </div>
</div>

<!-- Data Management -->
<div class="card mt-4">
    <div class="card-header bg-white">
        <h5 class="mb-0"><i class="fas fa-database me-2"></i>Data Management</h5>
    </div>
    <div class="card-body">
        <div class="alert alert-warning">
            <strong>Warning:</strong> These actions cannot be undone. Please proceed with caution.
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Export Data</h5>
                        <p class="card-text">Export all your data as a backup.</p>
                        <button type="button" class="btn btn-outline-primary" id="exportDataBtn">
                            <i class="fas fa-download me-1"></i> Export Data
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Reset Application</h5>
                        <p class="card-text">Reset the application to its default state. All your data will be lost.</p>
                        <button type="button" class="btn btn-outline-danger" id="resetAppBtn">
                            <i class="fas fa-trash me-1"></i> Reset Application
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Export data button
    const exportDataBtn = document.getElementById('exportDataBtn');
    
    if (exportDataBtn) {
        exportDataBtn.addEventListener('click', function() {
            alert('This feature is not yet implemented.');
        });
    }
    
    // Reset application button
    const resetAppBtn = document.getElementById('resetAppBtn');
    
    if (resetAppBtn) {
        resetAppBtn.addEventListener('click', function() {
            if (confirm('Are you sure you want to reset the application? All your data will be lost.')) {
                if (confirm('This action cannot be undone. Are you really sure?')) {
                    alert('This feature is not yet implemented.');
                }
            }
        });
    }
});
</script>

<?php
// Include footer
include '../includes/footer.php';

// Close database connection
close_connection($conn);
?>