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
    margin-bottom: 1rem;
    overflow-x: auto;
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 2px;
    background-color: #dee2e6;
    padding: 2px;
    border-radius: 8px;
}

.calendar-header {
    text-align: center;
    font-weight: 600;
    padding: 10px 5px;
    background-color: #f8f9fa;
    font-size: 0.85rem;
    text-transform: uppercase;
    color: #495057;
}

.calendar-day {
    aspect-ratio: 1;
    background-color: #fff;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 5px;
    cursor: pointer;
    transition: all 0.2s ease;
    position: relative;
    min-height: 60px;
}

.calendar-day:hover {
    background-color: #f8f9fa;
    transform: translateY(-2px);
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.calendar-day.empty {
    background-color: #f8f9fa;
    cursor: default;
    color: #adb5bd;
}

.calendar-day.empty:hover {
    transform: none;
    box-shadow: none;
}

.calendar-day.has-entries {
    background-color: var(--accent-color-light);
}

.calendar-day.today {
    border: 2px solid var(--accent-color);
    font-weight: bold;
}

.calendar-day.other-month {
    opacity: 0.5;
}

.day-number {
    position: absolute;
    top: 5px;
    right: 5px;
    font-size: 0.9rem;
    font-weight: 500;
    color: #495057;
}

.entry-indicator {
    font-size: 1.4rem;
    margin-top: 5px;
}

.entry-count {
    position: absolute;
    bottom: 3px;
    right: 5px;
    font-size: 0.7rem;
    color: #495057;
    background-color: rgba(255, 255, 255, 0.8);
    padding: 1px 4px;
    border-radius: 10px;
}

/* Month Navigation */
.month-navigation {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1rem;
}

.month-title {
    font-size: 1.2rem;
    font-weight: 600;
    margin: 0;
    color: var(--accent-color);
}

.month-nav-buttons {
    display: flex;
    gap: 0.5rem;
}

.month-nav-btn {
    padding: 0.4rem 0.8rem;
    border-radius: 4px;
    background-color: transparent;
    border: 1px solid #dee2e6;
    color: #495057;
    transition: all 0.2s ease;
}

.month-nav-btn:hover {
    background-color: var(--accent-color-light);
    border-color: var(--accent-color);
    color: #495057;
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
    .calendar-container {
        gap: 1px;
        padding: 1px;
    }
    .calendar-header {
        padding: 8px 2px;
        font-size: 0.75rem;
    }
    .calendar-day {
        min-height: 50px;
        padding: 3px;
    }
    .day-number {
        font-size: 0.8rem;
        top: 3px;
        right: 3px;
    }
    .entry-indicator {
        font-size: 1.2rem;
        margin-top: 8px;
    }
    .entry-count {
        font-size: 0.65rem;
        padding: 0 3px;
    }
    .month-title {
        font-size: 1.1rem;
    }
    .month-nav-btn {
        padding: 0.3rem 0.6rem;
        font-size: 0.9rem;
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

/* Small Mobile Screens */
@media (max-width: 375px) {
    .calendar-day {
        min-height: 45px;
    }
    .entry-indicator {
        font-size: 1rem;
        margin-top: 10px;
    }
    .day-number {
        font-size: 0.75rem;
    }
}

/* Calendar Card */
.calendar-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    overflow: hidden;
}

.calendar-card-header {
    padding: 1rem;
    border-bottom: 1px solid #dee2e6;
}

.calendar-card-body {
    padding: 1rem;
}

/* Accordion Styles */
.accordion-item {
    background-color: transparent;
    border-radius: 8px;
    overflow: hidden;
}

.accordion-button {
    background-color: #fff;
    border: 1px solid #dee2e6;
    border-radius: 8px !important;
    padding: 1rem;
    box-shadow: none;
    transition: all 0.2s ease;
}

.accordion-button:not(.collapsed) {
    background-color: var(--accent-color-light);
    border-color: var(--accent-color);
    color: inherit;
    box-shadow: none;
}

.accordion-button::after {
    background-size: 1rem;
    width: 1rem;
    height: 1rem;
    margin-left: 1rem;
}

.accordion-button:focus {
    box-shadow: none;
    border-color: var(--accent-color);
}

.accordion-body {
    background-color: #fff;
    border: 1px solid #dee2e6;
    border-top: none;
    border-bottom-left-radius: 8px;
    border-bottom-right-radius: 8px;
    padding: 1rem;
}

.entry-date {
    color: #495057;
    font-weight: 500;
}

.entry-notes {
    color: #495057;
    white-space: pre-line;
}

.entry-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 0.3rem;
}

.mood-badge {
    font-size: 0.75rem;
    padding: 0.2rem 0.5rem;
}

@media (max-width: 767.98px) {
    .accordion-button {
        padding: 0.75rem;
    }
    
    .entry-date {
        font-size: 0.9rem;
    }
    
    .mood-badge {
        font-size: 0.7rem;
        padding: 0.15rem 0.4rem;
    }
    
    .entry-emoji {
        font-size: 1.2rem !important;
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
            <div class="calendar-card">
                <div class="calendar-card-header">
                    <div class="month-navigation">
                        <h5 class="month-title">
                            <i class="fas fa-calendar-alt me-2"></i>
                        <?php
                            // Get selected month and year from URL parameters or use current date
                            $selected_month = isset($_GET['month']) ? $_GET['month'] : date('m');
                            $selected_year = isset($_GET['year']) ? $_GET['year'] : date('Y');
                            $current_date = "$selected_year-$selected_month-01";
                            ?>
                            <?php echo date('F Y', strtotime($current_date)); ?>
                        </h5>
                        <div class="month-nav-buttons">
                            <?php
                            // Calculate previous and next month/year
                            $prev_date = date('Y-m', strtotime('-1 month', strtotime($current_date)));
                            $next_date = date('Y-m', strtotime('+1 month', strtotime($current_date)));
                            list($prev_year, $prev_month) = explode('-', $prev_date);
                            list($next_year, $next_month) = explode('-', $next_date);
                            ?>
                            <a href="?month=<?php echo $prev_month; ?>&year=<?php echo $prev_year; ?>" 
                               class="month-nav-btn">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                            <a href="?month=<?php echo $next_month; ?>&year=<?php echo $next_year; ?>" 
                               class="month-nav-btn">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="calendar-card-body">
                    <div class="calendar-container">
                        <?php
                        // Calendar headers (Sun-Sat)
                        $days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                        foreach ($days as $day) {
                            echo '<div class="calendar-header">' . $day . '</div>';
                        }
                        
                        // Get first day of month and total days
                        $first_day_of_month = date('w', strtotime($current_date));
                        $total_days = date('t', strtotime($current_date));
                        $today = date('j');
                        $current_month_year = date('Y-m');
                        
                        // Get entries for the selected month
                        $month_entries = getMoodEntriesByDay("$selected_year-$selected_month");
                        
                        // Add empty cells for days before the 1st
                        for ($i = 0; $i < $first_day_of_month; $i++) {
                            echo '<div class="calendar-day empty"></div>';
                        }
                        
                        // Add days of the month
                        for ($day = 1; $day <= $total_days; $day++) {
                            $date = sprintf('%s-%02d', "$selected_year-$selected_month", $day);
                            $is_today = ($day == date('j') && "$selected_year-$selected_month" == date('Y-m'));
                            $has_entries = isset($month_entries[$day]);
                            $avg_mood = $has_entries ? $month_entries[$day]['avg_mood'] : 0;
                            $entry_count = $has_entries ? $month_entries[$day]['entry_count'] : 0;
                            
                            $class = 'calendar-day';
                            if ($is_today) $class .= ' today';
                            if ($has_entries) $class .= ' has-entries';
                            
                            echo '<div class="' . $class . '" onclick="viewDayEntries(\'' . $date . '\')">';
                            echo '<span class="day-number">' . $day . '</span>';
                            if ($has_entries) {
                                $emoji = '';
                                if ($avg_mood >= 4.5) $emoji = '😄';
                                else if ($avg_mood >= 3.5) $emoji = '🙂';
                                else if ($avg_mood >= 2.5) $emoji = '😐';
                                else if ($avg_mood >= 1.5) $emoji = '😕';
                                else $emoji = '😢';
                                
                                echo '<span class="entry-indicator">' . $emoji . '</span>';
                                if ($entry_count > 1) {
                                    echo '<span class="entry-count">(' . $entry_count . ')</span>';
                                }
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
                        <div class="accordion" id="recentEntriesAccordion">
                            <?php foreach ($recent_entries as $index => $entry): ?>
                                <div class="accordion-item border-0 mb-2">
                                    <h2 class="accordion-header" id="entry-heading-<?php echo $entry['id']; ?>">
                                        <button class="accordion-button collapsed" type="button" 
                                                data-bs-toggle="collapse" 
                                                data-bs-target="#entry-collapse-<?php echo $entry['id']; ?>" 
                                                aria-expanded="false" 
                                                aria-controls="entry-collapse-<?php echo $entry['id']; ?>">
                                            <div class="d-flex align-items-center w-100">
                                                <div class="entry-emoji me-3 fs-4">
                                                    <?php
                                                    $mood = $entry['mood_level'];
                                                    if ($mood == 5) echo '😄';
                                                    else if ($mood == 4) echo '🙂';
                                                    else if ($mood == 3) echo '😐';
                                                    else if ($mood == 2) echo '😕';
                                                    else echo '😢';
                                                    ?>
                                                </div>
                                                <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between w-100">
                                                    <div class="entry-date">
                                                        <?php echo date('M j, Y g:i A', strtotime($entry['date'])); ?>
                                                    </div>
                                                    <div class="entry-tags mt-1 mt-sm-0">
                                                        <?php if (!empty($entry['tags'])): ?>
                                                            <?php foreach ($entry['tags'] as $tag): ?>
                                                                <span class="mood-badge" style="background-color: <?php echo $tag['color']; ?>">
                                                                    <?php echo htmlspecialchars($tag['name']); ?>
                                                                </span>
                                                            <?php endforeach; ?>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </button>
                                    </h2>
                                    <div id="entry-collapse-<?php echo $entry['id']; ?>" 
                                         class="accordion-collapse collapse" 
                                         aria-labelledby="entry-heading-<?php echo $entry['id']; ?>" 
                                         data-bs-parent="#recentEntriesAccordion">
                                        <div class="accordion-body">
                                            <?php if (!empty($entry['notes'])): ?>
                                                <div class="entry-notes mb-3">
                                                    <?php echo nl2br(htmlspecialchars($entry['notes'])); ?>
                                                </div>
                                            <?php else: ?>
                                                <div class="text-muted mb-3">No notes added</div>
                                            <?php endif; ?>
                                            <div class="entry-actions">
                                                <a href="entry.php?id=<?php echo $entry['id']; ?>" class="btn btn-sm btn-outline-accent me-2">
                                                    <i class="fas fa-edit me-1"></i>Edit Entry
                                                </a>
                                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteEntry(<?php echo $entry['id']; ?>)">
                                                    <i class="fas fa-trash me-1"></i>Delete Entry
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="text-center mt-4">
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
