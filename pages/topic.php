<?php
// Include database connection
require_once '../config/db_connect.php';

// Check if topic ID and subject are provided
if (!isset($_GET['id']) || !isset($_GET['subject'])) {
    header('Location: /pages/subjects.php');
    exit;
}

$topic_id = intval($_GET['id']);
$subject = $_GET['subject'];

// Set table names based on subject
if ($subject === 'english') {
    $sections_table = 'eng_sections';
    $subsections_table = 'eng_subsections';
    $topics_table = 'eng_topics';
    $progress_table = 'eng_topic_progress';
    $subject_name = 'English';
    $subject_page = '/pages/subjects/english.php';
    $gradient_colors = ['#28a745', '#20c997'];
} else {
    $sections_table = 'math_sections';
    $subsections_table = 'math_subsections';
    $topics_table = 'math_topics';
    $progress_table = 'topic_progress';
    $subject_name = 'Mathematics';
    $subject_page = '/pages/subjects/math.php';
    $gradient_colors = ['#0066ff', '#00ccff'];
}

// Fetch topic details with section and subsection info
$topic_query = "
    SELECT 
        t.*,
        s.name as section_name,
        sub.name as subsection_name,
        sub.id as subsection_id,
        tp.status,
        tp.total_time_spent,
        tp.confidence_level,
        tp.completion_date,
        tp.notes as progress_notes,
        COUNT(DISTINCT tq.id) as question_count,
        COUNT(DISTINCT tn.id) as note_count,
        COUNT(DISTINCT tr.id) as resource_count,
        latest_note.content as note_content,
        latest_note.edited_at as note_edited_at,
        latest_note.created_at as note_created_at
    FROM $topics_table t
    INNER JOIN $subsections_table sub ON t.subsection_id = sub.id
    INNER JOIN $sections_table s ON sub.section_id = s.id
    LEFT JOIN $progress_table tp ON t.id = tp.topic_id
    LEFT JOIN topic_questions tq ON t.id = tq.topic_id
    LEFT JOIN topic_notes tn ON t.id = tn.topic_id
    LEFT JOIN topic_resources tr ON t.id = tr.topic_id
    LEFT JOIN (
        SELECT topic_id, content, edited_at, created_at
        FROM topic_notes
        WHERE topic_id = ?
        ORDER BY edited_at DESC
        LIMIT 1
    ) latest_note ON t.id = latest_note.topic_id
    WHERE t.id = ?
    GROUP BY t.id;
";

$stmt = $conn->prepare($topic_query);
$stmt->bind_param('ii', $topic_id, $topic_id);
$stmt->execute();
$topic = $stmt->get_result()->fetch_assoc();

if (!$topic) {
    header("Location: $subject_page");
    exit;
}

// Set breadcrumbs
$breadcrumbs = [
    'Home' => '/',
    'Subjects' => '/pages/subjects.php',
    $subject_name => $subject_page,
    $topic['subsection_name'] => "/pages/topics.php?subsection=" . $topic['subsection_id'] . "&subject=" . $subject,
    $topic['name'] => null
];

// Fetch topic resources
$resources_query = "
    SELECT * FROM topic_resources 
    WHERE topic_id = ? 
    ORDER BY added_at DESC;
";
$stmt = $conn->prepare($resources_query);
$stmt->bind_param('i', $topic_id);
$stmt->execute();
$resources = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch topic notes
$notes_query = "
    SELECT * FROM topic_notes 
    WHERE topic_id = ? 
    ORDER BY created_at DESC;
";
$stmt = $conn->prepare($notes_query);
$stmt->bind_param('i', $topic_id);
$stmt->execute();
$notes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch topic questions
$questions_query = "
    SELECT * FROM topic_questions 
    WHERE topic_id = ? 
    ORDER BY created_at DESC;
";
$stmt = $conn->prepare($questions_query);
$stmt->bind_param('i', $topic_id);
$stmt->execute();
$questions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Include header
include '../includes/header.php';

// Add this at the top of your file to debug
error_log("Current page subject: " . $subject);
error_log("Current topic ID: " . $topic_id);
?>

<head>
    <!-- Add Quill.js Theme CSS -->
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <style>
        .ql-editor {
            min-height: 200px;
            max-height: 400px;
            overflow-y: auto;
        }
        .notes-content .ql-editor {
            padding: 0;
        }
        .notes-content .ql-container {
            border: none;
        }
        #editor-container {
            background: white;
            border-radius: 4px;
        }
    </style>
</head>

<div class="topic-page">
    <!-- Hero Section -->
    <div class="hero-section py-4 mb-4">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h6 class="text-white-50 mb-2">
                        <?php echo htmlspecialchars($topic['section_name']); ?> / 
                        <?php echo htmlspecialchars($topic['subsection_name']); ?>
                    </h6>
                    <h1 class="display-5 fw-bold text-white mb-3">
                        <?php echo htmlspecialchars($topic['name']); ?>
                    </h1>
                    <p class="lead text-white-75 mb-0">
                        <?php echo htmlspecialchars($topic['description']); ?>
                    </p>
                </div>
                <div class="col-lg-4">
                    <div class="stats-card bg-white p-4 rounded-4 shadow-sm">
                        <div class="stat-item mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="stat-label">Status</span>
                                <span class="stat-value">
                                    <?php if ($topic['status'] === 'completed'): ?>
                                        <span class="badge bg-success">Completed</span>
                                    <?php elseif ($topic['status'] === 'in_progress'): ?>
                                        <span class="badge bg-warning">In Progress</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Not Started</span>
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                        <?php if ($topic['confidence_level'] > 0): ?>
                        <div class="stat-item mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="stat-label">Confidence</span>
                                <div class="confidence-stars">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star <?php echo $i <= $topic['confidence_level'] ? 'text-warning' : 'text-muted'; ?>"></i>
                                    <?php endfor; ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if ($topic['total_time_spent'] > 0): ?>
                        <div class="stat-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="stat-label">Time Spent</span>
                                <span class="stat-value">
                                    <?php 
                                    $total_seconds = $topic['total_time_spent'];
                                    $hours = floor($total_seconds / 3600);
                                    $minutes = floor(($total_seconds % 3600) / 60);
                                    
                                    if ($hours > 0) {
                                        echo $hours . 'h ' . $minutes . 'm';
                                    } else {
                                        echo $minutes . 'm';
                                    }
                                    ?>
                                </span>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Timer Section -->
    <div class="container mb-4">
        <?php
        // Check if topic is completed and get total time spent
        $table_prefix = ($subject === 'math') ? 'topic' : 'eng_topic';
        $progress_query = "SELECT total_time_spent FROM {$table_prefix}_progress WHERE topic_id = ? AND status = 'completed'";
        $stmt = $conn->prepare($progress_query);
        $stmt->bind_param("i", $topic_id);
        $stmt->execute();
        $progress_result = $stmt->get_result();
        $progress = $progress_result->fetch_assoc();
        
        if ($progress && $progress['total_time_spent'] > 0) {
            // Convert seconds to hours and minutes for display
            $total_seconds = $progress['total_time_spent'];
            $hours = floor($total_seconds / 3600);
            $minutes = floor(($total_seconds % 3600) / 60);
            
            // Show completion message
            echo '<div class="timer-section bg-white p-3 rounded-3 shadow-sm">';
            echo '<div class="d-flex align-items-center">';
            echo '<i class="fas fa-check-circle text-success me-3"></i>';
            echo '<div>';
            echo '<h5 class="mb-1">Topic Completed!</h5>';
            echo '<p class="mb-0 text-muted">You have spent ';
            if ($hours > 0) {
                echo $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ';
            }
            echo $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' to complete this unit.';
            echo '</p>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
        } else {
            // Show timer for non-completed topics
            ?>
            <div class="timer-section bg-white p-3 rounded-3 shadow-sm">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center">
                        <span id="timerDisplay" class="h3 mb-0 me-3">00:00:00</span>
                        <div class="btn-group">
                            <button id="startTimer" class="btn btn-sm btn-primary">
                                <i class="fas fa-play"></i>
                            </button>
                            <button id="pauseTimer" class="btn btn-sm btn-warning" style="display: none;">
                                <i class="fas fa-pause"></i>
                            </button>
                            <button id="stopTimer" class="btn btn-sm btn-danger" disabled>
                                <i class="fas fa-stop"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php
        }
        ?>
    </div>

    <!-- Main Content -->
    <div class="container">
        <div class="row g-4">
            <!-- Left Column -->
            <div class="col-lg-8">
                <!-- Notes Section -->
                <div class="card mb-4">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                        <h5 class="card-title mb-0">Notes</h5>
                        <button class="btn btn-sm btn-outline-primary" id="editNotesBtn">
                            <i class="fas fa-edit me-1"></i>Edit Notes
                        </button>
                    </div>
                    <div class="card-body">
                        <div id="notesDisplay" class="notes-content">
                            <?php if (!empty($topic['note_content'])): ?>
                                <div class="notes-text ql-editor" data-gramm="false"><?php echo $topic['note_content']; ?></div>
                                <div class="text-muted small mt-2">
                                    Last edited: <?php echo date('M j, Y g:i A', strtotime($topic['note_edited_at'] ?? $topic['note_created_at'])); ?>
                                </div>
                            <?php else: ?>
                                <p class="text-muted mb-0">No notes yet. Click "Edit Notes" to add your notes.</p>
                            <?php endif; ?>
                        </div>
                        <div id="notesEdit" class="notes-edit d-none">
                            <div id="editor-container"></div>
                            <div class="d-flex justify-content-end gap-2 mt-3">
                                <button class="btn btn-sm btn-secondary" id="cancelNotesBtn">Cancel</button>
                                <button class="btn btn-sm btn-primary" id="saveNotesBtn">Save Notes</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Questions Section -->
                <div class="card mb-4">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                        <div class="d-flex align-items-center">
                            <h5 class="card-title mb-0">Practice Questions</h5>
                            <span class="badge bg-secondary ms-2"><?php echo count($questions); ?> Questions</span>
                        </div>
                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addQuestionModal">
                            <i class="fas fa-plus me-1"></i>Add Question
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($questions)): ?>
                            <div class="p-4">
                                <p class="text-muted mb-0">No practice questions added yet.</p>
                            </div>
                        <?php else: ?>
                            <div class="accordion questions-accordion" id="questionsAccordion">
                                <?php foreach ($questions as $index => $question): ?>
                                <div class="accordion-item question-item" data-question-id="<?php echo $question['id']; ?>">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" 
                                                type="button" 
                                                data-bs-toggle="collapse" 
                                                data-bs-target="#question<?php echo $question['id']; ?>">
                                            <div class="d-flex align-items-center justify-content-between w-100 me-3">
                                                <div class="question-title">
                                                    <span class="badge bg-primary me-2">Q<?php echo $index + 1; ?></span>
                                                    <span class="question-preview">
                                                        <?php 
                                                        $preview = strip_tags($question['question']);
                                                        echo strlen($preview) > 100 ? substr($preview, 0, 100) . '...' : $preview;
                                                        ?>
                                                    </span>
                                                </div>
                                                <div class="question-meta text-muted small">
                                                    <?php if ($question['is_correct']): ?>
                                                        <i class="fas fa-check-circle text-success me-1"></i>
                                                        Answered Correctly
                                                    <?php else: ?>
                                                        <i class="fas fa-circle text-muted me-1"></i>
                                                        Not Answered
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </button>
                                    </h2>
                                    <div id="question<?php echo $question['id']; ?>" 
                                         class="accordion-collapse collapse"
                                         data-bs-parent="#questionsAccordion">
                                        <div class="accordion-body">
                                            <div class="question-content mb-4">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <h6 class="fw-bold mb-0">Question</h6>
                                                    <div class="question-actions">
                                                        <button class="btn btn-sm btn-outline-primary edit-question-btn">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-danger delete-question-btn">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="question-display ql-editor p-0" data-gramm="false">
                                                    <?php echo $question['question']; ?>
                                                </div>
                                                <div class="question-edit d-none">
                                                    <div class="question-editor"></div>
                                                    <div class="d-flex justify-content-end gap-2 mt-2">
                                                        <button class="btn btn-sm btn-secondary cancel-edit-btn">Cancel</button>
                                                        <button class="btn btn-sm btn-primary save-question-btn">Save</button>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="answer-content">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <h6 class="fw-bold mb-0">Answer</h6>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <div class="form-check form-switch">
                                                            <input class="form-check-input answer-status-toggle" type="checkbox" 
                                                                   id="answerStatus<?php echo $question['id']; ?>"
                                                                   <?php echo $question['is_correct'] ? 'checked' : ''; ?>>
                                                            <label class="form-check-label" for="answerStatus<?php echo $question['id']; ?>">
                                                                Answered Correctly
                                                            </label>
                                                        </div>
                                                        <button class="btn btn-sm btn-outline-primary edit-answer-btn">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="answer-display ql-editor p-0" data-gramm="false">
                                                    <?php echo $question['answer'] ?: '<p class="text-muted">No answer provided yet.</p>'; ?>
                                                </div>
                                                <div class="answer-edit d-none">
                                                    <div class="answer-editor"></div>
                                                    <div class="d-flex justify-content-end gap-2 mt-2">
                                                        <button class="btn btn-sm btn-secondary cancel-answer-edit-btn">Cancel</button>
                                                        <button class="btn btn-sm btn-primary save-answer-btn">Save</button>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="question-footer text-muted small mt-3 pt-2 border-top">
                                                Added: <?php echo date('M j, Y g:i A', strtotime($question['created_at'])); ?>
                                                <?php if ($question['edited_at']): ?>
                                                    • Last edited: <?php echo date('M j, Y g:i A', strtotime($question['edited_at'])); ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="col-lg-4">
                <!-- Resources Section -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Resources</h5>
                        <a href="/pages/resource_viewer.php?topic_id=<?php echo $topic_id; ?>&subject=<?php echo $subject; ?>" class="btn btn-primary btn-sm">
                            <i class="fas fa-external-link-alt"></i> View All Resources
                        </a>
                    </div>
                    <div class="card-body">
                        <?php
                        // Count resources by type
                        $resources_query = "SELECT 
                            SUM(CASE WHEN resource_type = 'image' THEN 1 ELSE 0 END) as image_count,
                            SUM(CASE WHEN resource_type = 'youtube' THEN 1 ELSE 0 END) as video_count
                            FROM topic_resources 
                            WHERE topic_id = ? AND is_deleted = 0";
                        $stmt = $conn->prepare($resources_query);
                        $stmt->bind_param("i", $topic_id);
                        $stmt->execute();
                        $resource_counts = $stmt->get_result()->fetch_assoc();
                        
                        $image_count = $resource_counts['image_count'] ?? 0;
                        $video_count = $resource_counts['video_count'] ?? 0;
                        $total_count = $image_count + $video_count;
                        ?>

                        <?php if ($total_count === 0): ?>
                            <p class="text-muted mb-0">No resources added yet.</p>
                        <?php else: ?>
                            <p class="mb-0">
                                <?php
                                $resource_text = [];
                                if ($image_count > 0) {
                                    $resource_text[] = $image_count . ' ' . ($image_count === 1 ? 'image' : 'images');
                                }
                                if ($video_count > 0) {
                                    $resource_text[] = $video_count . ' ' . ($video_count === 1 ? 'video' : 'videos');
                                }
                                echo implode(' and ', $resource_text) . ' found.';
                                ?>
                                <br>
                                <small class="text-muted">Click "View All Resources" to manage your resources.</small>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Update Progress -->
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Update Progress</h5>
                        <form id="updateProgressForm" action="/api/topics/update_progress.php" method="POST">
                            <input type="hidden" name="topic_id" value="<?php echo $topic_id; ?>">
                            
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="not_started" <?php echo $topic['status'] === 'not_started' ? 'selected' : ''; ?>>
                                        Not Started
                                    </option>
                                    <option value="in_progress" <?php echo $topic['status'] === 'in_progress' ? 'selected' : ''; ?>>
                                        In Progress
                                    </option>
                                    <option value="completed" <?php echo $topic['status'] === 'completed' ? 'selected' : ''; ?>>
                                        Completed
                                    </option>
                                </select>
                            </div>

                            <div class="confidence-rating mb-3">
                                <h5>Confidence Level</h5>
                                <div class="rating-stars">
                                    <?php 
                                    // Get current confidence level from the appropriate progress table
                                    $table_prefix = ($subject === 'math') ? 'topic' : 'eng_topic';
                                    $sql = "SELECT confidence_level FROM {$table_prefix}_progress WHERE topic_id = ?";
                                    $stmt = $conn->prepare($sql);
                                    $stmt->bind_param("i", $topic_id);
                                    $stmt->execute();
                                    $result = $stmt->get_result();
                                    $currentConfidence = $result->fetch_assoc()['confidence_level'] ?? 0;
                                    $stmt->close();
                                    
                                    // Reset confidence to 0 if status is not started
                                    if ($topic['status'] === 'not_started') {
                                        $currentConfidence = 0;
                                    }
                                    ?>
                                    
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <span class="star <?php echo $i <= $currentConfidence ? 'active' : ''; ?>" 
                                              data-rating="<?php echo $i; ?>"
                                              <?php echo $topic['status'] === 'not_started' ? 'style="opacity: 0.5; cursor: not-allowed;"' : ''; ?>>
                                            ★
                                        </span>
                                    <?php endfor; ?>
                                </div>
                                <small class="text-muted">Rate your confidence level (1-5 stars)</small>
                                <!-- Hidden input to store the confidence level -->
                                <input type="hidden" name="confidence_level" id="confidenceInput" value="<?php echo $currentConfidence; ?>" form="updateProgressForm">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" class="form-control" rows="3"><?php echo htmlspecialchars($topic['progress_notes']); ?></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">
                                Update Progress
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Note Modal -->
<div class="modal fade" id="addNoteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Note</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addNoteForm" action="/api/topics/add_note.php" method="POST">
                    <input type="hidden" name="topic_id" value="<?php echo $topic_id; ?>">
                    <div class="mb-3">
                        <label class="form-label">Note Content</label>
                        <textarea name="content" class="form-control" rows="5" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Save Note</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Add Question Modal -->
<div class="modal fade" id="addQuestionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Practice Question</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addQuestionForm" action="/api/topics/add_question.php" method="POST">
                    <input type="hidden" name="topic_id" value="<?php echo $topic_id; ?>">
                    <div class="mb-3">
                        <label class="form-label">Question</label>
                        <div id="addQuestionEditor"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Answer (Optional)</label>
                        <div id="addAnswerEditor"></div>
                    </div>
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Question</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
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
                <form id="addResourceForm" action="/api/topics/add_resource.php" method="POST">
                    <input type="hidden" name="topic_id" value="<?php echo $topic_id; ?>">
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">YouTube URL</label>
                        <input type="url" name="youtube_url" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Save Resource</button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.hero-section {
    background: linear-gradient(135deg, <?php echo $gradient_colors[0]; ?> 0%, <?php echo $gradient_colors[1]; ?> 100%);
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

.timer-display {
    font-family: monospace;
}

.confidence-rating {
    padding: 15px;
    background-color: #f8f9fa;
    border-radius: 8px;
}

.rating-stars {
    font-size: 24px;
    cursor: pointer;
    display: inline-block;
}

.star {
    color: #ddd;
    transition: color 0.2s;
    padding: 0 2px;
}

.star:hover,
.star.active {
    color: #ffd700;
}

.star:hover ~ .star {
    color: #ddd;
}

.note-card,
.question-card,
.resource-card {
    transition: transform 0.2s, box-shadow 0.2s;
}

.note-card:hover,
.question-card:hover,
.resource-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(0,0,0,0.05);
}

.text-white-75 {
    color: rgba(255, 255, 255, 0.75);
}

.question-card {
    background-color: #fff;
    border-radius: 8px;
    padding: 1.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.question-number .badge {
    font-size: 0.9rem;
    padding: 0.5em 1em;
}

.show-answer-btn {
    color: var(--bs-primary);
}

.show-answer-btn:hover {
    color: var(--bs-primary-darker);
}

.answer-icon {
    transition: transform 0.2s;
}

.show-answer-btn[aria-expanded="true"] .answer-icon {
    transform: rotate(90deg);
}

.answer-section {
    border-top: 1px solid #eee;
    padding-top: 1rem;
}

.question-actions {
    opacity: 0.5;
    transition: opacity 0.2s;
}

.question-card:hover .question-actions {
    opacity: 1;
}

.questions-accordion .accordion-button {
    padding: 0.75rem 1rem;
}

.questions-accordion .accordion-button:not(.collapsed) {
    background-color: var(--bs-primary-bg-subtle);
    color: var(--bs-primary);
}

.questions-accordion .question-preview {
    color: var(--bs-body-color);
}

.questions-accordion .accordion-button.collapsed .question-preview {
    color: var(--bs-secondary-color);
}

.questions-accordion .question-meta {
    margin-left: auto;
    padding-left: 1rem;
    white-space: nowrap;
}

.questions-accordion .accordion-body {
    padding: 1.5rem;
    background-color: #fff;
}

.question-footer {
    color: var(--bs-secondary-color);
}

.questions-accordion .accordion-item {
    border-left: 0;
    border-right: 0;
}

.questions-accordion .accordion-item:first-of-type {
    border-top: 0;
}

.questions-accordion .accordion-item:last-of-type {
    border-bottom: 0;
}

.question-title {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex: 1;
    min-width: 0;
}

.question-preview {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 100%;
    white-space: normal;
}

.question-meta {
    white-space: nowrap;
    flex-shrink: 0;
}

.ql-editor {
    min-height: 100px;
}

.accordion-body {
    background-color: var(--bs-gray-100);
}

.question-display, .answer-display {
    background-color: white;
    padding: 1rem !important;
    border-radius: 0.375rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.card-header {
    position: sticky;
    top: 0;
    z-index: 1020;
    background-color: var(--bs-white);
    border-bottom: 1px solid var(--bs-border-color);
}

@media (max-width: 768px) {
    .questions-accordion .accordion-button {
        padding: 0.5rem;
    }
    
    .question-title {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.25rem;
    }
    
    .question-meta {
        font-size: 0.75rem;
    }
    
    .accordion-body {
        padding: 1rem;
    }
    
    .question-actions {
        display: flex;
        gap: 0.5rem;
    }
    
    .answer-content .d-flex {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .form-check.form-switch {
        margin-bottom: 0.5rem;
    }
}

.rating-container {
    margin: 20px 0;
    text-align: center;
}

.stars {
    font-size: 24px;
    cursor: pointer;
    display: inline-block;
}

.star {
    color: #ddd;
    transition: color 0.2s;
}

.star.active {
    color: #ffd700;
}

.rating-text {
    margin-top: 5px;
    font-size: 14px;
    color: #666;
}
</style>

<script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>

<script>
// Add this at the top of your script section
const QUILL_TOOLBAR_OPTIONS = [
    ['bold', 'italic', 'underline', 'strike'],
    ['blockquote', 'code-block'],
    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
    [{ 'script': 'sub'}, { 'script': 'super' }],
    [{ 'color': [] }, { 'background': [] }],
    ['link', 'formula'],
    ['clean']
];

// AJAX utility function
const ajax = {
    post: async function(url, data) {
        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });
            return await response.json();
        } catch (error) {
            console.error('AJAX Error:', error);
            throw error;
        }
    }
};

// Simple Timer Implementation
const Timer = {
    topicId: <?php echo $topic_id; ?>,
    subject: '<?php echo $subject; ?>',
    display: document.getElementById('timerDisplay'),
    startBtn: document.getElementById('startTimer'),
    pauseBtn: document.getElementById('pauseTimer'),
    stopBtn: document.getElementById('stopTimer'),
    seconds: 0,
    interval: null,
    isRunning: false,

    formatTime(seconds) {
        const h = Math.floor(seconds / 3600);
        const m = Math.floor((seconds % 3600) / 60);
        const s = seconds % 60;
        return `${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
    },

    updateDisplay() {
        this.display.textContent = this.formatTime(this.seconds);
    },

    async sendRequest(action, seconds = 0) {
        try {
            const response = await fetch('/api/topics/timer_state.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    topic_id: this.topicId,
                    subject: this.subject,
                    action: action,
                    elapsed_seconds: seconds
                })
            });
            return await response.json();
        } catch (error) {
            console.error('Timer API Error:', error);
            return { success: false, error: error.message };
        }
    },

    start() {
        if (this.isRunning) return;
        
        this.sendRequest('start').then(data => {
            if (data.success) {
                this.isRunning = true;
                this.startBtn.style.display = 'none';
                this.pauseBtn.style.display = 'inline-block';
                this.pauseBtn.innerHTML = '<i class="fas fa-pause"></i>';
                this.stopBtn.disabled = false;
                
                this.interval = setInterval(() => {
                    this.seconds++;
                    this.updateDisplay();
                }, 1000);
            }
        });
    },

    pause() {
        if (!this.isRunning) return;
        
        this.sendRequest('pause', this.seconds).then(data => {
            if (data.success) {
                this.isRunning = false;
                clearInterval(this.interval);
                this.pauseBtn.innerHTML = '<i class="fas fa-play"></i>';
            }
        });
    },

    resume() {
        if (this.isRunning) return;
        
        this.sendRequest('resume').then(data => {
            if (data.success) {
                this.isRunning = true;
                this.pauseBtn.innerHTML = '<i class="fas fa-pause"></i>';
                
                this.interval = setInterval(() => {
                    this.seconds++;
                    this.updateDisplay();
                }, 1000);
            }
        });
    },

    stop() {
        if (!confirm('Are you sure you want to stop the timer? This will save your progress.')) {
            return;
        }
        
        const finalTime = this.seconds;
        
        // Send request to stop timer and update progress
        this.sendRequest('stop', this.seconds).then(data => {
            if (data.success) {
                this.isRunning = false;
                clearInterval(this.interval);
                
                // Hide all timer controls
                this.startBtn.style.display = 'none';
                this.pauseBtn.style.display = 'none';
                this.stopBtn.style.display = 'none';
                
                // Show completion message with formatted time
                const timeSpent = this.formatTime(finalTime);
                const messageDiv = document.createElement('div');
                messageDiv.className = 'alert alert-success mt-2';
                messageDiv.innerHTML = `
                    <i class="fas fa-check-circle me-2"></i>
                    Study session completed! You spent ${timeSpent} on this topic.
                `;
                
                // Insert message after the timer display
                this.display.parentNode.appendChild(messageDiv);
                
                // Update the display one last time to show total duration
                this.display.textContent = timeSpent;
                
                // Reload the page after a short delay
                setTimeout(() => {
                    location.reload();
                }, 3000);
            }
        });
    },

    async init() {
        const data = await this.sendRequest('get_state');
        if (data.success && data.timer) {
            this.seconds = parseInt(data.timer.elapsed_seconds);
            this.isRunning = data.timer.status === 'active';
            this.updateDisplay();
            
            if (this.isRunning) {
                this.startBtn.style.display = 'none';
                this.pauseBtn.style.display = 'inline-block';
                this.pauseBtn.innerHTML = '<i class="fas fa-pause"></i>';
                this.stopBtn.disabled = false;
                
                this.interval = setInterval(() => {
                    this.seconds++;
                    this.updateDisplay();
                }, 1000);
            } else if (this.seconds > 0) {
                this.startBtn.style.display = 'none';
                this.pauseBtn.style.display = 'inline-block';
                this.pauseBtn.innerHTML = '<i class="fas fa-play"></i>';
                this.stopBtn.disabled = false;
            }
        }
    }
};

// Initialize timer and attach event listeners
document.addEventListener('DOMContentLoaded', () => {
    Timer.init();
    
    Timer.startBtn.addEventListener('click', () => Timer.start());
    Timer.pauseBtn.addEventListener('click', () => Timer.isRunning ? Timer.pause() : Timer.resume());
    Timer.stopBtn.addEventListener('click', () => Timer.stop());
});

// Form submissions
document.getElementById('updateProgressForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const jsonData = {
        topic_id: <?php echo $topic_id; ?>,
        subject: '<?php echo $subject; ?>',
        status: formData.get('status'),
        confidence_level: formData.get('confidence_level'),
        notes: formData.get('notes')
    };
    
    fetch(this.action, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(jsonData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error updating progress: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error updating progress');
    });
});

['addNoteForm', 'addQuestionForm', 'addResourceForm'].forEach(formId => {
    document.getElementById(formId)?.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('subject', '<?php echo $subject; ?>');
        
        fetch(this.action, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error saving data');
        });
    });
});

// Notes functionality
document.addEventListener('DOMContentLoaded', function() {
    const editNotesBtn = document.getElementById('editNotesBtn');
    const cancelNotesBtn = document.getElementById('cancelNotesBtn');
    const saveNotesBtn = document.getElementById('saveNotesBtn');
    const notesDisplay = document.getElementById('notesDisplay');
    const notesEdit = document.getElementById('notesEdit');
    
    // Initialize Quill
    const quill = new Quill('#editor-container', {
        theme: 'snow',
        modules: {
            toolbar: [
                ['bold', 'italic', 'underline', 'strike'],
                ['blockquote', 'code-block'],
                [{ 'header': 1 }, { 'header': 2 }],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                [{ 'script': 'sub'}, { 'script': 'super' }],
                [{ 'color': [] }, { 'background': [] }],
                ['link', 'formula'],
                ['clean']
            ]
        },
        placeholder: 'Write your notes here...'
    });

    if (editNotesBtn && notesDisplay && notesEdit) {
        // Get initial content
        const initialContent = notesDisplay.querySelector('.notes-text')?.innerHTML || '';
        quill.root.innerHTML = initialContent;

        editNotesBtn.addEventListener('click', function() {
            notesDisplay.classList.add('d-none');
            notesEdit.classList.remove('d-none');
        });

        cancelNotesBtn.addEventListener('click', function() {
            notesDisplay.classList.remove('d-none');
            notesEdit.classList.add('d-none');
            // Restore original content
            quill.root.innerHTML = initialContent;
        });

        saveNotesBtn.addEventListener('click', async function() {
            const content = quill.root.innerHTML;
            try {
                const response = await fetch('/api/topics/update_notes.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        topic_id: <?php echo $topic['id']; ?>,
                        content: content
                    })
                });

                if (!response.ok) {
                    throw new Error('Failed to save notes');
                }

                const data = await response.json();
                if (data.success) {
                    // Update the display
                    const notesText = notesDisplay.querySelector('.notes-text');
                    if (notesText) {
                        notesText.innerHTML = content;
                    }
                    
                    // Update the last edited time
                    const lastEdited = notesDisplay.querySelector('.text-muted');
                    if (lastEdited) {
                        lastEdited.textContent = `Last edited: ${new Date().toLocaleString()}`;
                    }

                    // Switch back to display mode
                    notesDisplay.classList.remove('d-none');
                    notesEdit.classList.add('d-none');
                }
            } catch (error) {
                console.error('Error saving notes:', error);
                alert('Error saving notes. Please try again.');
            }
        });
    }
});

// Questions functionality
document.addEventListener('DOMContentLoaded', function() {
    let questionEditors = {};
    let answerEditors = {};
    
    // Initialize Add Question Modal editor
    const addQuestionEditor = new Quill('#addQuestionEditor', {
        theme: 'snow',
        modules: { toolbar: QUILL_TOOLBAR_OPTIONS }
    });

    const addAnswerEditor = new Quill('#addAnswerEditor', {
        theme: 'snow',
        modules: { toolbar: QUILL_TOOLBAR_OPTIONS }
    });

    // Initialize editors for existing questions
    function initializeAllEditors() {
        // Clear existing editors
        questionEditors = {};
        answerEditors = {};
        
        document.querySelectorAll('.question-item').forEach(questionItem => {
            const questionId = questionItem.dataset.questionId;
            initializeEditors(questionId);
            attachQuestionListeners(questionItem);
        });
    }

    // Initialize editors and attach listeners for a question
    function initializeEditors(questionId) {
        const questionContainer = document.querySelector(`#question${questionId} .question-editor`);
        if (questionContainer) {
            questionEditors[questionId] = new Quill(questionContainer, {
                theme: 'snow',
                modules: { toolbar: QUILL_TOOLBAR_OPTIONS }
            });
        }

        const answerContainer = document.querySelector(`#question${questionId} .answer-editor`);
        if (answerContainer) {
            answerEditors[questionId] = new Quill(answerContainer, {
                theme: 'snow',
                modules: { toolbar: QUILL_TOOLBAR_OPTIONS }
            });
        }
    }

    // Handle Add Question form submission
    const addQuestionForm = document.getElementById('addQuestionForm');
    if (addQuestionForm) {
        addQuestionForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const questionContent = addQuestionEditor.root.innerHTML.trim();
            const answerContent = addAnswerEditor.root.innerHTML.trim();
            
            if (!questionContent) {
                alert('Please enter a question.');
                return;
            }

            const submitButton = this.querySelector('button[type="submit"]');
            const originalText = submitButton.innerHTML;
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Adding...';

            try {
                const response = await fetch('/api/topics/add_question.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        topic_id: <?php echo $topic_id; ?>,
                        question: questionContent,
                        answer: answerContent || null
                    })
                });

                const result = await response.json();
                
                if (result.success) {
                    // Close modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('addQuestionModal'));
                    modal.hide();
                    
                    // Clear editors
                    addQuestionEditor.setContents([]);
                    addAnswerEditor.setContents([]);
                    
                    // Refresh the page to show new question
                    window.location.reload();
                } else {
                    throw new Error(result.message || 'Failed to add question');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred while saving. Please check the console for details.');
            } finally {
                submitButton.disabled = false;
                submitButton.innerHTML = originalText;
            }
        });
    }

    // Initialize all editors on page load
    initializeAllEditors();

    // Make functions available globally
    window.questionFunctions = {
        toggleQuestionEdit,
        toggleAnswerEdit,
        saveQuestion,
        saveAnswer,
        deleteQuestion,
        updateAnswerStatus
    };

    // Attach event listeners to question elements
    function attachQuestionListeners(questionItem) {
        const questionId = questionItem.dataset.questionId;
        
        // Remove any existing listeners first
        const newQuestionItem = questionItem.cloneNode(true);
        questionItem.parentNode.replaceChild(newQuestionItem, questionItem);
        
        // Question edit handlers
        const questionEditBtn = newQuestionItem.querySelector('.edit-question-btn');
        const cancelQuestionEditBtn = newQuestionItem.querySelector('.cancel-edit-btn');
        const saveQuestionBtn = newQuestionItem.querySelector('.save-question-btn');
        
        questionEditBtn?.addEventListener('click', () => toggleQuestionEdit(questionId, true));
        cancelQuestionEditBtn?.addEventListener('click', () => toggleQuestionEdit(questionId, false));
        saveQuestionBtn?.addEventListener('click', () => saveQuestion(questionId));

        // Answer edit handlers
        const answerEditBtn = newQuestionItem.querySelector('.edit-answer-btn');
        const cancelAnswerEditBtn = newQuestionItem.querySelector('.cancel-answer-edit-btn');
        const saveAnswerBtn = newQuestionItem.querySelector('.save-answer-btn');
        
        answerEditBtn?.addEventListener('click', () => toggleAnswerEdit(questionId, true));
        cancelAnswerEditBtn?.addEventListener('click', () => toggleAnswerEdit(questionId, false));
        saveAnswerBtn?.addEventListener('click', () => saveAnswer(questionId));

        // Delete question handler
        const deleteBtn = newQuestionItem.querySelector('.delete-question-btn');
        deleteBtn?.addEventListener('click', () => deleteQuestion(questionId));

        // Add answer status toggle handler
        const answerStatusToggle = newQuestionItem.querySelector('.answer-status-toggle');
        answerStatusToggle?.addEventListener('change', () => updateAnswerStatus(questionId, answerStatusToggle.checked));
    }

    // Toggle question edit mode
    function toggleQuestionEdit(questionId, show) {
        const questionItem = document.querySelector(`[data-question-id="${questionId}"]`);
        const displayEl = questionItem.querySelector('.question-display');
        const editEl = questionItem.querySelector('.question-edit');
        
        if (show) {
            const content = displayEl.innerHTML;
            if (questionEditors[questionId]) {
                questionEditors[questionId].root.innerHTML = content;
                displayEl.classList.add('d-none');
                editEl.classList.remove('d-none');
            }
        } else {
            displayEl.classList.remove('d-none');
            editEl.classList.add('d-none');
        }
    }

    // Toggle answer edit mode
    function toggleAnswerEdit(questionId, show) {
        const questionItem = document.querySelector(`[data-question-id="${questionId}"]`);
        const displayEl = questionItem.querySelector('.answer-display');
        const editEl = questionItem.querySelector('.answer-edit');
        
        if (show) {
            const content = displayEl.innerHTML;
            if (answerEditors[questionId]) {
                answerEditors[questionId].root.innerHTML = content;
                displayEl.classList.add('d-none');
                editEl.classList.remove('d-none');
            }
        } else {
            displayEl.classList.remove('d-none');
            editEl.classList.add('d-none');
        }
    }

    // Save question changes
    async function saveQuestion(questionId) {
        if (!questionEditors[questionId]) return;
        
        const content = questionEditors[questionId].root.innerHTML.trim();
        
        if (!content) {
            alert('Question cannot be empty');
            return;
        }

        const questionItem = document.querySelector(`[data-question-id="${questionId}"]`);
        const saveBtn = questionItem.querySelector('.save-question-btn');
        const originalText = saveBtn.innerHTML;
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';

        try {
            const response = await fetch('/api/topics/update_question.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    question_id: questionId,
                    question: content
                })
            });

            const result = await response.json();
            
            if (result.success) {
                const displayEl = questionItem.querySelector('.question-display');
                displayEl.innerHTML = content;
                
                // Update preview text
                const previewEl = questionItem.querySelector('.question-preview');
                const preview = content.replace(/<[^>]*>/g, '');
                previewEl.textContent = preview.length > 100 ? preview.substring(0, 100) + '...' : preview;
                
                toggleQuestionEdit(questionId, false);
            } else {
                throw new Error(result.message || 'Failed to update question');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('An error occurred while saving. Please check the console for details.');
        } finally {
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalText;
        }
    }

    // Save answer changes
    async function saveAnswer(questionId) {
        if (!answerEditors[questionId]) return;
        
        const content = answerEditors[questionId].root.innerHTML.trim();
        
        const questionItem = document.querySelector(`[data-question-id="${questionId}"]`);
        const saveBtn = questionItem.querySelector('.save-answer-btn');
        const originalText = saveBtn.innerHTML;
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';

        try {
            const response = await fetch('/api/topics/update_answer.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    question_id: questionId,
                    answer: content || null
                })
            });

            const result = await response.json();
            
            if (result.success) {
                const displayEl = questionItem.querySelector('.answer-display');
                displayEl.innerHTML = content || '<p class="text-muted">No answer provided yet.</p>';
                toggleAnswerEdit(questionId, false);
            } else {
                throw new Error(result.message || 'Failed to update answer');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('An error occurred while saving. Please check the console for details.');
        } finally {
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalText;
        }
    }

    // Delete question
    async function deleteQuestion(questionId) {
        if (!confirm('Are you sure you want to delete this question?')) {
            return;
        }

        try {
            const response = await fetch('/api/topics/delete_question.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ question_id: questionId })
            });

            if (!response.ok) {
                throw new Error('Failed to delete question');
            }

            const result = await response.json();
            
            if (result.success) {
                const questionItem = document.querySelector(`[data-question-id="${questionId}"]`);
                questionItem.remove();
                
                // Update question count
                const countEl = document.querySelector('.card-header .badge');
                const currentCount = parseInt(countEl.textContent);
                countEl.textContent = `${currentCount - 1} Questions`;
                
                // Show "no questions" message if this was the last question
                if (currentCount - 1 === 0) {
                    const accordionEl = document.querySelector('.questions-accordion');
                    accordionEl.innerHTML = `
                        <div class="p-4">
                            <p class="text-muted mb-0">No practice questions added yet.</p>
                        </div>
                    `;
                }
            } else {
                throw new Error(result.message || 'Failed to delete question');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Failed to delete question. Please try again.');
        }
    }

    // Add new function to handle answer status updates
    async function updateAnswerStatus(questionId, isCorrect) {
        try {
            const response = await fetch('/api/topics/update_answer.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    question_id: questionId,
                    is_correct: isCorrect
                })
            });

            if (!response.ok) {
                throw new Error('Failed to update answer status');
            }

            const result = await response.json();
            
            if (result.success) {
                const questionItem = document.querySelector(`[data-question-id="${questionId}"]`);
                const metaEl = questionItem.querySelector('.question-meta');
                
                if (isCorrect) {
                    metaEl.innerHTML = '<i class="fas fa-check-circle text-success me-1"></i>Answered Correctly';
                } else {
                    metaEl.innerHTML = '<i class="fas fa-circle text-muted me-1"></i>Not Answered';
                }
            } else {
                throw new Error(result.message || 'Failed to update answer status');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Failed to update answer status. Please try again.');
        }
    }
});

// Add this to your existing JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Handle answer collapse events
    document.querySelectorAll('.show-answer-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const isExpanded = this.getAttribute('aria-expanded') === 'true';
            this.querySelector('.answer-icon').style.transform = isExpanded ? 'rotate(0deg)' : 'rotate(90deg)';
            this.innerHTML = isExpanded ? 
                '<i class="fas fa-chevron-right me-2 answer-icon"></i>Show Answer' :
                '<i class="fas fa-chevron-right me-2 answer-icon"></i>Hide Answer';
        });
    });
});

document.addEventListener('DOMContentLoaded', function() {
    const stars = document.querySelectorAll('.rating-stars .star');
    const confidenceInput = document.getElementById('confidenceInput');
    const statusSelect = document.querySelector('select[name="status"]');
    
    // Function to update stars interactivity based on status
    function updateStarsInteractivity() {
        const isNotStarted = statusSelect.value === 'not_started';
        stars.forEach(star => {
            if (isNotStarted) {
                star.style.opacity = '0.5';
                star.style.cursor = 'not-allowed';
                star.removeEventListener('click', handleStarClick);
            } else {
                star.style.opacity = '1';
                star.style.cursor = 'pointer';
                star.addEventListener('click', handleStarClick);
            }
        });
        
        // Reset confidence to 0 if status is not started
        if (isNotStarted) {
            confidenceInput.value = '0';
            stars.forEach(star => star.classList.remove('active'));
        }
    }
    
    // Handle star click
    function handleStarClick() {
        const rating = this.dataset.rating;
        confidenceInput.value = rating;
        stars.forEach(s => {
            s.classList.toggle('active', s.dataset.rating <= rating);
        });
    }
    
    // Initial setup
    updateStarsInteractivity();
    
    // Update stars when status changes
    statusSelect.addEventListener('change', updateStarsInteractivity);
});
</script>

<!-- Toast notifications -->
<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="successToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header bg-success text-white">
            <i class="fas fa-check-circle me-2"></i>
            <strong class="me-auto">Success</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body">
            Notes saved successfully!
        </div>
    </div>

    <div id="errorToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header bg-danger text-white">
            <i class="fas fa-exclamation-circle me-2"></i>
            <strong class="me-auto">Error</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body">
            Failed to save notes. Please try again.
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?> 