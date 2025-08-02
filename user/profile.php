<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_type'] !== 'user') {
    header("Location: ../index.php");
    exit();
}

$clinic_name = "Caring Paws Veterinary Clinic";
require_once '../config.php';

$user_id = $_SESSION['user_id'];
$user_data = [];
$error_message = "";
$success_message = "";

// Handle form submissions
if ($_POST) {
    if (isset($_POST['action']) && $_POST['action'] === 'update') {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $address = trim($_POST['address']);
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if (empty($name) || empty($email)) {
            $error_message = "Name and email are required.";
        } else {
            try {
                // Check if email already exists for another user
                $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
                $stmt->execute([$email, $user_id]);
                if ($stmt->fetch()) {
                    $error_message = "Email already exists.";
                } else {
                    // Update basic info
                    $stmt = $pdo->prepare("
                        UPDATE users 
                        SET name = ?, email = ?, phone = ?, address = ?
                        WHERE user_id = ?
                    ");
                    $stmt->execute([$name, $email, $phone, $address, $user_id]);
                    
                    // Update password if provided
                    if (!empty($current_password) && !empty($new_password)) {
                        // Verify current password
                        $stmt = $pdo->prepare("SELECT password FROM users WHERE user_id = ?");
                        $stmt->execute([$user_id]);
                        $current_hash = $stmt->fetchColumn();
                        
                        if ($current_hash === $current_password) {
                            if ($new_password === $confirm_password) {
                                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                                $stmt->execute([$new_password, $user_id]);
                                $success_message = "Profile and password updated successfully!";
                            } else {
                                $error_message = "New passwords do not match.";
                            }
                        } else {
                            $error_message = "Current password is incorrect.";
                        }
                    } else {
                        $success_message = "Profile updated successfully!";
                    }
                    
                    // Update session data
                    $_SESSION['user_name'] = $name;
                    $_SESSION['user_email'] = $email;
                }
            } catch(PDOException $e) {
                $error_message = "Failed to update profile.";
            }
        }
    }
}

// Get user data
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error_message = "Failed to load profile.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - <?php echo $clinic_name; ?></title>
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
    <header class="bg-gradient-to-r from-vet-blue to-vet-dark-blue text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-6 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center">
                    <span class="text-2xl mr-3">🐾</span>
                    <div>
                        <h1 class="text-xl font-bold"><?php echo $clinic_name; ?></h1>
                        <p class="text-blue-200 text-sm">Pet Owner Dashboard</p>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="text-right">
                        <p class="font-semibold"><?php echo htmlspecialchars($_SESSION['user_name']); ?></p>
                        <p class="text-blue-200 text-sm">Pet Owner</p>
                    </div>
                    <a href="../index.php?logout=1" class="bg-red-500 hover:bg-red-600 px-4 py-2 rounded-lg transition-colors">
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Navigation -->
    <nav class="bg-white shadow-md">
        <div class="max-w-7xl mx-auto px-6 py-3">
            <div class="flex space-x-8">
                <a href="dashboard.php" class="text-gray-600 hover:text-vet-blue transition-colors">Dashboard</a>
                <a href="pets.php" class="text-gray-600 hover:text-vet-blue transition-colors">My Pets</a>
                <a href="appointments.php" class="text-gray-600 hover:text-vet-blue transition-colors">Appointments</a>
                <a href="medical_records.php" class="text-gray-600 hover:text-vet-blue transition-colors">Medical Records</a>
                <a href="profile.php" class="text-vet-blue font-semibold border-b-2 border-vet-blue pb-2">Profile</a>
            </div>
        </div>
    </nav>

    <div class="max-w-4xl mx-auto px-6 py-8">
        <!-- Messages -->
        <?php if (!empty($error_message)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800">My Profile</h1>
            <p class="text-gray-600">Manage your personal information and account settings</p>
        </div>

        <!-- Profile Form -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-6">Personal Information</h2>
            <form method="POST" class="space-y-6">
                <input type="hidden" name="action" value="update">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Full Name *</label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($user_data['name'] ?? ''); ?>" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-vet-blue focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Email Address *</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($user_data['email'] ?? ''); ?>" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-vet-blue focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                        <input type="tel" name="phone" value="<?php echo htmlspecialchars($user_data['phone'] ?? ''); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-vet-blue focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Address</label>
                        <input type="text" name="address" value="<?php echo htmlspecialchars($user_data['address'] ?? ''); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-vet-blue focus:border-transparent">
                    </div>
                </div>

                <hr class="my-8">

                <h3 class="text-lg font-semibold text-gray-800 mb-4">Change Password</h3>
                <p class="text-sm text-gray-600 mb-4">Leave password fields empty if you don't want to change your password.</p>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Current Password</label>
                        <input type="password" name="current_password" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-vet-blue focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
                        <input type="password" name="new_password" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-vet-blue focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-vet-blue focus:border-transparent">
                    </div>
                </div>

                <div class="flex justify-end space-x-4">
                    <button type="submit" class="bg-vet-blue hover:bg-vet-dark-blue text-white px-6 py-2 rounded-lg transition-colors">
                        Update Profile
                    </button>
                </div>
            </form>
        </div>

        <!-- Account Information -->
        <div class="mt-8 bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-6">Account Information</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h3 class="font-semibold text-gray-700 mb-2">Account Details</h3>
                    <div class="space-y-2 text-sm text-gray-600">
                        <p><strong>User ID:</strong> <?php echo $user_data['user_id'] ?? 'N/A'; ?></p>
                        <p><strong>Account Type:</strong> Pet Owner</p>
                        <p><strong>Member Since:</strong> <?php echo date('M j, Y', strtotime($user_data['created_at'] ?? 'now')); ?></p>
                    </div>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-700 mb-2">Quick Actions</h3>
                    <div class="space-y-2">
                        <a href="pets.php" class="block text-vet-blue hover:text-vet-dark-blue transition-colors">Manage My Pets</a>
                        <a href="appointments.php" class="block text-vet-blue hover:text-vet-dark-blue transition-colors">View Appointments</a>
                        <a href="medical_records.php" class="block text-vet-blue hover:text-vet-dark-blue transition-colors">Medical Records</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 