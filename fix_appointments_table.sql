-- Fix appointments table by adding missing columns
-- Run this in phpMyAdmin or MySQL command line

USE trial;

-- Add missing columns to appointments table
ALTER TABLE appointments 
ADD COLUMN `reason` text DEFAULT NULL AFTER `status`;

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
UPDATE appointments SET 
    reason = 'X-Ray examination' WHERE appointment_id = 6;
UPDATE appointments SET 
    reason = 'Ultrasound' WHERE appointment_id = 7;
UPDATE appointments SET 
    reason = 'Microchipping' WHERE appointment_id = 8;
UPDATE appointments SET 
    reason = 'Grooming' WHERE appointment_id = 9;
UPDATE appointments SET 
    reason = 'Behavioral therapy' WHERE appointment_id = 10;

-- Verify the changes
DESCRIBE appointments;
SELECT * FROM appointments LIMIT 5; 