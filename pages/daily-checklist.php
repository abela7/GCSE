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
                            
                            <div class="card my-4">
                                <div class="card-header bg-primary text-white">
                                    <h4 class="mb-0">Corporal Works of Mercy</h4>
                                </div>
                                <div class="card-body">
                                    <?php 
                                    // Display only the corporal works of mercy
                                    echo renderJudgmentChecklist([
                                        'feed_hungry',
                                        'give_drink',
                                        'welcome_strangers',
                                        'clothe_naked',
                                        'care_sick',
                                        'visit_prisoners'
                                    ]); 
                                    ?>
                                </div>
                            </div>
                            
                            <div class="card my-4">
                                <div class="card-header bg-success text-white">
                                    <h4 class="mb-0">Spiritual Practices</h4>
                                </div>
                                <div class="card-body">
                                    <?php 
                                    // Display only the spiritual works
                                    echo renderJudgmentChecklist([
                                        'prayer',
                                        'repentance',
                                        'forgiveness',
                                        'scripture',
                                        'fasting'
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