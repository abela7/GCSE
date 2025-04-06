<?php
require_once 'db_connect.php';

try {
    // Create categories table
    $conn->query("
        CREATE TABLE IF NOT EXISTS categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            icon VARCHAR(50) NOT NULL,
            color VARCHAR(20) NOT NULL,
            display_order INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // Create habits table
    $conn->query("
        CREATE TABLE IF NOT EXISTS habits (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            category_id INT NOT NULL,
            target_time TIME NOT NULL,
            is_active BOOLEAN DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (category_id) REFERENCES categories(id)
        )
    ");

    // Create habit_progress table
    $conn->query("
        CREATE TABLE IF NOT EXISTS habit_progress (
            id INT AUTO_INCREMENT PRIMARY KEY,
            habit_id INT NOT NULL,
            date DATE NOT NULL,
            status ENUM('completed', 'pending', 'skipped') NOT NULL DEFAULT 'pending',
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (habit_id) REFERENCES habits(id),
            UNIQUE KEY unique_habit_date (habit_id, date)
        )
    ");

    // Create habit_completions table
    $conn->query("
        CREATE TABLE IF NOT EXISTS habit_completions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            habit_id INT NOT NULL,
            completion_date DATE NOT NULL,
            completion_time TIME NOT NULL,
            status ENUM('done', 'procrastinate', 'skip') NOT NULL,
            points_earned INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (habit_id) REFERENCES habits(id),
            UNIQUE KEY unique_habit_date (habit_id, completion_date)
        )
    ");

    // Create exam_reports table
    $conn->query("
        CREATE TABLE IF NOT EXISTS exam_reports (
            id INT AUTO_INCREMENT PRIMARY KEY,
            exam_id INT NOT NULL,
            date DATE NOT NULL,
            score DECIMAL(5,2) NOT NULL,
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (exam_id) REFERENCES exams(id)
        )
    ");

    // Insert some default categories
    $defaultCategories = [
        ['name' => 'Spiritual Life', 'icon' => 'fas fa-pray', 'color' => '#cdaf56', 'display_order' => 1],
        ['name' => 'Physical Health', 'icon' => 'fas fa-heartbeat', 'color' => '#4CAF50', 'display_order' => 2],
        ['name' => 'Mental Growth', 'icon' => 'fas fa-brain', 'color' => '#2196F3', 'display_order' => 3],
        ['name' => 'Productivity', 'icon' => 'fas fa-tasks', 'color' => '#9C27B0', 'display_order' => 4]
    ];

    $stmt = $conn->prepare("INSERT IGNORE INTO categories (name, icon, color, display_order) VALUES (?, ?, ?, ?)");
    
    foreach ($defaultCategories as $category) {
        $stmt->bind_param("sssi", 
            $category['name'], 
            $category['icon'], 
            $category['color'], 
            $category['display_order']
        );
        $stmt->execute();
    }

    // Insert some example habits for Spiritual Life
    $spiritualLifeCategoryId = $conn->query("SELECT id FROM categories WHERE name = 'Spiritual Life'")->fetch_object()->id;
    
    $defaultHabits = [
        ['name' => 'Morning Prayer', 'target_time' => '06:00:00'],
        ['name' => 'Read Bible', 'target_time' => '07:00:00'],
        ['name' => 'Night Prayer', 'target_time' => '22:00:00']
    ];

    $stmt = $conn->prepare("INSERT IGNORE INTO habits (name, category_id, target_time) VALUES (?, ?, ?)");
    
    foreach ($defaultHabits as $habit) {
        $stmt->bind_param("sis", 
            $habit['name'], 
            $spiritualLifeCategoryId, 
            $habit['target_time']
        );
        $stmt->execute();
    }

    echo "Database tables created successfully with default data!";

} catch (Exception $e) {
    echo "Error creating tables: " . $e->getMessage();
}
?> 