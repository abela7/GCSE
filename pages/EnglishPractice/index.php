<?php
// GCSE/pages/EnglishPractice/index.php
session_start();
require_once __DIR__ . '/../../includes/db_connect.php';
require_once __DIR__ . '/_functions.php';

$page_title = "English Practice Dashboard";

// Get today's date and practice day ID
$today = date('Y-m-d');
$today_practice_id = get_practice_day_id($conn, $today);

// Get recent practice days (last 5 days with entries)
$recent_days_sql = "
    SELECT DISTINCT pd.practice_date, pd.week_number, 
           COUNT(pi.id) as item_count
    FROM practice_days pd
    LEFT JOIN practice_items pi ON pd.id = pi.practice_day_id
    GROUP BY pd.id
    ORDER BY pd.practice_date DESC
    LIMIT 5";
$recent_days = [];
if ($result = $conn->query($recent_days_sql)) {
    while ($row = $result->fetch_assoc()) {
        $recent_days[] = $row;
    }
    $result->free();
}

// Get practice statistics
$stats_sql = "
    SELECT pc.name as category_name, 
           COUNT(pi.id) as item_count
    FROM practice_categories pc
    LEFT JOIN practice_items pi ON pc.id = pi.category_id
    GROUP BY pc.id
    ORDER BY item_count DESC";
$category_stats = [];
if ($result = $conn->query($stats_sql)) {
    while ($row = $result->fetch_assoc()) {
        $category_stats[] = $row;
    }
    $result->free();
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container mt-4 mb-5">
    <h1 class="h3 mb-4"><?php echo $page_title; ?></h1>

    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-md-6 col-lg-3 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">Quick Add</h5>
                    <p class="card-text">Add new practice items for today.</p>
                    <a href="daily_entry.php" class="btn btn-primary">
                        <i class="fas fa-plus-circle me-1"></i>Add Items
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">Practice Now</h5>
                    <p class="card-text">Start a flashcard practice session.</p>
                    <a href="practice.php" class="btn btn-success">
                        <i class="fas fa-bolt me-1"></i>Practice
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">Today's Review</h5>
                    <p class="card-text">Review today's practice items.</p>
                    <a href="review.php?view=day&date=<?php echo $today; ?>" class="btn btn-info text-white">
                        <i class="fas fa-list me-1"></i>Review Today
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">Weekly Overview</h5>
                    <p class="card-text">See this week's progress.</p>
                    <a href="review.php?view=week" class="btn btn-secondary">
                        <i class="fas fa-calendar-week me-1"></i>View Week
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity and Stats -->
    <div class="row">
        <!-- Recent Days -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Recent Practice Days</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($recent_days)): ?>
                        <p class="text-muted">No recent practice days found.</p>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($recent_days as $day): ?>
                                <a href="review.php?view=day&date=<?php echo $day['practice_date']; ?>" 
                                   class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="fw-bold"><?php echo format_practice_date($day['practice_date']); ?></span>
                                        <small class="text-muted ms-2">Week <?php echo $day['week_number']; ?></small>
                                    </div>
                                    <span class="badge bg-primary rounded-pill"><?php echo $day['item_count']; ?> items</span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                        <div class="mt-3">
                            <a href="review.php?view=week" class="btn btn-sm btn-outline-secondary">View All History</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Category Stats -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Practice Statistics</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($category_stats)): ?>
                        <p class="text-muted">No practice statistics available.</p>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($category_stats as $stat): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0"><?php echo htmlspecialchars($stat['category_name']); ?></h6>
                                        <span class="badge bg-secondary"><?php echo $stat['item_count']; ?> items</span>
                                    </div>
                                    <div class="progress mt-2" style="height: 5px;">
                                        <div class="progress-bar" role="progressbar" 
                                             style="width: <?php echo min(100, ($stat['item_count'] / 100) * 100); ?>%"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Practice Options -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Practice Options</h5>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-md-4">
                            <h6>By Time Period</h6>
                            <div class="list-group">
                                <a href="practice.php?date_filter=today" class="list-group-item list-group-item-action">
                                    <i class="fas fa-clock me-2"></i>Today's Items
                                </a>
                                <a href="practice.php?date_filter=week" class="list-group-item list-group-item-action">
                                    <i class="fas fa-calendar-week me-2"></i>This Week's Items
                                </a>
                                <a href="practice.php" class="list-group-item list-group-item-action">
                                    <i class="fas fa-infinity me-2"></i>All Items
                                </a>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <h6>By Category</h6>
                            <div class="list-group">
                                <?php foreach ($category_stats as $stat): ?>
                                    <a href="practice.php?category_id=<?php echo $stat['category_id']; ?>" 
                                       class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                        <?php echo htmlspecialchars($stat['category_name']); ?>
                                        <span class="badge bg-secondary rounded-pill"><?php echo $stat['item_count']; ?></span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <h6>Review Options</h6>
                            <div class="list-group">
                                <a href="review.php?view=day" class="list-group-item list-group-item-action">
                                    <i class="fas fa-list me-2"></i>Daily View
                                </a>
                                <a href="review.php?view=week" class="list-group-item list-group-item-action">
                                    <i class="fas fa-calendar-alt me-2"></i>Weekly View
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?> 