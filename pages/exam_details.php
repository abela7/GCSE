<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set page title
$page_title = "Exam Details";

// Include database connection
require_once '../config/db_connect.php';

// Include auth check
require_once __DIR__ . '/../../includes/auth_check.php';

// Function to adjust color brightness
function adjustBrightness($hex, $steps) {
    // Remove # if present
    $hex = ltrim($hex, '#');
    
    // Convert to RGB
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    
    // Adjust each component
    $r = max(0, min(255, $r + $steps));
    $g = max(0, min(255, $g + $steps));
    $b = max(0, min(255, $b + $steps));
    
    // Convert back to hex
    return sprintf("#%02x%02x%02x", $r, $g, $b);
}

// Get exam ID from URL
$exam_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get exam details
$exam_query = "SELECT e.*, s.name as subject_name, s.color as subject_color 
               FROM exams e 
               JOIN subjects s ON e.subject_id = s.id 
               WHERE e.id = $exam_id";

$exam_result = $conn->query($exam_query);

// Set breadcrumbs
$breadcrumbs = [
    'Subjects' => '/GCSE/pages/subjects.php'
];

if ($exam_result && $exam_result->num_rows > 0) {
    $exam = $exam_result->fetch_assoc();
    $page_title = $exam['title'];
    $breadcrumbs[$exam['subject_name']] = '/GCSE/pages/subjects/' . strtolower($exam['subject_name']) . '.php';
    $breadcrumbs['Exams'] = '/GCSE/pages/exams.php';
    $breadcrumbs[$exam['title']] = null;
}

// Include header
include_once __DIR__ . '/../../includes/header.php';

// Set timezone to London
date_default_timezone_set('Europe/London');

$exam_date = new DateTime($exam['exam_date']);
$now = new DateTime();

$interval = $now->diff($exam_date);

// Calculate total hours and minutes
$total_days = $interval->days;
$weeks = floor($total_days / 7);
$remaining_days = $total_days % 7;
$hours = $interval->h;
$minutes = $interval->i;
$seconds = $interval->s;

// Calculate total hours for display
$total_hours = ($total_days * 24) + $hours;

// Calculate progress percentage (assuming 90 days total study period)
$progress = 100 - (($total_days / 90) * 100);

// Override the subject color for Mathematics to use an elegant gold gradient
if (strtolower($exam['subject_name']) === 'math') {
    $exam['subject_color'] = '#FFD700'; // Bright gold as base color
}
?>

<div class="exam-page">
    <!-- Modern Exam Header -->
    <div class="exam-header" style="background: <?php echo strtolower($exam['subject_name']) === 'math' ? 'linear-gradient(135deg, #FFD700, #DAA520, #B8860B)' : 'linear-gradient(135deg, ' . $exam['subject_color'] . ', ' . adjustBrightness($exam['subject_color'], 40) . ')'; ?>">
        <div class="header-content">
            <div class="exam-meta">
                <div class="meta-badges">
                    <span class="meta-badge paper-code">
                        <?php echo htmlspecialchars($exam['paper_code']); ?>
                    </span>
                    <span class="meta-badge exam-board">
                        <?php echo htmlspecialchars($exam['exam_board']); ?>
                        </span>
                </div>
                <a href="exam_countdown.php" class="countdown-link">
                    <i class="fas fa-clock"></i> View All Countdowns
                </a>
            </div>
            
            <h1 class="exam-title"><?php echo htmlspecialchars($exam['title']); ?></h1>
            
            <div class="countdown-display">
                <div class="countdown-grid">
                    <div class="countdown-item">
                        <span class="count-number"><?php echo $total_days; ?></span>
                        <span class="count-label">days</span>
                    </div>
                </div>
                <div class="exam-datetime">
                    <?php echo $exam_date->format('F j, Y'); ?> at <?php echo $exam_date->format('g:i A'); ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="stats-section">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-star"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $exam['total_marks']; ?></h3>
                    <p>Total Marks</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo floor($exam['duration']/60); ?>h <?php echo $exam['duration']%60; ?>m</h3>
                    <p>Duration</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-map-marker-alt"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo htmlspecialchars($exam['location']); ?></h3>
                    <p>Location</p>
            </div>
        </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-percentage"></i>
                </div>
                <div class="stat-content">
                    <h3>50%</h3>
                    <p>of Final Grade</p>
                </div>
            </div>
        </div>
                </div>

    <!-- Navigation Tabs -->
    <div class="nav-container">
        <ul class="nav nav-tabs nav-fill" id="examTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" type="button" role="tab">
                    <i class="fas fa-info-circle"></i> Overview
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="sections-tab" data-bs-toggle="tab" data-bs-target="#sections" type="button" role="tab">
                    <i class="fas fa-list-alt"></i> Sections
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="preparation-tab" data-bs-toggle="tab" data-bs-target="#preparation" type="button" role="tab">
                    <i class="fas fa-tasks"></i> Preparation
                </button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="examTabsContent">
            <!-- Overview Tab -->
            <div class="tab-pane fade show active" id="overview" role="tabpanel">
                <div class="content-grid">
                    <div class="main-content">
                        <div class="content-card">
                            <h2><i class="fas fa-clipboard-list"></i> Exam Structure</h2>
                            <div class="timeline">
                    <?php if ($exam['section_a_topics']): ?>
                                <div class="timeline-item">
                                    <div class="timeline-badge">A</div>
                                    <div class="timeline-content">
                                        <h3>Section A</h3>
                                        <?php echo nl2br(htmlspecialchars($exam['section_a_topics'])); ?>
                                    </div>
                                </div>
                    <?php endif; ?>

                    <?php if ($exam['section_b_topics']): ?>
                                <div class="timeline-item">
                                    <div class="timeline-badge">B</div>
                                    <div class="timeline-content">
                                        <h3>Section B</h3>
                                        <?php echo nl2br(htmlspecialchars($exam['section_b_topics'])); ?>
                                    </div>
                                </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

                    <div class="sidebar">
                        <div class="content-card">
                            <h2><i class="fas fa-exclamation-circle"></i> Key Information</h2>
                            <div class="key-info-list">
                                <div class="key-info-item">
                                    <i class="fas fa-calculator"></i>
                                    <span>Calculator:</span>
                                    <span class="badge <?php echo $exam['calculator_allowed'] ? 'badge-success' : 'badge-danger'; ?>">
                                        <?php echo $exam['calculator_allowed'] ? 'Allowed' : 'Not Allowed'; ?>
                                    </span>
                                </div>
                                <div class="key-info-item">
                                    <i class="fas fa-file-alt"></i>
                                    <span>Formula Sheet:</span>
                                    <span class="badge <?php echo $exam['formula_sheet_provided'] ? 'badge-success' : 'badge-danger'; ?>">
                                        <?php echo $exam['formula_sheet_provided'] ? 'Provided' : 'Not Provided'; ?>
                                    </span>
                                </div>
                </div>

                            <h3>Required Equipment</h3>
                            <ul class="equipment-list">
                                <?php 
                                $equipment = explode("\n", $exam['equipment_needed']);
                                foreach ($equipment as $item):
                                    if (trim($item)):
                                ?>
                                    <li>
                                        <i class="fas fa-check"></i>
                                        <?php echo htmlspecialchars(trim($item)); ?>
                                    </li>
                        <?php 
                                    endif;
                                endforeach; 
                        ?>
                    </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sections Tab -->
            <div class="tab-pane fade" id="sections" role="tabpanel">
                <div class="content-grid">
                    <div class="main-content">
                        <?php if ($exam['section_a_topics']): ?>
                        <div class="section-card">
                            <div class="section-header">
                                <div class="section-badge">A</div>
                                <h2>Section A</h2>
                                <button class="study-btn">
                                    <i class="fas fa-book"></i>
                                    Study Now
                                </button>
                            </div>
                            <div class="section-content">
                                <div class="topic-list">
                                    <?php 
                                    $topics = explode("\n", $exam['section_a_topics']);
                                    foreach ($topics as $topic):
                                        if (trim($topic)):
                                    ?>
                                        <div class="topic-item">
                                            <i class="fas fa-check-circle"></i>
                                            <span><?php echo htmlspecialchars(trim($topic)); ?></span>
                                        </div>
                                    <?php 
                                        endif;
                                    endforeach; 
                                    ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if ($exam['section_b_topics']): ?>
                        <div class="section-card">
                            <div class="section-header">
                                <div class="section-badge">B</div>
                                <h2>Section B</h2>
                                <button class="study-btn">
                                    <i class="fas fa-book"></i>
                                    Study Now
                                </button>
                            </div>
                            <div class="section-content">
                                <div class="topic-list">
                                    <?php 
                                    $topics = explode("\n", $exam['section_b_topics']);
                                    foreach ($topics as $topic):
                                        if (trim($topic)):
                                    ?>
                                        <div class="topic-item">
                                            <i class="fas fa-check-circle"></i>
                                            <span><?php echo htmlspecialchars(trim($topic)); ?></span>
                                        </div>
                                    <?php 
                                        endif;
                                    endforeach; 
                                    ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
        </div>

                    <div class="sidebar">
                        <div class="content-card">
                            <h2><i class="fas fa-graduation-cap"></i> Study Resources</h2>
                            <div class="resource-list">
                                <?php if ($exam['revision_resources']): ?>
                        <?php 
                        $resources = explode("\n", $exam['revision_resources']);
                        foreach ($resources as $resource):
                                        if (trim($resource)):
                                    ?>
                                        <div class="resource-item">
                                            <i class="fas fa-file-alt"></i>
                                            <span><?php echo htmlspecialchars(trim($resource)); ?></span>
                                            <button class="resource-btn">
                                                <i class="fas fa-external-link-alt"></i>
                                            </button>
                                        </div>
                                    <?php 
                                        endif;
                                    endforeach; 
                                    ?>
                                <?php endif; ?>
                            </div>
                            <a href="<?php echo htmlspecialchars($exam['syllabus_link']); ?>" class="syllabus-btn" target="_blank">
                                <i class="fas fa-download"></i>
                                Download Syllabus
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Preparation Tab -->
            <div class="tab-pane fade" id="preparation" role="tabpanel">
                <div class="content-grid">
                    <div class="content-card">
                        <h2><i class="fas fa-lightbulb"></i> Exam Tips</h2>
                        <div class="tips-content">
                            <?php echo nl2br(htmlspecialchars($exam['exam_tips'])); ?>
                        </div>
                    </div>
                    <div class="content-card">
                        <h2><i class="fas fa-info-circle"></i> Special Instructions</h2>
                        <div class="instructions-content">
                            <?php echo nl2br(htmlspecialchars($exam['special_instructions'])); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>

<style>
/* Modern Design System */
:root {
    --cream: #F1ECE2;
    --purple: #4D4052;
    --deep-purple: #301934;
    --gold: #DAA520;
    --muted-gold: #CDAF56;
    --deep-brown: #5D4225;
    --pure-black: #000000;
    --off-white: #FFFFFF;
    
    /* Functional color assignments */
    --bg-primary: var(--cream);
    --bg-secondary: var(--off-white);
    --text-primary: var(--pure-black);
    --text-secondary: var(--deep-purple);
    --accent-primary: var(--gold);
    --accent-secondary: var(--muted-gold);
    --border-color: rgba(93, 66, 37, 0.15);
    
    --shadow-sm: 0 2px 4px rgba(93, 66, 37, 0.1);
    --shadow-md: 0 4px 8px rgba(93, 66, 37, 0.15);
    --radius-sm: 0.5rem;
    --radius-md: 0.75rem;
    --spacing-unit: 1rem;
}

/* Page Layout */
.exam-page {
    background: var(--bg-primary);
    min-height: 100vh;
    padding: 0;
    margin: 0;
    color: var(--text-primary);
}

/* Header Styles */
.exam-header {
    background: linear-gradient(135deg, var(--purple), var(--deep-purple)) !important;
    padding: 2rem 1.5rem;
    color: var(--off-white);
    margin-bottom: calc(var(--spacing-unit) * 3);
}

.header-content {
    max-width: 100%;
    margin: 0;
    padding: 0;
}

.exam-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.meta-badges {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.meta-badge {
    background: var(--off-white);
    color: var(--pure-black);
    padding: 0.5rem 1rem;
    border-radius: var(--radius-sm);
    font-weight: 600;
    font-size: 0.875rem;
    box-shadow: var(--shadow-sm);
}

.exam-title {
    color: var(--off-white);
    font-size: clamp(1.5rem, 5vw, 2rem);
    font-weight: 700;
    margin-bottom: 1rem;
    line-height: 1.2;
}

/* Countdown Display */
.countdown-display {
    background: rgba(241, 236, 226, 0.15);
    backdrop-filter: blur(8px);
    padding: 1.5rem;
    border-radius: var(--radius-md);
    text-align: center;
    border: 1px solid rgba(241, 236, 226, 0.2);
    color: var(--off-white);
}

.count-number {
    color: var(--off-white);
    font-size: clamp(3rem, 8vw, 4.5rem);
    font-weight: 800;
    line-height: 1;
    display: block;
    margin-bottom: 0.25rem;
}

.count-label {
    color: var(--off-white);
    font-size: clamp(1rem, 3vw, 1.2rem);
    text-transform: uppercase;
    letter-spacing: 0.1em;
    font-weight: 600;
}

/* Stats Section */
.stats-section {
    margin: calc(var(--spacing-unit) * -2) var(--spacing-unit) var(--spacing-unit);
    position: relative;
    z-index: 2;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
    padding: 1.25rem;
    border-radius: var(--radius-md);
    box-shadow: var(--shadow-md);
    display: flex;
    align-items: center;
    gap: 1rem;
}

.stat-icon {
    background: var(--accent-primary);
    color: var(--off-white);
    width: 35px;
    height: 35px;
    border-radius: var(--radius-sm);
    display: flex;
    align-items: center;
    justify-content: center;
}

.stat-content h3 {
    color: var(--text-primary);
    font-size: 1.25rem;
    font-weight: 700;
    margin: 0;
}

.stat-content p {
    color: var(--text-secondary);
    font-size: 0.875rem;
    margin: 0.25rem 0 0;
}

/* Navigation */
.nav-container {
    padding: 0 var(--spacing-unit);
    margin-bottom: var(--spacing-unit);
}

.nav-tabs {
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-md);
    padding: 0.5rem;
    box-shadow: var(--shadow-sm);
    display: flex;
    gap: 0.5rem;
    margin-bottom: 2rem;
}

.nav-tabs .nav-link {
    color: var(--text-primary);
    padding: 0.75rem 1.5rem;
    border-radius: var(--radius-sm);
    font-weight: 500;
    transition: all 0.2s ease;
}

.nav-tabs .nav-link.active {
    background: var(--accent-primary);
    color: var(--pure-black);
    font-weight: 600;
}

/* Content Layout */
.content-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 2rem;
    padding: 0 var(--spacing-unit);
    max-width: 1400px;
    margin: 0 auto;
}

.main-content {
    background: var(--bg-secondary);
    border-radius: var(--radius-md);
    box-shadow: var(--shadow-md);
    padding: 2rem;
}

.sidebar {
    background: var(--bg-secondary);
    border-radius: var(--radius-md);
    box-shadow: var(--shadow-md);
    padding: 2rem;
    height: fit-content;
}

/* Section Headers */
.content-card h2 {
    color: var(--text-primary);
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid var(--accent-secondary);
}

/* Timeline */
.timeline-item {
    margin-bottom: 2rem;
    padding-left: 3rem;
    position: relative;
}

.timeline-badge {
    position: absolute;
    left: 0;
    top: 0;
    width: 2.5rem;
    height: 2.5rem;
    background: var(--accent-primary);
    color: var(--pure-black);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 1.25rem;
}

.timeline-content {
    background: var(--bg-primary);
    padding: 1.5rem;
    border-radius: var(--radius-md);
    border: 1px solid var(--border-color);
}

.timeline-content h3 {
    color: var(--text-primary);
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 1rem;
}

/* Equipment List */
.equipment-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.equipment-list li {
    background: var(--bg-primary);
    padding: 1rem;
    margin-bottom: 0.75rem;
    border-radius: var(--radius-sm);
    display: flex;
    align-items: center;
    gap: 0.75rem;
    color: var(--text-primary);
    border: 1px solid var(--border-color);
}

.equipment-list li i {
    color: var(--accent-primary);
    font-size: 1.1rem;
}

/* Key Information */
.key-info-list {
    display: grid;
    gap: 1rem;
    margin-bottom: 2rem;
}

.key-info-item {
    background: var(--bg-primary);
    padding: 1rem;
    border-radius: var(--radius-sm);
    display: flex;
    align-items: center;
    gap: 1rem;
    color: var(--text-primary);
    border: 1px solid var(--border-color);
}

.key-info-item i {
    color: var(--accent-primary);
    font-size: 1.25rem;
}

/* Badges */
.badge {
    padding: 0.5rem 1rem;
    border-radius: var(--radius-sm);
    font-weight: 600;
    font-size: 0.875rem;
}

.badge-success {
    background: var(--accent-primary);
    color: var(--pure-black);
}

.badge-danger {
    background: var(--deep-purple);
    color: var(--off-white);
}

@media (min-width: 1024px) {
    .content-grid {
        grid-template-columns: 2fr 1fr;
    }
    
    .sidebar {
        position: sticky;
        top: var(--spacing-unit);
    }
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .main-content,
    .sidebar {
        padding: 1.5rem;
    }
    
    .timeline-item {
        padding-left: 2.5rem;
    }
    
    .timeline-badge {
        width: 2rem;
        height: 2rem;
        font-size: 1rem;
    }
}

@media (max-width: 480px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .nav-tabs {
        flex-wrap: wrap;
    }
    
    .nav-tabs .nav-link {
        flex: 1 1 auto;
        text-align: center;
        padding: 0.5rem;
    }
}

/* Modern Buttons */
.study-btn, .resource-btn, .syllabus-btn, .countdown-link {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.25rem;
    border-radius: var(--radius-sm);
    font-weight: 600;
    font-size: 0.875rem;
    transition: all 0.2s ease;
    border: none;
    cursor: pointer;
}

.study-btn {
    background: var(--accent-primary);
    color: var(--pure-black);
}

.study-btn:hover {
    background: var(--accent-secondary);
    transform: translateY(-1px);
}

.resource-btn {
    background: transparent;
    color: var(--accent-primary);
    padding: 0.5rem;
    border: 1px solid var(--border-color);
}

.resource-btn:hover {
    background: var(--bg-primary);
    color: var(--accent-secondary);
}

.syllabus-btn {
    background: var(--deep-purple);
    color: var(--off-white);
    width: 100%;
    justify-content: center;
    margin-top: 1rem;
}

.syllabus-btn:hover {
    background: var(--purple);
    transform: translateY(-1px);
}

.countdown-link {
    background: rgba(241, 236, 226, 0.15);
    color: var(--off-white);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(241, 236, 226, 0.2);
    text-decoration: none;
}

.countdown-link:hover {
    background: rgba(241, 236, 226, 0.25);
    color: var(--off-white);
}

/* Section Cards */
.section-card {
    background: var(--bg-secondary);
    border-radius: var(--radius-md);
    border: 1px solid var(--border-color);
    margin-bottom: 2rem;
    overflow: hidden;
}

.section-card .section-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.5rem;
    background: var(--bg-primary);
    border-bottom: 1px solid var(--border-color);
}

.section-card .section-header h2 {
    margin: 0;
    padding: 0;
    border: none;
    flex-grow: 1;
}

.section-badge {
    width: 2.5rem;
    height: 2.5rem;
    background: var(--accent-primary);
    color: var(--pure-black);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 1.25rem;
}

.section-content {
    padding: 1.5rem;
}

/* Topic List */
.topic-list {
    display: grid;
    gap: 1rem;
}

.topic-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: var(--bg-primary);
    border-radius: var(--radius-sm);
    border: 1px solid var(--border-color);
}

.topic-item i {
    color: var(--accent-primary);
    font-size: 1.1rem;
}

/* Resource List */
.resource-list {
    display: grid;
    gap: 1rem;
    margin-bottom: 1rem;
}

.resource-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: var(--bg-primary);
    border-radius: var(--radius-sm);
    border: 1px solid var(--border-color);
}

.resource-item span {
    flex-grow: 1;
}

.resource-item i:first-child {
    color: var(--accent-primary);
}
</style>

<?php
// Include footer
include '../includes/footer.php';
?>