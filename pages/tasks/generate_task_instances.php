<?php
require_once '../../includes/db_connect.php';

// Function to generate task instances
function generateTaskInstances($conn) {
    // Get all active recurring tasks that might need more instances
    $query = "SELECT t.*, tr.frequency, tr.specific_days, tr.start_date, 
              (SELECT MAX(due_date) FROM task_instances WHERE task_id = t.id) as last_instance_date
              FROM tasks t 
              JOIN task_recurrence_rules tr ON t.id = tr.task_id 
              WHERE t.task_type = 'recurring' 
              AND t.is_active = 1 
              AND tr.is_active = 1";
    
    $result = $conn->query($query);
    
    while ($task = $result->fetch_assoc()) {
        // If no instances exist yet, use start_date as reference
        $lastDate = $task['last_instance_date'] ?? $task['start_date'];
        $today = new DateTime();
        $lastInstanceDate = new DateTime($lastDate);
        $weeksAhead = ceil(($lastInstanceDate->getTimestamp() - $today->getTimestamp()) / (7 * 24 * 3600));
        
        // If less than 2 weeks of future instances exist, generate more
        if ($weeksAhead < 2) {
            $weeksToGenerate = 4; // Generate 4 more weeks
            
            if ($task['frequency'] === 'daily') {
                generateDailyInstances($conn, $task, $lastDate, $weeksToGenerate);
            } 
            elseif ($task['frequency'] === 'weekly') {
                generateWeeklyInstances($conn, $task, $lastDate, $weeksToGenerate);
            }
            elseif ($task['frequency'] === 'monthly') {
                generateMonthlyInstances($conn, $task, $lastDate, $weeksToGenerate);
            }
        }
    }
}

function generateDailyInstances($conn, $task, $lastDate, $weeks) {
    $startDate = new DateTime($lastDate);
    $startDate->modify('+1 day'); // Start from next day
    
    $stmt = $conn->prepare("INSERT INTO task_instances (task_id, due_date, due_time, status, created_at, updated_at) 
                           VALUES (?, ?, ?, 'pending', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
    
    for ($i = 0; $i < ($weeks * 7); $i++) {
        $date = clone $startDate;
        $date->modify("+$i days");
        
        $stmt->bind_param('iss', 
            $task['id'],
            $date->format('Y-m-d'),
            $task['due_time']
        );
        $stmt->execute();
    }
}

function generateWeeklyInstances($conn, $task, $lastDate, $weeks) {
    $startDate = new DateTime($lastDate);
    $startDate->modify('+1 day'); // Start from next day
    
    $stmt = $conn->prepare("INSERT INTO task_instances (task_id, due_date, due_time, status, created_at, updated_at) 
                           VALUES (?, ?, ?, 'pending', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
    
    $days = json_decode($task['specific_days'], true);
    $dayMap = array_flip(['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday']);
    
    for ($week = 0; $week < $weeks; $week++) {
        foreach ($days as $dayName) {
            $date = clone $startDate;
            $date->modify("+$week weeks");
            
            $targetDay = $dayMap[$dayName];
            $currentDay = (int)$date->format('w');
            $daysToAdd = ($targetDay - $currentDay + 7) % 7;
            $date->modify("+$daysToAdd days");
            
            $stmt->bind_param('iss', 
                $task['id'],
                $date->format('Y-m-d'),
                $task['due_time']
            );
            $stmt->execute();
        }
    }
}

function generateMonthlyInstances($conn, $task, $lastDate, $weeks) {
    $startDate = new DateTime($lastDate);
    $startDate->modify('+1 day'); // Start from next day
    
    $stmt = $conn->prepare("INSERT INTO task_instances (task_id, due_date, due_time, status, created_at, updated_at) 
                           VALUES (?, ?, ?, 'pending', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
    
    // Generate for roughly the same number of months as weeks requested
    $months = ceil($weeks / 4);
    
    for ($i = 0; $i < $months; $i++) {
        $date = clone $startDate;
        $date->modify("+$i months");
        
        $stmt->bind_param('iss', 
            $task['id'],
            $date->format('Y-m-d'),
            $task['due_time']
        );
        $stmt->execute();
    }
}

// Run the generation
try {
    $conn->begin_transaction();
    generateTaskInstances($conn);
    $conn->commit();
    echo "Task instances generated successfully.\n";
} catch (Exception $e) {
    $conn->rollback();
    echo "Error generating task instances: " . $e->getMessage() . "\n";
}
?> 