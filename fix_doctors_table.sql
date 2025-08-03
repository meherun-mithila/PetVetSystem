-- Fix doctors table by adding missing columns
-- Run this in phpMyAdmin or MySQL command line

USE trial;

-- Add missing columns to doctors table
ALTER TABLE doctors 
ADD COLUMN `address` text DEFAULT NULL AFTER `phone`,
ADD COLUMN `latitude` decimal(10,8) DEFAULT NULL AFTER `address`,
ADD COLUMN `longitude` decimal(11,8) DEFAULT NULL AFTER `latitude`,
ADD COLUMN `location_id` int(11) DEFAULT NULL AFTER `longitude`;

-- Add email column if it doesn't exist
ALTER TABLE doctors 
ADD COLUMN `email` varchar(100) DEFAULT NULL AFTER `phone`;

-- Update existing doctors with email addresses if they don't have them
UPDATE doctors SET email = CONCAT(LOWER(REPLACE(name, ' ', '.')), '@petvet.com') WHERE email IS NULL;

-- Add some sample address data for existing doctors
UPDATE doctors SET 
    address = '123 Medical Center Dr, Dhaka' WHERE doctor_id = 1;
UPDATE doctors SET 
    address = '456 Veterinary Ave, Chittagong' WHERE doctor_id = 2;
UPDATE doctors SET 
    address = '789 Pet Care Blvd, Sylhet' WHERE doctor_id = 3;
UPDATE doctors SET 
    address = '321 Animal Hospital Rd, Dhaka' WHERE doctor_id = 4;
UPDATE doctors SET 
    address = '654 Veterinary Clinic St, Chittagong' WHERE doctor_id = 5;

-- Verify the changes
DESCRIBE doctors;
SELECT * FROM doctors LIMIT 5; 