<?php
// GCSE/pages/EnglishPractice/review.php
session_start();
require_once __DIR__ . '/../../includes/db_connect.php';
require_once '_functions.php';

// Get date parameter, default to today
$selected_date_str = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
try {
    $selected_date = new DateTimeImmutable($selected_date_str);
    $formatted_date = $selected_date->format('Y-m-d');
    $display_date = format_practice_date($formatted_date);
} catch (Exception $e) {
    $_SESSION['error_ep'] = "Invalid date format.";
    header('Location: review.php');
    exit;
}

// Get practice day ID
$practice_day_id = null;
$stmt = $conn->prepare("SELECT id FROM practice_days WHERE practice_date = ?");
if ($stmt) {
    $stmt->bind_param("s", $formatted_date);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $practice_day_id = $row['id'];
    }
    $stmt->close();
}

// Get items for this day
$practice_items = [];
if ($practice_day_id) {
    $practice_items = get_practice_items_by_day($conn, $practice_day_id);
}

// Set page title and include header
$page_title = "Review Practice - " . $selected_date->format('M d');
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container mt-4 mb-5">
    <!-- Session Messages -->
    <?php if (!empty($_SESSION['success_ep'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($_SESSION['success_ep']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['success_ep']); ?>
    <?php endif; ?>
    
    <?php if (!empty($_SESSION['error_ep'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($_SESSION['error_ep']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['error_ep']); ?>
    <?php endif; ?>

    <!-- Header -->
    <div class="text-center mb-4">
        <h1 class="h3"><?php echo $display_date; ?></h1>
        <p class="text-muted">Review your practice items for this day</p>
    </div>

    <?php if (empty($practice_items)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            No practice items found for this date.
            <a href="daily_entry.php?date=<?php echo urlencode($formatted_date); ?>" class="alert-link">Add some now?</a>
        </div>
    <?php else: ?>
        <!-- Practice Items Accordion -->
        <div class="accordion review-accordion" id="practiceAccordion">
            <?php foreach ($practice_items as $category_id => $category): ?>
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                data-bs-target="#category<?php echo $category_id; ?>">
                            <?php echo htmlspecialchars($category['name']); ?>
                            <span class="badge bg-secondary ms-2"><?php echo count($category['items']); ?></span>
                        </button>
                    </h2>
                    <div id="category<?php echo $category_id; ?>" class="accordion-collapse collapse" 
                         data-bs-parent="#practiceAccordion">
                        <div class="accordion-body">
                            <ul class="list-unstyled mb-0">
                                <?php foreach ($category['items'] as $item): ?>
                                    <li>
                                        <div class="item-title"><?php echo htmlspecialchars($item['item_title']); ?></div>
                                        <div class="item-meta">
                                            <strong>Meaning:</strong> <?php echo htmlspecialchars($item['item_meaning']); ?>
                                        </div>
                                        <div class="item-meta">
                                            <strong>Example:</strong> <?php echo htmlspecialchars($item['item_example']); ?>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Action Buttons -->
        <div class="text-center mt-4">
            <a href="daily_entry.php?date=<?php echo urlencode($formatted_date); ?>" class="btn btn-outline-primary me-2">
                <i class="fas fa-edit me-2"></i>Edit Entries
            </a>
            <a href="practice.php" class="btn btn-primary">
                <i class="fas fa-graduation-cap me-2"></i>Practice Mode
            </a>
        </div>
    <?php endif; ?>
</div>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?> 