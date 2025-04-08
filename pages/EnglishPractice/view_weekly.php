<?php
// GCSE/pages/EnglishPractice/view_weekly.php
session_start();
require_once __DIR__ . '/../../includes/db_connect.php';
require_once __DIR__ . '/_functions.php';

// Set timezone to London
date_default_timezone_set('Europe/London');

// Get current week number from URL or default to current week
$current_week_stmt = $conn->prepare("SELECT week_number FROM practice_days WHERE practice_date = CURRENT_DATE");
$current_week_stmt->execute();
$current_week = $current_week_stmt->get_result()->fetch_assoc()['week_number'] ?? 1;

$selected_week = isset($_GET['week']) ? (int)$_GET['week'] : $current_week;
$selected_category = isset($_GET['category']) ? (int)$_GET['category'] : 0;

// Get all weeks for navigation
$weeks_query = "SELECT DISTINCT week_number, 
                      MIN(practice_date) as week_start, 
                      MAX(practice_date) as week_end 
               FROM practice_days 
               GROUP BY week_number 
               ORDER BY week_number DESC";
$weeks_result = $conn->query($weeks_query);
$weeks = [];
while ($week = $weeks_result->fetch_assoc()) {
    $weeks[] = $week;
}

// Get categories for filter
$categories = [];
$cat_result = $conn->query("SELECT id, name FROM practice_categories ORDER BY name ASC");
if ($cat_result) {
    while ($row = $cat_result->fetch_assoc()) {
        $categories[] = $row;
    }
    $cat_result->free();
}

// Get practice items for selected week
$items_query = "
    SELECT pi.*, pc.name as category_name, pc.color as category_color,
           pd.practice_date, pd.day_number,
           CASE WHEN fpi.practice_item_id IS NOT NULL THEN 1 ELSE 0 END as is_favorite
    FROM practice_items pi
    JOIN practice_days pd ON pi.practice_day_id = pd.id
    JOIN practice_categories pc ON pi.category_id = pc.id
    LEFT JOIN favorite_practice_items fpi ON pi.id = fpi.practice_item_id
    WHERE pd.week_number = ?
    " . ($selected_category > 0 ? "AND pi.category_id = ?" : "") . "
    ORDER BY pd.practice_date ASC, pc.name ASC, pi.item_title ASC";

$stmt = $conn->prepare($items_query);
if ($selected_category > 0) {
    $stmt->bind_param('ii', $selected_week, $selected_category);
} else {
    $stmt->bind_param('i', $selected_week);
}
$stmt->execute();
$result = $stmt->get_result();

// Organize items by day
$days_items = [];
while ($item = $result->fetch_assoc()) {
    $date = $item['practice_date'];
    if (!isset($days_items[$date])) {
        $days_items[$date] = [];
    }
    $days_items[$date][] = $item;
}

$page_title = "Weekly Revision - English";
require_once __DIR__ . '/../../includes/header.php';
?>

<style>
.week-nav {
    max-width: 800px;
    margin: 0 auto 2rem;
    background: white;
    padding: 1.5rem;
    border-radius: 15px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.05);
}

.accordion-button:not(.collapsed) {
    background-color: #f8f9fa;
    color: #2c3e50;
}

.category-badge {
    font-size: 0.85rem;
    padding: 0.35rem 0.75rem;
    border-radius: 20px;
    margin-right: 0.5rem;
}

.item-card {
    border: none;
    border-radius: 10px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    transition: transform 0.2s;
}

.item-card:hover {
    transform: translateY(-2px);
}

.favorite-btn {
    color: #ffc107;
    background: none;
    border: none;
    padding: 0;
    font-size: 1.1rem;
    cursor: pointer;
    transition: transform 0.2s;
}

.favorite-btn:hover {
    transform: scale(1.1);
}

.date-header {
    font-size: 1.2rem;
    color: #2c3e50;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #e9ecef;
}

.filters-container {
    max-width: 800px;
    margin: 0 auto 2rem;
    background: white;
    padding: 1.5rem;
    border-radius: 15px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.05);
}
</style>

<div class="container py-4">
    <!-- Header -->
    <div class="text-center mb-4">
        <h1 class="display-5 mb-2">Weekly Revision</h1>
        <p class="text-muted">Review your practice items by week</p>
    </div>

    <!-- Filters -->
    <div class="filters-container">
        <form action="view_weekly.php" method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Select Week</label>
                <select name="week" class="form-select">
                    <?php foreach ($weeks as $week): ?>
                        <option value="<?php echo $week['week_number']; ?>" 
                                <?php echo $selected_week == $week['week_number'] ? 'selected' : ''; ?>>
                            Week <?php echo $week['week_number']; ?> 
                            (<?php echo date('M d', strtotime($week['week_start'])); ?> - 
                             <?php echo date('M d', strtotime($week['week_end'])); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Filter by Category</label>
                <select name="category" class="form-select">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" 
                                <?php echo $selected_category == $cat['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-filter me-1"></i> Apply Filters
                </button>
            </div>
        </form>
    </div>

    <?php if (empty($days_items)): ?>
        <div class="text-center py-5">
            <i class="fas fa-book-open fa-3x text-muted mb-3"></i>
            <h3 class="h4 mb-2">No Practice Items Found</h3>
            <p class="text-muted mb-4">No items found for the selected week and filters.</p>
        </div>
    <?php else: ?>
        <div class="accordion" id="weeklyAccordion">
            <?php foreach ($days_items as $date => $items): 
                $date_id = str_replace('-', '', $date);
            ?>
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" 
                                data-bs-toggle="collapse" 
                                data-bs-target="#collapse<?php echo $date_id; ?>">
                            <?php echo date('l, F j, Y', strtotime($date)); ?> 
                            <span class="badge bg-secondary ms-2"><?php echo count($items); ?> items</span>
                        </button>
                    </h2>
                    <div id="collapse<?php echo $date_id; ?>" 
                         class="accordion-collapse collapse" 
                         data-bs-parent="#weeklyAccordion">
                        <div class="accordion-body">
                            <div class="row g-4">
                                <?php foreach ($items as $item): ?>
                                    <div class="col-md-6">
                                        <div class="card item-card h-100">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <span class="category-badge" 
                                                          style="background-color: <?php echo $item['category_color']; ?>20; color: <?php echo $item['category_color']; ?>;">
                                                        <?php echo htmlspecialchars($item['category_name']); ?>
                                                    </span>
                                                    <button type="button" 
                                                            class="favorite-btn toggle-favorite" 
                                                            data-item-id="<?php echo $item['id']; ?>">
                                                        <i class="<?php echo $item['is_favorite'] ? 'fas' : 'far'; ?> fa-star"></i>
                                                    </button>
                                                </div>
                                                <h5 class="card-title mb-3">
                                                    <?php echo html_entity_decode(htmlspecialchars_decode($item['item_title']), ENT_QUOTES | ENT_HTML5, 'UTF-8'); ?>
                                                </h5>
                                                <p class="card-text">
                                                    <strong>Meaning/Rule:</strong><br>
                                                    <?php echo nl2br(htmlspecialchars($item['item_meaning'])); ?>
                                                </p>
                                                <p class="card-text">
                                                    <strong>Example:</strong><br>
                                                    <em><?php echo nl2br(htmlspecialchars($item['item_example'])); ?></em>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
// Favorite button functionality
document.querySelectorAll('.toggle-favorite').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        const itemId = this.dataset.itemId;
        const icon = this.querySelector('i');
        
        fetch('toggle_favorite.php', {
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
            }
        })
        .catch(error => console.error('Error:', error));
    });
});

// Auto-expand current day's accordion if no specific day is expanded
document.addEventListener('DOMContentLoaded', function() {
    const today = new Date().toISOString().split('T')[0].replace(/-/g, '');
    const currentDayAccordion = document.getElementById(`collapse${today}`);
    if (currentDayAccordion && !document.querySelector('.accordion-collapse.show')) {
        currentDayAccordion.classList.add('show');
    }
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?> 