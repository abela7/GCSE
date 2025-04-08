-- Mood Tracking Tables for GCSE Tracker

-- Table for storing mood entries
CREATE TABLE IF NOT EXISTS `mood_entries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL DEFAULT current_timestamp(),
  `mood_level` tinyint(1) NOT NULL COMMENT 'Scale of 1-5 (1=very low, 5=very high)',
  `notes` text DEFAULT NULL,
  `associated_subject_id` int(11) DEFAULT NULL COMMENT 'Optional link to subject',
  `associated_topic_id` int(11) DEFAULT NULL COMMENT 'Optional link to topic',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `mood_date_idx` (`date`),
  KEY `mood_subject_idx` (`associated_subject_id`),
  KEY `mood_topic_idx` (`associated_topic_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table for storing mood factors (reasons affecting mood)
CREATE TABLE IF NOT EXISTS `mood_factors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `is_positive` tinyint(1) DEFAULT 1 COMMENT '1=positive factor, 0=negative factor',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_factor_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Junction table for mood entries and factors (many-to-many)
CREATE TABLE IF NOT EXISTS `mood_entry_factors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `mood_entry_id` int(11) NOT NULL,
  `mood_factor_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_entry_factor` (`mood_entry_id`,`mood_factor_id`),
  KEY `mood_entry_idx` (`mood_entry_id`),
  KEY `mood_factor_idx` (`mood_factor_id`),
  CONSTRAINT `fk_mood_entry` FOREIGN KEY (`mood_entry_id`) REFERENCES `mood_entries` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_mood_factor` FOREIGN KEY (`mood_factor_id`) REFERENCES `mood_factors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert default mood factors
INSERT INTO `mood_factors` (`name`, `description`, `is_positive`) VALUES
('Well Rested', 'Got enough sleep', 1),
('Focused', 'Able to concentrate well', 1),
('Motivated', 'Feeling driven to achieve goals', 1),
('Confident', 'Feeling self-assured about abilities', 1),
('Stressed', 'Feeling under pressure or overwhelmed', 0),
('Tired', 'Feeling fatigued or exhausted', 0),
('Anxious', 'Feeling worried about exams or performance', 0),
('Distracted', 'Having trouble focusing on studies', 0),
('Excited', 'Feeling enthusiastic about learning', 1),
('Frustrated', 'Feeling stuck or unable to progress', 0),
('Satisfied', 'Feeling content with progress made', 1),
('Overwhelmed', 'Feeling there is too much to learn', 0);
