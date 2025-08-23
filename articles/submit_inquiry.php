<?php
session_start();
require_once '../config.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'You must be logged in to submit an inquiry']);
    exit();
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Get form data
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');
$user_id = $_SESSION['user_id'] ?? null;

// Validate input
if (empty($subject) || empty($message)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Subject and message are required']);
    exit();
}

if (empty($user_id)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'User ID not found']);
    exit();
}

try {
    // Insert inquiry into database
    $stmt = $pdo->prepare("
        INSERT INTO inquiries (user_id, subject, message, status, timestamp)
        VALUES (?, ?, ?, 'Pending', NOW())
    ");
    $stmt->execute([$user_id, $subject, $message]);
    
    // Redirect back to articles page with success message
    header('Location: index.php?tab=inquiries&success=1');
    exit();
    
} catch (PDOException $e) {
    // Log error and redirect with error message
    error_log("Failed to submit inquiry: " . $e->getMessage());
    header('Location: index.php?tab=inquiries&error=1');
    exit();
}
?>
