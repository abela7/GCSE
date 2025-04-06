-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Apr 04, 2025 at 01:29 AM
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
(1, 36, 'in_progress', 0, 3, '2025-04-01 00:09:23', NULL, ''),
(2, 10, 'not_started', 0, 0, NULL, NULL, '');

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
(1, 1, 3, 'Morning Prayer', 'Start the day with prayer', 'fas fa-pray', '06:00:00', 0, 0, 0, 0, 0, 0, 0.00, 1, '2025-04-01 21:34:22', '2025-04-02 15:01:15'),
(2, 1, 3, 'Night Prayer', 'End the day with prayer', 'fas fa-pray', '22:00:00', 0, 0, 0, 0, 0, 0, 0.00, 1, '2025-04-01 21:34:22', '2025-04-02 04:08:12'),
(3, 1, 3, 'Read Bible', 'Daily scripture reading', 'fas fa-book-bible', '07:00:00', 0, 0, 0, 0, 0, 0, 0.00, 1, '2025-04-01 21:34:22', '2025-04-02 03:45:55'),
(4, 2, 2, 'Study GCSE Math', 'Practice math problems', 'fas fa-book-reader', '10:00:00', 0, 0, 0, 0, 0, 0, 0.00, 1, '2025-04-01 21:34:22', '2025-04-02 15:01:15'),
(5, 2, 2, 'Study GCSE English', 'Practice English', 'fas fa-book-reader', '14:00:00', 0, 0, 0, 0, 0, 0, 0.00, 1, '2025-04-01 21:34:22', '2025-04-02 15:01:15'),
(6, 2, 2, 'Review Notes', 'Review daily study notes', 'fas fa-clipboard-check', '20:00:00', 0, 0, 0, 0, 0, 0, 0.00, 1, '2025-04-01 21:34:22', '2025-04-01 23:28:27'),
(7, 3, 1, 'Morning Routine', 'Personal hygiene and grooming', 'fas fa-tasks', '06:30:00', 0, 0, 0, 0, 0, 0, 0.00, 1, '2025-04-01 21:34:22', '2025-04-01 22:04:20'),
(8, 3, 1, 'Take Supplements', 'Daily vitamins and supplements', 'fas fa-pills', '08:00:00', 0, 0, 0, 0, 0, 0, 0.00, 1, '2025-04-01 21:34:22', '2025-04-02 15:01:15'),
(9, 3, 1, 'Evening Routine', 'Prepare for bed', 'fas fa-tasks', '21:30:00', 0, 0, 0, 0, 0, 0, 0.00, 1, '2025-04-01 21:34:22', '2025-04-03 23:21:04'),
(11, 9, 2, 'Call home', '', 'fas fa-check-circle', '10:00:00', 0, 0, 0, 0, 0, 0, 0.00, 1, '2025-04-01 23:24:52', '2025-04-01 23:24:52'),
(13, 12, 2, 'Reading Books', '', 'fas fa-check-circle', '15:00:00', 0, 0, 0, 0, 0, 0, 0.00, 1, '2025-04-02 13:49:34', '2025-04-02 15:01:15');

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
(9, 'Social', 'Social connections and relationships', '#FF7F50', 'fas fa-users', 9, '2025-04-01 21:34:00'),
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
(1, 1, '2025-03-15', 60, 'Worked on Shakespeare quotes'),
(2, 1, '2025-03-18', 45, 'Practiced creative writing'),
(3, 2, '2025-03-16', 90, 'Solved quadratic equations'),
(4, 2, '2025-03-20', 75, 'Reviewed trigonometry');

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
(20, 186, '2025-03-31 22:24:33', '2025-03-31 22:26:31', 80, 'completed', NULL, 80);

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
  `completion_percentage` decimal(5,2) DEFAULT 0.00,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tasks`
--

INSERT INTO `tasks` (`id`, `category_id`, `parent_task_id`, `title`, `description`, `task_type`, `priority`, `estimated_duration`, `due_date`, `due_time`, `status`, `completion_percentage`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, 'Morning Prayer & Journaling', 'Daily morning spiritual routine', 'recurring', 'high', 20, '2025-04-02', '09:30:00', 'pending', 0.00, 0, '2025-04-02 19:13:35', '2025-04-04 00:22:19'),
(2, 2, NULL, 'Read 5 Pages of Book', 'Daily reading habit', 'recurring', 'medium', 15, '2025-04-02', '15:41:46', 'pending', 0.00, 0, '2025-04-02 19:13:35', '2025-04-04 00:19:11'),
(3, 3, NULL, 'Daily Task Review & Planning', 'End of day review and planning', 'recurring', 'high', 15, '2025-04-02', '00:30:00', 'pending', 0.00, 0, '2025-04-02 19:13:35', '2025-04-04 00:22:47'),
(7, 4, NULL, 'Weekly Assignment Planning', 'Plan upcoming assignments and deadlines', 'recurring', 'high', 60, '2025-04-02', '15:41:30', 'pending', 0.00, 1, '2025-04-02 19:13:35', '2025-04-03 12:41:30'),
(10, 6, NULL, 'Submit Unit 9 Assignment', 'Final submission for Unit 9', 'one-time', 'high', 30, '2025-04-04', '19:00:00', 'pending', 0.00, 0, '2025-04-02 19:13:35', '2025-04-04 00:19:01'),
(11, 10, NULL, 'Install Task Manager in Web App', 'Implementation of task management system', 'one-time', 'medium', 60, '2025-04-03', '21:00:00', 'completed', 0.00, 1, '2025-04-02 19:13:35', '2025-04-03 13:00:21'),
(12, 9, NULL, 'Attend Learndirect Tutor Call', 'Online tutoring session', 'one-time', 'high', 45, '2025-04-05', '11:00:00', 'not_done', 0.00, 0, '2025-04-02 19:13:35', '2025-04-03 23:22:59'),
(28, 3, NULL, 'Abel Demssie ', '', 'recurring', 'medium', 12, '2025-04-02', '16:38:07', 'pending', 0.00, 0, '2025-04-02 23:39:35', '2025-04-04 00:15:52'),
(31, 1, NULL, 'mukera', '', '', 'medium', 10, '2025-04-03', '15:39:06', 'pending', 0.00, 0, '2025-04-03 12:32:44', '2025-04-04 00:22:43'),
(32, 2, NULL, 'Tekle', '', '', 'high', 10, '2025-04-05', '15:37:00', 'pending', 0.00, 0, '2025-04-03 12:35:31', '2025-04-03 23:22:56'),
(34, 9, NULL, 'Reading', '', 'one-time', 'low', 20, '2025-04-03', '18:00:00', 'pending', 0.00, 0, '2025-04-03 14:46:20', '2025-04-03 14:46:20'),
(35, 9, NULL, 'Reading', '', 'one-time', 'low', 20, '2025-04-03', '18:00:00', 'pending', 0.00, 0, '2025-04-03 14:46:22', '2025-04-03 14:46:22'),
(36, 9, NULL, 'Reading', '', 'one-time', 'low', 20, '2025-04-03', '18:00:00', 'pending', 0.00, 0, '2025-04-03 14:46:23', '2025-04-03 14:46:23'),
(37, 9, NULL, 'Reading', '', 'one-time', 'low', 20, '2025-04-03', '18:00:00', 'pending', 0.00, 0, '2025-04-03 14:46:24', '2025-04-03 14:46:24'),
(38, 9, NULL, 'Reading', '', 'one-time', 'low', 20, '2025-04-03', '18:00:00', 'pending', 0.00, 0, '2025-04-03 14:46:24', '2025-04-03 14:46:24'),
(39, 9, NULL, 'Reading', '', 'one-time', 'low', 20, '2025-04-03', '18:00:00', 'pending', 0.00, 0, '2025-04-03 14:46:24', '2025-04-03 14:46:24'),
(40, 9, NULL, 'Reading', '', 'one-time', 'low', 20, '2025-04-03', '18:00:00', 'pending', 0.00, 0, '2025-04-03 14:46:49', '2025-04-03 14:46:49'),
(46, 7, NULL, 'call mom', '', 'one-time', 'high', 20, '2025-04-03', '18:00:00', '', 0.00, 1, '2025-04-03 14:58:04', '2025-04-03 14:58:13'),
(47, 6, NULL, 'GOOO', '', 'one-time', 'high', 20, '2025-04-03', '16:15:00', '', 0.00, 1, '2025-04-03 15:10:42', '2025-04-03 15:11:35'),
(48, 2, NULL, 'I am Abel ', '', 'one-time', 'high', 10, '2025-04-03', '16:40:00', '', 0.00, 1, '2025-04-03 15:38:54', '2025-04-03 15:40:03'),
(49, 5, NULL, 'Eat', '', 'one-time', 'high', 10, '2025-04-03', '17:00:00', 'pending', 0.00, 1, '2025-04-03 15:53:44', '2025-04-03 15:53:44');

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
(5, 'Health', 'Physical health and fitness', 'fas fa-heartbeat', '#32CD32', 5, 1, '2025-04-02 19:13:35', '2025-04-02 19:13:35'),
(6, 'Access to HE', 'Access to Higher Education coursework', 'fas fa-graduation-cap', '#8A2BE2', 6, 1, '2025-04-02 19:13:35', '2025-04-02 19:13:35'),
(7, 'Home & Chores', 'Household maintenance', 'fas fa-home', '#CD853F', 7, 1, '2025-04-02 19:13:35', '2025-04-02 19:13:35'),
(9, 'Education', 'General educational activities', 'fas fa-school', '#4169E1', 9, 1, '2025-04-02 19:13:35', '2025-04-02 19:13:35'),
(10, 'Uncategorized', NULL, 'fas fa-folder', '#6c757d', 0, 1, '2025-04-02 23:56:40', '2025-04-02 23:56:40');

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

--
-- Dumping data for table `task_instances`
--

INSERT INTO `task_instances` (`id`, `task_id`, `due_date`, `due_time`, `status`, `time_spent`, `notes`, `created_at`, `updated_at`) VALUES
(1, 28, '2025-04-02', '11:11:00', 'pending', 0, NULL, '2025-04-02 23:39:35', '2025-04-02 23:39:35'),
(2, 28, '2025-04-04', '11:11:00', 'pending', 0, NULL, '2025-04-02 23:39:35', '2025-04-02 23:39:35'),
(3, 28, '2025-04-09', '11:11:00', 'pending', 0, NULL, '2025-04-02 23:39:35', '2025-04-02 23:39:35'),
(4, 28, '2025-04-11', '11:11:00', 'pending', 0, NULL, '2025-04-02 23:39:35', '2025-04-02 23:39:35'),
(5, 28, '2025-04-16', '11:11:00', 'pending', 0, NULL, '2025-04-02 23:39:35', '2025-04-02 23:39:35'),
(6, 28, '2025-04-18', '11:11:00', 'pending', 0, NULL, '2025-04-02 23:39:35', '2025-04-02 23:39:35'),
(7, 28, '2025-04-23', '11:11:00', 'pending', 0, NULL, '2025-04-02 23:39:35', '2025-04-02 23:39:35'),
(8, 28, '2025-04-25', '11:11:00', 'pending', 0, NULL, '2025-04-02 23:39:35', '2025-04-02 23:39:35'),
(9, 1, '2025-04-03', '09:30:00', 'completed', 0, NULL, '2025-04-02 23:56:14', '2025-04-03 13:18:23'),
(10, 1, '2025-04-04', '09:30:00', 'not_done', 0, NULL, '2025-04-02 23:56:14', '2025-04-04 00:22:19'),
(11, 1, '2025-04-05', '09:30:00', 'not_done', 0, NULL, '2025-04-02 23:56:14', '2025-04-04 00:22:19'),
(12, 1, '2025-04-06', '09:30:00', 'not_done', 0, NULL, '2025-04-02 23:56:14', '2025-04-04 00:22:19'),
(13, 1, '2025-04-07', '09:30:00', 'not_done', 0, NULL, '2025-04-02 23:56:14', '2025-04-04 00:22:19'),
(14, 1, '2025-04-08', '09:30:00', 'not_done', 0, NULL, '2025-04-02 23:56:14', '2025-04-04 00:22:19'),
(15, 1, '2025-04-09', '09:30:00', 'not_done', 0, NULL, '2025-04-02 23:56:14', '2025-04-04 00:22:19'),
(16, 1, '2025-04-10', '09:30:00', 'not_done', 0, NULL, '2025-04-02 23:56:14', '2025-04-04 00:22:19'),
(17, 1, '2025-04-11', '09:30:00', 'not_done', 0, NULL, '2025-04-02 23:56:14', '2025-04-04 00:22:19'),
(18, 1, '2025-04-12', '09:30:00', 'not_done', 0, NULL, '2025-04-02 23:56:14', '2025-04-04 00:22:19'),
(19, 1, '2025-04-13', '09:30:00', 'not_done', 0, NULL, '2025-04-02 23:56:14', '2025-04-04 00:22:19'),
(20, 1, '2025-04-14', '09:30:00', 'not_done', 0, NULL, '2025-04-02 23:56:14', '2025-04-04 00:22:19'),
(21, 1, '2025-04-15', '09:30:00', 'not_done', 0, NULL, '2025-04-02 23:56:14', '2025-04-04 00:22:19'),
(22, 1, '2025-04-16', '09:30:00', 'not_done', 0, NULL, '2025-04-02 23:56:14', '2025-04-04 00:22:19'),
(23, 1, '2025-04-17', '09:30:00', 'not_done', 0, NULL, '2025-04-02 23:56:14', '2025-04-04 00:22:19'),
(24, 1, '2025-04-18', '09:30:00', 'not_done', 0, NULL, '2025-04-02 23:56:14', '2025-04-04 00:22:19'),
(25, 1, '2025-04-19', '09:30:00', 'not_done', 0, NULL, '2025-04-02 23:56:14', '2025-04-04 00:22:19'),
(26, 1, '2025-04-20', '09:30:00', 'not_done', 0, NULL, '2025-04-02 23:56:14', '2025-04-04 00:22:19'),
(27, 1, '2025-04-21', '09:30:00', 'not_done', 0, NULL, '2025-04-02 23:56:14', '2025-04-04 00:22:19'),
(28, 1, '2025-04-22', '09:30:00', 'not_done', 0, NULL, '2025-04-02 23:56:14', '2025-04-04 00:22:19'),
(29, 1, '2025-04-23', '09:30:00', 'not_done', 0, NULL, '2025-04-02 23:56:14', '2025-04-04 00:22:19'),
(30, 1, '2025-04-24', '09:30:00', 'not_done', 0, NULL, '2025-04-02 23:56:14', '2025-04-04 00:22:19'),
(31, 1, '2025-04-25', '09:30:00', 'not_done', 0, NULL, '2025-04-02 23:56:14', '2025-04-04 00:22:19'),
(32, 1, '2025-04-26', '09:30:00', 'not_done', 0, NULL, '2025-04-02 23:56:14', '2025-04-04 00:22:19'),
(33, 1, '2025-04-27', '09:30:00', 'not_done', 0, NULL, '2025-04-02 23:56:14', '2025-04-04 00:22:19'),
(34, 1, '2025-04-28', '09:30:00', 'not_done', 0, NULL, '2025-04-02 23:56:14', '2025-04-04 00:22:19'),
(35, 1, '2025-04-29', '09:30:00', 'not_done', 0, NULL, '2025-04-02 23:56:14', '2025-04-04 00:22:19'),
(36, 1, '2025-04-30', '09:30:00', 'not_done', 0, NULL, '2025-04-02 23:56:14', '2025-04-04 00:22:19'),
(37, 2, '2025-04-03', '15:41:46', '', 0, NULL, '2025-04-02 23:56:14', '2025-04-03 15:37:49'),
(38, 2, '2025-04-04', '17:00:00', 'not_done', 0, NULL, '2025-04-02 23:56:14', '2025-04-04 00:19:11'),
(39, 2, '2025-04-05', '17:00:00', 'not_done', 0, NULL, '2025-04-02 23:56:14', '2025-04-04 00:19:11'),
(40, 2, '2025-04-06', '17:00:00', 'not_done', 0, NULL, '2025-04-02 23:56:14', '2025-04-04 00:19:11'),
(41, 2, '2025-04-07', '17:00:00', 'not_done', 0, NULL, '2025-04-02 23:56:14', '2025-04-04 00:19:11'),
(42, 2, '2025-04-08', '17:00:00', 'not_done', 0, NULL, '2025-04-02 23:56:14', '2025-04-04 00:19:11'),
(43, 2, '2025-04-09', '17:00:00', 'not_done', 0, NULL, '2025-04-02 23:56:14', '2025-04-04 00:19:11'),
(44, 2, '2025-04-10', '17:00:00', 'not_done', 0, NULL, '2025-04-02 23:56:14', '2025-04-04 00:19:11'),
(45, 2, '2025-04-11', '17:00:00', 'not_done', 0, NULL, '2025-04-02 23:56:14', '2025-04-04 00:19:11'),
(46, 2, '2025-04-12', '17:00:00', 'not_done', 0, NULL, '2025-04-02 23:56:14', '2025-04-04 00:19:11'),
(47, 2, '2025-04-13', '17:00:00', 'not_done', 0, NULL, '2025-04-02 23:56:14', '2025-04-04 00:19:11'),
(48, 2, '2025-04-14', '17:00:00', 'not_done', 0, NULL, '2025-04-02 23:56:14', '2025-04-04 00:19:11'),
(49, 2, '2025-04-15', '17:00:00', 'not_done', 0, NULL, '2025-04-02 23:56:14', '2025-04-04 00:19:11'),
(50, 2, '2025-04-16', '17:00:00', 'not_done', 0, NULL, '2025-04-02 23:56:14', '2025-04-04 00:19:11'),
(51, 2, '2025-04-17', '17:00:00', 'not_done', 0, NULL, '2025-04-02 23:56:14', '2025-04-04 00:19:11'),
(52, 2, '2025-04-18', '17:00:00', 'not_done', 0, NULL, '2025-04-02 23:56:14', '2025-04-04 00:19:11'),
(53, 2, '2025-04-19', '17:00:00', 'not_done', 0, NULL, '2025-04-02 23:56:14', '2025-04-04 00:19:11'),
(54, 2, '2025-04-20', '17:00:00', 'not_done', 0, NULL, '2025-04-02 23:56:14', '2025-04-04 00:19:11'),
(55, 2, '2025-04-21', '17:00:00', 'not_done', 0, NULL, '2025-04-02 23:56:14', '2025-04-04 00:19:11'),
(56, 2, '2025-04-22', '17:00:00', 'not_done', 0, NULL, '2025-04-02 23:56:14', '2025-04-04 00:19:11'),
(57, 2, '2025-04-23', '17:00:00', 'not_done', 0, NULL, '2025-04-02 23:56:14', '2025-04-04 00:19:11'),
(58, 2, '2025-04-24', '17:00:00', 'not_done', 0, NULL, '2025-04-02 23:56:14', '2025-04-04 00:19:11'),
(59, 2, '2025-04-25', '17:00:00', 'not_done', 0, NULL, '2025-04-02 23:56:14', '2025-04-04 00:19:11'),
(60, 2, '2025-04-26', '17:00:00', 'not_done', 0, NULL, '2025-04-02 23:56:14', '2025-04-04 00:19:11'),
(61, 2, '2025-04-27', '17:00:00', 'not_done', 0, NULL, '2025-04-02 23:56:14', '2025-04-04 00:19:11'),
(62, 2, '2025-04-28', '17:00:00', 'not_done', 0, NULL, '2025-04-02 23:56:14', '2025-04-04 00:19:11'),
(63, 2, '2025-04-29', '17:00:00', 'not_done', 0, NULL, '2025-04-02 23:56:14', '2025-04-04 00:19:11'),
(64, 2, '2025-04-30', '17:00:00', 'not_done', 0, NULL, '2025-04-02 23:56:14', '2025-04-04 00:19:11'),
(65, 3, '2025-04-03', '00:30:00', 'completed', 0, NULL, '2025-04-02 23:56:14', '2025-04-03 12:23:58'),
(66, 3, '2025-04-04', '00:30:00', 'not_done', 0, NULL, '2025-04-02 23:56:14', '2025-04-04 00:22:47'),
(67, 3, '2025-04-05', '00:30:00', 'not_done', 0, NULL, '2025-04-02 23:56:14', '2025-04-04 00:22:47'),
(68, 3, '2025-04-06', '00:30:00', 'not_done', 0, NULL, '2025-04-02 23:56:14', '2025-04-04 00:22:47'),
(69, 3, '2025-04-07', '00:30:00', 'not_done', 0, NULL, '2025-04-02 23:56:14', '2025-04-04 00:22:47'),
(70, 3, '2025-04-08', '00:30:00', 'not_done', 0, NULL, '2025-04-02 23:56:14', '2025-04-04 00:22:47'),
(71, 3, '2025-04-09', '00:30:00', 'not_done', 0, NULL, '2025-04-02 23:56:14', '2025-04-04 00:22:47'),
(72, 3, '2025-04-10', '00:30:00', 'not_done', 0, NULL, '2025-04-02 23:56:14', '2025-04-04 00:22:47'),
(73, 3, '2025-04-11', '00:30:00', 'not_done', 0, NULL, '2025-04-02 23:56:14', '2025-04-04 00:22:47'),
(74, 3, '2025-04-12', '00:30:00', 'not_done', 0, NULL, '2025-04-02 23:56:14', '2025-04-04 00:22:47'),
(75, 3, '2025-04-13', '00:30:00', 'not_done', 0, NULL, '2025-04-02 23:56:14', '2025-04-04 00:22:47'),
(76, 3, '2025-04-14', '00:30:00', 'not_done', 0, NULL, '2025-04-02 23:56:14', '2025-04-04 00:22:47'),
(77, 3, '2025-04-15', '00:30:00', 'not_done', 0, NULL, '2025-04-02 23:56:14', '2025-04-04 00:22:47'),
(78, 3, '2025-04-16', '00:30:00', 'not_done', 0, NULL, '2025-04-02 23:56:14', '2025-04-04 00:22:47'),
(79, 3, '2025-04-17', '00:30:00', 'not_done', 0, NULL, '2025-04-02 23:56:14', '2025-04-04 00:22:47'),
(80, 3, '2025-04-18', '00:30:00', 'not_done', 0, NULL, '2025-04-02 23:56:14', '2025-04-04 00:22:47'),
(81, 3, '2025-04-19', '00:30:00', 'not_done', 0, NULL, '2025-04-02 23:56:14', '2025-04-04 00:22:47'),
(82, 3, '2025-04-20', '00:30:00', 'not_done', 0, NULL, '2025-04-02 23:56:14', '2025-04-04 00:22:47'),
(83, 3, '2025-04-21', '00:30:00', 'not_done', 0, NULL, '2025-04-02 23:56:14', '2025-04-04 00:22:47'),
(84, 3, '2025-04-22', '00:30:00', 'not_done', 0, NULL, '2025-04-02 23:56:14', '2025-04-04 00:22:47'),
(85, 3, '2025-04-23', '00:30:00', 'not_done', 0, NULL, '2025-04-02 23:56:14', '2025-04-04 00:22:47'),
(86, 3, '2025-04-24', '00:30:00', 'not_done', 0, NULL, '2025-04-02 23:56:14', '2025-04-04 00:22:47'),
(87, 3, '2025-04-25', '00:30:00', 'not_done', 0, NULL, '2025-04-02 23:56:14', '2025-04-04 00:22:47'),
(88, 3, '2025-04-26', '00:30:00', 'not_done', 0, NULL, '2025-04-02 23:56:14', '2025-04-04 00:22:47'),
(89, 3, '2025-04-27', '00:30:00', 'not_done', 0, NULL, '2025-04-02 23:56:14', '2025-04-04 00:22:47'),
(90, 3, '2025-04-28', '00:30:00', 'not_done', 0, NULL, '2025-04-02 23:56:14', '2025-04-04 00:22:47'),
(91, 3, '2025-04-29', '00:30:00', 'not_done', 0, NULL, '2025-04-02 23:56:14', '2025-04-04 00:22:47'),
(92, 3, '2025-04-30', '00:30:00', 'not_done', 0, NULL, '2025-04-02 23:56:14', '2025-04-04 00:22:47'),
(129, 7, '2025-04-05', '15:00:00', 'pending', 0, NULL, '2025-04-02 23:56:14', '2025-04-02 23:56:14'),
(130, 7, '2025-04-12', '15:00:00', 'pending', 0, NULL, '2025-04-02 23:56:14', '2025-04-02 23:56:14'),
(131, 7, '2025-04-19', '15:00:00', 'pending', 0, NULL, '2025-04-02 23:56:14', '2025-04-02 23:56:14'),
(132, 7, '2025-04-26', '15:00:00', 'pending', 0, NULL, '2025-04-02 23:56:14', '2025-04-02 23:56:14'),
(253, 28, '2025-04-03', '16:38:07', '', 0, NULL, '2025-04-03 12:38:07', '2025-04-03 14:15:06'),
(254, 31, '2025-04-03', '15:39:06', 'snoozed', 0, NULL, '2025-04-03 12:39:06', '2025-04-03 12:39:06'),
(255, 7, '2025-04-03', '15:41:30', '', 0, NULL, '2025-04-03 12:41:30', '2025-04-03 14:34:06'),
(256, 3, '2025-04-03', '00:30:00', 'completed', 0, NULL, '2025-04-03 13:16:47', '2025-04-03 13:16:53'),
(257, 3, '2025-04-03', '00:30:00', 'completed', 0, NULL, '2025-04-03 13:18:19', '2025-04-03 13:18:19');

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

--
-- Dumping data for table `task_recurrence_rules`
--

INSERT INTO `task_recurrence_rules` (`id`, `task_id`, `frequency`, `times_per_period`, `specific_days`, `start_date`, `end_date`, `last_generated_date`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 'daily', 1, NULL, '2025-04-02', NULL, '2025-04-03', 1, '2025-04-02 19:13:35', '2025-04-02 23:56:14'),
(2, 2, 'daily', 1, NULL, '2025-04-02', NULL, '2025-04-03', 1, '2025-04-02 19:13:35', '2025-04-02 23:56:14'),
(3, 3, 'daily', 1, NULL, '2025-04-02', NULL, '2025-04-03', 1, '2025-04-02 19:13:35', '2025-04-02 23:56:14'),
(7, 7, 'weekly', 1, '[\"saturday\"]', '2025-04-02', NULL, '2025-04-03', 1, '2025-04-02 19:13:35', '2025-04-02 23:56:14'),
(13, 28, 'weekly', 1, '\"[\\\"4\\\",\\\"5\\\",\\\"6\\\"]\"', '0000-00-00', NULL, NULL, 0, '2025-04-03 00:09:04', '2025-04-04 00:15:52');

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
(11, 186, '<p>This is very nice! i like it </p>', '2025-03-31 22:07:53', '2025-03-31 22:07:53', '2025-03-31 22:07:53');

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
(84, 186, 'completed', 80, 5, '2025-03-31 22:26:31', '2025-03-31 22:26:07', '');

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
(10, 186, '<p>1+1</p>', 'pending', '<p>2</p>', '2025-03-31 22:08:03', '2025-03-31 22:08:11', 1);

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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
