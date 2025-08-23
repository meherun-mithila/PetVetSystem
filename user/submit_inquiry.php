<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_type'] !== 'user') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$action = $_POST['action'] ?? '';

if ($action !== 'submit_inquiry') {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit();
}

require_once '../config.php';

try {
    $user_id = $_SESSION['user_id'];
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    // Validate input
    if (empty($subject) || empty($message)) {
        echo json_encode(['success' => false, 'message' => 'Subject and message are required']);
        exit();
    }
    
    if (strlen($subject) > 200) {
        echo json_encode(['success' => false, 'message' => 'Subject is too long (max 200 characters)']);
        exit();
    }
    
    if (strlen($message) > 2000) {
        echo json_encode(['success' => false, 'message' => 'Message is too long (max 2000 characters)']);
        exit();
    }
    
    // Try to insert into inquiries table with different possible structures
    $inquiry_id = null;
    
    try {
        // First try: standard inquiries table structure with created_at
        $stmt = $pdo->prepare("
            INSERT INTO inquiries (user_id, subject, message, status, created_at) 
            VALUES (?, ?, ?, 'Pending', NOW())
        ");
        $stmt->execute([$user_id, $subject, $message]);
        $inquiry_id = $pdo->lastInsertId();
    } catch(PDOException $e) {
        try {
            // Second try: inquiries table with date column
            $stmt = $pdo->prepare("
                INSERT INTO inquiries (user_id, subject, message, status, date) 
                VALUES (?, ?, ?, 'Pending', NOW())
            ");
            $stmt->execute([$user_id, $subject, $message]);
            $inquiry_id = $pdo->lastInsertId();
        } catch(PDOException $e2) {
            try {
                // Third try: inquiries table without date columns
                $stmt = $pdo->prepare("
                    INSERT INTO inquiries (user_id, subject, message, status) 
                    VALUES (?, ?, ?, 'Pending')
                ");
                $stmt->execute([$user_id, $subject, $message]);
                $inquiry_id = $pdo->lastInsertId();
            } catch(PDOException $e3) {
                try {
                    // Fourth try: create inquiries table if it doesn't exist
                    $pdo->exec("
                        CREATE TABLE IF NOT EXISTS `inquiries` (
                            `inquiry_id` int(11) NOT NULL AUTO_INCREMENT,
                            `user_id` int(11) NOT NULL,
                            `subject` varchar(200) NOT NULL,
                            `message` text NOT NULL,
                            `status` enum('Pending','In Progress','Replied','Closed') DEFAULT 'Pending',
                            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
                            `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                            PRIMARY KEY (`inquiry_id`),
                            KEY `user_id` (`user_id`),
                            KEY `status` (`status`),
                            KEY `created_at` (`created_at`),
                            CONSTRAINT `fk_inquiry_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                    ");
                    
                    // Now insert the inquiry
                    $stmt = $pdo->prepare("
                        INSERT INTO inquiries (user_id, subject, message, status, created_at) 
                        VALUES (?, ?, ?, 'Pending', NOW())
                    ");
                    $stmt->execute([$user_id, $subject, $message]);
                    $inquiry_id = $pdo->lastInsertId();
                } catch(PDOException $e4) {
                    // If table creation fails, try to check what columns exist
                    try {
                        $check_stmt = $pdo->prepare("DESCRIBE inquiries");
                        $check_stmt->execute();
                        $columns = $check_stmt->fetchAll(PDO::FETCH_COLUMN);
                        
                        // Build dynamic INSERT query based on existing columns
                        $available_columns = [];
                        $placeholders = [];
                        $values = [];
                        
                        if (in_array('user_id', $columns)) {
                            $available_columns[] = 'user_id';
                            $placeholders[] = '?';
                            $values[] = $user_id;
                        }
                        
                        if (in_array('subject', $columns)) {
                            $available_columns[] = 'subject';
                            $placeholders[] = '?';
                            $values[] = $subject;
                        }
                        
                        if (in_array('message', $columns)) {
                            $available_columns[] = 'message';
                            $placeholders[] = '?';
                            $values[] = $message;
                        }
                        
                        if (in_array('status', $columns)) {
                            $available_columns[] = 'status';
                            $placeholders[] = '?';
                            $values[] = 'Pending';
                        }
                        
                        if (in_array('date', $columns)) {
                            $available_columns[] = 'date';
                            $placeholders[] = '?';
                            $values[] = date('Y-m-d H:i:s');
                        }
                        
                        if (in_array('created_at', $columns)) {
                            $available_columns[] = 'created_at';
                            $placeholders[] = '?';
                            $values[] = date('Y-m-d H:i:s');
                        }
                        
                        if (empty($available_columns)) {
                            throw new Exception("No valid columns found in inquiries table");
                        }
                        
                        $sql = "INSERT INTO inquiries (" . implode(', ', $available_columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute($values);
                        $inquiry_id = $pdo->lastInsertId();
                        
                    } catch(PDOException $e5) {
                        throw new Exception("Failed to handle inquiries table: " . $e5->getMessage());
                    }
                }
            }
        }
    }
    
    if ($inquiry_id) {
        // Send success response
        echo json_encode([
            'success' => true, 
            'message' => 'Inquiry submitted successfully',
            'inquiry_id' => $inquiry_id
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to submit inquiry']);
    }
    
} catch(Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
