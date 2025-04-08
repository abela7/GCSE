<?php
class EmailTemplate {
    protected $styles = '
        <style>
            body { 
                font-family: Arial, sans-serif;
                line-height: 1.6;
                color: #333;
            }
            .container {
                max-width: 600px;
                margin: 0 auto;
                padding: 20px;
                background: #ffffff;
            }
            .header {
                background: rgb(168, 142, 64);
                color: white;
                padding: 20px;
                text-align: center;
                border-radius: 5px 5px 0 0;
            }
            .content {
                padding: 20px;
                background: #fff;
                border: 1px solid #e0e0e0;
            }
            .footer {
                text-align: center;
                padding: 20px;
                font-size: 12px;
                color: #666;
                border-top: 1px solid #e0e0e0;
            }
            .task-list {
                list-style: none;
                padding: 0;
            }
            .task-item {
                padding: 10px;
                margin: 5px 0;
                background: #f8f9fa;
                border-left: 4px solid #4a90e2;
                border-radius: 3px;
            }
            .task-time {
                color: #666;
                font-size: 0.9em;
            }
            .priority-high {
                border-left-color: #dc3545;
            }
            .priority-medium {
                border-left-color: #ffc107;
            }
            .priority-low {
                border-left-color: #28a745;
            }
            .btn {
                display: inline-block;
                padding: 10px 20px;
                background: rgb(168, 142, 64);
                color: #000;
                text-decoration: none;
                border-radius: 5px;
                margin: 10px 5px;
                font-weight: 600;
                min-width: 200px;
                text-align: center;
            }
            .btn-container {
                display: flex;
                flex-wrap: nowrap;
                justify-content: center;
                gap: 10px;
                margin: 20px 0;
            }
            @media (max-width: 600px) {
                .btn-container {
                    flex-direction: row;
                    flex-wrap: nowrap;
                }
                .btn {
                    flex: 1;
                    margin: 5px;
                }
            }
            .section {
                margin: 20px 0;
            }
            .section-title {
                border-bottom: 2px solid #4a90e2;
                padding-bottom: 5px;
                margin-bottom: 15px;
                color: #2c3e50;
            }
            .highlight {
                background: #fff3cd;
                padding: 10px;
                border-radius: 3px;
                margin: 10px 0;
            }
        </style>
    ';

    protected function getBaseTemplate($title, $content) {
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>'.$title.'</title>
            '.$this->styles.'
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>'.$title.'</h1>
                </div>
                <div class="content">
                    '.$content.'
                </div>
                <div class="footer">
                    <p>This email was sent from Amha-Silassie Study App</p>
                    <p>© '.date('Y').' Amha-Silassie. All rights reserved.</p>
                    <p><a href="{unsubscribe_link}">Unsubscribe</a> from these notifications</p>
                </div>
            </div>
        </body>
        </html>';
    }
}
?> 