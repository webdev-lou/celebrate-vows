-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 05, 2026 at 10:22 PM
-- Server version: 8.0.20
-- PHP Version: 8.3.3

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_mikomae`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--

CREATE TABLE `admin_users` (
  `id` int NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin_users`
--

INSERT INTO `admin_users` (`id`, `username`, `password_hash`, `created_at`) VALUES
(1, 'admin', '$2y$10$yuXPwhhV25TtgxRlUthb6uFUwjSOcW5pNfZj80zcF3eBhCxZRV9PC', '2026-02-04 16:12:56');

-- --------------------------------------------------------

--
-- Table structure for table `guests`
--

CREATE TABLE `guests` (
  `id` int NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('confirmed','declined') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'confirmed',
  `message` text COLLATE utf8mb4_unicode_ci,
  `quiz_score` int DEFAULT NULL,
  `quiz_answers` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `guests`
--

INSERT INTO `guests` (`id`, `name`, `status`, `message`, `quiz_score`, `quiz_answers`, `created_at`) VALUES
(1, 'Marlou Mupas', 'confirmed', 'This is a test', 10, '{\"q1\": \"coffee\", \"q2\": \"simultaneously\", \"q3\": \"chocolate\", \"q4\": \"beach\", \"q5\": \"hiking\", \"q6\": \"3\", \"q7\": \"miko\", \"q8\": \"notebook\", \"q9\": \"japan\", \"q10\": \"perfect\"}', '2026-02-04 16:23:18');

-- --------------------------------------------------------

--
-- Table structure for table `quiz_questions`
--

CREATE TABLE `quiz_questions` (
  `id` int NOT NULL,
  `question` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` json NOT NULL,
  `correct_answer` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort_order` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `quiz_questions`
--

INSERT INTO `quiz_questions` (`id`, `question`, `options`, `correct_answer`, `sort_order`, `created_at`) VALUES
(1, 'Where was our first date?', '[\"At a coffee shop\", \"In the park\", \"At the movies\", \"Fancy dinner\"]', 'At a coffee shop', 1, '2026-02-04 16:12:56'),
(2, 'Who said \"I love you\" first?', '[\"Miko\", \"Mae\", \"At the same time\", \"The cat\"]', 'At the same time', 2, '2026-02-04 16:12:56'),
(3, 'What is Mae\'s favorite dessert?', '[\"Chocolate Cake\", \"Ice Cream\", \"Cheesecake\", \"Fresh Fruit\"]', 'Chocolate Cake', 3, '2026-02-04 16:12:56'),
(4, 'Where did Miko propose?', '[\"At the beach\", \"Top of a mountain\", \"At home\", \"At a restaurant\"]', 'At the beach', 4, '2026-02-04 16:12:56'),
(5, 'What is our first joint hobby?', '[\"Hiking\", \"Cooking\", \"Gaming\", \"Photography\"]', 'Hiking', 5, '2026-02-04 16:12:56'),
(6, 'How many years have we been together?', '[\"2 Years\", \"3 Years\", \"4 Years\", \"5 Years\"]', '3 Years', 6, '2026-02-04 16:12:56'),
(7, 'Who is the better cook?', '[\"Miko\", \"Mae\", \"We order delivery\", \"It\'s a tie\"]', 'Miko', 7, '2026-02-04 16:12:56'),
(8, 'What was the first movie we watched?', '[\"Titanic\", \"The Notebook\", \"The Avengers\", \"The Lion King\"]', 'The Notebook', 8, '2026-02-04 16:12:56'),
(9, 'What is Miko\'s dream travel destination?', '[\"Japan\", \"Italy\", \"Iceland\", \"New Zealand\"]', 'Japan', 9, '2026-02-04 16:12:56'),
(12, 'What is our \"Song\"?', '[\"Perfect\", \"God gave me you\", \"Your Love\", \"The Gift\"]', 'God gave me you', 10, '2026-02-04 16:53:37');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int NOT NULL,
  `setting_key` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text COLLATE utf8mb4_unicode_ci,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `updated_at`) VALUES
(1, 'couple_names', 'Miko & Mae', '2026-02-04 16:12:56'),
(2, 'wedding_date', '2026-02-28', '2026-02-05 21:04:17'),
(3, 'wedding_time', '15:00', '2026-02-04 16:12:56'),
(4, 'venue_name', 'San Agustin Church', '2026-02-04 16:12:56'),
(5, 'venue_address', 'Gen. Luna St, Intramuros, Manila', '2026-02-04 16:12:56'),
(6, 'rsvp_deadline', '2026-01-15', '2026-02-05 21:04:17'),
(7, 'groom_name', 'Miko', '2026-02-05 20:29:34'),
(8, 'bride_name', 'Mae', '2026-02-05 20:29:34'),
(11, 'wedding_hashtag', '#MAEdMIKOmplete', '2026-02-05 20:29:07');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `guests`
--
ALTER TABLE `guests`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `quiz_questions`
--
ALTER TABLE `quiz_questions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `guests`
--
ALTER TABLE `guests`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `quiz_questions`
--
ALTER TABLE `quiz_questions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
