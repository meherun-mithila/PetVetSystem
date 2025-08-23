<?php
session_start();
require_once '../config.php';

// Check if user is logged in and has admin/staff privileges
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || 
    !in_array($_SESSION['user_type'], ['admin', 'staff'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Get form data
$inquiry_id = (int)($_POST['inquiry_id'] ?? 0);
$reply_message = trim($_POST['reply_message'] ?? '');
$staff_id = $_SESSION['user_id'] ?? null;
$staff_name = $_SESSION['user_name'] ?? 'Staff Member';

// Validate input
if ($inquiry_id <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid inquiry ID']);
    exit();
}

if (empty($reply_message)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Reply message is required']);
    exit();
}

try {
    // First, check if the inquiry exists
    $stmt = $pdo->prepare("SELECT * FROM inquiries WHERE inquiry_id = ?");
    $stmt->execute([$inquiry_id]);
    $inquiry = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$inquiry) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Inquiry not found']);
        exit();
    }

    // Insert reply into inquiry_replies table (create if doesn't exist)
    try {
        $stmt = $pdo->prepare("
            INSERT INTO inquiry_replies (inquiry_id, staff_id, staff_name, reply_message, timestamp)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$inquiry_id, $staff_id, $staff_name, $reply_message]);
    } catch (PDOException $e) {
        // If inquiry_replies table doesn't exist, create it
        if (strpos($e->getMessage(), "doesn't exist") !== false) {
            $pdo->exec("
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
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
            ");
            
            // Try inserting again
            $stmt = $pdo->prepare("
                INSERT INTO inquiry_replies (inquiry_id, staff_id, staff_name, reply_message, timestamp)
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$inquiry_id, $staff_id, $staff_name, $reply_message]);
        } else {
            throw $e;
        }
    }

    // Update inquiry status to 'Replied' if it was 'Pending'
    if ($inquiry['status'] === 'Pending') {
        $stmt = $pdo->prepare("UPDATE inquiries SET status = 'Replied' WHERE inquiry_id = ?");
        $stmt->execute([$inquiry_id]);
    }
    
    // Redirect back to articles page with success message
    header('Location: index.php?tab=inquiries&reply_success=1');
    exit();
    
} catch (PDOException $e) {
    // Log error and redirect with error message
    error_log("Failed to submit reply: " . $e->getMessage());
    header('Location: index.php?tab=inquiries&reply_error=1');
    exit();
}
?>
