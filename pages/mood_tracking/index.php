<?php
// Include required files
require_once __DIR__ . '/includes/functions.php';

// Set page title
$page_title = "Mood Tracker";

// Include header
include '../../includes/header.php';

// Get current date and time
$current_date = date('Y-m-d');
$current_month = date('Y-m');

// Get mood entries for the current month
$month_entries = getMoodEntriesByDay($current_month);

// Get mood tags
$mood_tags = getMoodTags();
?>

<style>
:root {
    --accent-color: #cdaf56;
    --accent-color-light: #dbc77a;
    --accent-color-dark: #b99b3e;
}

.mood-calendar {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 10px;
    margin-bottom: 20px;
}

.calendar-day {
    aspect-ratio: 1;
    border-radius: 10px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background-color: #f8f9fa;
    position: relative;
    cursor: pointer;
    transition: all 0.2s ease;
}

.calendar-day:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.calendar-day.today {
    border: 2px solid var(--accent-color);
}

.calendar-day .day-number {
    font-weight: 600;
    font-size: 1.1rem;
}

.calendar-day .mood-indicator {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    margin-top: 5px;
}

.calendar-day .entry-count {
    position: absolute;
    top: 5px;
    right: 5px;
    background-color: var(--accent-color);
    color: white;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    font-size: 0.7rem;
    display: flex;
    align-items: center;
    justify-content: center;
}

.mood-level-1 { background-color: #dc3545; }
.mood-level-2 { background-color: #fd7e14; }
.mood-level-3 { background-color: #ffc107; }
.mood-level-4 { background-color: #20c997; }
.mood-level-5 { background-color: #28a745; }

.mood-emoji {
    font-size: 2.5rem;
    cursor: pointer;
    transition: transform 0.2s;
    opacity: 0.5;
    margin: 0 5px;
}

.mood-emoji:hover, .mood-emoji.selected {
    transform: scale(1.2);
    opacity: 1;
}

.tag-badge {
    margin-right: 5px;
    margin-bottom: 5px;
    cursor: pointer;
    transition: all 0.2s;
    font-size: 1rem;
    padding: 8px 12px;
}

.tag-badge:hover, .tag-badge.selected {
    transform: translateY(-2px);
}

.recent-entry {
    border-left: 4px solid var(--accent-color);
    padding-left: 15px;
    margin-bottom: 15px;
    transition: all 0.2s;
}

.recent-entry:hover {
    transform: translateX(5px);
}

.mood-stats-card {
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    transition: all 0.3s;
    height: 100%;
}

.mood-stats-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 15px rgba(0,0,0,0.1);
}

.mood-stats-card .card-header {
    font-weight: 600;
    background-color: var(--accent-color);
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

/* Mobile Responsiveness Improvements */
@media (max-width: 767.98px) {
    .mood-calendar {
        gap: 5px;
    }
    
    .calendar-day .day-number {
        font-size: 0.9rem;
    }
    
    .calendar-day .mood-indicator {
        width: 18px;
        height: 18px;
    }
    
    .calendar-day .entry-count {
        width: 16px;
        height: 16px;
        font-size: 0.6rem;
    }
    
    .mood-emoji {
        font-size: 2rem;
        margin: 0 2px;
    }
    
    .tag-badge {
        font-size: 0.9rem;
        padding: 6px 10px;
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
}

/* Touch-friendly improvements */
@media (max-width: 576px) {
    .mood-emoji {
        font-size: 2.2rem;
        padding: 10px;
        margin: 0;
    }
    
    .mood-emoji-container {
        justify-content: space-between;
        width: 100%;
    }
    
    .tag-badge {
        padding: 8px 12px;
        margin-bottom: 10px;
    }
    
    .form-control, .form-select {
        font-size: 16px; /* Prevents iOS zoom on focus */
        padding: 12px;
        height: auto;
    }
    
    .btn {
        padding: 12px 16px;
    }
}
</style>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h3 mb-3">Mood Tracker</h1>
        </div>
        <div class="col-md-4 text-md-end">
            <a href="entry.php" class="btn btn-accent">
                <i class="fas fa-plus me-2"></i>New Mood Entry
            </a>
            <a href="history.php" class="btn btn-outline-accent ms-2">
                <i class="fas fa-history me-2"></i>History
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Left Column: Calendar and Quick Entry -->
        <div class="col-lg-8">
            <!-- Month Calendar -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-calendar-alt me-2" style="color: var(--accent-color);"></i>
                            <?php echo date('F Y'); ?>
                        </h5>
                        <div>
                            <button class="btn btn-sm btn-outline-accent me-2" id="prev-month">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-accent" id="next-month">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between mb-2">
                        <div class="text-center" style="width: 14.28%">Sun</div>
                        <div class="text-center" style="width: 14.28%">Mon</div>
                        <div class="text-center" style="width: 14.28%">Tue</div>
                        <div class="text-center" style="width: 14.28%">Wed</div>
                        <div class="text-center" style="width: 14.28%">Thu</div>
                        <div class="text-center" style="width: 14.28%">Fri</div>
                        <div class="text-center" style="width: 14.28%">Sat</div>
                    </div>
                    
                    <div class="mood-calendar" id="mood-calendar">
                        <?php
                        // Get first day of month
                        $first_day = date('N', strtotime($current_month . '-01'));
                        $first_day = $first_day % 7; // Convert to 0-6 (Sun-Sat)
                        
                        // Get number of days in month
                        $days_in_month = date('t', strtotime($current_month . '-01'));
                        
                        // Add empty cells for days before first day of month
                        for ($i = 0; $i < $first_day; $i++) {
                            echo '<div class="calendar-day" style="visibility: hidden;"></div>';
                        }
                        
                        // Add cells for each day of the month
                        for ($day = 1; $day <= $days_in_month; $day++) {
                            $day_date = $current_month . '-' . str_pad($day, 2, '0', STR_PAD_LEFT);
                            $is_today = $day_date === $current_date;
                            $has_entries = isset($month_entries[$day]);
                            
                            $class = $is_today ? 'calendar-day today' : 'calendar-day';
                            $mood_level = $has_entries ? round($month_entries[$day]['avg_mood']) : 0;
                            $entry_count = $has_entries ? $month_entries[$day]['entry_count'] : 0;
                            
                            echo '<div class="' . $class . '" data-date="' . $day_date . '">';
                            echo '<span class="day-number">' . $day . '</span>';
                            
                            if ($has_entries) {
                                echo '<div class="mood-indicator mood-level-' . $mood_level . '"></div>';
                                echo '<span class="entry-count">' . $entry_count . '</span>';
                            }
                            
                            echo '</div>';
                        }
                        ?>
                    </div>
                    
                    <div class="d-flex justify-content-center flex-wrap">
                        <div class="d-flex align-items-center me-3 mb-2">
                            <div class="mood-indicator mood-level-1 me-2"></div>
                            <span>Very Low</span>
                        </div>
                        <div class="d-flex align-items-center me-3 mb-2">
                            <div class="mood-indicator mood-level-2 me-2"></div>
                            <span>Low</span>
                        </div>
                        <div class="d-flex align-items-center me-3 mb-2">
                            <div class="mood-indicator mood-level-3 me-2"></div>
                            <span>Neutral</span>
                        </div>
                        <div class="d-flex align-items-center me-3 mb-2">
                            <div class="mood-indicator mood-level-4 me-2"></div>
                            <span>Good</span>
                        </div>
                        <div class="d-flex align-items-center mb-2">
                            <div class="mood-indicator mood-level-5 me-2"></div>
                            <span>Excellent</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Quick Mood Entry -->
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title mb-3">
                        <i class="fas fa-bolt me-2" style="color: var(--accent-color);"></i>
                        Quick Mood Entry
                    </h5>
                    
                    <form id="quick-mood-form">
                        <div class="mb-4">
                            <label class="form-label">How are you feeling right now?</label>
                            <div class="d-flex justify-content-between mood-emoji-container">
                                <div class="text-center">
                                    <div class="mood-emoji" data-level="1">😢</div>
                                    <div>Very Low</div>
                                </div>
                                <div class="text-center">
                                    <div class="mood-emoji" data-level="2">😕</div>
                                    <div>Low</div>
                                </div>
                                <div class="text-center">
                                    <div class="mood-emoji" data-level="3">😐</div>
                                    <div>Neutral</div>
                                </div>
                                <div class="text-center">
                                    <div class="mood-emoji" data-level="4">🙂</div>
                                    <div>Good</div>
                                </div>
                                <div class="text-center">
                                    <div class="mood-emoji" data-level="5">😄</div>
                                    <div>Excellent</div>
                                </div>
                            </div>
                            <input type="hidden" id="mood-level" name="mood_level" required>
                        </div>
                        
                        <div class="mb-4">
                            <label for="notes" class="form-label">Notes (optional)</label>
                            <textarea class="form-control" id="notes" name="notes" rows="2" placeholder="What's on your mind?"></textarea>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label">Tags</label>
                            <div class="tag-container d-flex flex-wrap">
                                <?php foreach ($mood_tags as $tag): ?>
                                <span class="badge tag-badge" 
                                      style="background-color: <?php echo htmlspecialchars($tag['color']); ?>"
                                      data-tag-id="<?php echo $tag['id']; ?>">
                                    <?php echo htmlspecialchars($tag['name']); ?>
                                </span>
                                <?php endforeach; ?>
                            </div>
                            <input type="hidden" id="selected-tags" name="tags">
                        </div>
                        
                        <div class="text-end">
                            <button type="submit" class="btn btn-accent">
                                <i class="fas fa-save me-2"></i>Save Mood
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Right Column: Stats and Recent Entries -->
        <div class="col-lg-4">
            <!-- Mood Stats -->
            <div class="card mood-stats-card mb-4">
                <div class="card-header text-white">
                    <i class="fas fa-chart-line me-2"></i>
                    Mood Statistics
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6 class="text-muted mb-2">Today's Average Mood</h6>
                        <div class="d-flex align-items-center">
                            <div class="display-4 me-3" id="today-mood-emoji">😐</div>
                            <div>
                                <div class="progress" style="height: 8px; width: 150px;">
                                    <div class="progress-bar" id="today-mood-bar" role="progressbar" style="width: 60%; background-color: var(--accent-color);"></div>
                                </div>
                                <div class="small text-muted mt-1" id="today-mood-text">Neutral (3.0)</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <h6 class="text-muted mb-2">This Week</h6>
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="small text-muted">Average Mood:</div>
                            <div class="fw-bold" id="week-avg-mood">3.2</div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="small text-muted">Entries:</div>
                            <div class="fw-bold" id="week-entry-count">12</div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <h6 class="text-muted mb-2">Most Used Tags</h6>
                        <div id="top-tags">
                            <div class="placeholder-glow">
                                <span class="placeholder col-4"></span>
                                <span class="placeholder col-3"></span>
                                <span class="placeholder col-5"></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-center">
                        <a href="analytics.php" class="btn btn-sm btn-outline-accent">
                            <i class="fas fa-chart-bar me-2"></i>View Detailed Analytics
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Recent Entries -->
            <div class="card mood-stats-card">
                <div class="card-header text-white">
                    <i class="fas fa-history me-2"></i>
                    Recent Entries
                </div>
                <div class="card-body">
                    <div id="recent-entries">
                        <div class="placeholder-glow">
                            <span class="placeholder col-12 mb-2"></span>
                            <span class="placeholder col-10 mb-2"></span>
                            <span class="placeholder col-8 mb-3"></span>
                            
                            <span class="placeholder col-12 mb-2"></span>
                            <span class="placeholder col-10 mb-2"></span>
                            <span class="placeholder col-8 mb-3"></span>
                        </div>
                    </div>
                    
                    <div class="text-center">
                        <a href="history.php" class="btn btn-sm btn-outline-accent">
                            <i class="fas fa-list me-2"></i>View All Entries
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mood emoji selection
    const moodEmojis = document.querySelectorAll('.mood-emoji');
    const moodLevelInput = document.getElementById('mood-level');
    
    moodEmojis.forEach(emoji => {
        emoji.addEventListener('click', function() {
            // Remove selected class from all emojis
            moodEmojis.forEach(e => e.classList.remove('selected'));
            
            // Add selected class to clicked emoji
            this.classList.add('selected');
            
            // Set mood level value
            moodLevelInput.value = this.dataset.level;
        });
    });
    
    // Tag selection
    const tagBadges = document.querySelectorAll('.tag-badge');
    const selectedTagsInput = document.getElementById('selected-tags');
    const selectedTags = [];
    
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
    
    // Calendar day click
    const calendarDays = document.querySelectorAll('.calendar-day');
    
    calendarDays.forEach(day => {
        if (day.dataset.date) {
            day.addEventListener('click', function() {
                window.location.href = 'history.php?date=' + this.dataset.date;
            });
        }
    });
    
    // Form submission
    const quickMoodForm = document.getElementById('quick-mood-form');
    
    quickMoodForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Validate form
        if (!moodLevelInput.value) {
            alert('Please select your mood level');
            return;
        }
        
        // Collect form data
        const formData = new FormData(this);
        
        // Send AJAX request
        fetch('ajax/save_mood.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message
                alert('Mood entry saved successfully!');
                
                // Reset form
                quickMoodForm.reset();
                moodEmojis.forEach(e => e.classList.remove('selected'));
                tagBadges.forEach(b => b.classList.remove('selected'));
                selectedTags.length = 0;
                selectedTagsInput.value = '';
                
                // Reload page to update calendar and stats
                window.location.reload();
            } else {
                // Show error message
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while saving your mood entry');
        });
    });
    
    // Load recent entries
    fetch('ajax/get_recent_entries.php')
    .then(response => response.json())
    .then(data => {
        const recentEntriesContainer = document.getElementById('recent-entries');
        
        if (data.length > 0) {
            let html = '';
            
            data.forEach(entry => {
                const date = new Date(entry.date);
                const formattedDate = date.toLocaleDateString('en-US', { 
                    weekday: 'short', 
                    month: 'short', 
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
                
                let moodEmoji = '😐';
                switch (parseInt(entry.mood_level)) {
                    case 1: moodEmoji = '😢'; break;
                    case 2: moodEmoji = '😕'; break;
                    case 3: moodEmoji = '😐'; break;
                    case 4: moodEmoji = '🙂'; break;
                    case 5: moodEmoji = '😄'; break;
                }
                
                html += `
                <div class="recent-entry">
                    <div class="d-flex align-items-center mb-1">
                        <div class="me-2 fs-4">${moodEmoji}</div>
                        <div>
                            <div class="small text-muted">${formattedDate}</div>
                        </div>
                    </div>
                `;
                
                if (entry.notes) {
                    html += `<div class="small mb-1">${entry.notes}</div>`;
                }
                
                if (entry.tags && entry.tags.length > 0) {
                    html += '<div class="mb-2">';
                    entry.tags.forEach(tag => {
                        html += `
                        <span class="badge" style="background-color: ${tag.color}; font-size: 0.7rem;">
                            ${tag.name}
                        </span> `;
                    });
                    html += '</div>';
                }
                
                html += `
                    <div class="text-end">
                        <a href="entry.php?id=${entry.id}" class="btn btn-sm btn-outline-accent">
                            <i class="fas fa-edit"></i>
                        </a>
                    </div>
                </div>`;
            });
            
            recentEntriesContainer.innerHTML = html;
        } else {
            recentEntriesContainer.innerHTML = '<p class="text-muted">No recent entries found.</p>';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('recent-entries').innerHTML = '<p class="text-danger">Error loading recent entries</p>';
    });
    
    // Load mood statistics
    fetch('ajax/get_mood_stats.php')
    .then(response => response.json())
    .then(data => {
        // Update today's mood
        if (data.today_avg_mood) {
            const todayMood = parseFloat(data.today_avg_mood);
            const todayMoodBar = document.getElementById('today-mood-bar');
            const todayMoodEmoji = document.getElementById('today-mood-emoji');
            const todayMoodText = document.getElementById('today-mood-text');
            
            // Update progress bar
            todayMoodBar.style.width = (todayMood / 5 * 100) + '%';
            
            // Update emoji
            let moodEmoji = '😐';
            let moodText = 'Neutral';
            let barColor = 'var(--accent-color)';
            
            switch (Math.round(todayMood)) {
                case 1: 
                    moodEmoji = '😢'; 
                    moodText = 'Very Low';
                    barColor = '#dc3545';
                    break;
                case 2: 
                    moodEmoji = '😕'; 
                    moodText = 'Low';
                    barColor = '#fd7e14';
                    break;
                case 3: 
                    moodEmoji = '😐'; 
                    moodText = 'Neutral';
                    barColor = '#ffc107';
                    break;
                case 4: 
                    moodEmoji = '🙂'; 
                    moodText = 'Good';
                    barColor = '#20c997';
                    break;
                case 5: 
                    moodEmoji = '😄'; 
                    moodText = 'Excellent';
                    barColor = '#28a745';
                    break;
            }
            
            todayMoodEmoji.textContent = moodEmoji;
            todayMoodText.textContent = `${moodText} (${todayMood.toFixed(1)})`;
            todayMoodBar.style.backgroundColor = barColor;
        }
        
        // Update weekly stats
        if (data.week_avg_mood) {
            document.getElementById('week-avg-mood').textContent = parseFloat(data.week_avg_mood).toFixed(1);
        }
        
        if (data.week_entry_count) {
            document.getElementById('week-entry-count').textContent = data.week_entry_count;
        }
        
        // Update top tags
        if (data.top_tags && data.top_tags.length > 0) {
            const topTagsContainer = document.getElementById('top-tags');
            let html = '';
            
            data.top_tags.forEach(tag => {
                html += `
                <span class="badge mb-1 me-1" style="background-color: ${tag.color};">
                    ${tag.name} (${tag.count})
                </span>`;
            });
            
            topTagsContainer.innerHTML = html;
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
});
</script>

<?php
include '../../includes/footer.php';
?>
