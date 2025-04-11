-- Create birthday table for storing user's birthdate
CREATE TABLE IF NOT EXISTS `birthday` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `day` int(2) NOT NULL,
  `month` int(2) NOT NULL,
  `year` int(4) NOT NULL,
  `birthday` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert a default record if none exists (can be updated later)
INSERT INTO `birthday` (`day`, `month`, `year`, `birthday`)
SELECT 1, 1, 2000, '2000-01-01'
WHERE NOT EXISTS (SELECT 1 FROM `birthday` LIMIT 1); 