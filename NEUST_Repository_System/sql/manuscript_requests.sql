-- Create manuscript_requests table
CREATE TABLE IF NOT EXISTS `manuscript_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `research_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `purpose` varchar(255) NOT NULL,
  `other_purpose` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `admin_notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `research_id` (`research_id`),
  KEY `student_id` (`student_id`),
  CONSTRAINT `manuscript_requests_ibfk_1` FOREIGN KEY (`research_id`) REFERENCES `research` (`id`) ON DELETE CASCADE,
  CONSTRAINT `manuscript_requests_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;