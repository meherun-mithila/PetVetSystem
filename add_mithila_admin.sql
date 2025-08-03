-- Add Mithila's admin account
-- Run this in phpMyAdmin or MySQL command line

USE trial;

-- Add Mithila's admin account if it doesn't exist
INSERT IGNORE INTO admin (name, email, password) VALUES 
('Mithila', 'mithila@petvet.com', 'Mithila12');

-- Verify the account was added
SELECT * FROM admin WHERE name LIKE '%Mithila%' OR email = 'mithila@petvet.com'; 