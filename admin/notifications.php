<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

$clinic_name = "Caring Paws Veterinary Clinic";
require_once '../config.php';

$admin_name = $_SESSION['user_name'] ?? $_SESSION['name'] ?? ($_SESSION['user_email'] ?? 'Admin');

// Handle form submission for new notifications
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_notification') {
    $message = trim($_POST['message'] ?? '');
    $type = trim($_POST['type'] ?? 'general');
    $audience = $_POST['audience'] ?? 'all';
    $target_user_id = $_POST['target_user_id'] ?? null;
    $target_staff_id = $_POST['target_staff_id'] ?? null;
    
    if (empty($message)) {
        $error_message = 'Notification message is required.';
    } else {
        try {
            // Check if staff_id column exists in notifications table
            $hasStaffId = false;
            try {
                $pdo->query("SELECT staff_id FROM notifications LIMIT 0");
                $hasStaffId = true;
            } catch (PDOException $e) {
                // staff_id column doesn't exist
                $hasStaffId = false;
            }
            
            if ($hasStaffId) {
                // Insert with staff_id column
                $stmt = $pdo->prepare("INSERT INTO notifications (user_id, staff_id, message, type, audience, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$target_user_id, $target_staff_id, $message, $type, $audience]);
            } else {
                // Insert without staff_id column (fallback for existing tables)
                $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message, type, audience, created_at) VALUES (?, ?, ?, ?, NOW())");
                $stmt->execute([$target_user_id, $message, $type, $audience]);
            }
            
            $success_message = 'Notification sent successfully!';
            
            // Clear form data
            $_POST = array();
        } catch (PDOException $e) {
            $error_message = 'Failed to send notification: ' . $e->getMessage();
        }
    }
}

// Fetch users for targeting specific notifications
$users = [];
$users_error = '';
try {
    $stmt = $pdo->prepare("SELECT user_id, name as full_name, email FROM users ORDER BY name");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $users_error = 'Failed to load users: ' . $e->getMessage();
    // Users table might not exist, continue without user targeting
}

// Fetch staff for targeting specific notifications
$staff = [];
$staff_error = '';
try {
    $stmt = $pdo->prepare("SELECT staff_id, name as full_name, email FROM staff ORDER BY name");
    $stmt->execute();
    $staff = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $staff_error = 'Failed to load staff: ' . $e->getMessage();
    // Staff table might not exist, continue without staff targeting
}

// Fetch notifications with read stats
$notifications = [];
try {
    $stmt = $pdo->prepare("SELECT n.*, 
        SUM(CASE WHEN nr.reader_type='user' THEN 1 ELSE 0 END) AS user_reads,
        SUM(CASE WHEN nr.reader_type='staff' THEN 1 ELSE 0 END) AS staff_reads,
        SUM(CASE WHEN nr.reader_type='admin' THEN 1 ELSE 0 END) AS admin_reads
        FROM notifications n
        LEFT JOIN notification_reads nr ON nr.notification_id = n.notification_id
        GROUP BY n.notification_id
        ORDER BY n.created_at DESC");
    $stmt->execute();
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = 'Failed to load notifications: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - <?php echo $clinic_name; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        function updateTargetFields() {
            const audience = document.getElementById('audience').value;
            const userTarget = document.getElementById('user_target_section');
            const staffTarget = document.getElementById('staff_target_section');
            
            if (audience === 'specific_user') {
                userTarget.style.display = 'block';
                staffTarget.style.display = 'none';
            } else if (audience === 'specific_staff') {
                userTarget.style.display = 'none';
                staffTarget.style.display = 'block';
            } else {
                userTarget.style.display = 'none';
                staffTarget.style.display = 'none';
            }
        }
    </script>
</head>
<body class="bg-gray-100">
    <header class="bg-blue-600 text-white p-4">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold"><?php echo $clinic_name; ?> - Notifications</h1>
            <div class="flex items-center space-x-4">
                <span class="text-sm">Welcome, <?php echo htmlspecialchars($admin_name); ?></span>
                <a href="dashboard.php" class="bg-blue-700 px-4 py-2 rounded">Dashboard</a>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto p-6 space-y-6">
        <!-- Success/Error Messages -->
        <?php if ($success_message): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>





        <!-- Send New Notification Form -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-xl font-semibold">Send New Notification</h2>
            </div>
            <div class="p-6">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="send_notification">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="message" class="block text-sm font-medium text-gray-700 mb-2">Notification Message *</label>
                            <textarea 
                                id="message" 
                                name="message" 
                                rows="4" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                placeholder="Enter your notification message here..."
                                required
                            ><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="space-y-4">
                            <div>
                                <label for="type" class="block text-sm font-medium text-gray-700 mb-2">Notification Type</label>
                                <select id="type" name="type" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="general" <?php echo ($_POST['type'] ?? '') === 'general' ? 'selected' : ''; ?>>General</option>
                                    <option value="appointment" <?php echo ($_POST['type'] ?? '') === 'appointment' ? 'selected' : ''; ?>>Appointment</option>
                                    <option value="medical" <?php echo ($_POST['type'] ?? '') === 'medical' ? 'selected' : ''; ?>>Medical</option>
                                    <option value="vaccine" <?php echo ($_POST['type'] ?? '') === 'vaccine' ? 'selected' : ''; ?>>Vaccine</option>
                                    <option value="adoption" <?php echo ($_POST['type'] ?? '') === 'adoption' ? 'selected' : ''; ?>>Adoption</option>
                                    <option value="billing" <?php echo ($_POST['type'] ?? '') === 'billing' ? 'selected' : ''; ?>>Billing</option>
                                    <option value="urgent" <?php echo ($_POST['type'] ?? '') === 'urgent' ? 'selected' : ''; ?>>Urgent</option>
                                </select>
                            </div>
                            
                            <div>
                                <label for="audience" class="block text-sm font-medium text-gray-700 mb-2">Target Audience</label>
                                <select id="audience" name="audience" onchange="updateTargetFields()" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="all" <?php echo ($_POST['audience'] ?? '') === 'all' ? 'selected' : ''; ?>>All Users & Staff</option>
                                    <option value="user" <?php echo ($_POST['audience'] ?? '') === 'user' ? 'selected' : ''; ?>>All Users Only</option>
                                    <option value="staff" <?php echo ($_POST['audience'] ?? '') === 'staff' ? 'selected' : ''; ?>>All Staff Only</option>
                                    <option value="specific_user" <?php echo ($_POST['audience'] ?? '') === 'specific_user' ? 'selected' : ''; ?>>Specific User</option>
                                    <option value="specific_staff" <?php echo ($_POST['audience'] ?? '') === 'specific_staff' ? 'selected' : ''; ?>>Specific Staff Member</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Specific User Target Section -->
                    <div id="user_target_section" class="mt-4" style="display: none;">
                        <label for="target_user_id" class="block text-sm font-medium text-gray-700 mb-2">Select User</label>
                        <?php if ($users_error): ?>
                            <div class="text-red-600 text-sm mb-2"><?php echo htmlspecialchars($users_error); ?></div>
                        <?php elseif (empty($users)): ?>
                            <div class="text-gray-500 text-sm mb-2">No users found in the system.</div>
                        <?php else: ?>
                            <select id="target_user_id" name="target_user_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select a user...</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?php echo $user['user_id']; ?>" <?php echo ($_POST['target_user_id'] ?? '') == $user['user_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($user['full_name'] . ' (' . $user['email'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Specific Staff Target Section -->
                    <div id="staff_target_section" class="mt-4" style="display: none;">
                        <label for="target_staff_id" class="block text-sm font-medium text-gray-700 mb-2">Select Staff Member</label>
                        <?php if ($staff_error): ?>
                            <div class="text-red-600 text-sm mb-2"><?php echo htmlspecialchars($staff_error); ?></div>
                        <?php elseif (empty($staff)): ?>
                            <div class="text-gray-500 text-sm mb-2">No staff members found in the system.</div>
                        <?php else: ?>
                            <select id="target_staff_id" name="target_staff_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select a staff member...</option>
                                <?php foreach ($staff as $staff_member): ?>
                                    <option value="<?php echo $staff_member['staff_id']; ?>" <?php echo ($_POST['target_staff_id'] ?? '') == $staff_member['staff_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($staff_member['full_name'] . ' (' . $staff_member['email'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mt-6">
                        <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            Send Notification
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Recent Notifications -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <h2 class="text-xl font-semibold">Recent Notifications</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Message</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Audience</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Target</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reads</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach($notifications as $n): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm text-gray-900"><?php echo htmlspecialchars($n['message']); ?></td>
                            <td class="px-6 py-4 text-sm text-gray-500"><?php echo htmlspecialchars($n['type']); ?></td>
                            <td class="px-6 py-4 text-sm text-gray-500"><?php echo htmlspecialchars($n['audience'] ?? 'user'); ?></td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                <?php 
                                if ($n['user_id']) {
                                    echo 'User #' . (int)$n['user_id'];
                                } elseif (isset($n['staff_id']) && $n['staff_id']) {
                                    echo 'Staff #' . (int)$n['staff_id'];
                                } elseif ($n['audience'] === 'specific_user' || $n['audience'] === 'specific_staff') {
                                    echo 'Specific Target';
                                } else {
                                    echo 'Broadcast';
                                }
                                ?>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <span class="mr-2">Users: <?php echo (int)($n['user_reads'] ?? 0); ?></span>
                                <span class="mr-2">Staff: <?php echo (int)($n['staff_reads'] ?? 0); ?></span>
                                <span>Admins: <?php echo (int)($n['admin_reads'] ?? 0); ?></span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500"><?php echo date('M j, Y g:i A', strtotime($n['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Initialize target field visibility on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateTargetFields();
        });
    </script>
</body>
</html>
