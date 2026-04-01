-- ============================================
-- Design Task Manager — Database Schema
-- ============================================
-- Run this SQL in your MySQL database on Hostinger.
-- Make sure the database name matches your config/database.php

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- ============================================
-- Users Table
-- ============================================
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `full_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('manager', 'designer') NOT NULL DEFAULT 'designer',
    `language_preference` ENUM('en', 'ar') NOT NULL DEFAULT 'en',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tasks Table
-- ============================================
CREATE TABLE IF NOT EXISTS `tasks` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title` VARCHAR(255) NOT NULL,
    `client_name` VARCHAR(150) NOT NULL,
    `design_type` ENUM('file', 'logo', 'design', 'motion_design', 'profile') NOT NULL,
    `notes` TEXT NULL,
    `deadline` DATE NULL,
    `assigned_to` INT UNSIGNED NOT NULL,
    `progress_percentage` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `status` ENUM('new', 'in_progress', 'delivered') NOT NULL DEFAULT 'new',
    `created_by` INT UNSIGNED NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_assigned_to` (`assigned_to`),
    KEY `idx_status` (`status`),
    KEY `idx_design_type` (`design_type`),
    KEY `idx_deadline` (`deadline`),
    KEY `idx_created_by` (`created_by`),
    CONSTRAINT `fk_tasks_assigned_to` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_tasks_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Task Status Logs Table
-- ============================================
CREATE TABLE IF NOT EXISTS `task_status_logs` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `task_id` INT UNSIGNED NOT NULL,
    `old_status` VARCHAR(20) NULL,
    `new_status` VARCHAR(20) NOT NULL,
    `changed_by` INT UNSIGNED NOT NULL,
    `changed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_task_id` (`task_id`),
    KEY `idx_changed_by` (`changed_by`),
    CONSTRAINT `fk_logs_task_id` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_logs_changed_by` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Seed Data
-- ============================================

-- IMPORTANT: After importing this file, run hash_passwords.php from CLI or
-- temporarily visit /setup_passwords.php to generate proper bcrypt hashes.
-- Default credentials:
--   Manager: admin@design.com / Admin@123
--   Designer: sara@design.com / Designer@123
--   Designer: omar@design.com / Designer@123

-- Manager account
INSERT INTO `users` (`full_name`, `email`, `password`, `role`, `language_preference`) VALUES
('Ahmed Al-Manager', 'admin@design.com', 'NEEDS_HASH_Admin@123', 'manager', 'ar');

-- Designer accounts
INSERT INTO `users` (`full_name`, `email`, `password`, `role`, `language_preference`) VALUES
('Sara Designer', 'sara@design.com', 'NEEDS_HASH_Designer@123', 'designer', 'ar'),
('Omar Creative', 'omar@design.com', 'NEEDS_HASH_Designer@123', 'designer', 'en');

-- Sample tasks
INSERT INTO `tasks` (`title`, `client_name`, `design_type`, `notes`, `deadline`, `assigned_to`, `progress_percentage`, `status`, `created_by`) VALUES
('Company Logo Redesign', 'ABC Corporation', 'logo', 'Modern minimal logo with blue color scheme', DATE_ADD(CURDATE(), INTERVAL 5 DAY), 2, 50, 'in_progress', 1),
('Marketing Brochure', 'XYZ Ltd', 'file', 'Tri-fold brochure for product launch', DATE_ADD(CURDATE(), INTERVAL 3 DAY), 2, 25, 'in_progress', 1),
('Social Media Kit', 'Tech Startup', 'design', 'Instagram and Twitter post templates', DATE_ADD(CURDATE(), INTERVAL 7 DAY), 3, 0, 'new', 1),
('Product Animation', 'E-Commerce Store', 'motion_design', '30 second product showcase animation', DATE_ADD(CURDATE(), INTERVAL 10 DAY), 3, 75, 'in_progress', 1),
('Company Profile', 'National Bank', 'profile', 'Full company profile document, 20 pages', DATE_ADD(CURDATE(), INTERVAL -1 DAY), 2, 100, 'delivered', 1),
('Event Banner', 'Conference Center', 'design', 'Large format banner for annual event', DATE_ADD(CURDATE(), INTERVAL 2 DAY), 3, 0, 'new', 1);
