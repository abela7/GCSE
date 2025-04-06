<?php
// Set page title
$page_title = "Progress Tracking";

// Include database connection
require_once '../config/db_connect.php';

// Get overall progress
$overall_query = "SELECT * FROM overall_progress WHERE id = 1";
$overall_result = $conn->query($overall_query);
$overall = $overall_result->fetch_assoc();

// Get subject-wise progress
$subjects_query = "SELECT s.*, 
    (SELECT COUNT(DISTINCT t.id) 
     FROM math_topics t 
     JOIN math_subsections sub ON t.subsection_id = sub.id 
     JOIN math_sections sec ON sub.section_id = sec.id 
     WHERE s.id = 2) + 
    (SELECT COUNT(DISTINCT t.id) 
     FROM eng_topics t 
     JOIN eng_subsections sub ON t.subsection_id = sub.id 
     JOIN eng_sections sec ON sub.section_id = sec.id 
     WHERE s.id = 1) as total_topics,
    (SELECT COUNT(DISTINCT t.id) 
     FROM math_topics t 
     JOIN math_subsections sub ON t.subsection_id = sub.id 
     JOIN math_sections sec ON sub.section_id = sec.id 
     JOIN topic_progress tp ON t.id = tp.topic_id 
     WHERE s.id = 2 AND tp.status = 'completed') +
    (SELECT COUNT(DISTINCT t.id) 
     FROM eng_topics t 
     JOIN eng_subsections sub ON t.subsection_id = sub.id 
     JOIN eng_sections sec ON sub.section_id = sec.id 
     JOIN eng_topic_progress tp ON t.id = tp.topic_id 
     WHERE s.id = 1 AND tp.status = 'completed') as completed_topics
FROM subjects s";
$subjects_result = $conn->query($subjects_query);

// Get study time data for the last 7 days
$study_time_query = "SELECT 
    DATE(start_time) as study_date,
    SUM(duration_seconds) as total_seconds
FROM study_time_tracking 
WHERE start_time >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
GROUP BY DATE(start_time)
ORDER BY study_date";
$study_time_result = $conn->query($study_time_query);

// Get subject-wise study time
$subject_study_query = "SELECT 
    s.name as subject_name,
    SUM(st.duration_seconds) as total_seconds
FROM study_time_tracking st
JOIN math_topics t ON st.topic_id = t.id
JOIN math_subsections sub ON t.subsection_id = sub.id
JOIN math_sections sec ON sub.section_id = sec.id
JOIN subjects s ON s.id = 2
GROUP BY s.name
UNION ALL
SELECT 
    s.name as subject_name,
    SUM(st.duration_seconds) as total_seconds
FROM eng_study_time_tracking st
JOIN eng_topics t ON st.topic_id = t.id
JOIN eng_subsections sub ON t.subsection_id = sub.id
JOIN eng_sections sec ON sub.section_id = sec.id
JOIN subjects s ON s.id = 1
GROUP BY s.name";
$subject_study_result = $conn->query($subject_study_query);

// Include header
include '../includes/header.php';
?>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
.progress-card {
    border: none;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: transform 0.2s;
}
.progress-card:hover {
    transform: translateY(-2px);
}
.chart-container {
    position: relative;
    height: 300px;
    margin-bottom: 2rem;
}
.stat-value {
    font-size: 2rem;
    font-weight: 600;
}
.stat-label {
    color: #6c757d;
    font-size: 0.9rem;
}
</style>

<!-- Overall Progress Stats -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card progress-card">
            <div class="card-body text-center">
                <div class="stat-value"><?php echo round($overall['progress_percentage']); ?>%</div>
                <div class="stat-label">Overall Progress</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card progress-card">
            <div class="card-body text-center">
                <div class="stat-value"><?php echo $overall['completed_topics']; ?></div>
                <div class="stat-label">Topics Completed</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card progress-card">
            <div class="card-body text-center">
                <div class="stat-value"><?php echo round($overall['total_study_time'] / 3600, 1); ?>h</div>
                <div class="stat-label">Total Study Time</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card progress-card">
            <div class="card-body text-center">
                <div class="stat-value"><?php echo $overall['total_topics']; ?></div>
                <div class="stat-label">Total Topics</div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row">
    <!-- Subject Progress Chart -->
    <div class="col-md-6">
        <div class="card progress-card">
            <div class="card-body">
                <h5 class="card-title mb-4">Subject Progress</h5>
                <div class="chart-container">
                    <canvas id="subjectProgressChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Study Time Distribution -->
    <div class="col-md-6">
        <div class="card progress-card">
            <div class="card-body">
                <h5 class="card-title mb-4">Study Time Distribution</h5>
                <div class="chart-container">
                    <canvas id="studyTimeChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Daily Study Time Chart -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card progress-card">
            <div class="card-body">
                <h5 class="card-title mb-4">Daily Study Time (Last 7 Days)</h5>
                <div class="chart-container">
                    <canvas id="dailyStudyChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Prepare data for charts
const subjectData = {
    labels: [],
    datasets: [{
        data: [],
        backgroundColor: []
    }]
};

const studyTimeData = {
    labels: [],
    datasets: [{
        data: [],
        backgroundColor: []
    }]
};

const dailyStudyData = {
    labels: [],
    datasets: [{
        label: 'Study Time (hours)',
        data: [],
        borderColor: '#cdaf56',
        tension: 0.1
    }]
};

<?php
// Process subject progress data
while($subject = $subjects_result->fetch_assoc()) {
    $progress = $subject['total_topics'] > 0 ? 
        round(($subject['completed_topics'] / $subject['total_topics']) * 100) : 0;
    echo "subjectData.labels.push('" . $subject['name'] . "');\n";
    echo "subjectData.datasets[0].data.push(" . $progress . ");\n";
    echo "subjectData.datasets[0].backgroundColor.push('" . $subject['color'] . "');\n";
}

// Process study time data
while($study = $subject_study_result->fetch_assoc()) {
    echo "studyTimeData.labels.push('" . $study['subject_name'] . "');\n";
    echo "studyTimeData.datasets[0].data.push(" . round($study['total_seconds'] / 3600, 1) . ");\n";
    echo "studyTimeData.datasets[0].backgroundColor.push('" . 
        ($study['subject_name'] === 'Math' ? '#007bff' : '#28a745') . "');\n";
}

// Process daily study time data
while($day = $study_time_result->fetch_assoc()) {
    echo "dailyStudyData.labels.push('" . date('M d', strtotime($day['study_date'])) . "');\n";
    echo "dailyStudyData.datasets[0].data.push(" . round($day['total_seconds'] / 3600, 1) . ");\n";
}
?>

// Create charts
new Chart(document.getElementById('subjectProgressChart'), {
    type: 'doughnut',
    data: subjectData,
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});

new Chart(document.getElementById('studyTimeChart'), {
    type: 'pie',
    data: studyTimeData,
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});

new Chart(document.getElementById('dailyStudyChart'), {
    type: 'line',
    data: dailyStudyData,
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'Hours'
                }
            }
        }
    }
});
</script>

<?php
include '../includes/footer.php';
close_connection($conn);
?>
