-- Add sample staff data to the existing staff table
-- Run this in phpMyAdmin or MySQL command line

INSERT INTO staff (name, email, password, role) VALUES 
('Sarah Wilson', 'staff@petvet.com', 'staff123', 'Receptionist'),
('Mike Johnson', 'mike@petvet.com', 'staff123', 'Nurse'),
('Lisa Chen', 'lisa@petvet.com', 'staff123', 'Veterinary Technician'),
('David Brown', 'david@petvet.com', 'staff123', 'Lab Assistant'),
('Emma Davis', 'emma@petvet.com', 'staff123', 'Receptionist'),
('James Wilson', 'james@petvet.com', 'staff123', 'Nurse'),
('Maria Garcia', 'maria@petvet.com', 'staff123', 'Veterinary Technician'),
('Robert Taylor', 'robert@petvet.com', 'staff123', 'Lab Assistant');

-- Verify the data was added
SELECT * FROM staff; 