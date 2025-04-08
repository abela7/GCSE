<?php
require_once __DIR__ . '/email_template.php';

class MorningBriefing extends EmailTemplate {
    public function generateEmail($data) {
        $title = "Be ready for the amazing day! Here are your tasks for today😇";
        
        // Generate tasks section
        $tasksContent = '';
        if (!empty($data['tasks'])) {
            $tasksContent .= '
            <div class="section">
                <ul class="task-list">';
            
            foreach ($data['tasks'] as $task) {
                $priorityClass = 'priority-' . strtolower($task['priority']);
                $tasksContent .= '
                <li class="task-item '.$priorityClass.'">
                    <strong>'.$task['title'].'</strong>
                    <div class="task-description">'.$task['description'].'</div>
                    <div class="task-time" style="color: #cdaf56; font-size: 1.1em; font-weight: 600;">
                        Due: '.$task['due_time'].'
                    </div>
                </li>';
            }
            
            $tasksContent .= '</ul></div>';
        }
        
        // Generate habits section
        $habitsContent = '';
        if (!empty($data['habits'])) {
            $habitsContent .= '
            <div class="section">
                <h2 class="section-title">Today\'s Habits</h2>
                <ul class="task-list">';
            
            foreach ($data['habits'] as $habit) {
                $habitsContent .= '
                <li class="task-item">
                    <strong>'.$habit['title'].'</strong>
                    <div class="task-description">'.$habit['description'].'</div>
                    <div class="task-time" style="color: #cdaf56; font-size: 1.1em; font-weight: 600;">
                        Scheduled: '.$habit['time'].'
                    </div>
                </li>';
            }
            
            $habitsContent .= '</ul></div>';
        }
        
        // Generate overdue section
        $overdueContent = '';
        if (!empty($data['overdue'])) {
            $overdueContent .= '
            <div class="section">
                <h2 class="section-title">Overdue Tasks</h2>
                <div class="highlight">
                    These tasks need your attention:
                </div>
                <ul class="task-list">';
            
            foreach ($data['overdue'] as $task) {
                $overdueContent .= '
                <li class="task-item priority-high">
                    <strong>'.$task['title'].'</strong>
                    <div class="task-description">'.$task['description'].'</div>
                    <div class="task-time" style="color: #cdaf56; font-size: 1.1em; font-weight: 600;">
                        Was due: '.$task['due_time'].'
                    </div>
                </li>';
            }
            
            $overdueContent .= '</ul></div>';
        }
        
        // Combine all sections
        $content = '
        <p>I hope you\'re ready for a productive day! Here\'s what\'s on your schedule:</p>
        '.$tasksContent.'
        '.$habitsContent.'
        '.$overdueContent.'
        <div class="section">
            <div class="btn-container">
                <a href="https://abel.abuneteklehaymanot.org/pages/tasks/index.php" class="btn">View Full Tasks Schedule</a>
                <a href="https://abel.abuneteklehaymanot.org/pages/habits/index.php" class="btn">View Habits</a>
            </div>
        </div>
        <div class="highlight">
            <strong>ኃይል በሚሰጠኝ በክርስቶስ ሁሉን እችላለሁ!</strong>
            </br> ፈልጵስዩስ 4:13<br>
            "The future depends on what you do today!"
        </div>';
        
        return $this->getBaseTemplate($title, $content);
    }
}
?> 