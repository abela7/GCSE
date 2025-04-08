<?php
require_once '../config/db_connect.php';
include '../includes/header.php';

// Get filter parameters
$subject_filter = isset($_GET['subject']) ? intval($_GET['subject']) : 0;
$topic_filter = isset($_GET['topic']) ? intval($_GET['topic']) : 0;
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build the query for resources
$query = "
    SELECT 
        tr.*,
        t.name as topic_name,
        t.id as topic_id,
        s.name as subject_name,
        s.id as subject_id,
        s.color as subject_color,
        sub.name as subsection_name
    FROM topic_resources tr
    JOIN math_topics t ON tr.topic_id = t.id
    JOIN math_subsections sub ON t.subsection_id = sub.id
    JOIN math_sections sec ON sub.section_id = sec.id
    JOIN subjects s ON s.id = 2
    WHERE tr.is_deleted = 0 AND s.id = 2

    UNION ALL

    SELECT 
        tr.*,
        t.name as topic_name,
        t.id as topic_id,
        s.name as subject_name,
        s.id as subject_id,
        s.color as subject_color,
        sub.name as subsection_name
    FROM topic_resources tr
    JOIN eng_topics t ON tr.topic_id = t.id
    JOIN eng_subsections sub ON t.subsection_id = sub.id
    JOIN eng_sections sec ON sub.section_id = sec.id
    JOIN subjects s ON s.id = 1
    WHERE tr.is_deleted = 0 AND s.id = 1
";

$params = [];
$types = '';

if ($subject_filter) {
    $query .= " HAVING subject_id = ?";
    $params[] = $subject_filter;
    $types .= 'i';
}

if ($topic_filter) {
    $query .= ($subject_filter ? " AND" : " HAVING") . " topic_id = ?";
    $params[] = $topic_filter;
    $types .= 'i';
}

if ($search) {
    $search_condition = " HAVING title LIKE ?";
    if ($subject_filter || $topic_filter) {
        $search_condition = " AND title LIKE ?";
    }
    $query .= $search_condition;
    $params[] = "%$search%";
    $types .= 's';
}

$query .= " ORDER BY subject_name, topic_name, title";

// Prepare and execute the query
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$resources = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get all subjects for the filter
$subjects = $conn->query("SELECT id, name, color FROM subjects ORDER BY name")->fetch_all(MYSQLI_ASSOC);

// Get topics based on selected subject
$topics = [];
if ($subject_filter) {
    if ($subject_filter == 1) { // English
        $topics_query = "
            SELECT t.id, t.name, sub.name as subsection_name
            FROM eng_topics t
            JOIN eng_subsections sub ON t.subsection_id = sub.id
            ORDER BY sub.subsection_number, t.name
        ";
    } else { // Math
        $topics_query = "
            SELECT t.id, t.name, sub.name as subsection_name
            FROM math_topics t
            JOIN math_subsections sub ON t.subsection_id = sub.id
            ORDER BY sub.subsection_number, t.name
        ";
    }
    $topics = $conn->query($topics_query)->fetch_all(MYSQLI_ASSOC);
}
?>

<!-- Add GLightbox CSS after other stylesheets -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/glightbox/3.2.0/css/glightbox.min.css" />

<div class="container py-4">
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3" id="filterForm">
                <div class="col-md-4">
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
                <div class="col-md-4">
                    <label class="form-label">Topic</label>
                    <select name="topic" class="form-select" id="topicSelect" <?php echo !$subject_filter ? 'disabled' : ''; ?>>
                        <option value="">All Topics</option>
                        <?php foreach ($topics as $topic): ?>
                            <option value="<?php echo $topic['id']; ?>" 
                                    <?php echo $topic_filter == $topic['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($topic['subsection_name'] . ' - ' . $topic['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" 
                           value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Search resources...">
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <a href="/pages/resources.php" class="btn btn-outline-secondary">Clear Filters</a>
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
                            <i class="fas fa-bookmark"></i>
                            <span><?php echo htmlspecialchars($resource['topic_name']); ?></span>
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

.topic-info {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.25rem 0.75rem;
    background: #f8fafc;
    border-radius: 0.5rem;
    color: #64748b;
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
    padding-bottom: 56.25%; /* 16:9 Aspect Ratio */
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

img.img-fluid {
    max-height: 200px;
    width: 100%;
    object-fit: cover;
}

.resource-thumbnail {
    width: 100%;
    height: 200px;
    object-fit: cover;
    cursor: pointer;
    transition: transform 0.2s;
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

<!-- Add GLightbox JS before the closing body tag -->
<script src="https://cdn.jsdelivr.net/gh/mcstudios/glightbox/dist/js/glightbox.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const subjectSelect = document.getElementById('subjectSelect');
    const topicSelect = document.getElementById('topicSelect');
    const filterForm = document.getElementById('filterForm');

    subjectSelect.addEventListener('change', async function() {
        const subjectId = this.value;
        topicSelect.disabled = !subjectId;
        
        if (subjectId) {
            try {
                const response = await fetch(`/api/topics/get_topics.php?subject_id=${subjectId}`);
                const data = await response.json();
                
                if (data.success) {
                    topicSelect.innerHTML = '<option value="">All Topics</option>' +
                        data.topics.map(topic => 
                            `<option value="${topic.id}">${topic.subsection_name} - ${topic.name}</option>`
                        ).join('');
                }
            } catch (error) {
                console.error('Error fetching topics:', error);
            }
        } else {
            topicSelect.innerHTML = '<option value="">All Topics</option>';
        }
    });
});

function getYoutubeId(url) {
    const regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|&v=)([^#&?]*).*/;
    const match = url.match(regExp);
    return (match && match[2].length === 11) ? match[2] : null;
}

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

// Prevent default link behavior
document.querySelectorAll('.glightbox').forEach(link => {
    link.addEventListener('click', (e) => {
        e.preventDefault();
    });
});
</script>
</body>
</html>
