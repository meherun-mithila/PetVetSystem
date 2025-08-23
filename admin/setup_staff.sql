-- Staff table setup for PetVet system
-- Run this SQL script to create the staff table

CREATE TABLE IF NOT EXISTS `staff` (
  `staff_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL UNIQUE,
  `phone` varchar(20) DEFAULT NULL,
  `role` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `extra_info` text DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`staff_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert some sample staff members (optional)
INSERT INTO `staff` (`name`, `email`, `phone`, `role`, `password`, `extra_info`) VALUES
('John Smith', 'john.smith@petvet.com', '+1234567890', 'Manager', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Experienced veterinary practice manager'),
('Sarah Johnson', 'sarah.johnson@petvet.com', '+1234567891', 'Receptionist', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Friendly and organized receptionist'),
('Mike Wilson', 'mike.wilson@petvet.com', '+1234567892', 'Veterinary Technician', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Certified veterinary technician with 5 years experience');

-- Note: The password hash above is for 'password' - change this in production!
