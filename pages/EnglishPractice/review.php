<?php
// GCSE/pages/EnglishPractice/review.php
session_start();
require_once __DIR__ . '/../../includes/db_connect.php';
require_once __DIR__ . '/_functions.php';

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
$page_title = "Review English Practice"; // Default title

// Daily View Logic
$selected_date_str = date('Y-m-d'); // Default
if ($view_mode === 'day') {
    $selected_date_str = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
    try {
        $selected_date = new DateTimeImmutable($selected_date_str);
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

<div class="container mt-4 mb-5">

    <!-- Session Messages -->
    <?php if (!empty($_SESSION['success_ep'])): ?><div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($_SESSION['success_ep']); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php unset($_SESSION['success_ep']); endif; ?>
    <?php if (!empty($_SESSION['error_ep'])): ?><div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($_SESSION['error_ep']); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php unset($_SESSION['error_ep']); endif; ?>
    <?php if (!empty($_SESSION['warning_ep'])): ?><div class="alert alert-warning alert-dismissible fade show"><i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($_SESSION['warning_ep']); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php unset($_SESSION['warning_ep']); endif; ?>


    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
        <h1 class="h3 mb-0"><?php echo $page_title; ?></h1>
         <!-- View Mode Toggle -->
         <div class="btn-group">
            <a href="?view=day<?php echo ($view_mode==='day' ? '&date='.$formatted_date : ''); ?>" class="btn btn-sm <?php echo ($view_mode==='day' ? 'btn-primary active' : 'btn-outline-primary'); ?>">Day View</a>
            <a href="?view=week<?php echo ($view_mode==='week' ? '&week='.$selected_week_num : ''); ?>" class="btn btn-sm <?php echo ($view_mode==='week' ? 'btn-primary active' : 'btn-outline-primary'); ?>">Week View</a>
        </div>
    </div>

    <!-- Day View Content -->
    <?php if ($view_mode === 'day'): ?>
        <div class="date-nav d-flex align-items-center justify-content-center mb-4">
             <a href="?view=day&date=<?php echo $prev_date; ?>" class="btn btn-outline-secondary btn-sm me-3"><i class="fas fa-chevron-left"></i> Prev Day</a>
             <span class="text-muted">Reviewing items for this day</span>
             <a href="?view=day&date=<?php echo $next_date; ?>" class="btn btn-outline-secondary btn-sm ms-3">Next Day <i class="fas fa-chevron-right"></i></a>
        </div>

        <?php if ($practice_day_id === null && empty($_SESSION['error_ep'])): ?>
             <div class="alert alert-warning">Practice day record not found for this date.</div>
        <?php elseif (empty($items_for_display)): ?>
             <div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>No practice items found for <?php echo $display_date; ?>. <a href="daily_entry.php?date=<?php echo urlencode($formatted_date); ?>" class="alert-link">Add entries?</a></div>
        <?php else: ?>
            <div class="accordion review-accordion" id="practiceDayAccordion">
                 <?php foreach ($items_for_display as $category_id => $category): ?>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="heading-day-<?php echo $category_id; ?>">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-day-<?php echo $category_id; ?>" aria-expanded="false" aria-controls="collapse-day-<?php echo $category_id; ?>">
                                <?php echo $category['name']; ?>
                                <span class="badge bg-secondary rounded-pill ms-2"><?php echo count($category['items']); ?></span>
                            </button>
                        </h2>
                        <div id="collapse-day-<?php echo $category_id; ?>" class="accordion-collapse collapse" aria-labelledby="heading-day-<?php echo $category_id; ?>" data-bs-parent="#practiceDayAccordion">
                            <div class="accordion-body">
                                <ul class="list-unstyled mb-0">
                                    <?php foreach ($category['items'] as $item): ?>
                                        <li class="mb-3 pb-3 border-bottom">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <div class="item-title h6 mb-0"><?php echo htmlspecialchars($item['item_title']); ?></div>
                                                <button class="btn btn-sm <?php echo isset($favorites_lookup[$item['id']]) ? 'btn-warning' : 'btn-outline-warning'; ?> toggle-favorite" 
                                                        data-item-id="<?php echo $item['id']; ?>">
                                                    <i class="<?php echo isset($favorites_lookup[$item['id']]) ? 'fas' : 'far'; ?> fa-star"></i>
                                                </button>
                                            </div>
                                            <div class="item-meta mb-1">
                                                <strong>Meaning/Rule:</strong> 
                                                <?php echo nl2br(htmlspecialchars($item['item_meaning'])); ?>
                                            </div>
                                            <?php if (!empty($item['item_example'])): ?>
                                                <div class="item-meta">
                                                    <strong>Example:</strong> 
                                                    <em><?php echo nl2br(htmlspecialchars($item['item_example'])); ?></em>
                                                </div>
                                            <?php endif; ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                 <?php endforeach; ?>
             </div>
             <div class="text-center mt-4"> <a href="daily_entry.php?date=<?php echo urlencode($formatted_date); ?>" class="btn btn-outline-secondary me-2"><i class="fas fa-edit me-1"></i>Edit Today</a> <a href="practice.php?date=<?php echo urlencode($formatted_date); ?>" class="btn btn-primary"><i class="fas fa-bolt me-1"></i>Practice Today's Items</a></div>
        <?php endif; ?>

    <!-- Week View Content -->
    <?php elseif ($view_mode === 'week' && isset($items_for_display_by_date)): ?>
         <div class="date-nav d-flex align-items-center justify-content-center mb-4">
             <?php if ($prev_week): ?><a href="?view=week&week=<?php echo $prev_week; ?>" class="btn btn-outline-secondary btn-sm me-3"><i class="fas fa-chevron-left"></i> Prev Week</a><?php else: ?><span class="btn btn-outline-secondary btn-sm me-3 disabled"><i class="fas fa-chevron-left"></i> Prev Week</span><?php endif; ?>
             <span class="text-muted">Displaying Week <?php echo $selected_week_num; ?></span>
             <?php if ($next_week): ?><a href="?view=week&week=<?php echo $next_week; ?>" class="btn btn-outline-secondary btn-sm ms-3">Next Week <i class="fas fa-chevron-right"></i></a><?php else: ?><span class="btn btn-outline-secondary btn-sm ms-3 disabled">Next Week <i class="fas fa-chevron-right"></i></span><?php endif; ?>
        </div>

        <?php if (empty($items_for_display_by_date)): ?>
             <div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>No practice items found for Week <?php echo $selected_week_num; ?>.</div>
        <?php else: ?>
             <div class="accordion review-accordion" id="practiceWeekAccordion">
                 <?php foreach ($items_for_display_by_date as $date => $categories_on_day): // Loop through dates in the week ?>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="heading-week-<?php echo str_replace('-','',$date); // Valid ID ?>">
                             <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-week-<?php echo str_replace('-','',$date); ?>" aria-expanded="false" aria-controls="collapse-week-<?php echo str_replace('-','',$date); ?>">
                                 <?php echo format_practice_date($date); // Display formatted date ?>
                            </button>
                        </h2>
                        <div id="collapse-week-<?php echo str_replace('-','',$date); ?>" class="accordion-collapse collapse" aria-labelledby="heading-week-<?php echo str_replace('-','',$date); ?>" data-bs-parent="#practiceWeekAccordion">
                            <div class="accordion-body">
                                 <?php foreach ($categories_on_day as $category_id => $category): ?>
                                     <h6 class="mt-3 mb-2 fw-bold"><?php echo $category['name']; ?> <span class="badge bg-light text-dark fw-normal ms-1"><?php echo count($category['items']); ?></span></h6>
                                     <ul class="list-unstyled mb-3">
                                         <?php foreach ($category['items'] as $item): ?>
                                             <li class="mb-2 pb-2 border-bottom">
                                                 <div class="d-flex justify-content-between align-items-start mb-2">
                                                     <div class="item-title h6 mb-0"><?php echo htmlspecialchars($item['item_title']); ?></div>
                                                     <button class="btn btn-sm <?php echo isset($favorites_lookup[$item['id']]) ? 'btn-warning' : 'btn-outline-warning'; ?> toggle-favorite" 
                                                             data-item-id="<?php echo $item['id']; ?>">
                                                         <i class="<?php echo isset($favorites_lookup[$item['id']]) ? 'fas' : 'far'; ?> fa-star"></i>
                                                     </button>
                                                 </div>
                                                 <div class="item-meta mb-1">
                                                     <strong>Meaning/Rule:</strong> 
                                                     <?php echo nl2br(htmlspecialchars($item['item_meaning'])); ?>
                                                 </div>
                                                 <?php if (!empty($item['item_example'])): ?>
                                                     <div class="item-meta">
                                                         <strong>Example:</strong> 
                                                         <em><?php echo nl2br(htmlspecialchars($item['item_example'])); ?></em>
                                                     </div>
                                                 <?php endif; ?>
                                             </li>
                                         <?php endforeach; ?>
                                          <li style="border-bottom: none !important;"></li><!-- Prevent last item border -->
                                     </ul>
                                 <?php endforeach; ?>
                                 <a href="daily_entry.php?date=<?php echo urlencode($date); ?>" class="btn btn-sm btn-outline-secondary mt-2">Edit This Day</a>
                            </div>
                        </div>
                    </div>
                 <?php endforeach; ?>
            </div>
            <div class="text-center mt-4"> <a href="practice.php?date_filter=week&week_num=<?php echo $selected_week_num; ?>" class="btn btn-primary"><i class="fas fa-bolt me-1"></i>Practice This Week's Items</a></div>

        <?php endif; ?>

    <?php endif; // End view_mode check ?>


</div><!-- /.container -->

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>