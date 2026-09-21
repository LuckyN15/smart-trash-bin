-- phpMyAdmin SQL Dump
-- version 6.0.0-dev+20260623.a3d7ba6948
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jul 04, 2026 at 10:36 AM
-- Server version: 8.4.3
-- PHP Version: 8.4.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `smart_iot`
--

-- --------------------------------------------------------

--
-- Table structure for table `bins`
--

CREATE TABLE `bins` (
  `id` bigint UNSIGNED NOT NULL,
  `device_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `location` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `area` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `camera_ip` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `battery_level` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `status` enum('online','offline') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'online',
  `pending_servo_command` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `last_reported_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `bins`
--

INSERT INTO `bins` (`id`, `device_id`, `name`, `location`, `area`, `camera_ip`, `battery_level`, `status`, `pending_servo_command`, `last_reported_at`, `created_at`, `updated_at`) VALUES
(1, 'BIN-03', 'Bina Insani University', 'lantai 5', 'Kampus', NULL, 85, 'online', 0, '2026-06-24 04:20:59', '2026-06-19 06:02:30', '2026-07-03 03:02:11'),
(2, 'BIN-07', 'Taman Monumen', 'Taman Monumen', 'Taman', NULL, 43, 'online', 0, '2026-06-19 06:02:30', '2026-06-19 06:02:30', '2026-07-03 03:02:11'),
(3, 'BIN-12', 'Jl. Sudirman', 'Jl. Sudirman 45', 'Komersial', NULL, 77, 'online', 0, '2026-06-19 06:02:30', '2026-06-19 06:02:30', '2026-07-03 03:02:11'),
(4, 'BIN-18', 'Kantor Walikota', 'Kantor Walikota', 'Pemerintah', NULL, 93, 'online', 0, '2026-06-19 06:02:30', '2026-06-19 06:02:30', '2026-07-03 03:02:11'),
(5, 'BIN-22', 'Mall Raya', 'Mall Raya Gatot', 'Komersial', NULL, 35, 'online', 0, '2026-06-19 06:02:30', '2026-06-19 06:02:30', '2026-07-03 03:02:11'),
(6, 'BIN-09', 'Taman Kota', 'Taman Kota Barat', 'Taman', NULL, 24, 'online', 0, '2026-06-19 06:02:30', '2026-06-19 06:02:30', '2026-07-03 03:02:11');

-- --------------------------------------------------------

--
-- Table structure for table `bin_compartments`
--

CREATE TABLE `bin_compartments` (
  `id` bigint UNSIGNED NOT NULL,
  `bin_id` bigint UNSIGNED NOT NULL,
  `category` enum('organik','anorganik','b3') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'organik',
  `capacity_percent` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `status` enum('empty','ok','near','full') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ok',
  `last_updated_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `bin_compartments`
--

INSERT INTO `bin_compartments` (`id`, `bin_id`, `category`, `capacity_percent`, `status`, `last_updated_at`, `created_at`, `updated_at`) VALUES
(1, 1, 'organik', 0, 'empty', '2026-07-03 03:02:11', '2026-06-19 06:02:30', '2026-07-03 03:02:11'),
(2, 1, 'anorganik', 0, 'empty', '2026-07-03 03:02:11', '2026-06-19 06:02:30', '2026-07-03 03:02:11'),
(3, 1, 'b3', 0, 'empty', '2026-07-03 03:02:11', '2026-06-19 06:02:30', '2026-07-03 03:02:11'),
(4, 2, 'organik', 0, 'empty', '2026-07-03 03:02:11', '2026-06-19 06:02:30', '2026-07-03 03:02:11'),
(5, 2, 'anorganik', 0, 'empty', '2026-07-03 03:02:11', '2026-06-19 06:02:30', '2026-07-03 03:02:11'),
(6, 2, 'b3', 0, 'empty', '2026-07-03 03:02:11', '2026-06-19 06:02:30', '2026-07-03 03:02:11'),
(7, 3, 'organik', 0, 'empty', '2026-07-03 03:02:11', '2026-06-19 06:02:30', '2026-07-03 03:02:11'),
(8, 3, 'anorganik', 0, 'empty', '2026-07-03 03:02:11', '2026-06-19 06:02:30', '2026-07-03 03:02:11'),
(9, 3, 'b3', 0, 'empty', '2026-07-03 03:02:11', '2026-06-19 06:02:30', '2026-07-03 03:02:11'),
(10, 4, 'organik', 0, 'empty', '2026-07-03 03:02:11', '2026-06-19 06:02:30', '2026-07-03 03:02:11'),
(11, 4, 'anorganik', 0, 'empty', '2026-07-03 03:02:11', '2026-06-19 06:02:30', '2026-07-03 03:02:11'),
(12, 4, 'b3', 0, 'empty', '2026-07-03 03:02:11', '2026-06-19 06:02:30', '2026-07-03 03:02:11'),
(13, 5, 'organik', 0, 'empty', '2026-07-03 03:02:11', '2026-06-19 06:02:30', '2026-07-03 03:02:11'),
(14, 5, 'anorganik', 0, 'empty', '2026-07-03 03:02:11', '2026-06-19 06:02:30', '2026-07-03 03:02:11'),
(15, 5, 'b3', 0, 'empty', '2026-07-03 03:02:11', '2026-06-19 06:02:30', '2026-07-03 03:02:11'),
(16, 6, 'organik', 0, 'empty', '2026-07-03 03:02:11', '2026-06-19 06:02:30', '2026-07-03 03:02:11'),
(17, 6, 'anorganik', 0, 'empty', '2026-07-03 03:02:11', '2026-06-19 06:02:30', '2026-07-03 03:02:11'),
(18, 6, 'b3', 0, 'empty', '2026-07-03 03:02:11', '2026-06-19 06:02:30', '2026-07-03 03:02:11');

-- --------------------------------------------------------

--
-- Table structure for table `bin_notifications`
--

CREATE TABLE `bin_notifications` (
  `id` bigint UNSIGNED NOT NULL,
  `bin_id` bigint UNSIGNED DEFAULT NULL,
  `type` enum('bin_full','offline','low_battery','classification_failed','setting_change','device_alert','custom') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'custom',
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `level` enum('info','warning','danger','success') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'info',
  `status` enum('unread','read') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unread',
  `occurred_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cache`
--

INSERT INTO `cache` (`key`, `value`, `expiration`) VALUES
('laravel-cache-admin@smartbin.io|127.0.0.1', 'i:3;', 1782290901),
('laravel-cache-admin@smartbin.io|127.0.0.1:timer', 'i:1782290901;', 1782290901),
('laravel-cache-admin1@gmail.com|127.0.0.1', 'i:1;', 1781874597),
('laravel-cache-admin1@gmail.com|127.0.0.1:timer', 'i:1781874597;', 1781874597),
('laravel-cache-admin123@gmail.com|127.0.0.1', 'i:1;', 1781874590),
('laravel-cache-admin123@gmail.com|127.0.0.1:timer', 'i:1781874590;', 1781874590);

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `classifications`
--

CREATE TABLE `classifications` (
  `id` bigint UNSIGNED NOT NULL,
  `bin_compartment_id` bigint UNSIGNED NOT NULL,
  `category` enum('organik','anorganik','b3') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'organik',
  `detected_label` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `confidence` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `status` enum('success','failed') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'success',
  `model_version` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `detected_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint UNSIGNED NOT NULL,
  `uuid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint UNSIGNED NOT NULL,
  `queue` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` smallint UNSIGNED NOT NULL,
  `reserved_at` int UNSIGNED DEFAULT NULL,
  `available_at` int UNSIGNED NOT NULL,
  `created_at` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int UNSIGNED NOT NULL,
  `migration` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '0001_01_01_000003_add_role_to_users_table', 1),
(5, '0001_01_01_000004_create_bins_table', 1),
(6, '0001_01_01_000005_create_classifications_table', 1),
(7, '0001_01_01_000006_create_bin_notifications_table', 1),
(8, '0001_01_01_000007_create_trips_table', 1),
(9, '2026_06_21_000000_add_detected_label_to_classifications_table', 2),
(10, '2026_06_21_045849_change_model_version_in_classifications_table', 3),
(11, '2026_06_24_000000_add_pending_servo_command_to_bins_table', 4),
(12, '2026_07_03_000000_create_settings_table', 5),
(13, '2026_07_03_000001_add_camera_ip_to_bins_table', 6);

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` bigint UNSIGNED NOT NULL,
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `key`, `value`, `created_at`, `updated_at`) VALUES
(1, 'max_items_organik', '45', '2026-07-03 03:12:41', '2026-07-03 03:12:41'),
(2, 'max_items_anorganik', '50', '2026-07-03 03:12:41', '2026-07-03 03:12:41'),
(3, 'max_items_b3', '50', '2026-07-03 03:12:41', '2026-07-03 03:12:41'),
(4, 'camera_rtsp_url', '', '2026-07-03 03:42:05', '2026-07-03 03:42:25');

-- --------------------------------------------------------

--
-- Table structure for table `trips`
--

CREATE TABLE `trips` (
  `id` bigint UNSIGNED NOT NULL,
  `driver_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `trip_date` date DEFAULT NULL,
  `bins_served` smallint UNSIGNED NOT NULL DEFAULT '0',
  `weight_kg` decimal(8,2) UNSIGNED NOT NULL DEFAULT '0.00',
  `duration_minutes` smallint UNSIGNED DEFAULT NULL,
  `on_time` tinyint(1) NOT NULL DEFAULT '1',
  `completed` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('admin','user') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'user',
  `remember_token` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `role`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'Admin Utama', 'admin@smartbin.io', '2026-06-19 06:02:29', '$2y$12$h4QZfdSq9UKf1IaFoXeEI.PfvyztA9rTgS3vBUY8uteoi5xffhTA.', 'admin', 'nMxGeWvxxum7hGfe6pqDg3e46Kk1qIG0oH3n64TiRnB4a4uLM5hsxvhBvHTg', '2026-06-19 06:02:30', '2026-06-19 06:02:30'),
(2, 'Test User', 'test@example.com', '2026-06-19 06:02:30', '$2y$12$h4QZfdSq9UKf1IaFoXeEI.PfvyztA9rTgS3vBUY8uteoi5xffhTA.', 'user', 'Ji2NA1dTmJ', '2026-06-19 06:02:30', '2026-06-19 06:02:30'),
(3, 'admin1', 'admins@gmail.com', NULL, '$2y$12$5Ow9Q2OyoKBo/4HyB2.c2uiPXIB4AA0s5wJkKzBIYVyEhk6yByKva', 'user', NULL, '2026-06-19 06:06:38', '2026-06-19 06:06:38'),
(4, 'Admin 2', 'Admin@gmail.com', '2026-06-24 08:42:39', '$2y$12$TixMOyfkZg5N75pTI8IIfOHrHtV6SFI8U1wqtaD3R4T/.GIJlpsda', 'admin', 'HINsKAQctbU7jGaXFkZnpbvyJKSvEZhZWKLsenVPNfJ6qFUzXVvcAANoPmZk', NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bins`
--
ALTER TABLE `bins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `bins_device_id_unique` (`device_id`);

--
-- Indexes for table `bin_compartments`
--
ALTER TABLE `bin_compartments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `bin_compartments_bin_id_category_unique` (`bin_id`,`category`);

--
-- Indexes for table `bin_notifications`
--
ALTER TABLE `bin_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bin_notifications_bin_id_foreign` (`bin_id`);

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_expiration_index` (`expiration`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_locks_expiration_index` (`expiration`);

--
-- Indexes for table `classifications`
--
ALTER TABLE `classifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `classifications_bin_compartment_id_foreign` (`bin_compartment_id`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
  ADD KEY `failed_jobs_connection_queue_failed_at_index` (`connection`,`queue`,`failed_at`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `settings_key_unique` (`key`);

--
-- Indexes for table `trips`
--
ALTER TABLE `trips`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bins`
--
ALTER TABLE `bins`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `bin_compartments`
--
ALTER TABLE `bin_compartments`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `bin_notifications`
--
ALTER TABLE `bin_notifications`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `classifications`
--
ALTER TABLE `classifications`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `trips`
--
ALTER TABLE `trips`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bin_compartments`
--
ALTER TABLE `bin_compartments`
  ADD CONSTRAINT `bin_compartments_bin_id_foreign` FOREIGN KEY (`bin_id`) REFERENCES `bins` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `bin_notifications`
--
ALTER TABLE `bin_notifications`
  ADD CONSTRAINT `bin_notifications_bin_id_foreign` FOREIGN KEY (`bin_id`) REFERENCES `bins` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `classifications`
--
ALTER TABLE `classifications`
  ADD CONSTRAINT `classifications_bin_compartment_id_foreign` FOREIGN KEY (`bin_compartment_id`) REFERENCES `bin_compartments` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
