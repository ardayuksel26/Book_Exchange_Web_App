-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Dec 23, 2025 at 01:07 PM
-- Server version: 8.0.44-0ubuntu0.22.04.1
-- PHP Version: 7.4.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `gr2025-012_db`
--

-- --------------------------------------------------------
-- Table structure for table `books`
--

CREATE TABLE `books` (
  `book_id` int NOT NULL,
  `user_id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `author` varchar(255) NOT NULL,
  `publication_year` year NOT NULL,
  `condition` enum('new','good','fair','worn') NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `description` text,
  `price` decimal(10,2) DEFAULT NULL,
  `status` enum('available','rented','unavailable') DEFAULT 'available',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `books`
--

INSERT INTO `books` (`book_id`, `user_id`, `title`, `author`, `publication_year`, `condition`, `category`, `description`, `price`, `status`, `created_at`) VALUES
(101, 2, 'Introduction to Algorithms', 'Thomas H. Cormen', '2009', 'new', 'Computer Science', NULL, 45.00, 'rented', '2025-11-28 07:49:54'),
(104, 3, 'Introduction to Physics', 'David Halliday', '2013', 'good', 'Physics', NULL, 30.00, 'rented', '2025-11-28 07:49:54'),
(109, 1, 'Computer Science Distilled', 'Wladston Ferreira', '2017', 'fair', 'Computer Science', NULL, 15.50, 'available', '2025-11-28 07:49:54'),
(112, 4, 'Intro to Chemistry', 'Steven S. Zumdahl', '2018', 'worn', 'Science', NULL, 25.00, 'rented', '2025-11-28 07:49:54'),
(115, 1, 'Data Science from Scratch', 'Joel Grus', '2019', 'new', 'Computer Science', NULL, 35.00, 'available', '2025-11-28 07:49:54'),
(202, 4, 'Modern Design', 'Mike John', '2020', 'good', 'Art', NULL, 20.00, 'rented', '2025-11-28 07:49:54'),
(205, 5, 'Refactoring UI', 'Adam Wathan', '2018', 'new', 'Design', NULL, 50.00, 'rented', '2025-11-28 07:49:54'),
(305, 2, 'Calculus II', 'James Stewart', '2015', 'good', 'Math', NULL, 40.00, 'rented', '2025-11-28 07:49:54'),
(401, 6, 'Biology 101', 'Campbell', '2016', 'fair', 'Science', NULL, 28.00, 'rented', '2025-11-28 07:49:54'),
(402, 7, 'Python Guide', 'Guido van Rossum', '2021', 'new', 'Computer Science', NULL, 60.00, 'available', '2025-11-28 07:49:54'),
(408, 8, 'Advanced Literature', 'Test Author', '0000', '', 'Literature', 'An interesting sample book', 34.00, 'available', '2025-12-19 14:39:58');

-- --------------------------------------------------------
-- Table structure for table `book_images`
--

CREATE TABLE `book_images` (
  `image_id` int NOT NULL,
  `book_id` int NOT NULL,
  `image_path` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `book_images`
--

INSERT INTO `book_images` (`image_id`, `book_id`, `image_path`) VALUES
(1, 101, '/images/algo.jpg'),
(2, 104, '/images/physics.jpg'),
(3, 115, '/images/datascience.jpg');

-- --------------------------------------------------------
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int NOT NULL,
  `user_id` int NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notification_id`, `user_id`, `message`, `is_read`, `created_at`) VALUES
(801, 5, 'Your rental request for \'Calculus 1\' was accepted.', 0, '2025-11-28 06:30:00'),
(802, 5, 'User 1 sent you a swap offer.', 0, '2025-11-28 05:15:00'),
(803, 5, 'Reminder: Please return \'Physics\' by tomorrow.', 1, '2025-11-27 11:00:00'),
(804, 5, 'Your book \'Chemistry\' has been successfully listed.', 1, '2025-11-26 07:00:00'),
(805, 5, 'Your rental request for \'History\' was declined.', 1, '2025-11-25 13:45:00'),
(806, 1, 'Your rental request for Book #101 has been successfully sent.', 1, '2025-12-19 09:15:39'),
(807, 1, 'Your rental request for Book #101 has been successfully sent.', 1, '2025-12-19 09:16:02'),
(808, 1, 'Your swap request for Book #104 has been sent.', 1, '2025-12-19 09:25:12');

-- --------------------------------------------------------
-- Table structure for table `rentals`
--

CREATE TABLE `rentals` (
  `rental_id` int NOT NULL,
  `book_id` int NOT NULL,
  `renter_id` int NOT NULL,
  `owner_id` int NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('pending','accepted','declined','completed') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `rentals`
--

INSERT INTO `rentals` (`rental_id`, `book_id`, `renter_id`, `owner_id`, `start_date`, `end_date`, `status`, `created_at`) VALUES
(501, 101, 5, 2, '2025-12-01', '2025-12-15', 'pending', '2025-11-28 07:49:54'),
(502, 104, 7, 3, '2025-12-02', '2025-12-16', 'pending', '2025-11-28 07:49:54'),
(503, 115, 6, 1, '2025-12-03', '2025-12-17', 'pending', '2025-11-28 07:49:54'),
(504, 202, 5, 4, '2025-12-05', '2025-12-20', 'pending', '2025-11-28 07:49:54'),
(505, 305, 9, 2, '2025-12-10', '2025-12-24', 'pending', '2025-11-28 07:49:54'),
(517, 101, 1, 2, '0000-00-00', '0000-00-00', 'pending', '2025-12-19 09:15:39'),
(518, 101, 1, 2, '0000-00-00', '0000-00-00', 'pending', '2025-12-19 09:16:02'),
(519, 401, 1, 6, '2025-12-19', '2025-12-26', 'pending', '2025-12-19 10:40:53'),
(520, 305, 1, 2, '2025-12-19', '2025-12-26', 'pending', '2025-12-19 11:02:00'),
(521, 112, 1, 4, '2025-12-19', '2025-12-26', 'pending', '2025-12-19 12:04:28'),
(522, 104, 1, 3, '2025-12-19', '2025-12-26', 'pending', '2025-12-19 13:10:34'),
(523, 202, 1, 4, '2025-12-19', '2025-12-26', 'pending', '2025-12-19 13:11:56'),
(524, 205, 3, 5, '2025-12-19', '2025-12-26', 'pending', '2025-12-19 13:14:50'),
(525, 402, 1, 7, '2025-12-19', '2025-12-26', 'pending', '2025-12-19 13:28:20'),
(526, 109, 8, 1, '2025-12-19', '2025-12-26', 'pending', '2025-12-19 14:55:20'),
(527, 402, 1, 7, '2025-12-19', '2025-12-26', 'pending', '2025-12-19 15:04:50'),
(528, 402, 1, 7, '2025-12-19', '2025-12-26', 'pending', '2025-12-19 15:04:56');

-- --------------------------------------------------------
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `report_id` int NOT NULL,
  `reported_book_id` int NOT NULL,
  `reporter_id` int NOT NULL,
  `reason` text NOT NULL,
  `status` enum('pending','reviewed') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `reports`
--

INSERT INTO `reports` (`report_id`, `reported_book_id`, `reporter_id`, `reason`, `status`, `created_at`) VALUES
(1, 401, 1, 'Wrong category listed', 'pending', '2025-11-28 07:49:54'),
(2, 305, 3, 'Inappropriate image', 'reviewed', '2025-11-28 07:49:54'),
(3, 402, 2, 'Fake book listing', 'pending', '2025-11-28 07:49:54'),
(4, 202, 4, 'Spam description', 'pending', '2025-11-28 07:49:54'),
(5, 115, 6, 'Price is too high', 'reviewed', '2025-11-28 07:49:54');

-- --------------------------------------------------------
-- Table structure for table `swaps`
--

CREATE TABLE `swaps` (
  `swap_id` int NOT NULL,
  `requested_book_id` int NOT NULL,
  `offered_book_id` int NOT NULL,
  `requester_id` int NOT NULL,
  `owner_id` int NOT NULL,
  `status` enum('pending','accepted','declined','completed') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `swaps`
--

INSERT INTO `swaps` (`swap_id`, `requested_book_id`, `offered_book_id`, `requester_id`, `owner_id`, `status`, `created_at`) VALUES
(20, 305, 104, 5, 2, 'pending', '2025-11-28 07:49:54'),
(21, 101, 205, 7, 5, 'accepted', '2025-11-28 07:49:54'),
(22, 202, 305, 6, 4, 'declined', '2025-11-28 07:49:54'),
(23, 115, 109, 9, 1, 'pending', '2025-11-28 07:49:54'),
(24, 112, 104, 5, 4, 'completed', '2025-11-28 07:49:54'),
(28, 104, 109, 1, 3, 'pending', '2025-12-19 09:25:12'),
(29, 112, 109, 1, 4, 'pending', '2025-12-19 11:59:55'),
(30, 112, 109, 1, 4, 'pending', '2025-12-19 12:01:11'),
(31, 112, 115, 1, 4, 'pending', '2025-12-19 12:01:58'),
(32, 402, 109, 1, 7, 'pending', '2025-12-19 13:12:03'),
(33, 402, 109, 1, 7, 'pending', '2025-12-19 13:28:13'),
(34, 115, 408, 8, 1, 'pending', '2025-12-19 14:55:44'),
(35, 115, 408, 8, 1, 'pending', '2025-12-19 14:59:16'),
(36, 408, 109, 1, 8, 'pending', '2025-12-19 15:06:35'),
(37, 109, 408, 8, 1, 'pending', '2025-12-23 09:44:34');

-- --------------------------------------------------------
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('student','admin') DEFAULT 'student',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `first_name`, `last_name`, `email`, `password_hash`, `role`, `created_at`) VALUES
(1, 'User1', 'Demo', 'user1@univ.edu', '$2y$10$dUESgxhw2YzWN1zqPzjrguUE5peZjA3Khjbe5pBJDxjmA0kiVFUG2', 'student', '2025-11-28 07:49:54'),
(2, 'User2', 'Demo', 'user2@univ.edu', '$2y$10$dUESgxhw2YzWN1zqPzjrguUE5peZjA3Khjbe5pBJDxjmA0kiVFUG2', 'student', '2025-11-28 07:49:54'),
(3, 'User3', 'Demo', 'user3@univ.edu', '$2y$10$dUESgxhw2YzWN1zqPzjrguUE5peZjA3Khjbe5pBJDxjmA0kiVFUG2', 'student', '2025-11-28 07:49:54'),
(4, 'User4', 'Demo', 'user4@univ.edu', '$2y$10$dUESgxhw2YzWN1zqPzjrguUE5peZjA3Khjbe5pBJDxjmA0kiVFUG2', 'student', '2025-11-28 07:49:54'),
(5, 'User5', 'Demo', 'user5@univ.edu', '$2y$10$dUESgxhw2YzWN1zqPzjrguUE5peZjA3Khjbe5pBJDxjmA0kiVFUG2', 'student', '2025-11-28 07:49:54'),
(6, 'User6', 'Demo', 'user6@univ.edu', '$2y$10$dUESgxhw2YzWN1zqPzjrguUE5peZjA3Khjbe5pBJDxjmA0kiVFUG2', 'student', '2025-11-28 07:49:54'),
(7, 'User7', 'Demo', 'user7@univ.edu', '$2y$10$dUESgxhw2YzWN1zqPzjrguUE5peZjA3Khjbe5pBJDxjmA0kiVFUG2', 'student', '2025-11-28 07:49:54'),
(8, 'User8', 'Demo', 'user8@univ.edu', '$2y$10$Wxv0VAliujGeTxCE5xsBP.v5zg2tfQp/DfysfFt6/bj6i4DQMn4ua', 'student', '2025-12-19 14:15:40'),
(9, 'User9', 'Demo', 'user9@univ.edu', '$2y$10$dBfw9DwQskQgwgLrdIiVXuunZFTjsMviOeKw6Hl9D6DMp9Pxq13Mq', 'student', '2025-12-23 09:40:28'),
(10, 'Admin', 'User', 'admin@univ.edu', '$2y$10$5y5qJf8BCDacYoqI/dyTXe53DB4vYgR7dWYlJgyM8eKT/zh1xbz92', 'admin', '2025-12-19 10:15:16');

--
-- Indexes for dumped tables
--

ALTER TABLE `books`
  ADD PRIMARY KEY (`book_id`),
  ADD KEY `user_id` (`user_id`);

ALTER TABLE `book_images`
  ADD PRIMARY KEY (`image_id`),
  ADD KEY `book_id` (`book_id`);

ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `user_id` (`user_id`);

ALTER TABLE `rentals`
  ADD PRIMARY KEY (`rental_id`),
  ADD KEY `book_id` (`book_id`),
  ADD KEY `renter_id` (`renter_id`),
  ADD KEY `owner_id` (`owner_id`);

ALTER TABLE `reports`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `reported_book_id` (`reported_book_id`),
  ADD KEY `reporter_id` (`reporter_id`);

ALTER TABLE `swaps`
  ADD PRIMARY KEY (`swap_id`),
  ADD KEY `requested_book_id` (`requested_book_id`),
  ADD KEY `offered_book_id` (`offered_book_id`),
  ADD KEY `requester_id` (`requester_id`),
  ADD KEY `owner_id` (`owner_id`);

ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

ALTER TABLE `books` MODIFY `book_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=409;
ALTER TABLE `book_images` MODIFY `image_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
ALTER TABLE `notifications` MODIFY `notification_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=809;
ALTER TABLE `rentals` MODIFY `rental_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=530;
ALTER TABLE `reports` MODIFY `report_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
ALTER TABLE `swaps` MODIFY `swap_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;
ALTER TABLE `users` MODIFY `user_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

ALTER TABLE `books`
  ADD CONSTRAINT `books_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

ALTER TABLE `book_images`
  ADD CONSTRAINT `book_images_ibfk_1` FOREIGN KEY (`book_id`) REFERENCES `books` (`book_id`) ON DELETE CASCADE;

ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

ALTER TABLE `rentals`
  ADD CONSTRAINT `rentals_ibfk_1` FOREIGN KEY (`book_id`) REFERENCES `books` (`book_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `rentals_ibfk_2` FOREIGN KEY (`renter_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `rentals_ibfk_3` FOREIGN KEY (`owner_id`) REFERENCES `users` (`user_id`);

ALTER TABLE `reports`
  ADD CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`reported_book_id`) REFERENCES `books` (`book_id`),
  ADD CONSTRAINT `reports_ibfk_2` FOREIGN KEY (`reporter_id`) REFERENCES `users` (`user_id`);

ALTER TABLE `swaps`
  ADD CONSTRAINT `swaps_ibfk_1` FOREIGN KEY (`requested_book_id`) REFERENCES `books` (`book_id`),
  ADD CONSTRAINT `swaps_ibfk_2` FOREIGN KEY (`offered_book_id`) REFERENCES `books` (`book_id`),
  ADD CONSTRAINT `swaps_ibfk_3` FOREIGN KEY (`requester_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `swaps_ibfk_4` FOREIGN KEY (`owner_id`) REFERENCES `users` (`user_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
