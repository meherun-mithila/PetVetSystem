<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || 
    !in_array($_SESSION['user_type'], ['admin', 'staff', 'doctor'])) {
    header("Location: ../index.php");
    exit();
}

$clinic_name = "Caring Paws Veterinary Clinic";
require_once '../config.php';

$error_message = "";
$success_message = "";

$author_id = $_SESSION['user_id'];
$author_type = $_SESSION['user_type'];
$author_name = $_SESSION['user_name'] ?? $_SESSION['name'] ?? $_SESSION['email'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $summary = trim($_POST['summary'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $topic = trim($_POST['topic'] ?? '');
    $tags = trim($_POST['tags'] ?? '');
    
    if (empty($title) || empty($content)) {
        $error_message = "Title and content are required.";
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO articles (title, summary, content, topic, tags, author_id, author_type, created_at, updated_at, view_count)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), 0)
            ");
            $stmt->execute([$title, $summary, $content, $topic, $tags, $author_id, $author_type]);
            
            $success_message = "Article posted successfully!";
            
            // Clear form data after successful submission
            $title = $summary = $content = $topic = $tags = '';
        } catch (PDOException $e) {
            $error_message = "Failed to post article: " . $e->getMessage();
        }
    }
}

// Predefined topics for consistency
$predefined_topics = [
    'Pet Care Tips',
    'Veterinary Medicine',
    'Pet Nutrition',
    'Pet Behavior',
    'Emergency Care',
    'Preventive Medicine',
    'Surgery & Procedures',
    'Pet Health Issues',
    'Breed Information',
    'Vaccination',
    'Dental Care',
    'Senior Pet Care',
    'Puppy/Kitten Care',
    'Pet Safety',
    'General Veterinary'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post Article - <?php echo $clinic_name; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
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
            <h1 class="text-2xl font-bold"><?php echo $clinic_name; ?> - Post Article</h1>
            <nav class="flex items-center space-x-6">
                <a href="index.php" class="text-white hover:text-gray-200 transition-colors">Articles</a>
                <a href="../index.php" class="text-white hover:text-gray-200 transition-colors">Home</a>
                <a href="../logout.php" class="bg-red-600 px-4 py-2 rounded-lg hover:bg-red-700 transition-colors">Logout</a>
            </nav>
        </div>
    </header>

    <div class="max-w-4xl mx-auto p-6">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="mb-6">
                <h2 class="text-2xl font-bold text-gray-900 mb-2">Create New Article</h2>
                <p class="text-gray-600">Share your veterinary knowledge and expertise with pet owners.</p>
            </div>

            <?php if (!empty($error_message)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success_message)): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <?php echo htmlspecialchars($success_message); ?>
                    <a href="index.php" class="ml-2 underline">View all articles</a>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Article Title *</label>
                    <input type="text" name="title" value="<?php echo htmlspecialchars($title ?? ''); ?>" 
                           required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-vet-blue"
                           placeholder="Enter a compelling title for your article">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Summary</label>
                    <textarea name="summary" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-vet-blue"
                              placeholder="Brief summary of the article (optional but recommended)"><?php echo htmlspecialchars($summary ?? ''); ?></textarea>
                    <p class="text-sm text-gray-500 mt-1">A brief summary helps readers understand what the article is about.</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Topic</label>
                    <select name="topic" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-vet-blue">
                        <option value="">Select a topic</option>
                        <?php foreach ($predefined_topics as $topic_option): ?>
                            <option value="<?php echo htmlspecialchars($topic_option); ?>" 
                                    <?php echo (isset($topic) && $topic === $topic_option) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($topic_option); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="text-sm text-gray-500 mt-1">Choose a topic that best describes your article content.</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tags</label>
                    <input type="text" name="tags" value="<?php echo htmlspecialchars($tags ?? ''); ?>" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-vet-blue"
                           placeholder="Enter tags separated by commas (e.g., dogs, nutrition, health)">
                    <p class="text-sm text-gray-500 mt-1">Tags help readers find your article more easily.</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Article Content *</label>
                    <textarea name="content" id="content" rows="15" required 
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-vet-blue"
                              placeholder="Write your article content here..."><?php echo htmlspecialchars($content ?? ''); ?></textarea>
                    <p class="text-sm text-gray-500 mt-1">Use the rich text editor above for better formatting.</p>
                </div>

                <div class="flex items-center justify-between pt-4">
                    <div class="text-sm text-gray-600">
                        <p><strong>Author:</strong> <?php echo htmlspecialchars($author_name); ?> (<?php echo ucfirst($author_type); ?>)</p>
                        <p><strong>Date:</strong> <?php echo date('M j, Y'); ?></p>
                    </div>
                    
                    <div class="flex space-x-3">
                        <a href="index.php" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 transition-colors">
                            Cancel
                        </a>
                        <button type="submit" class="px-6 py-2 bg-vet-blue text-white rounded-md hover:bg-vet-dark-blue transition-colors">
                            Post Article
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Initialize TinyMCE for rich text editing
        tinymce.init({
            selector: '#content',
            plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount checklist mediaembed casechange export formatpainter pageembed linkchecker a11ychecker tinymcespellchecker permanentpen powerpaste advtable advcode editimage tinycomments tableofcontents footnotes mergetags autocorrect typography inlinecss',
            toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table mergetags | addcomment showcomments | spellcheckdialog a11ycheck typography | align lineheight | checklist numlist bullist indent outdent | emoticons charmap | removeformat',
            tinycomments_mode: 'embedded',
            tinycomments_author: '<?php echo htmlspecialchars($author_name); ?>',
            mergetags_list: [
                { value: 'First.Name', title: 'First Name' },
                { value: 'Email', title: 'Email' },
            ],
            height: 400,
            menubar: false,
            branding: false,
            promotion: false
        });
    </script>
</body>
</html>
