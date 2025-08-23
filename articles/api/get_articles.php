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

try {
    // Fetch articles with actual table structure
    $stmt = $pdo->prepare("
        SELECT a.article_id, a.title, a.content, a.author_id, a.date_posted, a.tags,
               'Unknown' as author_name,
               'unknown@email.com' as author_email,
               'unknown' as author_type,
               0 as comment_count
        FROM articles a
        ORDER BY a.date_posted DESC
    ");
    $stmt->execute();
    $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'articles' => $articles]);
    
} catch (PDOException $e) {
    error_log("Failed to fetch articles: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>
