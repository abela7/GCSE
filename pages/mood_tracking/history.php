<?php
require_once __DIR__ . '/../../../includes/auth_check.php';

// Include database connection
require_once '../../config/db_connect.php';

// Include required files
require_once __DIR__ . '/includes/functions.php';

// Set page title
$page_title = "Mood History";

// Include header
require_once __DIR__ . '/../../includes/header.php';

// Initialize filter variables
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$mood_level = isset($_GET['mood_level']) ? intval($_GET['mood_level']) : null;
$tag_ids = isset($_GET['tags']) && !empty($_GET['tags']) ? explode(',', $_GET['tags']) : [];
$time_of_day = isset($_GET['time_of_day']) ? $_GET['time_of_day'] : null;
$search = isset($_GET['search']) ? trim($_GET['search']) : null;

// Get mood entries based on filters
$entries = getMoodEntries($start_date, $end_date, $mood_level, $tag_ids, $time_of_day, $search);

// Get all tags for filtering
$all_tags = getMoodTags();
?>

<style>
:root {
    --accent-color: #cdaf56;
    --accent-color-light: #e0cb8c;
    --accent-color-dark: #b09339;
}

/* Card Styles */
.history-card {
    border: none;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    margin-bottom: 1.5rem;
}

/* Filter Card */
.filter-card {
    position: sticky;
    top: 20px;
}

/* Entry Card */
.entry-card {
    border-left: 5px solid var(--accent-color);
    transition: transform 0.2s ease;
}
.entry-card:hover {
    transform: translateX(5px);
}
.entry-card.mood-1 {
    border-left-color: #dc3545;
}
.entry-card.mood-2 {
    border-left-color: #fd7e14;
}
.entry-card.mood-3 {
    border-left-color: #ffc107;
}
.entry-card.mood-4 {
    border-left-color: #28a745;
}
.entry-card.mood-5 {
    border-left-color: #17a2b8;
}

/* Mood Badge */
.mood-badge {
    display: inline-block;
    padding: 0.4rem 0.8rem;
    border-radius: 50rem;
    font-weight: 500;
    color: #fff;
    margin-right: 0.5rem;
    margin-bottom: 0.5rem;
}

/* Tag Badge */
.tag-badge {
    display: inline-block;
    padding: 0.3rem 0.6rem;
    border-radius: 50rem;
    font-size: 0.85rem;
    font-weight: 500;
    color: #fff;
    margin-right: 0.3rem;
    margin-bottom: 0.3rem;
}

/* Button Styles */
.btn-accent {
    background-color: var(--accent-color);
    border-color: var(--accent-color);
    color: #fff;
}
.btn-accent:hover {
    background-color: var(--accent-color-dark);
    border-color: var(--accent-color-dark);
    color: #fff;
}
.btn-outline-accent {
    color: var(--accent-color);
    border-color: var(--accent-color);
}
.btn-outline-accent:hover {
    background-color: var(--accent-color);
    color: #fff;
}

/* Mobile Optimizations */
@media (max-width: 767.98px) {
    .filter-card {
        position: relative;
        top: 0;
        margin-bottom: 1.5rem;
    }
    .mood-badge {
        padding: 0.3rem 0.6rem;
        font-size: 0.9rem;
    }
    .tag-badge {
        padding: 0.2rem 0.5rem;
        font-size: 0.8rem;
    }
    .btn {
        padding: 0.5rem 1rem;
        font-size: 1rem;
    }
    .form-control {
        font-size: 1rem;
        padding: 0.75rem;
        height: auto;
    }
}
</style>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h3 mb-0" style="color: var(--accent-color);">
                <i class="fas fa-history me-2"></i>Mood History
            </h1>
            <p class="text-muted">View and filter your past mood entries</p>
        </div>
        <div class="col-md-4 text-md-end text-center mt-3 mt-md-0">
            <a href="index.php" class="btn btn-outline-accent me-2">
                <i class="fas fa-arrow-left me-1"></i>Dashboard
            </a>
            <a href="entry.php" class="btn btn-accent">
                <i class="fas fa-plus me-1"></i>New Entry
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Filters -->
        <div class="col-lg-3 col-md-4 mb-4">
            <div class="history-card filter-card">
                <div class="card-body">
                    <h5 class="card-title mb-3" style="color: var(--accent-color);">
                        <i class="fas fa-filter me-2"></i>Filters
                    </h5>
                    
                    <form id="filter_form" method="GET" action="history.php">
                    <!-- Date Range -->
                        <div class="mb-3">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                    </div>
                    
                    <!-- Mood Level -->
                        <div class="mb-3">
                            <label for="mood_level" class="form-label">Mood Level</label>
                            <select class="form-select" id="mood_level" name="mood_level">
                                <option value="">All Moods</option>
                                <option value="1" <?php echo $mood_level === 1 ? 'selected' : ''; ?>>😢 Very Bad</option>
                                <option value="2" <?php echo $mood_level === 2 ? 'selected' : ''; ?>>😕 Bad</option>
                                <option value="3" <?php echo $mood_level === 3 ? 'selected' : ''; ?>>😐 Neutral</option>
                                <option value="4" <?php echo $mood_level === 4 ? 'selected' : ''; ?>>🙂 Good</option>
                                <option value="5" <?php echo $mood_level === 5 ? 'selected' : ''; ?>>😄 Very Good</option>
                        </select>
                    </div>
                    
                    <!-- Time of Day -->
                        <div class="mb-3">
                            <label for="time_of_day" class="form-label">Time of Day</label>
                            <select class="form-select" id="time_of_day" name="time_of_day">
                                <option value="">All Times</option>
                            <option value="morning" <?php echo $time_of_day === 'morning' ? 'selected' : ''; ?>>Morning (5am-12pm)</option>
                            <option value="afternoon" <?php echo $time_of_day === 'afternoon' ? 'selected' : ''; ?>>Afternoon (12pm-5pm)</option>
                            <option value="evening" <?php echo $time_of_day === 'evening' ? 'selected' : ''; ?>>Evening (5pm-9pm)</option>
                            <option value="night" <?php echo $time_of_day === 'night' ? 'selected' : ''; ?>>Night (9pm-5am)</option>
                        </select>
                    </div>
                    
                    <!-- Tags -->
                        <?php if (!empty($all_tags)): ?>
                            <div class="mb-3">
                                <label class="form-label">Tags</label>
                                <div class="tag-selector">
                                    <?php foreach ($all_tags as $tag): ?>
                                        <div class="form-check">
                                            <input class="form-check-input tag-checkbox" type="checkbox" 
                                                   id="tag_<?php echo $tag['id']; ?>" 
                                                   value="<?php echo $tag['id']; ?>"
                                                   <?php echo in_array($tag['id'], $tag_ids) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="tag_<?php echo $tag['id']; ?>">
                                                <span class="tag-badge" style="background-color: <?php echo $tag['color']; ?>">
                                <?php echo htmlspecialchars($tag['name']); ?>
                            </span>
                                            </label>
                                        </div>
                            <?php endforeach; ?>
                        </div>
                                <input type="hidden" id="tags" name="tags" value="<?php echo implode(',', $tag_ids); ?>">
                    </div>
                        <?php endif; ?>
                    
                    <!-- Search -->
                        <div class="mb-3">
                            <label for="search" class="form-label">Search Notes</label>
                            <input type="text" class="form-control" id="search" name="search" value="<?php echo $search; ?>" placeholder="Search...">
                    </div>
                    
                    <!-- Filter Buttons -->
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-accent">
                                <i class="fas fa-filter me-1"></i>Apply Filters
                            </button>
                            <button type="button" class="btn btn-outline-secondary" onclick="resetFilters()">
                                <i class="fas fa-undo me-1"></i>Reset Filters
                            </button>
                        </div>
                    </form>
                    </div>
            </div>
        </div>
        
        <!-- Entries -->
        <div class="col-lg-9 col-md-8">
            <?php if (!empty($entries)): ?>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Found <?php echo count($entries); ?> entries</h5>
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="sortDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-sort me-1"></i>Sort
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="sortDropdown">
                            <li><a class="dropdown-item" href="#" onclick="sortEntries('date', 'desc')">Newest First</a></li>
                            <li><a class="dropdown-item" href="#" onclick="sortEntries('date', 'asc')">Oldest First</a></li>
                            <li><a class="dropdown-item" href="#" onclick="sortEntries('mood', 'desc')">Highest Mood First</a></li>
                            <li><a class="dropdown-item" href="#" onclick="sortEntries('mood', 'asc')">Lowest Mood First</a></li>
                        </ul>
            </div>
            </div>
                
                <div id="entries_container">
                    <?php foreach ($entries as $entry): ?>
                        <div class="history-card entry-card mood-<?php echo $entry['mood_level']; ?>" data-entry-id="<?php echo $entry['id']; ?>" data-entry-date="<?php echo $entry['date']; ?>" data-entry-mood="<?php echo $entry['mood_level']; ?>">
                            <div class="card-body">
                                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3">
                                    <h5 class="card-title mb-2 mb-md-0">
                                        <?php
                                        $mood = $entry['mood_level'];
                                        $emoji = '';
                                        if ($mood == 5) $emoji = '😄';
                                        else if ($mood == 4) $emoji = '🙂';
                                        else if ($mood == 3) $emoji = '😐';
                                        else if ($mood == 2) $emoji = '😕';
                                        else $emoji = '😢';
                                        echo $emoji . ' ' . getMoodLevelText($mood);
                                        ?>
                                    </h5>
                                    <div class="text-muted">
                                        <i class="fas fa-calendar-alt me-1"></i><?php echo date('M j, Y g:i A', strtotime($entry['date'])); ?>
                            </div>
                        </div>
                        
                                <?php if (!empty($entry['tags'])): ?>
                                    <div class="mb-3">
                                        <?php foreach ($entry['tags'] as $tag): ?>
                                            <span class="tag-badge" style="background-color: <?php echo $tag['color']; ?>">
                                                <?php echo htmlspecialchars($tag['name']); ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($entry['notes'])): ?>
                                    <div class="card-text mb-3">
                                        <?php echo nl2br(htmlspecialchars($entry['notes'])); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="d-flex justify-content-end">
                            <a href="entry.php?id=<?php echo $entry['id']; ?>" class="btn btn-sm btn-outline-accent me-2">
                                <i class="fas fa-edit me-1"></i>Edit
                            </a>
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteEntry(<?php echo $entry['id']; ?>)">
                                <i class="fas fa-trash me-1"></i>Delete
                            </button>
                        </div>
                    </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="history-card">
                    <div class="card-body text-center py-5">
                    <div class="mb-3">
                            <i class="fas fa-search fa-3x text-muted"></i>
                    </div>
                        <h5 class="mb-3">No entries found</h5>
                        <p class="text-muted mb-4">Try adjusting your filters or create a new mood entry</p>
                        <a href="entry.php" class="btn btn-accent">
                            <i class="fas fa-plus me-1"></i>Create New Entry
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Delete Entry Modal -->
<div class="modal fade" id="deleteEntryModal" tabindex="-1" aria-labelledby="deleteEntryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteEntryModalLabel" style="color: #dc3545;">Delete Entry</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this mood entry?</p>
                <p class="text-danger"><strong>Warning:</strong> This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirm_delete">Delete Entry</button>
            </div>
        </div>
    </div>
</div>

<script>
// Function to handle tag checkboxes
document.addEventListener('DOMContentLoaded', function() {
    const tagCheckboxes = document.querySelectorAll('.tag-checkbox');
    const tagsInput = document.getElementById('tags');
    
    tagCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const selectedTags = [];
            tagCheckboxes.forEach(cb => {
                if (cb.checked) {
                    selectedTags.push(cb.value);
                }
            });
            tagsInput.value = selectedTags.join(',');
        });
    });
});

// Function to reset filters
function resetFilters() {
    document.getElementById('start_date').value = '';
    document.getElementById('end_date').value = '';
    document.getElementById('mood_level').value = '';
    document.getElementById('time_of_day').value = '';
    document.getElementById('search').value = '';
    
    // Reset tag checkboxes
    document.querySelectorAll('.tag-checkbox').forEach(checkbox => {
        checkbox.checked = false;
    });
    document.getElementById('tags').value = '';
    
    // Submit form
    document.getElementById('filter_form').submit();
}

// Function to sort entries
function sortEntries(sortBy, sortOrder) {
    const entriesContainer = document.getElementById('entries_container');
    const entries = Array.from(entriesContainer.children);
    
    entries.sort((a, b) => {
        let valueA, valueB;
        
        if (sortBy === 'date') {
            valueA = new Date(a.dataset.entryDate);
            valueB = new Date(b.dataset.entryDate);
        } else if (sortBy === 'mood') {
            valueA = parseInt(a.dataset.entryMood);
            valueB = parseInt(b.dataset.entryMood);
        }
        
        if (sortOrder === 'asc') {
            return valueA > valueB ? 1 : -1;
        } else {
            return valueA < valueB ? 1 : -1;
        }
    });
    
    // Clear container
    entriesContainer.innerHTML = '';
    
    // Append sorted entries
    entries.forEach(entry => {
        entriesContainer.appendChild(entry);
    });
}

// Function to delete entry
function deleteEntry(entryId) {
    // Store entry ID for deletion
    const confirmButton = document.getElementById('confirm_delete');
    confirmButton.dataset.entryId = entryId;
    
    // Show confirmation modal
    const modal = new bootstrap.Modal(document.getElementById('deleteEntryModal'));
    modal.show();
    
    // Set up confirmation button
    confirmButton.onclick = function() {
        // Disable button and show loading state
        confirmButton.disabled = true;
        confirmButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Deleting...';
        
        // Send AJAX request to delete entry
        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'ajax/delete_entry.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        // Hide modal
                        modal.hide();
                        
                        // Remove entry from DOM
                        const entryElement = document.querySelector(`.entry-card[data-entry-id="${entryId}"]`);
                        if (entryElement) {
                            entryElement.remove();
                        }
                        
                        // Check if there are no more entries
                        const entriesContainer = document.getElementById('entries_container');
                        if (entriesContainer.children.length === 0) {
                            location.reload(); // Reload to show "No entries found" message
                        }
                    } else {
                        alert('Error: ' + response.message);
                    }
                } catch (e) {
                    alert('Error processing response');
                }
            } else {
                alert('Error deleting entry');
            }
            
            // Reset button state
            confirmButton.disabled = false;
            confirmButton.innerHTML = 'Delete Entry';
        };
        xhr.onerror = function() {
            alert('Network error occurred');
            confirmButton.disabled = false;
            confirmButton.innerHTML = 'Delete Entry';
        };
        xhr.send('entry_id=' + entryId);
    };
}

// Helper function to get mood level text
function getMoodLevelText(level) {
    switch (parseInt(level)) {
        case 1: return 'Very Bad';
        case 2: return 'Bad';
        case 3: return 'Neutral';
        case 4: return 'Good';
        case 5: return 'Very Good';
        default: return '';
    }
}

// Improve mobile experience
document.addEventListener('DOMContentLoaded', function() {
    // Fix iOS zoom on input focus
    document.querySelectorAll('input[type="text"], input[type="email"], input[type="tel"], input[type="date"]').forEach(input => {
        input.style.fontSize = '16px';
    });
});
</script>

<?php
// Include footer
require_once __DIR__ . '/../../includes/footer.php';
?>
