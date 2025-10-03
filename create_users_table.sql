-- SQL Query to create users table for Cryndol API
-- This creates the table with the correct structure for our API

CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `fullName` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phoneNumber` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `acceptTerms` tinyint(1) NOT NULL DEFAULT '0',
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_phonenumber_unique` (`phoneNumber`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Alternative query if you want to drop and recreate the existing table
-- WARNING: This will delete all existing data in the users table

-- DROP TABLE IF EXISTS `users`;
-- CREATE TABLE `users` (
--   `id` bigint unsigned NOT NULL AUTO_INCREMENT,
--   `fullName` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
--   `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
--   `phoneNumber` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
--   `email_verified_at` timestamp NULL DEFAULT NULL,
--   `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
--   `acceptTerms` tinyint(1) NOT NULL DEFAULT '0',
--   `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
--   `created_at` timestamp NULL DEFAULT NULL,
--   `updated_at` timestamp NULL DEFAULT NULL,
--   PRIMARY KEY (`id`),
--   UNIQUE KEY `users_email_unique` (`email`),
--   UNIQUE KEY `users_phonenumber_unique` (`phoneNumber`)
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
