<?php
require_once __DIR__ . '/email_template.php';

class HabitNotification extends EmailTemplate {
    public function generateEmail($data) {
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Habit Reminder</title>
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
                .habit-time {
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
                .habit-card {
                    background-color: #2a2a2a;
                    border-left: 4px solid #4a90e2;
                    border-radius: 8px;
                    padding: 15px;
                    margin-bottom: 15px;
                }
                .habit-card.current {
                    background-color: #222;
                    border-left: 4px solid rgb(168, 142, 64);
                    border-radius: 8px;
                    padding: 15px;
                    margin-bottom: 15px;
                }
                .habit-title {
                    font-size: 18px;
                    font-weight: 600;
                    margin-bottom: 6px;
                    color: #ffffff;
                    word-break: break-word;
                }
                .habit-description {
                    font-size: 15px;
                    margin: 8px 0;
                    color: #ccc;
                    line-height: 1.4;
                    word-break: break-word;
                }
                .habit-details {
                    display: flex;
                    flex-direction: column;
                    gap: 6px;
                    font-size: 14px;
                    color: #aaa;
                    margin-top: 12px;
                }
                .habit-detail-item {
                    display: flex;
                    align-items: center;
                    padding: 5px 10px;
                    border-radius: 4px;
                    word-break: break-word;
                }
                .habit-detail-item:before {
                    content: "• ";
                    margin-right: 5px;
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
                .points {
                    color: #4a90e2;
                    font-weight: bold;
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
                    .habit-time {
                        font-size: 14px;
                        padding: 4px 12px;
                    }
                    .habit-title {
                        font-size: 16px;
                    }
                    .habit-description {
                        font-size: 14px;
                    }
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>' . htmlspecialchars($data['current_task']['title']) . '</h1>
                    <div class="habit-time">Due at: ' . htmlspecialchars($data['current_task']['due_time']) . '</div>
                </div>
                
                <div class="section">
                    <div class="habit-card current">
                        <div class="habit-title">Time to complete your habit: ' . htmlspecialchars($data['current_task']['title']) . '</div>
                        ' . ($data['current_task']['description'] ? '<div class="habit-description">' . htmlspecialchars($data['current_task']['description']) . '</div>' : '') . '
                        <div class="habit-details">
                            <div class="habit-detail-item priority-' . htmlspecialchars($data['current_task']['priority']) . '">
                                Priority: ' . ucfirst(htmlspecialchars($data['current_task']['priority'])) . '
                            </div>
                            ' . ($data['current_task']['due_time'] ? '<div class="habit-detail-item">Due: ' . htmlspecialchars($data['current_task']['due_time']) . '</div>' : '') . '
                            ' . ($data['current_task']['points'] ? '<div class="habit-detail-item points">Earn: +' . htmlspecialchars($data['current_task']['points']) . ' points</div>' : '') . '
                        </div>
                        <a href="https://abel.abuneteklehaymanot.org/pages/habits/index.php?action=complete&habit_id=' . htmlspecialchars($data['current_task']['id']) . '" class="action-button">Complete Now</a>
                    </div>
                </div>
                
                ' . (count($data['upcoming_tasks']) > 0 ? '
                <div class="section">
                    <div class="section-title">Coming Up Next</div>
                    ' . $this->renderHabitList($data['upcoming_tasks']) . '
                </div>' : '') . '
                
                ' . (isset($data['completed_tasks']) && count($data['completed_tasks']) > 0 ? '
                <div class="section">
                    <div class="section-title">Already Completed Today</div>
                    ' . $this->renderHabitList($data['completed_tasks']) . '
                </div>' : '') . '
                
                <div class="footer">
                    <p>ኃይልን በሚሰጠኝ በክርስቶስ ሁሉን እችላለሁ </br>
                    ፊልጵስዩስ 4:13
                    </br>
                    
                    </p>
                    <p>This email was sent from AMHA-SLASSIE</p>
                    <p>© ' . date('Y') . ' Amha-SELASIE. All rights reserved.</p>
                    <p><a href="{unsubscribe_link}" style="color: #888; text-decoration: underline;">Unsubscribe</a> from these notifications</p>
                </div>
            </div>
        </body>
        </html>';
    }
    
    private function renderHabitList($habits) {
        $output = '';
        foreach ($habits as $habit) {
            $output .= '
            <div class="habit-card">
                <div class="habit-title">' . htmlspecialchars($habit['title']) . '</div>
                ' . ($habit['description'] ? '<div class="habit-description">' . htmlspecialchars($habit['description']) . '</div>' : '') . '
                <div class="habit-details">
                    <div class="habit-detail-item priority-' . htmlspecialchars($habit['priority']) . '">
                        Priority: ' . ucfirst(htmlspecialchars($habit['priority'])) . '
                    </div>
                    ' . (isset($habit['due_time']) && $habit['due_time'] ? '<div class="habit-detail-item">Due: ' . htmlspecialchars($habit['due_time']) . '</div>' : '') . '
                    ' . (isset($habit['completed_text']) ? '<div class="habit-detail-item" style="color: #4a90e2;"><strong>' . htmlspecialchars($habit['completed_text']) . '</strong></div>' : '') . '
                    ' . (isset($habit['upcoming_text']) ? '<div class="habit-detail-item" style="color: #4caf50;"><strong>' . htmlspecialchars($habit['upcoming_text']) . '</strong></div>' : '') . '
                    ' . (isset($habit['estimated_duration']) && $habit['estimated_duration'] ? '<div class="habit-detail-item">' . htmlspecialchars($habit['estimated_duration']) . '</div>' : '') . '
                </div>
            </div>';
        }
        return $output;
    }
}
?> 