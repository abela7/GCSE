<?php
require_once __DIR__ . '/email_template.php';

class VocabularyNotification extends EmailTemplate {
    public function generateEmail($data) {
        // Start building HTML content
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Daily Vocabulary Study List</title>
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
                .date {
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
                .category {
                    background-color: #2a2a2a;
                    border-radius: 8px;
                    margin-bottom: 20px;
                    overflow: hidden;
                }
                .category-header {
                    background-color: #333;
                    padding: 10px 15px;
                    font-weight: 600;
                    font-size: 16px;
                    cursor: pointer;
                    position: relative;
                }
                .category-header:after {
                    content: "+";
                    position: absolute;
                    right: 15px;
                    top: 50%;
                    transform: translateY(-50%);
                }
                .word-item {
                    border-bottom: 1px solid #333;
                    padding: 12px 15px;
                }
                .word-item:last-child {
                    border-bottom: none;
                }
                .word-title {
                    font-size: 16px;
                    font-weight: 600;
                    color: #4a90e2;
                    margin-bottom: 5px;
                    word-break: break-word;
                }
                .word-meaning {
                    font-size: 14px;
                    color: #ddd;
                    margin-bottom: 5px;
                }
                .word-example {
                    font-size: 14px;
                    color: #aaa;
                    font-style: italic;
                    padding-left: 15px;
                    border-left: 3px solid rgb(168, 142, 64);
                    margin-top: 8px;
                }
                .summary-text {
                    font-size: 15px;
                    color: #ddd;
                    margin: 10px 0;
                    line-height: 1.6;
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
                @media only screen and (max-width: 600px) {
                    body {
                        padding: 0;
                        margin: 0;
                    }
                    .header h1 {
                        font-size: 20px;
                    }
                    .date {
                        font-size: 14px;
                        padding: 4px 12px;
                    }
                    .word-title {
                        font-size: 15px;
                    }
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>Your Daily Vocabulary Study List</h1>
                    <div class="date">' . htmlspecialchars($data['date_formatted']) . '</div>
                </div>
                
                <div class="section">
                    <p class="summary-text">
                        Here are your vocabulary words to study today. Take time to review these words and try to use them in your writing or conversations.
                    </p>';
        
        // Loop through each category and render words
        foreach ($data['categories'] as $category) {
            if (count($category['items']) === 0) continue;
            
            $html .= '
                    <div class="category">
                        <div class="category-header">' . htmlspecialchars($category['name']) . ' (' . count($category['items']) . ' items)</div>';
            
            foreach ($category['items'] as $item) {
                $html .= '
                        <div class="word-item">
                            <div class="word-title">' . htmlspecialchars($item['item_title']) . '</div>
                            <div class="word-meaning">' . htmlspecialchars($item['item_meaning']) . '</div>
                            <div class="word-example">' . htmlspecialchars($item['item_example']) . '</div>
                        </div>';
            }
            
            $html .= '
                    </div>';
        }
        
        $html .= '
                    <a href="' . htmlspecialchars($data['app_url']) . '/pages/practice/index.php" class="action-button">View Complete Study Materials</a>
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
            
            <script type="text/javascript">
                // Simple toggle script for category headers
                document.addEventListener("DOMContentLoaded", function() {
                    var headers = document.querySelectorAll(".category-header");
                    headers.forEach(function(header) {
                        header.addEventListener("click", function() {
                            this.classList.toggle("active");
                            var content = this.nextElementSibling;
                            if (content.style.display === "none") {
                                content.style.display = "block";
                                this.textContent = this.textContent.replace("+", "-");
                            } else {
                                content.style.display = "none";
                                this.textContent = this.textContent.replace("-", "+");
                            }
                        });
                    });
                });
            </script>
        </body>
        </html>';
        
        return $html;
    }
}
?> 