-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3307
-- Generation Time: Nov 22, 2026 at 10:08 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `studycircle_dev_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `answers`
--

CREATE TABLE `answers` (
  `id` int(10) UNSIGNED NOT NULL,
  `question_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `body` text NOT NULL,
  `is_best` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `answers`
--

INSERT INTO `answers` (`id`, `question_id`, `user_id`, `body`, `is_best`, `created_at`) VALUES
(1, 1, 3, '2NF removes partial dependencies on part of a composite key. 3NF removes transitive dependencies, where a non-key column depends on another non-key column.', 1, '2026-11-14 09:03:57'),
(2, 1, 4, 'Tip: in 3NF every non-key attribute must depend on the key, the whole key and nothing but the key.', 0, '2026-11-15 07:03:57'),
(3, 1, 5, 'The normalization summary note in the Materials page has a worked example for both.', 0, '2026-11-15 10:03:57'),
(4, 2, 2, 'Call session_regenerate_id(true) right after a successful login and store only the user id and role in the session.', 1, '2026-11-15 11:03:57'),
(5, 2, 4, 'Also use password_verify() and always escape output with htmlspecialchars().', 0, '2026-11-16 07:03:57'),
(6, 3, 2, 'Focus on use case, class and sequence diagrams. Past papers usually ask for one of each.', 1, '2026-11-16 12:03:57'),
(7, 4, 3, 'Try factoring the numerator first. Often the zero term cancels with the denominator.', 0, '2026-11-18 07:03:57'),
(8, 5, 4, 'A /26 has 64 addresses. Subtract 2 (network and broadcast) to get 62 usable hosts.', 1, '2026-11-19 11:03:57');

-- --------------------------------------------------------

--
-- Table structure for table `group_members`
--

CREATE TABLE `group_members` (
  `id` int(10) UNSIGNED NOT NULL,
  `group_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `group_members`
--

INSERT INTO `group_members` (`id`, `group_id`, `user_id`, `joined_at`) VALUES
(1, 1, 2, '2026-11-20 07:03:57'),
(2, 1, 3, '2026-11-20 07:03:57'),
(3, 1, 5, '2026-11-20 07:03:57'),
(4, 1, 6, '2026-11-20 07:03:57'),
(5, 2, 3, '2026-11-20 07:03:57'),
(6, 2, 2, '2026-11-20 07:03:57'),
(7, 2, 4, '2026-11-20 07:03:57'),
(8, 3, 4, '2026-11-20 07:03:57'),
(9, 3, 2, '2026-11-20 07:03:57'),
(10, 3, 5, '2026-11-20 07:03:57');

-- --------------------------------------------------------

--
-- Table structure for table `materials`
--

CREATE TABLE `materials` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `subject_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `downloads` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `materials`
--

INSERT INTO `materials` (`id`, `user_id`, `subject_id`, `title`, `description`, `file_name`, `file_path`, `status`, `downloads`, `created_at`) VALUES
(1, 2, 1, 'Normalization Summary', 'Short notes on 1NF, 2NF, 3NF and BCNF with worked examples.', 'normalization-summary.pdf', 'sample_normalization.pdf', 'approved', 96, '2026-10-31 07:03:57'),
(2, 3, 2, 'REST API Cheat Sheet', 'Common HTTP methods, status codes and PHP examples.', 'rest-api-cheatsheet.pdf', 'sample_rest_api.pdf', 'approved', 74, '2026-11-02 07:03:57'),
(3, 4, 3, 'UML Diagrams Guide', 'Use case, class and sequence diagrams explained step by step.', 'uml-diagrams-guide.pdf', 'sample_uml.pdf', 'approved', 58, '2026-11-05 07:03:57'),
(4, 5, 4, 'Calculus Past Papers', 'Past exam questions with short answers.', 'calculus-past-papers.pdf', 'sample_calculus.pdf', 'approved', 132, '2026-11-08 07:03:57'),
(5, 2, 1, 'SQL Joins Explained', 'INNER, LEFT, RIGHT and FULL joins with diagrams.', 'sql-joins.pdf', 'sample_sql_joins.pdf', 'approved', 88, '2026-11-10 07:03:57'),
(6, 3, 2, 'JavaScript Basics', 'Variables, functions, arrays and DOM basics.', 'javascript-basics.pdf', 'sample_js_basics.pdf', 'approved', 41, '2026-11-12 07:03:57'),
(7, 6, 5, 'Subnetting Made Easy', 'Step by step subnetting method with practice questions.', 'subnetting.pdf', 'sample_subnetting.pdf', 'pending', 0, '2026-11-18 07:03:57'),
(8, 4, 6, 'Loops and Arrays Practice', 'Practice problems with answers for loops and arrays.', 'loops-arrays.pdf', 'sample_loops_arrays.pdf', 'pending', 0, '2026-11-19 07:03:57'),
(9, 5, 3, 'Agile vs Waterfall', 'Comparison table of both approaches.', 'agile-vs-waterfall.pdf', 'sample_agile.pdf', 'rejected', 0, '2026-11-15 07:03:57');

-- --------------------------------------------------------

--
-- Table structure for table `material_ratings`
--

CREATE TABLE `material_ratings` (
  `id` int(10) UNSIGNED NOT NULL,
  `material_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `rating` tinyint(3) UNSIGNED NOT NULL,
  `comment` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ;

--
-- Dumping data for table `material_ratings`
--

INSERT INTO `material_ratings` (`id`, `material_id`, `user_id`, `rating`, `comment`, `created_at`) VALUES
(1, 1, 3, 5, 'Very clear examples. Helped me before the exam.', '2026-11-20 07:03:57'),
(2, 1, 4, 5, 'Great summary of all the normal forms.', '2026-11-20 07:03:57'),
(3, 1, 5, 4, 'Good notes. Could use more BCNF examples.', '2026-11-20 07:03:57'),
(4, 2, 2, 5, 'Handy cheat sheet for the group project.', '2026-11-20 07:03:57'),
(5, 2, 4, 4, 'Useful status code list.', '2026-11-20 07:03:57'),
(6, 3, 2, 4, 'Diagrams are easy to follow.', '2026-11-20 07:03:57'),
(7, 3, 3, 4, 'Nice explanation of sequence diagrams.', '2026-11-20 07:03:57'),
(8, 4, 2, 5, 'Best past paper set so far.', '2026-11-20 07:03:57'),
(9, 4, 3, 5, 'Answers were very helpful.', '2026-11-20 07:03:57'),
(10, 5, 3, 5, 'The join diagrams made it click for me.', '2026-11-20 07:03:57'),
(11, 5, 6, 4, 'Clear and short.', '2026-11-20 07:03:57'),
(12, 6, 4, 4, 'Good starting point for beginners.', '2026-11-20 07:03:57');

-- --------------------------------------------------------

--
-- Table structure for table `questions`
--

CREATE TABLE `questions` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `subject_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(200) NOT NULL,
  `body` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `questions`
--

INSERT INTO `questions` (`id`, `user_id`, `subject_id`, `title`, `body`, `created_at`) VALUES
(1, 2, 1, 'What is the difference between 2NF and 3NF?', 'I understand 1NF but I keep mixing up partial and transitive dependencies. Can someone explain with a small example?', '2026-11-14 07:03:57'),
(2, 3, 2, 'How do I start a PHP session safely?', 'What should I do after login to keep the session secure? Should I regenerate the session id?', '2026-11-15 07:03:57'),
(3, 4, 3, 'Best way to prepare for the UML exam?', 'Which diagrams are most likely to come up, and how much detail do we need to draw?', '2026-11-16 07:03:57'),
(4, 5, 4, 'Explain limits with an example', 'I cannot understand how to find the limit when the denominator becomes zero.', '2026-11-17 07:03:57'),
(5, 6, 5, 'How many hosts are in a /26 network?', 'I am confused about how to count usable hosts. Do we subtract anything?', '2026-11-19 07:03:57');

-- --------------------------------------------------------

--
-- Table structure for table `study_groups`
--

CREATE TABLE `study_groups` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(120) NOT NULL,
  `subject_id` int(10) UNSIGNED NOT NULL,
  `creator_id` int(10) UNSIGNED NOT NULL,
  `description` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `study_groups`
--

INSERT INTO `study_groups` (`id`, `name`, `subject_id`, `creator_id`, `description`, `created_at`) VALUES
(1, 'DBMS Exam Prep', 1, 2, 'Weekly revision for the Database Systems exam: SQL practice and past papers.', '2026-11-06 07:03:57'),
(2, 'Web Project Team', 2, 3, 'Helping each other with PHP and MySQL for the WAD final project.', '2026-11-10 07:03:57'),
(3, 'SE Study Circle', 3, 4, 'UML practice and requirement analysis discussions.', '2026-11-13 07:03:57');

-- --------------------------------------------------------

--
-- Table structure for table `study_sessions`
--

CREATE TABLE `study_sessions` (
  `id` int(10) UNSIGNED NOT NULL,
  `group_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(150) NOT NULL,
  `session_date` datetime NOT NULL,
  `location` varchar(200) DEFAULT NULL,
  `created_by` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `study_sessions`
--

INSERT INTO `study_sessions` (`id`, `group_id`, `title`, `session_date`, `location`, `created_by`, `created_at`) VALUES
(1, 1, 'Exam revision', '2026-11-22 16:33:57', 'Library, Study Room 2', 2, '2026-11-20 07:03:57'),
(2, 1, 'SQL joins practice', '2026-11-27 17:33:57', 'Online (Zoom link in group)', 2, '2026-11-20 07:03:57'),
(3, 2, 'Project meetup', '2026-11-23 22:33:57', 'Computer Lab 3', 3, '2026-11-20 07:03:57'),
(4, 3, 'UML diagram workshop', '2026-11-25 14:33:57', 'Seminar Room B', 4, '2026-11-20 07:03:57');

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` int(10) UNSIGNED NOT NULL,
  `code` varchar(20) NOT NULL,
  `name` varchar(120) NOT NULL,
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`id`, `code`, `name`, `description`) VALUES
(1, 'DBS', 'Database Systems', 'ER modelling, SQL, normalization and transactions'),
(2, 'WAD', 'Web Application Development', 'HTML, CSS, JavaScript, PHP and MySQL'),
(3, 'SE', 'Software Engineering', 'Requirements, design, UML and testing'),
(4, 'MAT', 'Mathematics', 'Calculus, algebra and discrete maths'),
(5, 'NET', 'Computer Networks', 'Network models, protocols and addressing'),
(6, 'PRG', 'Programming Fundamentals', 'Core programming concepts and problem solving');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('student','admin') NOT NULL DEFAULT 'student',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `profile_image` varchar(255) DEFAULT NULL,
  `personal_goal` varchar(255) DEFAULT NULL,
  `login_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `gender` enum('female','male') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `status`, `created_at`, `profile_image`, `personal_goal`, `login_count`, `gender`) VALUES
(1, 'StudyCircle Admin', 'admin@nsbm.ac.lk', '$2y$10$MQKbmQ5zHZQM4DYFra1BQOqThC3LsZxvszeD7z1mLApsGVJDk38PK', 'admin', 'active', '2026-11-20 07:03:57', NULL, NULL, 3, NULL),
(2, 'Nimal Perera', 'nimal@students.nsbm.ac.lk', '$2y$10$SbL1DuoPxZTqhW8BdYDUz.GaRagHe59AkO6Titf2P73pf5A8ozDAa', 'student', 'active', '2026-11-20 07:03:57', 'f0240dbda2583683341235c60bb7931e.jpg', 'Improve my programming skills and complete my assignments on time.', 3, NULL),
(3, 'Kasuni Dias', 'kasuni@students.nsbm.ac.lk', '$2y$10$SbL1DuoPxZTqhW8BdYDUz.GaRagHe59AkO6Titf2P73pf5A8ozDAa', 'student', 'active', '2026-11-20 07:03:57', NULL, NULL, 1, NULL),
(4, 'Amal Silva', 'amal@students.nsbm.ac.lk', '$2y$10$SbL1DuoPxZTqhW8BdYDUz.GaRagHe59AkO6Titf2P73pf5A8ozDAa', 'student', 'active', '2026-11-20 07:03:57', NULL, NULL, 1, NULL),
(5, 'Dilani Rajapaksha', 'dilani@students.nsbm.ac.lk', '$2y$10$SbL1DuoPxZTqhW8BdYDUz.GaRagHe59AkO6Titf2P73pf5A8ozDAa', 'student', 'active', '2026-11-20 07:03:57', NULL, NULL, 1, NULL),
(6, 'Tharindu Fernando', 'tharindu@students.nsbm.ac.lk', '$2y$10$SbL1DuoPxZTqhW8BdYDUz.GaRagHe59AkO6Titf2P73pf5A8ozDAa', 'student', 'active', '2026-11-20 07:03:57', NULL, NULL, 1, NULL),
(7, 'Sanduni Jayawardena', 'sanduni@students.nsbm.ac.lk', '$2y$10$SbL1DuoPxZTqhW8BdYDUz.GaRagHe59AkO6Titf2P73pf5A8ozDAa', 'student', 'inactive', '2026-11-20 07:03:57', NULL, NULL, 1, NULL),
(8, 'Kaveesha Gamage', 'gnalinie69@gmail.com', '$2y$10$B2T0aAtjkLuK9Zxn3lUSdu.M2UcE5DRYRX5uebKkCGUpRTz8fqLX.', 'student', 'active', '2026-11-20 11:53:52', NULL, NULL, 2, NULL),
(9, 'test', 'randi122@gmail.com', '$2y$10$WN49maJCDs7fgCwldM59wuKtb54cQ7LbR1BqcOK4zMTJRp2ny81cS', 'student', 'active', '2026-11-20 12:09:00', '8bf4beec2b6f0296b286305e18b6c818.jpg', 'Improve my Java programming skills.', 5, 'female'),
(10, 'Test Female Student', 'testfemale2026@example.com', '$2y$10$YSS1AbE1kFebZwAYNQEw1O0LjQf.onNn/LlCjIpCn6WtGAVRV9O6O', 'student', 'active', '2026-11-20 13:17:03', NULL, NULL, 1, 'female'),
(11, 'test2', 'testmale2026@example.com', '$2y$10$YKavKoqJppKNnyMUJAV.tOMvZWs5s4em/xN0nJAXVzXxKmPkfrRN6', 'student', 'active', '2026-11-20 14:01:23', NULL, NULL, 1, 'male'),
(12, 'Test Admin User', 'admincheck2026@example.com', '$2y$10$suyzFz87yHqWtebjy953FuWRnzfeQLovWI6TdI/0uG2j9vqSxSyVe', 'student', 'active', '2026-11-20 14:35:14', 'd1eefbede9c1d23c93f581950470cf81.jpg', NULL, 3, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `answers`
--
ALTER TABLE `answers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `question_id` (`question_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `group_members`
--
ALTER TABLE `group_members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_member` (`group_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `materials`
--
ALTER TABLE `materials`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_materials_status` (`status`),
  ADD KEY `idx_materials_subject` (`subject_id`);

--
-- Indexes for table `material_ratings`
--
ALTER TABLE `material_ratings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_one_rating` (`material_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `questions`
--
ALTER TABLE `questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `study_groups`
--
ALTER TABLE `study_groups`
  ADD PRIMARY KEY (`id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `creator_id` (`creator_id`);

--
-- Indexes for table `study_sessions`
--
ALTER TABLE `study_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `group_id` (`group_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_sessions_date` (`session_date`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `answers`
--
ALTER TABLE `answers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `group_members`
--
ALTER TABLE `group_members`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `materials`
--
ALTER TABLE `materials`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `material_ratings`
--
ALTER TABLE `material_ratings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `questions`
--
ALTER TABLE `questions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `study_groups`
--
ALTER TABLE `study_groups`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `study_sessions`
--
ALTER TABLE `study_sessions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `answers`
--
ALTER TABLE `answers`
  ADD CONSTRAINT `answers_ibfk_1` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `answers_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `group_members`
--
ALTER TABLE `group_members`
  ADD CONSTRAINT `group_members_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `study_groups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `group_members_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `materials`
--
ALTER TABLE `materials`
  ADD CONSTRAINT `materials_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `materials_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `material_ratings`
--
ALTER TABLE `material_ratings`
  ADD CONSTRAINT `material_ratings_ibfk_1` FOREIGN KEY (`material_id`) REFERENCES `materials` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `material_ratings_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `questions`
--
ALTER TABLE `questions`
  ADD CONSTRAINT `questions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `questions_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `study_groups`
--
ALTER TABLE `study_groups`
  ADD CONSTRAINT `study_groups_ibfk_1` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `study_groups_ibfk_2` FOREIGN KEY (`creator_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `study_sessions`
--
ALTER TABLE `study_sessions`
  ADD CONSTRAINT `study_sessions_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `study_groups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `study_sessions_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
