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
                    background-color: #f5f5f5;
                }
                .container {
                    max-width: 600px;
                    margin: 0 auto;
                    padding: 0;
                    background-color: #ffffff;
                    border-radius: 12px;
                    overflow: hidden;
                    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
                }
                .header {
                    background-color: rgb(168, 142, 64);
                    color: white;
                    padding: 25px 20px;
                    text-align: center;
                }
                .header h1 {
                    margin: 0;
                    font-size: 24px;
                    font-weight: 600;
                    letter-spacing: 0.5px;
                    word-break: break-word;
                }
                .habit-time {
                    font-size: 16px;
                    margin-top: 10px;
                    font-weight: bold;
                    color: rgba(255, 255, 255, 0.9);
                    display: inline-block;
                    padding: 5px 15px;
                    background-color: rgba(0, 0, 0, 0.2);
                    border-radius: 20px;
                    margin-top: 15px;
                }
                .section {
                    margin: 20px 0 0 0;
                    padding: 0 20px 20px 20px;
                }
                .section-title {
                    font-size: 18px;
                    font-weight: 600;
                    color: #2d3436;
                    margin-bottom: 15px;
                    padding-bottom: 10px;
                    border-bottom: 2px solid #f1f3f5;
                    position: relative;
                }
                .section-title:after {
                    content: "";
                    position: absolute;
                    left: 0;
                    bottom: -2px;
                    width: 50px;
                    height: 2px;
                    background-color: rgb(168, 142, 64);
                }
                .habit-card {
                    background-color: #f8f9fa;
                    border-left: 4px solid #4a90e2;
                    border-radius: 8px;
                    padding: 15px;
                    margin-bottom: 15px;
                    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
                    transition: transform 0.2s ease, box-shadow 0.2s ease;
                    word-wrap: break-word;
                    overflow-wrap: break-word;
                }
                .habit-card:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
                }
                .habit-card.current {
                    background-color: rgba(238, 246, 255, 0.8);
                    border-left: 4px solid rgb(168, 142, 64);
                    border-radius: 10px;
                    padding: 20px 15px;
                    margin: 0 0 25px 0;
                    box-shadow: 0 5px 15px rgba(0,0,0,0.07);
                    position: relative;
                }
                .habit-card.current:before {
                    content: "NOW DUE";
                    position: absolute;
                    top: -10px;
                    right: 15px;
                    background-color: rgb(168, 142, 64);
                    color: white;
                    padding: 5px 10px;
                    border-radius: 4px;
                    font-size: 12px;
                    font-weight: bold;
                    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
                }
                .habit-title {
                    font-size: 18px;
                    font-weight: 600;
                    margin-bottom: 8px;
                    color: #2d3436;
                    word-break: break-word;
                }
                .habit-card.current .habit-title {
                    font-size: 20px;
                    color: #1a1a1a;
                }
                .habit-description {
                    font-size: 15px;
                    margin: 10px 0;
                    color: #555;
                    line-height: 1.5;
                    word-break: break-word;
                }
                .habit-details {
                    display: flex;
                    flex-wrap: wrap;
                    gap: 8px;
                    font-size: 14px;
                    color: #666;
                    margin-top: 15px;
                    align-items: center;
                }
                .habit-detail-item {
                    display: inline-flex;
                    align-items: center;
                    background: rgba(0,0,0,0.05);
                    padding: 5px 10px;
                    border-radius: 4px;
                    margin-bottom: 5px;
                    max-width: 100%;
                    box-sizing: border-box;
                    word-break: break-word;
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
                .points {
                    color: #4a90e2;
                    font-weight: bold;
                }
                .streak {
                    color: #9c27b0;
                    font-weight: bold;
                }
                .action-buttons {
                    display: flex;
                    gap: 10px;
                    margin-top: 20px;
                    flex-wrap: wrap;
                }
                .action-button {
                    display: inline-block;
                    color: white;
                    padding: 10px 15px;
                    text-decoration: none;
                    border-radius: 6px;
                    font-weight: 600;
                    text-align: center;
                    transition: background-color 0.2s ease;
                    min-width: 110px;
                    font-size: 15px;
                }
                .complete-button {
                    background-color: #43a047;
                }
                .complete-button:hover {
                    background-color: #388e3c;
                }
                .skip-button {
                    background-color: #ff9800;
                }
                .skip-button:hover {
                    background-color: #f57c00;
                }
                .view-button {
                    background-color: rgb(168, 142, 64);
                }
                .view-button:hover {
                    background-color: rgb(148, 122, 44);
                }
                .footer {
                    text-align: center;
                    padding: 20px;
                    font-size: 12px;
                    color: #666;
                    background-color: #f9f9f9;
                    border-top: 1px solid #eee;
                }
                .app-name {
                    font-weight: bold;
                    color: rgb(168, 142, 64);
                }
                .divider {
                    height: 1px;
                    background-color: #eee;
                    margin: 15px 0;
                }
                @media only screen and (max-width: 600px) {
                    body {
                        padding: 0;
                        margin: 0;
                    }
                    .container {
                        width: 100%;
                        max-width: 100%;
                        border-radius: 0;
                        margin: 0;
                    }
                    .header {
                        border-radius: 0;
                        padding: 15px 10px;
                    }
                    .header h1 {
                        font-size: 20px;
                    }
                    .habit-time {
                        font-size: 14px;
                        padding: 4px 12px;
                    }
                    .section {
                        padding: 0 12px 15px 12px;
                        margin: 15px 0 0 0;
                    }
                    .section-title {
                        font-size: 16px;
                        margin-bottom: 12px;
                    }
                    .habit-card {
                        padding: 12px 10px;
                        margin-bottom: 12px;
                    }
                    .habit-card.current {
                        padding: 15px 10px;
                    }
                    .habit-card.current:before {
                        font-size: 10px;
                        padding: 3px 8px;
                        top: -8px;
                        right: 10px;
                    }
                    .habit-title {
                        font-size: 16px;
                    }
                    .habit-card.current .habit-title {
                        font-size: 18px;
                    }
                    .habit-description {
                        font-size: 14px;
                    }
                    .habit-details {
                        gap: 6px;
                        font-size: 13px;
                    }
                    .habit-detail-item {
                        padding: 4px 8px;
                    }
                    .action-buttons {
                        flex-direction: column;
                        gap: 8px;
                    }
                    .action-button {
                        width: 100%;
                        padding: 12px 10px;
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
                            ' . ($data['current_task']['points'] ? '<div class="habit-detail-item points">Earn: +' . htmlspecialchars($data['current_task']['points']) . ' points</div>' : '') . '
                            ' . ($data['current_task']['category_name'] ? '<div class="habit-detail-item">Category: ' . htmlspecialchars($data['current_task']['category_name']) . '</div>' : '') . '
                        </div>
                        <div class="action-buttons">
                            <a href="https://abel.abuneteklehaymanot.org/pages/habits/index.php?action=complete&habit_id=' . htmlspecialchars($data['current_task']['id']) . '" class="action-button complete-button">Complete Now</a>
                            <a href="https://abel.abuneteklehaymanot.org/pages/habits/index.php?action=skip&habit_id=' . htmlspecialchars($data['current_task']['id']) . '" class="action-button skip-button">Skip Today</a>
                            <a href="https://abel.abuneteklehaymanot.org/pages/habits/index.php?habit_id=' . htmlspecialchars($data['current_task']['id']) . '" class="action-button view-button">View Details</a>
                        </div>
                    </div>
                </div>
                
                ' . (count($data['upcoming_tasks']) > 0 ? '
                <div class="section">
                    <div class="section-title">Other Habits Today</div>
                    ' . $this->renderHabitList($data['upcoming_tasks']) . '
                </div>' : '') . '
                
                <div class="footer">
                    <p>This is an automated notification from <span class="app-name">Amha-Silassie Study App</span></p>
                    <div class="divider"></div>
                    <p>© ' . date('Y') . ' Amha-Silassie. All rights reserved.</p>
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
                    ' . ($habit['due_time'] ? '<div class="habit-detail-item">Due: ' . htmlspecialchars($habit['due_time']) . '</div>' : '') . '
                    ' . ($habit['estimated_duration'] ? '<div class="habit-detail-item">' . htmlspecialchars($habit['estimated_duration']) . '</div>' : '') . '
                    ' . ($habit['category_name'] ? '<div class="habit-detail-item">Category: ' . htmlspecialchars($habit['category_name']) . '</div>' : '') . '
                </div>
            </div>';
        }
        return $output;
    }
}
?> 