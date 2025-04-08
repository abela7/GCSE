<?php
$page_title = "Resource Viewer";
require_once '../config/db_connect.php';
require_once '../includes/functions.php';

if (!isset($_GET['topic_id']) || !isset($_GET['subject'])) {
    header('Location: /pages/subjects.php');
    exit;
}

$topic_id = intval($_GET['topic_id']);
$subject = $_GET['subject'];

// Get topic details
$topic_query = "SELECT t.name as topic_name, s.name as section_name, sub.name as subsection_name
                FROM " . ($subject === 'english' ? 'eng_topics' : 'math_topics') . " t
                JOIN " . ($subject === 'english' ? 'eng_subsections' : 'math_subsections') . " sub ON t.subsection_id = sub.id
                JOIN " . ($subject === 'english' ? 'eng_sections' : 'math_sections') . " s ON sub.section_id = s.id
                WHERE t.id = ?";

$stmt = $conn->prepare($topic_query);
$stmt->bind_param("i", $topic_id);
$stmt->execute();
$topic_result = $stmt->get_result()->fetch_assoc();

// Get resources without category filter
$resources_query = "SELECT * FROM topic_resources WHERE topic_id = ? AND is_deleted = 0 ORDER BY added_at DESC";
$stmt = $conn->prepare($resources_query);
$stmt->bind_param("i", $topic_id);
$stmt->execute();
$resources = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/glightbox/3.2.0/css/glightbox.min.css" />
    <style>
        .resource-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            padding: 20px;
        }
        .resource-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
            transition: transform 0.2s;
        }
        .resource-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .resource-thumbnail {
            width: 100%;
            height: 200px;
            object-fit: cover;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .resource-info {
            padding: 15px;
        }
        .youtube-container {
            position: relative;
            padding-bottom: 56.25%;
            height: 0;
            overflow: hidden;
        }
        .youtube-container iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }
        .back-button {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
        }

        /* Lightbox customization */
        .lb-data .lb-caption {
            font-size: 16px;
            font-weight: 500;
            color: #fff;
        }
        .lb-nav a.lb-prev,
        .lb-nav a.lb-next {
            opacity: 0.3;
        }
        .lb-nav a.lb-prev:hover,
        .lb-nav a.lb-next:hover {
            opacity: 1;
        }
        .lb-outerContainer {
            border-radius: 4px 4px 0 0;
        }
        .lb-dataContainer {
            border-radius: 0 0 4px 4px;
        }

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
</head>
<body class="bg-light">
    <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="/pages/subjects.php">Subjects</a></li>
                        <li class="breadcrumb-item"><a href="/pages/subjects/<?php echo $subject; ?>.php"><?php echo ucfirst($subject); ?></a></li>
                        <li class="breadcrumb-item"><a href="/pages/topic.php?id=<?php echo $topic_id; ?>&subject=<?php echo $subject; ?>"><?php echo htmlspecialchars($topic_result['topic_name']); ?></a></li>
                        <li class="breadcrumb-item active">Resources</li>
                    </ol>
                </nav>
                <h1 class="mb-4"><?php echo htmlspecialchars($topic_result['topic_name']); ?> Resources</h1>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addResourceModal">
                    <i class="fas fa-plus"></i> Add Resource
                </button>
            </div>
        </div>

        <div class="resource-grid">
            <?php foreach ($resources as $resource): ?>
                <div class="resource-card">
                    <?php if ($resource['resource_type'] === 'youtube'): ?>
                        <div class="youtube-container">
                            <iframe 
                                src="https://www.youtube.com/embed/<?php echo getYoutubeId($resource['youtube_url']); ?>" 
                                frameborder="0" 
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                allowfullscreen>
                            </iframe>
                        </div>
                    <?php else: ?>
                        <?php 
                            $imagePath = $resource['image_path'];
                            if (!str_starts_with($imagePath, '/')) {
                                $imagePath = '/' . $imagePath;
                            }
                        ?>
                        <a href="<?php echo htmlspecialchars($imagePath); ?>" 
                           class="glightbox"
                           data-gallery="resource-gallery"
                           data-description="<?php echo htmlspecialchars($resource['title']); ?>"
                           data-type="image">
                            <img src="<?php echo htmlspecialchars($imagePath); ?>" 
                                 alt="<?php echo htmlspecialchars($resource['title']); ?>" 
                                 class="resource-thumbnail"
                                 onerror="this.onerror=null; this.src='/assets/images/image-not-found.png';">
                        </a>
                    <?php endif; ?>
                    <div class="resource-info">
                        <h5><?php echo htmlspecialchars($resource['title']); ?></h5>
                        <p class="text-muted small">Added: <?php echo date('M j, Y', strtotime($resource['added_at'])); ?></p>
                        <button class="btn btn-sm btn-danger delete-resource" data-id="<?php echo $resource['id']; ?>">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($resources)): ?>
            <div class="text-center py-5">
                <h3>No resources added yet</h3>
                <p>Click the "Add Resource" button to add your first resource!</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Add Resource Modal -->
    <div class="modal fade" id="addResourceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Resource</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="resourceForm" enctype="multipart/form-data">
                        <input type="hidden" name="topic_id" value="<?php echo $topic_id; ?>">
                        
                        <div class="mb-3">
                            <label class="form-label">Resource Type</label>
                            <select class="form-select" name="resource_type" id="resourceType">
                                <option value="youtube">YouTube Video</option>
                                <option value="image">Image</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Title</label>
                            <input type="text" class="form-control" name="title" required>
                        </div>

                        <div id="youtubeInput" class="mb-3">
                            <label class="form-label">YouTube URL</label>
                            <input type="url" class="form-control" name="youtube_url" placeholder="https://www.youtube.com/watch?v=...">
                        </div>

                        <div id="imageInput" class="mb-3" style="display: none;">
                            <label class="form-label">Image File</label>
                            <input type="file" class="form-control" name="image" accept="image/*">
                        </div>

                        <button type="submit" class="btn btn-primary">Add Resource</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <a href="/pages/topic.php?id=<?php echo $topic_id; ?>&subject=<?php echo $subject; ?>" class="btn btn-primary back-button">
        <i class="fas fa-arrow-left"></i> Back to Topic
    </a>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/gh/mcstudios/glightbox/dist/js/glightbox.min.js"></script>
    <script>
        document.getElementById('resourceType').addEventListener('change', function() {
            const youtubeInput = document.getElementById('youtubeInput');
            const imageInput = document.getElementById('imageInput');
            
            if (this.value === 'youtube') {
                youtubeInput.style.display = 'block';
                imageInput.style.display = 'none';
            } else {
                youtubeInput.style.display = 'none';
                imageInput.style.display = 'block';
            }
        });

        document.getElementById('resourceForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitButton = this.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            
            try {
                // Validate YouTube URL if resource type is youtube
                if (formData.get('resource_type') === 'youtube') {
                    const youtubeUrl = formData.get('youtube_url');
                    if (!youtubeUrl) {
                        throw new Error('Please enter a YouTube URL');
                    }
                    
                    // Simple validation for YouTube URL format
                    const youtubeRegex = /^(https?:\/\/)?(www\.)?(youtube\.com\/watch\?v=|youtu\.be\/)[a-zA-Z0-9_-]{11}/;
                    if (!youtubeRegex.test(youtubeUrl)) {
                        throw new Error('Please enter a valid YouTube URL');
                    }
                } else if (formData.get('resource_type') === 'image') {
                    const imageFile = formData.get('image');
                    if (!imageFile || imageFile.size === 0) {
                        throw new Error('Please select an image file');
                    }
                }
                
                const response = await fetch('/api/topics/add_resource.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const result = await response.json();
                
                if (result.success) {
                    window.location.reload();
                } else {
                    throw new Error(result.message || 'Error adding resource');
                }
            } catch (error) {
                console.error('Error:', error);
                alert(error.message || 'Error adding resource');
            } finally {
                submitButton.disabled = false;
            }
        });

        document.querySelectorAll('.delete-resource').forEach(button => {
            button.addEventListener('click', async function() {
                if (!confirm('Are you sure you want to delete this resource?')) return;
                
                const resourceId = this.dataset.id;
                
                try {
                    const response = await fetch('/api/topics/delete_resource.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ resource_id: resourceId })
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        location.reload();
                    } else {
                        alert(result.message || 'Error deleting resource');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('Error deleting resource');
                }
            });
        });

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