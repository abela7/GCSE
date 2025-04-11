<?php
/**
 * Daily Judgment Checklist - Reusable Orthodox Christian checklist items
 * This file contains functions to generate checklist items that can be used
 * across the web application.
 */

/**
 * Constants for checklist categories
 */
define('JUDGMENT_CATEGORY_SPIRITUAL', 'spiritual');
define('JUDGMENT_CATEGORY_CORPORAL', 'corporal');
define('JUDGMENT_CATEGORY_ADDITIONAL', 'additional');

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
            'label' => 'Did you pray with your whole heart today?',
            'category' => JUDGMENT_CATEGORY_SPIRITUAL
        ],
        [
            'id' => 'repentance',
            'label' => 'Did you repent of your sins today?',
            'category' => JUDGMENT_CATEGORY_SPIRITUAL
        ],
        
        // Corporal works of mercy (Matthew 25:35-36)
        [
            'id' => 'feed_hungry',
            'label' => 'Did you feed the hungry?',
            'category' => JUDGMENT_CATEGORY_CORPORAL
        ],
        [
            'id' => 'give_drink',
            'label' => 'Did you give drink to the thirsty?',
            'category' => JUDGMENT_CATEGORY_CORPORAL
        ],
        [
            'id' => 'welcome_strangers',
            'label' => 'Did you welcome strangers and show hospitality?',
            'category' => JUDGMENT_CATEGORY_CORPORAL
        ],
        [
            'id' => 'clothe_naked',
            'label' => 'Did you clothe those who were naked and in need?',
            'category' => JUDGMENT_CATEGORY_CORPORAL
        ],
        [
            'id' => 'care_sick',
            'label' => 'Did you care for the sick and suffering?',
            'category' => JUDGMENT_CATEGORY_CORPORAL
        ],
        [
            'id' => 'visit_prisoners',
            'label' => 'Did you visit prisoners and comfort the afflicted?',
            'category' => JUDGMENT_CATEGORY_CORPORAL
        ],
        
        // Additional spiritual checks
        [
            'id' => 'forgiveness',
            'label' => 'Did you forgive those who wronged you?',
            'category' => JUDGMENT_CATEGORY_ADDITIONAL
        ],
        [
            'id' => 'scripture',
            'label' => 'Did you read Scripture today?',
            'category' => JUDGMENT_CATEGORY_ADDITIONAL
        ],
        [
            'id' => 'fasting',
            'label' => 'Did you practice self-discipline today?',
            'category' => JUDGMENT_CATEGORY_ADDITIONAL
        ]
    ];
}

/**
 * Get items by category
 * 
 * @param string $category The category to filter by
 * @return array Filtered items
 */
function getJudgmentItemsByCategory($category) {
    $allItems = getJudgmentChecklistItems();
    return array_filter($allItems, function($item) use ($category) {
        return $item['category'] === $category;
    });
}

/**
 * Get IDs of items by category
 * 
 * @param string $category The category to filter by
 * @return array Array of item IDs
 */
function getJudgmentItemIdsByCategory($category) {
    $categoryItems = getJudgmentItemsByCategory($category);
    return array_map(function($item) {
        return $item['id'];
    }, $categoryItems);
}

/**
 * Get all spiritual work items
 * 
 * @return array Spiritual items
 */
function getSpiritualWorkItems() {
    return getJudgmentItemsByCategory(JUDGMENT_CATEGORY_SPIRITUAL);
}

/**
 * Get all corporal work items (Matthew 25:35-36)
 * 
 * @return array Corporal works items
 */
function getCorporalWorkItems() {
    return getJudgmentItemsByCategory(JUDGMENT_CATEGORY_CORPORAL);
}

/**
 * Get all corporal work item IDs
 * 
 * @return array Array of corporal work item IDs
 */
function getCorporalWorkItemIds() {
    return getJudgmentItemIdsByCategory(JUDGMENT_CATEGORY_CORPORAL);
}

/**
 * Get all spiritual work item IDs
 * 
 * @return array Array of spiritual work item IDs
 */
function getSpiritualWorkItemIds() {
    return getJudgmentItemIdsByCategory(JUDGMENT_CATEGORY_SPIRITUAL);
}

/**
 * Get all additional spiritual work item IDs
 * 
 * @return array Array of additional spiritual work item IDs
 */
function getAdditionalSpiritualItemIds() {
    return getJudgmentItemIdsByCategory(JUDGMENT_CATEGORY_ADDITIONAL);
}

/**
 * Get all IDs of all items
 * 
 * @return array All item IDs
 */
function getAllJudgmentItemIds() {
    $allItems = getJudgmentChecklistItems();
    return array_map(function($item) {
        return $item['id'];
    }, $allItems);
}

/**
 * Get item by ID
 * 
 * @param string $id The item ID to find
 * @return array|null The item or null if not found
 */
function getJudgmentItemById($id) {
    $allItems = getJudgmentChecklistItems();
    foreach ($allItems as $item) {
        if ($item['id'] === $id) {
            return $item;
        }
    }
    return null;
}

/**
 * Renders a single judgment checklist item
 * 
 * @param string $id The ID of the item to render
 * @return string HTML output or empty string if item not found
 */
function renderSingleJudgmentItem($id) {
    $item = getJudgmentItemById($id);
    if (!$item) {
        return '';
    }
    
    return '<div class="checklist-item">
        <input type="checkbox" id="' . $item['id'] . '" class="judgment-check">
        <label for="' . $item['id'] . '">' . $item['label'] . '</label>
    </div>';
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
    
    // If no items selected, include all items
    if (empty($selectedItems)) {
        foreach ($allItems as $item) {
            $html .= '<div class="checklist-item">
                <input type="checkbox" id="' . $item['id'] . '" class="judgment-check">
                <label for="' . $item['id'] . '">' . $item['label'] . '</label>
            </div>';
        }
    } else {
        // Include only the selected items
        foreach ($allItems as $item) {
            if (in_array($item['id'], $selectedItems)) {
                $html .= '<div class="checklist-item">
                    <input type="checkbox" id="' . $item['id'] . '" class="judgment-check">
                    <label for="' . $item['id'] . '">' . $item['label'] . '</label>
                </div>';
            }
        }
    }
    
    $html .= '</div>';
    return $html;
}

/**
 * Renders HTML for the judgment checklist items organized by category
 * 
 * @param string $class Optional CSS class for the checklist
 * @return string HTML output
 */
function renderCategorizedJudgmentChecklist($class = 'checklist') {
    $allItems = getJudgmentChecklistItems();
    $categorized = [];
    
    // Organize items by category
    foreach ($allItems as $item) {
        $category = $item['category'];
        if (!isset($categorized[$category])) {
            $categorized[$category] = [];
        }
        $categorized[$category][] = $item;
    }
    
    $html = '';
    
    // Render spiritual works
    if (isset($categorized[JUDGMENT_CATEGORY_SPIRITUAL])) {
        $html .= '<div class="checklist-category mb-4">
            <h5>Spiritual Works</h5>
            <div class="' . $class . '">';
        
        foreach ($categorized[JUDGMENT_CATEGORY_SPIRITUAL] as $item) {
            $html .= '<div class="checklist-item">
                <input type="checkbox" id="' . $item['id'] . '" class="judgment-check">
                <label for="' . $item['id'] . '">' . $item['label'] . '</label>
            </div>';
        }
        
        $html .= '</div></div>';
    }
    
    // Render corporal works of mercy
    if (isset($categorized[JUDGMENT_CATEGORY_CORPORAL])) {
        $html .= '<div class="checklist-category mb-4">
            <h5>Corporal Works of Mercy</h5>
            <div class="' . $class . '">';
        
        foreach ($categorized[JUDGMENT_CATEGORY_CORPORAL] as $item) {
            $html .= '<div class="checklist-item">
                <input type="checkbox" id="' . $item['id'] . '" class="judgment-check">
                <label for="' . $item['id'] . '">' . $item['label'] . '</label>
            </div>';
        }
        
        $html .= '</div></div>';
    }
    
    // Render additional checks
    if (isset($categorized[JUDGMENT_CATEGORY_ADDITIONAL])) {
        $html .= '<div class="checklist-category mb-4">
            <h5>Additional Spiritual Practices</h5>
            <div class="' . $class . '">';
        
        foreach ($categorized[JUDGMENT_CATEGORY_ADDITIONAL] as $item) {
            $html .= '<div class="checklist-item">
                <input type="checkbox" id="' . $item['id'] . '" class="judgment-check">
                <label for="' . $item['id'] . '">' . $item['label'] . '</label>
            </div>';
        }
        
        $html .= '</div></div>';
    }
    
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