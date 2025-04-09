<?php
require_once __DIR__ . '/../../includes/auth_check.php';

// GCSE/pages/EnglishPractice/index.php
session_start();
require_once __DIR__ . '/../../includes/db_connect.php';
require_once __DIR__ . '/_functions.php';

$page_title = "English Practice Dashboard";

// Get today's date and practice day ID
$today = date('Y-m-d');
$today_practice_id = get_practice_day_id($conn, $today);

// Get favorite items
$favorites_sql = "
    SELECT pi.*, pc.name as category_name, fpi.favorited_at,
           pd.practice_date
    FROM favorite_practice_items fpi
    JOIN practice_items pi ON fpi.practice_item_id = pi.id
    JOIN practice_categories pc ON pi.category_id = pc.id
    JOIN practice_days pd ON pi.practice_day_id = pd.id
    ORDER BY fpi.favorited_at DESC
    LIMIT 5";
$favorite_items = [];
if ($result = $conn->query($favorites_sql)) {
    while ($row = $result->fetch_assoc()) {
        $favorite_items[] = $row;
    }
    $result->free();
}

// Get recent practice days (last 5 days with entries)
$recent_days_sql = "
    SELECT DISTINCT pd.practice_date, pd.week_number, 
           COUNT(pi.id) as item_count,
           COUNT(fpi.id) as favorite_count
    FROM practice_days pd
    LEFT JOIN practice_items pi ON pd.id = pi.practice_day_id
    LEFT JOIN favorite_practice_items fpi ON pi.id = fpi.practice_item_id
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
    SELECT pc.name as category_name, pc.id as category_id,
           COUNT(pi.id) as item_count,
           COUNT(fpi.id) as favorite_count
    FROM practice_categories pc
    LEFT JOIN practice_items pi ON pc.id = pi.category_id
    LEFT JOIN favorite_practice_items fpi ON pi.id = fpi.practice_item_id
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
                    <div class="btn-group w-100">
                        <a href="practice.php" class="btn btn-success">
                            <i class="fas fa-bolt me-1"></i>Practice All
                        </a>
                        <a href="practice.php?favorites=1" class="btn btn-outline-success">
                            <i class="fas fa-star me-1"></i>Favorites
                        </a>
                    </div>
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
                    <a href="/pages/EnglishPractice/view_weekly.php" class="btn btn-secondary">
                        <i class="fas fa-calendar-week me-1"></i>View Week
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Favorites Section -->
    <?php if (!empty($favorite_items)): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-star text-warning me-2"></i>Favorite Items
                    </h5>
                    <a href="practice.php?favorites=1" class="btn btn-sm btn-outline-warning">
                        Practice Favorites
                    </a>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach ($favorite_items as $item): ?>
                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="card h-100 border-warning">
                                <div class="card-body">
                                    <h6 class="card-title d-flex justify-content-between">
                                        <?php echo htmlspecialchars($item['item_title']); ?>
                                        <small class="text-muted"><?php echo htmlspecialchars($item['category_name']); ?></small>
                                    </h6>
                                    <p class="card-text small mb-2"><?php echo htmlspecialchars($item['item_meaning']); ?></p>
                                    <?php if (!empty($item['item_example'])): ?>
                                        <p class="card-text small text-muted fst-italic">
                                            "<?php echo htmlspecialchars($item['item_example']); ?>"
                                        </p>
                                    <?php endif; ?>
                                </div>
                                <div class="card-footer bg-transparent border-warning">
                                    <small class="text-muted">
                                        Added on <?php echo format_practice_date($item['practice_date']); ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

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
                                    <div>
                                        <span class="badge bg-primary rounded-pill me-2"><?php echo $day['item_count']; ?> items</span>
                                        <?php if ($day['favorite_count'] > 0): ?>
                                            <span class="badge bg-warning rounded-pill">
                                                <i class="fas fa-star me-1"></i><?php echo $day['favorite_count']; ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
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
                                        <div>
                                            <span class="badge bg-secondary me-2"><?php echo $stat['item_count']; ?> items</span>
                                            <?php if ($stat['favorite_count'] > 0): ?>
                                                <span class="badge bg-warning">
                                                    <i class="fas fa-star me-1"></i><?php echo $stat['favorite_count']; ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
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
                                <a href="practice.php?favorites=1" class="list-group-item list-group-item-action">
                                    <i class="fas fa-star me-2"></i>Favorite Items
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
                                        <div>
                                            <span class="badge bg-secondary rounded-pill me-1"><?php echo $stat['item_count']; ?></span>
                                            <?php if ($stat['favorite_count'] > 0): ?>
                                                <span class="badge bg-warning rounded-pill">
                                                    <i class="fas fa-star me-1"></i><?php echo $stat['favorite_count']; ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
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
                                <a href="review.php?favorites=1" class="list-group-item list-group-item-action">
                                    <i class="fas fa-star me-2"></i>Favorite Items
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