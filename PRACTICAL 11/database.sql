-- =====================================================
-- StudentHub ITUE203 — PRACTICAL 11 : Student Module
-- Database: studenthub_p11 (PHP 8 + MySQL / XAMPP, root / empty)
-- Run: import this file via phpMyAdmin > Import, or:
--   mysql -u root < database.sql
-- =====================================================

CREATE DATABASE IF NOT EXISTS `studenthub_p11`
  CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `studenthub_p11`;

-- ---------------- Users (login carried over for audit) ----------------
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('admin','student') NOT NULL DEFAULT 'student',
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `last_login` DATETIME NULL,
  `remember_token_hash` VARCHAR(255) NULL,
  `remember_expires` DATETIME NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------- Students (core module of Practical 11) ----------------
CREATE TABLE IF NOT EXISTS `students` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `phone` VARCHAR(15) NOT NULL,
  `student_id` VARCHAR(20) NOT NULL UNIQUE,
  `course` VARCHAR(100) NOT NULL,
  `year` INT NOT NULL,
  `gender` ENUM('Male','Female','Other') NOT NULL,
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------- Events (minimal, for registrations FK) ----------------
CREATE TABLE IF NOT EXISTS `events` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(150) NOT NULL,
  `event_date` DATE NOT NULL,
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------- Registrations (student <-> event) ----------------
CREATE TABLE IF NOT EXISTS `registrations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT NOT NULL,
  `event_id` INT NOT NULL,
  `registered_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_student_event` (`student_id`, `event_id`),
  CONSTRAINT `fk_reg_student` FOREIGN KEY (`student_id`)
    REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_reg_event` FOREIGN KEY (`event_id`)
    REFERENCES `events` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------- Audit logs ----------------
CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NULL,
  `action` VARCHAR(100) NOT NULL,
  `details` TEXT NULL,
  `ip_address` VARCHAR(45) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------- Seed users (EXACT credentials) ----------------
-- admin / Admin@123 | student / Student@123
INSERT INTO `users` (`username`, `email`, `password_hash`, `role`, `status`) VALUES
('admin', 'admin@studenthub.local', '$2b$10$IxL73cChhqbhMu8GgUBcQOw97GGxgivtR9dx0qAs6W4vsJ110Bg0e', 'admin', 'active'),
('student', 'student@studenthub.local', '$2b$10$g57ksW2ZDcxFPn9wV7GqbenmxjZ3MSXTRmo29G6vs03cq7Levmtyy', 'student', 'active')
ON DUPLICATE KEY UPDATE `password_hash` = VALUES(`password_hash`);

-- ---------------- Seed events (minimal demo data) ----------------
INSERT INTO `events` (`title`, `event_date`, `status`) VALUES
('Tech Fest 2026', '2026-02-15', 'active'),
('Workshop on PHP & MySQL', '2026-03-10', 'active')
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`);

-- ---------------- Seed AT LEAST 20 students (STU001-STU020) ----------------
-- NOTE: STU001-STU019 active, STU020 inactive (to demo soft delete / filter).
INSERT INTO `students` (`full_name`, `email`, `phone`, `student_id`, `course`, `year`, `gender`, `status`) VALUES
('Aarav Sharma', 'aarav.sharma@example.com', '9876543210', 'STU001', 'Computer Engineering', 2024, 'Male', 'active'),
('Diya Patel', 'diya.patel@example.com', '9876543211', 'STU002', 'Information Technology', 2023, 'Female', 'active'),
('Rohan Verma', 'rohan.verma@example.com', '9876543212', 'STU003', 'Electronics Engineering', 2025, 'Male', 'active'),
('Sneha Iyer', 'sneha.iyer@example.com', '9876543213', 'STU004', 'Mechanical Engineering', 2024, 'Female', 'active'),
('Arjun Mehta', 'arjun.mehta@example.com', '9876543214', 'STU005', 'Civil Engineering', 2023, 'Male', 'active'),
('Priya Nair', 'priya.nair@example.com', '9876543215', 'STU006', 'Computer Engineering', 2025, 'Female', 'active'),
('Kabir Singh', 'kabir.singh@example.com', '9876543216', 'STU007', 'Information Technology', 2026, 'Male', 'active'),
('Ananya Gupta', 'ananya.gupta@example.com', '9876543217', 'STU008', 'Electronics Engineering', 2024, 'Female', 'active'),
('Vikram Reddy', 'vikram.reddy@example.com', '9876543218', 'STU009', 'Mechanical Engineering', 2025, 'Male', 'active'),
('Ishita Desai', 'ishita.desai@example.com', '9876543219', 'STU010', 'Civil Engineering', 2026, 'Female', 'active'),
('Aditya Kumar', 'aditya.kumar@example.com', '9876543220', 'STU011', 'Computer Engineering', 2023, 'Male', 'active'),
('Kavya Menon', 'kavya.menon@example.com', '9876543221', 'STU012', 'Information Technology', 2024, 'Female', 'active'),
('Nikhil Joshi', 'nikhil.joshi@example.com', '9876543222', 'STU013', 'Electronics Engineering', 2026, 'Male', 'active'),
('Riya Kapoor', 'riya.kapoor@example.com', '9876543223', 'STU014', 'Mechanical Engineering', 2023, 'Female', 'active'),
('Manav Shah', 'manav.shah@example.com', '9876543224', 'STU015', 'Civil Engineering', 2025, 'Male', 'active'),
('Pooja Bhatt', 'pooja.bhatt@example.com', '9876543225', 'STU016', 'Computer Engineering', 2026, 'Female', 'active'),
('Rahul Yadav', 'rahul.yadav@example.com', '9876543226', 'STU017', 'Information Technology', 2025, 'Male', 'active'),
('Tanvi Kulkarni', 'tanvi.kulkarni@example.com', '9876543227', 'STU018', 'Electronics Engineering', 2023, 'Female', 'active'),
('Karan Ahuja', 'karan.ahuja@example.com', '9876543228', 'STU019', 'Mechanical Engineering', 2026, 'Male', 'active'),
('Neha Singhania', 'neha.singhania@example.com', '9876543229', 'STU020', 'Civil Engineering', 2024, 'Female', 'inactive')
ON DUPLICATE KEY UPDATE `full_name` = VALUES(`full_name`);
