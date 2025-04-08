<?php
require_once __DIR__ . '/email_template.php';

class MorningBriefing extends EmailTemplate {
    public function generateEmail($data) {
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Morning Briefing</title>
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
                    <h1>' . $data['greeting'] . '</h1>
                    <div class="date">' . $data['date'] . '</div>
                </div>
                
                <div class="section">
                    <h2 class="section-title">Be ready for the amazing day! Here are your tasks for today 🤗</h2>
                    ' . $this->generateTasks($data['tasks']) . '
                </div>
                
                <div class="section">
                    <h2 class="section-title">Your Habits for Today</h2>
                    ' . $this->generateHabits($data['habits']) . '
                </div>
                
                ' . ($data['overdue'] ? '
                <div class="section">
                    <h2 class="section-title">Overdue Tasks</h2>
                    ' . $this->generateOverdue($data['overdue']) . '
                </div>
                ' : '') . '
                
                <div class="buttons">
                    <a href="https://abel.abuneteklehaymanot.org/pages/tasks/index.php" class="button">View Tasks</a>
                    <a href="https://abel.abuneteklehaymanot.org/pages/habits/index.php" class="button">View Habits</a>
                </div>
                
                <div class="footer">
                    <p>Have a productive day! 🌟</p>
                </div>
            </div>
        </body>
        </html>';
    }

    private function generateTasks($tasks) {
        if (empty($tasks)) {
            return '<div class="item">
                <div class="item-title">No tasks scheduled for today</div>
                <div class="item-description">Take this opportunity to plan your day!</div>
            </div>';
        }

        $html = '';
        foreach ($tasks as $task) {
            $priorityClass = 'priority-' . ($task['priority'] ?? 'medium');
            $html .= '
            <div class="item ' . $priorityClass . '">
                <div class="item-title">' . htmlspecialchars($task['title']) . '</div>
                <div class="item-description">' . htmlspecialchars($task['description']) . '</div>
                <div class="item-time">⏰ ' . htmlspecialchars($task['time']) . '</div>
            </div>';
        }
        return $html;
    }

    private function generateHabits($habits) {
        if (empty($habits)) {
            return '<div class="item">
                <div class="item-title">No habits scheduled for today</div>
                <div class="item-description">Time to build some new habits!</div>
            </div>';
        }

        $html = '';
        foreach ($habits as $habit) {
            $html .= '
            <div class="item">
                <div class="item-title">' . htmlspecialchars($habit['title']) . '</div>
                <div class="item-description">' . htmlspecialchars($habit['description']) . '</div>
                <div class="item-time">⏰ ' . htmlspecialchars($habit['time']) . '</div>
            </div>';
        }
        return $html;
    }

    private function generateOverdue($overdue) {
        $html = '';
        foreach ($overdue as $task) {
            $html .= '
            <div class="item priority-high">
                <div class="item-title">' . htmlspecialchars($task['title']) . '</div>
                <div class="item-description">' . htmlspecialchars($task['description']) . '</div>
                <div class="item-time">⏰ ' . htmlspecialchars($task['time']) . '</div>
            </div>';
        }
        return $html;
    }
}
?> 