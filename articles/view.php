<?php
session_start();
require_once '../config.php';

$clinic_name = "Caring Paws Veterinary Clinic";

$article_id = $_GET['id'] ?? null;
if (!$article_id || !is_numeric($article_id)) {
    header("Location: index.php");
    exit();
}

$article_id = (int)$article_id;

// Handle comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_comment') {
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        $error_message = "You must be logged in to comment.";
    } else {
        $comment_text = trim($_POST['comment_text'] ?? '');
        if (empty($comment_text)) {
            $error_message = "Comment cannot be empty.";
        } else {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO article_comments (article_id, commenter_id, commenter_type, commenter_name, comment_text, created_at)
                    VALUES (?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([
                    $article_id,
                    $_SESSION['user_id'],
                    $_SESSION['user_type'],
                    $_SESSION['user_name'] ?? $_SESSION['name'] ?? $_SESSION['email'],
                    $comment_text
                ]);
                
                $success_message = "Comment added successfully!";
                
                // Redirect to refresh the page and show the new comment
                header("Location: view.php?id=$article_id&success=1");
                exit();
            } catch (PDOException $e) {
                $error_message = "Failed to add comment: " . $e->getMessage();
            }
        }
    }
}

// Handle comment deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_comment') {
    $comment_id = (int)($_POST['comment_id'] ?? 0);
    $can_delete = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && 
                  (($_SESSION['user_type'] === 'admin') || 
                   ($_SESSION['user_type'] === 'doctor' && $_SESSION['user_id'] == $article['author_id']));
    
    if ($can_delete) {
        try {
            $stmt = $pdo->prepare("DELETE FROM article_comments WHERE comment_id = ? AND article_id = ?");
            $stmt->execute([$comment_id, $article_id]);
            $success_message = "Comment deleted successfully!";
        } catch (PDOException $e) {
            $error_message = "Failed to delete comment: " . $e->getMessage();
        }
    }
}

// Fetch article details
try {
    $stmt = $pdo->prepare("
        SELECT a.*, 
               COALESCE(d.name, ad.name) as author_name,
               COALESCE(d.email, ad.email) as author_email,
               CASE 
                   WHEN d.doctor_id IS NOT NULL THEN 'doctor'
                   WHEN ad.admin_id IS NOT NULL THEN 'admin'
                   ELSE 'unknown'
               END as author_type
        FROM articles a
        LEFT JOIN doctors d ON a.author_id = d.doctor_id AND a.author_type = 'doctor'
        LEFT JOIN admin ad ON a.author_id = ad.admin_id AND a.author_type = 'admin'
        WHERE a.article_id = ?
    ");
    $stmt->execute([$article_id]);
    $article = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$article) {
        header("Location: index.php");
        exit();
    }
    
    // Increment view count
    $stmt = $pdo->prepare("UPDATE articles SET view_count = view_count + 1 WHERE article_id = ?");
    $stmt->execute([$article_id]);
    
} catch (PDOException $e) {
    header("Location: index.php");
    exit();
}

// Fetch comments
try {
    $stmt = $pdo->prepare("
        SELECT c.*, 
               CASE 
                   WHEN c.commenter_type = 'admin' THEN 'Admin'
                   WHEN c.commenter_type = 'doctor' THEN 'Doctor'
                   WHEN c.commenter_type = 'user' THEN 'Pet Owner'
                   ELSE 'User'
               END as commenter_role
        FROM article_comments c
        WHERE c.article_id = ?
        ORDER BY c.created_at ASC
    ");
    $stmt->execute([$article_id]);
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $comments = [];
}

// Check if user can edit/delete the article
$can_edit = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && 
            (($_SESSION['user_type'] === 'admin') || 
             ($_SESSION['user_type'] === 'doctor' && $_SESSION['user_id'] == $article['author_id']));

$can_comment = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;

// Show success message if redirected after comment
if (isset($_GET['success'])) {
    $success_message = "Comment added successfully!";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($article['title']); ?> - <?php echo $clinic_name; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'vet-blue': '#2c5aa0',
                        'vet-dark-blue': '#1e3d72',
                        'vet-coral': '#ff6b6b'
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-100">
    <!-- Header -->
    <header class="bg-vet-blue text-white p-4">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold"><?php echo $clinic_name; ?></h1>
            <nav class="flex items-center space-x-6">
                <a href="index.php" class="text-white hover:text-gray-200 transition-colors">Articles</a>
                <?php if ($can_edit): ?>
                    <a href="edit.php?id=<?php echo $article_id; ?>" class="bg-vet-dark-blue px-4 py-2 rounded-lg hover:bg-blue-800 transition-colors">Edit Article</a>
                <?php endif; ?>
                <a href="../index.php" class="text-white hover:text-gray-200 transition-colors">Home</a>
                <?php if (isset($_SESSION['logged_in'])): ?>
                    <a href="../logout.php" class="bg-red-600 px-4 py-2 rounded-lg hover:bg-red-700 transition-colors">Logout</a>
                <?php else: ?>
                    <a href="../index.php" class="bg-vet-dark-blue px-4 py-2 rounded-lg hover:bg-blue-800 transition-colors">Login</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <div class="max-w-4xl mx-auto p-6">
        <!-- Article Content -->
        <div class="bg-white rounded-lg shadow p-8 mb-6">
            <!-- Article Header -->
            <div class="mb-6">
                <div class="flex items-center justify-between mb-4">
                    <span class="px-3 py-1 text-sm font-medium bg-vet-blue text-white rounded-full">
                        <?php echo htmlspecialchars($article['topic'] ?? 'General'); ?>
                    </span>
                    <div class="flex items-center space-x-4 text-sm text-gray-500">
                        <span class="flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                            <?php echo number_format(($article['view_count'] ?? 0) + 1); ?> views
                        </span>
                        <span><?php echo date('M j, Y \a\t g:i A', strtotime($article['created_at'])); ?></span>
                    </div>
                </div>
                
                <h1 class="text-3xl font-bold text-gray-900 mb-4"><?php echo htmlspecialchars($article['title']); ?></h1>
                
                <?php if (!empty($article['summary'])): ?>
                    <p class="text-lg text-gray-600 mb-4"><?php echo htmlspecialchars($article['summary']); ?></p>
                <?php endif; ?>
                
                <div class="flex items-center justify-between py-4 border-t border-gray-200">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-vet-blue rounded-full flex items-center justify-center text-white font-semibold">
                            <?php echo strtoupper(substr($article['author_name'], 0, 1)); ?>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900"><?php echo htmlspecialchars($article['author_name']); ?></p>
                            <p class="text-sm text-gray-500"><?php echo ucfirst($article['author_type']); ?></p>
                        </div>
                    </div>
                    
                    <?php if (!empty($article['tags'])): ?>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach (explode(',', $article['tags']) as $tag): ?>
                                <span class="px-2 py-1 text-xs bg-gray-100 text-gray-700 rounded">
                                    <?php echo htmlspecialchars(trim($tag)); ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Article Body -->
            <div class="prose max-w-none">
                <?php echo $article['content']; ?>
            </div>
        </div>

        <!-- Comments Section -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-xl font-semibold text-gray-900 mb-4">
                Comments (<?php echo count($comments); ?>)
            </h3>

            <?php if (!empty($error_message)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success_message)): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <!-- Add Comment Form -->
            <?php if ($can_comment): ?>
                <form method="POST" class="mb-6 p-4 border border-gray-200 rounded-lg">
                    <input type="hidden" name="action" value="add_comment">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Add a Comment</label>
                        <textarea name="comment_text" rows="3" required 
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-vet-blue"
                                  placeholder="Share your thoughts on this article..."></textarea>
                    </div>
                    <button type="submit" class="px-4 py-2 bg-vet-blue text-white rounded-md hover:bg-vet-dark-blue transition-colors">
                        Post Comment
                    </button>
                </form>
            <?php else: ?>
                <div class="mb-6 p-4 bg-gray-50 border border-gray-200 rounded-lg text-center">
                    <p class="text-gray-600">Please <a href="../index.php" class="text-vet-blue hover:underline">login</a> to leave a comment.</p>
                </div>
            <?php endif; ?>

            <!-- Comments List -->
            <?php if (!empty($comments)): ?>
                <div class="space-y-4">
                    <?php foreach ($comments as $comment): ?>
                        <div class="border-l-4 border-vet-blue pl-4 py-3">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-2 mb-2">
                                        <span class="font-medium text-gray-900">
                                            <?php echo htmlspecialchars($comment['commenter_name']); ?>
                                        </span>
                                        <span class="px-2 py-1 text-xs bg-gray-100 text-gray-600 rounded">
                                            <?php echo htmlspecialchars($comment['commenter_role']); ?>
                                        </span>
                                        <span class="text-sm text-gray-500">
                                            <?php echo date('M j, Y g:i A', strtotime($comment['created_at'])); ?>
                                        </span>
                                    </div>
                                    <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($comment['comment_text'])); ?></p>
                                </div>
                                
                                <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && 
                                         (($_SESSION['user_type'] === 'admin') || 
                                          ($_SESSION['user_type'] === 'doctor' && $_SESSION['user_id'] == $article['author_id']))): ?>
                                    <form method="POST" class="ml-4" onsubmit="return confirm('Are you sure you want to delete this comment?')">
                                        <input type="hidden" name="action" value="delete_comment">
                                        <input type="hidden" name="comment_id" value="<?php echo $comment['comment_id']; ?>">
                                        <button type="submit" class="text-red-600 hover:text-red-800 text-sm">
                                            Delete
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-8 text-gray-500">
                    <p>No comments yet. Be the first to share your thoughts!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <style>
        .prose {
            line-height: 1.6;
            color: #374151;
        }
        .prose h1, .prose h2, .prose h3, .prose h4, .prose h5, .prose h6 {
            color: #111827;
            font-weight: 600;
            margin-top: 1.5em;
            margin-bottom: 0.5em;
        }
        .prose p {
            margin-bottom: 1em;
        }
        .prose ul, .prose ol {
            margin-bottom: 1em;
            padding-left: 1.5em;
        }
        .prose li {
            margin-bottom: 0.5em;
        }
        .prose blockquote {
            border-left: 4px solid #e5e7eb;
            padding-left: 1em;
            margin: 1em 0;
            font-style: italic;
            color: #6b7280;
        }
        .prose img {
            max-width: 100%;
            height: auto;
            border-radius: 0.5rem;
            margin: 1em 0;
        }
        .prose table {
            width: 100%;
            border-collapse: collapse;
            margin: 1em 0;
        }
        .prose th, .prose td {
            border: 1px solid #e5e7eb;
            padding: 0.5em;
            text-align: left;
        }
        .prose th {
            background-color: #f9fafb;
            font-weight: 600;
        }
    </style>
</body>
</html>
