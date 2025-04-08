<?php
require_once __DIR__ . '/email_template.php';

class MorningBriefing extends EmailTemplate {
    protected $styles = '
        <style>
            body { 
                font-family: Arial, sans-serif;
                line-height: 1.6;
                color: #333;
                background: #f8f9fa;
            }
            .container {
                max-width: 600px;
                margin: 0 auto;
                padding: 20px;
                background: #ffffff;
                border-radius: 10px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            .header {
                background: #4a90e2;
                color: white;
                padding: 30px;
                text-align: center;
                border-radius: 10px 10px 0 0;
                background: linear-gradient(135deg, #4a90e2 0%, #357abd 100%);
            }
            .header h1 {
                margin: 0;
                font-size: 28px;
                font-weight: 600;
            }
            .header .greeting {
                font-size: 18px;
                margin-top: 10px;
                opacity: 0.9;
            }
            .content {
                padding: 30px;
                background: #fff;
            }
            .section {
                margin: 30px 0;
                background: #fff;
                border-radius: 8px;
                overflow: hidden;
            }
            .section-title {
                background: #f8f9fa;
                padding: 15px 20px;
                border-bottom: 2px solid #e9ecef;
                color: #2c3e50;
                font-size: 18px;
                font-weight: 600;
                display: flex;
                align-items: center;
                gap: 10px;
            }
            .task-list {
                list-style: none;
                padding: 0;
                margin: 0;
            }
            .task-item {
                padding: 20px;
                border-bottom: 1px solid #e9ecef;
                transition: background-color 0.2s;
            }
            .task-item:hover {
                background-color: #f8f9fa;
            }
            .task-item:last-child {
                border-bottom: none;
            }
            .task-title {
                font-size: 16px;
                font-weight: 600;
                color: #2c3e50;
                margin-bottom: 5px;
                display: flex;
                align-items: center;
                gap: 8px;
            }
            .task-category {
                font-size: 12px;
                padding: 3px 8px;
                border-radius: 12px;
                background: #e9ecef;
                color: #6c757d;
            }
            .task-description {
                color: #6c757d;
                font-size: 14px;
                margin: 8px 0;
                line-height: 1.5;
            }
            .task-meta {
                display: flex;
                align-items: center;
                gap: 15px;
                font-size: 13px;
                color: #6c757d;
            }
            .task-time {
                display: flex;
                align-items: center;
                gap: 5px;
            }
            .task-priority {
                display: flex;
                align-items: center;
                gap: 5px;
            }
            .priority-high {
                color: #dc3545;
            }
            .priority-medium {
                color: #ffc107;
            }
            .priority-low {
                color: #28a745;
            }
            .highlight {
                background: #fff3cd;
                padding: 15px;
                border-radius: 8px;
                margin: 20px 0;
                border-left: 4px solid #ffc107;
            }
            .quote {
                font-style: italic;
                color: #6c757d;
                text-align: center;
                margin: 20px 0;
                padding: 15px;
                background: #f8f9fa;
                border-radius: 8px;
            }
            .footer {
                text-align: center;
                padding: 20px;
                font-size: 12px;
                color: #6c757d;
                border-top: 1px solid #e9ecef;
            }
            .emoji {
                font-size: 24px;
            }
        </style>
    ';

    public function generateEmail($data) {
        $title = "Good Morning Abel 🌞";
        
        // Generate tasks section
        $tasksContent = '';
        if (!empty($data['tasks'])) {
            $tasksContent .= '
            <div class="section">
                <div class="section-title">
                    <span class="emoji">📋</span>
                    Today\'s Tasks
                </div>
                <ul class="task-list">';
            
            foreach ($data['tasks'] as $task) {
                $priorityClass = 'priority-' . strtolower($task['priority']);
                $tasksContent .= '
                <li class="task-item">
                    <div class="task-title">
                        ' . $task['title'] . '
                        <span class="task-category">' . $task['category_name'] . '</span>
                    </div>
                    ' . ($task['description'] ? '<div class="task-description">' . $task['description'] . '</div>' : '') . '
                    <div class="task-meta">
                        <div class="task-time">
                            <span class="emoji">⏰</span>
                            ' . $task['due_time'] . '
                        </div>
                        <div class="task-priority ' . $priorityClass . '">
                            <span class="emoji">' . ($task['priority'] == 'high' ? '🔥' : ($task['priority'] == 'medium' ? '⭐' : '🌱')) . '</span>
                            ' . ucfirst($task['priority']) . ' Priority
                        </div>
                    </div>
                </li>';
            }
            
            $tasksContent .= '</ul></div>';
        }
        
        // Generate habits section
        $habitsContent = '';
        if (!empty($data['habits'])) {
            $habitsContent .= '
            <div class="section">
                <div class="section-title">
                    <span class="emoji">🔄</span>
                    Today\'s Habits
                </div>
                <ul class="task-list">';
            
            foreach ($data['habits'] as $habit) {
                $habitsContent .= '
                <li class="task-item">
                    <div class="task-title">
                        ' . $habit['title'] . '
                        <span class="task-category">' . $habit['category_name'] . '</span>
                    </div>
                    ' . ($habit['description'] ? '<div class="task-description">' . $habit['description'] . '</div>' : '') . '
                    <div class="task-meta">
                        <div class="task-time">
                            <span class="emoji">⏰</span>
                            ' . $habit['time'] . '
                        </div>
                    </div>
                </li>';
            }
            
            $habitsContent .= '</ul></div>';
        }
        
        // Generate overdue section
        $overdueContent = '';
        if (!empty($data['overdue'])) {
            $overdueContent .= '
            <div class="section">
                <div class="section-title">
                    <span class="emoji">⚠️</span>
                    Overdue Tasks
                </div>
                <div class="highlight">
                    These tasks need your attention:
                </div>
                <ul class="task-list">';
            
            foreach ($data['overdue'] as $task) {
                $overdueContent .= '
                <li class="task-item">
                    <div class="task-title">
                        ' . $task['title'] . '
                        <span class="task-category">' . $task['category_name'] . '</span>
                    </div>
                    ' . ($task['description'] ? '<div class="task-description">' . $task['description'] . '</div>' : '') . '
                    <div class="task-meta">
                        <div class="task-time">
                            <span class="emoji">⏰</span>
                            ' . $task['due_time'] . '
                        </div>
                    </div>
                </li>';
            }
            
            $overdueContent .= '</ul></div>';
        }
        
        // Combine all sections
        $content = '
        <div class="greeting">
            <p>I hope you\'re ready for a productive day! Here\'s what\'s on your schedule:</p>
        </div>
        ' . $tasksContent . '
        ' . $habitsContent . '
        ' . $overdueContent . '
        <div class="section">
            <a href="https://abel.abuneteklehaymanot.org/dashboard.php" class="btn">View Full Schedule</a>
        </div>
        <div class="quote">
            <span class="emoji">💭</span>
            "The future depends on what you do today." - Mahatma Gandhi
        </div>';
        
        return $this->getBaseTemplate($title, $content);
    }
}
?> 