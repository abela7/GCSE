<?php
// Set breadcrumbs
$breadcrumbs = [
    'Home' => '/',
    'Subjects' => '/pages/subjects.php',
    'Mathematics' => null
];

// Set page actions
$page_actions = '
<a href="/pages/resources.php?subject=2" class="btn btn-outline-primary btn-sm">
    <i class="fas fa-book me-1"></i> Resources
</a>
<a href="/pages/exams.php?subject=2" class="btn btn-outline-primary btn-sm ms-2">
    <i class="fas fa-file-alt me-1"></i> Exams
</a>
';

// Include database connection
require_once '../../config/db_connect.php';

// Fetch math sections with their progress
$sections_query = "
    SELECT 
        ms.*,
        COUNT(DISTINCT mt.id) as total_topics,
        COALESCE(SUM(CASE WHEN tp.status = 'completed' THEN 1 ELSE 0 END), 0) as completed_topics,
        COALESCE(ROUND(SUM(CASE WHEN tp.status = 'completed' THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(DISTINCT mt.id), 0), 1), 0) as progress_percentage,
        COALESCE(
            SUM(COALESCE(tp.total_time_spent, 0)) + 
            COALESCE((
                SELECT SUM(accumulated_seconds)
                FROM study_time_tracking stt
                INNER JOIN math_topics mt2 ON stt.topic_id = mt2.id
                INNER JOIN math_subsections msub2 ON mt2.subsection_id = msub2.id
                WHERE msub2.section_id = ms.id
                AND stt.status IN ('active', 'paused')
            ), 0)
        , 0) as total_study_time
    FROM math_sections ms
    LEFT JOIN math_subsections msub ON ms.id = msub.section_id
    LEFT JOIN math_topics mt ON msub.id = mt.subsection_id
    LEFT JOIN topic_progress tp ON mt.id = tp.topic_id
    GROUP BY ms.id
    ORDER BY ms.section_number;
";

$sections_result = $conn->query($sections_query);
$sections = [];

if ($sections_result) {
    $sections = $sections_result->fetch_all(MYSQLI_ASSOC);
} else {
    die("Error fetching sections: " . $conn->error);
}

// Get overall progress with accurate calculations
$progress_query = "
    SELECT 
        COUNT(DISTINCT mt.id) as total_topics,
        COUNT(DISTINCT CASE WHEN tp.status = 'completed' THEN mt.id END) as completed_topics,
        COALESCE(AVG(CASE WHEN tp.confidence_level > 0 THEN tp.confidence_level END), 0) as avg_confidence,
        COALESCE(
            SUM(COALESCE(tp.total_time_spent, 0)) + 
            COALESCE((
                SELECT SUM(accumulated_seconds)
                FROM study_time_tracking stt
                WHERE stt.topic_id = mt.id
                AND stt.status IN ('active', 'paused')
            ), 0)
        , 0) as total_study_time
    FROM math_topics mt
    LEFT JOIN topic_progress tp ON mt.id = tp.topic_id;
";

$progress_result = $conn->query($progress_query);
$progress = $progress_result ? $progress_result->fetch_assoc() : null;

// Calculate overall progress
$overall_progress = 0;
$avg_confidence = 0;
$total_study_time = 0;

if ($progress) {
    $overall_progress = $progress['total_topics'] > 0 ? 
        round(($progress['completed_topics'] / $progress['total_topics']) * 100, 1) : 0;
    $avg_confidence = round($progress['avg_confidence'], 1);
    $total_study_time = $progress['total_study_time'];
    
    // Convert total study time to hours and minutes for display
    $hours = floor($total_study_time / 3600);
    $minutes = floor(($total_study_time % 3600) / 60);
}

// Include header
include '../../includes/header.php';
?>

<!-- Alert Container for JavaScript notifications -->
<div id="alert-container" class="position-fixed top-0 end-0 p-3" style="z-index: 1050;"></div>

<div class="subject-page math-page">
    <!-- Hero Section -->
    <div class="hero-section py-5 mb-4">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h1 class="display-4 fw-bold text-white mb-3">Mathematics</h1>
                    <p class="lead text-white mb-0">Master key mathematical concepts and prepare for your GCSE exams</p>
                </div>
                <div class="col-lg-4">
                    <div class="stats-card bg-white p-4 rounded-4 shadow-sm">
                        <div class="stat-item mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="stat-label">Overall Progress</span>
                                <span class="stat-value"><?php echo number_format($overall_progress, 1); ?>%</span>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-success" role="progressbar" 
                                     style="width: <?php echo $overall_progress; ?>%">
                                </div>
                            </div>
                        </div>
                        <div class="stat-item mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="stat-label">Average Confidence</span>
                                <span class="stat-value">
                                    <?php 
                                    for ($i = 1; $i <= 5; $i++) {
                                        echo '<i class="fas fa-star' . ($i <= $avg_confidence ? ' text-warning' : ' text-muted') . '"></i>';
                                    }
                                    ?>
                                </span>
                            </div>
                        </div>
                        <div class="stat-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="stat-label">Total Study Time</span>
                                <span class="stat-value">
                                    <?php 
                                    if ($hours > 0) {
                                        echo $hours . 'h ' . $minutes . 'm';
                                    } else {
                                        echo $minutes . 'm';
                                    }
                                    ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="container">
        <!-- Search Bar -->
        <div class="row mb-4">
            <div class="col-md-6 mx-auto">
                <div class="search-box">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="searchInput" class="form-control form-control-lg search-input" 
                           placeholder="Search sections...">
                </div>
            </div>
        </div>

        <!-- Sections Grid -->
        <div class="row g-4" id="sectionsGrid">
            <?php foreach ($sections as $section): ?>
            <div class="col-md-6 col-lg-4 section-item">
                <div class="section-card card h-100" 
                     data-section-id="<?php echo $section['id']; ?>"
                     data-section-name="<?php echo htmlspecialchars($section['name']); ?>"
                     data-section-description="<?php echo htmlspecialchars($section['description']); ?>">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($section['name']); ?></h5>
                        <span class="badge"><?php echo $section['section_number']; ?></span>
                        <p class="card-text"><?php echo htmlspecialchars($section['description']); ?></p>
                        <div class="progress">
                            <div class="progress-bar" role="progressbar" 
                                 style="width: <?php echo $section['progress_percentage']; ?>%">
                            </div>
                        </div>
                        <div class="progress-info">
                            <span class="completed"><?php echo $section['completed_topics']; ?>/<?php echo $section['total_topics']; ?> topics</span>
                            <span class="percentage"><?php echo number_format($section['progress_percentage'], 1); ?>% complete</span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
    <!-- Section Modal -->
    <div class="modal fade" id="sectionModal" tabindex="-1" aria-labelledby="sectionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="sectionModalLabel"></h5>
                        <p class="section-description text-muted mb-0 mt-1"></p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="section-progress mb-4">
                        <div class="progress mb-2" style="height: 8px;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: 0%"></div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center text-muted small">
                            <span class="topics-progress">0/0 topics</span>
                            <span class="overall-progress">0% complete</span>
                        </div>
                    </div>
                    <div class="subsections-container">
                        <!-- Subsections will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Search Results Container -->
    <div class="search-results collapse" id="searchResults">
        <div class="container mb-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">Search Results</h6>
                    <div class="search-sections mb-3">
                        <h6 class="small text-muted mb-2">Sections</h6>
                        <div class="section-matches"></div>
                    </div>
                    <div class="search-subsections mb-3">
                        <h6 class="small text-muted mb-2">Subsections</h6>
                        <div class="subsection-matches"></div>
                    </div>
                    <div class="search-topics">
                        <h6 class="small text-muted mb-2">Topics</h6>
                        <div class="topic-matches"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.hero-section {
    background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%);
    color: white;
    border-radius: 0 0 2rem 2rem;
    box-shadow: 0 4px 20px rgba(37, 99, 235, 0.2);
}

.stats-card {
    border-radius: 1.25rem;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.stat-label {
    color: #64748b;
    font-size: 0.875rem;
    font-weight: 500;
}

.stat-value {
    font-weight: 600;
    font-size: 1.125rem;
    color: #1e293b;
}

.search-box {
    position: relative;
    max-width: 600px;
    margin: 0 auto;
}

.search-icon {
    position: absolute;
    left: 20px;
    top: 50%;
    transform: translateY(-50%);
    color: #64748b;
}

.search-input {
    padding: 1rem 1rem 1rem 3rem;
    border-radius: 1rem;
    border: 2px solid #e2e8f0;
    font-size: 1rem;
    transition: all 0.3s ease;
    background: #f8fafc;
}

.search-input:focus {
    border-color: #3b82f6;
    background: #ffffff;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.1);
}

.section-card {
    transition: all 0.3s ease;
    cursor: pointer;
    border: 1px solid rgba(226, 232, 240, 0.8);
    border-radius: 1.25rem;
    background: #ffffff;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
    position: relative;
    overflow: hidden;
}

.section-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
    border-color: #3b82f6;
}

.section-card .card-body {
    padding: 1.75rem;
}

.section-card .card-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: #1e293b;
    margin-right: 3rem;
}

.section-card .badge {
    position: absolute;
    top: 1.75rem;
    right: 1.75rem;
    padding: 0.5rem 1rem;
    font-weight: 600;
    font-size: 1rem;
    border-radius: 0.75rem;
    background: #e0e7ff;
    color: #4338ca;
    min-width: 2.5rem;
    text-align: center;
    box-shadow: 0 2px 8px rgba(67, 56, 202, 0.15);
}

.section-card .card-text {
    color: #64748b;
    font-size: 0.95rem;
    margin-bottom: 1.5rem;
    line-height: 1.5;
}

.progress {
    background-color: #f1f5f9;
    border-radius: 1rem;
    overflow: hidden;
    height: 0.5rem;
    margin-bottom: 0.75rem;
}

.progress-bar {
    border-radius: 1rem;
    background: linear-gradient(90deg, #22c55e 0%, #16a34a 100%);
    transition: width 0.5s ease;
}

.section-card .text-muted {
    color: #64748b !important;
    font-weight: 500;
}

.section-card .small {
    font-size: 0.875rem;
}

/* Add a subtle hover effect for the progress bar */
.section-card:hover .progress-bar {
    background: linear-gradient(90deg, #2563eb 0%, #3b82f6 100%);
}

/* Add a subtle border effect on hover */
.section-card::after {
    content: '';
    position: absolute;
    inset: 0;
    border-radius: 1.25rem;
    padding: 2px;
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    -webkit-mask: 
        linear-gradient(#fff 0 0) content-box, 
        linear-gradient(#fff 0 0);
    -webkit-mask-composite: xor;
    mask-composite: exclude;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.section-card:hover::after {
    opacity: 1;
}

/* Improve completed/total topics display */
.section-card .progress-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 0.5rem;
}

.section-card .progress-info span {
    padding: 0.25rem 0.75rem;
    border-radius: 0.5rem;
    background: #f8fafc;
    font-weight: 500;
}

.section-card .progress-info span.completed {
    color: #16a34a;
}

.section-card .progress-info span.percentage {
    color: #3b82f6;
}

/* Modal Styles */
#sectionModal .modal-content {
    border: none;
    border-radius: 1.5rem;
    box-shadow: 0 12px 32px rgba(0, 0, 0, 0.1);
    background: #ffffff;
}

#sectionModal .modal-header {
    padding: 1.75rem;
    border-bottom: 1px solid #e2e8f0;
    background: #f8fafc;
    border-radius: 1.5rem 1.5rem 0 0;
}

#sectionModal .modal-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: #1e293b;
}

#sectionModal .section-description {
    color: #64748b;
    font-size: 1rem;
    margin: 0.5rem 0 0;
}

#sectionModal .modal-body {
    padding: 1.75rem;
}

#sectionModal .section-progress {
    background: #f8fafc;
    padding: 1.25rem;
    border-radius: 1rem;
    margin-bottom: 2rem;
}

#sectionModal .subsection-item .card {
    border: 1px solid #e2e8f0;
    border-radius: 1rem;
    transition: all 0.3s ease;
    margin-bottom: 1rem;
}

#sectionModal .subsection-item .card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
}

#sectionModal .subsection-item .card-body {
    padding: 1.25rem;
}

#sectionModal .subsection-item .badge {
    background: #f1f5f9;
    color: #475569;
    font-weight: 500;
    padding: 0.5rem 1rem;
    border-radius: 0.75rem;
}

#sectionModal .btn-outline-primary {
    border-radius: 0.75rem;
    padding: 0.5rem 1rem;
    font-weight: 500;
    border-color: #3b82f6;
    color: #3b82f6;
    transition: all 0.3s ease;
}

#sectionModal .btn-outline-primary:hover {
    background: #3b82f6;
    color: #ffffff;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);
}

/* Search Results Styles */
.search-results {
    background: #f8fafc;
    padding: 1.5rem 0;
    margin-bottom: 2rem;
    border-radius: 1rem;
}

.search-results .card {
    border: none;
    border-radius: 1rem;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
}

.search-results .match-item {
    padding: 0.75rem;
    border-radius: 0.75rem;
    transition: all 0.2s ease;
    margin-bottom: 0.5rem;
}

.search-results .match-item:hover {
    background-color: #f1f5f9;
}

.search-results mark {
    background-color: #fef3c7;
    color: #92400e;
    padding: 0.2em 0.4em;
    border-radius: 0.25rem;
}

.section-item {
    opacity: 1;
    transition: opacity 0.3s ease;
}

.section-item.hidden {
    display: none;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Bootstrap Modal
    const sectionModal = new bootstrap.Modal(document.getElementById('sectionModal'));
    
    // Search functionality
    const searchInput = document.getElementById('searchInput');
    const searchResults = document.getElementById('searchResults');
    const sectionsGrid = document.getElementById('sectionsGrid');
    let searchTimeout;

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const searchTerm = this.value.toLowerCase();
            
            searchTimeout = setTimeout(() => {
                const sectionCards = document.querySelectorAll('.section-item');
                
                sectionCards.forEach(card => {
                    const title = card.querySelector('.card-title').textContent.toLowerCase();
                    const description = card.querySelector('.card-text').textContent.toLowerCase();
                    
                    if (title.includes(searchTerm) || description.includes(searchTerm)) {
                        card.style.display = '';
                    } else {
                        card.style.display = 'none';
                    }
                });
            }, 300);
        });
    }

    // Section card click handlers
    document.querySelectorAll('.section-card').forEach(card => {
        card.addEventListener('click', function() {
            const sectionId = this.dataset.sectionId;
            const sectionName = this.dataset.sectionName;
            const sectionDescription = this.dataset.sectionDescription;
            
            // Get the modal elements
            const modalTitle = document.querySelector('#sectionModal .modal-title');
            const modalDescription = document.querySelector('#sectionModal .section-description');
            const subsectionsContainer = document.querySelector('#sectionModal .subsections-container');
            
            // Update modal content
            modalTitle.textContent = sectionName;
            modalDescription.textContent = sectionDescription;
            
            // Show loading state
            subsectionsContainer.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';
            
            // Show the modal
            sectionModal.show();
            
            // Load subsections
            fetch(`/api/math/subsections.php?section_id=${sectionId}`)
                .then(response => response.json())
                .then(data => {
                    // Clear loading state
                    subsectionsContainer.innerHTML = '';
                    
                    // Update progress information
                    const progressBar = document.querySelector('#sectionModal .progress-bar');
                    const topicsProgress = document.querySelector('#sectionModal .topics-progress');
                    const overallProgress = document.querySelector('#sectionModal .overall-progress');
                    
                    if (data.section_progress) {
                        progressBar.style.width = `${data.section_progress.progress_percentage}%`;
                        topicsProgress.textContent = `${data.section_progress.completed_topics}/${data.section_progress.total_topics} topics`;
                        overallProgress.textContent = `${data.section_progress.progress_percentage}% complete`;
                    }
                    
                    // Add subsections
                    if (data.subsections && data.subsections.length > 0) {
                        data.subsections.forEach(subsection => {
                            const subsectionElement = document.createElement('div');
                            subsectionElement.className = 'subsection-item mb-3';
                            subsectionElement.innerHTML = `
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h6 class="card-title mb-0">${subsection.name}</h6>
                                            <span class="badge bg-secondary">${subsection.subsection_number}</span>
                                        </div>
                                        <p class="card-text small text-muted mb-3">${subsection.description}</p>
                                        <div class="progress mb-2" style="height: 6px;">
                                            <div class="progress-bar bg-success" role="progressbar" 
                                                 style="width: ${subsection.progress_percentage}%">
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-muted">${subsection.completed_topics}/${subsection.total_topics} topics</small>
                                            <a href="/pages/subjects/math_topics.php?subsection=${subsection.id}" 
                                               class="btn btn-sm btn-outline-primary">View Topics</a>
                                        </div>
                                    </div>
                                </div>
                            `;
                            subsectionsContainer.appendChild(subsectionElement);
                        });
                    } else {
                        subsectionsContainer.innerHTML = '<div class="alert alert-info">No subsections found for this section.</div>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    subsectionsContainer.innerHTML = '<div class="alert alert-danger">Error loading subsections. Please try again.</div>';
                });
        });
    });

    // Search functionality
    const searchForm = document.querySelector('form.d-flex');
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const searchTerm = this.querySelector('input[type="search"]').value.trim();
            
            if (searchTerm.length > 0) {
                window.location.href = `/pages/search.php?q=${encodeURIComponent(searchTerm)}`;
            }
        });
    }
});
</script>

<input type="hidden" id="topicData" 
       data-topic-id="<?php echo $topic_id; ?>" 
       data-subject="<?php echo $subject; ?>">

<?php include '../../includes/footer.php'; ?>