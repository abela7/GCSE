<?php
// Set page title
$page_title = "Daily Judgment Checklist";

// Set breadcrumbs
$breadcrumbs = [
    'Dashboard' => 'dashboard.php',
    'Daily Checklist' => null
];

// Include judgment checklist component
require_once '../includes/judgment_checklist.php';

// Include header
include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-lg-10 mx-auto">
            <div class="card feature-card">
                <div class="card-header bg-gradient" style="background: linear-gradient(to right, #2c3e50, #1a1a1a);">
                    <h3 class="mb-0 text-white"><i class="fas fa-cross me-2"></i>Daily Orthodox Judgment Checklist</h3>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="alert alert-info">
                                <p>
                                    <i class="fas fa-info-circle me-2"></i>
                                    "For I was hungry and you gave me food, I was thirsty and you gave me drink, I was a stranger and you welcomed me, I was naked and you clothed me, I was sick and you visited me, I was in prison and you came to me."
                                </p>
                                <p class="mb-0 text-end">— Matthew 25:35-36</p>
                            </div>
                            
                            <!-- Method 1: Rendering categorized checklist automatically -->
                            <div class="card mb-4">
                                <div class="card-header bg-dark text-white">
                                    <h4 class="mb-0">All Categories (Automatically Organized)</h4>
                                </div>
                                <div class="card-body">
                                    <?php echo renderCategorizedJudgmentChecklist(); ?>
                                </div>
                            </div>
                            
                            <!-- Method 2: Rendering specific categories using helper functions -->
                            <div class="card mb-4">
                                <div class="card-header bg-primary text-white">
                                    <h4 class="mb-0">Corporal Works of Mercy (Using Helper Function)</h4>
                                </div>
                                <div class="card-body">
                                    <?php echo renderJudgmentChecklist(getCorporalWorkItemIds()); ?>
                                </div>
                            </div>
                            
                            <div class="card mb-4">
                                <div class="card-header bg-success text-white">
                                    <h4 class="mb-0">Spiritual Works (Using Helper Function)</h4>
                                </div>
                                <div class="card-body">
                                    <?php echo renderJudgmentChecklist(
                                        array_merge(
                                            getSpiritualWorkItemIds(),
                                            getAdditionalSpiritualItemIds()
                                        )
                                    ); ?>
                                </div>
                            </div>
                            
                            <!-- Method 3: Rendering individual items -->
                            <div class="card mb-4">
                                <div class="card-header bg-danger text-white">
                                    <h4 class="mb-0">Individual Items (Hand-Picked)</h4>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h5>Most Essential</h5>
                                            <div class="checklist">
                                                <?php 
                                                echo renderSingleJudgmentItem('prayer');
                                                echo renderSingleJudgmentItem('repentance');
                                                echo renderSingleJudgmentItem('feed_hungry');
                                                ?>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <h5>Daily Practice</h5>
                                            <div class="checklist">
                                                <?php 
                                                echo renderSingleJudgmentItem('scripture');
                                                echo renderSingleJudgmentItem('forgiveness');
                                                echo renderSingleJudgmentItem('fasting');
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Method 4: Custom list using explicit IDs -->
                            <div class="card mb-4">
                                <div class="card-header bg-warning text-dark">
                                    <h4 class="mb-0">Custom Selection (Explicit List)</h4>
                                </div>
                                <div class="card-body">
                                    <?php 
                                    // Custom selection of items
                                    echo renderJudgmentChecklist([
                                        'feed_hungry',
                                        'give_drink',
                                        'forgiveness',
                                        'prayer'
                                    ]); 
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add checklist JavaScript -->
<script>
<?php echo getJudgmentChecklistScript(); ?>
</script>

<?php
include '../includes/footer.php';
?> 