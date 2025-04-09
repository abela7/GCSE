-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Apr 09, 2025 at 01:13 PM
-- Server version: 10.11.11-MariaDB-cll-lve
-- PHP Version: 8.3.19

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `abunetdg_web_app`
--

-- --------------------------------------------------------

--
-- Table structure for table `access_assignments`
--

CREATE TABLE `access_assignments` (
  `id` int(11) NOT NULL,
  `unit_id` int(11) DEFAULT NULL,
  `unit_overview` text DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `overview` text DEFAULT NULL,
  `question_text` text DEFAULT NULL,
  `guidance` text DEFAULT NULL,
  `word_limit` int(11) DEFAULT NULL,
  `credits` int(11) NOT NULL,
  `due_date` date NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('not_started','in_progress','completed') DEFAULT 'not_started',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `total_criteria` int(11) DEFAULT 0,
  `completed_criteria` int(11) DEFAULT 0,
  `progress_percentage` decimal(5,2) DEFAULT 0.00,
  `priority` enum('low','medium','high') DEFAULT 'medium',
  `estimated_hours` int(11) DEFAULT NULL,
  `actual_hours` int(11) DEFAULT 0,
  `submitted_date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `access_assignments`
--

INSERT INTO `access_assignments` (`id`, `unit_id`, `unit_overview`, `title`, `overview`, `question_text`, `guidance`, `word_limit`, `credits`, `due_date`, `description`, `status`, `created_at`, `updated_at`, `total_criteria`, `completed_criteria`, `progress_percentage`, `priority`, `estimated_hours`, `actual_hours`, `submitted_date`) VALUES
(1, 10, 'Write a journal article that shows your understanding of AI, Machine Learning, and Deep Learning, including analysis of three current areas in deep learning.', 'AI, Machine Learning and Deep Learning', '<p><strong>The article should explain the concepts, compare types of AI and ML, discuss benefits and risks, and present three current research areas in Deep Learning. Stick to a journal-style format with Harvard references.</strong></p>', '<p>What does **Artificial Intelligence (AI)** mean? \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\r\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\n2. Explain the differences between: \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\r\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\n - Artificial Narrow Intelligence \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\r\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\n - Artificial General Intelligence \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\r\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\n - Artificial Super Intelligence \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\r\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\n3. What are the **challenges** in achieving AI? \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\r\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\n4. Discuss **successes and failures** in AI. \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\r\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\n5. What is **Machine Learning (ML)**? \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\r\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\n6. Describe types of ML: \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\r\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\n - Supervised \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\r\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\n - Unsupervised \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\r\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\n - Reinforcement \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\r\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\n7. What are the **uses and limitations** of ML? \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\r\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\n8. Compare **Machine Learning vs AI**. \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\r\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\n9. What is **Deep Learning**? \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\r\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\n10. What can/can\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\'t Deep Learning currently do? \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\r\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\n11. Explore **Deep Learning architecture**. \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\r\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\n12. Research **3 current areas** in Deep Learning.</p>', NULL, 2000, 3, '2025-04-10', 'Understanding and implementing AI, ML, and DL concepts', 'in_progress', '2025-04-01 16:51:13', '2025-04-01 21:48:44', 12, 7, 58.00, 'high', 20, 0, NULL),
(10, NULL, NULL, 'The Safe and Ethical Use of Generative Artificial Intelligence', NULL, NULL, NULL, NULL, 3, '2025-04-20', NULL, 'not_started', '2025-04-01 21:09:10', '2025-04-01 21:09:10', 0, 0, 0.00, 'high', 0, 0, NULL),
(11, NULL, NULL, 'Software Development', NULL, NULL, NULL, NULL, 6, '2025-05-12', NULL, 'not_started', '2025-04-01 21:09:10', '2025-04-01 21:09:10', 0, 0, 0.00, 'high', 48, 0, NULL),
(12, NULL, NULL, 'Study Skills Portfolio Building', NULL, NULL, NULL, NULL, 0, '2025-06-16', NULL, 'not_started', '2025-04-01 21:09:10', '2025-04-01 21:09:10', 0, 0, 0.00, 'high', 16, 0, NULL),
(13, NULL, NULL, 'Programming Constructs', NULL, NULL, NULL, NULL, 6, '2025-06-22', NULL, 'not_started', '2025-04-01 21:09:10', '2025-04-01 21:09:10', 0, 0, 0.00, 'high', 48, 0, NULL),
(14, NULL, NULL, 'Web Page Design and Production', NULL, NULL, NULL, NULL, 3, '2025-06-20', NULL, 'not_started', '2025-04-01 21:09:10', '2025-04-01 21:09:10', 0, 0, 0.00, 'high', 0, 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `access_course_units`
--

CREATE TABLE `access_course_units` (
  `id` int(11) NOT NULL,
  `unit_code` varchar(10) NOT NULL,
  `unit_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `credits` int(11) DEFAULT 3,
  `is_graded` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `access_course_units`
--

INSERT INTO `access_course_units` (`id`, `unit_code`, `unit_name`, `description`, `credits`, `is_graded`, `created_at`, `updated_at`) VALUES
(1, 'U1', 'Preparing for Success', 'Unit 1: Preparing for Success', 3, 1, '2025-04-01 16:53:33', '2025-04-01 16:53:33'),
(2, 'U2', 'Academic Writing Skills', 'Unit 2: Academic Writing Skills', 3, 1, '2025-04-01 16:53:33', '2025-04-01 16:53:33'),
(3, 'U3', 'Reading & Note Making', 'Unit 3: Reading & Note Making', 3, 1, '2025-04-01 16:53:33', '2025-04-01 16:53:33'),
(4, 'U4', 'Use of Information and Communication Technology', 'Unit 4: Use of Information and Communication Technology', 3, 1, '2025-04-01 16:53:33', '2025-04-01 16:53:33'),
(5, 'U5', 'Components of Computer Systems', 'Unit 5: Components of Computer Systems', 3, 1, '2025-04-01 16:53:33', '2025-04-01 16:53:33'),
(6, 'U6', 'Algebra and Functions', 'Unit 6: Algebra and Functions', 3, 1, '2025-04-01 16:53:33', '2025-04-01 16:53:33'),
(7, 'U7', 'Cyber Security Fundamentals', 'Unit 7: Cyber Security Fundamentals', 3, 1, '2025-04-01 16:53:33', '2025-04-01 16:53:33'),
(8, 'U8', 'Database Development', 'Unit 8: Database Development', 3, 1, '2025-04-01 16:53:33', '2025-04-01 16:53:33'),
(9, 'U9', 'Calculus', 'Unit 9: Calculus', 3, 1, '2025-04-01 16:53:33', '2025-04-01 16:53:33'),
(10, 'U10', 'AI, Machine Learning and Deep Learning', 'Unit 10: AI, Machine Learning and Deep Learning', 3, 1, '2025-04-01 16:53:33', '2025-04-01 16:53:33'),
(11, 'U11', 'The Safe and Ethical Use of Generative Artificial Intelligence', 'Unit 11: The Safe and Ethical Use of Generative Artificial Intelligence', 3, 1, '2025-04-01 16:53:33', '2025-04-01 16:53:33'),
(12, 'U12', 'Software Development', 'Unit 12: Software Development', 3, 1, '2025-04-01 16:53:33', '2025-04-01 16:53:33'),
(13, 'U13', 'Pure Maths', 'Unit 13: Pure Maths', 3, 1, '2025-04-01 16:53:33', '2025-04-01 16:53:33'),
(14, 'U14', 'Study Skills Portfolio Building', 'Unit 14: Study Skills Portfolio Building', 3, 1, '2025-04-01 16:53:33', '2025-04-01 16:53:33'),
(15, 'U15', 'Programming Constructs', 'Unit 15: Programming Constructs', 3, 1, '2025-04-01 16:53:33', '2025-04-01 16:53:33'),
(16, 'U16', 'Further Differentiation', 'Unit 16: Further Differentiation', 3, 1, '2025-04-01 16:53:33', '2025-04-01 16:53:33'),
(17, 'U17', 'Web Page Design and Production', 'Unit 17: Web Page Design and Production', 3, 1, '2025-04-01 16:53:33', '2025-04-01 16:53:33'),
(18, 'U18', 'Further Trigonometry', 'Unit 18: Further Trigonometry', 3, 1, '2025-04-01 16:53:33', '2025-04-01 16:53:33');

-- --------------------------------------------------------

--
-- Table structure for table `assessment_criteria`
--

CREATE TABLE `assessment_criteria` (
  `id` int(11) NOT NULL,
  `assignment_id` int(11) NOT NULL,
  `criteria_code` varchar(10) NOT NULL,
  `criteria_text` text NOT NULL,
  `grade_required` enum('pass','merit','distinction') NOT NULL DEFAULT 'pass',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `assessment_criteria`
--

INSERT INTO `assessment_criteria` (`id`, `assignment_id`, `criteria_code`, `criteria_text`, `grade_required`, `created_at`) VALUES
(1, 1, 'AC 11.1', 'Outline what artificial intelligence means', 'distinction', '2025-04-01 17:58:50'),
(2, 1, 'AC 11.2', 'Explain the differences between: ANI, AGI, ASI', 'distinction', '2025-04-01 17:58:50'),
(3, 1, 'AC 11.3', 'Discuss the challenges in achieving artificial intelligence', 'distinction', '2025-04-01 17:58:50'),
(4, 1, 'AC 11.4', 'Analyse the successes and failures of artificial intelligence', 'distinction', '2025-04-01 17:58:50'),
(5, 1, 'AC 21.1', 'Outline what \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\"machine learning\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\" means', 'distinction', '2025-04-01 17:58:50'),
(6, 1, 'AC 21.2', 'Explain types of machine learning: supervised, unsupervised, reinforcement', 'distinction', '2025-04-01 17:58:50'),
(7, 1, 'AC 21.3', 'Investigate the uses and limitations of machine learning', 'distinction', '2025-04-01 17:58:50'),
(8, 1, 'AC 21.4', 'Examine the difference between AI and ML', 'distinction', '2025-04-01 17:58:50'),
(9, 1, 'AC 31.1', 'Outline what \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\"deep learning\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\" means', 'distinction', '2025-04-01 17:58:50'),
(10, 1, 'AC 31.2', 'Examine deep learning architecture', 'distinction', '2025-04-01 17:58:50'),
(11, 1, 'AC 31.3', 'Discuss what deep learning can and cannot currently do', 'distinction', '2025-04-01 17:58:50'),
(12, 1, 'AC 31.4', 'Investigate three current areas of research in deep learning', 'merit', '2025-04-01 17:58:50');

-- --------------------------------------------------------

--
-- Table structure for table `assignment_criteria_progress`
--

CREATE TABLE `assignment_criteria_progress` (
  `id` int(11) NOT NULL,
  `assignment_id` int(11) NOT NULL,
  `criteria_id` int(11) NOT NULL,
  `status` enum('not_started','in_progress','completed') DEFAULT 'not_started',
  `notes` text DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `assignment_criteria_progress`
--

INSERT INTO `assignment_criteria_progress` (`id`, `assignment_id`, `criteria_id`, `status`, `notes`, `completed_at`, `created_at`, `updated_at`) VALUES
(74, 1, 1, 'completed', NULL, '2025-04-01 21:48:44', '2025-04-01 20:48:44', '2025-04-01 20:48:44'),
(75, 1, 2, 'completed', NULL, '2025-04-01 21:48:44', '2025-04-01 20:48:44', '2025-04-01 20:48:44'),
(76, 1, 3, 'completed', NULL, '2025-04-01 21:48:44', '2025-04-01 20:48:44', '2025-04-01 20:48:44'),
(77, 1, 4, 'completed', NULL, '2025-04-01 21:48:44', '2025-04-01 20:48:44', '2025-04-01 20:48:44'),
(78, 1, 5, 'completed', NULL, '2025-04-01 21:48:44', '2025-04-01 20:48:44', '2025-04-01 20:48:44'),
(79, 1, 6, 'completed', NULL, '2025-04-01 21:48:44', '2025-04-01 20:48:44', '2025-04-01 20:48:44'),
(80, 1, 7, 'completed', NULL, '2025-04-01 21:48:44', '2025-04-01 20:48:44', '2025-04-01 20:48:44');

-- --------------------------------------------------------

--
-- Table structure for table `assignment_guidance`
--

CREATE TABLE `assignment_guidance` (
  `id` int(11) NOT NULL,
  `assignment_id` int(11) NOT NULL,
  `guidance_text` text NOT NULL,
  `guidance_type` enum('general','research','reference','technical') NOT NULL DEFAULT 'general',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `assignment_guidance`
--

INSERT INTO `assignment_guidance` (`id`, `assignment_id`, `guidance_text`, `guidance_type`, `created_at`) VALUES
(1, 1, 'Follow journal article style (refer to Academic Writing Skills unit, Section 1).', 'general', '2025-04-01 17:58:50'),
(2, 1, 'Include images/diagrams where helpful.', 'general', '2025-04-01 17:58:50'),
(3, 1, 'Use Harvard referencing throughout.', 'general', '2025-04-01 17:58:50'),
(4, 1, 'Must be your original work with proper citation.', 'general', '2025-04-01 17:58:50'),
(5, 1, 'Word Limit: 2,000 words max', 'general', '2025-04-01 17:58:50'),
(6, 1, 'Include reference list and bibliography.', 'general', '2025-04-01 17:58:50');

-- --------------------------------------------------------

--
-- Table structure for table `assignment_progress`
--

CREATE TABLE `assignment_progress` (
  `id` int(11) NOT NULL,
  `assignment_id` int(11) DEFAULT NULL,
  `criteria_id` int(11) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `time_spent` decimal(4,2) DEFAULT NULL,
  `progress_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `assignment_progress_log`
--

CREATE TABLE `assignment_progress_log` (
  `id` int(11) NOT NULL,
  `assignment_id` int(11) NOT NULL,
  `action_type` enum('started','updated','completed','time_logged') NOT NULL,
  `description` text NOT NULL,
  `logged_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `assignment_progress_log`
--

INSERT INTO `assignment_progress_log` (`id`, `assignment_id`, `action_type`, `description`, `logged_at`) VALUES
(1, 1, 'updated', 'Updated criteria status to: Completed', '2025-04-01 18:05:33'),
(2, 1, 'updated', 'Updated criteria status to: Completed', '2025-04-01 18:05:43'),
(3, 1, 'updated', 'Updated criteria status to: Not_started', '2025-04-01 18:05:59');

-- --------------------------------------------------------

--
-- Table structure for table `assignment_resources`
--

CREATE TABLE `assignment_resources` (
  `id` int(11) NOT NULL,
  `assignment_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `type` enum('word_doc','pdf','powerpoint','excel','image','link','other') NOT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `file_extension` varchar(10) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `upload_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_modified` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_required` tinyint(1) DEFAULT 0,
  `download_count` int(11) DEFAULT 0,
  `mime_type` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `assignment_resources`
--

INSERT INTO `assignment_resources` (`id`, `assignment_id`, `title`, `type`, `file_name`, `file_extension`, `file_size`, `file_path`, `upload_date`, `last_modified`, `is_required`, `download_count`, `mime_type`, `description`) VALUES
(1, 1, 'Assignment Brief - AI and Machine Learning', 'word_doc', 'AI_ML_Assignment_Brief.docx', 'docx', 245000, 'uploads/assignments/unit11/AI_ML_Assignment_Brief.docx', '2025-04-01 16:37:41', '2025-04-01 16:37:41', 1, 0, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'Official assignment brief document with all requirements and marking criteria'),
(2, 1, 'Research Template', 'word_doc', 'AI_ML_Research_Template.docx', 'docx', 125000, 'uploads/assignments/unit11/AI_ML_Research_Template.docx', '2025-04-01 16:37:41', '2025-04-01 16:37:41', 1, 0, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'Template for organizing research findings and analysis');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `icon` varchar(50) NOT NULL,
  `color` varchar(20) NOT NULL,
  `display_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `icon`, `color`, `display_order`, `created_at`) VALUES
(1, 'Spiritual Life', 'fas fa-pray', '#cdaf56', 1, '2025-04-03 16:33:24'),
(2, 'Physical Health', 'fas fa-heartbeat', '#4CAF50', 2, '2025-04-03 16:33:24'),
(3, 'Mental Growth', 'fas fa-brain', '#2196F3', 3, '2025-04-03 16:33:24'),
(4, 'Productivity', 'fas fa-tasks', '#9C27B0', 4, '2025-04-03 16:33:24'),
(5, 'Spiritual Life', 'fas fa-pray', '#cdaf56', 1, '2025-04-03 16:36:01'),
(6, 'Physical Health', 'fas fa-heartbeat', '#4CAF50', 2, '2025-04-03 16:36:01'),
(7, 'Mental Growth', 'fas fa-brain', '#2196F3', 3, '2025-04-03 16:36:01'),
(8, 'Productivity', 'fas fa-tasks', '#9C27B0', 4, '2025-04-03 16:36:01');

-- --------------------------------------------------------

--
-- Table structure for table `eng_sections`
--

CREATE TABLE `eng_sections` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `section_number` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `eng_sections`
--

INSERT INTO `eng_sections` (`id`, `name`, `section_number`, `description`, `created_at`) VALUES
(1, 'Foundational Grammar', 1, 'Core grammar concepts and language mechanics', '2025-03-31 21:29:19'),
(2, 'Reading Comprehension', 2, 'Understanding and analyzing written texts', '2025-03-31 21:29:19'),
(3, 'Extended Reading Analysis', 3, 'In-depth analysis of literary texts and perspectives', '2025-03-31 21:29:19'),
(4, 'Writing Skills', 4, 'Developing writing techniques and structures', '2025-03-31 21:29:19'),
(5, 'Transactional and Creative Writing', 5, 'Different forms of writing and creative expression', '2025-03-31 21:29:19');

-- --------------------------------------------------------

--
-- Table structure for table `eng_section_progress`
--

CREATE TABLE `eng_section_progress` (
  `id` int(11) NOT NULL,
  `section_id` int(11) NOT NULL,
  `total_subsections` int(11) DEFAULT 0,
  `total_topics` int(11) DEFAULT 0,
  `completed_topics` int(11) DEFAULT 0,
  `progress_percentage` decimal(5,2) DEFAULT 0.00,
  `total_time_spent_seconds` bigint(20) DEFAULT 0,
  `last_updated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `eng_study_time_tracking`
--

CREATE TABLE `eng_study_time_tracking` (
  `id` int(11) NOT NULL,
  `topic_id` int(11) NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime DEFAULT NULL,
  `duration_seconds` int(11) DEFAULT 0,
  `status` enum('active','paused','completed') DEFAULT 'active',
  `last_pause_time` datetime DEFAULT NULL,
  `accumulated_seconds` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `eng_subsections`
--

CREATE TABLE `eng_subsections` (
  `id` int(11) NOT NULL,
  `section_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `subsection_number` varchar(10) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `eng_subsections`
--

INSERT INTO `eng_subsections` (`id`, `section_id`, `name`, `subsection_number`, `description`, `created_at`) VALUES
(69, 1, 'Parts of Speech', '1.1', 'Understanding different types of words and their functions', '2025-03-31 21:32:06'),
(70, 1, 'Sentence Structure', '1.2', 'Types and components of sentences', '2025-03-31 21:32:06'),
(71, 1, 'Punctuation', '1.3', 'Rules and usage of punctuation marks', '2025-03-31 21:32:06'),
(72, 1, 'Tenses', '1.4', 'Understanding and using different verb tenses', '2025-03-31 21:32:06'),
(73, 2, 'Reading Techniques', '2.1', 'Methods for effective reading and understanding', '2025-03-31 21:32:29'),
(74, 2, 'Understanding Fiction', '2.2', 'Analyzing fictional texts and their elements', '2025-03-31 21:32:29'),
(75, 2, 'Language Analysis', '2.3', 'Examining language use and literary devices', '2025-03-31 21:32:29'),
(76, 2, 'Structure Analysis', '2.4', 'Understanding text organization and structure', '2025-03-31 21:32:29'),
(77, 3, 'Literary Perspectives', '3.1', 'Different ways of interpreting texts', '2025-03-31 21:33:23'),
(78, 3, 'Audience and Purpose', '3.2', 'Understanding target readers and writer\'s intent', '2025-03-31 21:33:23'),
(79, 3, 'Language and Structure', '3.3', 'Analyzing language and structural features', '2025-03-31 21:33:23'),
(80, 3, 'Building Interpretation', '3.4', 'Developing analytical skills and interpretations', '2025-03-31 21:33:23'),
(81, 4, 'Writing Preparation', '4.1', 'Planning and organizing writing', '2025-03-31 21:33:23'),
(82, 4, 'Sentence & Punctuation for Effect', '4.2', 'Using language features for impact', '2025-03-31 21:33:23'),
(83, 5, 'Forms of Transactional Writing', '5.1', 'Different types of formal writing', '2025-03-31 21:33:23'),
(84, 5, 'Narrative and Descriptive Techniques', '5.2', 'Creative writing methods', '2025-03-31 21:33:23'),
(85, 5, 'Finalising Writing', '5.3', 'Editing and polishing written work', '2025-03-31 21:33:23');

-- --------------------------------------------------------

--
-- Table structure for table `eng_subsection_progress`
--

CREATE TABLE `eng_subsection_progress` (
  `id` int(11) NOT NULL,
  `subsection_id` int(11) NOT NULL,
  `total_topics` int(11) DEFAULT 0,
  `completed_topics` int(11) DEFAULT 0,
  `progress_percentage` decimal(5,2) DEFAULT 0.00,
  `total_time_spent_seconds` bigint(20) DEFAULT 0,
  `last_updated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `eng_topics`
--

CREATE TABLE `eng_topics` (
  `id` int(11) NOT NULL,
  `subsection_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `eng_topics`
--

INSERT INTO `eng_topics` (`id`, `subsection_id`, `name`, `description`, `created_at`) VALUES
(10, 69, 'Nouns & Pronouns', 'Understanding naming words and their substitutes', '2025-03-31 21:43:01'),
(11, 69, 'Verbs', 'Action and state words', '2025-03-31 21:43:01'),
(12, 69, 'Adjectives', 'Descriptive modifiers', '2025-03-31 21:43:01'),
(13, 69, 'Adverbs', 'Verb, adjective, and sentence modifiers', '2025-03-31 21:43:01'),
(14, 69, 'Articles & Determiners', 'Words that introduce nouns', '2025-03-31 21:43:01'),
(15, 69, 'Prepositions', 'Relationship words', '2025-03-31 21:43:01'),
(16, 69, 'Conjunctions', 'Connecting words', '2025-03-31 21:43:01'),
(17, 69, 'Interjections', 'Exclamatory words', '2025-03-31 21:43:01'),
(18, 70, 'Simple Sentences', 'Basic sentence structures', '2025-03-31 21:43:01'),
(19, 70, 'Compound Sentences', 'Connected simple sentences', '2025-03-31 21:43:01'),
(20, 70, 'Complex Sentences', 'Independent and dependent clauses', '2025-03-31 21:43:01'),
(21, 70, 'Compound-Complex Sentences', 'Multiple clause structures', '2025-03-31 21:43:01'),
(22, 70, 'Sentence Types', 'Declarative, interrogative, imperative, exclamatory', '2025-03-31 21:43:01'),
(23, 70, 'Subject-Verb Agreement', 'Matching subjects with verbs', '2025-03-31 21:43:01'),
(24, 71, 'Full Stops, Question Marks, Exclamation Marks', 'End punctuation', '2025-03-31 21:43:53'),
(25, 71, 'Commas', 'Uses and rules for commas', '2025-03-31 21:43:53'),
(26, 71, 'Semicolons & Colons', 'Advanced punctuation', '2025-03-31 21:43:53'),
(27, 71, 'Quotation Marks & Dialogue', 'Punctuating speech and quotations', '2025-03-31 21:43:53'),
(28, 71, 'Apostrophes', 'Possession and contraction', '2025-03-31 21:43:53'),
(29, 71, 'Hyphens & Dashes', 'Joining and separating punctuation', '2025-03-31 21:43:53'),
(30, 71, 'Brackets & Parentheses', 'Enclosing additional information', '2025-03-31 21:43:53'),
(31, 72, 'Present Tense', 'Current time verbs', '2025-03-31 21:43:53'),
(32, 72, 'Past Tense', 'Previous time verbs', '2025-03-31 21:43:53'),
(33, 72, 'Future Tense', 'Coming time verbs', '2025-03-31 21:43:53'),
(34, 72, 'Conditional Forms', 'Hypothetical situations', '2025-03-31 21:43:53'),
(35, 72, 'Active & Passive Voice', 'Subject-action relationships', '2025-03-31 21:43:53'),
(36, 73, 'Skimming & Scanning', 'Quick reading methods', '2025-03-31 21:43:53'),
(37, 73, 'Inference & Deduction', 'Drawing conclusions from text', '2025-03-31 21:43:53'),
(38, 73, 'Contextual Understanding', 'Understanding context', '2025-03-31 21:43:53'),
(39, 73, 'Critical Reading', 'Analyzing and evaluating texts', '2025-03-31 21:43:53'),
(40, 73, 'Summarizing & Paraphrasing', 'Condensing and rephrasing text', '2025-03-31 21:43:53'),
(41, 74, 'Plot Analysis', 'Understanding story structure', '2025-03-31 21:43:53'),
(42, 74, 'Character Development', 'Analyzing character growth', '2025-03-31 21:43:53'),
(43, 74, 'Setting & Atmosphere', 'Environment and mood', '2025-03-31 21:43:53'),
(44, 74, 'Themes & Motifs', 'Main ideas and recurring elements', '2025-03-31 21:43:53'),
(45, 74, 'Narrative Perspective', 'Point of view and narration', '2025-03-31 21:43:53'),
(46, 75, 'Identifying Literary Devices', 'Recognizing writing techniques', '2025-03-31 21:45:07'),
(47, 75, 'Analyzing Word Choice', 'Examining vocabulary selection', '2025-03-31 21:45:07'),
(48, 75, 'Tone & Mood', 'Emotional impact of writing', '2025-03-31 21:45:07'),
(49, 75, 'Imagery & Symbolism', 'Visual and symbolic elements', '2025-03-31 21:45:07'),
(50, 75, 'Sound Devices', 'Phonetic techniques', '2025-03-31 21:45:07'),
(51, 76, 'Text Organization', 'Overall text structure', '2025-03-31 21:45:07'),
(52, 76, 'Beginning, Middle, End Structure', 'Narrative progression', '2025-03-31 21:45:07'),
(53, 76, 'Paragraph Structure', 'Paragraph organization', '2025-03-31 21:45:07'),
(54, 76, 'Tension & Climax', 'Building and resolving conflict', '2025-03-31 21:45:07'),
(55, 76, 'Foreshadowing & Flashback', 'Time manipulation in narrative', '2025-03-31 21:45:07'),
(56, 77, 'Unit 3: Themes and Ideas', 'Different ways to read texts', '2025-03-31 21:45:07'),
(57, 77, 'Unit 4: Ideas and Perspectives', 'Understanding period influences', '2025-03-31 21:45:07'),
(58, 77, 'Unit 5: The Writer\'s Perspective', 'Cultural influences on interpretation', '2025-03-31 21:45:07'),
(61, 78, 'Unit 6: Audience and Purpose in Fiction', 'Understanding intended readers', '2025-03-31 21:45:07'),
(62, 78, 'Unit 7: Audience and Purpose in Non-Fiction', 'Understanding author\'s goals', '2025-03-31 21:45:07'),
(66, 79, 'Unit 8: Features of Language in Fiction', 'Deep examination of language choices', '2025-03-31 21:45:07'),
(67, 79, 'Unit 9: Features of Language in Non-Fiction', 'Complex structural devices', '2025-03-31 21:45:07'),
(68, 79, 'Unit 10: Language and Structure', 'Sustained figurative language', '2025-03-31 21:45:07'),
(71, 80, 'Unit 11: Communicating Ideas', 'Choosing supporting quotations', '2025-03-31 21:45:56'),
(72, 80, 'Unit 12: Language Choices', 'Building analytical responses', '2025-03-31 21:45:56'),
(73, 80, 'Unit 13: Structural Devices', 'Considering different interpretations', '2025-03-31 21:45:56'),
(74, 80, 'Unit 14: Selecting Appropriate Examples', 'Assessing effectiveness', '2025-03-31 21:45:56'),
(75, 80, 'Unit 15: Comparing Texts', 'Comparing different texts', '2025-03-31 21:45:56'),
(76, 81, 'Unit 18: Planning Your Writing', 'Methods for organizing writing', '2025-03-31 21:45:56'),
(77, 81, 'Unit 19: Beginnings, Middles, and Endings', 'Brainstorming and research', '2025-03-31 21:45:56'),
(78, 81, 'Unit 20: Writing for Audience and Purpose', 'Organizing content effectively', '2025-03-31 21:45:56'),
(81, 82, 'Unit 21: Using Punctuation', 'Using different sentence types', '2025-03-31 21:45:56'),
(82, 82, 'Unit 22: Using Sentences and Punctuation for Effect', 'Creating effects with punctuation', '2025-03-31 21:45:56'),
(86, 83, 'Unit 23: Form in Transactional Writing', 'Formal and informal correspondence', '2025-03-31 21:45:56'),
(87, 83, 'Unit 24: Ideas for Writing', 'Informative writing', '2025-03-31 21:45:56'),
(91, 84, 'Unit 25: Writing Narratives', 'Narrative organization', '2025-03-31 21:45:56'),
(92, 84, 'Unit 26: Writing Descriptions', 'Developing believable characters', '2025-03-31 21:45:56'),
(93, 84, 'Unit 27: Writing Monologues', 'Creating vivid environments', '2025-03-31 21:45:56'),
(94, 84, 'Unit 28: Crafting Language for Effect', 'Sensory and detailed description', '2025-03-31 21:45:56'),
(96, 85, 'Unit 29: Checking and Editing', 'Improving written work', '2025-03-31 21:45:56'),
(97, 85, 'Unit 30: Writing Texts', 'Checking for accuracy', '2025-03-31 21:45:56'),
(101, 80, 'Unit 16: Analysing Fictional Texts', 'Analysis of fiction texts', '2025-03-31 21:48:27'),
(102, 80, 'Unit 17: Analysing Non-Fictional Texts', 'Analysis of non-fiction texts', '2025-03-31 21:48:27');

-- --------------------------------------------------------

--
-- Table structure for table `eng_topic_progress`
--

CREATE TABLE `eng_topic_progress` (
  `id` int(11) NOT NULL,
  `topic_id` int(11) NOT NULL,
  `status` enum('not_started','in_progress','completed') DEFAULT 'not_started',
  `total_time_spent` int(11) DEFAULT 0,
  `confidence_level` int(11) DEFAULT 0,
  `last_studied` datetime DEFAULT NULL,
  `completion_date` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `eng_topic_progress`
--

INSERT INTO `eng_topic_progress` (`id`, `topic_id`, `status`, `total_time_spent`, `confidence_level`, `last_studied`, `completion_date`, `notes`) VALUES
(1, 36, 'completed', 0, 5, '2025-04-08 03:07:47', '2025-04-08 03:07:47', '');

-- --------------------------------------------------------

--
-- Table structure for table `exams`
--

CREATE TABLE `exams` (
  `id` int(11) NOT NULL,
  `subject_id` int(11) DEFAULT NULL,
  `title` varchar(100) NOT NULL,
  `exam_date` datetime NOT NULL,
  `duration` int(11) DEFAULT 120,
  `location` varchar(100) DEFAULT NULL,
  `exam_board` varchar(50) DEFAULT NULL,
  `paper_code` varchar(20) DEFAULT NULL,
  `importance` int(11) DEFAULT 3,
  `notes` text DEFAULT NULL,
  `section_a_topics` text DEFAULT NULL,
  `section_b_topics` text DEFAULT NULL,
  `total_marks` int(11) DEFAULT 0,
  `calculator_allowed` tinyint(1) DEFAULT 0,
  `formula_sheet_provided` tinyint(1) DEFAULT 0,
  `equipment_needed` text DEFAULT NULL,
  `special_instructions` text DEFAULT NULL,
  `revision_resources` text DEFAULT NULL,
  `exam_tips` text DEFAULT NULL,
  `syllabus_link` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `exams`
--

INSERT INTO `exams` (`id`, `subject_id`, `title`, `exam_date`, `duration`, `location`, `exam_board`, `paper_code`, `importance`, `notes`, `section_a_topics`, `section_b_topics`, `total_marks`, `calculator_allowed`, `formula_sheet_provided`, `equipment_needed`, `special_instructions`, `revision_resources`, `exam_tips`, `syllabus_link`) VALUES
(1, 2, 'Mathematics Paper 1: Non-Calculator (Higher Tier)', '2025-05-15 09:00:00', 90, 'Main Hall', 'Edexcel', 'MATH1', 5, 'This is the first of three papers for the Edexcel GCSE Maths Higher Tier. No calculator allowed. Paper contributes one-third to the overall Maths GCSE grade.', 'Covers:\n\r\n- Number\n\r\n- Algebra\n\r\n- Ratio, proportion and rates of change\n\r\n- Geometry and measures\n\r\n- Probability\n\r\n- Statistics\n\n\r\nSkills Tested:\n\r\n- Manual calculation skills\n\r\n- Algebraic manipulation\n\r\n- Applying maths to problem-solving situations\n\r\n- Interpreting data and diagrams', 'All questions are compulsory.\n\r\n- Mixture of short, structured and multi-step questions\n\r\n- Word problems, diagrams, real-world maths scenarios', 80, 0, 1, 'Black pen\nPencil\nRuler\nRubber\nProtractor\nCompass\nScientific calculator (not allowed for Paper 1)', '- Show full working out\n\r\n- Write clearly and label diagrams\n\r\n- Attempt every question\n\r\n- Answer all questions in the spaces provided', '1. Pearson Maths Higher Tier Revision Guide\n\r\n2. Exam-style practice papers (non-calculator)\n\r\n3. GCSE Maths Tutor YouTube\n\r\n4. Corbettmaths revision cards\n\r\n5. Dr Frost Maths practice sets', '- Read each question carefully\n\r\n- Show full method even if unsure\n\r\n- Watch your units and rounding\n\r\n- Check your work with estimation', 'https://qualifications.pearson.com/content/dam/pdf/GCSE/mathematics/2015/specification-and-sample-assesment/gcse-maths-2015-specification.pdf'),
(2, 2, 'Mathematics Paper 2: Calculator (Higher Tier)', '2025-06-04 09:00:00', 90, 'Main Hall', 'Edexcel', 'MATH2', 5, 'This is the second of three papers for Edexcel GCSE Maths Higher Tier. A calculator is allowed. Paper contributes one-third to the overall Maths GCSE grade.', 'Covers:\n\r\n- Number\n\r\n- Algebra\n\r\n- Ratio, proportion and rates of change\n\r\n- Geometry and measures\n\r\n- Probability\n\r\n- Statistics\n\n\r\nSkills Tested:\n\r\n- Calculator-based problem solving\n\r\n- Interpreting multi-step problems\n\r\n- Working with percentages, indices, graphs, etc.', 'All questions are compulsory.\n\r\n- Real-world applications of maths\n\r\n- Use of formulae, diagrams, conversions\n\r\n- Mix of short and long questions requiring written methods', 80, 1, 1, 'Black pen\nPencil\nRuler\nRubber\nProtractor\nCompass\nScientific calculator', '- Use your calculator efficiently\n\r\n- Show working even when using a calculator\n\r\n- Write clearly and neatly\n\r\n- Round answers only when instructed', '1. Pearson Maths Higher Tier Workbook\n\r\n2. JustMaths practice sets\n\r\n3. Corbettmaths and Maths Genie topic videos\n\r\n4. Examwizard past papers\n\r\n5. Dr Frost diagnostic quizzes', '- Use the calculator for accuracy\n\r\n- Check mode (degrees/radians) before you begin\n\r\n- Use formula sheet to save time\n\r\n- Estimate answers to catch errors', 'https://qualifications.pearson.com/content/dam/pdf/GCSE/mathematics/2015/specification-and-sample-assesment/gcse-maths-2015-specification.pdf'),
(3, 2, 'Mathematics Paper 3: Calculator (Higher Tier)', '2025-06-11 09:00:00', 90, 'Main Hall', 'Edexcel', 'MATH3', 5, 'This is the third and final paper for Edexcel GCSE Maths Higher Tier. A calculator is allowed. Paper contributes one-third to the overall Maths GCSE grade.', 'Covers:\n\r\n- Number\n\r\n- Algebra\n\r\n- Ratio, proportion and rates of change\n\r\n- Geometry and measures\n\r\n- Probability\n\r\n- Statistics\n\n\r\nSkills Tested:\n\r\n- Deep understanding across all topics\n\r\n- Linking multiple mathematical skills in one question\n\r\n- Problem-solving and logical reasoning', 'All questions are compulsory.\n\r\n- Longer, more challenging questions often appear here\n\r\n- Expect multi-step reasoning and application\n\r\n- Requires strong topic crossover understanding', 80, 1, 1, 'Black pen\nPencil\nRuler\nRubber\nProtractor\nCompass\nScientific calculator', '- Don’t panic on tricky questions – break them down\n\r\n- Show all working\n\r\n- Use formula sheet and calculator together wisely\n\r\n- Answer all questions – attempt even hard ones', '1. Advanced Maths Problem Packs\n\r\n2. Mixed-topic mock papers\n\r\n3. Corbettmaths Practice Papers\n\r\n4. Hegarty Maths Tasks\n\r\n5. GCSE Maths Tutor challenge questions', '- Paper 3 often includes trickier, unseen question types\n\r\n- Stay calm and take time to understand the problem\n\r\n- Label diagrams clearly\n\r\n- Check calculations at the end', 'https://qualifications.pearson.com/content/dam/pdf/GCSE/mathematics/2015/specification-and-sample-assesment/gcse-maths-2015-specification.pdf'),
(4, 1, 'English Language Paper 1: Fiction and Imaginative Writing', '2025-05-23 09:00:00', 105, 'Main Hall', 'Edexcel', 'ENG1', 5, 'Paper 1 = 50% of overall English Language GCSE grade. Focus on 19th-century fiction and creative writing.', 'SECTION A - READING (40 marks, 55 minutes):\n\r\n- Question 1 (4 marks): Identify 4 things from text\n\r\n- Question 2 (6 marks): Language analysis\n\r\n- Question 3 (6 marks): Structure analysis\n\r\n- Question 4 (24 marks): Evaluation\n\n\r\nSkills Tested:\n\r\n- Understanding explicit & implicit meanings\n\r\n- Analysing writer\'s use of language and structure\n\r\n- Evaluating text effectiveness\n\r\n- Selecting and using evidence', 'SECTION B - CREATIVE WRITING (40 marks, 50 minutes):\n\r\n- 24 marks: Content & Organisation\n\r\n- 16 marks: Spelling, Punctuation & Grammar (SPaG)\n\n\r\nTask Types:\n\r\n- Narrative story writing\n\r\n- Descriptive scene writing\n\n\r\nSkills Tested:\n\r\n- Creative use of language\n\r\n- Using structure effectively\n\r\n- Clear voice and sense of audience/purpose\n\r\n- Technical accuracy (SPaG)', 64, 0, 0, 'Black pen (required)\nHighlighter (optional but recommended)\nEraser', '- Answer ALL questions in Section A\n\r\n- Choose ONE question from Section B\n\r\n- SPaG is assessed (especially in Section B)\n\r\n- No calculator or dictionary allowed\n\r\n- Spend about 55 minutes on Section A\n\r\n- Spend about 50 minutes on Section B (35 mins writing + 15 mins planning & checking)', '1. Official Pearson Revision Guide\n\r\n2. Mr Bruff YouTube Channel\n\r\n3. Past Papers and Mark Schemes\n\r\n4. Examiner Reports\n\r\n5. Practice 19th Century Fiction Extracts\n\r\n6. Creative Writing Prompts Collection', 'Reading Section (Q1-Q4):\n\r\n- Use quotations in every answer (except Q1)\n\r\n- For language (Q2), focus on effect on the reader\n\r\n- For structure (Q3), think about beginning-middle-end, contrast, or shifts\n\r\n- In evaluation (Q4), use your opinion, backed up with analysis\n\n\r\nWriting Section:\n\r\n- Plan for 5 minutes: structure your ideas\n\r\n- Use sensory language – what can you see, hear, feel?\n\r\n- Use a range of sentence lengths and punctuation\n\r\n- Use paragraphs clearly – don\'t write in one chunk!\n\r\n- Proofread at the end – 16 SPaG marks matter!', 'https://qualifications.pearson.com/content/dam/pdf/GCSE/English%20Language/2015/specification-and-sample-assessments/9781446914281_GCSE_2015_L12_EngLang.pdf'),
(5, 1, 'English Language Paper 2: Non-Fiction and Transactional Writing', '2025-06-06 09:00:00', 125, 'Main Hall', 'Edexcel', 'ENG2', 5, 'Paper 2 = 50% of overall English Language GCSE grade. Focus on non-fiction reading and transactional writing.', 'SECTION A - READING (56 marks, ~70 minutes):\n\r\n- 2 unseen non-fiction texts (20th & 21st century)\n\r\n- Q1 (4 marks): Identify 4 facts/ideas\n\r\n- Q2 (6 marks): Language analysis (Text 1)\n\r\n- Q3 (6 marks): Structure analysis (Text 1)\n\r\n- Q4 (15 marks): Evaluation (Text 1)\n\r\n- Q5 (1-2 marks): Key ideas from Text 2\n\r\n- Q6 (15 marks): Compare writer\'s viewpoints\n\r\n- Q7 (9 marks): Synthesis of key ideas\n\n\r\nSkills Tested:\n\r\n- Reading comprehension\n\r\n- Analysing language and structure\n\r\n- Evaluating techniques and effects\n\r\n- Comparing perspectives and viewpoints', 'SECTION B - TRANSACTIONAL WRITING (40 marks, ~55 minutes):\n\r\n- 24 marks: Content & Organisation\n\r\n- 16 marks: Spelling, Punctuation & Grammar (SPaG)\n\n\r\nTask Types:\n\r\n- Article, Letter, Speech, Review, Leaflet, Essay\n\n\r\nSkills Tested:\n\r\n- Adapting tone and structure for audience/purpose\n\r\n- Organising ideas clearly\n\r\n- Persuasive and clear writing techniques\n\r\n- Technical accuracy (SPaG)', 96, 0, 0, 'Black pen (required)\nHighlighter (optional but recommended)\nEraser', '- Answer ALL questions in Section A\n\r\n- Choose ONE task from Section B\n\r\n- SPaG is assessed (especially in Section B)\n\r\n- No calculator or dictionary allowed\n\r\n- Spend about 70 minutes on Section A\n\r\n- Spend about 55 minutes on Section B (plan, write, and proofread)', '1. Pearson Revision Guide\n\r\n2. Mr Bruff YouTube Channel\n\r\n3. Past Papers and Mark Schemes\n\r\n4. Examiner Reports\n\r\n5. Sample Transactional Writing Tasks\n\r\n6. Annotated Non-Fiction Extracts', 'Reading Section (Q1–Q7):\n\r\n- Read both texts carefully and annotate key points\n\r\n- Use quotations and analyse techniques\n\r\n- Use comparative connectives in Q6 (e.g., similarly, however)\n\r\n- Focus on key differences/similarities in Q7\n\n\r\nWriting Section:\n\r\n- Know your format: speech, letter, article, etc.\n\r\n- Use rhetorical techniques (AFOREST)\n\r\n- Structure clearly: intro, main points, conclusion\n\r\n- Proofread your writing – 16 SPaG marks are critical!', 'https://qualifications.pearson.com/content/dam/pdf/GCSE/English%20Language/2015/specification-and-sample-assessments/9781446914281_GCSE_2015_L12_EngLang.pdf');

-- --------------------------------------------------------

--
-- Table structure for table `exam_reports`
--

CREATE TABLE `exam_reports` (
  `id` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `score` decimal(5,2) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `favorite_practice_items`
--

CREATE TABLE `favorite_practice_items` (
  `id` int(11) NOT NULL,
  `practice_item_id` int(11) NOT NULL COMMENT 'FK to practice_items table',
  `favorited_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `favorite_practice_items`
--

INSERT INTO `favorite_practice_items` (`id`, `practice_item_id`, `favorited_at`) VALUES
(2, 157, '2025-04-06 08:15:30'),
(3, 145, '2025-04-06 08:21:15'),
(4, 79, '2025-04-08 00:53:05');

-- --------------------------------------------------------

--
-- Table structure for table `files`
--

CREATE TABLE `files` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `path` varchar(255) NOT NULL,
  `type` varchar(50) DEFAULT NULL,
  `size` int(11) DEFAULT NULL,
  `subject_id` int(11) DEFAULT NULL,
  `topic_id` int(11) DEFAULT NULL,
  `upload_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `habits`
--

CREATE TABLE `habits` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `point_rule_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(50) DEFAULT 'fas fa-check-circle' COMMENT 'Font Awesome icon class name',
  `target_time` time DEFAULT NULL,
  `current_points` int(11) DEFAULT 0,
  `total_completions` int(11) DEFAULT 0,
  `total_procrastinated` int(11) DEFAULT 0,
  `total_skips` int(11) DEFAULT 0,
  `current_streak` int(11) DEFAULT 0,
  `longest_streak` int(11) DEFAULT 0,
  `success_rate` decimal(5,2) DEFAULT 0.00,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `habits`
--

INSERT INTO `habits` (`id`, `category_id`, `point_rule_id`, `name`, `description`, `icon`, `target_time`, `current_points`, `total_completions`, `total_procrastinated`, `total_skips`, `current_streak`, `longest_streak`, `success_rate`, `is_active`, `created_at`, `updated_at`) VALUES
(21, 1, 3, 'Morning Prayer', 'ተግተን እንፀልይ ወደ ፈተና እንዳንገባ!', 'fas fa-check-circle', '09:00:00', 0, 0, 0, 0, 0, 0, 0.00, 1, '2025-04-08 05:45:56', '2025-04-08 05:45:56'),
(22, 3, 2, 'Use Meloxline', 'Consistency is the key!', 'fas fa-check-circle', '08:50:00', -7, 0, 0, 1, 0, 0, 0.00, 1, '2025-04-08 05:47:04', '2025-04-09 03:52:40');

-- --------------------------------------------------------

--
-- Table structure for table `habit_categories`
--

CREATE TABLE `habit_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `color` varchar(7) DEFAULT '#6c757d',
  `icon` varchar(50) DEFAULT 'fas fa-folder' COMMENT 'Font Awesome icon class name',
  `display_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `habit_categories`
--

INSERT INTO `habit_categories` (`id`, `name`, `description`, `color`, `icon`, `display_order`, `created_at`) VALUES
(1, 'Spiritual Life', 'Religious and spiritual activities', '#e6d305', 'fas fa-pray', 1, '2025-04-01 21:34:00'),
(2, 'Education', 'Learning and academic activities', '#1E90FF', 'fas fa-graduation-cap', 2, '2025-04-01 21:34:00'),
(3, 'Self Care', 'Personal care and wellbeing', '#4682B4', 'fas fa-spa', 3, '2025-04-01 21:34:00'),
(4, 'Work', 'Professional and career activities', '#2E8B57', 'fas fa-briefcase', 4, '2025-04-01 21:34:00'),
(5, 'Finance', 'Financial management and goals', '#DAA520', 'fas fa-coins', 5, '2025-04-01 21:34:00'),
(6, 'Family', 'Family relationships and responsibilities', '#FF69B4', 'fas fa-home', 6, '2025-04-01 21:34:00'),
(7, 'Health', 'Physical health and fitness', '#32CD32', 'fas fa-heartbeat', 7, '2025-04-01 21:34:00'),
(8, 'Sleep', 'Sleep schedule and routine', '#483D8B', 'fas fa-bed', 8, '2025-04-01 21:34:00'),
(10, 'Personal Growth', 'Self-improvement and development', '#9370DB', 'fas fa-brain', 10, '2025-04-01 21:34:00'),
(12, 'Reading', NULL, '#56a5cd', 'fas fa-book', 11, '2025-04-02 13:48:53');

-- --------------------------------------------------------

--
-- Table structure for table `habit_completions`
--

CREATE TABLE `habit_completions` (
  `id` int(11) NOT NULL,
  `habit_id` int(11) NOT NULL,
  `completion_date` date NOT NULL,
  `completion_time` time NOT NULL,
  `status` enum('completed','procrastinated','skipped') NOT NULL,
  `reason` varchar(100) DEFAULT NULL,
  `points_earned` int(11) DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `habit_completions`
--

INSERT INTO `habit_completions` (`id`, `habit_id`, `completion_date`, `completion_time`, `status`, `reason`, `points_earned`, `notes`, `created_at`) VALUES
(5, 22, '2025-04-09', '04:52:40', 'skipped', 'Being stressed', -7, 'Stressed and can\'t manage my time yet', '2025-04-09 03:52:40');

-- --------------------------------------------------------

--
-- Table structure for table `habit_point_rules`
--

CREATE TABLE `habit_point_rules` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `completion_points` int(11) NOT NULL,
  `procrastinated_points` int(11) NOT NULL,
  `skip_points` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `habit_point_rules`
--

INSERT INTO `habit_point_rules` (`id`, `name`, `description`, `completion_points`, `procrastinated_points`, `skip_points`, `created_at`) VALUES
(1, 'Basic Habit', 'Simple daily habits', 5, 2, -3, '2025-04-01 21:34:00'),
(2, 'Important Habit', 'Key daily activities', 10, 4, -7, '2025-04-01 21:34:00'),
(3, 'Critical Habit', 'Essential daily practices', 20, 8, -12, '2025-04-01 21:34:00');

-- --------------------------------------------------------

--
-- Table structure for table `habit_progress`
--

CREATE TABLE `habit_progress` (
  `id` int(11) NOT NULL,
  `habit_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `status` enum('completed','pending','skipped') NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `habit_progress`
--

INSERT INTO `habit_progress` (`id`, `habit_id`, `date`, `status`, `notes`, `created_at`) VALUES
(1, 11, '2025-04-07', 'completed', NULL, '2025-04-07 02:02:20'),
(2, 17, '2025-04-07', 'completed', NULL, '2025-04-07 02:40:58');

-- --------------------------------------------------------

--
-- Table structure for table `habit_reasons`
--

CREATE TABLE `habit_reasons` (
  `id` int(11) NOT NULL,
  `reason_text` varchar(100) NOT NULL,
  `is_default` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `habit_reasons`
--

INSERT INTO `habit_reasons` (`id`, `reason_text`, `is_default`, `created_at`) VALUES
(1, 'Using social media', 1, '2025-04-02 02:07:26'),
(2, 'Being lazy', 1, '2025-04-02 02:07:26'),
(3, 'Being moody', 1, '2025-04-02 02:07:26'),
(4, 'Being careless', 1, '2025-04-02 02:07:26'),
(5, 'Being stressed', 1, '2025-04-02 02:07:26'),
(6, 'Chatting with people', 1, '2025-04-02 02:07:26'),
(7, 'Super busy', 1, '2025-04-02 02:07:26'),
(8, 'Tired of this habit', 1, '2025-04-02 02:07:26');

-- --------------------------------------------------------

--
-- Table structure for table `math_sections`
--

CREATE TABLE `math_sections` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `section_number` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `math_sections`
--

INSERT INTO `math_sections` (`id`, `name`, `section_number`, `description`, `created_at`) VALUES
(1, 'Number', 1, 'Core number operations, place value, and numerical concepts', '2025-03-31 21:08:17'),
(2, 'Algebra', 2, 'Equations, expressions, functions, and algebraic manipulation', '2025-03-31 21:08:17'),
(3, 'Ratio, Proportion and Rates of Change', 3, 'Relationships between quantities and rates', '2025-03-31 21:08:17'),
(4, 'Geometry and Measure', 4, 'Shape, space, and measurement concepts', '2025-03-31 21:08:17'),
(5, 'Probability', 5, 'Chance, likelihood, and probability calculations', '2025-03-31 21:08:17'),
(6, 'Statistics', 6, 'Data handling, analysis, and interpretation', '2025-03-31 21:08:17');

-- --------------------------------------------------------

--
-- Table structure for table `math_subsections`
--

CREATE TABLE `math_subsections` (
  `id` int(11) NOT NULL,
  `section_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `subsection_number` varchar(10) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `math_subsections`
--

INSERT INTO `math_subsections` (`id`, `section_id`, `name`, `subsection_number`, `description`, `created_at`) VALUES
(1, 1, 'Basic Number Operations', '1.1', 'Fundamental operations and number concepts', '2025-03-31 21:09:37'),
(2, 1, 'Advanced Number Concepts', '1.2', 'Advanced operations and number theory', '2025-03-31 21:09:37'),
(3, 1, 'Accuracy and Estimation', '1.3', 'Working with approximations and errors', '2025-03-31 21:09:37'),
(4, 2, 'Basic Algebraic Manipulation', '2.1', 'Core algebraic operations and concepts', '2025-03-31 21:09:37'),
(5, 2, 'Equations and Inequalities', '2.2', 'Solving various types of equations and inequalities', '2025-03-31 21:09:37'),
(6, 2, 'Functions and Graphs', '2.3', 'Understanding and working with different types of functions', '2025-03-31 21:09:37'),
(7, 2, 'Advanced Algebra', '2.4', 'Complex algebraic concepts and proofs', '2025-03-31 21:09:37'),
(8, 3, 'Ratio', '3.1', 'Understanding and working with ratios', '2025-03-31 21:09:37'),
(9, 3, 'Percentages', '3.2', 'Calculations involving percentages', '2025-03-31 21:09:37'),
(10, 3, 'Proportion', '3.3', 'Direct and inverse proportion', '2025-03-31 21:09:37'),
(11, 3, 'Compound Measures', '3.4', 'Working with compound units and measures', '2025-03-31 21:09:37'),
(12, 4, 'Basic Geometry', '4.1', 'Fundamental geometric concepts', '2025-03-31 21:09:37'),
(13, 4, 'Transformations', '4.2', 'Geometric transformations and their properties', '2025-03-31 21:09:37'),
(14, 4, '3D Geometry', '4.3', 'Three-dimensional shapes and their properties', '2025-03-31 21:09:37'),
(15, 4, 'Advanced Geometry', '4.4', 'Complex geometric concepts and proofs', '2025-03-31 21:09:37'),
(16, 4, 'Trigonometry', '4.5', 'Trigonometric ratios and applications', '2025-03-31 21:09:37'),
(17, 5, 'Basic Probability', '5.1', 'Fundamental concepts of probability', '2025-03-31 21:09:37'),
(18, 5, 'Probability Diagrams', '5.2', 'Visual representations of probability', '2025-03-31 21:09:37'),
(19, 5, 'Advanced Probability', '5.3', 'Complex probability concepts and calculations', '2025-03-31 21:09:37'),
(20, 6, 'Data Representation', '6.1', 'Different ways to present and visualize data', '2025-03-31 21:09:37'),
(21, 6, 'Data Analysis', '6.2', 'Statistical measures and analysis techniques', '2025-03-31 21:09:37'),
(22, 6, 'Statistical Reasoning', '6.3', 'Drawing conclusions from statistical data', '2025-03-31 21:09:37');

-- --------------------------------------------------------

--
-- Table structure for table `math_topics`
--

CREATE TABLE `math_topics` (
  `id` int(11) NOT NULL,
  `subsection_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `math_topics`
--

INSERT INTO `math_topics` (`id`, `subsection_id`, `name`, `description`, `created_at`) VALUES
(186, 1, 'Understanding place value', 'Learn about the position and value of digits in numbers', '2025-03-31 21:14:18'),
(187, 1, 'Operations with integers, decimals and fractions', 'Perform calculations with different number types', '2025-03-31 21:14:18'),
(188, 1, 'Order of operations (BIDMAS/BODMAS)', 'Understanding the correct order of mathematical operations', '2025-03-31 21:14:18'),
(189, 1, 'Factors, multiples, and prime numbers', 'Explore number relationships and prime factorization', '2025-03-31 21:14:18'),
(190, 1, 'HCF and LCM', 'Find highest common factors and lowest common multiples', '2025-03-31 21:14:18'),
(191, 1, 'Powers and roots', 'Work with exponents and square/cube roots', '2025-03-31 21:14:18'),
(192, 2, 'Surds', 'Simplifying and rationalizing surds', '2025-03-31 21:14:18'),
(193, 2, 'Index laws', 'Working with negative and fractional indices', '2025-03-31 21:14:18'),
(194, 2, 'Standard form calculations', 'Calculations with numbers in scientific notation', '2025-03-31 21:14:18'),
(195, 2, 'Upper and lower bounds', 'Understanding limits of accuracy', '2025-03-31 21:14:18'),
(196, 2, 'Recurring decimals to fractions', 'Converting between decimal and fraction forms', '2025-03-31 21:14:18'),
(197, 2, 'Product rule for counting', 'Understanding combinatorial counting principles', '2025-03-31 21:14:18'),
(198, 3, 'Rounding to decimal places and significant figures', 'Different methods of approximation', '2025-03-31 21:14:18'),
(199, 3, 'Error intervals', 'Understanding and calculating error ranges', '2025-03-31 21:14:18'),
(200, 3, 'Limits of accuracy', 'Working with measurements and precision', '2025-03-31 21:14:18'),
(201, 3, 'Working with bounds', 'Calculations involving upper and lower bounds', '2025-03-31 21:14:18'),
(202, 3, 'Estimation techniques', 'Methods for approximating calculations', '2025-03-31 21:14:18'),
(203, 4, 'Collecting like terms', 'Simplifying algebraic expressions', '2025-03-31 21:14:18'),
(204, 4, 'Substitution', 'Replacing variables with values', '2025-03-31 21:14:18'),
(205, 4, 'Expanding brackets', 'Single, double, and triple bracket expansion', '2025-03-31 21:14:18'),
(206, 4, 'Factorizing expressions', 'Finding common factors and quadratic factorization', '2025-03-31 21:14:18'),
(207, 4, 'Laws of indices', 'Rules for working with powers', '2025-03-31 21:14:18'),
(208, 4, 'Algebraic fractions', 'Operations with fractional expressions', '2025-03-31 21:14:18'),
(209, 5, 'Solving linear equations', 'Methods for solving first-degree equations', '2025-03-31 21:14:18'),
(210, 5, 'Solving quadratic equations by factorization', 'Finding solutions using factoring', '2025-03-31 21:14:18'),
(211, 5, 'Quadratic formula method', 'Using the formula to solve quadratic equations', '2025-03-31 21:14:18'),
(212, 5, 'Completing the square', 'Alternative method for solving quadratics', '2025-03-31 21:14:18'),
(213, 5, 'Linear inequalities', 'Solving and representing inequalities', '2025-03-31 21:14:18'),
(214, 5, 'Quadratic inequalities', 'Solving second-degree inequalities', '2025-03-31 21:14:18'),
(215, 5, 'Graphical inequalities', 'Representing inequalities on graphs', '2025-03-31 21:14:18'),
(216, 5, 'Simultaneous equations (linear)', 'Solving systems of linear equations', '2025-03-31 21:14:18'),
(217, 5, 'Simultaneous equations (linear/quadratic)', 'Solving mixed systems of equations', '2025-03-31 21:14:18'),
(218, 6, 'Linear graphs', 'Understanding and plotting straight lines', '2025-03-31 21:14:18'),
(219, 6, 'Quadratic graphs', 'Parabolas and their properties', '2025-03-31 21:14:18'),
(220, 6, 'Cubic and reciprocal graphs', 'Higher degree and reciprocal functions', '2025-03-31 21:14:18'),
(221, 6, 'Exponential graphs', 'Understanding exponential growth and decay', '2025-03-31 21:14:18'),
(222, 6, 'Graph transformations', 'Translations, reflections, and stretches', '2025-03-31 21:14:18'),
(223, 6, 'Coordinate geometry', 'Working with coordinates and equations', '2025-03-31 21:14:18'),
(224, 6, 'Perpendicular lines', 'Finding perpendicular line equations', '2025-03-31 21:14:18'),
(225, 6, 'Equation of a circle and tangents', 'Circle equations and their properties', '2025-03-31 21:14:18'),
(226, 6, 'Linear sequences', 'Arithmetic progressions and nth term', '2025-03-31 21:14:18'),
(227, 6, 'Quadratic and geometric sequences', 'More complex sequence types', '2025-03-31 21:14:18'),
(228, 7, 'Algebraic proof', 'Proving mathematical statements algebraically', '2025-03-31 21:14:18'),
(229, 7, 'Function notation', 'Understanding and using function notation', '2025-03-31 21:14:18'),
(230, 7, 'Inverse and composite functions', 'Working with function operations', '2025-03-31 21:14:18'),
(231, 7, 'Iterative methods', 'Finding solutions by iteration', '2025-03-31 21:14:18'),
(232, 7, 'Turning points', 'Finding and using maxima and minima', '2025-03-31 21:14:18'),
(233, 7, 'Completing the square', 'Using completing the square for various purposes', '2025-03-31 21:14:18'),
(234, 8, 'Simplifying ratios', 'Techniques for reducing ratios to their simplest form', '2025-03-31 21:14:18'),
(235, 8, 'Dividing quantities in a ratio', 'Solving problems involving sharing in a ratio', '2025-03-31 21:14:18'),
(236, 8, 'Multi-part ratios', 'Working with ratios involving three or more parts', '2025-03-31 21:14:18'),
(237, 8, 'Converting between fractions and ratios', 'Understanding the relationship between fractions and ratios', '2025-03-31 21:14:18'),
(238, 8, 'Problem solving with ratios', 'Applied ratio problems in real-world contexts', '2025-03-31 21:14:18'),
(239, 9, 'Percentage calculations', 'Basic percentage operations and conversions', '2025-03-31 21:14:18'),
(240, 9, 'Percentage increase and decrease', 'Calculating percentage changes', '2025-03-31 21:14:18'),
(241, 9, 'Reverse percentages', 'Finding original values from percentage changes', '2025-03-31 21:14:18'),
(242, 9, 'Compound percentage change', 'Multiple percentage changes', '2025-03-31 21:14:18'),
(243, 9, 'Simple and compound interest', 'Financial applications of percentages', '2025-03-31 21:14:18'),
(244, 9, 'Depreciation', 'Calculating value decrease over time', '2025-03-31 21:14:18'),
(245, 10, 'Direct proportion', 'Understanding direct relationships', '2025-03-31 21:14:18'),
(246, 10, 'Inverse proportion', 'Understanding inverse relationships', '2025-03-31 21:14:18'),
(247, 10, 'Graphs of proportion relationships', 'Visualizing proportional relationships', '2025-03-31 21:14:18'),
(248, 10, 'Rates of change', 'Understanding how quantities change relative to each other', '2025-03-31 21:14:18'),
(249, 10, 'Growth and decay problems', 'Applications of exponential change', '2025-03-31 21:14:18'),
(250, 11, 'Speed, distance and time', 'Understanding and using speed calculations', '2025-03-31 21:14:18'),
(251, 11, 'Density, mass and volume', 'Working with density relationships', '2025-03-31 21:14:18'),
(252, 11, 'Pressure, force and area', 'Understanding pressure calculations', '2025-03-31 21:14:18'),
(253, 11, 'Velocity-time graphs', 'Interpreting motion graphs', '2025-03-31 21:14:18'),
(254, 11, 'Gradient as rate of change', 'Understanding gradient in real contexts', '2025-03-31 21:14:18'),
(255, 11, 'Area under a graph', 'Finding distance from velocity-time graphs', '2025-03-31 21:14:18'),
(256, 12, 'Angle facts', 'Understanding angles in lines, triangles, and polygons', '2025-03-31 21:14:18'),
(257, 12, 'Properties of 2D shapes', 'Exploring characteristics of 2D shapes', '2025-03-31 21:14:18'),
(258, 12, 'Area and perimeter calculations', 'Finding perimeter and area of shapes', '2025-03-31 21:14:18'),
(259, 12, 'Circle terminology', 'Understanding parts of circles', '2025-03-31 21:14:18'),
(260, 12, 'Circumference and area of circles', 'Calculations with circles', '2025-03-31 21:14:18'),
(261, 12, 'Arc lengths and sectors', 'Working with parts of circles', '2025-03-31 21:14:18'),
(262, 12, 'Bearings', 'Three-figure bearings and navigation', '2025-03-31 21:14:18'),
(263, 13, 'Reflection', 'Mirror images and reflection lines', '2025-03-31 21:14:18'),
(264, 13, 'Rotation', 'Rotating shapes around points', '2025-03-31 21:14:18'),
(265, 13, 'Translation', 'Moving shapes using vectors', '2025-03-31 21:14:18'),
(266, 13, 'Enlargement', 'Positive and negative scale factors', '2025-03-31 21:14:18'),
(267, 13, 'Combined transformations', 'Multiple transformation sequences', '2025-03-31 21:14:18'),
(268, 14, 'Properties of 3D shapes', 'Understanding 3D shape characteristics', '2025-03-31 21:14:18'),
(269, 14, 'Volume and surface area', 'Calculations with 3D shapes', '2025-03-31 21:14:18'),
(270, 14, 'Plans and elevations', '2D representations of 3D objects', '2025-03-31 21:14:18'),
(271, 14, 'Prisms and cylinders', 'Properties and calculations', '2025-03-31 21:14:18'),
(272, 14, 'Cones, pyramids and spheres', 'Advanced 3D shape work', '2025-03-31 21:14:18'),
(273, 15, 'Congruence and similarity', 'Understanding shape relationships', '2025-03-31 21:14:18'),
(274, 15, 'Scale factors', 'Length, area, and volume relationships', '2025-03-31 21:14:18'),
(275, 15, 'Construction techniques', 'Geometric construction methods', '2025-03-31 21:14:18'),
(276, 15, 'Loci', 'Paths and regions', '2025-03-31 21:14:18'),
(277, 15, 'Circle theorems', 'Proving and applying circle properties', '2025-03-31 21:14:18'),
(278, 15, 'Vectors', 'Vector arithmetic and geometry', '2025-03-31 21:14:18'),
(279, 15, 'Geometric proof', 'Formal geometric reasoning', '2025-03-31 21:14:18'),
(280, 16, 'Pythagoras theorem', '2D and 3D applications', '2025-03-31 21:14:18'),
(281, 16, 'Trigonometric ratios', 'Sine, cosine, and tangent', '2025-03-31 21:14:18'),
(282, 16, 'Sine and cosine rules', 'Non-right-angled triangles', '2025-03-31 21:14:18'),
(283, 16, 'Area of a triangle', 'Using ½ab sin C', '2025-03-31 21:14:18'),
(284, 16, 'Exact trigonometric values', 'Standard angle values', '2025-03-31 21:14:18'),
(285, 16, '3D trigonometry', 'Trigonometry in three dimensions', '2025-03-31 21:14:18'),
(286, 16, 'Trigonometric graphs', 'Properties and transformations', '2025-03-31 21:14:18'),
(287, 17, 'Probability scale and notation', 'Understanding probability basics', '2025-03-31 21:14:18'),
(288, 17, 'Mutually exclusive events', 'Events that cannot occur together', '2025-03-31 21:14:18'),
(289, 17, 'Exhaustive events', 'Complete set of possible outcomes', '2025-03-31 21:14:18'),
(290, 17, 'Theoretical probability', 'Calculating expected probabilities', '2025-03-31 21:14:18'),
(291, 17, 'Experimental probability', 'Observed frequencies', '2025-03-31 21:14:18'),
(292, 17, 'Relative frequency', 'Long-run probability', '2025-03-31 21:14:18'),
(293, 18, 'Probability trees (independent events)', 'Tree diagrams for independent events', '2025-03-31 21:14:18'),
(294, 18, 'Probability trees (dependent events)', 'Tree diagrams for dependent events', '2025-03-31 21:14:18'),
(295, 18, 'Venn diagrams', 'Set notation and probability', '2025-03-31 21:14:18'),
(296, 18, 'Set notation', 'Mathematical notation for sets', '2025-03-31 21:14:18'),
(297, 18, 'Two-way tables', 'Organizing probability data', '2025-03-31 21:14:18'),
(298, 18, 'Frequency trees', 'Representing frequency relationships', '2025-03-31 21:14:18'),
(299, 19, 'Conditional probability', 'Probability given conditions', '2025-03-31 21:14:18'),
(300, 19, 'Combined events', 'Multiple event probability', '2025-03-31 21:14:18'),
(301, 19, 'Probability equations', 'Mathematical probability rules', '2025-03-31 21:14:18'),
(302, 19, 'Expected outcomes', 'Calculating expected values', '2025-03-31 21:14:18'),
(303, 20, 'Tables and charts', 'Different ways to present data', '2025-03-31 21:14:18'),
(304, 20, 'Pie charts', 'Circular representation of data', '2025-03-31 21:14:18'),
(305, 20, 'Stem and leaf diagrams', 'Ordered data presentation', '2025-03-31 21:14:18'),
(306, 20, 'Frequency polygons', 'Line graphs for frequency', '2025-03-31 21:14:18'),
(307, 20, 'Scatter graphs and correlation', 'Relationships between variables', '2025-03-31 21:14:18'),
(308, 20, 'Time series graphs', 'Data trends over time', '2025-03-31 21:14:18'),
(309, 21, 'Averages', 'Mean, median, and mode', '2025-03-31 21:14:18'),
(310, 21, 'Range and interquartile range', 'Measures of spread', '2025-03-31 21:14:18'),
(311, 21, 'Calculating from frequency tables', 'Working with grouped data', '2025-03-31 21:14:18'),
(312, 21, 'Box plots', 'Displaying data distribution', '2025-03-31 21:14:18'),
(313, 21, 'Cumulative frequency graphs', 'Running totals of frequency', '2025-03-31 21:14:18'),
(314, 21, 'Histograms', 'Equal and unequal class widths', '2025-03-31 21:14:18'),
(315, 22, 'Sampling methods', 'Different ways to collect data', '2025-03-31 21:14:18'),
(316, 22, 'Bias in sampling', 'Understanding data collection issues', '2025-03-31 21:14:18'),
(317, 22, 'Comparing distributions', 'Analyzing different data sets', '2025-03-31 21:14:18'),
(318, 22, 'Interpreting statistical measures', 'Understanding data analysis', '2025-03-31 21:14:18'),
(319, 22, 'Drawing conclusions from data', 'Making statistical inferences', '2025-03-31 21:14:18');

-- --------------------------------------------------------

--
-- Table structure for table `mood_entries`
--

CREATE TABLE `mood_entries` (
  `id` int(11) NOT NULL,
  `date` datetime NOT NULL DEFAULT current_timestamp(),
  `mood_level` tinyint(1) NOT NULL COMMENT 'Scale of 1-5 (1=very low, 5=very high)',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mood_entries`
--

INSERT INTO `mood_entries` (`id`, `date`, `mood_level`, `notes`, `created_at`, `updated_at`) VALUES
(2, '2025-04-08 20:10:00', 1, 'I don\'t now what to do man!', '2025-04-08 20:11:12', '2025-04-08 21:40:45'),
(3, '2025-04-08 21:13:49', 1, 'Oh crap', '2025-04-08 21:13:49', '2025-04-08 21:13:49'),
(4, '2025-05-23 00:00:00', 5, 'AMAZING!', '2025-04-08 21:48:39', '2025-04-08 21:48:39'),
(5, '2025-04-02 00:13:34', 5, 'Amazing morning! Completed workout and meditation.', '2025-04-08 23:13:34', '2025-04-08 23:13:34'),
(6, '2025-04-02 00:13:34', 2, 'Received disappointing news at work.', '2025-04-08 23:13:34', '2025-04-08 23:13:34'),
(7, '2025-04-02 00:13:34', 1, 'Massive project setback, feeling overwhelmed.', '2025-04-08 23:13:34', '2025-04-08 23:13:34'),
(8, '2025-04-02 00:13:34', 4, 'Evening chat with family lifted spirits significantly.', '2025-04-08 23:13:34', '2025-04-08 23:13:34'),
(9, '2025-04-03 00:13:34', 2, 'Woke up with terrible headache.', '2025-04-08 23:13:34', '2025-04-08 23:13:34'),
(10, '2025-04-03 00:13:34', 1, 'Had to cancel important meeting due to health.', '2025-04-08 23:13:34', '2025-04-08 23:13:34'),
(11, '2025-04-03 00:13:34', 3, 'Medicine started working, feeling better.', '2025-04-08 23:13:34', '2025-04-08 23:13:34'),
(12, '2025-04-03 00:13:34', 5, 'Evening meditation session was transformative!', '2025-04-08 23:13:34', '2025-04-08 23:13:34'),
(13, '2025-04-04 00:13:34', 4, 'Productive morning, cleared inbox.', '2025-04-08 23:13:34', '2025-04-08 23:13:34'),
(14, '2025-04-04 00:13:34', 1, 'Major argument with team member.', '2025-04-08 23:13:34', '2025-04-08 23:13:34'),
(15, '2025-04-04 00:13:34', 2, 'Still stressed about work conflict.', '2025-04-08 23:13:34', '2025-04-08 23:13:34'),
(16, '2025-04-04 00:13:34', 5, 'Resolution reached! Great teamwork.', '2025-04-08 23:13:34', '2025-04-08 23:13:34'),
(17, '2025-04-05 00:13:34', 3, 'Standard morning routine.', '2025-04-08 23:13:34', '2025-04-08 23:13:34'),
(18, '2025-04-05 00:13:34', 5, 'Surprise lunch with old friends!', '2025-04-08 23:13:34', '2025-04-08 23:13:34'),
(19, '2025-04-05 00:13:34', 5, 'Got promoted! Incredible news!', '2025-04-08 23:13:34', '2025-04-08 23:13:34'),
(20, '2025-04-05 00:13:34', 4, 'Celebration dinner with family.', '2025-04-08 23:13:34', '2025-04-08 23:13:34'),
(21, '2025-04-06 00:13:34', 1, 'Insomnia hit hard, barely slept.', '2025-04-08 23:13:34', '2025-04-08 23:13:34'),
(22, '2025-04-06 00:13:34', 2, 'Struggling to focus at work.', '2025-04-08 23:13:34', '2025-04-08 23:13:34'),
(23, '2025-04-06 00:13:34', 4, 'Afternoon nap helped recover.', '2025-04-08 23:13:34', '2025-04-08 23:13:34'),
(24, '2025-04-06 00:13:34', 5, 'Evening exercise session was amazing!', '2025-04-08 23:13:34', '2025-04-08 23:13:34'),
(25, '2025-04-07 00:13:34', 2, 'Technology issues derailing work.', '2025-04-08 23:13:34', '2025-04-08 23:13:34'),
(26, '2025-04-07 00:13:34', 1, 'Lost important file, panic mode.', '2025-04-08 23:13:34', '2025-04-08 23:13:34'),
(27, '2025-04-07 00:13:34', 3, 'IT helped recover everything.', '2025-04-08 23:13:34', '2025-04-08 23:13:34'),
(28, '2025-04-07 00:13:34', 5, 'Found even better solution!', '2025-04-08 23:13:34', '2025-04-08 23:13:34'),
(29, '2025-04-08 00:13:34', 4, 'Morning meditation and exercise.', '2025-04-08 23:13:34', '2025-04-08 23:13:34'),
(30, '2025-04-08 00:13:34', 2, 'Difficult client meeting.', '2025-04-08 23:13:34', '2025-04-08 23:58:09'),
(31, '2025-04-08 00:13:34', 1, 'Project deadline stress mounting.', '2025-04-08 23:13:34', '2025-04-08 23:13:34'),
(32, '2025-04-08 00:13:34', 5, 'Successfully submitted project!', '2025-04-08 23:13:34', '2025-04-08 23:59:30'),
(33, '2025-04-09 00:00:00', 3, 'Regular start to the day.', '2025-04-08 23:13:34', '2025-04-08 23:13:34'),
(34, '2025-04-09 00:00:00', 5, 'Breakthrough in morning meeting!', '2025-04-08 23:13:34', '2025-04-08 23:13:34'),
(35, '2025-04-09 00:00:00', 2, 'Post-lunch energy crash.', '2025-04-08 23:13:34', '2025-04-08 23:13:34'),
(36, '2025-04-09 00:00:00', 4, 'Productive afternoon session.', '2025-04-08 23:13:34', '2025-04-08 23:13:34'),
(37, '2025-04-09 07:30:00', 4, 'Morning workout was great! Feeling energized.', '2025-04-08 23:52:04', '2025-04-08 23:52:04'),
(38, '2025-04-09 14:15:00', 2, 'Struggling with work deadlines. Need to focus.', '2025-04-08 23:52:04', '2025-04-08 23:52:04'),
(39, '2025-04-09 19:45:00', 3, 'Evening meditation helped calm my mind.', '2025-04-08 23:52:04', '2025-04-08 23:52:04'),
(40, '2025-04-08 06:45:00', 1, 'Terrible sleep last night, feeling exhausted.', '2025-04-08 23:52:04', '2025-04-08 23:52:04'),
(41, '2025-04-08 15:30:00', 5, 'Productive work session! Got everything done.', '2025-04-08 23:52:04', '2025-04-08 23:52:04'),
(42, '2025-04-08 20:00:00', 4, 'Nice family dinner, feeling content.', '2025-04-08 23:52:04', '2025-04-08 23:52:04'),
(43, '2025-04-07 08:15:00', 3, 'Regular morning, nothing special.', '2025-04-08 23:52:04', '2025-04-08 23:52:04'),
(44, '2025-04-07 13:00:00', 4, 'Good progress at work today.', '2025-04-08 23:52:04', '2025-04-08 23:52:04'),
(45, '2025-04-07 22:30:00', 2, 'Feeling a bit anxious about tomorrow\'s meeting.', '2025-04-08 23:52:04', '2025-04-08 23:52:04'),
(46, '2025-04-06 06:30:00', 5, 'Early morning exercise and meditation!', '2025-04-08 23:52:04', '2025-04-08 23:52:04'),
(47, '2025-04-06 16:45:00', 2, 'Work stress is building up.', '2025-04-08 23:52:04', '2025-04-08 23:52:04'),
(48, '2025-04-06 18:30:00', 3, 'Evening walk helped clear my mind.', '2025-04-08 23:52:04', '2025-04-08 23:52:04'),
(49, '2025-04-05 07:00:00', 1, 'Insomnia last night, rough morning.', '2025-04-08 23:52:04', '2025-04-08 23:52:04'),
(50, '2025-04-05 12:30:00', 3, 'Getting better after lunch break.', '2025-04-08 23:52:04', '2025-04-08 23:52:04'),
(51, '2025-04-05 17:15:00', 4, 'Productive afternoon, feeling accomplished.', '2025-04-08 23:52:04', '2025-04-08 23:52:04'),
(52, '2025-04-04 09:00:00', 2, 'Monday morning blues hitting hard.', '2025-04-08 23:52:04', '2025-04-08 23:52:04'),
(53, '2025-04-04 14:45:00', 5, 'Great team meeting and productive work session!', '2025-04-08 23:52:04', '2025-04-08 23:52:04'),
(54, '2025-04-04 21:00:00', 3, 'Tired but satisfied with today\'s work.', '2025-04-08 23:52:04', '2025-04-08 23:52:04'),
(55, '2025-04-03 08:45:00', 4, 'Weekend morning, feeling relaxed.', '2025-04-08 23:52:04', '2025-04-08 23:52:04'),
(56, '2025-04-03 13:30:00', 5, 'Amazing social lunch with friends!', '2025-04-08 23:52:04', '2025-04-08 23:52:04'),
(57, '2025-04-03 20:15:00', 3, 'Quiet evening at home.', '2025-04-08 23:52:04', '2025-04-08 23:52:04');

-- --------------------------------------------------------

--
-- Table structure for table `mood_entry_factors`
--

CREATE TABLE `mood_entry_factors` (
  `id` int(11) NOT NULL,
  `mood_entry_id` int(11) NOT NULL,
  `mood_factor_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mood_entry_tags`
--

CREATE TABLE `mood_entry_tags` (
  `id` int(11) NOT NULL,
  `mood_entry_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Dumping data for table `mood_entry_tags`
--

INSERT INTO `mood_entry_tags` (`id`, `mood_entry_id`, `tag_id`, `created_at`) VALUES
(4, 3, 4, '2025-04-08 21:13:49'),
(6, 2, 4, '2025-04-08 21:40:45'),
(7, 4, 4, '2025-04-08 21:48:39'),
(8, 5, 7, '2025-04-08 23:19:53'),
(9, 5, 10, '2025-04-08 23:19:53'),
(10, 5, 5, '2025-04-08 23:19:53'),
(11, 6, 5, '2025-04-08 23:19:53'),
(12, 7, 5, '2025-04-08 23:19:53'),
(13, 8, 2, '2025-04-08 23:19:53'),
(14, 9, 1, '2025-04-08 23:19:53'),
(15, 10, 1, '2025-04-08 23:19:53'),
(16, 10, 5, '2025-04-08 23:19:53'),
(17, 11, 1, '2025-04-08 23:19:53'),
(18, 11, 5, '2025-04-08 23:19:53'),
(19, 12, 10, '2025-04-08 23:19:53'),
(20, 13, 11, '2025-04-08 23:19:53'),
(21, 15, 5, '2025-04-08 23:19:53'),
(22, 16, 5, '2025-04-08 23:19:53'),
(23, 18, 8, '2025-04-08 23:19:53'),
(24, 20, 2, '2025-04-08 23:19:53'),
(25, 21, 9, '2025-04-08 23:19:53'),
(26, 22, 5, '2025-04-08 23:19:53'),
(27, 24, 7, '2025-04-08 23:19:53'),
(28, 25, 5, '2025-04-08 23:19:53'),
(29, 29, 7, '2025-04-08 23:19:53'),
(30, 29, 10, '2025-04-08 23:19:53'),
(32, 31, 5, '2025-04-08 23:19:53'),
(34, 34, 5, '2025-04-08 23:19:53'),
(35, 36, 11, '2025-04-08 23:19:53'),
(39, 57, 7, '2025-04-08 23:52:04'),
(40, 56, 5, '2025-04-08 23:52:04'),
(41, 56, 11, '2025-04-08 23:52:04'),
(42, 55, 10, '2025-04-08 23:52:04'),
(43, 54, 9, '2025-04-08 23:52:04'),
(44, 53, 5, '2025-04-08 23:52:04'),
(45, 53, 11, '2025-04-08 23:52:04'),
(46, 52, 2, '2025-04-08 23:52:04'),
(47, 51, 5, '2025-04-08 23:52:04'),
(48, 50, 5, '2025-04-08 23:52:04'),
(49, 50, 11, '2025-04-08 23:52:04'),
(50, 49, 7, '2025-04-08 23:52:04'),
(51, 49, 10, '2025-04-08 23:52:04'),
(52, 48, 5, '2025-04-08 23:52:04'),
(53, 47, 7, '2025-04-08 23:52:04'),
(54, 46, 9, '2025-04-08 23:52:04'),
(55, 45, 5, '2025-04-08 23:52:04'),
(56, 44, 11, '2025-04-08 23:52:04'),
(57, 43, 5, '2025-04-08 23:52:04'),
(58, 42, 5, '2025-04-08 23:52:04'),
(59, 42, 11, '2025-04-08 23:52:04'),
(60, 41, 5, '2025-04-08 23:52:04'),
(61, 40, 1, '2025-04-08 23:52:04'),
(62, 39, 8, '2025-04-08 23:52:04'),
(63, 38, 1, '2025-04-08 23:52:04'),
(64, 30, 1, '2025-04-08 23:58:09'),
(65, 32, 7, '2025-04-08 23:59:30');

-- --------------------------------------------------------

--
-- Table structure for table `mood_factors`
--

CREATE TABLE `mood_factors` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `is_positive` tinyint(1) DEFAULT 1 COMMENT '1=positive factor, 0=negative factor',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mood_factors`
--

INSERT INTO `mood_factors` (`id`, `name`, `description`, `is_positive`, `created_at`) VALUES
(1, 'Well Rested', 'Got enough sleep', 1, '2025-04-08 17:41:36'),
(2, 'Focused', 'Able to concentrate well', 1, '2025-04-08 17:41:36'),
(3, 'Motivated', 'Feeling driven to achieve goals', 1, '2025-04-08 17:41:36'),
(4, 'Confident', 'Feeling self-assured about abilities', 1, '2025-04-08 17:41:36'),
(5, 'Stressed', 'Feeling under pressure or overwhelmed', 0, '2025-04-08 17:41:36'),
(6, 'Tired', 'Feeling fatigued or exhausted', 0, '2025-04-08 17:41:36'),
(7, 'Anxious', 'Feeling worried about exams or performance', 0, '2025-04-08 17:41:36'),
(8, 'Distracted', 'Having trouble focusing on studies', 0, '2025-04-08 17:41:36'),
(9, 'Excited', 'Feeling enthusiastic about learning', 1, '2025-04-08 17:41:36'),
(10, 'Frustrated', 'Feeling stuck or unable to progress', 0, '2025-04-08 17:41:36'),
(11, 'Satisfied', 'Feeling content with progress made', 1, '2025-04-08 17:41:36'),
(12, 'Overwhelmed', 'Feeling there is too much to learn', 0, '2025-04-08 17:41:36');

-- --------------------------------------------------------

--
-- Table structure for table `mood_tags`
--

CREATE TABLE `mood_tags` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `color` varchar(7) DEFAULT '#6c757d',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Dumping data for table `mood_tags`
--

INSERT INTO `mood_tags` (`id`, `name`, `category`, `color`, `created_at`) VALUES
(1, 'Health', 'Personal', '#6f42c1', '2025-04-08 18:26:18'),
(2, 'Family', 'Personal', '#17a2b8', '2025-04-08 18:26:18'),
(3, 'Relationship', 'Personal', '#dc3545', '2025-04-08 18:26:18'),
(4, 'School', 'Academic', '#007bff', '2025-04-08 18:26:18'),
(5, 'Work', 'Professional', '#fd7e14', '2025-04-08 18:26:18'),
(6, 'Spiritual Life', 'Spiritual', '#28a745', '2025-04-08 22:17:12'),
(7, 'Exercise', NULL, '#2196F3', '2025-04-08 23:13:34'),
(8, 'Social', NULL, '#9C27B0', '2025-04-08 23:13:34'),
(9, 'Sleep', NULL, '#795548', '2025-04-08 23:13:34'),
(10, 'Meditation', NULL, '#FF9800', '2025-04-08 23:13:34'),
(11, 'Productivity', NULL, '#009688', '2025-04-08 23:13:34');

-- --------------------------------------------------------

--
-- Table structure for table `notification_tracking`
--

CREATE TABLE `notification_tracking` (
  `id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `item_type` enum('task','habit') NOT NULL,
  `last_sent_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `overall_progress`
--

CREATE TABLE `overall_progress` (
  `id` int(11) NOT NULL,
  `total_sections` int(11) DEFAULT 0,
  `total_subsections` int(11) DEFAULT 0,
  `total_topics` int(11) DEFAULT 0,
  `completed_topics` int(11) DEFAULT 0,
  `progress_percentage` decimal(5,2) DEFAULT 0.00,
  `total_study_time` int(11) DEFAULT 0,
  `last_updated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `overall_progress`
--

INSERT INTO `overall_progress` (`id`, `total_sections`, `total_subsections`, `total_topics`, `completed_topics`, `progress_percentage`, `total_study_time`, `last_updated`) VALUES
(1, 6, 22, 48, 0, 0.00, 30, '2025-03-28 05:35:34');

-- --------------------------------------------------------

--
-- Table structure for table `practice_categories`
--

CREATE TABLE `practice_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `practice_categories`
--

INSERT INTO `practice_categories` (`id`, `name`, `description`) VALUES
(1, 'Vocabulary', 'Daily focus words and their meanings/usage.'),
(2, 'Spelling', 'Commonly misspelled words and memory aids.'),
(3, 'Figurative Language', 'Techniques like metaphors, similes, idioms etc.'),
(4, 'Phrasal Verbs', 'Common multi-word verbs and their meanings.');

-- --------------------------------------------------------

--
-- Table structure for table `practice_days`
--

CREATE TABLE `practice_days` (
  `id` int(11) NOT NULL,
  `practice_date` date NOT NULL,
  `day_number` int(11) DEFAULT NULL COMMENT 'Sequential day number (1, 2, 3...)',
  `week_number` int(11) DEFAULT NULL COMMENT 'Week number in the practice period',
  `notes` text DEFAULT NULL COMMENT 'Optional notes for the entire day'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `practice_days`
--

INSERT INTO `practice_days` (`id`, `practice_date`, `day_number`, `week_number`, `notes`) VALUES
(1, '2025-04-04', 1, 1, NULL),
(2, '2025-04-05', 2, 1, NULL),
(3, '2025-04-06', 3, 1, NULL),
(4, '2025-04-07', 4, 1, NULL),
(5, '2025-04-08', 5, 1, NULL),
(6, '2025-04-09', 6, 1, NULL),
(7, '2025-04-10', 7, 1, NULL),
(8, '2025-04-11', 8, 2, NULL),
(9, '2025-04-12', 9, 2, NULL),
(10, '2025-04-13', 10, 2, NULL),
(11, '2025-04-14', 11, 2, NULL),
(12, '2025-04-15', 12, 2, NULL),
(13, '2025-04-16', 13, 2, NULL),
(14, '2025-04-17', 14, 2, NULL),
(15, '2025-04-18', 15, 3, NULL),
(16, '2025-04-19', 16, 3, NULL),
(17, '2025-04-20', 17, 3, NULL),
(18, '2025-04-21', 18, 3, NULL),
(19, '2025-04-22', 19, 3, NULL),
(20, '2025-04-23', 20, 3, NULL),
(21, '2025-04-24', 21, 3, NULL),
(22, '2025-04-25', 22, 4, NULL),
(23, '2025-04-26', 23, 4, NULL),
(24, '2025-04-27', 24, 4, NULL),
(25, '2025-04-28', 25, 4, NULL),
(26, '2025-04-29', 26, 4, NULL),
(27, '2025-04-30', 27, 4, NULL),
(28, '2025-05-01', 28, 4, NULL),
(29, '2025-05-02', 29, 5, NULL),
(30, '2025-05-03', 30, 5, NULL),
(31, '2025-05-04', 31, 5, NULL),
(32, '2025-05-05', 32, 5, NULL),
(33, '2025-05-06', 33, 5, NULL),
(34, '2025-05-07', 34, 5, NULL),
(35, '2025-05-08', 35, 5, NULL),
(36, '2025-05-09', 36, 6, NULL),
(37, '2025-05-10', 37, 6, NULL),
(38, '2025-05-11', 38, 6, NULL),
(39, '2025-05-12', 39, 6, NULL),
(40, '2025-05-13', 40, 6, NULL),
(41, '2025-05-14', 41, 6, NULL),
(42, '2025-05-15', 42, 6, NULL),
(43, '2025-05-16', 43, 7, NULL),
(44, '2025-05-17', 44, 7, NULL),
(45, '2025-05-18', 45, 7, NULL),
(46, '2025-05-19', 46, 7, NULL),
(47, '2025-05-20', 47, 7, NULL),
(48, '2025-05-21', 48, 7, NULL),
(49, '2025-05-22', 49, 7, NULL),
(50, '2025-05-23', 50, 8, NULL),
(51, '2025-05-24', 51, 8, NULL),
(52, '2025-05-25', 52, 8, NULL),
(53, '2025-05-26', 53, 8, NULL),
(54, '2025-05-27', 54, 8, NULL),
(55, '2025-05-28', 55, 8, NULL),
(56, '2025-05-29', 56, 8, NULL),
(57, '2025-05-30', 57, 9, NULL),
(58, '2025-05-31', 58, 9, NULL),
(59, '2025-06-01', 59, 9, NULL),
(60, '2025-06-02', 60, 9, NULL),
(61, '2025-06-03', 61, 9, NULL),
(62, '2025-06-04', 62, 9, NULL),
(63, '2025-06-05', 63, 9, NULL),
(64, '2025-06-06', 64, 10, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `practice_items`
--

CREATE TABLE `practice_items` (
  `id` int(11) NOT NULL,
  `practice_day_id` int(11) NOT NULL COMMENT 'FK to practice_days table',
  `category_id` int(11) NOT NULL COMMENT 'FK to practice_categories table',
  `item_title` text NOT NULL COMMENT 'The word, spelling, term, or phrasal verb',
  `item_meaning` text NOT NULL COMMENT 'Definition or rule/explanation',
  `item_example` text NOT NULL COMMENT 'Example sentence',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `practice_items`
--

INSERT INTO `practice_items` (`id`, `practice_day_id`, `category_id`, `item_title`, `item_meaning`, `item_example`, `created_at`) VALUES
(1, 1, 1, 'Ambiguous', 'Having more than one possible meaning or interpretation', 'The ambiguous ending of the novel left readers debating what really happened to the main character.', '2025-04-06 07:10:48'),
(2, 1, 1, 'Cynical', 'Believing that people are only motivated by self-interest; distrustful of human sincerity', 'His cynical attitude toward politics meant he never believed campaign promises.', '2025-04-06 07:10:48'),
(3, 1, 1, 'Eloquent', 'Fluent, persuasive, and expressive in speech or writing', 'Her eloquent speech moved the entire audience to tears.', '2025-04-06 07:10:48'),
(4, 1, 1, 'Prejudice', 'A preconceived opinion not based on reason or experience', 'The novel explores themes of prejudice and discrimination in 1930s America.', '2025-04-06 07:10:48'),
(5, 1, 1, 'Vulnerable', 'Exposed to the possibility of being harmed; physically or emotionally susceptible', 'The character\'s vulnerable state after losing his job made him sympathetic to readers.', '2025-04-06 07:10:48'),
(6, 1, 2, 'Necessary', 'Rule: One C, two Ss', 'It is necessary to revise all key texts before the exam.', '2025-04-06 07:10:48'),
(7, 1, 2, 'Definitely', 'Rule: It has \"finite\" in the middle', 'I will definitely complete all practice questions before the exam.', '2025-04-06 07:10:48'),
(8, 1, 2, 'Separate', 'Rule: There\'s \"a rat\" in \"separate\"', 'The author uses paragraph breaks to separate different ideas.', '2025-04-06 07:10:48'),
(9, 1, 2, 'Occurrence', 'Rule: Double R, double C', 'The occurrence of natural imagery throughout the poem suggests the author\'s love of nature.', '2025-04-06 07:10:48'),
(10, 1, 2, 'Rhythm', 'Rule: Rhythm Helps Your Two Hips Move', 'The rhythm of the poem creates a sense of urgency.', '2025-04-06 07:10:48'),
(11, 1, 3, 'Metaphor', 'Direct comparison between two unlike things. Application: Identify and explain how the writer uses metaphors to create specific effects', '\"The classroom was a zoo\"', '2025-04-06 07:10:48'),
(12, 1, 3, 'Simile', 'Comparison using \"like\" or \"as\". Application: Analyze how similes help create vivid imagery in descriptive writing', '\"Her smile was as bright as the sun\"', '2025-04-06 07:10:48'),
(13, 1, 3, 'Personification', 'Giving human qualities to non-human things. Application: Explain how personification brings settings or objects to life in texts', '\"The wind whispered through the trees\"', '2025-04-06 07:10:48'),
(14, 1, 3, 'Hyperbole', 'Extreme exaggeration not meant to be taken literally. Application: Identify how hyperbole is used for emphasis or comic effect', '\"I\'ve told you a million times\"', '2025-04-06 07:10:48'),
(15, 1, 4, 'Break down', 'To stop functioning; to analyze in detail; to lose emotional control', 'The character broke down in tears after receiving the devastating news.', '2025-04-06 07:10:48'),
(16, 1, 4, 'Bring up', 'To raise a topic; to raise a child', 'The interviewer brought up the controversial issue that the politician had been avoiding.', '2025-04-06 07:10:48'),
(17, 1, 4, 'Carry out', 'To complete a task; to perform', 'Scientists carried out experiments to test their hypothesis.', '2025-04-06 07:10:48'),
(18, 2, 1, 'Contempt', 'The feeling that someone or something is worthless or beneath consideration', 'The villain\'s face showed contempt for anyone who challenged his authority.', '2025-04-06 07:10:48'),
(19, 2, 1, 'Dilemma', 'A situation requiring a choice between equally undesirable alternatives', 'The moral dilemma faced by the protagonist forms the central conflict of the novel.', '2025-04-06 07:10:48'),
(20, 2, 1, 'Empathy', 'The ability to understand and share the feelings of another', 'The nurse showed great empathy when dealing with patients and their families.', '2025-04-06 07:10:48'),
(21, 2, 1, 'Manipulative', 'Characterized by unscrupulous control of a situation or person', 'The manipulative antagonist convinced others to do his dirty work.', '2025-04-06 07:10:48'),
(22, 2, 1, 'Resilient', 'Able to recover quickly from difficulties; tough', 'Despite facing numerous setbacks, the resilient character never gave up on her dreams.', '2025-04-06 07:10:48'),
(23, 2, 2, 'Accommodation', 'Rule: Two Cs, two Ms', 'The accommodation described in the Victorian novel reflected the social class of the characters.', '2025-04-06 07:10:48'),
(24, 2, 2, 'Embarrass', 'Rule: Two Rs, two Ss', 'The character\'s actions would embarrass his family if they were discovered.', '2025-04-06 07:10:48'),
(25, 2, 2, 'Privilege', 'Rule: No D', 'The author explores how privilege affects different characters\' opportunities.', '2025-04-06 07:10:48'),
(26, 2, 2, 'Recommend', 'Rule: One C, two Ms', 'The teacher will recommend this book to students who enjoy dystopian fiction.', '2025-04-06 07:10:48'),
(27, 2, 2, 'Unnecessary', 'Rule: One N, two Ns, one C, two Ss', 'The author uses unnecessary detail to create a sense of realism.', '2025-04-06 07:10:48'),
(28, 2, 3, 'Oxymoron', 'Combining contradictory terms. Application: Identify dramatic effect/contradictions', '\"Deafening silence\"', '2025-04-06 07:10:48'),
(29, 2, 3, 'Pathetic fallacy', 'Attributing human emotions to nature. Application: Analyze how weather/environment reflect emotions', '\"The gloomy clouds reflected his dark mood\"', '2025-04-06 07:10:48'),
(30, 2, 3, 'Foreshadowing', 'Hints or clues about later events. Application: Identify subtle hints', '\"The dark clouds gathering... warned of trouble\"', '2025-04-06 07:10:48'),
(31, 2, 3, 'Juxtaposition', 'Placing contrasting elements side by side. Application: Explain how contrast creates meaning', '\"Mansion stood next to the slum\"', '2025-04-06 07:10:48'),
(32, 2, 4, 'Look into', 'To investigate or examine', 'The detective promised to look into the mysterious disappearance.', '2025-04-06 07:10:48'),
(33, 2, 4, 'Put off', 'To postpone; to discourage', 'Don\'t put off studying until the night before the exam.', '2025-04-06 07:10:48'),
(34, 2, 4, 'Turn down', 'To reject; to reduce volume', 'She turned down his invitation to the dance, breaking his heart.', '2025-04-06 07:10:48'),
(35, 3, 1, 'Apathy', 'Lack of interest, enthusiasm, or concern', 'The character\'s apathy toward school worried his parents.', '2025-04-06 07:10:48'),
(36, 3, 1, 'Benevolent', 'Kind, helpful, and generous', 'The benevolent king was loved by all his subjects.', '2025-04-06 07:10:48'),
(37, 3, 1, 'Desolate', 'Deserted, empty; feeling sad and abandoned', 'After the war, the city was left desolate.', '2025-04-06 07:10:48'),
(38, 3, 1, 'Inevitable', 'Certain to happen; unavoidable', 'Their confrontation was inevitable.', '2025-04-06 07:10:48'),
(39, 3, 1, 'Profound', 'Very great or intense; showing deep insight', 'The novel\'s profound message resonated with readers.', '2025-04-06 07:10:48'),
(40, 3, 2, 'Conscience', 'Rule: \"con\" + \"science\"', 'His conscience wouldn\'t let him ignore the suffering.', '2025-04-06 07:10:48'),
(41, 3, 2, 'Disappear', 'Rule: \"dis\" + \"appear\"', 'The mysterious character would disappear.', '2025-04-06 07:10:48'),
(42, 3, 2, 'Environment', 'Rule: \"environ\" + \"ment\"', 'The author describes the environment.', '2025-04-06 07:10:48'),
(43, 3, 2, 'Immediately', 'Rule: i-e-i-a-e-y', 'She immediately regretted her words.', '2025-04-06 07:10:48'),
(44, 3, 2, 'Possession', 'Rule: Double S mid', 'His most valued possession was a book.', '2025-04-06 07:10:48'),
(45, 3, 3, 'Irony', 'Opposite of expected occurs. Application: Identify types', 'Fire station burned down', '2025-04-06 07:10:48'),
(46, 3, 3, 'Symbolism', 'Objects/etc represent ideas. Application: Analyze theme dev', 'Dove symbolized peace', '2025-04-06 07:10:48'),
(47, 3, 3, 'Alliteration', 'Repetition initial consonant. Application: Explain effect', '\"She sells seashells\"', '2025-04-06 07:10:48'),
(48, 3, 3, 'Onomatopoeia', 'Words imitate sound. Application: Identify sensory effect', '\"Buzz of the bees\"', '2025-04-06 07:10:48'),
(49, 3, 4, 'Give up', 'Surrender; stop trying', 'She refused to give up.', '2025-04-06 07:10:48'),
(50, 3, 4, 'Point out', 'Draw attention; show', 'Teacher pointed out errors.', '2025-04-06 07:10:48'),
(51, 3, 4, 'Stand for', 'Represent; tolerate', 'Flag stands for freedom.', '2025-04-06 07:10:48'),
(52, 4, 1, 'Ambivalent', 'Having mixed feelings', 'She felt ambivalent about moving.', '2025-04-06 07:10:48'),
(53, 4, 1, 'Enigmatic', 'Mysterious', 'Mona Lisa\'s enigmatic smile.', '2025-04-06 07:10:48'),
(54, 4, 1, 'Harrowing', 'Extremely distressing', 'Harrowing wartime account.', '2025-04-06 07:10:48'),
(55, 4, 1, 'Meticulous', 'Attention to detail; precise', 'Meticulous investigation found truth.', '2025-04-06 07:10:48'),
(56, 4, 1, 'Vindictive', 'Desire to harm', 'Vindictive character plotted revenge.', '2025-04-06 07:10:48'),
(57, 4, 2, 'Achieve', 'Rule: i before e exception', 'Able to achieve goals.', '2025-04-06 07:10:48'),
(58, 4, 2, 'Beginning', 'Rule: Double N for -ing', 'Beginning introduces characters.', '2025-04-06 07:10:48'),
(59, 4, 2, 'Committed', 'Rule: Double T for -ed', 'Committed to finding truth.', '2025-04-06 07:10:48'),
(60, 4, 2, 'Referred', 'Rule: Double R for -ed', 'Author referred to events.', '2025-04-06 07:10:48'),
(61, 4, 2, 'Tomorrow', 'Rule: One M, two Rs', '\"Deal with this tomorrow\"', '2025-04-06 07:10:48'),
(62, 4, 3, 'Assonance', 'Repetition vowel sounds. App: ID rhythm/mood', '\"Rain in Spain stays...\"', '2025-04-06 07:10:48'),
(63, 4, 3, 'Euphemism', 'Mild substitute phrase. App: Analyze attitudes', '\"Passed away\"', '2025-04-06 07:10:48'),
(64, 4, 3, 'Motif', 'Recurring symbolic element. App: ID contribution', 'Ravens suggest doom', '2025-04-06 07:10:48'),
(65, 4, 3, 'Paradox', 'Contradictory statement w/truth. App: Explain depth', '\"More learn, less know\"', '2025-04-06 07:10:48'),
(66, 4, 4, 'Come across', 'Find by chance; impression', 'Came across diary.', '2025-04-06 07:10:48'),
(67, 4, 4, 'Fall apart', 'Break pieces; lose control', 'His life fall apart.', '2025-04-06 07:10:48'),
(68, 4, 4, 'Make up', 'Invent; reconcile', 'Child made up story.', '2025-04-06 07:10:48'),
(69, 5, 1, 'Candid', 'Truthful, straightforward', 'Candid autobiography revealed struggles.', '2025-04-06 07:10:48'),
(70, 5, 1, 'Despondent', 'Feeling hopeless, dejected', 'Became despondent after failing.', '2025-04-06 07:10:48'),
(71, 5, 1, 'Fervent', 'Great passion or intensity', 'Fervent speech inspired action.', '2025-04-06 07:10:48'),
(72, 5, 1, 'Impartial', 'Treating equally; unbiased', 'Judge remained impartial.', '2025-04-06 07:10:48'),
(73, 5, 1, 'Tenacious', 'Persistent; firm hold', 'Tenacious pursuit revealed truth.', '2025-04-06 07:10:48'),
(74, 5, 2, 'Argument', 'Rule: No E after U', 'Argument revealed values.', '2025-04-06 07:10:48'),
(75, 5, 2, 'Believe', 'Rule: i before e exc. c', 'Readers believe in triumph.', '2025-04-06 07:10:48'),
(76, 5, 2, 'Government', 'Rule: govern + ment', 'Novel criticizes government.', '2025-04-06 07:10:48'),
(77, 5, 2, 'Relevant', 'Rule: All Es exc 2nd last', 'Find relevant quotes.', '2025-04-06 07:10:48'),
(78, 5, 2, 'Surprise', 'Rule: Single consonants', 'Surprise ending questioned.', '2025-04-06 07:10:48'),
(79, 5, 3, 'Anaphora', 'Repeat start word/phrase. App: ID emphasis', '\"I have a dream...\"', '2025-04-06 07:10:48'),
(80, 5, 3, 'Dramatic irony', 'Audience knows more. App: Analyze tension', 'Killer hiding', '2025-04-06 07:10:48'),
(81, 5, 3, 'Extended metaphor', 'Metaphor developed length. App: Explain themes', 'Life as journey', '2025-04-06 07:10:48'),
(82, 5, 3, 'Understatement', 'Present less significant. App: ID humor', '\"Bit warm\"', '2025-04-06 07:10:48'),
(83, 5, 4, 'Back up', 'Support; copy data', 'Friends backed up story.', '2025-04-06 07:10:48'),
(84, 5, 4, 'Set up', 'Establish; arrange; frame', 'Set up meeting.', '2025-04-06 07:10:48'),
(85, 5, 4, 'Work out', 'Exercise; solve; end ok', 'Everything worked out.', '2025-04-06 07:10:48'),
(86, 6, 1, 'Arbitrary', 'Based on random choice', 'Arbitrary rules made no sense.', '2025-04-06 07:10:48'),
(87, 6, 1, 'Connotation', 'Invoked idea/feeling', '\"Home\" positive connotations.', '2025-04-06 07:10:48'),
(88, 6, 1, 'Ephemeral', 'Lasting short time', 'Ephemeral beauty.', '2025-04-06 07:10:48'),
(89, 6, 1, 'Implicit', 'Implied not expressed', 'Implicit message love conquers.', '2025-04-06 07:10:48'),
(90, 6, 1, 'Nuance', 'Subtle difference', 'Actor captured nuances.', '2025-04-06 07:10:48'),
(91, 6, 2, 'Apparent', 'Rule: appar + ent', 'Apparent narrator not truthful.', '2025-04-06 07:10:48'),
(92, 6, 2, 'Desperate', 'Rule: desper + ate', 'Desperate character.', '2025-04-06 07:10:48'),
(93, 6, 2, 'Existence', 'Rule: exist + ence', 'Questions human existence.', '2025-04-06 07:10:48'),
(94, 6, 2, 'Parallel', 'Rule: Double L mid', 'Author draws parallel.', '2025-04-06 07:10:48'),
(95, 6, 2, 'Weird', 'Rule: i before e exc c exc', 'Weird atmosphere unease.', '2025-04-06 07:10:48'),
(96, 6, 3, 'Allegory', 'Hidden meaning. App: ID meaning', 'Animal Farm', '2025-04-06 07:10:48'),
(97, 6, 3, 'Enjambment', 'Continue sentence beyond line. App: Analyze', 'Poetry example', '2025-04-06 07:10:48'),
(98, 6, 3, 'Caesura', 'Pause mid-line poetry. App: Explain', '\"To be, || or not...\"', '2025-04-06 07:10:48'),
(99, 6, 3, 'Bathos', 'Anticlimax. App: ID humor', 'King afraid spiders', '2025-04-06 07:10:48'),
(100, 6, 4, 'Break out', 'Escape; begin suddenly', 'War broke out.', '2025-04-06 07:10:48'),
(101, 6, 4, 'Go through', 'Experience; examine', 'Went through difficult time.', '2025-04-06 07:10:48'),
(102, 6, 4, 'Take after', 'Resemble family', 'Boy takes after father.', '2025-04-06 07:10:48'),
(103, 7, 1, 'Catalyst', 'Precipitates change', 'Her arrival was catalyst.', '2025-04-06 07:10:48'),
(104, 7, 1, 'Dichotomy', 'Contrast between opposites', 'Dichotomy good/evil.', '2025-04-06 07:10:48'),
(105, 7, 1, 'Facade', 'Deceptive outward appearance', 'Behind cheerful facade.', '2025-04-06 07:10:48'),
(106, 7, 1, 'Intrinsic', 'Belonging naturally', 'Intrinsic value connection.', '2025-04-06 07:10:48'),
(107, 7, 1, 'Poignant', 'Evoking sadness/regret', 'Poignant ending.', '2025-04-06 07:10:48'),
(108, 7, 2, 'Changeable', 'Rule: Keep E', 'Changeable weather.', '2025-04-06 07:10:48'),
(109, 7, 2, 'Conscious', 'Rule: con+sci+ous', 'Conscious of effect.', '2025-04-06 07:10:48'),
(110, 7, 2, 'Exaggerate', 'Rule: Double G', 'Narrator exaggerate effect.', '2025-04-06 07:10:48'),
(111, 7, 2, 'Noticeable', 'Rule: Keep E', 'Noticeable change behavior.', '2025-04-06 07:10:48'),
(112, 7, 2, 'Occasionally', 'Rule: 2C, 2L', 'Occasionally broke wall.', '2025-04-06 07:10:48'),
(113, 7, 3, 'Antithesis', 'Contrasting ideas, balanced. App: Analyze', '\"Err is human...\"', '2025-04-06 07:10:48'),
(114, 7, 3, 'Synecdoche', 'Part represents whole. App: ID', '\"All hands...\"', '2025-04-06 07:10:48'),
(115, 7, 3, 'Metonymy', 'Associated thing represents. App: Distinguish', '\"Crown\"', '2025-04-06 07:10:48'),
(116, 7, 3, 'Litotes', 'Understate via negative. App: ID', '\"Not bad\"', '2025-04-06 07:10:48'),
(117, 7, 4, 'Call off', 'Cancel', 'Called off wedding.', '2025-04-06 07:10:48'),
(118, 7, 4, 'Look up to', 'Admire/respect', 'Looked up to sister.', '2025-04-06 07:10:48'),
(119, 7, 4, 'Run into', 'Meet by chance', 'Ran into friend.', '2025-04-06 07:10:48'),
(120, 8, 1, 'Altruistic', 'Selfless concern', 'Altruistic nature helped others.', '2025-04-06 07:10:48'),
(121, 8, 1, 'Cacophony', 'Harsh sounds', 'Cacophony of battlefield.', '2025-04-06 07:10:48'),
(122, 8, 1, 'Ephemeral', 'Lasting short time', 'Fame proved ephemeral.', '2025-04-06 07:10:48'),
(123, 8, 1, 'Juxtapose', 'Place close for contrast', 'Juxtaposes wealth/poverty.', '2025-04-06 07:10:48'),
(124, 8, 1, 'Sycophant', 'Acts obsequiously', 'King surrounded by sycophants.', '2025-04-06 07:10:48'),
(125, 8, 2, 'Acceptable', 'Rule: accept+able', 'Behavior not acceptable.', '2025-04-06 07:10:48'),
(126, 8, 2, 'Colleague', 'Rule: collea+gue', 'Discussed with colleague.', '2025-04-06 07:10:48'),
(127, 8, 2, 'Liaison', 'Rule: Two Is', 'Acted as liaison.', '2025-04-06 07:10:48'),
(128, 8, 2, 'Questionnaire', 'Rule: Double N', 'Distributed questionnaire.', '2025-04-06 07:10:48'),
(129, 8, 2, 'Threshold', 'Rule: Single H mid', 'Crossing threshold.', '2025-04-06 07:10:48'),
(130, 8, 3, 'Chiasmus', 'Reversed balanced parts. App: ID', '\"Ask not what...\"', '2025-04-06 07:10:48'),
(131, 8, 3, 'Consonance', 'Repeat consonant sounds. App: Analyze', '\"Pitter patter\"', '2025-04-06 07:10:48'),
(132, 8, 3, 'Hubris', 'Excessive pride/downfall. App: ID', 'Hubris led downfall', '2025-04-06 07:10:48'),
(133, 8, 3, 'Pathos', 'Evokes pity/sadness. App: Analyze', 'Pathos of situation', '2025-04-06 07:10:48'),
(134, 8, 4, 'Bring about', 'Cause happen', 'Brought about changes.', '2025-04-06 07:10:48'),
(135, 8, 4, 'Hold back', 'Restrain; withhold', 'Held back tears.', '2025-04-06 07:10:48'),
(136, 8, 4, 'Pass away', 'Die (euphemism)', 'Grandfather passed away.', '2025-04-06 07:10:48'),
(137, 9, 1, 'Acerbic', 'Sharp and forthright', 'Acerbic review devastated.', '2025-04-06 07:10:48'),
(138, 9, 1, 'Brevity', 'Concise use words', 'Power in brevity.', '2025-04-06 07:10:48'),
(139, 9, 1, 'Duplicity', 'Deceitfulness', 'Villain\'s duplicity revealed.', '2025-04-06 07:10:48'),
(140, 9, 1, 'Egregious', 'Outstandingly bad', 'Egregious behavior alienated.', '2025-04-06 07:10:48'),
(141, 9, 1, 'Pernicious', 'Harmful effect, subtle', 'Pernicious effects prejudice.', '2025-04-06 07:10:48'),
(142, 9, 2, 'Bureaucracy', 'Rule: bureau+cracy', 'Trapped in bureaucracy.', '2025-04-06 07:10:48'),
(143, 9, 2, 'Consensus', 'Rule: con+sensus', 'Reached consensus.', '2025-04-06 07:10:48'),
(144, 9, 2, 'Harass', 'Rule: 1R, 2S', 'Protagonist harassed.', '2025-04-06 07:10:48'),
(145, 9, 2, 'Mischievous', 'Rule: 3 syllables', 'Mischievous child.', '2025-04-06 07:10:48'),
(146, 9, 2, 'Pronunciation', 'Rule: Drops O', 'Unusual pronunciation.', '2025-04-06 07:10:48'),
(147, 9, 3, 'Catharsis', 'Release emotion via art. App: Explain', 'Tragic ending catharsis', '2025-04-06 07:10:48'),
(148, 9, 3, 'Hamartia', 'Fatal flaw. App: ID', 'Hamartia was anger', '2025-04-06 07:10:48'),
(149, 9, 3, 'Soliloquy', 'Speak thoughts aloud. App: Analyze', 'Hamlet\'s soliloquy', '2025-04-06 07:10:48'),
(150, 9, 3, 'Verisimilitude', 'Appearance real. App: Explain realism', 'Novel\'s verisimilitude', '2025-04-06 07:10:48'),
(151, 9, 4, 'Carry on', 'Continue; behave', 'Carried on lives.', '2025-04-06 07:10:48'),
(152, 9, 4, 'Figure out', 'Understand/solve', 'Figure out motive.', '2025-04-06 07:10:48'),
(153, 9, 4, 'Take on', 'Accept challenge', 'Took on leader role.', '2025-04-06 07:10:48'),
(154, 10, 1, 'Ameliorate', 'Make better; improve', 'Policies ameliorate conditions.', '2025-04-06 07:10:48'),
(155, 10, 1, 'Capricious', 'Sudden changes mood', 'Capricious weather.', '2025-04-06 07:10:48'),
(156, 10, 1, 'Disparate', 'Essentially different', 'Brings disparate characters.', '2025-04-06 07:10:48'),
(157, 10, 1, 'Inextricable', 'Unable separate/escape', 'Fates inextricably linked.', '2025-04-06 07:10:48'),
(158, 10, 1, 'Ubiquitous', 'Present everywhere', 'Nature refs ubiquitous.', '2025-04-06 07:10:48'),
(159, 10, 2, 'Accommodate', 'Rule: 2C, 2M', 'Hotel accommodate guests.', '2025-04-06 07:10:48'),
(160, 10, 2, 'Embarrass', 'Rule: 2R, 2S', 'Revelation embarrass family.', '2025-04-06 07:10:48'),
(161, 10, 2, 'Millennium', 'Rule: 2N, 2L', 'Turn of millennium.', '2025-04-06 07:10:48'),
(162, 10, 2, 'Occurrence', 'Rule: 2C, 2R', 'Supernatural occurrence.', '2025-04-06 07:10:48'),
(163, 10, 2, 'Supersede', 'Rule: super+sede', 'New edition supersedes old.', '2025-04-06 07:10:48'),
(164, 10, 3, 'Anagnorisis', 'Critical discovery. App: ID moments', 'Realizes betrayal', '2025-04-06 07:10:48'),
(165, 10, 3, 'Deus ex machina', 'Unexpected solve problem. App: Eval resolutions', 'Sudden inheritance', '2025-04-06 07:10:48'),
(166, 10, 3, 'In medias res', 'Begin narrative mid action. App: Analyze', 'Begins fleeing', '2025-04-06 07:10:48'),
(167, 10, 3, 'Peripeteia', 'Sudden reversal fortune. App: ID turning points', 'Loses everything valued', '2025-04-06 07:10:48'),
(168, 10, 4, 'Come up with', 'Produce/suggest idea', 'Came up with solution.', '2025-04-06 07:10:48'),
(169, 10, 4, 'Put up with', 'Tolerate', 'Could not put up with behavior.', '2025-04-06 07:10:48'),
(170, 10, 4, 'Turn out', 'Prove to be; result', 'Plan turned out successful.', '2025-04-06 07:10:48'),
(172, 3, 2, 'Frustrated', 'Upset', 'I understand your frustration ', '2025-04-06 08:49:18'),
(173, 4, 3, 'Not my cup of tea ', 'Not my type', 'Coding is not my cup of tea!', '2025-04-07 01:58:10'),
(174, 5, 4, 'let\'s bygone is bygone ', 'once done is done', 'Let\'s bygone is bygone, but do not disappoint me anymore please!', '2025-04-08 00:05:10');

-- --------------------------------------------------------

--
-- Table structure for table `resources`
--

CREATE TABLE `resources` (
  `id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `type` varchar(20) NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `resources`
--

INSERT INTO `resources` (`id`, `subject_id`, `title`, `type`, `link`, `notes`) VALUES
(1, 1, 'English Language Revision Guide', 'book', NULL, 'CGP revision guide with practice questions'),
(2, 1, 'Macbeth Analysis Video', 'video', 'https://example.com/macbeth', 'Mr. Bruff analysis of key scenes'),
(3, 2, 'Algebra Cheat Sheet', 'document', 'https://example.com/algebra', 'Formula reference sheet'),
(4, 2, 'Corbett Maths', 'website', 'https://corbettmaths.com', 'Great for practice questions and video explanations');

-- --------------------------------------------------------

--
-- Table structure for table `schedule`
--

CREATE TABLE `schedule` (
  `id` int(11) NOT NULL,
  `subject_id` int(11) DEFAULT NULL,
  `topic_id` int(11) DEFAULT NULL,
  `planned_date` date NOT NULL,
  `duration` int(11) DEFAULT NULL,
  `priority` varchar(10) DEFAULT 'medium',
  `completed` tinyint(1) DEFAULT 0,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `section_progress`
--

CREATE TABLE `section_progress` (
  `id` int(11) NOT NULL,
  `section_id` int(11) NOT NULL,
  `total_subsections` int(11) DEFAULT 0,
  `total_topics` int(11) DEFAULT 0,
  `completed_topics` int(11) DEFAULT 0,
  `progress_percentage` decimal(5,2) DEFAULT 0.00,
  `total_time_spent_seconds` bigint(20) DEFAULT 0,
  `last_updated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Triggers `section_progress`
--
DELIMITER $$
CREATE TRIGGER `update_overall_progress` AFTER UPDATE ON `section_progress` FOR EACH ROW BEGIN
    UPDATE overall_progress
    SET 
        completed_topics = (
            SELECT SUM(completed_topics) 
            FROM section_progress
        ),
        progress_percentage = (
            SELECT SUM(completed_topics) * 100.0 / SUM(total_topics)
            FROM section_progress
        ),
        total_study_time = (
            SELECT COALESCE(SUM(total_time_spent), 0)
            FROM topic_progress
        ),
        last_updated = CURRENT_TIMESTAMP
    WHERE id = 1;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `duration` int(11) NOT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `subject_id`, `date`, `duration`, `notes`) VALUES
(0, 1, '2025-04-08', 10, 'Studied maths Algebra');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `theme` varchar(10) DEFAULT 'light',
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `theme`, `last_updated`) VALUES
(1, 'light', '2025-03-27 04:53:19');

-- --------------------------------------------------------

--
-- Table structure for table `study_time_tracking`
--

CREATE TABLE `study_time_tracking` (
  `id` int(11) NOT NULL,
  `topic_id` int(11) NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime DEFAULT NULL,
  `duration_seconds` int(11) DEFAULT 0,
  `status` enum('active','paused','completed') DEFAULT 'active',
  `last_pause_time` datetime DEFAULT NULL,
  `accumulated_seconds` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `study_time_tracking`
--

INSERT INTO `study_time_tracking` (`id`, `topic_id`, `start_time`, `end_time`, `duration_seconds`, `status`, `last_pause_time`, `accumulated_seconds`) VALUES
(0, 203, '2025-04-08 04:45:59', '2025-04-08 04:48:27', 276, 'completed', NULL, 276);

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `color` varchar(20) DEFAULT '#007bff'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`id`, `name`, `color`) VALUES
(1, 'English', '#28a745'),
(2, 'Math', '#dc3545');

-- --------------------------------------------------------

--
-- Table structure for table `subsection_progress`
--

CREATE TABLE `subsection_progress` (
  `id` int(11) NOT NULL,
  `subsection_id` int(11) NOT NULL,
  `total_topics` int(11) DEFAULT 0,
  `completed_topics` int(11) DEFAULT 0,
  `progress_percentage` decimal(5,2) DEFAULT 0.00,
  `total_time_spent_seconds` bigint(20) DEFAULT 0,
  `last_updated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `parent_task_id` int(11) DEFAULT NULL COMMENT 'For subtasks - references parent task',
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `task_type` enum('one-time','recurring') NOT NULL DEFAULT 'one-time',
  `priority` enum('low','medium','high') NOT NULL DEFAULT 'medium',
  `estimated_duration` int(11) DEFAULT 0 COMMENT 'Estimated duration in minutes',
  `due_date` date DEFAULT NULL,
  `due_time` time DEFAULT NULL,
  `status` enum('pending','in_progress','completed','not_done','snoozed') DEFAULT 'pending',
  `notification_sent` tinyint(1) DEFAULT 0,
  `completion_percentage` decimal(5,2) DEFAULT 0.00,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tasks`
--

INSERT INTO `tasks` (`id`, `category_id`, `parent_task_id`, `title`, `description`, `task_type`, `priority`, `estimated_duration`, `due_date`, `due_time`, `status`, `notification_sent`, `completion_percentage`, `is_active`, `created_at`, `updated_at`) VALUES
(107, 0, NULL, 'Miky', 'How', 'one-time', 'high', 0, '2025-04-09', '02:19:18', 'not_done', 0, 0.00, 1, '2025-04-08 08:30:13', '2025-04-09 02:09:31'),
(109, 9, NULL, 'Start Studying ', 'Maths ', 'one-time', 'medium', 120, '2025-04-09', '12:00:00', 'pending', 0, 0.00, 1, '2025-04-09 03:26:00', '2025-04-09 03:26:00'),
(110, 12, NULL, 'Call sara', 'Ask her to Bring the cash', 'one-time', 'medium', 5, '2025-04-09', '13:30:00', 'pending', 0, 0.00, 1, '2025-04-09 03:50:13', '2025-04-09 03:51:04'),
(111, 12, NULL, 'Call John ', '', 'one-time', 'medium', 10, '2025-04-09', '13:00:00', 'pending', 0, 0.00, 1, '2025-04-09 03:51:53', '2025-04-09 03:51:53'),
(112, 11, NULL, 'Go to Boots', 'Buy Self care Products', 'one-time', 'high', 20, '2025-04-09', '14:30:00', 'pending', 0, 0.00, 1, '2025-04-09 03:55:26', '2025-04-09 03:55:26'),
(113, 9, NULL, 'Study English', 'Start Studying', 'one-time', 'high', 120, '2025-04-09', '16:00:00', 'pending', 0, 0.00, 1, '2025-04-09 03:58:58', '2025-04-09 03:58:58'),
(114, 12, NULL, 'Geez Web App project', 'Do it just for 1hr', 'one-time', 'medium', 60, '2025-04-09', '18:00:00', 'pending', 0, 0.00, 1, '2025-04-09 04:02:10', '2025-04-09 04:02:10'),
(115, 1, NULL, 'Pray', 'Start Easy', 'one-time', 'high', 15, '2025-04-09', '11:40:00', 'pending', 0, 0.00, 1, '2025-04-09 04:03:15', '2025-04-09 04:03:15'),
(116, 12, NULL, 'Call Mihret', 'Ask her the status of the project', 'one-time', 'medium', 5, '2025-04-09', '18:50:00', 'pending', 0, 0.00, 1, '2025-04-09 04:04:23', '2025-04-09 04:04:23'),
(117, 9, NULL, 'AH Assigniment', 'Start Today', 'one-time', 'high', 240, '2025-04-09', '19:00:00', 'pending', 0, 0.00, 1, '2025-04-09 04:05:30', '2025-04-09 04:05:30'),
(118, 10, NULL, 'Take TT from restaurant ', 'Tonight ', 'one-time', 'medium', 10, '2025-04-09', '23:55:00', 'pending', 0, 0.00, 1, '2025-04-09 12:07:02', '2025-04-09 12:07:02');

-- --------------------------------------------------------

--
-- Table structure for table `task_categories`
--

CREATE TABLE `task_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(50) DEFAULT 'fas fa-tasks' COMMENT 'Font Awesome icon class name',
  `color` varchar(7) DEFAULT '#6c757d',
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `task_categories`
--

INSERT INTO `task_categories` (`id`, `name`, `description`, `icon`, `color`, `display_order`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Spiritual Life', 'Religious and spiritual activities', 'fas fa-pray', '#e6d305', 1, 1, '2025-04-02 19:13:35', '2025-04-02 19:13:35'),
(2, 'Self-Development', 'Personal growth and learning', 'fas fa-brain', '#9370DB', 2, 1, '2025-04-02 19:13:35', '2025-04-02 19:13:35'),
(3, 'Productivity', 'Task management and planning', 'fas fa-tasks', '#4682B4', 3, 1, '2025-04-02 19:13:35', '2025-04-02 19:13:35'),
(4, 'Study', 'Academic work and revision', 'fas fa-book-reader', '#1E90FF', 4, 1, '2025-04-02 19:13:35', '2025-04-02 19:13:35'),
(9, 'Education', 'General educational activities', 'fas fa-school', '#4169E1', 9, 1, '2025-04-02 19:13:35', '2025-04-02 19:13:35'),
(10, 'Uncategorized', NULL, 'fas fa-folder', '#6c757d', 0, 1, '2025-04-02 23:56:40', '2025-04-02 23:56:40'),
(11, 'Self Care', NULL, 'fas fa-heart', '#c70000', 0, 1, '2025-04-08 09:18:13', '2025-04-09 03:57:33'),
(12, 'Social Life', NULL, 'fas fa-users', '#277a7a', 0, 1, '2025-04-09 03:25:12', '2025-04-09 03:57:05');

-- --------------------------------------------------------

--
-- Table structure for table `task_checklist_items`
--

CREATE TABLE `task_checklist_items` (
  `id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `is_completed` tinyint(1) DEFAULT 0,
  `display_order` int(11) DEFAULT 0,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `task_instances`
--

CREATE TABLE `task_instances` (
  `id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `due_date` date NOT NULL,
  `due_time` time DEFAULT NULL,
  `status` enum('pending','in_progress','completed','not_done','snoozed') DEFAULT 'pending',
  `time_spent` int(11) DEFAULT 0 COMMENT 'Time spent in minutes',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `task_recurrence_rules`
--

CREATE TABLE `task_recurrence_rules` (
  `id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `frequency` enum('daily','weekly','monthly') NOT NULL,
  `times_per_period` int(11) NOT NULL DEFAULT 1,
  `specific_days` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Array of days ["monday", "wednesday", "friday"]' CHECK (json_valid(`specific_days`)),
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `last_generated_date` date DEFAULT NULL COMMENT 'Track last instance generation',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `task_tags`
--

CREATE TABLE `task_tags` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `color` varchar(7) DEFAULT '#6c757d',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `task_tag_relations`
--

CREATE TABLE `task_tag_relations` (
  `task_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `task_time_logs`
--

CREATE TABLE `task_time_logs` (
  `id` int(11) NOT NULL,
  `task_instance_id` int(11) NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime DEFAULT NULL,
  `duration` int(11) DEFAULT 0 COMMENT 'Duration in minutes',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `topic_images`
--

CREATE TABLE `topic_images` (
  `id` int(11) NOT NULL,
  `topic_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `title` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `uploaded_at` datetime DEFAULT current_timestamp(),
  `file_size` int(11) DEFAULT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `is_deleted` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `topic_notes`
--

CREATE TABLE `topic_notes` (
  `id` int(11) NOT NULL,
  `topic_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `edited_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `topic_notes`
--

INSERT INTO `topic_notes` (`id`, `topic_id`, `content`, `created_at`, `updated_at`, `edited_at`) VALUES
(11, 186, '<p>This is very nice! i like it </p>', '2025-03-31 22:07:53', '2025-03-31 22:07:53', '2025-03-31 22:07:53'),
(0, 187, '<p>What is A+B =  c</p>', '2025-04-08 02:39:27', '2025-04-08 02:39:27', '2025-04-08 02:39:27'),
(0, 190, '<p>How i like it!!!</p>', '2025-04-08 03:11:26', '2025-04-08 03:11:34', '2025-04-08 03:11:34'),
(0, 10, '<p>Noun</p>', '2025-04-08 03:16:21', '2025-04-08 03:16:21', '2025-04-08 03:16:21'),
(0, 203, '<p>Just Wow! </p>', '2025-04-08 04:46:18', '2025-04-08 04:46:18', '2025-04-08 04:46:18');

-- --------------------------------------------------------

--
-- Table structure for table `topic_progress`
--

CREATE TABLE `topic_progress` (
  `id` int(11) NOT NULL,
  `topic_id` int(11) NOT NULL,
  `status` enum('not_started','in_progress','completed') DEFAULT 'not_started',
  `total_time_spent` int(11) DEFAULT 0,
  `confidence_level` int(11) DEFAULT 0,
  `last_studied` datetime DEFAULT NULL,
  `completion_date` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `topic_progress`
--

INSERT INTO `topic_progress` (`id`, `topic_id`, `status`, `total_time_spent`, `confidence_level`, `last_studied`, `completion_date`, `notes`) VALUES
(0, 203, 'completed', 276, 3, '2025-04-08 04:48:27', NULL, '');

-- --------------------------------------------------------

--
-- Table structure for table `topic_questions`
--

CREATE TABLE `topic_questions` (
  `id` int(11) NOT NULL,
  `topic_id` int(11) NOT NULL,
  `question` text NOT NULL,
  `status` enum('pending','answered') DEFAULT 'pending',
  `answer` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `edited_at` datetime DEFAULT NULL,
  `is_correct` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `topic_questions`
--

INSERT INTO `topic_questions` (`id`, `topic_id`, `question`, `status`, `answer`, `created_at`, `edited_at`, `is_correct`) VALUES
(10, 186, '<p>1+1</p>', 'pending', '<p>2</p>', '2025-03-31 22:08:03', '2025-04-08 03:18:38', 0),
(0, 187, '<p>1+2</p>', 'pending', '<p>3</p>', '2025-04-08 02:39:45', '2025-04-08 03:18:16', 0),
(0, 190, '<p>x3<sup>3 </sup>+ 4x<sup>2 </sup>= ?<sup> </sup></p>', 'pending', '<p>11</p>', '2025-04-08 03:10:55', '2025-04-08 03:18:16', 0),
(0, 10, '<p>One noun</p>', 'pending', '<p>ABel</p>', '2025-04-08 03:16:30', '2025-04-08 03:18:16', 0);

-- --------------------------------------------------------

--
-- Table structure for table `topic_ratings`
--

CREATE TABLE `topic_ratings` (
  `id` int(11) NOT NULL,
  `topic_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `topic_resources`
--

CREATE TABLE `topic_resources` (
  `id` int(11) NOT NULL,
  `topic_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `resource_type` enum('youtube','image') NOT NULL,
  `youtube_url` varchar(255) DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `added_at` datetime DEFAULT current_timestamp(),
  `is_deleted` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `topic_resources`
--

INSERT INTO `topic_resources` (`id`, `topic_id`, `title`, `resource_type`, `youtube_url`, `image_path`, `file_size`, `file_type`, `added_at`, `is_deleted`) VALUES
(0, 186, 'YISERAL!', 'image', NULL, '/uploads/topic_resources/67f489985f860_1744079256.png', NULL, NULL, '2025-04-08 03:27:36', 0),
(0, 186, 'Question 5', 'image', NULL, '/uploads/topic_resources/67f48a2b92080_1744079403.jpg', NULL, NULL, '2025-04-08 03:30:03', 0),
(0, 187, 'HWo', 'youtube', 'https://www.youtube.com/watch?v=L46fJDx4AIk&list=PLUHcYgR3f2x1lTSTVLuQEA9Tln4NVf2TK', NULL, NULL, NULL, '2025-04-08 03:56:45', 0),
(0, 203, 'Collecting Like terms', 'image', NULL, '/uploads/topic_resources/67f4973310a86_1744082739.jpg', NULL, NULL, '2025-04-08 04:25:39', 0);

-- --------------------------------------------------------

--
-- Table structure for table `units`
--

CREATE TABLE `units` (
  `id` int(11) NOT NULL,
  `unit_code` varchar(50) DEFAULT NULL,
  `unit_name` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `credits` int(11) DEFAULT NULL,
  `is_graded` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `units`
--

INSERT INTO `units` (`id`, `unit_code`, `unit_name`, `description`, `credits`, `is_graded`, `created_at`) VALUES
(7, 'UNIT001', 'AI and Machine Learning', 'Introduction to AI, Machine Learning and Deep Learning', 20, 1, '2025-04-01 17:40:06');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `favorite_practice_items`
--
ALTER TABLE `favorite_practice_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_favorite_item` (`practice_item_id`);

--
-- Indexes for table `habits`
--
ALTER TABLE `habits`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `habit_completions`
--
ALTER TABLE `habit_completions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `habit_id_index` (`habit_id`),
  ADD KEY `date_index` (`completion_date`);

--
-- Indexes for table `habit_progress`
--
ALTER TABLE `habit_progress`
  ADD PRIMARY KEY (`id`),
  ADD KEY `habit_id_index` (`habit_id`),
  ADD KEY `date_index` (`date`);

--
-- Indexes for table `mood_entries`
--
ALTER TABLE `mood_entries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `mood_date_idx` (`date`);

--
-- Indexes for table `mood_entry_factors`
--
ALTER TABLE `mood_entry_factors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_entry_factor` (`mood_entry_id`,`mood_factor_id`),
  ADD KEY `mood_entry_idx` (`mood_entry_id`),
  ADD KEY `mood_factor_idx` (`mood_factor_id`);

--
-- Indexes for table `mood_entry_tags`
--
ALTER TABLE `mood_entry_tags`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_entry_tag` (`mood_entry_id`,`tag_id`),
  ADD KEY `fk_tag_entry` (`tag_id`);

--
-- Indexes for table `mood_factors`
--
ALTER TABLE `mood_factors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_factor_name` (`name`);

--
-- Indexes for table `mood_tags`
--
ALTER TABLE `mood_tags`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_tag_name` (`name`);

--
-- Indexes for table `notification_tracking`
--
ALTER TABLE `notification_tracking`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_notification` (`item_id`,`item_type`);

--
-- Indexes for table `practice_categories`
--
ALTER TABLE `practice_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `practice_days`
--
ALTER TABLE `practice_days`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `practice_date` (`practice_date`),
  ADD KEY `day_number` (`day_number`),
  ADD KEY `week_number` (`week_number`);

--
-- Indexes for table `practice_items`
--
ALTER TABLE `practice_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `practice_day_id` (`practice_day_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `task_categories`
--
ALTER TABLE `task_categories`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `favorite_practice_items`
--
ALTER TABLE `favorite_practice_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `habits`
--
ALTER TABLE `habits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `habit_completions`
--
ALTER TABLE `habit_completions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `habit_progress`
--
ALTER TABLE `habit_progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `mood_entries`
--
ALTER TABLE `mood_entries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT for table `mood_entry_factors`
--
ALTER TABLE `mood_entry_factors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `mood_entry_tags`
--
ALTER TABLE `mood_entry_tags`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- AUTO_INCREMENT for table `mood_factors`
--
ALTER TABLE `mood_factors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `mood_tags`
--
ALTER TABLE `mood_tags`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `notification_tracking`
--
ALTER TABLE `notification_tracking`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `practice_categories`
--
ALTER TABLE `practice_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `practice_days`
--
ALTER TABLE `practice_days`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=65;

--
-- AUTO_INCREMENT for table `practice_items`
--
ALTER TABLE `practice_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=175;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=119;

--
-- AUTO_INCREMENT for table `task_categories`
--
ALTER TABLE `task_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `favorite_practice_items`
--
ALTER TABLE `favorite_practice_items`
  ADD CONSTRAINT `fk_fav_item_id` FOREIGN KEY (`practice_item_id`) REFERENCES `practice_items` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `mood_entry_factors`
--
ALTER TABLE `mood_entry_factors`
  ADD CONSTRAINT `fk_mood_entry` FOREIGN KEY (`mood_entry_id`) REFERENCES `mood_entries` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_mood_factor` FOREIGN KEY (`mood_factor_id`) REFERENCES `mood_factors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `mood_entry_tags`
--
ALTER TABLE `mood_entry_tags`
  ADD CONSTRAINT `fk_mood_entry_tag` FOREIGN KEY (`mood_entry_id`) REFERENCES `mood_entries` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_tag_entry` FOREIGN KEY (`tag_id`) REFERENCES `mood_tags` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `practice_items`
--
ALTER TABLE `practice_items`
  ADD CONSTRAINT `fk_pi_category_id` FOREIGN KEY (`category_id`) REFERENCES `practice_categories` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pi_practice_day_id` FOREIGN KEY (`practice_day_id`) REFERENCES `practice_days` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
