<?php
session_start();
require_once '../../config.php';

// Check if user is logged in and has admin/staff privileges
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || 
    !in_array($_SESSION['user_type'], ['admin', 'staff', 'doctor'])) {
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
$title = trim($_POST['title'] ?? '');
$summary = trim($_POST['summary'] ?? '');
$topic = trim($_POST['topic'] ?? '');
$tags = trim($_POST['tags'] ?? '');
$content = trim($_POST['content'] ?? '');
$author_id = $_SESSION['user_id'] ?? null;
$author_type = $_SESSION['user_type'] ?? '';

if (empty($title) || empty($content) || empty($author_id)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Title, content, and author are required']);
    exit();
}

try {
    // Insert the article with actual table structure
    $stmt = $pdo->prepare("
        INSERT INTO articles (title, content, author_id, date_posted, tags)
        VALUES (?, ?, ?, CURDATE(), ?)
    ");
    $stmt->execute([$title, $content, $author_id, $tags]);
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Article added successfully']);
    
} catch (PDOException $e) {
    error_log("Failed to add article: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>
