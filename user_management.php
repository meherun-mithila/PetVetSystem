<?php
session_start();
require_once 'config.php';

$clinic_name = "Caring Paws Veterinary Clinic";
$error_message = "";
$success_message = "";

// Handle form submissions
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'delete':
                handleUserDeletion($pdo);
                break;
        }
    }
}

function handleUserDeletion($pdo) {
    global $error_message, $success_message;
    
    $email = trim($_POST['delete_email'] ?? '');
    $password = $_POST['delete_password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error_message = "Please provide both email and password for account deletion.";
        return;
    }
    
    try {
        // Check if user exists and verify password
        $stmt = $pdo->prepare("SELECT user_id, name, password FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            $error_message = "No account found with this email address.";
            return;
        }
        
        // Verify password (handle both hashed and plain text)
        $password_matches = false;
        if (password_verify($password, $user['password'])) {
            $password_matches = true;
        } elseif ($user['password'] === $password) {
            $password_matches = true;
        }
        
        if (!$password_matches) {
            $error_message = "Incorrect password. Please try again.";
            return;
        }
        
        // Check for related data
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM pets WHERE user_id = ?");
        $stmt->execute([$user['user_id']]);
        $pet_count = $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE user_id = ?");
        $stmt->execute([$user['user_id']]);
        $appointment_count = $stmt->fetchColumn();
        
        if ($pet_count > 0 || $appointment_count > 0) {
            $error_message = "Cannot delete account. You have $pet_count pet(s) and $appointment_count appointment(s) associated with this account. Please contact support for assistance.";
            return;
        }
        
        // Start transaction
        $pdo->beginTransaction();
        
        try {
            // Delete verification records
            $stmt = $pdo->prepare("DELETE FROM email_verifications WHERE user_id = ?");
            $stmt->execute([$user['user_id']]);
            
            // Delete user
            $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
            $stmt->execute([$user['user_id']]);
            
            $pdo->commit();
            
            $success_message = "Account deleted successfully. We're sorry to see you go!";
            
        } catch(PDOException $e) {
            $pdo->rollBack();
            throw $e;
        }
        
    } catch(PDOException $e) {
        $error_message = "Account deletion failed. Please try again.";
        error_log("User deletion error: " . $e->getMessage());
    }
}

// Get user count for display
$user_count = 0;
$verified_count = 0;
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $user_count = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE is_verified = 1");
    $verified_count = $stmt->fetchColumn();
} catch(PDOException $e) {
    // Ignore error for display purposes
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - <?php echo $clinic_name; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-6">
                <div class="flex items-center">
                    <h1 class="text-3xl font-bold text-gray-900"><?php echo $clinic_name; ?></h1>
                </div>
                <nav class="flex space-x-8">
                    <a href="index.php" class="text-gray-500 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">Home</a>
                    <a href="auth/signup.php" class="text-blue-600 hover:text-blue-800 px-3 py-2 rounded-md text-sm font-medium">Register</a>
                    <a href="auth/delete_account.php" class="text-gray-500 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">Delete Account</a>
                    <a href="user_management.php" class="text-gray-500 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">User Management</a>
                </nav>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="px-4 py-6 sm:px-0">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">User Management System</h2>
                    <p class="mt-1 text-sm text-gray-600">Manage existing accounts</p>
                </div>
                <div class="flex space-x-4">
                    <div class="bg-blue-50 px-4 py-2 rounded-lg">
                        <span class="text-sm font-medium text-blue-800">Total Users: <?php echo $user_count; ?></span>
                    </div>
                    <div class="bg-green-50 px-4 py-2 rounded-lg">
                        <span class="text-sm font-medium text-green-800">Verified: <?php echo $verified_count; ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Messages -->
        <?php if ($error_message): ?>
            <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <!-- Account Deletion Form -->
        <div class="px-4 sm:px-0">
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Delete Account</h3>
                    <div class="bg-red-50 border border-red-200 rounded-md p-4 mb-4">
                        <p class="text-sm text-red-700">⚠️ Warning: This action cannot be undone. All your data will be permanently deleted.</p>
                    </div>

                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="delete">
                        
                        <div>
                            <label for="delete_email" class="block text-sm font-medium text-gray-700">Email Address *</label>
                            <input type="email" name="delete_email" id="delete_email" required 
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500 sm:text-sm">
                        </div>

                        <div>
                            <label for="delete_password" class="block text-sm font-medium text-gray-700">Password *</label>
                            <input type="password" name="delete_password" id="delete_password" required 
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500 sm:text-sm">
                        </div>

                        <div>
                            <button type="submit" 
                                    class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                Delete Account
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t mt-12">
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <div class="text-center text-sm text-gray-500">
                <p>&copy; <?php echo date('Y'); ?> <?php echo $clinic_name; ?>. All rights reserved.</p>
                <p class="mt-1">For support, contact us at support@caringpaws.com</p>
            </div>
        </div>
    </footer>
</body>
</html>
