<?php
// Include required files
require_once __DIR__ . '/includes/functions.php';

// Set page title
$page_title = "Mood Tracker";

// Include header
require_once __DIR__ . '/../../includes/header.php';

// Get recent mood entries
$recent_entries = getMoodEntries(null, null, null, [], null, null);
$recent_entries = array_slice($recent_entries, 0, 5);

// Get all tags for filtering
$all_tags = getMoodTags();

// Get current month for calendar
$current_month = date('Y-m');
$month_entries = getMoodEntriesByDay($current_month);

// Get mood statistics
$stats = getMoodStatistics(date('Y-m-d', strtotime('-30 days')), date('Y-m-d'));
?>

<style>
:root {
    --accent-color: #cdaf56;
    --accent-color-light: #e0cb8c;
    --accent-color-dark: #b09339;
}

/* Card Styles */
.dashboard-card {
    border: none;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    height: 100%;
}
.dashboard-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.15);
}

/* Mood Emoji Styles */
.mood-emoji {
    font-size: 2rem;
    margin-bottom: 0.5rem;
}
.mood-badge {
    display: inline-block;
    padding: 0.4rem 0.8rem;
    border-radius: 50rem;
    font-weight: 500;
    color: #fff;
    margin-right: 0.5rem;
    margin-bottom: 0.5rem;
}

/* Calendar Styles */
.calendar-container {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 5px;
}
.calendar-header {
    text-align: center;
    font-weight: 600;
    padding: 5px;
    background-color: #f8f9fa;
}
.calendar-day {
    aspect-ratio: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
    position: relative;
}
.calendar-day:hover {
    background-color: #f8f9fa;
}
.calendar-day.has-entries {
    background-color: var(--accent-color-light);
    color: #333;
}
.calendar-day.today {
    border: 2px solid var(--accent-color);
    font-weight: bold;
}
.day-number {
    font-size: 1rem;
    font-weight: 500;
}
.entry-indicator {
    font-size: 0.7rem;
    margin-top: 2px;
}

/* Quick Entry Styles */
.quick-entry-container {
    display: flex;
    flex-direction: column;
    align-items: center;
}
.emoji-selector {
    display: flex;
    justify-content: space-between;
    width: 100%;
    margin-bottom: 1rem;
}
.emoji-option {
    font-size: 2rem;
    cursor: pointer;
    transition: transform 0.2s ease;
    opacity: 0.5;
    text-align: center;
}
.emoji-option:hover {
    transform: scale(1.2);
    opacity: 1;
}
.emoji-option.selected {
    transform: scale(1.2);
    opacity: 1;
}

/* Tag Styles */
.tag-selector {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin: 1rem 0;
}
.tag-option {
    padding: 0.4rem 0.8rem;
    border-radius: 50rem;
    cursor: pointer;
    transition: all 0.2s ease;
    color: #fff;
    font-weight: 500;
    opacity: 0.7;
}
.tag-option:hover {
    opacity: 1;
    transform: translateY(-2px);
}
.tag-option.selected {
    opacity: 1;
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
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
    .mood-emoji {
        font-size: 1.8rem;
    }
    .emoji-option {
        font-size: 1.8rem;
    }
    .calendar-day {
        min-height: 40px;
    }
    .day-number {
        font-size: 0.9rem;
    }
    .entry-indicator {
        font-size: 0.6rem;
    }
    .dashboard-card {
        margin-bottom: 1rem;
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
                <i class="fas fa-smile me-2"></i>Mood Tracker
            </h1>
            <p class="text-muted">Track, analyze, and understand your moods</p>
        </div>
        <div class="col-md-4 text-md-end text-center mt-3 mt-md-0">
            <a href="entry.php" class="btn btn-accent me-2">
                <i class="fas fa-plus me-1"></i>New Entry
            </a>
            <a href="settings.php" class="btn btn-outline-accent">
                <i class="fas fa-cog me-1"></i>Settings
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Quick Entry -->
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="dashboard-card">
                <div class="card-body">
                    <h5 class="card-title mb-3" style="color: var(--accent-color);">
                        <i class="fas fa-bolt me-2"></i>Quick Mood Entry
                    </h5>
                    <div class="quick-entry-container">
                        <div class="emoji-selector">
                            <div class="emoji-option" data-value="1" onclick="selectMood(this, 1)">😢</div>
                            <div class="emoji-option" data-value="2" onclick="selectMood(this, 2)">😕</div>
                            <div class="emoji-option" data-value="3" onclick="selectMood(this, 3)">😐</div>
                            <div class="emoji-option" data-value="4" onclick="selectMood(this, 4)">🙂</div>
                            <div class="emoji-option" data-value="5" onclick="selectMood(this, 5)">😄</div>
                        </div>
                        
                        <div class="form-group mb-3 w-100">
                            <textarea class="form-control" id="quick_notes" rows="2" placeholder="How are you feeling? (optional)"></textarea>
                        </div>
                        
                        <div class="tag-selector w-100" id="quick_tags">
                            <?php foreach ($all_tags as $tag): ?>
                                <div class="tag-option" 
                                     style="background-color: <?php echo $tag['color']; ?>"
                                     data-id="<?php echo $tag['id']; ?>"
                                     onclick="toggleTag(this)">
                                    <?php echo htmlspecialchars($tag['name']); ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <button type="button" class="btn btn-accent w-100" id="save_quick_entry" onclick="saveQuickEntry()" disabled>
                            <i class="fas fa-save me-1"></i>Save Mood
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Monthly Calendar -->
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="dashboard-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title mb-0" style="color: var(--accent-color);">
                            <i class="fas fa-calendar-alt me-2"></i><?php echo date('F Y'); ?>
                        </h5>
                        <div>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="changeMonth(-1)">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="changeMonth(1)">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="calendar-container" id="mood_calendar">
                        <!-- Calendar headers (Sun-Sat) -->
                        <?php
                        $days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                        foreach ($days as $day) {
                            echo '<div class="calendar-header">' . $day . '</div>';
                        }
                        
                        // Get first day of month and total days
                        $first_day_of_month = date('w', strtotime($current_month . '-01'));
                        $total_days = date('t', strtotime($current_month . '-01'));
                        $today = date('j');
                        $current_month_year = date('Y-m');
                        
                        // Add empty cells for days before the 1st
                        for ($i = 0; $i < $first_day_of_month; $i++) {
                            echo '<div class="calendar-day empty"></div>';
                        }
                        
                        // Add days of the month
                        for ($day = 1; $day <= $total_days; $day++) {
                            $is_today = ($day == $today && $current_month_year == date('Y-m'));
                            $has_entries = isset($month_entries[$day]);
                            $avg_mood = $has_entries ? $month_entries[$day]['avg_mood'] : 0;
                            $entry_count = $has_entries ? $month_entries[$day]['entry_count'] : 0;
                            
                            $class = 'calendar-day';
                            if ($is_today) $class .= ' today';
                            if ($has_entries) $class .= ' has-entries';
                            
                            echo '<div class="' . $class . '" onclick="viewDayEntries(\'' . $current_month . '-' . sprintf('%02d', $day) . '\')">';
                            echo '<span class="day-number">' . $day . '</span>';
                            if ($has_entries) {
                                $emoji = '';
                                if ($avg_mood >= 4.5) $emoji = '😄';
                                else if ($avg_mood >= 3.5) $emoji = '🙂';
                                else if ($avg_mood >= 2.5) $emoji = '😐';
                                else if ($avg_mood >= 1.5) $emoji = '😕';
                                else $emoji = '😢';
                                
                                echo '<span class="entry-indicator">' . $emoji . '</span>';
                            }
                            echo '</div>';
                        }
                        
                        // Add empty cells for days after the last day
                        $remaining_cells = 7 - (($first_day_of_month + $total_days) % 7);
                        if ($remaining_cells < 7) {
                            for ($i = 0; $i < $remaining_cells; $i++) {
                                echo '<div class="calendar-day empty"></div>';
                            }
                        }
                        ?>
                    </div>
                    
                    <div class="text-center mt-3">
                        <a href="history.php" class="btn btn-sm btn-outline-accent">View Full History</a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Mood Stats -->
        <div class="col-lg-4 col-md-12 mb-4">
            <div class="dashboard-card">
                <div class="card-body">
                    <h5 class="card-title mb-3" style="color: var(--accent-color);">
                        <i class="fas fa-chart-line me-2"></i>Mood Insights
                    </h5>
                    
                    <?php if (isset($stats['total_entries']) && $stats['total_entries'] > 0): ?>
                        <div class="row text-center mb-3">
                            <div class="col-4">
                                <div class="mood-emoji">
                                    <?php
                                    $avg_mood = isset($stats['average_mood']) ? $stats['average_mood'] : 0;
                                    if ($avg_mood >= 4.5) echo '😄';
                                    else if ($avg_mood >= 3.5) echo '🙂';
                                    else if ($avg_mood >= 2.5) echo '😐';
                                    else if ($avg_mood >= 1.5) echo '😕';
                                    else echo '😢';
                                    ?>
                                </div>
                                <div class="small text-muted">Average Mood</div>
                                <div class="fw-bold"><?php echo number_format($avg_mood, 1); ?>/5</div>
                            </div>
                            <div class="col-4">
                                <div class="mood-emoji">📊</div>
                                <div class="small text-muted">Entries</div>
                                <div class="fw-bold"><?php echo $stats['total_entries']; ?></div>
                            </div>
                            <div class="col-4">
                                <div class="mood-emoji">
                                    <?php
                                    $most_common_mood = isset($stats['most_common_mood']) ? $stats['most_common_mood'] : 3;
                                    if ($most_common_mood == 5) echo '😄';
                                    else if ($most_common_mood == 4) echo '🙂';
                                    else if ($most_common_mood == 3) echo '😐';
                                    else if ($most_common_mood == 2) echo '😕';
                                    else echo '😢';
                                    ?>
                                </div>
                                <div class="small text-muted">Most Common</div>
                                <div class="fw-bold">Level <?php echo $most_common_mood; ?></div>
                            </div>
                        </div>
                        
                        <?php if (!empty($stats['top_tags'])): ?>
                            <h6 class="mt-4 mb-2">Top Tags</h6>
                            <div>
                                <?php foreach ($stats['top_tags'] as $tag): ?>
                                    <span class="mood-badge" style="background-color: <?php echo $tag['color']; ?>">
                                        <?php echo htmlspecialchars($tag['name']); ?> (<?php echo $tag['count']; ?>)
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="text-center mt-4">
                            <a href="analytics.php" class="btn btn-sm btn-outline-accent">View Detailed Analytics</a>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <div class="mb-3">
                                <i class="fas fa-chart-bar fa-3x text-muted"></i>
                            </div>
                            <p class="text-muted mb-3">No mood data available yet</p>
                            <p class="small text-muted">Start tracking your mood to see insights</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Entries -->
    <div class="row">
        <div class="col-12">
            <div class="dashboard-card">
                <div class="card-body">
                    <h5 class="card-title mb-3" style="color: var(--accent-color);">
                        <i class="fas fa-history me-2"></i>Recent Entries
                    </h5>
                    
                    <?php if (!empty($recent_entries)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Date & Time</th>
                                        <th>Mood</th>
                                        <th>Tags</th>
                                        <th>Notes</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_entries as $entry): ?>
                                        <tr>
                                            <td><?php echo date('M j, Y g:i A', strtotime($entry['date'])); ?></td>
                                            <td>
                                                <?php
                                                $mood = $entry['mood_level'];
                                                $emoji = '';
                                                if ($mood == 5) $emoji = '😄';
                                                else if ($mood == 4) $emoji = '🙂';
                                                else if ($mood == 3) $emoji = '😐';
                                                else if ($mood == 2) $emoji = '😕';
                                                else $emoji = '😢';
                                                echo $emoji . ' (' . $mood . '/5)';
                                                ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($entry['tags'])): ?>
                                                    <?php foreach ($entry['tags'] as $tag): ?>
                                                        <span class="mood-badge" style="background-color: <?php echo $tag['color']; ?>">
                                                            <?php echo htmlspecialchars($tag['name']); ?>
                                                        </span>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <span class="text-muted">No tags</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($entry['notes'])): ?>
                                                    <?php echo nl2br(htmlspecialchars(substr($entry['notes'], 0, 100))); ?>
                                                    <?php if (strlen($entry['notes']) > 100): ?>
                                                        <span class="text-muted">...</span>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-muted">No notes</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="entry.php?id=<?php echo $entry['id']; ?>" class="btn btn-sm btn-outline-accent me-1">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteEntry(<?php echo $entry['id']; ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="text-center mt-3">
                            <a href="history.php" class="btn btn-outline-accent">View All Entries</a>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <div class="mb-3">
                                <i class="fas fa-book fa-3x text-muted"></i>
                            </div>
                            <p class="text-muted mb-3">No mood entries yet</p>
                            <a href="entry.php" class="btn btn-accent">
                                <i class="fas fa-plus me-1"></i>Create Your First Entry
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
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

<!-- Day Entries Modal -->
<div class="modal fade" id="dayEntriesModal" tabindex="-1" aria-labelledby="dayEntriesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="dayEntriesModalLabel" style="color: var(--accent-color);">Mood Entries for <span id="day_date"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="day_entries_container">
                    <div class="text-center py-3">
                        <div class="spinner-border text-accent" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <a href="#" class="btn btn-accent" id="add_entry_for_day">
                    <i class="fas fa-plus me-1"></i>Add Entry
                </a>
            </div>
        </div>
    </div>
</div>

<script>
// Variables to store selected mood and tags
let selectedMood = null;
let selectedTags = [];

// Function to select mood
function selectMood(element, value) {
    // Remove selected class from all options
    document.querySelectorAll('.emoji-option').forEach(option => {
        option.classList.remove('selected');
    });
    
    // Add selected class to clicked option
    element.classList.add('selected');
    
    // Store selected mood
    selectedMood = value;
    
    // Enable save button if mood is selected
    document.getElementById('save_quick_entry').disabled = !selectedMood;
}

// Function to toggle tag selection
function toggleTag(element) {
    const tagId = parseInt(element.dataset.id);
    
    if (element.classList.contains('selected')) {
        // Remove tag from selection
        element.classList.remove('selected');
        selectedTags = selectedTags.filter(id => id !== tagId);
    } else {
        // Add tag to selection
        element.classList.add('selected');
        selectedTags.push(tagId);
    }
}

// Function to save quick entry
function saveQuickEntry() {
    if (!selectedMood) {
        alert('Please select a mood level');
        return;
    }
    
    const notes = document.getElementById('quick_notes').value;
    const tags = selectedTags.join(',');
    
    // Disable save button and show loading state
    const saveButton = document.getElementById('save_quick_entry');
    const originalText = saveButton.innerHTML;
    saveButton.disabled = true;
    saveButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...';
    
    // Send AJAX request to save mood
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'ajax/save_mood.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function() {
        if (xhr.status === 200) {
            try {
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                    // Reset form
                    document.querySelectorAll('.emoji-option').forEach(option => {
                        option.classList.remove('selected');
                    });
                    document.querySelectorAll('.tag-option').forEach(option => {
                        option.classList.remove('selected');
                    });
                    document.getElementById('quick_notes').value = '';
                    selectedMood = null;
                    selectedTags = [];
                    
                    // Show success message
                    alert('Mood entry saved successfully!');
                    
                    // Reload page to show updated data
                    window.location.reload();
                } else {
                    alert('Error: ' + (response.message || 'Failed to save mood entry'));
                }
            } catch (e) {
                console.error('Error parsing response:', e);
                console.error('Response text:', xhr.responseText);
                alert('Error: Invalid response from server');
            }
        } else {
            console.error('Server returned status:', xhr.status);
            alert('Error: Server returned status ' + xhr.status);
        }
        
        // Reset button state
        saveButton.disabled = false;
        saveButton.innerHTML = originalText;
    };
    xhr.onerror = function() {
        console.error('Network error occurred');
        alert('Error: Network error occurred. Please check your connection.');
        saveButton.disabled = false;
        saveButton.innerHTML = originalText;
    };
    xhr.send('mood_level=' + selectedMood + '&notes=' + encodeURIComponent(notes) + '&tags=' + tags);
}

// Function to change month
function changeMonth(direction) {
    // Get current month from calendar title
    const currentTitle = document.querySelector('.card-title').textContent;
    const currentDate = new Date(currentTitle);
    
    // Calculate new month
    currentDate.setMonth(currentDate.getMonth() + direction);
    const newMonth = currentDate.toISOString().slice(0, 7); // Format: YYYY-MM
    
    // Update calendar title
    document.querySelector('.card-title').innerHTML = 
        `<i class="fas fa-calendar-alt me-2"></i>${currentDate.toLocaleString('en-US', { month: 'long', year: 'numeric' })}`;
    
    // Show loading state
    document.getElementById('mood_calendar').innerHTML = `
        <div class="text-center py-5" style="grid-column: span 7;">
            <div class="spinner-border text-accent" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    `;
    
    // Load new month data via AJAX
    const xhr = new XMLHttpRequest();
    xhr.open('GET', 'ajax/get_month_entries.php?month=' + newMonth, true);
    xhr.onload = function() {
        if (xhr.status === 200) {
            document.getElementById('mood_calendar').innerHTML = xhr.responseText;
        } else {
            document.getElementById('mood_calendar').innerHTML = `
                <div class="alert alert-danger" style="grid-column: span 7;">
                    Error loading calendar. Please try again.
                </div>
            `;
        }
    };
    xhr.onerror = function() {
        document.getElementById('mood_calendar').innerHTML = `
            <div class="alert alert-danger" style="grid-column: span 7;">
                Network error occurred. Please check your connection.
            </div>
        `;
    };
    xhr.send();
}

// Function to view day entries
function viewDayEntries(date) {
    // Set date in modal
    document.getElementById('day_date').textContent = new Date(date).toLocaleDateString('en-US', { 
        weekday: 'long', 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    });
    
    // Set link for adding entry on this date
    document.getElementById('add_entry_for_day').href = 'entry.php?date=' + date;
    
    // Show loading state
    document.getElementById('day_entries_container').innerHTML = `
        <div class="text-center py-3">
            <div class="spinner-border text-accent" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    `;
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('dayEntriesModal'));
    modal.show();
    
    // Load entries for this day via AJAX
    const xhr = new XMLHttpRequest();
    xhr.open('GET', 'ajax/get_day_entries.php?date=' + date, true);
    xhr.onload = function() {
        if (xhr.status === 200) {
            document.getElementById('day_entries_container').innerHTML = xhr.responseText;
        } else {
            document.getElementById('day_entries_container').innerHTML = `
                <div class="alert alert-danger">
                    Error loading entries. Please try again.
                </div>
            `;
        }
    };
    xhr.onerror = function() {
        document.getElementById('day_entries_container').innerHTML = `
            <div class="alert alert-danger">
                Network error occurred. Please check your connection.
            </div>
        `;
    };
    xhr.send();
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
                        
                        // Reload page to show updated data
                        window.location.reload();
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

// Improve mobile experience
document.addEventListener('DOMContentLoaded', function() {
    // Fix iOS zoom on input focus
    document.querySelectorAll('input[type="text"], input[type="email"], input[type="tel"], textarea').forEach(input => {
        input.style.fontSize = '16px';
    });
    
    // Increase touch target sizes on mobile
    if (window.innerWidth < 768) {
        document.querySelectorAll('.btn-sm').forEach(btn => {
            btn.classList.remove('btn-sm');
        });
    }
});
</script>

<?php
// Include footer
require_once __DIR__ . '/../../includes/footer.php';
?>
