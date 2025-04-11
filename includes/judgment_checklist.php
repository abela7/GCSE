<?php
/**
 * Daily Judgment Checklist - Reusable Orthodox Christian checklist items
 * This file contains functions to generate checklist items that can be used
 * across the web application.
 */

/**
 * Returns an array of all judgment checklist items with their IDs and labels
 * 
 * @return array All checklist items
 */
function getJudgmentChecklistItems() {
    return [
        // Spiritual works
        [
            'id' => 'prayer',
            'label' => 'Did you pray with your whole heart today?'
        ],
        [
            'id' => 'repentance',
            'label' => 'Did you repent of your sins today?'
        ],
        
        // Corporal works of mercy (Matthew 25:35-36)
        [
            'id' => 'feed_hungry',
            'label' => 'Did you feed the hungry?'
        ],
        [
            'id' => 'give_drink',
            'label' => 'Did you give drink to the thirsty?'
        ],
        [
            'id' => 'welcome_strangers',
            'label' => 'Did you welcome strangers and show hospitality?'
        ],
        [
            'id' => 'clothe_naked',
            'label' => 'Did you clothe those who were naked and in need?'
        ],
        [
            'id' => 'care_sick',
            'label' => 'Did you care for the sick and suffering?'
        ],
        [
            'id' => 'visit_prisoners',
            'label' => 'Did you visit prisoners and comfort the afflicted?'
        ],
        
        // Additional spiritual checks
        [
            'id' => 'forgiveness',
            'label' => 'Did you forgive those who wronged you?'
        ],
        [
            'id' => 'scripture',
            'label' => 'Did you read Scripture today?'
        ],
        [
            'id' => 'fasting',
            'label' => 'Did you practice self-discipline today?'
        ]
    ];
}

/**
 * Renders HTML for the judgment checklist items
 * 
 * @param array $selectedItems Optional array of item IDs to include (default: all items)
 * @param string $class Optional CSS class for the checklist
 * @return string HTML output
 */
function renderJudgmentChecklist($selectedItems = [], $class = 'checklist') {
    $allItems = getJudgmentChecklistItems();
    $html = '<div class="' . $class . '">';
    
    foreach ($allItems as $item) {
        // If selectedItems is provided but empty, include all items
        // If selectedItems has values, only include items in the array
        if (empty($selectedItems) || in_array($item['id'], $selectedItems)) {
            $html .= '<div class="checklist-item">
                <input type="checkbox" id="' . $item['id'] . '" class="judgment-check">
                <label for="' . $item['id'] . '">' . $item['label'] . '</label>
            </div>';
        }
    }
    
    $html .= '</div>';
    return $html;
}

/**
 * JavaScript for checklist cookie functionality
 * 
 * @return string JavaScript code
 */
function getJudgmentChecklistScript() {
    return "
    function setupJudgmentChecklist() {
        const checkboxes = document.querySelectorAll('.judgment-check');
        const today = new Date().toISOString().split('T')[0]; // YYYY-MM-DD format
        
        // Check if we have saved state for today
        const savedDate = getCookie('judgment_date');
        
        // If it's a new day, clear previous checkboxes
        if (savedDate !== today) {
            // Clear all checkboxes
            checkboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
            
            // Set today's date in cookie
            setCookie('judgment_date', today, 365);
        } else {
            // Restore saved state
            checkboxes.forEach(checkbox => {
                const isChecked = getCookie(`judgment_${checkbox.id}`) === 'true';
                checkbox.checked = isChecked;
            });
        }
        
        // Add event listeners to save state when checkboxes change
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                setCookie(`judgment_${this.id}`, this.checked, 365);
            });
        });
    }
    
    // Cookie helper functions
    function setCookie(name, value, days) {
        const d = new Date();
        d.setTime(d.getTime() + (days * 24 * 60 * 60 * 1000));
        const expires = 'expires=' + d.toUTCString();
        document.cookie = name + '=' + value + ';' + expires + ';path=/';
    }
    
    function getCookie(name) {
        const cname = name + '=';
        const decodedCookie = decodeURIComponent(document.cookie);
        const ca = decodedCookie.split(';');
        for(let i = 0; i < ca.length; i++) {
            let c = ca[i];
            while (c.charAt(0) === ' ') {
                c = c.substring(1);
            }
            if (c.indexOf(cname) === 0) {
                return c.substring(cname.length, c.length);
            }
        }
        return '';
    }
    
    // Initialize checklist when DOM is loaded
    document.addEventListener('DOMContentLoaded', function() {
        setupJudgmentChecklist();
    });
    ";
}
?> 