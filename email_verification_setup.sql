-- Email Verification System Setup for PetVet
-- Run this SQL to create the necessary table structure

-- Create email verifications table
CREATE TABLE IF NOT EXISTS `email_verifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `verification_token` varchar(255) NOT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `expires_at` timestamp DEFAULT (CURRENT_TIMESTAMP + INTERVAL 24 HOUR),
  PRIMARY KEY (`id`),
  KEY `verification_token` (`verification_token`),
  KEY `email` (`email`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add is_verified column to users table if it doesn't exist
-- Note: For older MySQL versions, run this manually if the column doesn't exist
-- ALTER TABLE `users` ADD COLUMN `is_verified` tinyint(1) DEFAULT 0;

-- Update existing users to be verified (optional)
-- UPDATE `users` SET `is_verified` = 1 WHERE `is_verified` IS NULL;

-- Sample verification record (for testing)
-- INSERT INTO `email_verifications` (`user_id`, `email`, `verification_token`, `is_verified`) 
-- VALUES (1, 'test@example.com', 'test_token_123', 0);
