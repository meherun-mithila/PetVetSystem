<?php
session_start();
require_once '../../config.php';

// Check if user is logged in and has admin/staff privileges
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || 
    !in_array($_SESSION['user_type'], ['admin', 'staff'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

try {
    // Fetch inquiries with user information and replies
    $stmt = $pdo->prepare("
        SELECT i.*, u.name as user_name, u.email as user_email
        FROM inquiries i
        LEFT JOIN users u ON i.user_id = u.user_id
        ORDER BY i.timestamp DESC
    ");
    $stmt->execute();
    $inquiries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch replies for each inquiry
    foreach ($inquiries as &$inquiry) {
        try {
            $stmt = $pdo->prepare("
                SELECT * FROM inquiry_replies 
                WHERE inquiry_id = ? 
                ORDER BY timestamp ASC
            ");
            $stmt->execute([$inquiry['inquiry_id']]);
            $inquiry['replies'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $inquiry['replies'] = [];
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'inquiries' => $inquiries]);
    
} catch (PDOException $e) {
    error_log("Failed to fetch inquiries: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>
