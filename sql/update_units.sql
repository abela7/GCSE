-- First, clear existing units
TRUNCATE TABLE units;

-- Create the access_course_units table
CREATE TABLE IF NOT EXISTS access_course_units (
    id INT AUTO_INCREMENT PRIMARY KEY,
    unit_code VARCHAR(10) NOT NULL,
    unit_name VARCHAR(255) NOT NULL,
    description TEXT,
    credits INT DEFAULT 3,
    is_graded BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert all units
INSERT INTO access_course_units (unit_code, unit_name, description, credits, is_graded) VALUES
('U1', 'Preparing for Success', 'Unit 1: Preparing for Success', 3, 1),
('U2', 'Academic Writing Skills', 'Unit 2: Academic Writing Skills', 3, 1),
('U3', 'Reading & Note Making', 'Unit 3: Reading & Note Making', 3, 1),
('U4', 'Use of Information and Communication Technology', 'Unit 4: Use of Information and Communication Technology', 3, 1),
('U5', 'Components of Computer Systems', 'Unit 5: Components of Computer Systems', 3, 1),
('U6', 'Algebra and Functions', 'Unit 6: Algebra and Functions', 3, 1),
('U7', 'Cyber Security Fundamentals', 'Unit 7: Cyber Security Fundamentals', 3, 1),
('U8', 'Database Development', 'Unit 8: Database Development', 3, 1),
('U9', 'Calculus', 'Unit 9: Calculus', 3, 1),
('U10', 'AI, Machine Learning and Deep Learning', 'Unit 10: AI, Machine Learning and Deep Learning', 3, 1),
('U11', 'The Safe and Ethical Use of Generative Artificial Intelligence', 'Unit 11: The Safe and Ethical Use of Generative Artificial Intelligence', 3, 1),
('U12', 'Software Development', 'Unit 12: Software Development', 3, 1),
('U13', 'Pure Maths', 'Unit 13: Pure Maths', 3, 1),
('U14', 'Study Skills Portfolio Building', 'Unit 14: Study Skills Portfolio Building', 3, 1),
('U15', 'Programming Constructs', 'Unit 15: Programming Constructs', 3, 1),
('U16', 'Further Differentiation', 'Unit 16: Further Differentiation', 3, 1),
('U17', 'Web Page Design and Production', 'Unit 17: Web Page Design and Production', 3, 1),
('U18', 'Further Trigonometry', 'Unit 18: Further Trigonometry', 3, 1); 