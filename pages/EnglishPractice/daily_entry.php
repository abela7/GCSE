<?php
// GCSE/pages/EnglishPractice/daily_entry.php
// Updated Version - No inline script tag

session_start();
require_once __DIR__ . '/../../config/db_connect.php';
// Optional: General functions if needed
// require_once __DIR__ . '/../../includes/functions.php';
// require_once __DIR__ . '/_functions.php';

// --- Determine Date ---
$selected_date_str = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
try { $selected_date = new DateTimeImmutable($selected_date_str); }
catch (Exception $e) { $selected_date = new DateTimeImmutable(); if(isset($_GET['date'])){ $_SESSION['warning_ep'] = "Invalid date. Showing today.";} }
$formatted_selected_date = $selected_date->format('Y-m-d');
$display_date_str = $selected_date->format('l, F j, Y');
$prev_date = $selected_date->modify('-1 day')->format('Y-m-d');
$next_date = $selected_date->modify('+1 day')->format('Y-m-d');

// --- Get Practice Day ID ---
$practice_day_id = null;
$stmt_day = $conn->prepare("SELECT id FROM practice_days WHERE practice_date = ?");
if ($stmt_day) {
    $stmt_day->bind_param("s", $formatted_selected_date); $stmt_day->execute(); $result_day = $stmt_day->get_result();
    if ($row_day = $result_day->fetch_assoc()) { $practice_day_id = $row_day['id']; }
    else { $_SESSION['error_ep'] = "Practice day for {$formatted_selected_date} not found."; error_log("Day not found: $formatted_selected_date");}
    $stmt_day->close();
} else { $_SESSION['error_ep'] = "DB error checking day."; error_log("DB error prep day check: ".$conn->error); }

// --- Fetch Categories ---
$categories = []; $cat_result = $conn->query("SELECT id, name FROM practice_categories ORDER BY id ASC");
if ($cat_result) { while($cat_row = $cat_result->fetch_assoc()) { $categories[] = $cat_row; } $cat_result->free(); }
else { $_SESSION['error_ep'] = "Could not load categories."; error_log("Error fetching cats: ".$conn->error); }
$items_per_category = 5;

// --- Start HTML ---
$page_title = "Daily English Entry - " . $selected_date->format('M d');
require_once __DIR__ . '/../../includes/header.php'; // Should now include conditional CSS link
?>

<div class="container mt-4 mb-5"> <!-- Added mb-5 -->

    <!-- Session Messages -->
    <?php if (!empty($_SESSION['success_ep'])): ?><div class="alert alert-success alert-dismissible fade show" role="alert"><i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($_SESSION['success_ep']); ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div><?php unset($_SESSION['success_ep']); endif; ?>
    <?php if (!empty($_SESSION['error_ep'])): ?><div class="alert alert-danger alert-dismissible fade show" role="alert"><i class="fas fa-exclamation-triangle me-2"></i> <?php echo htmlspecialchars($_SESSION['error_ep']); ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div><?php unset($_SESSION['error_ep']); endif; ?>
    <?php if (!empty($_SESSION['warning_ep'])): ?><div class="alert alert-warning alert-dismissible fade show" role="alert"><i class="fas fa-exclamation-circle me-2"></i> <?php echo htmlspecialchars($_SESSION['warning_ep']); ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div><?php unset($_SESSION['warning_ep']); endif; ?>


    <!-- Header and Date Navigation -->
    <div class="text-center mb-4">
        <div class="date-nav d-flex align-items-center justify-content-center mb-2">
            <a href="?date=<?php echo $prev_date; ?>" class="btn btn-outline-secondary btn-sm me-3" aria-label="Previous Day"><i class="fas fa-chevron-left"></i> Prev</a>
            <h1 class="h4 mb-0"><?php echo $display_date_str; ?></h1>
            <a href="?date=<?php echo $next_date; ?>" class="btn btn-outline-secondary btn-sm ms-3" aria-label="Next Day">Next <i class="fas fa-chevron-right"></i></a>
        </div>
         <small class="text-muted">Enter <?php echo $items_per_category ?> items for each category for this day.</small>
    </div>

    <?php if ($practice_day_id === null && !isset($_SESSION['error_ep'])) :
        // Show error only if DB query didn't fail but day wasn't found
        if(!isset($_SESSION['error_ep'])) $_SESSION['error_ep'] = "Practice day record not found for date: {$formatted_selected_date}. Please add it to the database.";
    ?>
         <div class="alert alert-danger"><?php echo htmlspecialchars($_SESSION['error_ep']); unset($_SESSION['error_ep']); ?></div>

    <?php elseif (empty($categories)): ?>
         <div class="alert alert-warning">Practice categories missing. Cannot add entries.</div>

    <?php else: ?>
        <!-- Form targets save_entry.php -->
        <form action="save_entry.php" method="POST" id="dailyEntryForm" novalidate>
            <input type="hidden" name="practice_date" value="<?php echo htmlspecialchars($formatted_selected_date); ?>">
            <input type="hidden" name="practice_day_id" value="<?php echo htmlspecialchars($practice_day_id); ?>"> <!-- Pass the found ID -->

            <?php foreach ($categories as $category):
                $cat_id = $category['id'];
                $cat_name = htmlspecialchars($category['name']);
            ?>
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light py-2"> <!-- Reduced padding -->
                        <h2 class="h6 mb-0 fw-normal"><?php echo $cat_name; ?></h2> <!-- Smaller header -->
                    </div>
                    <div class="card-body px-3 py-2"> <!-- Reduced padding -->
                       <!-- Input fields loop -->
                        <?php for ($i = 1; $i <= $items_per_category; $i++): ?>
                            <div class="row gx-2 mb-2 pb-2 entry-item <?php if ($i < $items_per_category) echo 'border-bottom';?>"> <!-- gx-2 for smaller gutter -->
                                <div class="col-md-1 pt-1 text-end pe-0 text-muted d-none d-md-block">
                                    <small><?php echo $i; ?>.</small>
                                </div>
                                <div class="col-12 col-md-11">
                                    <div class="mb-1">
                                        <label for="item_title_<?php echo $cat_id; ?>_<?php echo $i; ?>" class="form-label visually-hidden">Title/Word</label>
                                        <input type="text" class="form-control form-control-sm" id="item_title_<?php echo $cat_id; ?>_<?php echo $i; ?>" name="items[<?php echo $cat_id; ?>][<?php echo $i; ?>][title]" placeholder="<?php echo $cat_name; ?> - Title/Word <?php echo $i; ?>" required>
                                        <div class="invalid-feedback">Required.</div>
                                    </div>
                                    <div class="mb-1">
                                        <label for="item_meaning_<?php echo $cat_id; ?>_<?php echo $i; ?>" class="form-label visually-hidden">Meaning/Rule</label>
                                        <textarea class="form-control form-control-sm" id="item_meaning_<?php echo $cat_id; ?>_<?php echo $i; ?>" name="items[<?php echo $cat_id; ?>][<?php echo $i; ?>][meaning]" rows="1" placeholder="Meaning / Rule / Explanation" required></textarea>
                                        <div class="invalid-feedback">Required.</div>
                                    </div>
                                    <div> <!-- Removed mb-1 -->
                                        <label for="item_example_<?php echo $cat_id; ?>_<?php echo $i; ?>" class="form-label visually-hidden">Example</label>
                                        <textarea class="form-control form-control-sm" id="item_example_<?php echo $cat_id; ?>_<?php echo $i; ?>" name="items[<?php echo $cat_id; ?>][<?php echo $i; ?>][example]" rows="1" placeholder="Example Sentence" required></textarea>
                                        <div class="invalid-feedback">Required.</div>
                                    </div>
                                </div>
                            </div>
                        <?php endfor; ?>
                         <!-- Optional: Add Extra Item Button -->
                         <button type="button" class="btn btn-sm btn-outline-secondary add-extra-item mt-2" data-category-id="<?php echo $cat_id; ?>" data-next-index="<?php echo $items_per_category + 1; ?>">
                             <i class="fas fa-plus fa-xs me-1"></i> Add Extra
                         </button>
                    </div> <!-- /card-body -->
                </div> <!-- /card -->
            <?php endforeach; ?>

            <div class="text-center mt-4 mb-5">
                 <button type="submit" class="btn btn-primary btn-lg" <?php if ($practice_day_id === null) echo 'disabled'; /* Disable save if day missing */ ?>>
                     <i class="fas fa-save me-2"></i> Save Entries for <?php echo $selected_date->format('M d'); ?>
                 </button>
            </div>
        </form>
    <?php endif; ?>

</div><!-- /.container -->

<?php
// Include footer (should now include conditional JS link)
require_once __DIR__ . '/../../includes/footer.php';
?> 