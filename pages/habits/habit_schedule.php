<!-- Habit Schedule Options -->
<div class="mb-4">
    <label class="form-label fw-medium">Schedule Type</label>
    <div class="schedule-options mb-3">
        <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="schedule_type" id="schedule_daily" value="daily" checked>
            <label class="form-check-label" for="schedule_daily">Daily</label>
        </div>
        <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="schedule_type" id="schedule_specific_days" value="specific_days">
            <label class="form-check-label" for="schedule_specific_days">Specific Days</label>
        </div>
        <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="schedule_type" id="schedule_frequency" value="frequency">
            <label class="form-check-label" for="schedule_frequency">X Times Per Week</label>
        </div>
    </div>
    
    <!-- Specific days selection -->
    <div id="specific_days_options" class="schedule-panel d-none mb-3 p-3 border rounded">
        <p class="text-muted small mb-2">Select which days of the week this habit should occur:</p>
        <div class="d-flex flex-wrap gap-2">
            <?php
            $days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
            foreach ($days as $index => $day) {
                echo '<div class="form-check">
                        <input type="checkbox" class="form-check-input" name="weekdays[]" id="day_'.$index.'" value="'.$index.'">
                        <label class="form-check-label" for="day_'.$index.'">'.$day.'</label>
                      </div>';
            }
            ?>
        </div>
    </div>
    
    <!-- Frequency selection -->
    <div id="frequency_options" class="schedule-panel d-none p-3 border rounded">
        <div class="form-group">
            <label for="times_per_week" class="form-label">Times per week</label>
            <select class="form-select" id="times_per_week" name="times_per_week">
                <?php for ($i = 1; $i <= 7; $i++): ?>
                <option value="<?php echo $i; ?>"><?php echo $i; ?> time<?php echo $i > 1 ? 's' : ''; ?> per week</option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="form-group mt-3">
            <label for="week_starts_on" class="form-label">Week starts on</label>
            <select class="form-select" id="week_starts_on" name="week_starts_on">
                <option value="0">Sunday</option>
                <option value="1">Monday</option>
                <option value="6">Saturday</option>
            </select>
            <small class="form-text text-muted">This determines when the weekly counter resets</small>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const scheduleTypes = document.querySelectorAll('input[name="schedule_type"]');
    const schedulePanels = document.querySelectorAll('.schedule-panel');
    
    // Function to hide all schedule panels
    function hideAllPanels() {
        schedulePanels.forEach(panel => panel.classList.add('d-none'));
    }
    
    // Function to update display based on selected schedule type
    function updateScheduleDisplay() {
        hideAllPanels();
        
        const selected = document.querySelector('input[name="schedule_type"]:checked').value;
        if (selected === 'specific_days') {
            document.getElementById('specific_days_options').classList.remove('d-none');
        } else if (selected === 'frequency') {
            document.getElementById('frequency_options').classList.remove('d-none');
        }
    }
    
    // Add event listeners to schedule type radio buttons
    scheduleTypes.forEach(radio => {
        radio.addEventListener('change', updateScheduleDisplay);
    });
    
    // Initialize display based on current selection
    updateScheduleDisplay();
});
</script> 