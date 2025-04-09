-- Table structure for task notification tracking
CREATE TABLE IF NOT EXISTS `task_notification_tracking` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `task_id` int(11) NOT NULL,
  `notification_type` enum('due','reminder') NOT NULL DEFAULT 'due',
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_task_notification` (`task_id`, `notification_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci; 