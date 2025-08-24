<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

require_once '../includes/status_helper.php';

$clinic_name = "Caring Paws Veterinary Clinic";
require_once '../config.php';

$stats = [];
$admins = [];
$notifications = [];
$error_message = "";
$success_message = "";

// Get admin user information - use the correct session variable names
$admin_name = $_SESSION['user_name'] ?? $_SESSION['name'] ?? $_SESSION['email'] ?? 'Admin User';
$admin_email = $_SESSION['user_email'] ?? $_SESSION['email'] ?? 'N/A';
$admin_id = $_SESSION['user_id'] ?? 'N/A';

// Handle admin management form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_admin':
                try {
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM admin WHERE email = ?");
                    $stmt->execute([$_POST['email']]);
                    if ($stmt->fetchColumn() > 0) {
                        $error_message = "Email already exists!";
                } else {
                        $stmt = $pdo->prepare("
                            INSERT INTO admin (name, email, password)
                            VALUES (?, ?, ?)
                        ");
                        $stmt->execute([
                            $_POST['name'],
                            $_POST['email'],
                            $_POST['password'] // Note: In production, this should be hashed
                        ]);
                        $success_message = "Admin added successfully!";
                    }
                } catch(PDOException $e) {
                    $error_message = "Failed to add admin: " . $e->getMessage();
                }
                break;
                
            case 'edit_admin':
                try {
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM admin WHERE email = ? AND admin_id != ?");
                    $stmt->execute([$_POST['email'], $_POST['admin_id']]);
                    if ($stmt->fetchColumn() > 0) {
                        $error_message = "Email already exists!";
                    } else {
                        if (!empty($_POST['password'])) {
                            $stmt = $pdo->prepare("
                                UPDATE admin 
                                SET name = ?, email = ?, password = ?
                                WHERE admin_id = ?
                            ");
                            $stmt->execute([
                                $_POST['name'],
                                $_POST['email'],
                                $_POST['password'], // Note: In production, this should be hashed
                                $_POST['admin_id']
                            ]);
                        } else {
                            $stmt = $pdo->prepare("
                                UPDATE admin 
                                SET name = ?, email = ?
                                WHERE admin_id = ?
                            ");
                            $stmt->execute([
                                $_POST['name'],
                                $_POST['email'],
                                $_POST['admin_id']
                            ]);
                        }
                        $success_message = "Admin updated successfully!";
                }
            } catch(PDOException $e) {
                    $error_message = "Failed to update admin: " . $e->getMessage();
                }
                break;
                
            case 'delete_admin':
                try {
                    if ($_POST['admin_id'] == $_SESSION['user_id']) {
                        $error_message = "You cannot delete your own account!";
                    } else {
                $stmt = $pdo->prepare("DELETE FROM admin WHERE admin_id = ?");
                        $stmt->execute([$_POST['admin_id']]);
                        $success_message = "Admin deleted successfully!";
                    }
            } catch(PDOException $e) {
                    $error_message = "Failed to delete admin: " . $e->getMessage();
                }
                break;
        }
    }
}

try {
    // Get statistics - work with existing database structure
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_users FROM users");
    $stmt->execute();
    $stats['total_users'] = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_doctors FROM doctors");
    $stmt->execute();
    $stats['total_doctors'] = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_patients FROM patients");
    $stmt->execute();
    $stats['total_patients'] = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_staff FROM staff");
    $stmt->execute();
    $stats['total_staff'] = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_appointments FROM appointments");
    $stmt->execute();
    $stats['total_appointments'] = $stmt->fetchColumn();
    
    // Check if appointments table has status column, if not use a default count
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as pending_appointments FROM appointments WHERE status = 'pending'");
        $stmt->execute();
        $stats['pending_appointments'] = $stmt->fetchColumn();
    } catch(PDOException $e) {
        // If status column doesn't exist, set pending to 0
        $stats['pending_appointments'] = 0;
    }
    
    // Get all admin users
    $stmt = $pdo->prepare("SELECT * FROM admin ORDER BY admin_id DESC");
    $stmt->execute();
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get recent notifications with read status
    try {
        $stmt = $pdo->prepare("
            SELECT n.*, 
                   COALESCE(u.name, 'System') as recipient_name,
                   COALESCE(u.email, 'system@clinic.com') as recipient_email,
                   COUNT(nr.read_id) as read_count,
                   GROUP_CONCAT(CONCAT(CASE 
                       WHEN nr.reader_type = 'user' THEN CONCAT('User: ', u2.name)
                       WHEN nr.reader_type = 'staff' THEN CONCAT('Staff: ', s.name)
                       WHEN nr.reader_type = 'admin' THEN CONCAT('Admin: ', a.name)
                   END) SEPARATOR ', ') as read_by
            FROM notifications n
            LEFT JOIN users u ON n.user_id = u.user_id
            LEFT JOIN notification_reads nr ON n.notification_id = nr.notification_id
            LEFT JOIN users u2 ON nr.reader_type = 'user' AND nr.reader_id = u2.user_id
            LEFT JOIN staff s ON nr.reader_type = 'staff' AND nr.reader_id = s.staff_id
            LEFT JOIN admin a ON nr.reader_type = 'admin' AND nr.reader_id = a.admin_id
            GROUP BY n.notification_id
            ORDER BY n.notification_id DESC
            LIMIT 10
        ");
        $stmt->execute();
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $t) {
        // Fallback: try to get notifications without read status
        try {
    $stmt = $pdo->prepare("
                SELECT n.*, 
                       COALESCE(u.name, 'System') as recipient_name,
                       COALESCE(u.email, 'system@clinic.com') as recipient_email,
                       0 as read_count,
                       'N/A' as read_by
                FROM notifications n
                LEFT JOIN users u ON n.user_id = u.user_id
                ORDER BY n.notification_id DESC
                LIMIT 10
    ");
    $stmt->execute();
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $t2) {
            $notifications = [];
        }
    }
    
} catch(PDOException $e) {
    $error_message = "Failed to load dashboard data: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo $clinic_name; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-100">
    <header class="bg-blue-600 text-white p-4">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold"><?php echo $clinic_name; ?> - Admin Dashboard</h1>
                <div class="flex items-center space-x-4">
                <span class="text-sm">Welcome, <?php echo htmlspecialchars($admin_name); ?></span>
                <a href="../logout.php" class="bg-red-600 px-4 py-2 rounded hover:bg-red-700">Logout</a>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto p-6">
        <h2 class="text-3xl font-bold mb-6">Dashboard Overview</h2>
        
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

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Users</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo $stats['total_users'] ?? 0; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Doctors</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo $stats['total_doctors'] ?? 0; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Patients</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo $stats['total_patients'] ?? 0; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Staff</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo $stats['total_staff'] ?? 0; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
                <div class="space-y-3">
                    <a href="users.php" class="block w-full bg-blue-600 text-white text-center px-4 py-2 rounded hover:bg-blue-700">
                        Manage Users
                    </a>
                    <a href="doctors.php" class="block w-full bg-green-600 text-white text-center px-4 py-2 rounded hover:bg-green-700">
                        Manage Doctors
                    </a>
                    <a href="patients.php" class="block w-full bg-purple-600 text-white text-center px-4 py-2 rounded hover:bg-purple-700">
                        Manage Patients
                    </a>
                    <a href="staff.php" class="block w-full bg-yellow-600 text-white text-center px-4 py-2 rounded hover:bg-yellow-700">
                        Manage Staff
                    </a>
                    <a href="adoption.php" class="block w-full bg-pink-600 text-white text-center px-4 py-2 rounded hover:bg-pink-700">
                        Manage Adoption
                    </a>
                    <button onclick="showArticlesModal()" class="block w-full bg-indigo-600 text-white text-center px-4 py-2 rounded hover:bg-indigo-700">
                        Articles & Inquiries
                    </button>
                </div>
                                    </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Appointments</h3>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Total Appointments:</span>
                        <span class="font-semibold"><?php echo $stats['total_appointments'] ?? 0; ?></span>
                                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Pending:</span>
                        <span class="font-semibold text-yellow-600"><?php echo $stats['pending_appointments'] ?? 0; ?></span>
                                </div>
                    <a href="appointments.php" class="block w-full bg-indigo-600 text-white text-center px-4 py-2 rounded hover:bg-indigo-700">
                        View All Appointments
                    </a>
                            </div>
                        </div>

            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">System</h3>
                <div class="space-y-3">
                    <a href="reports.php" class="block w-full bg-gray-600 text-white text-center px-4 py-2 rounded hover:bg-gray-700">
                        Generate Reports
                    </a>
                    <a href="medical_records.php" class="block w-full bg-red-600 text-white text-center px-4 py-2 rounded hover:bg-red-700">
                        Medical Records
                    </a>
                    <a href="vaccine_records.php" class="block w-full bg-orange-600 text-white text-center px-4 py-2 rounded hover:bg-orange-700">
                         Vaccine Records
                    </a>
                    <a href="verified_emails.php" class="block w-full bg-indigo-600 text-white text-center px-4 py-2 rounded hover:bg-indigo-700">
                        View Verified Emails
                    </a>
                    <button onclick="showSystemInfo()" class="block w-full bg-teal-600 text-white text-center px-4 py-2 rounded hover:bg-teal-700">
                        System Info
                    </button>
                    
                    <a href="notifications.php" class="block w-full bg-purple-600 text-white text-center px-4 py-2 rounded hover:bg-purple-700 relative">
                        Notifications
                        <?php if (count($notifications) > 0): ?>
                            <span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full h-6 w-6 flex items-center justify-center">
                                <?php echo count($notifications); ?>
                            </span>
                    <?php endif; ?>
                    </a>
                </div>
            </div>


        </div>

        <!-- Admin Management Section -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-900">Admin Management</h3>
                <button onclick="showAddAdminForm()" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                    Add New Admin
                </button>
            </div>
            
            <!-- Add Admin Form -->
            <div id="addAdminForm" class="hidden px-6 py-4 border-b border-gray-200">
                <h4 class="text-md font-semibold text-gray-900 mb-4">Add New Admin</h4>
                <form method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <input type="hidden" name="action" value="add_admin">
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                        <input type="text" name="name" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" name="email" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                        <input type="password" name="password" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div class="md:col-span-3 flex gap-2">
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                            Add Admin
                        </button>
                        <button type="button" onclick="hideAddAdminForm()" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>

            <!-- Edit Admin Form -->
            <div id="editAdminForm" class="hidden px-6 py-4 border-b border-gray-200">
                <h4 class="text-md font-semibold text-gray-900 mb-4">Edit Admin</h4>
                <form method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <input type="hidden" name="action" value="edit_admin">
                    <input type="hidden" name="admin_id" id="edit_admin_id">
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                        <input type="text" name="name" id="edit_admin_name" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                    
                                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" name="email" id="edit_admin_email" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">New Password (leave blank to keep current)</label>
                        <input type="password" name="password" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    </div>
                    
                    <div class="md:col-span-3 flex gap-2">
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                            Update Admin
                        </button>
                        <button type="button" onclick="hideEditAdminForm()" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                            Cancel
                        </button>
                                </div>
                </form>
                            </div>

            <!-- Admin List -->
            <div class="overflow-x-auto">
                <?php if (!empty($admins)): ?>
                    <table class="min-w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach($admins as $admin_user): ?>
                            <tr class="hover:bg-gray-50 <?php echo $admin_user['admin_id'] == $_SESSION['user_id'] ? 'bg-blue-50' : ''; ?>">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo htmlspecialchars($admin_user['admin_id']); ?>
                                    <?php if ($admin_user['admin_id'] == $_SESSION['user_id']): ?>
                                        <span class="ml-2 text-xs text-blue-600">(You)</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($admin_user['name']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo htmlspecialchars($admin_user['email']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button onclick="editAdmin(<?php echo htmlspecialchars(json_encode($admin_user)); ?>)" 
                                            class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
                                    <?php if ($admin_user['admin_id'] != $_SESSION['user_id']): ?>
                                        <button onclick="deleteAdmin(<?php echo $admin_user['admin_id']; ?>)" 
                                                class="text-red-600 hover:text-red-900">Delete</button>
                                    <?php else: ?>
                                        <span class="text-gray-400">Delete</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="text-center py-12">
                        <p class="text-gray-500">No admin users found.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3 text-center">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Confirm Delete</h3>
                <p class="text-sm text-gray-500 mb-4">Are you sure you want to delete this admin? This action cannot be undone.</p>
                <div class="flex justify-center space-x-4">
                    <form method="POST" id="deleteForm">
                        <input type="hidden" name="action" value="delete_admin">
                        <input type="hidden" name="admin_id" id="delete_admin_id">
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

    <!-- System Info Modal -->
    <div id="systemInfoModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-4/5 max-w-4xl shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4 text-center">System Information</h3>
                

                
                <!-- System Details Section -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div class="text-left text-sm text-gray-600 space-y-2">
                        <h4 class="font-semibold text-gray-800 mb-3">System Details</h4>
                        <p><strong>PHP Version:</strong> <?php echo phpversion(); ?></p>
                        <p><strong>Server:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></p>
                        <p><strong>Database:</strong> MySQL/PDO</p>
                        <p><strong>Session:</strong> Active</p>
                        <p><strong>Admin User:</strong> <?php echo htmlspecialchars($admin_name); ?></p>
                        <p><strong>Admin Email:</strong> <?php echo htmlspecialchars($admin_email); ?></p>
                        <p><strong>Admin ID:</strong> <?php echo htmlspecialchars($admin_id); ?></p>
                        <p><strong>Session ID:</strong> <?php echo htmlspecialchars(session_id()); ?></p>
                        <p><strong>Login Time:</strong> <?php echo isset($_SESSION['login_time']) ? date('M j, Y g:i A', $_SESSION['login_time']) : 'N/A'; ?></p>
                    </div>
                    
                    <div class="text-left text-sm text-gray-600 space-y-2">
                        <h4 class="font-semibold text-gray-800 mb-3">Session Variables</h4>
                        <div class="bg-gray-100 p-3 rounded text-xs">
                            <?php 
                            echo "logged_in: " . ($_SESSION['logged_in'] ?? 'Not set') . "<br>";
                            echo "user_type: " . ($_SESSION['user_type'] ?? 'Not set') . "<br>";
                            echo "user_id: " . ($_SESSION['user_id'] ?? 'Not set') . "<br>";
                            echo "user_name: " . ($_SESSION['user_name'] ?? 'Not set') . "<br>";
                            echo "user_email: " . ($_SESSION['user_email'] ?? 'Not set') . "<br>";
                            echo "name: " . ($_SESSION['name'] ?? 'Not set') . "<br>";
                            echo "email: " . ($_SESSION['email'] ?? 'Not set');
                            ?>
                        </div>
                    </div>
                </div>
                

                
                <div class="mt-6 text-center">
                    <button onclick="hideSystemInfo()" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Articles & Inquiries Modal -->
    <div id="articlesModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-6xl w-full max-h-screen overflow-y-auto">
                <div class="flex items-center justify-between p-6 border-b border-gray-200">
                    <h3 class="text-2xl font-semibold text-gray-900">📚 Articles & Inquiries Management</h3>
                    <button onclick="hideArticlesModal()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <div class="p-6">
                    <!-- Tab Navigation -->
                    <div class="border-b border-gray-200 mb-6">
                        <nav class="flex space-x-8" aria-label="Tabs">
                            <button onclick="switchTab('articles')" id="articlesTab" class="border-b-2 border-indigo-500 text-indigo-600 whitespace-nowrap py-4 px-1 text-sm font-medium">
                                📖 Articles
                            </button>
                            <button onclick="switchTab('inquiries')" id="inquiriesTab" class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 text-sm font-medium">
                                💬 Inquiries
                            </button>
                        </nav>
                    </div>

                    <!-- Articles Tab Content -->
                    <div id="articlesContent" class="space-y-4">
                        <div class="flex justify-between items-center mb-4">
                            <h4 class="text-lg font-semibold text-gray-900">Manage Articles</h4>
                            <button onclick="showAddArticleForm()" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">
                                + Add New Article
                            </button>
                        </div>
                        
                        <div id="articlesList" class="space-y-3">
                            <!-- Articles will be loaded here -->
                            <div class="text-center py-8">
                                <div class="text-4xl mb-2">📚</div>
                                <p class="text-gray-500">Loading articles...</p>
                            </div>
                        </div>
                    </div>

                    <!-- Inquiries Tab Content -->
                    <div id="inquiriesContent" class="hidden space-y-4">
                        <div class="flex justify-between items-center mb-4">
                            <h4 class="text-lg font-semibold text-gray-900">Manage Inquiries</h4>
                        </div>
                        
                        <div id="inquiriesList" class="space-y-3">
                            <!-- Inquiries will be loaded here -->
                            <div class="text-center py-8">
                                <div class="text-4xl mb-2">💬</div>
                                <p class="text-gray-500">Loading inquiries...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Article Modal -->
    <div id="addArticleModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-screen overflow-y-auto">
                <div class="flex items-center justify-between p-6 border-b border-gray-200">
                    <h3 class="text-xl font-semibold text-gray-900">Add New Article</h3>
                    <button onclick="hideAddArticleForm()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
            <div class="p-6">
                    <form id="addArticleForm" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Article Title *</label>
                            <input type="text" id="articleTitle" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Enter article title">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Summary</label>
                            <textarea id="articleSummary" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Brief summary of the article"></textarea>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Topic</label>
                            <select id="articleTopic" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="">Select a topic</option>
                                <option value="Pet Care Tips">Pet Care Tips</option>
                                <option value="Veterinary Medicine">Veterinary Medicine</option>
                                <option value="Pet Nutrition">Pet Nutrition</option>
                                <option value="Pet Behavior">Pet Behavior</option>
                                <option value="Emergency Care">Emergency Care</option>
                                <option value="Preventive Medicine">Preventive Medicine</option>
                                <option value="Surgery & Procedures">Surgery & Procedures</option>
                                <option value="Pet Health Issues">Pet Health Issues</option>
                                <option value="Breed Information">Breed Information</option>
                                <option value="Vaccination">Vaccination</option>
                                <option value="Dental Care">Dental Care</option>
                                <option value="Senior Pet Care">Senior Pet Care</option>
                                <option value="Puppy/Kitten Care">Puppy/Kitten Care</option>
                                <option value="Pet Safety">Pet Safety</option>
                                <option value="General Veterinary">General Veterinary</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tags</label>
                            <input type="text" id="articleTags" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Enter tags separated by commas">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Article Content *</label>
                            <textarea id="articleContent" rows="10" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Write your article content here..."></textarea>
                        </div>
                        
                        <div class="flex space-x-3 pt-4">
                            <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded hover:bg-indigo-700">
                                Publish Article
                            </button>
                            <button type="button" onclick="hideAddArticleForm()" class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showAddAdminForm() {
            document.getElementById('addAdminForm').classList.remove('hidden');
            document.getElementById('editAdminForm').classList.add('hidden');
        }
        
        function hideAddAdminForm() {
            document.getElementById('addAdminForm').classList.add('hidden');
        }
        
        function editAdmin(admin) {
            document.getElementById('edit_admin_id').value = admin.admin_id;
            document.getElementById('edit_admin_name').value = admin.name;
            document.getElementById('edit_admin_email').value = admin.email;
            
            document.getElementById('addAdminForm').classList.add('hidden');
            document.getElementById('editAdminForm').classList.remove('hidden');
        }
        
        function hideEditAdminForm() {
            document.getElementById('editAdminForm').classList.add('hidden');
        }
        
        function deleteAdmin(adminId) {
            document.getElementById('delete_admin_id').value = adminId;
            document.getElementById('deleteModal').classList.remove('hidden');
        }
        
        function hideDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
        }
        
        function showSystemInfo() {
            document.getElementById('systemInfoModal').classList.remove('hidden');
        }
        
        function hideSystemInfo() {
            document.getElementById('systemInfoModal').classList.add('hidden');
        }
        
        // Articles & Inquiries Modal Functions
        function showArticlesModal() {
            document.getElementById('articlesModal').classList.remove('hidden');
            loadArticles();
            loadInquiries();
        }
        
        function hideArticlesModal() {
            document.getElementById('articlesModal').classList.add('hidden');
            document.getElementById('addArticleModal').classList.add('hidden');
        }
        
        function switchTab(tab) {
            if (tab === 'articles') {
                document.getElementById('articlesContent').classList.remove('hidden');
                document.getElementById('inquiriesContent').classList.add('hidden');
                document.getElementById('articlesTab').classList.add('border-indigo-500', 'text-indigo-600');
                document.getElementById('articlesTab').classList.remove('border-transparent', 'text-gray-500');
                document.getElementById('inquiriesTab').classList.remove('border-indigo-500', 'text-indigo-600');
                document.getElementById('inquiriesTab').classList.add('border-transparent', 'text-gray-500');
            } else {
                document.getElementById('inquiriesContent').classList.remove('hidden');
                document.getElementById('articlesContent').classList.add('hidden');
                document.getElementById('inquiriesTab').classList.add('border-indigo-500', 'text-indigo-600');
                document.getElementById('inquiriesTab').classList.remove('border-transparent', 'text-gray-500');
                document.getElementById('articlesTab').classList.remove('border-indigo-500', 'text-indigo-600');
                document.getElementById('articlesTab').classList.add('border-transparent', 'text-gray-500');
            }
        }
        
        function showAddArticleForm() {
            document.getElementById('addArticleModal').classList.remove('hidden');
        }
        
        function hideAddArticleForm() {
            document.getElementById('addArticleModal').classList.add('hidden');
        }
        
        function loadArticles() {
            fetch('../articles/api/get_articles.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayArticles(data.articles);
                    } else {
                        document.getElementById('articlesList').innerHTML = '<div class="text-center py-8"><p class="text-red-500">Failed to load articles</p></div>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('articlesList').innerHTML = '<div class="text-center py-8"><p class="text-red-500">Error loading articles</p></div>';
                });
        }
        
        function loadInquiries() {
            fetch('../articles/api/get_inquiries.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayInquiries(data.inquiries);
                    } else {
                        document.getElementById('inquiriesList').innerHTML = '<div class="text-center py-8"><p class="text-red-500">Failed to load inquiries</p></div>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('inquiriesList').innerHTML = '<div class="text-center py-8"><p class="text-red-500">Error loading inquiries</p></div>';
                });
        }
        
        function displayArticles(articles) {
            const container = document.getElementById('articlesList');
            if (articles.length === 0) {
                container.innerHTML = '<div class="text-center py-8"><p class="text-gray-500">No articles found</p></div>';
                return;
            }
            
            container.innerHTML = articles.map(article => `
                <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50">
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <h5 class="font-semibold text-gray-900 mb-2">${article.title}</h5>
                            <p class="text-sm text-gray-600 mb-2">${article.summary || 'No summary'}</p>
                            <div class="flex items-center space-x-4 text-xs text-gray-500">
                                <span>Topic: ${article.topic || 'General'}</span>
                                <span>Views: ${article.view_count || 0}</span>
                                <span>Created: ${new Date(article.created_at).toLocaleDateString()}</span>
                            </div>
                        </div>
                        <div class="flex space-x-2 ml-4">
                            <button onclick="editArticle(${article.article_id})" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                Edit
                            </button>
                            <button onclick="deleteArticle(${article.article_id})" class="text-red-600 hover:text-red-800 text-sm font-medium">
                                Delete
                            </button>
                        </div>
                    </div>
                </div>
            `).join('');
        }
        
        function displayInquiries(inquiries) {
            const container = document.getElementById('inquiriesList');
            if (inquiries.length === 0) {
                container.innerHTML = '<div class="text-center py-8"><p class="text-gray-500">No inquiries found</p></div>';
                return;
            }
            
            container.innerHTML = inquiries.map(inquiry => `
                <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50">
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <h5 class="font-semibold text-gray-900 mb-2">${inquiry.subject}</h5>
                            <p class="text-sm text-gray-600 mb-2">${inquiry.message}</p>
                            <div class="flex items-center space-x-4 text-xs text-gray-500">
                                <span>From: ${inquiry.user_name || 'Anonymous'}</span>
                                <span>Status: <span class="px-2 py-1 rounded-full ${getStatusClass(inquiry.status)}">${inquiry.status}</span></span>
                                <span>Date: ${new Date(inquiry.timestamp).toLocaleDateString()}</span>
                            </div>
                        </div>
                        <div class="flex space-x-2 ml-4">
                            <button onclick="replyToInquiry(${inquiry.inquiry_id})" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                Reply
                            </button>
                            <button onclick="updateInquiryStatus(${inquiry.inquiry_id}, '${inquiry.status === 'Pending' ? 'Replied' : 'Closed'}')" class="text-green-600 hover:text-green-800 text-sm font-medium">
                                ${inquiry.status === 'Pending' ? 'Mark Replied' : 'Close'}
                            </button>
                        </div>
                    </div>
                </div>
            `).join('');
        }
        
        <?php echo getStatusClassJS(); ?>
        
        // Handle form submission
        document.getElementById('addArticleForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData();
            formData.append('title', document.getElementById('articleTitle').value);
            formData.append('summary', document.getElementById('articleSummary').value);
            formData.append('topic', document.getElementById('articleTopic').value);
            formData.append('tags', document.getElementById('articleTags').value);
            formData.append('content', document.getElementById('articleContent').value);
            
            fetch('../articles/api/add_article.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Article added successfully!');
                    hideAddArticleForm();
                    loadArticles();
                    // Clear form
                    document.getElementById('addArticleForm').reset();
                } else {
                    alert('Failed to add article: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error adding article');
            });
        });
        
        // Auto-refresh dashboard every 30 seconds
        setTimeout(function() {
            location.reload();
        }, 30000);
    </script>
</body>
</html> 