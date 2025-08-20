<?php
session_start();

// Handle logout first, before any output
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

$clinic_name = "Caring Paws Veterinary Clinic";
$error_message = "";
$success_message = "";

// Check for verification success message
if (isset($_SESSION['verification_success'])) {
    $success_message = $_SESSION['verification_success'];
    unset($_SESSION['verification_success']);
}

require_once 'config.php';

// Email verification is now handled post-login on the user profile

// Handle login form submission
if ($_POST && !$error_message) {
    $login_email = trim($_POST['email'] ?? '');
    $login_password = $_POST['password'] ?? '';
    $user_type = $_POST['user_type'] ?? 'user';
    
    // Validate input
    if (empty($login_email) || empty($login_password)) {
        $error_message = "Please fill in all fields";
    } else {
        try {
            $user_found = false;
            $user_data = null;
            
            // Check based on user type
            if ($user_type === 'admin') {
                // Check admin table
                $stmt = $pdo->prepare("SELECT admin_id as id, name, email, password FROM admin WHERE email = ?");
                $stmt->execute([$login_email]);
                $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user_data && $user_data['password'] === $login_password) {
                    $user_found = true;
                    $_SESSION['user_type'] = 'admin';
                }
            } elseif ($user_type === 'staff') {
                // Check staff table
                $stmt = $pdo->prepare("SELECT staff_id as id, name, email, password, role FROM staff WHERE email = ?");
                $stmt->execute([$login_email]);
                $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user_data && $user_data['password'] === $login_password) {
                    $user_found = true;
                    $_SESSION['user_type'] = 'staff';
                    $_SESSION['role'] = $user_data['role'];
                }
            } else {
                // Check users table (verification handled after login)
                $stmt = $pdo->prepare("SELECT user_id as id, name, email, password, phone, address, is_verified FROM users WHERE email = ?");
                $stmt->execute([$login_email]);
                $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user_data) {
                    // Check if password matches (handle both hashed and plain text)
                    $password_matches = false;
                    
                    // First try password_verify for hashed passwords
                    if (password_verify($login_password, $user_data['password'])) {
                        $password_matches = true;
                    }
                    // Fallback to plain text comparison for old passwords
                    elseif ($user_data['password'] === $login_password) {
                        $password_matches = true;
                    }
                    
                    if ($password_matches) {
                        $user_found = true;
                        $_SESSION['user_type'] = 'user';
                    }
                }
            }
            
            if ($user_found) {
                // Set session variables
                $_SESSION['logged_in'] = true;
                $_SESSION['user_id'] = $user_data['id'];
                $_SESSION['user_name'] = $user_data['name'];
                $_SESSION['user_email'] = $user_data['email'];
                
                // Redirect based on user type
                if ($_SESSION['user_type'] === 'admin') {
                    header("Location: admin/dashboard.php");
                    exit();
                } elseif ($_SESSION['user_type'] === 'staff') {
                    header("Location: staff_member/dashboard.php");
                    exit();
                } else {
                    header("Location: user/dashboard.php");
                    exit();
                }
            } else {
                if (empty($error_message)) {
                    $error_message = "Invalid email or password";
                }
            }
            
        } catch(PDOException $e) {
            $error_message = "Login failed. Please try again.";
        }
    }
}

// Pre-login verification links and resend flow removed per new design

function createVerificationTable($pdo) {
    try {
        $sql = "CREATE TABLE IF NOT EXISTS email_verifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            email VARCHAR(255) NOT NULL,
            verification_token VARCHAR(255) NOT NULL,
            is_verified TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            expires_at TIMESTAMP DEFAULT (CURRENT_TIMESTAMP + INTERVAL 24 HOUR),
            INDEX (verification_token),
            INDEX (email),
            INDEX (user_id)
        )";
        $pdo->exec($sql);
        
        // Add is_verified column to users table if it doesn't exist
        try {
            $pdo->exec("ALTER TABLE users ADD COLUMN is_verified TINYINT(1) DEFAULT 0");
        } catch(PDOException $e) {
            // Column might already exist, ignore error
            if (strpos($e->getMessage(), 'Duplicate column name') === false) {
                error_log("Error adding is_verified column: " . $e->getMessage());
            }
        }
        
    } catch(PDOException $e) {
        error_log("Error creating verification table: " . $e->getMessage());
    }
}

function handleEmailVerification($pdo, $token) {
    global $error_message, $success_message;
    
    try {
        // Validate token format
        if (empty($token) || strlen($token) !== 64) {
            $error_message = "Invalid verification link format.";
            return;
        }
        
        // Find verification record
        $stmt = $pdo->prepare("
            SELECT v.*, u.name, u.email, u.user_id
            FROM email_verifications v 
            JOIN users u ON v.user_id = u.user_id 
            WHERE v.verification_token = ? AND v.is_verified = 0
        ");
        $stmt->execute([$token]);
        $verification = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$verification) {
            $error_message = "Invalid or expired verification link.";
            return;
        }
        
        // Check if token has expired
        $stmt = $pdo->prepare("SELECT * FROM email_verifications WHERE verification_token = ? AND expires_at > NOW()");
        $stmt->execute([$token]);
        if (!$stmt->fetch()) {
            $error_message = "Verification link has expired. Please request a new one.";
            return;
        }
        
        // Start transaction for data consistency
        $pdo->beginTransaction();
        
        try {
            // Mark user as verified
            $stmt = $pdo->prepare("UPDATE users SET is_verified = 1 WHERE user_id = ?");
            $stmt->execute([$verification['user_id']]);
            
            // Mark verification as complete
            $stmt = $pdo->prepare("UPDATE email_verifications SET is_verified = 1 WHERE verification_token = ?");
            $stmt->execute([$token]);
            
            // Commit transaction
            $pdo->commit();
            
            $success_message = "Email verified successfully! Welcome to Caring Paws Veterinary Clinic. You can now login with your email and password.";
            
        } catch(PDOException $e) {
            // Rollback on error
            $pdo->rollBack();
            throw $e;
        }
        
    } catch(PDOException $e) {
        $error_message = "Verification failed. Please try again.";
        error_log("Email verification error: " . $e->getMessage());
    }
}

function handleResendVerification($pdo) {
    global $error_message, $success_message;
    
    $email = trim($_POST['resend_email'] ?? '');
    
    if (empty($email)) {
        $error_message = "Please enter your email address.";
        return;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address.";
        return;
    }
    
    try {
        // Check if user exists
        $stmt = $pdo->prepare("SELECT user_id, name FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            $error_message = "No account found with this email address.";
            return;
        }
        
        // Check if user is already verified
        $stmt = $pdo->prepare("SELECT is_verified FROM users WHERE user_id = ?");
        $stmt->execute([$user['user_id']]);
        $user_status = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user_status['is_verified'] == 1) {
            $error_message = "This account is already verified. You can login directly.";
            return;
        }
        
        // Generate new verification token
        $verification_token = bin2hex(random_bytes(32));
        
        // Check if verification record already exists
        $stmt = $pdo->prepare("SELECT id FROM email_verifications WHERE user_id = ?");
        $stmt->execute([$user['user_id']]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            // Update existing record
            $stmt = $pdo->prepare("
                UPDATE email_verifications 
                SET verification_token = ?, created_at = CURRENT_TIMESTAMP, expires_at = (CURRENT_TIMESTAMP + INTERVAL 24 HOUR)
                WHERE user_id = ?
            ");
            $stmt->execute([$verification_token, $user['user_id']]);
        } else {
            // Insert new record
            $stmt = $pdo->prepare("
                INSERT INTO email_verifications (user_id, email, verification_token) 
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$user['user_id'], $email, $verification_token]);
        }
        
        // Send verification email
        if (sendVerificationEmail($email, $user['name'], $verification_token)) {
            $success_message = "Verification email sent successfully! Please check your inbox and click the verification link.";
        } else {
            $error_message = "Failed to send verification email. Please try again or contact support.";
        }
        
    } catch(PDOException $e) {
        $error_message = "Failed to resend verification. Please try again.";
        error_log("Resend verification error: " . $e->getMessage());
    }
}

function sendVerificationEmail($email, $name, $token) {
    // Configure email settings to fix the mail server error
    ini_set('SMTP', 'localhost');
    ini_set('smtp_port', '25');
    ini_set('sendmail_from', 'noreply@caringpaws.com');
    
    $verification_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/index.php?token=" . $token;
    
    $subject = "Verify Your Email - Caring Paws Veterinary Clinic";
    
    $message = "
    <html>
    <head>
        <title>Email Verification</title>
    </head>
    <body>
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <div style='background-color: #3B82F6; color: white; padding: 20px; text-align: center;'>
                <h1>🐾 Caring Paws Veterinary Clinic</h1>
            </div>
            
            <div style='padding: 30px; background-color: #f9f9f9;'>
                <h2 style='color: #333;'>Hello $name!</h2>
                
                <p>Thank you for registering with Caring Paws Veterinary Clinic. To complete your registration, please verify your email address by clicking the button below:</p>
                
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='$verification_link' 
                       style='background-color: #10B981; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; display: inline-block; font-weight: bold;'>
                        Verify Email Address
                    </a>
                </div>
                
                <p>Or copy and paste this link into your browser:</p>
                <p style='word-break: break-all; color: #666;'>$verification_link</p>
                
                <p><strong>Important:</strong> This verification link will expire in 24 hours.</p>
                
                <p>If you didn't create this account, please ignore this email.</p>
                
                <p>Best regards,<br>
                The Caring Paws Team</p>
            </div>
            
            <div style='background-color: #333; color: white; padding: 20px; text-align: center; font-size: 12px;'>
                <p>&copy; " . date('Y') . " Caring Paws Veterinary Clinic. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: Caring Paws <noreply@caringpaws.com>" . "\r\n";
    $headers .= "Reply-To: support@caringpaws.com" . "\r\n";
    
    return mail($email, $subject, $message, $headers);
}

// Get one demo user from each section with passwords
$demo_users = [];
try {
    if (isset($pdo)) {
        // Get one admin (Mithila) with password
        $stmt = $pdo->query("SELECT name, email, password FROM admin WHERE name LIKE '%Mithila%' OR name LIKE '%mithila%' LIMIT 1");
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($admin) {
            $demo_users['admin'] = $admin;
        } else {
            // If Mithila doesn't exist, get the first admin
            $stmt = $pdo->query("SELECT name, email, password FROM admin LIMIT 1");
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($admin) $demo_users['admin'] = $admin;
        }
        
        // Get one regular user with password
        $stmt = $pdo->query("SELECT name, email, password FROM users LIMIT 1");
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) $demo_users['user'] = $user;
        
        // Get one staff member with password
        $stmt = $pdo->query("SELECT name, email, password FROM staff LIMIT 1");
        $staff = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($staff) $demo_users['staff'] = $staff;
    }
} catch(PDOException $e) {
    // Ignore demo user fetch errors
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $clinic_name; ?> - Login</title>
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
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full mx-4">
        <!-- Login Card -->
        <div class="bg-white rounded-2xl shadow-xl p-8">
            <!-- Header -->
            <div class="text-center mb-8">
                <div class="text-4xl mb-4">🐾</div>
                <h1 class="text-2xl font-bold text-vet-blue mb-2"><?php echo $clinic_name; ?></h1>
                <p class="text-gray-600">Login Portal</p>
                <div class="mt-4 space-y-2">
                    <a href="auth_system/signup.php" class="text-sm text-vet-blue hover:text-vet-dark-blue transition-colors underline block">
                        New user? Sign up here
                    </a>
                </div>
            </div>

            <!-- Error/Success Messages -->
            <?php if (!empty($error_message)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
                    <div class="flex items-center">
                        <span class="text-xl mr-2">⚠️</span>
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($success_message)): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6">
                    <div class="flex items-center">
                        <span class="text-xl mr-2">✅</span>
                        <?php echo htmlspecialchars($success_message); ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form method="POST" class="space-y-6">
                <!-- User Type Selection -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Login As</label>
                    <select name="user_type" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-vet-blue focus:border-transparent outline-none transition-all">
                        <option value="user" <?php echo (($_POST['user_type'] ?? 'user') === 'user') ? 'selected' : ''; ?>>Pet Owner</option>
                        <option value="admin" <?php echo (($_POST['user_type'] ?? '') === 'admin') ? 'selected' : ''; ?>>Administrator</option>
                        <option value="staff" <?php echo (($_POST['user_type'] ?? '') === 'staff') ? 'selected' : ''; ?>>Staff Member</option>
                    </select>
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                        Email Address
                    </label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-vet-blue focus:border-transparent outline-none transition-all"
                        placeholder="Enter your email"
                        required
                    >
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                        Password
                    </label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-vet-blue focus:border-transparent outline-none transition-all"
                        placeholder="Enter your password"
                        required
                    >
                </div>

                <div class="flex items-center justify-between">
                    <label class="flex items-center">
                        <input type="checkbox" class="w-4 h-4 text-vet-blue bg-gray-100 border-gray-300 rounded focus:ring-vet-blue">
                        <span class="ml-2 text-sm text-gray-600">Remember me</span>
                    </label>
                    <a href="#" class="text-sm text-vet-blue hover:text-vet-dark-blue transition-colors">
                        Forgot password?
                    </a>
                </div>

                <button 
                    type="submit" 
                    class="w-full bg-vet-blue hover:bg-vet-dark-blue text-white font-semibold py-3 px-4 rounded-lg transition-colors duration-200"
                >
                    Sign In
                </button>
            </form>

            <!-- Email verification moved to user profile after login -->

            <!-- Demo Credentials -->
            <?php if (!empty($demo_users)): ?>
            <div class="mt-8 p-4 bg-gray-50 rounded-lg">
                <p class="text-sm text-gray-600 text-center mb-3">Demo Credentials:</p>

                <?php if (isset($demo_users['admin'])): ?>
                <div class="mb-2 p-2 bg-blue-50 rounded text-xs">
                    <strong>Admin:</strong><br>
                    Email: <?php echo htmlspecialchars($demo_users['admin']['email']); ?><br>
                    Password: <?php echo htmlspecialchars($demo_users['admin']['password']); ?>
                </div>
                <?php endif; ?>

                <?php if (isset($demo_users['user'])): ?>
                <div class="mb-2 p-2 bg-blue-50 rounded text-xs">
                    <strong>Pet Owner:</strong><br>
                    Email: <?php echo htmlspecialchars($demo_users['user']['email']); ?><br>
                    Password: <?php echo htmlspecialchars($demo_users['user']['password']); ?>
                </div>
                <?php endif; ?>
                
                <?php if (isset($demo_users['staff'])): ?>
                <div class="mb-2 p-2 bg-blue-50 rounded text-xs">
                    <strong>Staff:</strong><br>
                    Email: <?php echo htmlspecialchars($demo_users['staff']['email']); ?><br>
                    Password: <?php echo htmlspecialchars($demo_users['staff']['password']); ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Current Session Info -->
            <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']): ?>
            <div class="mt-6 p-4 bg-blue-50 rounded-lg">
                <p class="text-sm text-blue-800 text-center">
                    <strong>Currently logged in as:</strong><br>
                    <?php echo htmlspecialchars($_SESSION['user_name']); ?> 
                    (<?php echo ucfirst($_SESSION['user_type']); ?>)
                </p>
                <div class="text-center mt-2">
                    <a href="?logout=1" class="text-xs text-red-600 hover:text-red-800">Logout</a>
                </div>
            </div>
            <?php endif; ?>

            <!-- Footer Links -->
            <div class="mt-6 text-center">
                <p class="text-sm text-gray-600">
                    Need help?
                    <button id="supportBtn" type="button" class="text-vet-blue hover:text-vet-dark-blue transition-colors underline">Contact Support</button>
                </p>
            </div>
        </div>

        <!-- Back to Website -->
        <div class="text-center mt-6">
            <a href="#" class="text-white hover:text-blue-200 transition-colors text-sm">
                ← Back to Main Website
            </a>
        </div>
    </div>

    <script>
        // Auto-fill email when user type changes
        document.addEventListener('DOMContentLoaded', function() {
            // Support quick-view toast
            const btn = document.getElementById('supportBtn');
            if (btn) {
                btn.addEventListener('click', async () => {
                    try {
                        const res = await fetch('staff_member/support_data.php', { headers: { 'Accept': 'application/json' } });
                        const data = await res.json();
                        const contacts = (data && data.success && Array.isArray(data.contacts)) ? data.contacts : [];
                        showSupportToast(contacts);
                    } catch (e) {
                        showSupportToast([]);
                    }
                });
            }

            function showSupportToast(contacts) {
                const container = document.createElement('div');
                container.className = 'fixed inset-0 flex items-end md:items-center md:justify-center z-50';

                const overlay = document.createElement('div');
                overlay.className = 'absolute inset-0 bg-black/30';
                overlay.addEventListener('click', () => container.remove());

                const panel = document.createElement('div');
                panel.className = 'relative m-4 md:m-0 max-w-md w-full bg-white rounded-xl shadow-2xl border border-gray-200 p-4 animate-[fadeIn_.2s_ease-out]';
                panel.innerHTML = `
                    <div class="flex items-start justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Contact Support</h3>
                            <p class="text-sm text-gray-500">Front desk contact details</p>
                        </div>
                        <button class="text-gray-400 hover:text-gray-700" aria-label="Close">✕</button>
                    </div>
                    <div class="mt-3 space-y-3" id="supportList"></div>
                    <div class="mt-4 text-right">
                        <a href="staff_member/support.php" class="text-sm text-vet-blue hover:text-vet-dark-blue underline">Open full page</a>
                    </div>
                `;
                panel.querySelector('button[aria-label="Close"]').addEventListener('click', () => container.remove());

                const list = panel.querySelector('#supportList');
                if (contacts.length === 0) {
                    list.innerHTML = '<div class="text-sm text-gray-600">No support contacts available.</div>';
                } else {
                    contacts.forEach(c => {
                        const item = document.createElement('div');
                        item.className = 'border rounded-lg p-3';
                        const email = (c.email || '').replace(/"/g, '');
                        const name = c.name || 'Reception';
                        const role = c.role || 'Receptionist';
                        item.innerHTML = `
                            <div class="font-medium text-gray-900">${name}</div>
                            <div class="text-xs text-gray-500 mb-1">${role}</div>
                            <div class="text-sm">
                                <a class="text-vet-blue hover:text-vet-dark-blue underline" href="mailto:${email}">${email}</a>
                            </div>
                        `;
                        list.appendChild(item);
                    });
                }

                container.appendChild(overlay);
                container.appendChild(panel);
                document.body.appendChild(container);
            }
            const userTypeSelect = document.querySelector('select[name="user_type"]');
            const emailInput = document.querySelector('input[name="email"]');
            const passwordInput = document.querySelector('input[name="password"]');
            
            <?php if (!empty($demo_users)): ?>
            const demoCredentials = {};
            <?php if (isset($demo_users['admin'])): ?>
            demoCredentials['admin'] = {
                email: '<?php echo htmlspecialchars($demo_users['admin']['email']); ?>',
                password: '<?php echo htmlspecialchars($demo_users['admin']['password']); ?>'
            };
            <?php endif; ?>
            <?php if (isset($demo_users['user'])): ?>
            demoCredentials['user'] = {
                email: '<?php echo htmlspecialchars($demo_users['user']['email']); ?>',
                password: '<?php echo htmlspecialchars($demo_users['user']['password']); ?>'
            };
            <?php endif; ?>
            <?php if (isset($demo_users['staff'])): ?>
            demoCredentials['staff'] = {
                email: '<?php echo htmlspecialchars($demo_users['staff']['email']); ?>',
                password: '<?php echo htmlspecialchars($demo_users['staff']['password']); ?>'
            };
            <?php endif; ?>
            
            userTypeSelect.addEventListener('change', function() {
                const selectedType = this.value;
                if (demoCredentials[selectedType]) {
                    emailInput.value = demoCredentials[selectedType].email;
                    passwordInput.value = demoCredentials[selectedType].password;
                }
            });
            <?php endif; ?>
        });
    </script>
</body>
</html> 