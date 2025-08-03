-- Add sample admin data to the existing admin table
-- Run this in phpMyAdmin or MySQL command line

INSERT INTO admin (name, email, password) VALUES 
('Adeeb', '12adeeb@gmail.com', 'adeeb123'),
('Mithila', 'mithila@petvet.com', 'Mithila12'),
('Admin User', 'admin@petvet.com', 'admin123'),
('System Admin', 'system@petvet.com', 'system123'),
('Clinic Manager', 'manager@petvet.com', 'manager123');

-- Verify the data was added
SELECT * FROM admin; 