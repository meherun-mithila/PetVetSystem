<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

$clinic_name = "Caring Paws Veterinary Clinic";
require_once '../config.php';

$users = [];
$error_message = "";
$success_message = "";

// Get admin user information - use the correct session variable names
$admin_name = $_SESSION['user_name'] ?? $_SESSION['name'] ?? $_SESSION['email'] ?? 'Admin User';
$admin_id = $_SESSION['user_id'] ?? 'N/A';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                try {
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
                    $stmt->execute([$_POST['email']]);
                    if ($stmt->fetchColumn() > 0) {
                        $error_message = "Email already exists!";
                    } else {
                        $stmt = $pdo->prepare("
                            INSERT INTO users (name, email, phone, password, address)
                            VALUES (?, ?, ?, ?, ?)
                        ");
                        $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                        $stmt->execute([
                            $_POST['name'],
                            $_POST['email'],
                            $_POST['phone'],
                            $hashed_password,
                            $_POST['address']
                        ]);
                        $success_message = "User added successfully!";
                    }
                } catch(PDOException $e) {
                    $error_message = "Failed to add user: " . $e->getMessage();
                }
                break;
                
            case 'edit':
                try {
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND user_id != ?");
                    $stmt->execute([$_POST['email'], $_POST['user_id']]);
                    if ($stmt->fetchColumn() > 0) {
                        $error_message = "Email already exists!";
                    } else {
                        if (!empty($_POST['password'])) {
                            $stmt = $pdo->prepare("
                                UPDATE users 
                                SET name = ?, email = ?, phone = ?, password = ?, address = ?
                                WHERE user_id = ?
                            ");
                            $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                            $stmt->execute([
                                $_POST['name'],
                                $_POST['email'],
                                $_POST['phone'],
                                $hashed_password,
                                $_POST['address'],
                                $_POST['user_id']
                            ]);
                        } else {
                            $stmt = $pdo->prepare("
                                UPDATE users 
                                SET name = ?, email = ?, phone = ?, address = ?
                                WHERE user_id = ?
                            ");
                            $stmt->execute([
                                $_POST['name'],
                                $_POST['email'],
                                $_POST['phone'],
                                $_POST['address'],
                                $_POST['user_id']
                            ]);
                        }
                        $success_message = "User updated successfully!";
                    }
                } catch(PDOException $e) {
                    $error_message = "Failed to update user: " . $e->getMessage();
                }
                break;
                
            case 'delete':
                try {
                    // Check if user has any pets
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM patients WHERE owner_id = ?");
                    $stmt->execute([$_POST['user_id']]);
                    $pet_count = $stmt->fetchColumn();
                    
                    // Proceed with deletion regardless, but mention pets if any
                    $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
                    $stmt->execute([$_POST['user_id']]);
                    $success_message = $pet_count > 0
                        ? "User deleted successfully! Note: {$pet_count} pet(s) were linked and should be reassigned."
                        : "User deleted successfully!";
                } catch(PDOException $e) {
                    $error_message = "Failed to delete user: " . $e->getMessage();
                }
                break;
        }
    }
}

// Pagination settings
$users_per_page = isset($_GET['size']) ? (int)$_GET['size'] : 10;
$users_per_page = in_array($users_per_page, [10, 25, 50, 100]) ? $users_per_page : 10; // Validate page size
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$current_page = max(1, $current_page); // Ensure page is at least 1

try {
         // Get total count of all users
     $count_stmt = $pdo->prepare("SELECT COUNT(*) as total FROM users");
     $count_stmt->execute();
     $total_users = $count_stmt->fetchColumn();
     
     $total_pages = ceil($total_users / $users_per_page);
     $current_page = min($current_page, $total_pages); // Ensure page doesn't exceed total
     $offset = ($current_page - 1) * $users_per_page;
     
     // Show all users with their pet counts with pagination
     $stmt = $pdo->prepare(
         "SELECT u.user_id, u.name, u.email, u.phone, u.address, 
                 COALESCE(p.pet_count, 0) AS pet_count
          FROM users u
          LEFT JOIN (
              SELECT owner_id, COUNT(*) AS pet_count
              FROM patients
              GROUP BY owner_id
          ) p ON p.owner_id = u.user_id
          ORDER BY u.user_id DESC
          LIMIT " . (int)$users_per_page . " OFFSET " . (int)$offset
     );
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error_message = "Failed to load users: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users Management - <?php echo $clinic_name; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <header class="bg-blue-600 text-white p-4">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold"><?php echo $clinic_name; ?> - Users</h1>
            <div class="flex items-center space-x-4">
                <span class="text-sm">Welcome, <?php echo htmlspecialchars($admin_name); ?></span>
            <a href="dashboard.php" class="bg-blue-700 px-4 py-2 rounded">Dashboard</a>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto p-6">
                          <div class="flex justify-between items-center mb-6">
              <h2 class="text-3xl font-bold">Users Management</h2>
              <p class="text-gray-600 mt-2">Showing all users with their pet information</p>
             <div class="flex items-center space-x-4">
                 <!-- Page Size Selector -->
                 <div class="flex items-center space-x-2">
                     <label class="text-sm text-gray-700">Show:</label>
                     <select onchange="changePageSize(this.value)" class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                         <option value="10" <?php echo $users_per_page == 10 ? 'selected' : ''; ?>>10</option>
                         <option value="25" <?php echo $users_per_page == 25 ? 'selected' : ''; ?>>25</option>
                         <option value="50" <?php echo $users_per_page == 50 ? 'selected' : ''; ?>>50</option>
                         <option value="100" <?php echo $users_per_page == 100 ? 'selected' : ''; ?>>100</option>
                     </select>
                     <span class="text-sm text-gray-700">per page</span>
                 </div>
                 <button onclick="showAddForm()" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                     Add New User
                 </button>
             </div>
         </div>
        
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

        <!-- Add User Form -->
        <div id="addUserForm" class="hidden bg-white rounded-lg shadow p-6 mb-6">
            <h3 class="text-xl font-semibold mb-4">Add New User</h3>
            <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <input type="hidden" name="action" value="add">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                    <input type="text" name="name" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" name="email" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                    <input type="tel" name="phone" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <input type="password" name="password" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                    <textarea name="address" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                </div>
                
                <div class="md:col-span-2 flex gap-2">
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                        Add User
                    </button>
                    <button type="button" onclick="hideAddForm()" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                        Cancel
                    </button>
                </div>
            </form>
        </div>

        <!-- Edit User Form -->
        <div id="editUserForm" class="hidden bg-white rounded-lg shadow p-6 mb-6">
            <h3 class="text-xl font-semibold mb-4">Edit User</h3>
            <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="user_id" id="edit_user_id">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                    <input type="text" name="name" id="edit_name" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" name="email" id="edit_email" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                    <input type="tel" name="phone" id="edit_phone" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">New Password (leave blank to keep current)</label>
                    <input type="password" name="password" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                    <textarea name="address" id="edit_address" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                </div>
                
                <div class="md:col-span-2 flex gap-2">
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                        Update User
                    </button>
                    <button type="button" onclick="hideEditForm()" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                        Cancel
                    </button>
                </div>
            </form>
        </div>

        <?php if (!empty($users)): ?>
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <table class="min-w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Phone</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Address</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pets</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach($users as $user): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo htmlspecialchars($user['user_id']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                <?php echo htmlspecialchars($user['name']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo htmlspecialchars($user['email']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                <div class="max-w-xs truncate">
                                <?php echo htmlspecialchars($user['address'] ?? 'N/A'); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                <div class="text-xs">
                                    <?php if ($user['pet_count'] > 0): ?>
                                        <span class="inline-block bg-blue-100 text-blue-800 px-2 py-1 rounded">
                                            <?php echo $user['pet_count']; ?> Pet<?php echo $user['pet_count'] > 1 ? 's' : ''; ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-gray-400">0 Pets</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <button onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)" 
                                        class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
                                <button onclick="deleteUser(<?php echo $user['user_id']; ?>)" 
                                        class="text-red-600 hover:text-red-900">
                                    Delete
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
             
             <!-- Pagination Controls -->
             <?php if ($total_pages > 1): ?>
                 <div class="bg-white px-6 py-4 border-t border-gray-200">
                     <div class="flex items-center justify-between">
                         <div class="text-sm text-gray-700">
                             Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $users_per_page, $total_users); ?> of <?php echo $total_users; ?> users
                         </div>
                         <div class="flex items-center space-x-2">
                             <!-- Previous Page -->
                             <?php if ($current_page > 1): ?>
                                 <a href="?page=<?php echo $current_page - 1; ?>&size=<?php echo $users_per_page; ?>" 
                                    class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                     Previous
                                 </a>
                             <?php else: ?>
                                 <span class="px-3 py-2 text-sm font-medium text-gray-300 bg-gray-100 border border-gray-200 rounded-md cursor-not-allowed">
                                     Previous
                                 </span>
                             <?php endif; ?>
                             
                             <!-- Page Numbers -->
                             <?php
                             $start_page = max(1, $current_page - 2);
                             $end_page = min($total_pages, $current_page + 2);
                             
                             if ($start_page > 1): ?>
                                 <a href="?page=1&size=<?php echo $users_per_page; ?>" class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                     1
                                 </a>
                                 <?php if ($start_page > 2): ?>
                                     <span class="px-2 py-2 text-sm text-gray-500">...</span>
                                 <?php endif; ?>
                             <?php endif; ?>
                             
                             <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                 <?php if ($i == $current_page): ?>
                                     <span class="px-3 py-2 text-sm font-medium text-white bg-blue-600 border border-blue-600 rounded-md">
                                         <?php echo $i; ?>
                                     </span>
                                 <?php else: ?>
                                     <a href="?page=<?php echo $i; ?>&size=<?php echo $users_per_page; ?>" 
                                        class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                         <?php echo $i; ?>
                                     </a>
                                 <?php endif; ?>
                             <?php endfor; ?>
                             
                             <?php if ($end_page < $total_pages): ?>
                                 <?php if ($end_page < $total_pages - 1): ?>
                                     <span class="px-2 py-2 text-sm text-gray-500">...</span>
                                 <?php endif; ?>
                                 <a href="?page=<?php echo $total_pages; ?>&size=<?php echo $users_per_page; ?>" 
                                    class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                     <?php echo $total_pages; ?>
                                 </a>
                             <?php endif; ?>
                             
                             <!-- Next Page -->
                             <?php if ($current_page < $total_pages): ?>
                                 <a href="?page=<?php echo $current_page + 1; ?>&size=<?php echo $users_per_page; ?>" 
                                    class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                     Next
                                 </a>
                             <?php else: ?>
                                 <span class="px-3 py-2 text-sm font-medium text-gray-300 bg-gray-100 border border-gray-200 rounded-md cursor-not-allowed">
                                     Next
                                 </span>
                             <?php endif; ?>
                         </div>
                     </div>
                 </div>
             <?php endif; ?>
        <?php else: ?>
            <div class="text-center py-12">
                <p class="text-gray-500">No users found.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3 text-center">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Confirm Delete</h3>
                <p class="text-sm text-gray-500 mb-4">Are you sure you want to delete this user? This action cannot be undone.</p>
                <div class="flex justify-center space-x-4">
                    <form method="POST" id="deleteForm">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="user_id" id="delete_user_id">
                        <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
                            Delete
                        </button>
                    </form>
                    <button onclick="hideDeleteModal()" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showAddForm() {
            document.getElementById('addUserForm').classList.remove('hidden');
            document.getElementById('editUserForm').classList.add('hidden');
        }
        
        function hideAddForm() {
            document.getElementById('addUserForm').classList.add('hidden');
        }
        
        function editUser(user) {
            document.getElementById('edit_user_id').value = user.user_id;
            document.getElementById('edit_name').value = user.name;
            document.getElementById('edit_email').value = user.email;
            document.getElementById('edit_phone').value = user.phone || '';
            document.getElementById('edit_address').value = user.address || '';
            
            document.getElementById('addUserForm').classList.add('hidden');
            document.getElementById('editUserForm').classList.remove('hidden');
        }
        
        function hideEditForm() {
            document.getElementById('editUserForm').classList.add('hidden');
        }
        
        function deleteUser(userId) {
            document.getElementById('delete_user_id').value = userId;
            document.getElementById('deleteModal').classList.remove('hidden');
        }
        
        function hideDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
        }
        
        function changePageSize(size) {
            // Redirect to first page with new size
            window.location.href = '?page=1&size=' + size;
        }
        
        // Auto-hide success/error messages after 5 seconds
        setTimeout(function() {
            const messages = document.querySelectorAll('.bg-green-100, .bg-red-100');
            messages.forEach(function(message) {
                message.style.display = 'none';
            });
        }, 5000);
    </script>
</body>
</html> 
