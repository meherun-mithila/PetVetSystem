-- =====================================================
-- PetVet Veterinary Clinic System - Complete Database
-- =====================================================
-- This file contains all necessary tables, updates, and data
-- for the PetVet veterinary clinic management system
-- =====================================================

-- Set character set and collation
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- CORE TABLES
-- =====================================================

-- Users table (with all updates)
CREATE TABLE IF NOT EXISTS `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `user_type` enum('user','admin') DEFAULT 'user',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `email` (`email`),
  KEY `user_type_idx` (`user_type`),
  KEY `is_verified_idx` (`is_verified`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Admin table
CREATE TABLE IF NOT EXISTS `admin` (
  `admin_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `role` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`admin_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Staff table
CREATE TABLE IF NOT EXISTS `staff` (
  `staff_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `extra_info` text DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`staff_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Doctors table
CREATE TABLE IF NOT EXISTS `doctors` (
  `doctor_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `specialization` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`doctor_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- PATIENT AND PET MANAGEMENT
-- =====================================================

-- Patients (Pets) table
CREATE TABLE IF NOT EXISTS `patients` (
  `patient_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `species` varchar(50) DEFAULT NULL,
  `breed` varchar(100) DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `weight` decimal(5,2) DEFAULT NULL,
  `owner_id` int(11) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`patient_id`),
  KEY `owner_id` (`owner_id`),
  CONSTRAINT `fk_patients_owner` FOREIGN KEY (`owner_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- APPOINTMENTS AND SERVICES
-- =====================================================

-- Appointments table
CREATE TABLE IF NOT EXISTS `appointments` (
  `appointment_id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_id` int(11) DEFAULT NULL,
  `doctor_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `appointment_date` date DEFAULT NULL,
  `appointment_time` time DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('scheduled','completed','cancelled','no_show') DEFAULT 'scheduled',
  `notes` text DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`appointment_id`),
  KEY `patient_id` (`patient_id`),
  KEY `doctor_id` (`doctor_id`),
  KEY `user_id` (`user_id`),
  KEY `appointment_date` (`appointment_date`),
  KEY `status` (`status`),
  CONSTRAINT `fk_appointments_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_appointments_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`doctor_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_appointments_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Services table
CREATE TABLE IF NOT EXISTS `services` (
  `service_id` int(11) NOT NULL AUTO_INCREMENT,
  `service_name` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`service_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Appointment Services (many-to-many relationship)
CREATE TABLE IF NOT EXISTS `appointmentservices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `appointment_id` int(11) DEFAULT NULL,
  `service_id` int(11) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `appointment_id` (`appointment_id`),
  KEY `service_id` (`service_id`),
  CONSTRAINT `fk_appointmentservices_appointment` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`appointment_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_appointmentservices_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`service_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- MEDICAL RECORDS
-- =====================================================

-- Medical Records table
CREATE TABLE IF NOT EXISTS `medicalrecords` (
  `record_id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_id` int(11) DEFAULT NULL,
  `doctor_id` int(11) DEFAULT NULL,
  `diagnosis` text DEFAULT NULL,
  `treatment` text DEFAULT NULL,
  `prescription` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `record_date` date DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`record_id`),
  KEY `patient_id` (`patient_id`),
  KEY `doctor_id` (`doctor_id`),
  KEY `record_date` (`record_date`),
  CONSTRAINT `fk_medicalrecords_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_medicalrecords_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`doctor_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Vaccine Records table
CREATE TABLE IF NOT EXISTS `vaccinationrecords` (
  `vaccine_id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_id` int(11) DEFAULT NULL,
  `vaccine_name` varchar(100) DEFAULT NULL,
  `date_given` date DEFAULT NULL,
  `next_due_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`vaccine_id`),
  KEY `patient_id` (`patient_id`),
  KEY `date_given` (`date_given`),
  KEY `next_due_date` (`next_due_date`),
  CONSTRAINT `fk_vaccinationrecords_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- ADOPTION SYSTEM
-- =====================================================

-- Adoption Listings table
CREATE TABLE IF NOT EXISTS `adoptionlistings` (
  `listing_id` int(11) NOT NULL AUTO_INCREMENT,
  `pet_name` varchar(100) DEFAULT NULL,
  `species` varchar(50) DEFAULT NULL,
  `breed` varchar(100) DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `posted_by` int(11) DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `status` enum('pending','approved','adopted','rejected') DEFAULT 'pending',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`listing_id`),
  KEY `posted_by` (`posted_by`),
  KEY `approved_by` (`approved_by`),
  KEY `status` (`status`),
  CONSTRAINT `fk_adoptionlistings_posted_by` FOREIGN KEY (`posted_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_adoptionlistings_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `admin` (`admin_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Adoption Requests table
CREATE TABLE IF NOT EXISTS `adoptionrequests` (
  `request_id` int(11) NOT NULL AUTO_INCREMENT,
  `listing_id` int(11) DEFAULT NULL,
  `requested_by` int(11) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`request_id`),
  KEY `listing_id` (`listing_id`),
  KEY `requested_by` (`requested_by`),
  KEY `status` (`status`),
  CONSTRAINT `fk_adoptionrequests_listing` FOREIGN KEY (`listing_id`) REFERENCES `adoptionlistings` (`listing_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_adoptionrequests_user` FOREIGN KEY (`requested_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- ARTICLES SYSTEM
-- =====================================================

-- Articles table
CREATE TABLE IF NOT EXISTS `articles` (
  `article_id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `summary` text,
  `content` longtext NOT NULL,
  `topic` varchar(100),
  `tags` text,
  `author_id` int(11) NOT NULL,
  `author_type` enum('admin','doctor') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `view_count` int(11) NOT NULL DEFAULT 0,
  `status` enum('published','draft','archived') NOT NULL DEFAULT 'published',
  PRIMARY KEY (`article_id`),
  KEY `author_idx` (`author_type`, `author_id`),
  KEY `topic_idx` (`topic`),
  KEY `status_idx` (`status`),
  KEY `created_at_idx` (`created_at`),
  KEY `view_count_idx` (`view_count`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Article Comments table
CREATE TABLE IF NOT EXISTS `article_comments` (
  `comment_id` int(11) NOT NULL AUTO_INCREMENT,
  `article_id` int(11) NOT NULL,
  `commenter_id` int(11) NOT NULL,
  `commenter_type` enum('admin','doctor','user','staff') NOT NULL,
  `commenter_name` varchar(255) NOT NULL,
  `comment_text` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('active','hidden','deleted') NOT NULL DEFAULT 'active',
  PRIMARY KEY (`comment_id`),
  KEY `article_idx` (`article_id`),
  KEY `commenter_idx` (`commenter_type`, `commenter_id`),
  KEY `created_at_idx` (`created_at`),
  KEY `status_idx` (`status`),
  CONSTRAINT `fk_article_comments_article` FOREIGN KEY (`article_id`) REFERENCES `articles` (`article_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- NOTIFICATIONS SYSTEM
-- =====================================================

-- Notifications table
CREATE TABLE IF NOT EXISTS `notifications` (
  `notification_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `staff_id` int(11) DEFAULT NULL,
  `message` text NOT NULL,
  `type` varchar(50) DEFAULT 'general',
  `audience` enum('user','staff','admin','all','specific_user','specific_staff') DEFAULT 'user',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`notification_id`),
  KEY `user_id` (`user_id`),
  KEY `staff_id` (`staff_id`),
  KEY `audience` (`audience`),
  KEY `type` (`type`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notification Reads table
CREATE TABLE IF NOT EXISTS `notification_reads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `notification_id` int(11) NOT NULL,
  `reader_type` enum('user','staff','admin') NOT NULL,
  `reader_id` int(11) NOT NULL,
  `read_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_read` (`notification_id`, `reader_type`, `reader_id`),
  KEY `notification_id` (`notification_id`),
  KEY `reader_idx` (`reader_type`, `reader_id`),
  KEY `read_at` (`read_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- EMAIL VERIFICATION SYSTEM
-- =====================================================

-- Email OTPs table
CREATE TABLE IF NOT EXISTS `email_otps` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `otp_code` varchar(10) NOT NULL,
  `is_used` tinyint(1) DEFAULT 0,
  `attempts` int DEFAULT 0,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `expires_at` timestamp NULL,
  PRIMARY KEY (`id`),
  KEY `email_idx` (`email`),
  KEY `user_idx` (`user_id`),
  KEY `otp_idx` (`otp_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Email Verifications table (legacy support)
CREATE TABLE IF NOT EXISTS `email_verifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `verification_token` varchar(255) NOT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `expires_at` timestamp DEFAULT (CURRENT_TIMESTAMP + INTERVAL 24 HOUR),
  PRIMARY KEY (`id`),
  KEY `verification_token` (`verification_token`),
  KEY `email` (`email`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- INQUIRIES AND SUPPORT
-- =====================================================

-- Inquiries table
CREATE TABLE IF NOT EXISTS `inquiries` (
  `inquiry_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `status` enum('pending','in_progress','resolved','closed') DEFAULT 'pending',
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `assigned_to` int(11) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`inquiry_id`),
  KEY `user_id` (`user_id`),
  KEY `status` (`status`),
  KEY `priority` (`priority`),
  KEY `assigned_to` (`assigned_to`),
  CONSTRAINT `fk_inquiries_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_inquiries_staff` FOREIGN KEY (`assigned_to`) REFERENCES `staff` (`staff_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- SAMPLE DATA INSERTION
-- =====================================================

-- Insert sample admin users
INSERT IGNORE INTO `admin` (`admin_id`, `name`, `role`, `email`, `password`) VALUES
(1, 'Admin User', 'Administrator', 'admin@petvet.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
(2, 'Bilal Hossain', 'Assistant', 'bilal.hossain@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
(3, 'Chen Lee', 'Receptionist', 'chen.lee@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
(4, 'Deborah Sultana', 'Groomer', 'deborah.sultana@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
(5, 'Elias Kabir', 'Cleaner', 'elias.kabir@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Insert sample users
INSERT IGNORE INTO `users` (`user_id`, `name`, `email`, `phone`, `password`, `address`, `is_verified`, `user_type`) VALUES
(1, 'Arif Hossain', 'arif1@email.com', '01711000001', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dhaka', 1, 'user'),
(2, 'Tania Rahman', 'tania2@email.com', '01711000002', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Chittagong', 1, 'user'),
(3, 'Sajib Ahmed', 'sajib3@email.com', '01711000003', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sylhet', 1, 'user'),
(4, 'Nusrat Jahan', 'nusrat4@email.com', '01711000004', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Barisal', 1, 'user'),
(5, 'Rashed Karim', 'rashed5@email.com', '01711000005', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Rajshahi', 1, 'user'),
(6, 'Rima Akter', 'rima6@email.com', '01711000006', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Khulna', 1, 'user'),
(7, 'Imran Hossain', 'imran7@email.com', '01711000007', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Narayanganj', 1, 'user'),
(8, 'Sadia Binte', 'sadia8@email.com', '01711000008', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Comilla', 1, 'user'),
(9, 'Ovi Rahman', 'ovi9@email.com', '01711000009', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mymensingh', 1, 'user'),
(10, 'Jannat Nahar', 'jannat10@email.com', '01711000010', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Gazipur', 1, 'user');

-- Insert sample staff members
INSERT IGNORE INTO `staff` (`name`, `email`, `phone`, `role`, `password`, `extra_info`) VALUES
('John Smith', 'john.smith@petvet.com', '+1234567890', 'Manager', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Experienced veterinary practice manager'),
('Sarah Johnson', 'sarah.johnson@petvet.com', '+1234567891', 'Receptionist', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Friendly and organized receptionist'),
('Mike Wilson', 'mike.wilson@petvet.com', '+1234567892', 'Veterinary Technician', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Certified veterinary technician with 5 years experience');

-- Insert sample doctors
INSERT IGNORE INTO `doctors` (`name`, `specialization`, `email`, `phone`, `password`) VALUES
('Dr. Sarah Wilson', 'General Veterinary Medicine', 'dr.sarah@petvet.com', '+1234567893', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('Dr. Michael Chen', 'Surgery', 'dr.michael@petvet.com', '+1234567894', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('Dr. Emily Davis', 'Emergency Medicine', 'dr.emily@petvet.com', '+1234567895', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Insert sample services
INSERT IGNORE INTO `services` (`service_name`, `description`, `price`) VALUES
('General Checkup', 'Routine health examination and consultation.', 500.00),
('Vaccination', 'Core vaccinations for pets.', 800.00),
('Dental Cleaning', 'Professional dental cleaning and examination.', 1200.00),
('Spay/Neuter', 'Surgical sterilization procedure.', 2500.00),
('Emergency Care', 'Immediate treatment for critical cases.', 3000.00),
('X-Ray', 'Diagnostic imaging for internal examination.', 1500.00),
('Blood Test', 'Comprehensive blood work analysis.', 1000.00),
('Ultrasound', 'Ultrasonography for internal organs.', 1800.00),
('Surgery', 'Minor or major surgical procedures.', 5000.00),
('Grooming', 'Bathing, nail trimming, and fur care.', 800.00);

-- Insert sample patients (pets)
INSERT IGNORE INTO `patients` (`name`, `species`, `breed`, `age`, `weight`, `owner_id`) VALUES
('Buddy', 'Dog', 'Golden Retriever', 3, 25.50, 1),
('Whiskers', 'Cat', 'Persian', 2, 4.20, 2),
('Max', 'Dog', 'German Shepherd', 4, 30.00, 3),
('Luna', 'Cat', 'Siamese', 1, 3.80, 4),
('Rocky', 'Dog', 'Labrador', 5, 28.50, 5);

-- Insert sample articles
INSERT IGNORE INTO `articles` (`title`, `summary`, `content`, `topic`, `tags`, `author_id`, `author_type`, `status`) VALUES
(
    'Essential Pet Care Tips for New Pet Owners',
    'A comprehensive guide for first-time pet owners covering basic care, nutrition, and health maintenance.',
    '<h2>Welcome to Pet Parenthood!</h2><p>Congratulations on becoming a new pet owner! This guide will help you provide the best care for your furry friend.</p><h3>Basic Care Requirements</h3><ul><li>Regular feeding with appropriate food</li><li>Fresh water available at all times</li><li>Daily exercise and playtime</li><li>Regular grooming and hygiene</li><li>Veterinary check-ups</li></ul><h3>Nutrition Guidelines</h3><p>Choose high-quality pet food appropriate for your pet\'s age, size, and health needs. Avoid feeding human food that can be harmful.</p><h3>Exercise and Mental Stimulation</h3><p>Pets need both physical exercise and mental stimulation. Regular walks, play sessions, and interactive toys help keep them healthy and happy.</p>',
    'Pet Care Tips',
    'pet care, new owners, basic care, nutrition, exercise',
    1,
    'admin',
    'published'
),
(
    'Understanding Pet Vaccination Schedules',
    'Learn about the importance of vaccinations and recommended schedules for dogs and cats.',
    '<h2>Why Vaccinations Matter</h2><p>Vaccinations protect your pets from serious and potentially fatal diseases. They also help prevent the spread of diseases to other animals and humans.</p><h3>Core Vaccines for Dogs</h3><ul><li>Rabies - Required by law in most areas</li><li>Distemper - Protects against multiple diseases</li><li>Parvovirus - Highly contagious and deadly</li><li>Adenovirus - Protects against respiratory and liver disease</li></ul><h3>Core Vaccines for Cats</h3><ul><li>Rabies - Required by law in most areas</li><li>FVRCP - Protects against multiple respiratory and intestinal diseases</li><li>Feline Leukemia - Recommended for outdoor cats</li></ul><h3>Vaccination Schedule</h3><p>Puppies and kittens typically receive their first vaccines at 6-8 weeks of age, with boosters every 3-4 weeks until 16 weeks old. Adult pets need regular booster shots.</p>',
    'Vaccination',
    'vaccination, dogs, cats, health, prevention',
    1,
    'admin',
    'published'
);

-- Insert sample notifications
INSERT IGNORE INTO `notifications` (`message`, `type`, `audience`, `created_at`) VALUES
('Welcome to Caring Paws Veterinary Clinic! We\'re here to provide the best care for your pets.', 'general', 'all', NOW()),
('New vaccination schedule available. Please check your pet\'s records.', 'vaccine', 'user', NOW()),
('Staff meeting scheduled for Friday at 2 PM. All staff members are required to attend.', 'general', 'staff', NOW()),
('System maintenance scheduled for Sunday night. Some services may be temporarily unavailable.', 'general', 'all', NOW());

-- =====================================================
-- INDEXES AND OPTIMIZATIONS
-- =====================================================

-- Add additional indexes for better performance
ALTER TABLE `users` ADD INDEX IF NOT EXISTS `idx_email_verified` (`email`, `is_verified`);
ALTER TABLE `appointments` ADD INDEX IF NOT EXISTS `idx_date_time` (`appointment_date`, `appointment_time`);
ALTER TABLE `patients` ADD INDEX IF NOT EXISTS `idx_species_breed` (`species`, `breed`);
ALTER TABLE `articles` ADD INDEX IF NOT EXISTS `idx_search` (`title`, `summary`, `content`(100));
ALTER TABLE `articles` ADD INDEX IF NOT EXISTS `idx_author_topic` (`author_type`, `author_id`, `topic`);

-- Add fulltext search capability for articles
ALTER TABLE `articles` ADD FULLTEXT IF NOT EXISTS (`title`, `summary`, `content`);

-- =====================================================
-- VIEWS FOR COMMON QUERIES
-- =====================================================

-- View for appointment details with patient and owner info
CREATE OR REPLACE VIEW `appointment_details` AS
SELECT 
    a.appointment_id,
    a.appointment_date,
    a.appointment_time,
    a.reason,
    a.status,
    p.name as patient_name,
    p.species,
    p.breed,
    u.name as owner_name,
    u.email as owner_email,
    d.name as doctor_name
FROM appointments a
LEFT JOIN patients p ON a.patient_id = p.patient_id
LEFT JOIN users u ON a.user_id = u.user_id
LEFT JOIN doctors d ON a.doctor_id = d.doctor_id;

-- View for patient statistics
CREATE OR REPLACE VIEW `patient_stats` AS
SELECT 
    p.patient_id,
    p.name as patient_name,
    p.species,
    p.breed,
    u.name as owner_name,
    COUNT(a.appointment_id) as total_appointments,
    COUNT(mr.record_id) as total_medical_records,
    COUNT(vr.vaccine_id) as total_vaccines
FROM patients p
LEFT JOIN users u ON p.owner_id = u.user_id
LEFT JOIN appointments a ON p.patient_id = a.patient_id
LEFT JOIN medicalrecords mr ON p.patient_id = mr.patient_id
LEFT JOIN vaccinationrecords vr ON p.patient_id = vr.patient_id
GROUP BY p.patient_id;

-- =====================================================
-- TRIGGERS FOR DATA INTEGRITY
-- =====================================================

-- Trigger to update article view count
DELIMITER $$
CREATE TRIGGER IF NOT EXISTS `update_article_view_count`
AFTER UPDATE ON `articles`
FOR EACH ROW
BEGIN
    IF NEW.view_count != OLD.view_count THEN
        UPDATE articles SET updated_at = CURRENT_TIMESTAMP WHERE article_id = NEW.article_id;
    END IF;
END$$
DELIMITER ;

-- Trigger to ensure user_id uniqueness (prevents reuse)
DELIMITER $$
CREATE TRIGGER IF NOT EXISTS `ensure_next_user_id`
BEFORE INSERT ON `users`
FOR EACH ROW
BEGIN
    IF NEW.user_id IS NULL THEN
        SET NEW.user_id = (
            SELECT COALESCE(MAX(user_id) + 1, 1)
            FROM users
        );
    END IF;
END$$
DELIMITER ;

-- =====================================================
-- FINAL SETUP
-- =====================================================

-- Reset auto-increment values
ALTER TABLE `users` AUTO_INCREMENT = 31;
ALTER TABLE `admin` AUTO_INCREMENT = 6;
ALTER TABLE `staff` AUTO_INCREMENT = 4;
ALTER TABLE `doctors` AUTO_INCREMENT = 4;
ALTER TABLE `patients` AUTO_INCREMENT = 6;
ALTER TABLE `appointments` AUTO_INCREMENT = 1;
ALTER TABLE `services` AUTO_INCREMENT = 11;
ALTER TABLE `articles` AUTO_INCREMENT = 3;
ALTER TABLE `notifications` AUTO_INCREMENT = 5;

-- Enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- COMPLETION MESSAGE
-- =====================================================

SELECT 'PetVet Database Setup Complete!' as status;
SELECT 'All tables, data, and configurations have been successfully created.' as message;
SELECT 'You can now use the PetVet veterinary clinic management system.' as next_step;
