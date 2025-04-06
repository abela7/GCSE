<?php
session_start();
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../functions/stats_functions.php';

// Initialize variables with default values
$stats = [
    'total_items' => 0,
    'favorite_items' => 0,
    'items_added_today' => 0,
    'categories_practiced' => 0
];
$assignments = [];
$recent_items = [];

try {
    // Get stats using the function
    $stats = get_daily_stats($conn);
    
    // Get assignments using the function
    $assignments = get_upcoming_assignments($conn);
    
    // Get recent items using the function
    $recent_items = get_recent_practice_items($conn);
    
} catch (Exception $e) {
    error_log("Error in Today.php: " . $e->getMessage());
    // We'll continue with default values if there's an error
}

$page_title = "Today's Overview";
require_once __DIR__ . '/../includes/header.php';
?>

<style>
:root {
    --primary-gradient: linear-gradient(135deg, #cdaf56 0%, #e6ce89 100%);
    --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    --transition-speed: 0.3s;
}

.hero-section {
    background: var(--primary-gradient);
    padding: 3rem 0;
    margin-bottom: 2rem;
    color: white;
    position: relative;
    overflow: hidden;
}

.hero-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('/assets/images/pattern.png');
    opacity: 0.1;
}

.stat-card {
    background: white;
    border-radius: 1rem;
    padding: 1.5rem;
    box-shadow: var(--card-shadow);
    transition: transform var(--transition-speed);
    height: 100%;
}

.stat-card:hover {
    transform: translateY(-5px);
}

.stat-number {
    font-size: 2.5rem;
    font-weight: bold;
    color: #cdaf56;
    margin-bottom: 0.5rem;
}

.quick-action {
    padding: 1rem;
    border-radius: 0.75rem;
    background: white;
    box-shadow: var(--card-shadow);
    transition: all var(--transition-speed);
    text-decoration: none;
    color: inherit;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.quick-action:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 8px -1px rgba(0, 0, 0, 0.1);
    color: #cdaf56;
}

.quick-action i {
    font-size: 1.5rem;
    color: #cdaf56;
}

.assignment-card {
    background: white;
    border-radius: 1rem;
    padding: 1.5rem;
    margin-bottom: 1rem;
    box-shadow: var(--card-shadow);
    transition: all var(--transition-speed);
}

.assignment-card:hover {
    transform: translateY(-3px);
}

.progress-ring {
    width: 60px;
    height: 60px;
}

.practice-item {
    padding: 1rem;
    border-left: 4px solid #cdaf56;
    background: white;
    margin-bottom: 1rem;
    border-radius: 0 0.5rem 0.5rem 0;
    transition: all var(--transition-speed);
}

.practice-item:hover {
    transform: translateX(5px);
    box-shadow: var(--card-shadow);
}

.category-badge {
    background: rgba(205, 175, 86, 0.1);
    color: #cdaf56;
    padding: 0.25rem 0.75rem;
    border-radius: 1rem;
    font-size: 0.875rem;
}

@media (max-width: 768px) {
    .stat-card {
        margin-bottom: 1rem;
    }
    
    .quick-actions {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="hero-section">
    <div class="container position-relative">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="display-4 mb-3">Welcome Back!</h1>
                <p class="lead mb-0">Here's your learning progress for today</p>
            </div>
            <div class="col-md-4 text-md-end">
                <div class="d-flex justify-content-md-end align-items-center">
                    <div class="me-3">
                        <div class="text-sm opacity-75">Today's Date</div>
                        <div class="h4 mb-0"><?php echo date('j M Y'); ?></div>
                    </div>
                    <div class="h1 mb-0">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container mb-5">
    <!-- Stats Section -->
    <div class="row mb-5">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-number"><?php echo (int)$stats['items_added_today']; ?></div>
                <div class="text-muted">Items Added Today</div>
                <div class="mt-3">
                    <i class="fas fa-plus-circle text-success"></i>
                    <span class="ms-2 small">New Entries</span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-number"><?php echo (int)$stats['categories_practiced']; ?></div>
                <div class="text-muted">Categories Practiced</div>
                <div class="mt-3">
                    <i class="fas fa-layer-group text-primary"></i>
                    <span class="ms-2 small">Topics Covered</span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-number"><?php echo (int)$stats['total_items']; ?></div>
                <div class="text-muted">Total Practice Items</div>
                <div class="mt-3">
                    <i class="fas fa-book text-info"></i>
                    <span class="ms-2 small">Learning Material</span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-number"><?php echo (int)$stats['favorite_items']; ?></div>
                <div class="text-muted">Favorite Items</div>
                <div class="mt-3">
                    <i class="fas fa-star text-warning"></i>
                    <span class="ms-2 small">Saved for Review</span>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Quick Actions -->
        <div class="col-md-4 mb-4">
            <h2 class="h4 mb-4">Quick Actions</h2>
            <div class="d-grid gap-3">
                <a href="/pages/EnglishPractice/practice.php" class="quick-action">
                    <i class="fas fa-play-circle"></i>
                    <div>
                        <div class="fw-bold">Start Practice</div>
                        <div class="small text-muted">Review your flashcards</div>
                    </div>
                </a>
                <a href="/pages/EnglishPractice/daily_entry.php" class="quick-action">
                    <i class="fas fa-plus-circle"></i>
                    <div>
                        <div class="fw-bold">Add New Entry</div>
                        <div class="small text-muted">Create practice items</div>
                    </div>
                </a>
                <a href="/pages/EnglishPractice/review.php?favorites=1" class="quick-action">
                    <i class="fas fa-star"></i>
                    <div>
                        <div class="fw-bold">Review Favorites</div>
                        <div class="small text-muted">Practice saved items</div>
                    </div>
                </a>
            </div>
        </div>

        <!-- Upcoming Assignments -->
        <div class="col-md-4 mb-4">
            <h2 class="h4 mb-4">Upcoming Assignments</h2>
            <?php if (!empty($assignments)): ?>
                <?php foreach ($assignments as $assignment): 
                    $progress = $assignment['total_criteria'] > 0 ? 
                        ($assignment['completed_criteria'] / $assignment['total_criteria']) * 100 : 0;
                    $days_left = (strtotime($assignment['due_date']) - time()) / (60 * 60 * 24);
                ?>
                    <div class="assignment-card">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h3 class="h6 mb-1"><?php echo htmlspecialchars($assignment['title']); ?></h3>
                                <div class="text-muted small">
                                    Due in <?php echo ceil($days_left); ?> days
                                </div>
                            </div>
                            <div class="ms-3">
                                <div class="progress" style="width: 60px; height: 60px;">
                                    <div class="progress-bar bg-success" role="progressbar" 
                                         style="width: <?php echo $progress; ?>%" 
                                         aria-valuenow="<?php echo $progress; ?>" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="small">
                                <span class="text-success">
                                    <?php echo $assignment['completed_criteria']; ?>/<?php echo $assignment['total_criteria']; ?>
                                </span> criteria completed
                            </div>
                            <a href="#" class="btn btn-sm btn-outline-primary">Continue</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center py-4 text-muted">
                    <i class="fas fa-check-circle fa-2x mb-3"></i>
                    <p class="mb-0">No upcoming assignments!</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Recent Practice Items -->
        <div class="col-md-4 mb-4">
            <h2 class="h4 mb-4">Recent Practice Items</h2>
            <?php if (!empty($recent_items)): ?>
                <?php foreach ($recent_items as $item): ?>
                    <div class="practice-item">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h3 class="h6 mb-1"><?php echo htmlspecialchars($item['item_title']); ?></h3>
                            <button class="btn btn-link p-0 toggle-favorite" data-item-id="<?php echo $item['id']; ?>">
                                <i class="<?php echo $item['is_favorite'] ? 'fas' : 'far'; ?> fa-star text-warning"></i>
                            </button>
                        </div>
                        <span class="category-badge">
                            <?php echo htmlspecialchars($item['category_name']); ?>
                        </span>
                        <div class="mt-2 small text-muted">
                            Added <?php echo date('j M', strtotime($item['created_at'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center py-4 text-muted">
                    <i class="fas fa-book fa-2x mb-3"></i>
                    <p class="mb-0">No practice items yet!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltips.forEach(tooltip => new bootstrap.Tooltip(tooltip));

    // Favorite toggle functionality
    document.querySelectorAll('.toggle-favorite').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const itemId = this.dataset.itemId;
            const icon = this.querySelector('i');
            
            fetch('EnglishPractice/toggle_favorite.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `item_id=${itemId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    icon.classList.toggle('far');
                    icon.classList.toggle('fas');
                    
                    // Show toast notification
                    const toast = new bootstrap.Toast(document.createElement('div'));
                    toast.show();
                }
            })
            .catch(error => console.error('Error:', error));
        });
    });

    // Add animation to stats
    const stats = document.querySelectorAll('.stat-number');
    stats.forEach(stat => {
        const finalValue = parseInt(stat.textContent);
        let currentValue = 0;
        const duration = 1000; // 1 second
        const increment = finalValue / (duration / 16); // 60fps

        const animate = () => {
            currentValue = Math.min(currentValue + increment, finalValue);
            stat.textContent = Math.round(currentValue);
            
            if (currentValue < finalValue) {
                requestAnimationFrame(animate);
            }
        };

        animate();
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?> 