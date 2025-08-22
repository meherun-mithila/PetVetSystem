-- Fix patients table for admin management system
-- Run this in phpMyAdmin or MySQL command line

USE trial;

-- Add missing columns to patients table if they don't exist
ALTER TABLE patients 
ADD COLUMN IF NOT EXISTS `weight` decimal(5,2) DEFAULT NULL AFTER `age`,
ADD COLUMN IF NOT EXISTS `color` varchar(50) DEFAULT NULL AFTER `breed`,
ADD COLUMN IF NOT EXISTS `medical_history` text DEFAULT NULL AFTER `gender`,
ADD COLUMN IF NOT EXISTS `created_at` timestamp DEFAULT CURRENT_TIMESTAMP AFTER `medical_history`;

-- Update existing patients with sample data for new columns
UPDATE patients SET 
    weight = 15.5, color = 'Brown', medical_history = 'No previous issues' WHERE patient_id = 1;
UPDATE patients SET 
    weight = 8.2, color = 'Orange', medical_history = 'Regular checkups only' WHERE patient_id = 2;
UPDATE patients SET 
    weight = 12.0, color = 'Black', medical_history = 'Vaccinated, healthy' WHERE patient_id = 3;
UPDATE patients SET 
    weight = 18.0, color = 'White', medical_history = 'Annual checkup due' WHERE patient_id = 4;
UPDATE patients SET 
    weight = 6.5, color = 'Gray', medical_history = 'Spayed, healthy' WHERE patient_id = 5;

-- Verify the changes
DESCRIBE patients;
SELECT patient_id, animal_name, weight, color, medical_history FROM patients LIMIT 5;
