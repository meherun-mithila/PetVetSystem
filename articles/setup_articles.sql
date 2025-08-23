-- Articles System Database Setup
-- Run this file to create the necessary tables for the articles functionality

-- Create articles table
CREATE TABLE IF NOT EXISTS `articles` (
    `article_id` int(11) NOT NULL AUTO_INCREMENT,
    `title` varchar(255) NOT NULL,
    `summary` text,
    `content` longtext NOT NULL,
    `topic` varchar(100),
    `tags` text,
    `author_id` int(11) NOT NULL,
    `author_type` enum('admin','doctor') NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `view_count` int(11) NOT NULL DEFAULT 0,
    `status` enum('published','draft','archived') NOT NULL DEFAULT 'published',
    PRIMARY KEY (`article_id`),
    KEY `author_idx` (`author_type`, `author_id`),
    KEY `topic_idx` (`topic`),
    KEY `status_idx` (`status`),
    KEY `created_at_idx` (`created_at`),
    KEY `view_count_idx` (`view_count`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create article comments table
CREATE TABLE IF NOT EXISTS `article_comments` (
    `comment_id` int(11) NOT NULL AUTO_INCREMENT,
    `article_id` int(11) NOT NULL,
    `commenter_id` int(11) NOT NULL,
    `commenter_type` enum('admin','doctor','user','staff') NOT NULL,
    `commenter_name` varchar(255) NOT NULL,
    `comment_text` text NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `status` enum('active','hidden','deleted') NOT NULL DEFAULT 'active',
    PRIMARY KEY (`comment_id`),
    KEY `article_idx` (`article_id`),
    KEY `commenter_idx` (`commenter_type`, `commenter_id`),
    KEY `created_at_idx` (`created_at`),
    KEY `status_idx` (`status`),
    CONSTRAINT `fk_article_comments_article` FOREIGN KEY (`article_id`) REFERENCES `articles` (`article_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert sample articles (optional)
INSERT IGNORE INTO `articles` (`title`, `summary`, `content`, `topic`, `tags`, `author_id`, `author_type`, `status`) VALUES
(
    'Essential Pet Care Tips for New Pet Owners',
    'A comprehensive guide for first-time pet owners covering basic care, nutrition, and health maintenance.',
    '<h2>Welcome to Pet Parenthood!</h2><p>Congratulations on becoming a new pet owner! This guide will help you provide the best care for your furry friend.</p><h3>Basic Care Requirements</h3><ul><li>Regular feeding with appropriate food</li><li>Fresh water available at all times</li><li>Daily exercise and playtime</li><li>Regular grooming and hygiene</li><li>Veterinary check-ups</li></ul><h3>Nutrition Guidelines</h3><p>Choose high-quality pet food appropriate for your pet\'s age, size, and health needs. Avoid feeding human food that can be harmful.</p><h3>Exercise and Mental Stimulation</h3><p>Pets need both physical exercise and mental stimulation. Regular walks, play sessions, and interactive toys help keep them healthy and happy.</p>',
    'Pet Care Tips',
    'pet care, new owners, basic care, nutrition, exercise',
    1,
    'admin',
    'published'
),
(
    'Understanding Pet Vaccination Schedules',
    'Learn about the importance of vaccinations and recommended schedules for dogs and cats.',
    '<h2>Why Vaccinations Matter</h2><p>Vaccinations protect your pets from serious and potentially fatal diseases. They also help prevent the spread of diseases to other animals and humans.</p><h3>Core Vaccines for Dogs</h3><ul><li>Rabies - Required by law in most areas</li><li>Distemper - Protects against multiple diseases</li><li>Parvovirus - Highly contagious and deadly</li><li>Adenovirus - Protects against respiratory and liver disease</li></ul><h3>Core Vaccines for Cats</h3><ul><li>Rabies - Required by law in most areas</li><li>FVRCP - Protects against multiple respiratory and intestinal diseases</li><li>Feline Leukemia - Recommended for outdoor cats</li></ul><h3>Vaccination Schedule</h3><p>Puppies and kittens typically receive their first vaccines at 6-8 weeks of age, with boosters every 3-4 weeks until 16 weeks old. Adult pets need regular booster shots.</p>',
    'Vaccination',
    'vaccination, dogs, cats, health, prevention',
    1,
    'admin',
    'published'
),
(
    'Emergency Pet Care: What to Do Before the Vet',
    'Essential first aid and emergency care information for pet owners to handle urgent situations.',
    '<h2>Stay Calm and Assess the Situation</h2><p>In an emergency, the most important thing is to stay calm. Your pet can sense your anxiety, which may make the situation worse.</p><h3>Common Emergency Situations</h3><h4>Injuries and Bleeding</h4><ul><li>Apply direct pressure to stop bleeding</li><li>Use clean cloth or gauze</li><li>Elevate the injured area if possible</li><li>Transport to vet immediately</li></ul><h4>Poisoning</h4><ul><li>Do NOT induce vomiting unless directed by vet</li><li>Remove any remaining poison from pet\'s reach</li><li>Call vet or pet poison hotline immediately</li><li>Bring the poison container if possible</li></ul><h4>Heat Stroke</h4><ul><li>Move pet to cool area</li><li>Apply cool (not cold) water to body</li><li>Use wet towels on neck and armpits</li><li>Transport to vet immediately</li></ul><h3>When to Seek Immediate Veterinary Care</h3><p>Always contact your veterinarian or emergency clinic if you\'re unsure about the severity of your pet\'s condition.</p>',
    'Emergency Care',
    'emergency care, first aid, injuries, poisoning, heat stroke',
    1,
    'admin',
    'published'
);

-- Insert sample comments (optional)
INSERT IGNORE INTO `article_comments` (`article_id`, `commenter_id`, `commenter_type`, `commenter_name`, `comment_text`) VALUES
(1, 1, 'user', 'Sarah Johnson', 'This article was so helpful! I just got my first puppy and was feeling overwhelmed. These tips really put my mind at ease.'),
(1, 2, 'doctor', 'Dr. Michael Chen', 'Great article! I would add that new pet owners should also consider pet insurance and microchipping for additional protection.'),
(2, 1, 'user', 'Sarah Johnson', 'I had no idea about the vaccination schedule. This explains why my vet recommended those specific shots.'),
(2, 3, 'admin', 'Admin User', 'Thank you for the feedback! We\'re glad this information is helpful for pet owners.');

-- Add indexes for better performance
ALTER TABLE `articles` ADD INDEX `search_idx` (`title`, `summary`, `content`(100));
ALTER TABLE `articles` ADD INDEX `author_topic_idx` (`author_type`, `author_id`, `topic`);

-- Add fulltext search capability (optional, for advanced search)
ALTER TABLE `articles` ADD FULLTEXT(`title`, `summary`, `content`);

-- Create view for article statistics
CREATE OR REPLACE VIEW `article_stats` AS
SELECT 
    a.article_id,
    a.title,
    a.topic,
    a.view_count,
    a.created_at,
    COUNT(c.comment_id) as comment_count,
    COALESCE(d.name, ad.name) as author_name,
    a.author_type
FROM articles a
LEFT JOIN article_comments c ON a.article_id = c.comment_id AND c.status = 'active'
LEFT JOIN doctors d ON a.author_id = d.doctor_id AND a.author_type = 'doctor'
LEFT JOIN admin ad ON a.author_id = ad.admin_id AND a.author_type = 'admin'
WHERE a.status = 'published'
GROUP BY a.article_id;

-- Insert sample inquiries (optional)
INSERT IGNORE INTO `inquiries` (`user_id`, `subject`, `message`, `status`, `timestamp`) VALUES
(1, 'Pet Vaccination Schedule', 'Hi, I have a 3-month-old puppy and I\'m not sure about the vaccination schedule. Can you provide information about which vaccines are needed and when?', 'Replied', '2025-01-15 10:30:00'),
(1, 'Emergency Care Question', 'My dog ate something that might be toxic. What should I do immediately before bringing him to the clinic?', 'Closed', '2025-01-10 14:20:00'),
(2, 'Pet Nutrition Advice', 'I\'m looking for recommendations on the best food for my senior cat with kidney issues. Any suggestions?', 'Pending', '2025-01-20 09:15:00'),
(3, 'Appointment Booking', 'I need to schedule a routine checkup for my two cats. What are your available time slots this week?', 'Replied', '2025-01-18 16:45:00'),
(4, 'Behavioral Training', 'My dog has been showing aggressive behavior towards other dogs during walks. Do you offer behavioral training services?', 'Pending', '2025-01-22 11:30:00');

-- Create inquiry_replies table for staff responses
CREATE TABLE IF NOT EXISTS `inquiry_replies` (
    `reply_id` int(11) NOT NULL AUTO_INCREMENT,
    `inquiry_id` int(11) NOT NULL,
    `staff_id` int(11) NOT NULL,
    `staff_name` varchar(255) NOT NULL,
    `reply_message` text NOT NULL,
    `timestamp` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`reply_id`),
    KEY `inquiry_idx` (`inquiry_id`),
    KEY `staff_idx` (`staff_id`),
    CONSTRAINT `fk_inquiry_replies_inquiry` FOREIGN KEY (`inquiry_id`) REFERENCES `inquiries` (`inquiry_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Grant permissions (adjust as needed for your database setup)
-- GRANT SELECT, INSERT, UPDATE, DELETE ON `articles`.* TO 'your_user'@'localhost';
-- GRANT SELECT, INSERT, UPDATE, DELETE ON `article_comments`.* TO 'your_user'@'localhost';
-- GRANT SELECT, INSERT, UPDATE, DELETE ON `inquiry_replies`.* TO 'your_user'@'localhost';
