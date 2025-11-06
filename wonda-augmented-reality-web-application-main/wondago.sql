-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Oct 12, 2025 at 02:23 PM
-- Server version: 10.4.33-MariaDB-log
-- PHP Version: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `wondago`
--

-- --------------------------------------------------------

--
-- Table structure for table `ar`
--

CREATE TABLE `ar` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `points` varchar(20) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `ar_path` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `coordinates` varchar(255) DEFAULT NULL,
  `gmapcoordinates` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `ar`
--

INSERT INTO `ar` (`id`, `name`, `points`, `description`, `ar_path`, `created_at`, `updated_at`, `coordinates`, `gmapcoordinates`) VALUES
(1, 'Hotel 1', '1', '<p><strong>Hotel 1 Descs</strong></p>', '', '2025-08-06 17:10:21', '2025-09-11 15:19:12', '721,581', ''),
(2, 'Hotel 2', '2', '<p>sad</p>', '', '2025-08-06 17:22:44', '2025-09-11 15:17:36', '629,530', ''),
(3, '', '', '<p>sad</p>', '', '2025-09-11 15:17:26', '2025-09-11 15:17:26', '0,0', '');

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `stars` tinyint(4) NOT NULL CHECK (`stars` between 1 and 5),
  `rating` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `feedback`
--

INSERT INTO `feedback` (`id`, `user_id`, `stars`, `rating`, `created_at`, `updated_at`) VALUES
(1, 2, 4, '\"Visiting this Mother\'s Wonderland was an unforgettable experience! From thrilling rides to family-friendly attractions, every corner offered something exciting. The staff were incredibly welcoming and attentive, and the entire park was clean, well-maintained, and beautifully themed. Whether you\'re looking for adventure, relaxation, or fun for the whole family, this place truly has it all. We can’t wait to come back!', '2025-07-24 13:38:10', '2025-07-24 14:13:23');

-- --------------------------------------------------------

--
-- Table structure for table `news`
--

CREATE TABLE `news` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `type` int(11) NOT NULL DEFAULT 0,
  `photo` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `featured` tinyint(1) DEFAULT 0,
  `discount` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `news`
--

INSERT INTO `news` (`id`, `name`, `type`, `photo`, `description`, `start_date`, `end_date`, `featured`, `discount`, `created_at`, `updated_at`) VALUES
(1, 'Mother\'s Day promo', 0, '1.png,2.png', '25% off for mother\'s day', '2025-05-01', '2025-07-31', 1, 'Female:25,Male:0,PWD:0,Pregnant:0,Children:0,Senior:0', '2025-07-24 14:16:15', '2025-07-25 06:34:08'),
(3, 'Club 500 (Available for returning customers only)', 0, '', 'Privilege Card now Available', '2024-12-01', '2025-07-31', 0, 'Female:47.36842105263158,Male:47.36842105263158,PWD:47.36842105263158,Pregnant:47.36842105263158,Children:47.36842105263158,Senior:47.36842105263158', '2025-07-24 14:16:15', '2025-07-25 07:39:50');

-- --------------------------------------------------------

--
-- Table structure for table `subscriptions`
--

CREATE TABLE `subscriptions` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` varchar(255) NOT NULL,
  `pax` int(11) DEFAULT 1,
  `capacity` int(11) NOT NULL DEFAULT 0,
  `type` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `subscriptions`
--

INSERT INTO `subscriptions` (`id`, `name`, `description`, `price`, `pax`, `capacity`, `type`, `created_at`, `updated_at`) VALUES
(1, 'Alhambra Kingdom Ballroom & Chapel  ', 'A versatile and spacious events hall that can accommodate up to 300 guests.  Ideal for various celebrations including:  Children?s birthdays  Debuts  Engagement parties  Weddings  Features large cathedral windows offering views of a scenic landscape. Promoted as a part of Mother?s Wonderland.  Contact Information Telephone: 042-373-3504 Mobile: 0949-879-4919  ', 'Female:300,Male:300,PWD:200,Pregnant:200,Children:150,Senior:200', 1, 300, '1', '2025-07-23 03:15:35', '2025-07-25 07:33:34'),
(4, 'Day Pass', 'This ticket grants access to the event or venue for one full day only, allowing guests to enjoy all scheduled activities, attractions, and amenities during regular operating hours on the selected date. Ideal for those who wish to make the most out of a single day, this ticket offers a convenient and affordable way to experience everything the event has to offer without the commitment of a multi-day pass. Please note that re-entry policies may vary and overnight stays are not included.', 'Female:950,Male:950,PWD:950,Pregnant:950,Children:950,Senior:950', 1, 0, '0', '2025-07-24 13:32:10', '2025-07-25 07:33:15'),
(5, 'Premium Membership', 'Enjoy the benefits of continuous and unrestricted access with our Permanent Free Entrance per Month offering. This pass grants eligible individuals free entry to the venue or event once every month, allowing them to take part in activities, programs, or services without any admission charge during their visit. It is ideal for regular guests, beneficiaries, or community members who are entitled to consistent, complimentary access.', 'Female:27000,Male:27000,PWD:25000,Pregnant:25000,Children:20000,Senior:25000', 1, 0, '2', '2025-07-24 13:35:29', '2025-07-25 07:36:55');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `unique_id` varchar(100) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) DEFAULT NULL,
  `subscription` varchar(100) DEFAULT NULL,
  `reservation_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `payment_info` text DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT 0,
  `guest_names` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `unique_id`, `user_id`, `type`, `subscription`, `reservation_date`, `end_date`, `payment_info`, `status`, `guest_names`, `created_at`, `updated_at`) VALUES
(1, '54EC3BD982', 2, '0', '4', '2025-07-28 01:28:00', '2025-07-28 01:28:00', 'Amount:36575.00,Payment:Gcash,Proof:proof_688661cf75ff93.39541626.png,Female:2:950:25:1425,Male:5:950:0:4750,PWD:1:950:0:950,Pregnant:6:950:0:5700,Children:10:950:0:9500,Senior:15:950:0:14250', 3, '', '2025-07-27 17:28:47', '2025-09-11 14:14:24'),
(2, '187543B61E', 3, '0', '4', '2025-08-21 17:43:00', '2025-08-21 21:43:00', 'Amount:712.50,Payment:Gcash,Proof:proof_68a6ea4072a354.56589997.png,Female:1:950:25:712.5,Male:0:950:0:0,PWD:0:950:0:0,Pregnant:0:950:0:0,Children:0:950:0:0,Senior:0:950:0:0', 3, '', '2025-08-21 09:43:29', '2025-09-11 14:24:51'),
(3, '73CA7FF067', 3, '0', '4', '2025-09-26 23:19:00', '1970-01-01 00:00:00', 'Amount:712.50,Payment:Gcash,Proof:proof_68c2e88a9550a6.74986921.jpg,Female:1:950:25:712.5,Male:0:950:0:0,PWD:0:950:0:0,Pregnant:0:950:0:0,Children:0:950:0:0,Senior:0:950:0:0', 3, '[\"Sean\"]', '2025-09-11 15:19:38', '2025-09-27 15:31:15'),
(4, '03CAD0F990', 3, '0', '4', '2025-09-01 23:22:00', '1970-01-01 00:00:00', 'Amount:950.00,Payment:Gcash,Proof:proof_68c2e94c51b379.52400818.jpg,Female:0:950:25:0,Male:0:950:0:0,PWD:0:950:0:0,Pregnant:0:950:0:0,Children:0:950:0:0,Senior:1:950:0:950', 3, '[\"Sean charles pugosa\",\"scvp\"]', '2025-09-11 15:22:52', '2025-09-27 15:31:15'),
(5, 'F28FA4712A', 3, '0', '4', '2025-10-01 23:21:00', '2025-10-02 23:21:00', 'Amount:712.50,Payment:Gcash,Proof:proof_68dd46bba5f705.55315389.jpg,Female:1:950:25:712.5,Male:0:950:0:0,PWD:0:950:0:0,Pregnant:0:950:0:0,Children:0:950:0:0,Senior:0:950:0:0', 0, '[\"Sean\"]', '2025-10-01 15:20:27', '2025-10-01 15:30:04');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `type` tinyint(4) NOT NULL DEFAULT 0,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `number` varchar(20) DEFAULT NULL,
  `gender` varchar(255) DEFAULT NULL,
  `profile` varchar(255) DEFAULT NULL,
  `address` varchar(100) DEFAULT NULL,
  `birthdate` date DEFAULT NULL,
  `id_card` varchar(255) NOT NULL,
  `first_time` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `type`, `email`, `password`, `number`, `gender`, `profile`, `address`, `birthdate`, `id_card`, `first_time`, `created_at`, `updated_at`) VALUES
(1, 'Admin Account', 1, 'admin@gmail.com', '$2y$10$wXGBw77nFoX1xNb00uauuOXWUC3n0MMNUvUQaxudeTl/5j1hZ/iUy', '6395331809252', 'Male', 'profile_68c2d87c0a75c8.73353482.jpg', '45 Session Road, Barangay Kabayanihan, Baguio City, Benguet, 2600, Philippines\n', '2004-07-12', '', 1, '2025-07-21 04:45:05', '2025-10-02 03:19:34'),
(2, 'User', 0, 'user@gmail.com', '$2a$12$.IBIMWLVhU04uwmm/NCVX.DtRVHZuP4EMO1H6Qv36uTbSoGEn5ROi', '639533180925', 'Male', 'avatar-6.jpg', '123 Rizal Avenue, Pasay City, Metro Manila, 1300, Philippines', '2004-07-07', '', 0, '2025-08-12 04:45:05', '2025-10-02 03:18:26'),
(3, 'Sean', 0, 'seancvpugosa@gmail.com', '$2y$10$Bd55VJOqyiHb5c.n8viWL..paZ0RLJxWi1am4trBHKx7aO3gSjQrC', '639533180925', NULL, 'profile_68c2deafde00f3.77694064.png', '12 Colon Street, Barangay Kalubihan, Cebu City, Cebu, 6000, Philippines\n', NULL, 'idcard_68c2e644ae0e65.54765448.jpg', 1, '2025-09-05 05:05:06', '2025-10-02 03:19:38');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `ar`
--
ALTER TABLE `ar`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `news`
--
ALTER TABLE `news`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_id` (`unique_id`);

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
-- AUTO_INCREMENT for table `ar`
--
ALTER TABLE `ar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `news`
--
ALTER TABLE `news`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `subscriptions`
--
ALTER TABLE `subscriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
