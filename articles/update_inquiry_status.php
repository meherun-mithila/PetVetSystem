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
$status = trim($_POST['status'] ?? '');

// Validate input
if ($inquiry_id <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid inquiry ID']);
    exit();
}

if (!in_array($status, ['Pending', 'Replied', 'Closed'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit();
}

try {
    // Update inquiry status
    $stmt = $pdo->prepare("
        UPDATE inquiries 
        SET status = ? 
        WHERE inquiry_id = ?
    ");
    $result = $stmt->execute([$status, $inquiry_id]);
    
    if ($result && $stmt->rowCount() > 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Inquiry status updated successfully']);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Inquiry not found or no changes made']);
    }
    
} catch (PDOException $e) {
    // Log error and return error message
    error_log("Failed to update inquiry status: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>
