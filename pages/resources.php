<?php
require_once '../config/db_connect.php';
include '../includes/header.php';

// Get filter parameters
$subject_filter = isset($_GET['subject']) ? intval($_GET['subject']) : 0;
$section_filter = isset($_GET['section']) ? intval($_GET['section']) : 0;
$subsection_filter = isset($_GET['subsection']) ? intval($_GET['subsection']) : 0;
$topic_filter = isset($_GET['topic']) ? intval($_GET['topic']) : 0;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Get all subjects
$subjects = $conn->query("SELECT id, name, color FROM subjects ORDER BY name")->fetch_all(MYSQLI_ASSOC);

// Get sections based on selected subject
$sections = [];
if ($subject_filter) {
    $sections_query = "SELECT * FROM " . 
        ($subject_filter == 1 ? 'eng_sections' : 'math_sections') . 
        " ORDER BY section_number";
    $sections = $conn->query($sections_query)->fetch_all(MYSQLI_ASSOC);
}

// Get subsections based on selected section
$subsections = [];
if ($section_filter) {
    $subsections_query = "SELECT * FROM " . 
        ($subject_filter == 1 ? 'eng_subsections' : 'math_subsections') . 
        " WHERE section_id = ? ORDER BY subsection_number";
    $stmt = $conn->prepare($subsections_query);
    $stmt->bind_param('i', $section_filter);
    $stmt->execute();
    $subsections = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Get topics based on selected subsection
$topics = [];
if ($subsection_filter) {
    $topics_query = "SELECT * FROM " . 
        ($subject_filter == 1 ? 'eng_topics' : 'math_topics') . 
        " WHERE subsection_id = ? ORDER BY name";
    $stmt = $conn->prepare($topics_query);
    $stmt->bind_param('i', $subsection_filter);
    $stmt->execute();
    $topics = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Build the main resources query with proper joins
$query = "WITH resource_data AS (
    -- Math Resources
    SELECT 
        tr.id,
        tr.topic_id,
        tr.title,
        tr.resource_type,
        tr.youtube_url,
        tr.image_path,
        tr.is_deleted,
        t.name as topic_name,
        sub.id as subsection_id,
        sub.name as subsection_name,
        sec.id as section_id,
        sec.name as section_name,
        s.name as subject_name,
        s.id as subject_id,
        s.color as subject_color
    FROM topic_resources tr
    JOIN math_topics t ON tr.topic_id = t.id
    JOIN math_subsections sub ON t.subsection_id = sub.id
    JOIN math_sections sec ON sub.section_id = sec.id
    JOIN subjects s ON s.id = 2
    WHERE tr.is_deleted = 0

    UNION ALL

    -- English Resources
    SELECT 
        tr.id,
        tr.topic_id,
        tr.title,
        tr.resource_type,
        tr.youtube_url,
        tr.image_path,
        tr.is_deleted,
        t.name as topic_name,
        sub.id as subsection_id,
        sub.name as subsection_name,
        sec.id as section_id,
        sec.name as section_name,
        s.name as subject_name,
        s.id as subject_id,
        s.color as subject_color
    FROM topic_resources tr
    JOIN eng_topics t ON tr.topic_id = t.id
    JOIN eng_subsections sub ON t.subsection_id = sub.id
    JOIN eng_sections sec ON sub.section_id = sec.id
    JOIN subjects s ON s.id = 1
    WHERE tr.is_deleted = 0
)
SELECT * FROM resource_data WHERE 1=1";

$params = [];
$types = '';

// Add filters
if ($subject_filter) {
    $query .= " AND subject_id = ?";
    $params[] = $subject_filter;
    $types .= 'i';
}

if ($section_filter) {
    $query .= " AND section_id = ?";
    $params[] = $section_filter;
    $types .= 'i';
}

if ($subsection_filter) {
    $query .= " AND subsection_id = ?";
    $params[] = $subsection_filter;
    $types .= 'i';
}

if ($topic_filter) {
    $query .= " AND topic_id = ?";
    $params[] = $topic_filter;
    $types .= 'i';
}

if ($search) {
    $query .= " AND (title LIKE ? OR topic_name LIKE ? OR section_name LIKE ? OR subsection_name LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
    $types .= 'ssss';
}

$query .= " ORDER BY subject_name, section_name, subsection_name, topic_name, title";

// Execute the query
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$resources = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get current selections for display
$current_section = null;
$current_subsection = null;
$current_topic = null;

if ($section_filter && !empty($sections)) {
    $current_section = array_values(array_filter($sections, fn($s) => $s['id'] == $section_filter))[0] ?? null;
}
if ($subsection_filter && !empty($subsections)) {
    $current_subsection = array_values(array_filter($subsections, fn($s) => $s['id'] == $subsection_filter))[0] ?? null;
}
if ($topic_filter && !empty($topics)) {
    $current_topic = array_values(array_filter($topics, fn($t) => $t['id'] == $topic_filter))[0] ?? null;
}

// Add the getYoutubeId function that was missing
function getYoutubeId($url) {
    $regExp = '/^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|&v=)([^#&?]*).*/';
    $match = [];
    preg_match($regExp, $url, $match);
    return (isset($match[2]) && strlen($match[2]) === 11) ? $match[2] : null;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resources</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/glightbox/3.2.0/css/glightbox.min.css" />
</head>
<body class="bg-light">
    <div class="container py-4">
        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3" id="filterForm">
                    <!-- Subject Filter -->
                    <div class="col-md-3">
                        <label class="form-label">Subject</label>
                        <select name="subject" class="form-select" id="subjectSelect">
                            <option value="">All Subjects</option>
                            <?php foreach ($subjects as $subject): ?>
                                <option value="<?php echo $subject['id']; ?>" 
                                        <?php echo $subject_filter == $subject['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($subject['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Section Filter -->
                    <div class="col-md-3">
                        <label class="form-label">Section</label>
                        <select name="section" class="form-select" id="sectionSelect" <?php echo !$subject_filter ? 'disabled' : ''; ?>>
                            <option value="">All Sections</option>
                            <?php foreach ($sections as $section): ?>
                                <option value="<?php echo $section['id']; ?>" 
                                        <?php echo $section_filter == $section['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($section['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Subsection Filter -->
                    <div class="col-md-3">
                        <label class="form-label">Subsection</label>
                        <select name="subsection" class="form-select" id="subsectionSelect" <?php echo !$section_filter ? 'disabled' : ''; ?>>
                            <option value="">All Subsections</option>
                            <?php foreach ($subsections as $subsection): ?>
                                <option value="<?php echo $subsection['id']; ?>" 
                                        <?php echo $subsection_filter == $subsection['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($subsection['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Topic Filter -->
                    <div class="col-md-3">
                        <label class="form-label">Topic</label>
                        <select name="topic" class="form-select" id="topicSelect" <?php echo !$subsection_filter ? 'disabled' : ''; ?>>
                            <option value="">All Topics</option>
                            <?php foreach ($topics as $topic): ?>
                                <option value="<?php echo $topic['id']; ?>" 
                                        <?php echo $topic_filter == $topic['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($topic['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Search -->
                    <div class="col-md-12">
                        <label class="form-label">Search</label>
                        <div class="input-group">
                            <input type="text" name="search" class="form-control" 
                                   value="<?php echo htmlspecialchars($search); ?>" 
                                   placeholder="Search in titles, topics, sections...">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Filter Actions -->
                    <div class="col-12">
                        <div class="d-flex gap-2 align-items-center">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter"></i> Apply Filters
                            </button>
                            <a href="/pages/resources.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i> Clear Filters
                            </a>

                            <!-- Active Filters Display -->
                            <?php if ($subject_filter || $section_filter || $subsection_filter || $topic_filter || $search): ?>
                                <div class="ms-auto d-flex flex-wrap gap-2 align-items-center">
                                    <span class="text-muted">Active filters:</span>
                                    <?php if ($subject_filter): ?>
                                        <span class="badge bg-primary">
                                            Subject: <?php echo htmlspecialchars(array_values(array_filter($subjects, fn($s) => $s['id'] == $subject_filter))[0]['name']); ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($current_section): ?>
                                        <span class="badge bg-primary">
                                            Section: <?php echo htmlspecialchars($current_section['name']); ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($current_subsection): ?>
                                        <span class="badge bg-primary">
                                            Subsection: <?php echo htmlspecialchars($current_subsection['name']); ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($current_topic): ?>
                                        <span class="badge bg-primary">
                                            Topic: <?php echo htmlspecialchars($current_topic['name']); ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($search): ?>
                                        <span class="badge bg-primary">
                                            Search: <?php echo htmlspecialchars($search); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Resources Grid -->
        <div class="row g-4">
            <?php foreach ($resources as $resource): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 resource-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <h5 class="card-title mb-0">
                                    <?php echo htmlspecialchars($resource['title']); ?>
                                </h5>
                                <span class="badge" style="background-color: <?php echo $resource['subject_color']; ?>">
                                    <?php echo htmlspecialchars($resource['subject_name']); ?>
                                </span>
                            </div>
                            
                            <div class="topic-info mb-3">
                                <div class="d-flex flex-column gap-1">
                                    <small class="text-muted">
                                        <i class="fas fa-folder"></i> <?php echo htmlspecialchars($resource['section_name']); ?>
                                    </small>
                                    <small class="text-muted">
                                        <i class="fas fa-folder-open"></i> <?php echo htmlspecialchars($resource['subsection_name']); ?>
                                    </small>
                                    <small class="text-muted">
                                        <i class="fas fa-bookmark"></i> <?php echo htmlspecialchars($resource['topic_name']); ?>
                                    </small>
                                </div>
                            </div>

                            <?php if ($resource['resource_type'] === 'youtube'): ?>
                                <div class="embed-responsive embed-responsive-16by9 mb-3">
                                    <iframe class="embed-responsive-item" 
                                            src="https://www.youtube.com/embed/<?php echo getYoutubeId($resource['youtube_url']); ?>" 
                                            allowfullscreen></iframe>
                                </div>
                            <?php elseif ($resource['resource_type'] === 'image'): ?>
                                <?php 
                                    $imagePath = $resource['image_path'];
                                    if (!str_starts_with($imagePath, '/')) {
                                        $imagePath = '/' . $imagePath;
                                    }
                                ?>
                                <a href="<?php echo htmlspecialchars($imagePath); ?>" 
                                   class="glightbox"
                                   data-gallery="resources-gallery"
                                   data-description="<?php echo htmlspecialchars($resource['title']); ?>"
                                   data-type="image">
                                    <img src="<?php echo htmlspecialchars($imagePath); ?>" 
                                         class="img-fluid mb-3 rounded resource-thumbnail" 
                                         alt="<?php echo htmlspecialchars($resource['title']); ?>"
                                         onerror="this.onerror=null; this.src='/assets/images/image-not-found.png';">
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($resources)): ?>
            <div class="text-center py-5">
                <i class="fas fa-photo-video fa-3x text-muted mb-3"></i>
                <h3>No resources found</h3>
                <p class="text-muted">Try adjusting your filters or add resources to your topics.</p>
            </div>
        <?php endif; ?>
    </div>

    <style>
    .resource-card {
        transition: all 0.3s ease;
        border: 1px solid rgba(226, 232, 240, 0.8);
        border-radius: 1rem;
        background: #ffffff;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    }

    .resource-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
    }

    .resource-thumbnail {
        width: 100%;
        height: 200px;
        object-fit: cover;
        cursor: pointer;
        transition: transform 0.2s;
    }

    .topic-info {
        padding: 0.5rem;
        background: #f8fafc;
        border-radius: 0.5rem;
        font-size: 0.875rem;
    }

    .badge {
        padding: 0.5rem 1rem;
        border-radius: 0.75rem;
        font-weight: 500;
        font-size: 0.875rem;
    }

    .card-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: #1e293b;
    }

    .embed-responsive {
        position: relative;
        width: 100%;
        padding-bottom: 56.25%;
    }

    .embed-responsive iframe {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        border: 0;
        border-radius: 0.5rem;
    }

    /* GLightbox customization */
    .glightbox-clean {
        --gbackground: rgba(0, 0, 0, 0.95);
    }

    .gslide-description {
        background: transparent;
    }

    .gslide-title {
        color: #fff;
        font-size: 16px;
        margin-bottom: 0;
    }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/gh/mcstudios/glightbox/dist/js/glightbox.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const subjectSelect = document.getElementById('subjectSelect');
        const sectionSelect = document.getElementById('sectionSelect');
        const subsectionSelect = document.getElementById('subsectionSelect');
        const topicSelect = document.getElementById('topicSelect');
        const filterForm = document.getElementById('filterForm');
        const searchInput = document.querySelector('input[name="search"]');

        // Initialize GLightbox
        const lightbox = GLightbox({
            touchNavigation: true,
            loop: true,
            autoplayVideos: true,
            preload: true,
            moreLength: 0,
            slideEffect: 'fade',
            cssEfects: {
                fade: { in: 'fadeIn', out: 'fadeOut' }
            },
            touchFollowAxis: true,
            keyboardNavigation: true,
            closeOnOutsideClick: true,
            openEffect: 'zoom',
            closeEffect: 'fade',
            draggable: true,
            zoomable: true,
            dragToleranceX: 40,
            dragToleranceY: 40
        });

        // Handle subject change
        subjectSelect.addEventListener('change', async function() {
            const subjectId = this.value;
            resetSelects('section');
            
            if (subjectId) {
                try {
                    const response = await fetch(`/api/sections/get_sections.php?subject_id=${subjectId}`);
                    const data = await response.json();
                    
                    if (data.success) {
                        populateSelect(sectionSelect, data.sections);
                        sectionSelect.disabled = false;
                    }
                } catch (error) {
                    console.error('Error fetching sections:', error);
                    showError('Failed to load sections');
                }
            }
        });

        // Handle section change
        sectionSelect.addEventListener('change', async function() {
            const sectionId = this.value;
            resetSelects('subsection');
            
            if (sectionId) {
                try {
                    const response = await fetch(`/api/subsections/get_subsections.php?section_id=${sectionId}`);
                    const data = await response.json();
                    
                    if (data.success) {
                        populateSelect(subsectionSelect, data.subsections);
                        subsectionSelect.disabled = false;
                    }
                } catch (error) {
                    console.error('Error fetching subsections:', error);
                    showError('Failed to load subsections');
                }
            }
        });

        // Handle subsection change
        subsectionSelect.addEventListener('change', async function() {
            const subsectionId = this.value;
            resetSelects('topic');
            
            if (subsectionId) {
                try {
                    const response = await fetch(`/api/topics/get_topics.php?subsection_id=${subsectionId}`);
                    const data = await response.json();
                    
                    if (data.success) {
                        populateSelect(topicSelect, data.topics);
                        topicSelect.disabled = false;
                    }
                } catch (error) {
                    console.error('Error fetching topics:', error);
                    showError('Failed to load topics');
                }
            }
        });

        // Handle search input with debounce
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                filterForm.submit();
            }, 500);
        });

        // Helper function to reset dependent selects
        function resetSelects(startFrom) {
            const selects = {
                'section': [sectionSelect, subsectionSelect, topicSelect],
                'subsection': [subsectionSelect, topicSelect],
                'topic': [topicSelect]
            };

            selects[startFrom].forEach(select => {
                select.innerHTML = '<option value="">All ' + select.name.charAt(0).toUpperCase() + select.name.slice(1) + 's</option>';
                select.disabled = true;
            });
        }

        // Helper function to populate select elements
        function populateSelect(select, items) {
            select.innerHTML = `<option value="">All ${select.name.charAt(0).toUpperCase() + select.name.slice(1)}s</option>`;
            items.forEach(item => {
                const option = document.createElement('option');
                option.value = item.id;
                option.textContent = item.name;
                select.appendChild(option);
            });
        }

        // Error handling function
        function showError(message) {
            const errorDiv = document.createElement('div');
            errorDiv.className = 'alert alert-danger alert-dismissible fade show mt-3';
            errorDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            filterForm.insertAdjacentElement('afterend', errorDiv);
            
            setTimeout(() => {
                errorDiv.remove();
            }, 5000);
        }
    });
    </script>
</body>
</html>
