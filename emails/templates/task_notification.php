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
                    font-family: Arial, sans-serif;
                    line-height: 1.6;
                    color: #333;
                    margin: 0;
                    padding: 0;
                    background-color: #f5f5f5;
                }
                .container {
                    max-width: 600px;
                    margin: 0 auto;
                    padding: 20px;
                    background-color: #ffffff;
                    border-radius: 10px;
                    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
                }
                .header {
                    background-color: rgb(168, 142, 64);
                    color: white;
                    padding: 25px 20px;
                    text-align: center;
                    border-radius: 10px 10px 0 0;
                }
                .header h1 {
                    margin: 0;
                    font-size: 24px;
                    font-weight: 600;
                }
                .task-time {
                    font-size: 18px;
                    margin-top: 10px;
                    opacity: 0.9;
                    font-weight: bold;
                }
                .section {
                    margin: 25px 0;
                    padding: 0 20px;
                }
                .section-title {
                    font-size: 20px;
                    font-weight: 600;
                    color: #2d3436;
                    margin-bottom: 15px;
                    padding-bottom: 10px;
                    border-bottom: 2px solid #f1f3f5;
                }
                .task-card {
                    background-color: #f8f9fa;
                    border-left: 4px solid #4a90e2;
                    border-radius: 8px;
                    padding: 15px;
                    margin-bottom: 15px;
                    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
                }
                .task-card.current {
                    background-color: #e8f4fd;
                    border-left: 4px solid #1e88e5;
                    border-radius: 8px;
                    padding: 20px;
                    margin-bottom: 20px;
                }
                .task-card.overdue {
                    border-left-color: #e53935;
                }
                .task-title {
                    font-size: 18px;
                    font-weight: 600;
                    margin-bottom: 8px;
                    color: #2d3436;
                }
                .task-card.current .task-title {
                    font-size: 20px;
                }
                .task-description {
                    font-size: 15px;
                    margin: 8px 0;
                    color: #555;
                }
                .task-details {
                    font-size: 14px;
                    color: #666;
                    margin-top: 8px;
                }
                .priority-high {
                    color: #e53935;
                    font-weight: bold;
                }
                .priority-medium {
                    color: #fb8c00;
                }
                .priority-low {
                    color: #43a047;
                }
                .action-button {
                    display: inline-block;
                    background-color: #4a90e2;
                    color: white;
                    padding: 10px 20px;
                    text-decoration: none;
                    border-radius: 5px;
                    font-weight: 600;
                    margin-top: 15px;
                    margin-right: 10px;
                }
                .action-button:hover {
                    background-color: #357abd;
                }
                .complete-button {
                    background-color: #43a047;
                }
                .complete-button:hover {
                    background-color: #388e3c;
                }
                .footer {
                    text-align: center;
                    padding: 20px;
                    font-size: 12px;
                    color: #666;
                    border-top: 1px solid #e0e0e0;
                }
                @media only screen and (max-width: 600px) {
                    .container {
                        width: 100%;
                        border-radius: 0;
                    }
                    .header {
                        border-radius: 0;
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
                        <div class="task-title">Time to Complete: ' . htmlspecialchars($data['current_task']['title']) . '</div>
                        ' . ($data['current_task']['description'] ? '<div class="task-description">' . htmlspecialchars($data['current_task']['description']) . '</div>' : '') . '
                        <div class="task-details">
                            <span class="priority-' . htmlspecialchars($data['current_task']['priority']) . '">
                                Priority: ' . ucfirst(htmlspecialchars($data['current_task']['priority'])) . '
                            </span>
                            ' . ($data['current_task']['estimated_duration'] ? ' | Duration: ' . htmlspecialchars($data['current_task']['estimated_duration']) . ' min' : '') . '
                            ' . ($data['current_task']['category_name'] ? ' | Category: ' . htmlspecialchars($data['current_task']['category_name']) : '') . '
                        </div>
                        <div>
                            <a href="' . htmlspecialchars($data['app_url']) . '/task_manager.php?action=complete&task_id=' . htmlspecialchars($data['current_task']['id']) . '" class="action-button complete-button">Mark Complete</a>
                            <a href="' . htmlspecialchars($data['app_url']) . '/task_manager.php?task_id=' . htmlspecialchars($data['current_task']['id']) . '" class="action-button">View Details</a>
                        </div>
                    </div>
                </div>
                
                ' . (count($data['overdue_tasks']) > 0 ? '
                <div class="section">
                    <div class="section-title">Overdue Tasks</div>
                    ' . $this->renderTaskList($data['overdue_tasks'], 'overdue') . '
                </div>' : '') . '
                
                ' . (count($data['upcoming_tasks']) > 0 ? '
                <div class="section">
                    <div class="section-title">Other Tasks Today</div>
                    ' . $this->renderTaskList($data['upcoming_tasks']) . '
                </div>' : '') . '
                
                <div class="footer">
                    <p>This is an automated notification from Amha-Silassie Study App</p>
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
                <div class="task-details">
                    <span class="priority-' . htmlspecialchars($task['priority']) . '">
                        Priority: ' . ucfirst(htmlspecialchars($task['priority'])) . '
                    </span>
                    ' . ($task['due_time'] ? ' | Due: ' . htmlspecialchars($task['due_time']) : '') . '
                    ' . ($task['estimated_duration'] ? ' | Duration: ' . htmlspecialchars($task['estimated_duration']) . ' min' : '') . '
                    ' . ($task['category_name'] ? ' | Category: ' . htmlspecialchars($task['category_name']) : '') . '
                </div>
            </div>';
        }
        return $output;
    }
}
?> 