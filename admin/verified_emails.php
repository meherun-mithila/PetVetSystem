<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

require_once '../config.php';

$clinic_name = "Caring Paws Veterinary Clinic";

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Search functionality
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_condition = '';
$search_params = [];

if (!empty($search)) {
    $search_condition = "AND (name LIKE ? OR email LIKE ?)";
    $search_params = ["%$search%", "%$search%"];
}

try {
    // Get users with search functionality
    $query = "SELECT user_id, name, email FROM users WHERE user_type = 'user' $search_condition ORDER BY user_id DESC LIMIT $per_page OFFSET $offset";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($search_params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total count with search
    $count_query = "SELECT COUNT(*) FROM users WHERE user_type = 'user' $search_condition";
    $count_stmt = $pdo->prepare($count_query);
    $count_stmt->execute($search_params);
    $total_users = $count_stmt->fetchColumn();
    
    $total_pages = ceil($total_users / $per_page);
    
    // Debug: Log what we found
    error_log("Found " . count($users) . " users in verified_emails.php with search: '$search'");
    
} catch (PDOException $e) {
    // If the first query fails, try a simpler approach
    try {
        if (!empty($search)) {
            $simple_query = "SELECT user_id, name, email FROM users WHERE name LIKE ? OR email LIKE ? LIMIT $per_page OFFSET $offset";
        } else {
            $simple_query = "SELECT user_id, name, email FROM users LIMIT $per_page OFFSET $offset";
        }
        $stmt = $pdo->prepare($simple_query);
        $stmt->execute($search_params);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($search)) {
            $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE name LIKE ? OR email LIKE ?");
        } else {
            $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM users");
        }
        $count_stmt->execute($search_params);
        $total_users = $count_stmt->fetchColumn();
        $total_pages = ceil($total_users / $per_page);
        
        error_log("Using fallback query, found " . count($users) . " users with search: '$search'");
        
    } catch (PDOException $e2) {
        $error_message = "Database error: " . $e2->getMessage();
        error_log("Database error in verified_emails.php: " . $e2->getMessage());
        $users = [];
        $total_users = 0;
        $total_pages = 0;
    }
}

// Export functionality
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="verified_emails_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['User ID', 'Name', 'Email', 'Verification Status']);
    
    foreach ($users as $user) {
        fputcsv($output, [
            $user['user_id'],
            $user['name'],
            $user['email'],
            'Verified'
        ]);
    }
    
    fclose($output);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verified Emails - <?php echo $clinic_name; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'vet-blue': '#2c5aa0',
                        'vet-dark-blue': '#1e3d72'
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-100">
    <!-- Header -->
    <header class="bg-gradient-to-r from-vet-blue to-vet-dark-blue text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-6 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center">
                    <span class="text-2xl mr-3">🐾</span>
                    <div>
                        <h1 class="text-xl font-bold"><?php echo $clinic_name; ?></h1>
                        <p class="text-blue-200 text-sm">Admin Dashboard - Verified Emails</p>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="dashboard.php" class="bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded-lg transition-colors">
                        ← Back to Dashboard
                    </a>
                    <a href="../index.php?logout=1" class="bg-red-500 hover:bg-red-600 px-4 py-2 rounded-lg transition-colors">
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto px-6 py-8">
        <!-- Page Header -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex justify-between items-center mb-4">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">📧 Verified Email Addresses</h2>
                    <p class="text-gray-600">Manage and view all verified user accounts</p>
                </div>
                <div class="flex items-center space-x-3">
                    <span class="text-sm text-gray-600">
                        Total Users: <span class="font-semibold text-green-600"><?php echo $total_users; ?></span>
                    </span>
                    <a href="?export=csv<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                       class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition-colors">
                        📥 Export CSV
                    </a>
                </div>
            </div>

            <!-- Search Bar -->
            <form method="GET" class="flex space-x-3">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="Search by name or email..." 
                       class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-vet-blue focus:border-transparent">
                <button type="submit" class="bg-vet-blue hover:bg-vet-dark-blue text-white px-6 py-2 rounded-lg transition-colors">
                    🔍 Search
                </button>
                <?php if (!empty($search)): ?>
                    <a href="verified_emails.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition-colors">
                        Clear
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Users Table -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Verification Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                                    <div class="text-4xl mb-2">📭</div>
                                    <p class="text-lg">No users found</p>
                                    <?php if (!empty($search)): ?>
                                        <p class="text-sm mt-2">Try adjusting your search terms</p>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        #<?php echo $user['user_id']; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($user['name']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($user['email']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            ✅ Verified
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                    <div class="flex-1 flex justify-between sm:hidden">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                               class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                Previous
                            </a>
                        <?php endif; ?>
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                               class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                Next
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm text-gray-700">
                                Showing <span class="font-medium"><?php echo $offset + 1; ?></span> to 
                                <span class="font-medium"><?php echo min($offset + $per_page, $total_users); ?></span> of 
                                <span class="font-medium"><?php echo $total_users; ?></span> results
                            </p>
                        </div>
                        <div>
                            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                                       class="relative inline-flex items-center px-4 py-2 border text-sm font-medium 
                                              <?php echo $i === $page ? 'z-10 bg-vet-blue border-vet-blue text-white' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>
                            </nav>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>


</body>
</html>
