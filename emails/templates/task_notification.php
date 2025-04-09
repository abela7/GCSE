<?php
require_once __DIR__ . '/email_template.php';

class TaskNotification extends EmailTemplate {
    public function generateEmail($data) {
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Task Reminder</title>
            <style>
                body {
                    font-family: "Segoe UI", Arial, sans-serif;
                    line-height: 1.6;
                    color: #333;
                    margin: 0;
                    padding: 0;
                    background-color: #222;
                }
                .container {
                    max-width: 600px;
                    margin: 0 auto;
                    padding: 0;
                    background-color: #1e1e1e;
                    color: #ffffff;
                }
                .header {
                    background-color: rgb(168, 142, 64);
                    color: white;
                    padding: 20px;
                    text-align: center;
                }
                .header h1 {
                    margin: 0;
                    font-size: 22px;
                    font-weight: 600;
                    word-break: break-word;
                }
                .task-time {
                    font-size: 16px;
                    margin-top: 10px;
                    font-weight: 500;
                    display: inline-block;
                    padding: 5px 15px;
                    background-color: rgba(0, 0, 0, 0.2);
                    border-radius: 20px;
                }
                .section {
                    margin: 0;
                    padding: 15px;
                }
                .section-title {
                    font-size: 18px;
                    font-weight: 600;
                    color: #ffffff;
                    margin: 10px 0;
                    padding-bottom: 8px;
                    border-bottom: 1px solid #333;
                }
                .task-card {
                    background-color: #2a2a2a;
                    border-left: 4px solid #4a90e2;
                    border-radius: 8px;
                    padding: 15px;
                    margin-bottom: 15px;
                }
                .task-card.current {
                    background-color: #222;
                    border-left: 4px solid rgb(168, 142, 64);
                    border-radius: 8px;
                    padding: 15px;
                    margin-bottom: 15px;
                }
                .task-card.overdue {
                    border-left-color: #e53935;
                }
                .task-title {
                    font-size: 18px;
                    font-weight: 600;
                    margin-bottom: 6px;
                    color: #ffffff;
                    word-break: break-word;
                }
                .task-description {
                    font-size: 15px;
                    margin: 8px 0;
                    color: #ccc;
                    line-height: 1.4;
                    word-break: break-word;
                }
                .task-details {
                    list-style: none;
                    padding: 0;
                    margin: 12px 0 0 0;
                    color: #aaa;
                }
                .task-detail-item {
                    padding: 8px 0;
                    border-bottom: 1px solid #333;
                    font-size: 14px;
                }
                .task-detail-item:last-child {
                    border-bottom: none;
                }
                .task-detail-item:before {
                    content: "•";
                    margin-right: 8px;
                    color: rgb(168, 142, 64);
                }
                .priority-high {
                    color: #f44336;
                    font-weight: bold;
                }
                .priority-medium {
                    color: #ff9800;
                }
                .priority-low {
                    color: #4caf50;
                }
                .action-button {
                    display: block;
                    width: 100%;
                    color: white;
                    padding: 12px;
                    text-decoration: none;
                    border-radius: 6px;
                    font-weight: 600;
                    text-align: center;
                    background-color: #43a047;
                    margin-top: 15px;
                    font-size: 16px;
                    border: none;
                }
                .footer {
                    text-align: center;
                    padding: 15px;
                    font-size: 12px;
                    color: #888;
                    border-top: 1px solid #333;
                }
                .app-name {
                    font-weight: bold;
                    color: rgb(168, 142, 64);
                }
                @media only screen and (max-width: 600px) {
                    body {
                        padding: 0;
                        margin: 0;
                    }
                    .header h1 {
                        font-size: 20px;
                    }
                    .task-time {
                        font-size: 14px;
                        padding: 4px 12px;
                    }
                    .task-title {
                        font-size: 16px;
                    }
                    .task-description {
                        font-size: 14px;
                    }
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>' . htmlspecialchars($data['current_task']['title']) . '</h1>
                    <div class="task-time">Due at: ' . htmlspecialchars($data['current_task']['due_time']) . '</div>
                </div>
                
                <div class="section">
                    <div class="task-card current">
                        <div class="task-title">It\'s time to complete: ' . htmlspecialchars($data['current_task']['title']) . '</div>
                        ' . ($data['current_task']['description'] ? '<div class="task-description">' . htmlspecialchars($data['current_task']['description']) . '</div>' : '') . '
                        <ul class="task-details">
                            <li class="task-detail-item priority-' . htmlspecialchars($data['current_task']['priority']) . '">
                                Priority: ' . ucfirst(htmlspecialchars($data['current_task']['priority'])) . '
                            </li>
                            ' . ($data['current_task']['due_time'] ? '<li class="task-detail-item">Due Time: ' . htmlspecialchars($data['current_task']['due_time']) . '</li>' : '') . '
                            ' . ($data['current_task']['estimated_duration'] ? '<li class="task-detail-item">Estimated Duration: ' . htmlspecialchars($data['current_task']['estimated_duration']) . ' minutes</li>' : '') . '
                        </ul>
                        <a href="https://abel.abuneteklehaymanot.org/pages/tasks/index.php?action=complete&task_id=' . htmlspecialchars($data['current_task']['id']) . '" class="action-button">Mark Complete</a>
                    </div>
                </div>
                
                ' . (isset($data['upcoming_tasks']) && count($data['upcoming_tasks']) > 0 ? '
                <div class="section">
                    <div class="section-title">Upcoming Tasks Today</div>
                    ' . $this->renderTaskList($data['upcoming_tasks']) . '
                </div>' : '') . '
                
                ' . (isset($data['overdue_tasks']) && count($data['overdue_tasks']) > 0 ? '
                <div class="section">
                    <div class="section-title">Overdue Tasks</div>
                    ' . $this->renderTaskList($data['overdue_tasks'], 'overdue') . '
                </div>' : '') . '
                
                <div class="footer">
                    <p>This is an automated notification from <span class="app-name">Amha-Silassie Study App</span></p>
                    <p>© ' . date('Y') . ' Amha-Silassie. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>';
    }
    
    private function renderTaskList($tasks, $type = '') {
        $output = '';
        foreach ($tasks as $task) {
            $output .= '
            <div class="task-card ' . $type . '">
                <div class="task-title">' . htmlspecialchars($task['title']) . '</div>
                ' . ($task['description'] ? '<div class="task-description">' . htmlspecialchars($task['description']) . '</div>' : '') . '
                <ul class="task-details">
                    <li class="task-detail-item priority-' . htmlspecialchars($task['priority']) . '">
                        Priority: ' . ucfirst(htmlspecialchars($task['priority'])) . '
                    </li>
                    ' . ($task['due_time'] ? '<li class="task-detail-item">Due Time: ' . htmlspecialchars($task['due_time']) . '</li>' : '') . '
                    ' . ($type == 'overdue' && isset($task['overdue_text']) ? '<li class="task-detail-item" style="color: #f44336;"><strong>' . htmlspecialchars($task['overdue_text']) . '</strong></li>' : '') . '
                    ' . ($type != 'overdue' && isset($task['upcoming_text']) ? '<li class="task-detail-item" style="color: #4caf50;"><strong>' . htmlspecialchars($task['upcoming_text']) . '</strong></li>' : '') . '
                    ' . ($task['estimated_duration'] ? '<li class="task-detail-item">Estimated Duration: ' . htmlspecialchars($task['estimated_duration']) . ' minutes</li>' : '') . '
                </ul>
            </div>';
        }
        return $output;
    }
}
?> 