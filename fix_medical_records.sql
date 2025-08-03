-- Fix medicalrecords table by adding missing columns
-- Run this in phpMyAdmin or MySQL command line

USE trial;

-- Add missing columns to medicalrecords table
ALTER TABLE medicalrecords 
ADD COLUMN `medication` text DEFAULT NULL AFTER `treatment`,
ADD COLUMN `follow_up` text DEFAULT NULL AFTER `medication`,
ADD COLUMN `notes` text DEFAULT NULL AFTER `follow_up`,
ADD COLUMN `cost` decimal(10,2) DEFAULT NULL AFTER `bills`;

-- Copy bills data to cost column if cost is null
UPDATE medicalrecords SET cost = bills WHERE cost IS NULL;

-- Add some sample data for the new columns
UPDATE medicalrecords SET 
    medication = 'Vitamin supplements' WHERE record_id = 5;
UPDATE medicalrecords SET 
    medication = 'Deworming medication' WHERE record_id IN (12, 13);
UPDATE medicalrecords SET 
    medication = 'Core vaccines' WHERE record_id = 9;
UPDATE medicalrecords SET 
    follow_up = 'Schedule follow-up in 2 weeks' WHERE record_id IN (4, 6);
UPDATE medicalrecords SET 
    follow_up = 'Behavioral training sessions scheduled' WHERE record_id = 3;
UPDATE medicalrecords SET 
    notes = 'Patient responded well to treatment' WHERE record_id IN (1, 9, 10);

-- Verify the changes
DESCRIBE medicalrecords;
SELECT * FROM medicalrecords LIMIT 3; 