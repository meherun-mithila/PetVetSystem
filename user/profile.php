<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_type'] !== 'user') {
    header("Location: ../index.php");
    exit();
}

$clinic_name = "Caring Paws Veterinary Clinic";
require_once '../config.php';
require_once __DIR__ . '/../auth_system/bootstrap.php';

$user_id = $_SESSION['user_id'];
$user_data = [];
$error_message = "";
$success_message = "";
$verify_message = '';
$verify_error = '';

// Handle form submissions (profile update and post-login email verification via OTP)
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
    } elseif (isset($_POST['action']) && $_POST['action'] === 'send_otp') {
        // Send OTP to the current user's email
        try {
            $email = trim($_SESSION['user_email']);
            $userId = (int)$_SESSION['user_id'];
            if ($email) {
                $otp = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                $expiresAt = (new DateTime('+10 minutes'))->format('Y-m-d H:i:s');
                $insOtp = $pdo->prepare('INSERT INTO email_otps (user_id, email, otp_code, expires_at, is_used, attempts) VALUES (?, ?, ?, ?, 0, 0)');
                $insOtp->execute([$userId, $email, $otp, $expiresAt]);
                $sent = sendOtpEmail($email, $otp);
                if ($sent) {
                    $verify_message = 'We sent a 6-digit code to your email.';
                } else {
                    $verify_error = 'Failed to send email. Please check your email address or try again later.';
                }
            } else {
                $verify_error = 'No email associated with your account.';
            }
        } catch (Throwable $e) {
            $verify_error = 'Failed to send code. Please try again.';
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'verify_otp') {
        $code = trim($_POST['otp'] ?? '');
        $email = trim($_SESSION['user_email']);
        if ($code === '') {
            $verify_error = 'Enter the 6-digit code.';
        } else {
            try {
                $stmt = $pdo->prepare('SELECT id, user_id, otp_code, is_used, attempts, expires_at FROM email_otps WHERE email = ? ORDER BY id DESC LIMIT 1');
                $stmt->execute([$email]);
                $otpRow = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$otpRow) {
                    $verify_error = 'No OTP found. Please request a new code.';
                } else {
                    $now = new DateTime();
                    $expiresAt = $otpRow['expires_at'] ? new DateTime($otpRow['expires_at']) : null;
                    if ((int)$otpRow['is_used'] === 1) {
                        $verify_error = 'This code was already used. Request a new one.';
                    } elseif ($expiresAt && $now > $expiresAt) {
                        $verify_error = 'Code expired. Request a new one.';
                    } elseif ($otpRow['otp_code'] !== $code) {
                        $upd = $pdo->prepare('UPDATE email_otps SET attempts = attempts + 1 WHERE id = ?');
                        $upd->execute([$otpRow['id']]);
                        $verify_error = 'Invalid code. Try again.';
                    } else {
                        $pdo->beginTransaction();
                        try {
                            $upd = $pdo->prepare('UPDATE email_otps SET is_used = 1 WHERE id = ?');
                            $upd->execute([$otpRow['id']]);
                            try {
                                $u = $pdo->prepare('UPDATE users SET is_verified = 1 WHERE user_id = ?');
                                $u->execute([(int)$otpRow['user_id']]);
                            } catch (PDOException $e) { }
                            $pdo->commit();
                            $verify_message = 'Email verified successfully!';
                        } catch (Throwable $e) {
                            $pdo->rollBack();
                            $verify_error = 'Failed to verify. Please try again.';
                        }
                    }
                }
            } catch (Throwable $e) {
                $verify_error = 'Verification failed. Please try later.';
            }
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'delete_account') {
        $confirm = isset($_POST['confirm_delete']) ? (bool)$_POST['confirm_delete'] : false;
        $password = $_POST['delete_password'] ?? '';
        if (!$confirm) {
            $error_message = 'Please confirm you want to permanently delete your account.';
        } elseif ($password === '') {
            $error_message = 'Please enter your current password to delete your account.';
        } else {
            try {
                // Fetch user for password validation
                $stmt = $pdo->prepare('SELECT user_id, password, email FROM users WHERE user_id = ?');
                $stmt->execute([$user_id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$user) {
                    $error_message = 'User not found.';
                } else {
                    $valid = password_verify($password, $user['password']) || $password === $user['password'];
                    if (!$valid) {
                        $error_message = 'Incorrect password.';
                    } else {
                        // Allow account deletion anytime - remove restrictions
                        $pdo->beginTransaction();
                        try {
                            // Delete related OTPs
                            $delOtps = $pdo->prepare('DELETE FROM email_otps WHERE user_id = ?');
                            $delOtps->execute([$user_id]);

                            // Delete any legacy verification records if table exists
                            try {
                                $delVer = $pdo->prepare('DELETE FROM email_verifications WHERE user_id = ?');
                                $delVer->execute([$user_id]);
                            } catch (Throwable $e) { /* table may not exist */ }

                            // Delete pets and appointments first (cascade delete)
                            try {
                                $delPets = $pdo->prepare('DELETE FROM patients WHERE owner_id = ?');
                                $delPets->execute([$user_id]);
                            } catch (Throwable $e) { }

                            try {
                                $delAppts = $pdo->prepare('DELETE FROM appointments a JOIN patients p ON a.patient_id = p.patient_id WHERE p.owner_id = ?');
                                $delAppts->execute([$user_id]);
                            } catch (Throwable $e) { }

                            // Finally delete the user
                            $delUser = $pdo->prepare('DELETE FROM users WHERE user_id = ?');
                            $delUser->execute([$user_id]);

                            $pdo->commit();

                            // Logout and redirect
                            session_destroy();
                            session_start();
                            $_SESSION['verification_success'] = 'Account deleted successfully.';
                            header('Location: ../index.php');
                            exit;
                        } catch (Throwable $e) {
                            $pdo->rollBack();
                            $error_message = 'Account deletion failed. Please try again later.';
                        }
                    }
                }
            } catch (Throwable $e) {
                $error_message = 'An error occurred. Please try again later.';
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
        <?php
        // Fetch current verified status
        try {
            $vstmt = $pdo->prepare('SELECT is_verified, email FROM users WHERE user_id = ?');
            $vstmt->execute([$user_id]);
            $vdata = $vstmt->fetch(PDO::FETCH_ASSOC) ?: ['is_verified' => 0, 'email' => ''];
        } catch (PDOException $e) { $vdata = ['is_verified' => 0, 'email' => '']; }
        ?>

        <?php if (!empty($verify_error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6"><?php echo htmlspecialchars($verify_error); ?></div>
        <?php endif; ?>
        <?php if (!empty($verify_message)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6"><?php echo htmlspecialchars($verify_message); ?></div>
        <?php endif; ?>

        <?php if ((int)($vdata['is_verified'] ?? 0) !== 1): ?>
        <div class="mb-8 bg-yellow-50 border border-yellow-200 rounded-lg p-6">
            <h2 class="text-lg font-semibold text-yellow-900 mb-2">Verify your email</h2>
            <p class="text-sm text-yellow-800 mb-4">Your email (<?php echo htmlspecialchars($vdata['email']); ?>) is not verified. Verify to secure your account and enable all features.</p>
            <form method="POST" class="flex flex-col sm:flex-row gap-3">
                <input type="hidden" name="action" value="send_otp">
                <button type="submit" class="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded">Send Code</button>
            </form>
            <form method="POST" class="mt-3 flex flex-col sm:flex-row gap-3">
                <input type="hidden" name="action" value="verify_otp">
                <input type="text" name="otp" maxlength="6" pattern="\d{6}" placeholder="Enter 6-digit code" class="px-3 py-2 border border-yellow-300 rounded w-full sm:w-64" required>
                <button type="submit" class="bg-vet-blue hover:bg-vet-dark-blue text-white px-4 py-2 rounded">Verify</button>
            </form>
        </div>
        <?php endif; ?>
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

        <!-- Delete Account -->
        <div class="mt-8 bg-white rounded-lg shadow-md p-6 border border-red-200">
            <h2 class="text-xl font-bold text-red-700 mb-2">Delete Account</h2>
            <p class="text-sm text-gray-600 mb-4">Deleting your account is permanent and cannot be undone.</p>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="delete_account">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Confirm Password</label>
                    <input type="password" name="delete_password" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent" required>
                </div>
                <label class="flex items-center text-sm text-gray-700">
                    <input type="checkbox" name="confirm_delete" value="1" class="w-4 h-4 text-red-600 border-gray-300 rounded focus:ring-red-500 mr-2" required>
                    I understand this action cannot be undone and all my data will be permanently deleted.
                </label>
                <div>
                    <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded-lg">Delete My Account</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html> 