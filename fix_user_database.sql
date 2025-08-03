-- Fix database issues for user files
-- Run this in phpMyAdmin or MySQL command line

USE trial;

-- Fix appointments table column names and add missing columns
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

-- Add some sample appointment reasons
UPDATE appointments SET 
    reason = 'Routine checkup' WHERE appointment_id = 1;
UPDATE appointments SET 
    reason = 'Vaccination' WHERE appointment_id = 2;
UPDATE appointments SET 
    reason = 'Dental cleaning' WHERE appointment_id = 3;
UPDATE appointments SET 
    reason = 'Surgery consultation' WHERE appointment_id = 4;
UPDATE appointments SET 
    reason = 'Emergency care' WHERE appointment_id = 5;

-- Add some sample patient data with new columns
UPDATE patients SET 
    weight = 15.5, color = 'Brown' WHERE patient_id = 1;
UPDATE patients SET 
    weight = 8.2, color = 'Orange' WHERE patient_id = 2;
UPDATE patients SET 
    weight = 12.0, color = 'Black' WHERE patient_id = 3;

-- Verify the changes
DESCRIBE appointments;
DESCRIBE patients;
DESCRIBE doctors;
SELECT * FROM appointments LIMIT 3;
SELECT * FROM patients LIMIT 3; 