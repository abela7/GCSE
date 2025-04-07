<?php
// GCSE/pages/EnglishPractice/review.php
// Set timezone to London
date_default_timezone_set('Europe/London');

session_start();
require_once __DIR__ . '/../../includes/db_connect.php';
require_once __DIR__ . '/_functions.php';

// Handle favorite toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_favorite') {
    $item_id = $_POST['item_id'];
    $is_favorite = $_POST['is_favorite'];
    
    if ($is_favorite == 1) {
        // Add to favorites
        $stmt = $conn->prepare("INSERT INTO favorite_practice_items (practice_item_id) VALUES (?)");
    } else {
        // Remove from favorites
        $stmt = $conn->prepare("DELETE FROM favorite_practice_items WHERE practice_item_id = ?");
    }
    
    if ($stmt) {
        $stmt->bind_param("i", $item_id);
        if ($stmt->execute()) {
            $_SESSION['success'] = "Favorite status updated successfully!";
        } else {
            $_SESSION['error'] = "Failed to update favorite status.";
        }
        $stmt->close();
    }
    
    // Redirect back to prevent form resubmission
    header("Location: " . $_SERVER['PHP_SELF'] . (isset($_GET['favorites']) ? '?favorites=1' : ''));
    exit;
}

// Get all categories for filter
$categories = [];
$category_query = "SELECT id, name FROM practice_categories ORDER BY name ASC";
$category_result = $conn->query($category_query);
if ($category_result) {
    while ($row = $category_result->fetch_assoc()) {
        $categories[] = $row;
    }
    $category_result->free();
}

// Handle filters
$selected_category = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$where_conditions = [];

if (isset($_GET['favorites']) && $_GET['favorites'] == 1) {
    $where_conditions[] = "fpi.practice_item_id IS NOT NULL";
}

if ($selected_category > 0) {
    $where_conditions[] = "pi.category_id = " . $selected_category;
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get items with filters applied
$items = [];
$query = "
    SELECT pi.*, pc.name as category_name, 
           CASE WHEN fpi.practice_item_id IS NOT NULL THEN 1 ELSE 0 END as is_favorite
    FROM practice_items pi
    JOIN practice_categories pc ON pi.category_id = pc.id
    LEFT JOIN favorite_practice_items fpi ON pi.id = fpi.practice_item_id
    $where_clause
    ORDER BY pc.name ASC, pi.item_title ASC
";

$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    $result->free();
}

// Get favorite items for quick lookup
$favorites_lookup = [];
$favorites_sql = "SELECT practice_item_id FROM favorite_practice_items";
if ($result = $conn->query($favorites_sql)) {
    while ($row = $result->fetch_assoc()) {
        $favorites_lookup[$row['practice_item_id']] = true;
    }
    $result->free();
}

// --- Determine View Mode and Parameters ---
$view_mode = isset($_GET['view']) ? $_GET['view'] : 'day'; // Default to day view
$page_title = "Review Practice Items";

// Daily View Logic
$selected_date_str = date('Y-m-d'); // Default to current date in London timezone
if ($view_mode === 'day') {
    $selected_date_str = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
    try {
        $selected_date = new DateTimeImmutable($selected_date_str, new DateTimeZone('Europe/London'));
        $formatted_date = $selected_date->format('Y-m-d');
        $display_date = format_practice_date($formatted_date); // Format for display
        $page_title = "Review - " . $selected_date->format('M d, Y');
        $prev_date = $selected_date->modify('-1 day')->format('Y-m-d');
        $next_date = $selected_date->modify('+1 day')->format('Y-m-d');
        $practice_day_id = get_practice_day_id($conn, $formatted_date);
    } catch (Exception $e) {
        $_SESSION['error_ep'] = "Invalid date format provided.";
        header('Location: review.php'); // Redirect to default (today)
        exit;
    }
    $items_for_display = $practice_day_id ? get_practice_items_by_day($conn, $practice_day_id) : [];
}

// Weekly View Logic (Add this block)
elseif ($view_mode === 'week') {
    // Get week number. Find current week if not specified.
    $current_week_stmt = $conn->prepare("SELECT week_number FROM practice_days WHERE practice_date = ?");
    $today_for_week = date('Y-m-d');
    $current_week_num = 1; // Default
    if($current_week_stmt) {
        $current_week_stmt->bind_param("s", $today_for_week);
        if($current_week_stmt->execute()) {
            $current_week_res = $current_week_stmt->get_result();
            if($cw_row = $current_week_res->fetch_assoc()){
                $current_week_num = $cw_row['week_number'];
            }
        }
        $current_week_stmt->close();
    }

    $selected_week_num = isset($_GET['week']) ? filter_var($_GET['week'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) : $current_week_num;
    if($selected_week_num === false) $selected_week_num = $current_week_num; // Fallback if invalid

    $week_dates = get_week_dates($conn, $selected_week_num); // Get start/end date of week

    if(!$week_dates){
        $_SESSION['error_ep'] = "Invalid week number or week data not found.";
         $items_for_display_by_date = []; // Ensure it's an empty array
         // Don't redirect, show error on page
    } else {
        $page_title = "Review - Week " . $selected_week_num . " (".(new DateTime($week_dates['start_date']))->format('M d')." - ".(new DateTime($week_dates['end_date']))->format('M d').")";
        $items_for_display_by_date = get_practice_items_by_week($conn, $selected_week_num); // Fetches items grouped by date within the week
        // For Week navigation (simplified - requires knowing max week)
        // Fetch max week number once
        $max_week_res = $conn->query("SELECT MAX(week_number) as max_week FROM practice_days");
        $max_week = $max_week_res ? $max_week_res->fetch_assoc()['max_week'] : $selected_week_num;
        $prev_week = ($selected_week_num > 1) ? $selected_week_num - 1 : null;
        $next_week = ($selected_week_num < $max_week) ? $selected_week_num + 1 : null;
    }

}
else {
    // Default or handle invalid view mode
     $_SESSION['error_ep'] = "Invalid view mode selected.";
     header('Location: review.php'); // Redirect to default day view
     exit;
}


// --- Start HTML ---
require_once __DIR__ . '/../../includes/header.php';
?>

<style>
:root {
    --accent-color: #cdaf56;
    --accent-light: #d9c07a;
    --accent-lighter: #f5ecd6;
    --accent-dark: #b69843;
}

.bg-accent {
    background-color: var(--accent-color) !important;
}

.text-accent {
    color: var(--accent-color) !important;
}

.btn-accent {
    background-color: var(--accent-color);
    color: white;
    border: none;
}

.btn-accent:hover {
    background-color: var(--accent-dark);
    color: white;
}

.btn-outline-accent {
    border-color: var(--accent-color);
    color: var(--accent-color);
}

.btn-outline-accent:hover {
    background-color: var(--accent-color);
    color: white;
}

.card {
    border-color: var(--accent-lighter);
    transition: transform 0.2s ease-in-out;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.favorite-btn {
    color: var(--accent-color);
    cursor: pointer;
    transition: transform 0.2s ease;
}

.favorite-btn:hover {
    transform: scale(1.1);
}

.favorite-btn.active {
    color: var(--accent-color);
}

.category-badge {
    background-color: var(--accent-lighter);
    color: var(--accent-dark);
    font-weight: 500;
}

.nav-link.active {
    background-color: var(--accent-color) !important;
    color: white !important;
}

.filter-section {
    background-color: var(--accent-lighter);
    border-radius: 10px;
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.filter-section select {
    border-color: var(--accent-color);
}

.filter-section select:focus {
    border-color: var(--accent-dark);
    box-shadow: 0 0 0 0.2rem rgba(205, 175, 86, 0.25);
}

.filter-badge {
    background-color: var(--accent-color);
    color: white;
    font-size: 0.8rem;
    padding: 0.3rem 0.6rem;
    border-radius: 20px;
    margin-left: 0.5rem;
}
</style>

<div class="container py-4">
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i><?php echo $_SESSION['success']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i><?php echo $_SESSION['error']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <!-- Header Section -->
    <div class="row mb-4 align-items-center">
        <div class="col-md-8">
            <h1 class="h2 mb-2 fw-bold text-accent">Review Practice Items</h1>
            <p class="text-muted lead">Review and manage your English practice items</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="practice.php" class="btn btn-accent me-2">
                <i class="fas fa-graduation-cap me-2"></i>Practice
            </a>
            <a href="daily_entry.php" class="btn btn-outline-accent">
                <i class="fas fa-plus me-2"></i>New Entry
            </a>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="filter-section mb-4">
        <div class="row align-items-center">
            <div class="col-md-4 mb-3 mb-md-0">
                <form method="GET" class="d-flex gap-2 align-items-end">
                    <?php if (isset($_GET['favorites'])): ?>
                        <input type="hidden" name="favorites" value="1">
                    <?php endif; ?>
                    <div class="flex-grow-1">
                        <label for="categoryFilter" class="form-label fw-bold">Filter by Category</label>
                        <select class="form-select" id="categoryFilter" name="category">
                            <option value="0">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" 
                                        <?php echo ($selected_category == $category['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <button type="submit" class="btn btn-accent">
                            <i class="fas fa-filter me-1"></i>Filter
                        </button>
                    </div>
                </form>
            </div>
            <div class="col-md-8">
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <span class="fw-bold">Quick Filters:</span>
                    <a href="review.php" class="btn btn-sm <?php echo (!isset($_GET['favorites']) && !$selected_category) ? 'btn-accent' : 'btn-outline-accent'; ?>">
                        All Items
                    </a>
                    <a href="?favorites=1" class="btn btn-sm <?php echo isset($_GET['favorites']) ? 'btn-accent' : 'btn-outline-accent'; ?>">
                        <i class="fas fa-star me-1"></i>Favorites
                    </a>
                    <?php if ($selected_category): ?>
                        <div class="ms-2">
                            <span class="filter-badge">
                                Category: <?php echo htmlspecialchars(array_values(array_filter($categories, function($cat) use ($selected_category) { 
                                    return $cat['id'] == $selected_category; 
                                }))[0]['name']); ?>
                                <a href="<?php echo isset($_GET['favorites']) ? '?favorites=1' : 'review.php'; ?>" class="text-white text-decoration-none ms-2">
                                    <i class="fas fa-times"></i>
                                </a>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Items Grid -->
    <div class="row g-4">
        <?php if (empty($items)): ?>
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <h4 class="text-muted">No items found</h4>
                        <p class="text-muted mb-0">
                            <?php echo isset($_GET['favorites']) ? 
                                'You haven\'t added any favorites yet.' : 
                                'Start by adding some practice items.'; ?>
                        </p>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($items as $item): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-header bg-accent bg-opacity-10 d-flex justify-content-between align-items-center py-3">
                            <span class="category-badge badge rounded-pill">
                                <?php echo htmlspecialchars($item['category_name']); ?>
                            </span>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="action" value="toggle_favorite">
                                <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                <input type="hidden" name="is_favorite" value="<?php echo $item['is_favorite'] ? '0' : '1'; ?>">
                                <button type="submit" class="btn btn-link p-0 favorite-btn <?php echo $item['is_favorite'] ? 'active' : ''; ?>">
                                    <i class="fas fa-star fa-lg"></i>
                                </button>
                            </form>
                        </div>
                        <div class="card-body">
                            <h5 class="card-title text-accent mb-3">
                                <?php echo htmlspecialchars($item['item_title']); ?>
                            </h5>
                            <div class="card-text mb-3">
                                <p class="fw-bold mb-1">Meaning/Rule:</p>
                                <p class="text-muted"><?php echo nl2br(htmlspecialchars($item['item_meaning'])); ?></p>
                            </div>
                            <div class="card-text">
                                <p class="fw-bold mb-1">Example:</p>
                                <p class="fst-italic text-muted mb-0">
                                    <?php echo nl2br(htmlspecialchars($item['item_example'])); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>