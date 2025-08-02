-- Fix database schema issues for PetVet system
-- This script fixes the column name mismatches and adds missing columns

USE trial;

-- Fix appointments table column names
ALTER TABLE appointments 
CHANGE COLUMN `date` `appointment_date` date DEFAULT NULL,
CHANGE COLUMN `time` `appointment_time` time DEFAULT NULL;

-- Add missing columns to appointments table
ALTER TABLE appointments 
ADD COLUMN `reason` text DEFAULT NULL AFTER `status`;

-- Add missing columns to patients table
ALTER TABLE patients 
ADD COLUMN `created_at` timestamp DEFAULT CURRENT_TIMESTAMP AFTER `gender`,
ADD COLUMN `weight` decimal(5,2) DEFAULT NULL AFTER `age`,
ADD COLUMN `color` varchar(50) DEFAULT NULL AFTER `breed`;

-- Fix doctors table - rename contact to phone and add email column
ALTER TABLE doctors 
CHANGE COLUMN `contact` `phone` varchar(20) DEFAULT NULL,
ADD COLUMN `email` varchar(100) DEFAULT NULL AFTER `phone`;

-- Update doctors with email addresses
UPDATE doctors SET email = CONCAT(LOWER(REPLACE(name, ' ', '.')), '@petvet.com') WHERE email IS NULL;

-- Add some sample appointments data
INSERT INTO appointments (doctor_id, patient_id, appointment_date, appointment_time, status, reason) VALUES
(1, 1, CURDATE(), '09:00:00', 'Scheduled', 'Routine checkup'),
(2, 2, CURDATE(), '10:30:00', 'Scheduled', 'Vaccination'),
(3, 3, CURDATE(), '14:00:00', 'Completed', 'Dental cleaning'),
(4, 4, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '11:00:00', 'Scheduled', 'Surgery consultation'),
(5, 5, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '15:30:00', 'Scheduled', 'Emergency care'),
(6, 6, DATE_ADD(CURDATE(), INTERVAL -1 DAY), '08:00:00', 'Completed', 'X-Ray examination'),
(7, 7, DATE_ADD(CURDATE(), INTERVAL -1 DAY), '13:00:00', 'Completed', 'Ultrasound'),
(8, 8, DATE_ADD(CURDATE(), INTERVAL 2 DAY), '10:00:00', 'Scheduled', 'Microchipping'),
(9, 9, DATE_ADD(CURDATE(), INTERVAL 2 DAY), '16:00:00', 'Scheduled', 'Grooming'),
(10, 10, DATE_ADD(CURDATE(), INTERVAL 3 DAY), '09:30:00', 'Scheduled', 'Behavioral therapy');

-- Update patients table with created_at dates (backfill for existing records)
UPDATE patients SET created_at = DATE_SUB(NOW(), INTERVAL FLOOR(RAND() * 365) DAY) WHERE created_at IS NULL;

-- Add some additional sample appointments for better testing
INSERT INTO appointments (doctor_id, patient_id, appointment_date, appointment_time, status, reason) VALUES
(11, 11, CURDATE(), '12:00:00', 'Scheduled', 'Lab testing'),
(12, 12, CURDATE(), '17:00:00', 'Scheduled', 'Nutrition consultation'),
(13, 13, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '08:30:00', 'Scheduled', 'Boarding check-in'),
(14, 14, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '14:30:00', 'Scheduled', 'Weight management'),
(15, 15, DATE_ADD(CURDATE(), INTERVAL 2 DAY), '11:30:00', 'Scheduled', 'Spay/Neuter surgery'); 