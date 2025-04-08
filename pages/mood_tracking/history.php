<?php
// Include required files
require_once __DIR__ . '/includes/functions.php';

// Set page title
$page_title = "Mood History";

// Include header
require_once __DIR__ . '/../../includes/header.php';

// Get filter parameters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$mood_level = isset($_GET['mood_level']) ? intval($_GET['mood_level']) : 0;
$tag_ids = isset($_GET['tags']) ? explode(',', $_GET['tags']) : [];
$time_of_day = isset($_GET['time_of_day']) ? $_GET['time_of_day'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Get mood entries based on filters
$mood_entries = getMoodEntries($start_date, $end_date, $mood_level, $tag_ids, $time_of_day, $search);

// Get mood tags for filter
$mood_tags = getMoodTags();
?>

<style>
:root {
    --accent-color: #cdaf56;
    --accent-color-light: #dbc77a;
    --accent-color-dark: #b99b3e;
}

.mood-emoji {
    font-size: 1.5rem;
}

.tag-badge {
    margin-right: 5px;
    margin-bottom: 5px;
    cursor: pointer;
    transition: all 0.2s;
}

.tag-badge:hover, .tag-badge.selected {
    transform: translateY(-2px);
}

.mood-entry-card {
    border-left: 4px solid var(--accent-color);
    transition: all 0.2s;
    margin-bottom: 1rem;
}

.mood-entry-card:hover {
    transform: translateX(5px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.btn-accent {
    background-color: var(--accent-color);
    border-color: var(--accent-color);
    color: white;
}

.btn-accent:hover {
    background-color: var(--accent-color-dark);
    border-color: var(--accent-color-dark);
    color: white;
}

.btn-outline-accent {
    color: var(--accent-color);
    border-color: var(--accent-color);
}

.btn-outline-accent:hover {
    background-color: var(--accent-color);
    color: white;
}

.filter-card {
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    margin-bottom: 1.5rem;
}

.filter-card .card-header {
    background-color: var(--accent-color);
    color: white;
    font-weight: 600;
}

.filter-section {
    padding: 1rem;
    border-bottom: 1px solid #eee;
}

.filter-section:last-child {
    border-bottom: none;
}

/* Mobile Responsiveness Improvements */
@media (max-width: 767.98px) {
    .mood-emoji {
        font-size: 1.25rem;
    }
    
    .tag-badge {
        font-size: 0.8rem;
        padding: 0.25rem 0.5rem;
    }
    
    .btn {
        padding: 0.375rem 0.75rem;
        font-size: 0.9rem;
    }
    
    h1.h3 {
        font-size: 1.5rem;
    }
    
    .card-title {
        font-size: 1.1rem;
    }
    
    .filter-section {
        padding: 0.75rem;
    }
}

/* Touch-friendly improvements */
@media (max-width: 576px) {
    .filter-buttons {
        display: flex;
        flex-direction: column;
    }
    
    .filter-buttons .btn {
        margin-bottom: 0.5rem;
        width: 100%;
    }
    
    .form-control, .form-select {
        font-size: 16px; /* Prevents iOS zoom on focus */
        padding: 12px;
        height: auto;
    }
    
    .btn {
        padding: 12px 16px;
    }
    
    .mood-entry-actions {
        display: flex;
        justify-content: space-between;
        width: 100%;
        margin-top: 0.5rem;
    }
    
    .mood-entry-actions .btn {
        flex: 1;
        margin: 0 0.25rem;
    }
}
</style>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h3 mb-3">Mood History</h1>
        </div>
        <div class="col-md-4 text-md-end">
            <a href="index.php" class="btn btn-outline-accent me-2">
                <i class="fas fa-arrow-left me-2"></i>Back to Tracker
            </a>
            <a href="entry.php" class="btn btn-accent">
                <i class="fas fa-plus me-2"></i>New Entry
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Filters Column -->
        <div class="col-lg-3 mb-4">
            <div class="filter-card">
                <div class="card-header">
                    <i class="fas fa-filter me-2"></i>
                    Filters
                </div>
                <form id="filter-form" method="get">
                    <!-- Date Range -->
                    <div class="filter-section">
                        <h6 class="mb-3">Date Range</h6>
                        <div class="mb-3">
                            <label for="start-date" class="form-label">From</label>
                            <input type="date" class="form-control" id="start-date" name="start_date" value="<?php echo $start_date; ?>">
                        </div>
                        <div class="mb-3">
                            <label for="end-date" class="form-label">To</label>
                            <input type="date" class="form-control" id="end-date" name="end_date" value="<?php echo $end_date; ?>">
                        </div>
                    </div>
                    
                    <!-- Mood Level -->
                    <div class="filter-section">
                        <h6 class="mb-3">Mood Level</h6>
                        <select class="form-select" id="mood-level" name="mood_level">
                            <option value="0" <?php echo $mood_level === 0 ? 'selected' : ''; ?>>All Moods</option>
                            <option value="1" <?php echo $mood_level === 1 ? 'selected' : ''; ?>>Very Low 😢</option>
                            <option value="2" <?php echo $mood_level === 2 ? 'selected' : ''; ?>>Low 😕</option>
                            <option value="3" <?php echo $mood_level === 3 ? 'selected' : ''; ?>>Neutral 😐</option>
                            <option value="4" <?php echo $mood_level === 4 ? 'selected' : ''; ?>>Good 🙂</option>
                            <option value="5" <?php echo $mood_level === 5 ? 'selected' : ''; ?>>Excellent 😄</option>
                        </select>
                    </div>
                    
                    <!-- Time of Day -->
                    <div class="filter-section">
                        <h6 class="mb-3">Time of Day</h6>
                        <select class="form-select" id="time-of-day" name="time_of_day">
                            <option value="" <?php echo $time_of_day === '' ? 'selected' : ''; ?>>All Times</option>
                            <option value="morning" <?php echo $time_of_day === 'morning' ? 'selected' : ''; ?>>Morning (5am-12pm)</option>
                            <option value="afternoon" <?php echo $time_of_day === 'afternoon' ? 'selected' : ''; ?>>Afternoon (12pm-5pm)</option>
                            <option value="evening" <?php echo $time_of_day === 'evening' ? 'selected' : ''; ?>>Evening (5pm-9pm)</option>
                            <option value="night" <?php echo $time_of_day === 'night' ? 'selected' : ''; ?>>Night (9pm-5am)</option>
                        </select>
                    </div>
                    
                    <!-- Tags -->
                    <div class="filter-section">
                        <h6 class="mb-3">Tags</h6>
                        <div class="tag-container d-flex flex-wrap">
                            <?php foreach ($mood_tags as $tag): 
                                $is_selected = in_array($tag['id'], $tag_ids);
                            ?>
                            <span class="badge tag-badge <?php echo $is_selected ? 'selected' : ''; ?>" 
                                  style="background-color: <?php echo htmlspecialchars($tag['color']); ?>"
                                  data-tag-id="<?php echo $tag['id']; ?>">
                                <?php echo htmlspecialchars($tag['name']); ?>
                            </span>
                            <?php endforeach; ?>
                        </div>
                        <input type="hidden" id="selected-tags" name="tags" value="<?php echo implode(',', $tag_ids); ?>">
                    </div>
                    
                    <!-- Search -->
                    <div class="filter-section">
                        <h6 class="mb-3">Search Notes</h6>
                        <div class="input-group">
                            <input type="text" class="form-control" id="search" name="search" placeholder="Search..." value="<?php echo htmlspecialchars($search); ?>">
                            <button class="btn btn-outline-accent" type="button" id="clear-search">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Filter Buttons -->
                    <div class="filter-section">
                        <div class="filter-buttons">
                            <button type="submit" class="btn btn-accent mb-2">
                                <i class="fas fa-filter me-2"></i>Apply Filters
                            </button>
                            <button type="button" id="clear-filters" class="btn btn-outline-secondary">
                                <i class="fas fa-eraser me-2"></i>Clear Filters
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Entries Column -->
        <div class="col-lg-9">
            <?php if (empty($mood_entries)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                No mood entries found matching your filters.
            </div>
            <?php else: ?>
            <div class="mb-3">
                <p class="text-muted">
                    Showing <?php echo count($mood_entries); ?> mood entries
                    <?php if ($start_date !== date('Y-m-d', strtotime('-30 days')) || $end_date !== date('Y-m-d')): ?>
                    from <?php echo date('M j, Y', strtotime($start_date)); ?> to <?php echo date('M j, Y', strtotime($end_date)); ?>
                    <?php endif; ?>
                </p>
            </div>
            
            <?php foreach ($mood_entries as $entry): 
                $mood_emoji = '';
                $mood_color = '';
                
                switch ($entry['mood_level']) {
                    case 1:
                        $mood_emoji = '😢';
                        $mood_color = '#dc3545';
                        break;
                    case 2:
                        $mood_emoji = '😕';
                        $mood_color = '#fd7e14';
                        break;
                    case 3:
                        $mood_emoji = '😐';
                        $mood_color = '#ffc107';
                        break;
                    case 4:
                        $mood_emoji = '🙂';
                        $mood_color = '#20c997';
                        break;
                    case 5:
                        $mood_emoji = '😄';
                        $mood_color = '#28a745';
                        break;
                }
                
                $entry_date = new DateTime($entry['date']);
                $formatted_date = $entry_date->format('D, M j, Y');
                $formatted_time = $entry_date->format('g:i A');
            ?>
            <div class="card mood-entry-card mb-3" style="border-left-color: <?php echo $mood_color; ?>">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-start mb-2">
                        <div class="d-flex align-items-center mb-2">
                            <div class="mood-emoji me-2"><?php echo $mood_emoji; ?></div>
                            <div>
                                <div class="fw-bold"><?php echo $formatted_date; ?></div>
                                <div class="text-muted small"><?php echo $formatted_time; ?></div>
                            </div>
                        </div>
                        
                        <div class="mood-entry-actions">
                            <a href="entry.php?id=<?php echo $entry['id']; ?>" class="btn btn-sm btn-outline-accent me-2">
                                <i class="fas fa-edit me-1"></i>Edit
                            </a>
                            <button type="button" class="btn btn-sm btn-outline-danger delete-entry" data-entry-id="<?php echo $entry['id']; ?>">
                                <i class="fas fa-trash me-1"></i>Delete
                            </button>
                        </div>
                    </div>
                    
                    <?php if (!empty($entry['notes'])): ?>
                    <div class="mb-3">
                        <?php echo nl2br(htmlspecialchars($entry['notes'])); ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($entry['tags'])): ?>
                    <div class="d-flex flex-wrap">
                        <?php foreach ($entry['tags'] as $tag): ?>
                        <span class="badge me-1 mb-1" style="background-color: <?php echo htmlspecialchars($tag['color']); ?>">
                            <?php echo htmlspecialchars($tag['name']); ?>
                        </span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tag selection
    const tagBadges = document.querySelectorAll('.tag-badge');
    const selectedTagsInput = document.getElementById('selected-tags');
    let selectedTags = selectedTagsInput.value ? selectedTagsInput.value.split(',') : [];
    
    tagBadges.forEach(badge => {
        badge.addEventListener('click', function() {
            const tagId = this.dataset.tagId;
            
            if (this.classList.contains('selected')) {
                // Remove tag from selection
                this.classList.remove('selected');
                const index = selectedTags.indexOf(tagId);
                if (index > -1) {
                    selectedTags.splice(index, 1);
                }
            } else {
                // Add tag to selection
                this.classList.add('selected');
                selectedTags.push(tagId);
            }
            
            // Update hidden input
            selectedTagsInput.value = selectedTags.join(',');
        });
    });
    
    // Clear search button
    document.getElementById('clear-search').addEventListener('click', function() {
        document.getElementById('search').value = '';
    });
    
    // Clear filters button
    document.getElementById('clear-filters').addEventListener('click', function() {
        document.getElementById('start-date').value = '';
        document.getElementById('end-date').value = '';
        document.getElementById('mood-level').value = '0';
        document.getElementById('time-of-day').value = '';
        document.getElementById('search').value = '';
        
        // Clear tag selection
        tagBadges.forEach(badge => {
            badge.classList.remove('selected');
        });
        selectedTagsInput.value = '';
        
        // Submit form
        document.getElementById('filter-form').submit();
    });
    
    // Delete entry buttons
    const deleteButtons = document.querySelectorAll('.delete-entry');
    
    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const entryId = this.dataset.entryId;
            
            if (confirm('Are you sure you want to delete this mood entry?')) {
                fetch('ajax/delete_entry.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'entry_id=' + entryId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Remove entry from DOM
                        this.closest('.mood-entry-card').remove();
                        
                        // Show success message
                        alert('Mood entry deleted successfully!');
                        
                        // Reload page if no entries left
                        if (document.querySelectorAll('.mood-entry-card').length === 0) {
                            window.location.reload();
                        }
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while deleting the mood entry');
                });
            }
        });
    });
});
</script>

<?php
include __DIR__ . '/../../includes/footer.php';
?>
