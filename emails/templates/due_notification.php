<?php
require_once __DIR__ . '/email_template.php';

class DueNotification extends EmailTemplate {
    public function generateEmail($data) {
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Due ' . ($data['type'] === 'task' ? 'Task' : 'Habit') . ' Notification</title>
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
                .date {
                    font-size: 16px;
                    margin-top: 10px;
                    opacity: 0.9;
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
                .item {
                    background-color: #f8f9fa;
                    border-radius: 8px;
                    padding: 15px;
                    margin-bottom: 15px;
                    border-left: 4px solid rgb(168, 142, 64);
                }
                .item-title {
                    font-size: 18px;
                    font-weight: 600;
                    color: #2d3436;
                    margin-bottom: 8px;
                }
                .item-description {
                    font-size: 16px;
                    color: #636e72;
                    margin-bottom: 8px;
                }
                .item-time {
                    font-size: 16px;
                    font-weight: 500;
                    color: rgb(168, 142, 64);
                }
                .priority-high {
                    border-left-color: #e74c3c;
                }
                .priority-medium {
                    border-left-color: #f39c12;
                }
                .priority-low {
                    border-left-color: #2ecc71;
                }
                .countdown {
                    font-size: 18px;
                    font-weight: 600;
                    color: #e74c3c;
                    text-align: center;
                    margin: 20px 0;
                    padding: 15px;
                    background-color: #fff9f9;
                    border-radius: 8px;
                    border: 1px solid #ffecec;
                }
                .buttons {
                    text-align: center;
                    margin-top: 30px;
                    padding: 0 20px;
                }
                .button {
                    display: inline-block;
                    padding: 12px 25px;
                    background-color: rgb(168, 142, 64);
                    color: white;
                    text-decoration: none;
                    border-radius: 8px;
                    font-size: 16px;
                    font-weight: 500;
                    margin: 0 10px;
                    transition: background-color 0.3s;
                }
                .button:hover {
                    background-color: rgb(148, 122, 44);
                }
                .check-button {
                    background-color: #2ecc71;
                }
                .check-button:hover {
                    background-color: #27ae60;
                }
                .footer {
                    text-align: center;
                    padding: 20px;
                    color: #636e72;
                    font-size: 14px;
                }
                @media only screen and (max-width: 600px) {
                    .container {
                        padding: 15px;
                    }
                    .header {
                        padding: 20px 15px;
                    }
                    .header h1 {
                        font-size: 22px;
                    }
                    .date {
                        font-size: 15px;
                    }
                    .section {
                        margin: 20px 0;
                        padding: 0 15px;
                    }
                    .section-title {
                        font-size: 18px;
                    }
                    .item {
                        padding: 12px;
                    }
                    .item-title {
                        font-size: 16px;
                    }
                    .item-description {
                        font-size: 15px;
                    }
                    .item-time {
                        font-size: 15px;
                    }
                    .button {
                        padding: 10px 20px;
                        font-size: 15px;
                        margin: 5px;
                        display: block;
                        width: calc(100% - 40px);
                    }
                    .buttons {
                        margin-top: 25px;
                    }
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>Reminder: ' . ($data['type'] === 'task' ? 'Task' : 'Habit') . ' Due Soon</h1>
                    <div class="date">' . $data['date'] . '</div>
                </div>
                
                <div class="section">
                    <h2 class="section-title">Hey ' . $data['name'] . '! Don\'t forget about your upcoming ' . $data['type'] . ':</h2>
                    ' . $this->generateItem($data) . '
                </div>
                
                <div class="countdown">
                    ' . $this->generateCountdown($data) . '
                </div>
                
                <div class="buttons">
                    ' . ($data['type'] === 'task' ? '
                    <a href="https://abel.abuneteklehaymanot.org/pages/tasks/task_detail.php?id=' . $data['id'] . '" class="button">View Task</a>
                    <a href="https://abel.abuneteklehaymanot.org/pages/tasks/mark_complete.php?id=' . $data['id'] . '" class="button check-button">Mark Complete</a>
                    ' : '
                    <a href="https://abel.abuneteklehaymanot.org/pages/habits/habit_detail.php?id=' . $data['id'] . '" class="button">View Habit</a>
                    <a href="https://abel.abuneteklehaymanot.org/pages/habits/track.php?id=' . $data['id'] . '" class="button check-button">Complete Today</a>
                    ') . '
                </div>
                
                <div class="footer">
                    <p>Stay productive and keep up the good work! 🌟</p>
                    <p>This is an automated reminder from your GCSE Study App.</p>
                </div>
            </div>
        </body>
        </html>';
    }

    private function generateItem($data) {
        $priorityClass = $data['type'] === 'task' ? 'priority-' . ($data['priority'] ?? 'medium') : '';
        
        return '
        <div class="item ' . $priorityClass . '">
            <div class="item-title">' . htmlspecialchars($data['title']) . '</div>
            <div class="item-description">' . htmlspecialchars($data['description'] ?? 'No description provided') . '</div>
            <div class="item-time">⏰ ' . htmlspecialchars($data['due_time']) . '</div>
            ' . ($data['category'] ? '<div class="item-description">Category: ' . htmlspecialchars($data['category']) . '</div>' : '') . '
        </div>';
    }

    private function generateCountdown($data) {
        $timeRemaining = '';
        
        if (isset($data['hours_remaining'])) {
            if ($data['hours_remaining'] < 1) {
                $timeRemaining = 'Less than an hour remaining!';
            } else if ($data['hours_remaining'] == 1) {
                $timeRemaining = 'Only 1 hour remaining!';
            } else if ($data['hours_remaining'] <= 24) {
                $timeRemaining = 'Only ' . $data['hours_remaining'] . ' hours remaining!';
            } else {
                $days = floor($data['hours_remaining'] / 24);
                $hours = $data['hours_remaining'] % 24;
                $timeRemaining = $days . ' day' . ($days > 1 ? 's' : '') . 
                                 ($hours > 0 ? ' and ' . $hours . ' hour' . ($hours > 1 ? 's' : '') : '') . 
                                 ' remaining!';
            }
        }
        
        return $timeRemaining ?: 'Due soon!';
    }
}
?> 