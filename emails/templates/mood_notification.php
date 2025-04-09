<?php
require_once __DIR__ . '/email_template.php';

class MoodNotification extends EmailTemplate {
    public function generateEmail($data) {
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>How are you feeling?</title>
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
                .time {
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
                    padding: 25px;
                    text-align: center;
                }
                .message {
                    font-size: 18px;
                    margin-bottom: 30px;
                    line-height: 1.5;
                }
                .action-button {
                    display: inline-block;
                    color: white;
                    padding: 15px 30px;
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
                @media only screen and (max-width: 600px) {
                    body {
                        padding: 0;
                        margin: 0;
                    }
                    .header h1 {
                        font-size: 20px;
                    }
                    .time {
                        font-size: 14px;
                        padding: 4px 12px;
                    }
                    .message {
                        font-size: 16px;
                    }
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>How are you feeling right now?</h1>
                    <div class="time">' . htmlspecialchars($data['time_greeting']) . '</div>
                </div>
                
                <div class="section">
                    <p class="message">
                        ' . htmlspecialchars($data['message']) . '
                    </p>
                    
                    <a href="' . htmlspecialchars($data['app_url']) . '/pages/mood_tracking/entry.php" class="action-button">Record Your Feeling</a>
                </div>
                
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
}
?> 