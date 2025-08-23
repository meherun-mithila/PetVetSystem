<?php
session_start();
require_once '../config.php';
require_once '../includes/status_helper.php';

$clinic_name = "Caring Paws Veterinary Clinic";



// Get filter parameters
$topic_filter = $_GET['topic'] ?? '';
$search_query = $_GET['search'] ?? '';
$sort_by = $_GET['sort'] ?? 'latest';

// Build the query with filters
$where_conditions = ["1=1"];
$params = [];

if ($topic_filter) {
    // Filter by tags
    $where_conditions[] = "a.tags LIKE ?";
    $params[] = "%$topic_filter%";
}

if ($search_query) {
    $where_conditions[] = "(a.title LIKE ? OR a.content LIKE ? OR a.tags LIKE ?)";
    $search_param = "%$search_query%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$where_clause = implode(" AND ", $where_conditions);



// Determine sorting
switch($sort_by) {
    case 'latest':
        $order_clause = 'ORDER BY a.date_posted DESC';
        break;
    case 'oldest':
        $order_clause = 'ORDER BY a.date_posted ASC';
        break;
    case 'popular':
        $order_clause = 'ORDER BY a.article_id DESC';
        break;
    case 'title':
        $order_clause = 'ORDER BY a.title ASC';
        break;
    default:
        $order_clause = 'ORDER BY a.date_posted DESC';
        break;
}

// Fetch articles with author information and stats
try {

    
    // Fetch articles with filtering
    $stmt = $pdo->prepare("
        SELECT a.article_id, a.title, a.content, a.author_id, a.date_posted, a.tags,
               'Unknown' as author_name,
               'unknown@email.com' as author_email,
               'unknown' as author_type,
               0 as comment_count,
               0 as view_count,
               a.tags as topic,
               a.content as summary
        FROM articles a
        WHERE $where_clause
        $order_clause
    ");
    $stmt->execute($params);
    $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    

    
} catch (PDOException $e) {
    $articles = [];
}

// Fetch available topics for filter (using tags)
try {
    $stmt = $pdo->prepare("SELECT DISTINCT tags FROM articles WHERE tags IS NOT NULL AND tags != '' ORDER BY tags");
    $stmt->execute();
    $raw_topics = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Extract individual tags from comma-separated values
    $topics = [];
    foreach ($raw_topics as $tag_string) {
        $individual_tags = array_map('trim', explode(',', $tag_string));
        foreach ($individual_tags as $tag) {
            if (!empty($tag) && !in_array($tag, $topics)) {
                $topics[] = $tag;
            }
        }
    }
    sort($topics);
} catch (PDOException $e) {
    $topics = [];
}

// Check if user can post articles
$can_post = isset($_SESSION['logged_in']) && 
           in_array($_SESSION['user_type'], ['admin', 'staff', 'doctor']);

// Check if user can edit articles (admin, staff, or doctor)
$can_edit = isset($_SESSION['logged_in']) && 
           in_array($_SESSION['user_type'], ['admin', 'staff', 'doctor']);

// Increment view count for articles (view_count column doesn't exist, so we'll skip this)
if (isset($_GET['view']) && is_numeric($_GET['view'])) {
    $article_id = (int)$_GET['view'];
    // Note: view_count column doesn't exist in this table structure
    // Could add it later if needed
}

// Fetch inquiries with user information and replies
$inquiries = [];
try {
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
} catch (PDOException $e) {
    $inquiries = [];
}

// Get current tab
$current_tab = $_GET['tab'] ?? 'articles';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Articles - <?php echo $clinic_name; ?></title>
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
            <h1 class="text-2xl font-bold"><?php echo $clinic_name; ?> - Articles</h1>
            <nav class="flex items-center space-x-6">
                <a href="../index.php" class="text-white hover:text-gray-200 transition-colors">Home</a>
                <?php if ($can_post): ?>
                    <a href="post.php" class="bg-vet-dark-blue px-4 py-2 rounded-lg hover:bg-blue-800 transition-colors">Post Article</a>
                <?php endif; ?>
                <?php if (isset($_SESSION['logged_in'])): ?>
                    <a href="../logout.php" class="bg-red-600 px-4 py-2 rounded-lg hover:bg-red-700 transition-colors">Logout</a>
                <?php else: ?>
                    <a href="../index.php" class="bg-vet-dark-blue px-4 py-2 rounded-lg hover:bg-blue-800 transition-colors">Login</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <div class="max-w-7xl mx-auto p-6">
        <!-- Tab Navigation -->
        <div class="bg-white rounded-lg shadow mb-6">
            <div class="border-b border-gray-200">
                <nav class="flex space-x-8 px-6" aria-label="Tabs">
                    <a href="?tab=articles<?php echo $topic_filter ? '&topic=' . urlencode($topic_filter) : ''; ?><?php echo $search_query ? '&search=' . urlencode($search_query) : ''; ?><?php echo $sort_by !== 'latest' ? '&sort=' . urlencode($sort_by) : ''; ?>" 
                       class="<?php echo $current_tab === 'articles' ? 'border-vet-blue text-vet-blue' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                        📚 Articles
                    </a>
                    <a href="?tab=inquiries" 
                       class="<?php echo $current_tab === 'inquiries' ? 'border-vet-blue text-vet-blue' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                        💬 Inquiries
                    </a>
                </nav>
            </div>
        </div>

        <!-- Search and Filter Section (Articles Tab) -->
        <?php if ($current_tab === 'articles'): ?>
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search_query); ?>" 
                           placeholder="Search articles..." 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-vet-blue">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Topic</label>
                    <select name="topic" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-vet-blue">
                        <option value="">All Topics</option>
                        <?php foreach ($topics as $topic): ?>
                            <option value="<?php echo htmlspecialchars($topic); ?>" 
                                    <?php echo $topic_filter === $topic ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($topic); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Sort By</label>
                    <select name="sort" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-vet-blue">
                        <option value="latest" <?php echo $sort_by === 'latest' ? 'selected' : ''; ?>>Latest</option>
                        <option value="oldest" <?php echo $sort_by === 'oldest' ? 'selected' : ''; ?>>Oldest</option>
                        <option value="popular" <?php echo $sort_by === 'popular' ? 'selected' : ''; ?>>Most Popular</option>
                        <option value="title" <?php echo $sort_by === 'title' ? 'selected' : ''; ?>>Title A-Z</option>
                    </select>
                </div>
                
                <div class="flex items-end">
                    <button type="submit" class="w-full bg-vet-blue text-white px-4 py-2 rounded-md hover:bg-vet-dark-blue transition-colors">
                        Apply Filters
                    </button>
                </div>
            </form>
        </div>





        <!-- Articles Grid -->
        <?php if (!empty($articles)): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($articles as $article): ?>
                    <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-3">
                                <span class="px-3 py-1 text-xs font-medium bg-vet-blue text-white rounded-full">
                                    <?php echo htmlspecialchars($article['topic'] ?? 'General'); ?>
                                </span>
                                <span class="text-xs text-gray-500">
                                    <?php echo isset($article['date_posted']) ? date('M j, Y', strtotime($article['date_posted'])) : 'Unknown Date'; ?>
                                </span>
                            </div>
                            
                            <h3 class="text-lg font-semibold text-gray-900 mb-2 line-clamp-2">
                                <a href="view.php?id=<?php echo $article['article_id']; ?>" 
                                   class="hover:text-vet-blue transition-colors">
                                    <?php echo htmlspecialchars($article['title']); ?>
                                </a>
                            </h3>
                            
                            <p class="text-gray-600 text-sm mb-4 line-clamp-3">
                                <?php echo htmlspecialchars(substr($article['content'], 0, 150) . '...'); ?>
                            </p>
                            
                            <div class="flex items-center justify-between text-sm text-gray-500">
                                <div class="flex items-center space-x-4">
                                    <span class="flex items-center">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                        <?php echo number_format($article['view_count'] ?? 0); ?>
                                    </span>
                                    <span class="flex items-center">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 11-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                        </svg>
                                        <?php echo number_format($article['comment_count'] ?? 0); ?>
                                    </span>
                                </div>
                                
                                <div class="text-right">
                                    <p class="font-medium text-vet-blue">
                                        <?php echo htmlspecialchars($article['author_name']); ?>
                                    </p>
                                    <p class="text-xs text-gray-400">
                                        <?php echo ucfirst($article['author_type']); ?>
                                    </p>
                                    <?php if ($can_edit): ?>
                                        <div class="mt-2">
                                            <a href="edit.php?id=<?php echo $article['article_id']; ?>" 
                                               class="text-sm text-vet-blue hover:text-vet-dark-blue underline">
                                                Edit Article
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-12 bg-white rounded-lg shadow">
                <div class="text-6xl mb-4">📚</div>
                <h3 class="text-xl font-semibold text-gray-800 mb-2">No Articles Found</h3>
                <p class="text-gray-600 mb-4">
                    <?php if ($search_query || $topic_filter): ?>
                        No articles match your current filters. Try adjusting your search criteria.
                    <?php else: ?>
                        No articles have been posted yet. Be the first to share veterinary knowledge!
                    <?php endif; ?>
                </p>
                <p class="text-sm text-gray-400 mb-4">Debug: Articles array is empty. Check the debug information above.</p>
                <?php if ($can_post): ?>
                    <a href="post.php" class="inline-flex items-center px-4 py-2 bg-vet-blue text-white rounded-lg hover:bg-vet-dark-blue transition-colors">
                        Post First Article
                    </a>
                <?php endif; ?>
                <div class="mt-4">
                    <a href="../setup_articles_db.php" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                        Setup Articles Database
                    </a>
                </div>
            </div>
        <?php endif; ?>
        <?php endif; ?>

        <!-- Inquiries Section -->
        <?php if ($current_tab === 'inquiries'): ?>
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <!-- Success/Error Messages -->
                <?php if (isset($_GET['success'])): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                        ✅ Your inquiry has been submitted successfully! We'll get back to you soon.
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['error'])): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        ❌ Failed to submit inquiry. Please try again.
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['reply_success'])): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                        ✅ Your reply has been sent successfully!
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['reply_error'])): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        ❌ Failed to send reply. Please try again.
                    </div>
                <?php endif; ?>

                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-semibold text-gray-900">Customer Inquiries</h2>
                    <?php if (isset($_SESSION['logged_in']) && $_SESSION['user_type'] === 'user'): ?>
                        <button onclick="showInquiryForm()" class="bg-vet-blue text-white px-4 py-2 rounded-md hover:bg-vet-dark-blue transition-colors">
                            Submit New Inquiry
                        </button>
                    <?php endif; ?>
                </div>

                <?php if (!empty($inquiries)): ?>
                    <div class="space-y-4">
                        <?php foreach ($inquiries as $inquiry): ?>
                            <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition-colors">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-3 mb-2">
                                            <h3 class="font-semibold text-gray-900">
                                                <?php echo htmlspecialchars($inquiry['subject'] ?? 'No Subject'); ?>
                                            </h3>
                                            <?php echo displayStatusBadge($inquiry['status'], 'inquiry'); ?>
                                        </div>
                                        
                                        <p class="text-gray-600 mb-3">
                                            <?php echo nl2br(htmlspecialchars($inquiry['message'] ?? 'No message content')); ?>
                                        </p>
                                        
                                        <!-- Display Replies -->
                                        <?php if (!empty($inquiry['replies'])): ?>
                                            <div class="bg-gray-50 rounded-lg p-3 mb-3">
                                                <h4 class="text-sm font-medium text-gray-700 mb-2">Staff Replies:</h4>
                                                <?php foreach ($inquiry['replies'] as $reply): ?>
                                                    <div class="border-l-4 border-vet-blue pl-3 mb-2 last:mb-0">
                                                        <p class="text-sm text-gray-600">
                                                            <?php echo nl2br(htmlspecialchars($reply['reply_message'])); ?>
                                                        </p>
                                                        <div class="flex items-center justify-between text-xs text-gray-500 mt-1">
                                                            <span class="font-medium text-vet-blue">
                                                                <?php echo htmlspecialchars($reply['staff_name']); ?>
                                                            </span>
                                                            <span>
                                                                <?php echo date('M j, Y g:i A', strtotime($reply['timestamp'])); ?>
                                                            </span>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="flex items-center justify-between text-sm text-gray-500">
                                            <div class="flex items-center space-x-4">
                                                <span class="flex items-center">
                                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                                    </svg>
                                                    <?php echo htmlspecialchars($inquiry['user_name'] ?? 'Anonymous'); ?>
                                                </span>
                                                <span class="flex items-center">
                                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                    </svg>
                                                    <?php echo date('M j, Y g:i A', strtotime($inquiry['timestamp'])); ?>
                                                </span>
                                            </div>
                                            
                                            <?php if (isset($_SESSION['logged_in']) && in_array($_SESSION['user_type'], ['admin', 'staff'])): ?>
                                                <div class="flex space-x-2">
                                                    <?php if ($inquiry['status'] === 'Pending'): ?>
                                                        <button onclick="showReplyForm(<?php echo $inquiry['inquiry_id']; ?>)" 
                                                                class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                                            Reply
                                                        </button>
                                                        <button onclick="updateInquiryStatus(<?php echo $inquiry['inquiry_id']; ?>, 'Replied')" 
                                                                class="text-green-600 hover:text-green-800 text-sm font-medium">
                                                            Mark as Replied
                                                        </button>
                                                    <?php endif; ?>
                                                    <?php if ($inquiry['status'] === 'Replied'): ?>
                                                        <button onclick="showReplyForm(<?php echo $inquiry['inquiry_id']; ?>)" 
                                                                class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                                            Add Reply
                                                        </button>
                                                    <?php endif; ?>
                                                    <?php if ($inquiry['status'] !== 'Closed'): ?>
                                                        <button onclick="updateInquiryStatus(<?php echo $inquiry['inquiry_id']; ?>, 'Closed')" 
                                                                class="text-gray-600 hover:text-gray-800 text-sm font-medium">
                                                            Close
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-12">
                        <div class="text-6xl mb-4">💬</div>
                        <h3 class="text-xl font-semibold text-gray-800 mb-2">No Inquiries Found</h3>
                        <p class="text-gray-600 mb-4">No customer inquiries have been submitted yet.</p>
                        <?php if (isset($_SESSION['logged_in']) && $_SESSION['user_type'] === 'user'): ?>
                            <button onclick="showInquiryForm()" class="inline-flex items-center px-4 py-2 bg-vet-blue text-white rounded-lg hover:bg-vet-dark-blue transition-colors">
                                Submit First Inquiry
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Inquiry Form Modal -->
            <div id="inquiryModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
                <div class="flex items-center justify-center min-h-screen p-4">
                    <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">Submit New Inquiry</h3>
                            <button onclick="hideInquiryForm()" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                        
                        <form id="inquiryForm" method="POST" action="submit_inquiry.php" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Subject</label>
                                <input type="text" name="subject" required 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-vet-blue"
                                       placeholder="Brief description of your inquiry">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Message</label>
                                <textarea name="message" rows="4" required 
                                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-vet-blue"
                                          placeholder="Please describe your inquiry in detail..."></textarea>
                            </div>
                            
                            <div class="flex space-x-3">
                                <button type="button" onclick="hideInquiryForm()" 
                                        class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 transition-colors">
                                    Cancel
                                </button>
                                <button type="submit" 
                                        class="flex-1 px-4 py-2 bg-vet-blue text-white rounded-md hover:bg-vet-dark-blue transition-colors">
                                    Submit Inquiry
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Reply Form Modal -->
            <div id="replyModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
                <div class="flex items-center justify-center min-h-screen p-4">
                    <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">Reply to Inquiry</h3>
                            <button onclick="hideReplyForm()" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                        
                        <form id="replyForm" method="POST" action="submit_reply.php" class="space-y-4">
                            <input type="hidden" id="replyInquiryId" name="inquiry_id" value="">
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Your Reply</label>
                                <textarea name="reply_message" rows="4" required 
                                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-vet-blue"
                                          placeholder="Type your reply to the customer..."></textarea>
                            </div>
                            
                            <div class="flex space-x-3">
                                <button type="button" onclick="hideReplyForm()" 
                                        class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 transition-colors">
                                    Cancel
                                </button>
                                <button type="submit" 
                                        class="flex-1 px-4 py-2 bg-vet-blue text-white rounded-md hover:bg-vet-dark-blue transition-colors">
                                    Send Reply
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <style>
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .line-clamp-3 {
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
    </style>

    <script>
        // Inquiry form functions
        function showInquiryForm() {
            document.getElementById('inquiryModal').classList.remove('hidden');
        }

        function hideInquiryForm() {
            document.getElementById('inquiryModal').classList.add('hidden');
        }

        // Reply form functions
        function showReplyForm(inquiryId) {
            document.getElementById('replyInquiryId').value = inquiryId;
            document.getElementById('replyModal').classList.remove('hidden');
        }

        function hideReplyForm() {
            document.getElementById('replyModal').classList.add('hidden');
        }

        // Update inquiry status (for admin/staff)
        function updateInquiryStatus(inquiryId, status) {
            if (confirm(`Are you sure you want to mark this inquiry as '${status}'?`)) {
                fetch('update_inquiry_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `inquiry_id=${inquiryId}&status=${status}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload(); // Refresh to show updated status
                    } else {
                        alert('Failed to update inquiry status: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to update inquiry status. Please try again.');
                });
            }
        }

        // Close modals when clicking outside
        document.addEventListener('click', function(event) {
            const inquiryModal = document.getElementById('inquiryModal');
            const replyModal = document.getElementById('replyModal');
            
            if (event.target === inquiryModal) {
                hideInquiryForm();
            }
            if (event.target === replyModal) {
                hideReplyForm();
            }
        });
    </script>
</body>
</html>
