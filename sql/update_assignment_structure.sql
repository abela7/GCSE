-- Update access_assignments table structure
ALTER TABLE access_assignments 
ADD COLUMN unit_overview TEXT AFTER unit_id,
ADD COLUMN guidance TEXT AFTER question_text,
MODIFY COLUMN overview TEXT,
MODIFY COLUMN question_text TEXT;

-- Create table for assignment criteria if it doesn't exist
CREATE TABLE IF NOT EXISTS assignment_criteria (
    id INT AUTO_INCREMENT PRIMARY KEY,
    assignment_id INT NOT NULL,
    criteria_code VARCHAR(10) NOT NULL,
    criteria_text TEXT NOT NULL,
    grade_required ENUM('pass', 'merit', 'distinction') NOT NULL DEFAULT 'pass',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (assignment_id) REFERENCES access_assignments(id) ON DELETE CASCADE
);

-- Create table for assignment guidance if we want to store multiple guidance items
CREATE TABLE IF NOT EXISTS assignment_guidance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    assignment_id INT NOT NULL,
    guidance_text TEXT NOT NULL,
    guidance_type ENUM('general', 'research', 'reference', 'technical') NOT NULL DEFAULT 'general',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (assignment_id) REFERENCES access_assignments(id) ON DELETE CASCADE
); 