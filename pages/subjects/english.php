<?php
// Set breadcrumbs
$breadcrumbs = [
    'Home' => '/',
    'Subjects' => '/pages/subjects.php',
    'English' => null
];

// Set page actions
$page_actions = '
<a href="/pages/resources.php?subject=1" class="btn btn-outline-primary btn-sm">
    <i class="fas fa-book me-1"></i> Resources
</a>
<a href="/pages/exams.php?subject=1" class="btn btn-outline-primary btn-sm ms-2">
    <i class="fas fa-file-alt me-1"></i> Exams
</a>
';

// Include database connection
require_once '../../config/db_connect.php';

// Fetch English sections with their progress
$sections_query = "
    SELECT 
        es.*,
        COUNT(DISTINCT et.id) as total_topics,
        COUNT(DISTINCT CASE WHEN etp.status = 'completed' THEN et.id END) as completed_topics,
        COALESCE(ROUND(COUNT(DISTINCT CASE WHEN etp.status = 'completed' THEN et.id END) * 100.0 / 
            NULLIF(COUNT(DISTINCT et.id), 0), 1), 0) as progress_percentage,
        COALESCE(SUM(etp.total_time_spent), 0) as total_time_spent_seconds
    FROM eng_sections es
    LEFT JOIN eng_subsections esub ON es.id = esub.section_id
    LEFT JOIN eng_topics et ON esub.id = et.subsection_id
    LEFT JOIN eng_topic_progress etp ON et.id = etp.topic_id
    GROUP BY es.id
    ORDER BY es.section_number;
";

$sections_result = $conn->query($sections_query);
$sections = [];

if ($sections_result) {
    while ($row = $sections_result->fetch_assoc()) {
        $sections[] = $row;
    }
}

// Calculate overall progress
$overall_progress_query = "
    SELECT 
        COUNT(DISTINCT et.id) as total_topics,
        COUNT(DISTINCT CASE WHEN etp.status = 'completed' THEN et.id END) as completed_topics,
        COALESCE(ROUND(COUNT(DISTINCT CASE WHEN etp.status = 'completed' THEN et.id END) * 100.0 / 
            NULLIF(COUNT(DISTINCT et.id), 0), 1), 0) as progress_percentage,
        COALESCE(SUM(etp.total_time_spent), 0) as total_study_time
    FROM eng_topics et
    LEFT JOIN eng_topic_progress etp ON et.id = etp.topic_id;
";

$overall_result = $conn->query($overall_progress_query);
$overall_progress = $overall_result->fetch_assoc();

// Format study time
function formatStudyTime($seconds) {
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    return $hours . "h " . $minutes . "m";
}

// Include header
include '../../includes/header.php';
?>

<!-- Alert Container for JavaScript notifications -->
<div id="alert-container" class="position-fixed top-0 end-0 p-3" style="z-index: 1050;"></div>

<div class="subject-page english-page">
    <!-- Hero Section -->
    <div class="hero-section py-5 mb-4">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h1 class="display-4 fw-bold text-white mb-3">English</h1>
                    <p class="lead text-white mb-0">Master key English concepts and prepare for your GCSE exams</p>
                </div>
                <div class="col-lg-4">
                    <div class="stats-card bg-white p-4 rounded-4 shadow-sm">
                        <div class="stat-item mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="stat-label">Overall Progress</span>
                                <span class="stat-value"><?php echo $overall_progress['progress_percentage']; ?>%</span>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-success" role="progressbar" 
                                     style="width: <?php echo $overall_progress['progress_percentage']; ?>%">
                                </div>
                            </div>
                        </div>
                        <div class="stat-item mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="stat-label">Topics Completed</span>
                                <span class="stat-value"><?php echo $overall_progress['completed_topics']; ?>/<?php echo $overall_progress['total_topics']; ?></span>
                            </div>
                        </div>
                        <div class="stat-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="stat-label">Study Time</span>
                                <span class="stat-value"><?php echo formatStudyTime($overall_progress['total_study_time']); ?></span>
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

        <!-- Search Results Container -->
        <div id="searchResults" class="search-results mb-4" style="display: none;">
            <div class="container">
                <h3 class="mb-3">Search Results</h3>
                <div id="matchingItems"></div>
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
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <h5 class="card-title mb-0"><?php echo htmlspecialchars($section['name']); ?></h5>
                            <span class="badge bg-primary"><?php echo $section['section_number']; ?></span>
                        </div>
                        <p class="card-text text-muted"><?php echo htmlspecialchars($section['description']); ?></p>
                        <div class="progress mb-3" style="height: 8px;">
                            <div class="progress-bar bg-success" role="progressbar" 
                                 style="width: <?php echo $section['progress_percentage']; ?>%">
                            </div>
                        </div>
                        <div class="d-flex justify-content-between text-muted small">
                            <span><?php echo $section['completed_topics']; ?>/<?php echo $section['total_topics']; ?> topics</span>
                            <span><?php echo number_format($section['progress_percentage'], 1); ?>% complete</span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Section Modal -->
    <div class="modal fade" id="sectionModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="section-details mb-4">
                        <p class="section-description"></p>
                        <div class="progress mb-3" style="height: 8px;">
                            <div class="progress-bar bg-success" role="progressbar"></div>
                        </div>
                        <div class="d-flex justify-content-between text-muted small">
                            <span class="topics-count"></span>
                            <span class="progress-percentage"></span>
                        </div>
                    </div>
                    <div id="subsectionsList" class="subsections-container">
                        <!-- Subsections will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.hero-section {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
}

.stats-card {
    border-radius: 1rem;
}

.stat-label {
    color: #6c757d;
    font-size: 0.9rem;
}

.stat-value {
    font-weight: 600;
    font-size: 1.1rem;
}

.search-box {
    position: relative;
}

.search-icon {
    position: absolute;
    left: 16px;
    top: 50%;
    transform: translateY(-50%);
    color: #6c757d;
}

.search-input {
    padding-left: 45px;
    border-radius: 50px;
    border: 2px solid #e9ecef;
}

.search-input:focus {
    border-color: #28a745;
    box-shadow: none;
}

.section-card {
    transition: transform 0.2s, box-shadow 0.2s;
    cursor: pointer;
    border: none;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.section-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.progress {
    background-color: #e9ecef;
    border-radius: 50px;
    overflow: hidden;
}

.progress-bar {
    transition: width 0.3s ease;
}

.subsection-card {
    border: 1px solid #e9ecef;
    border-radius: 0.5rem;
    padding: 1rem;
    margin-bottom: 1rem;
    transition: all 0.2s ease-in-out;
}

.subsection-card:hover {
    border-color: #28a745;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.subsection-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.subsection-title {
    font-weight: 600;
    margin: 0;
}

.subsection-progress {
    font-size: 0.875rem;
    color: #6c757d;
}

.subsection-description {
    color: #6c757d;
    font-size: 0.875rem;
    margin-bottom: 1rem;
}

.subsection-stats {
    display: flex;
    gap: 1rem;
    font-size: 0.875rem;
    color: #6c757d;
}

.subsection-stats i {
    margin-right: 0.25rem;
    color: #28a745;
}

#sectionLoading {
    display: none;
}

#sectionLoading.active {
    display: block;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = new bootstrap.Modal(document.getElementById('sectionModal'));
    const searchInput = document.getElementById('searchInput');
    const searchResults = document.getElementById('searchResults');
    const matchingItems = document.getElementById('matchingItems');
    const sectionsGrid = document.getElementById('sectionsGrid');
    let sections = <?php echo json_encode($sections); ?>;

    // Handle section card clicks
    document.querySelectorAll('.section-card').forEach(card => {
        card.addEventListener('click', async function() {
            const sectionId = this.dataset.sectionId;
            const sectionName = this.dataset.sectionName;
            const sectionDescription = this.dataset.sectionDescription;

            // Update modal content
            document.querySelector('#sectionModal .modal-title').textContent = sectionName;
            document.querySelector('#sectionModal .section-description').textContent = sectionDescription;

            try {
                // Show loading state
                document.querySelector('#sectionModal .subsections-container').innerHTML = `
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                `;

                // Fetch subsections
                const response = await fetch(`/api/english/subsections.php?section_id=${sectionId}`);
                const data = await response.json();

                if (data.success) {
                    // Update progress in modal
                    const progressBar = document.querySelector('#sectionModal .progress-bar');
                    progressBar.style.width = `${data.section_progress.progress_percentage}%`;
                    document.querySelector('#sectionModal .topics-count').textContent = 
                        `${data.section_progress.completed_topics}/${data.section_progress.total_topics} topics`;
                    document.querySelector('#sectionModal .progress-percentage').textContent = 
                        `${data.section_progress.progress_percentage}% complete`;

                    // Render subsections
                    const subsectionsHtml = data.subsections.map(subsection => `
                        <div class="subsection-item mb-3">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <h5 class="card-title mb-0">${subsection.name}</h5>
                                        <span class="badge bg-primary">${subsection.subsection_number}</span>
                                    </div>
                                    <p class="card-text text-muted mb-3">${subsection.description || ''}</p>
                                    <div class="progress mb-3" style="height: 8px;">
                                        <div class="progress-bar bg-success" role="progressbar" 
                                             style="width: ${subsection.progress_percentage}%">
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="text-muted small">
                                            ${subsection.completed_topics}/${subsection.total_topics} topics • 
                                            ${subsection.progress_percentage}% complete
                                        </div>
                                        <a href="/GCSE/pages/subjects/english_topics.php?subsection_id=${subsection.id}" 
                                           class="btn btn-outline-primary btn-sm">
                                            View Topics
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `).join('');

                    document.querySelector('#sectionModal .subsections-container').innerHTML = subsectionsHtml;
                } else {
                    throw new Error(data.message || 'Failed to load subsections');
                }
            } catch (error) {
                console.error('Error:', error);
                document.querySelector('#sectionModal .subsections-container').innerHTML = `
                    <div class="alert alert-danger">
                        Error loading subsections. Please try again.
                    </div>
                `;
            }

            modal.show();
        });
    });

    // Search functionality
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    function highlightText(text, searchTerm) {
        if (!searchTerm) return text;
        const regex = new RegExp(`(${searchTerm})`, 'gi');
        return text.replace(regex, '<mark>$1</mark>');
    }

    const handleSearch = debounce(async (searchTerm) => {
        if (!searchTerm) {
            searchResults.style.display = 'none';
            document.querySelectorAll('.section-item').forEach(item => {
                item.style.display = 'block';
            });
            return;
        }

        try {
            const response = await fetch(`/api/english/search.php?term=${encodeURIComponent(searchTerm)}`);
            const data = await response.json();

            if (data.success) {
                const results = data.results;
                
                // Show/hide sections based on search results
                document.querySelectorAll('.section-item').forEach(item => {
                    const sectionId = item.querySelector('.section-card').dataset.sectionId;
                    if (results.sections.includes(parseInt(sectionId))) {
                        item.style.display = 'block';
                    } else {
                        item.style.display = 'none';
                    }
                });

                // Display detailed search results
                if (results.matches.length > 0) {
                    const matchesHtml = results.matches.map(match => `
                        <div class="match-item" style="cursor: pointer;" onclick="handleMatchClick(${match.id}, '${match.type}')">
                            <h6 class="mb-1">${highlightText(match.name, searchTerm)}</h6>
                            <p class="mb-0 small text-muted">
                                ${highlightText(match.description || '', searchTerm)}
                            </p>
                        </div>
                    `).join('');

                    matchingItems.innerHTML = matchesHtml;
                    searchResults.style.display = 'block';
                } else {
                    searchResults.style.display = 'none';
                }
            }
        } catch (error) {
            console.error('Search error:', error);
        }
    }, 300);

    searchInput.addEventListener('input', (e) => handleSearch(e.target.value.trim()));

    // Add click handler for search results
    function handleMatchClick(id, type) {
        if (type === 'section') {
            // Find and click the corresponding section card
            const sectionCard = document.querySelector(`.section-card[data-section-id="${id}"]`);
            if (sectionCard) {
                sectionCard.click();
            }
        } else if (type === 'subsection') {
            // For subsections, we'll need to first show the section modal
            const sectionCard = document.querySelector(`.section-card[data-section-id="${id}"]`);
            if (sectionCard) {
                sectionCard.click();
            }
        }
        // Clear search after clicking
        searchInput.value = '';
        searchResults.style.display = 'none';
    }
});
</script>

<?php include '../../includes/footer.php'; ?>