-- Drop the existing table if it exists
DROP TABLE IF EXISTS `assignment_guidance`;

-- Create the updated assignment_guidance table
CREATE TABLE `assignment_guidance` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `assignment_id` int(11) NOT NULL,
    `guidance_text` text NOT NULL,
    `guidance_type` ENUM('general', 'research', 'reference', 'technical') NOT NULL DEFAULT 'general',
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `assignment_id` (`assignment_id`),
    CONSTRAINT `assignment_guidance_ibfk_1` FOREIGN KEY (`assignment_id`) REFERENCES `access_assignments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4; 