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

require_once 'config.php';

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
                // Check users table
                $stmt = $pdo->prepare("SELECT user_id as id, name, email, password, phone, address FROM users WHERE email = ?");
                $stmt->execute([$login_email]);
                $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user_data && $user_data['password'] === $login_password) {
                    $user_found = true;
                    $_SESSION['user_type'] = 'user';
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
                $error_message = "Invalid email or password";
            }
            
        } catch(PDOException $e) {
            $error_message = "Login failed. Please try again.";
        }
    }
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
                    <a href="#" class="text-vet-blue hover:text-vet-dark-blue transition-colors">Contact Support</a>
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
            const userTypeSelect = document.querySelector('select[name="user_type"]');
            const emailInput = document.querySelector('input[name="email"]');
            const passwordInput = document.querySelector('input[name="password"]');
            
            <?php if (!empty($demo_users)): ?>
            const demoCredentials = {
                <?php if (isset($demo_users['admin'])): ?>
                'admin': {
                    email: '<?php echo htmlspecialchars($demo_users['admin']['email']); ?>',
                    password: '<?php echo htmlspecialchars($demo_users['admin']['password']); ?>'
                },
                <?php endif; ?>
                <?php if (isset($demo_users['user'])): ?>
                'user': {
                    email: '<?php echo htmlspecialchars($demo_users['user']['email']); ?>',
                    password: '<?php echo htmlspecialchars($demo_users['user']['password']); ?>'
                },
                <?php endif; ?>
                <?php if (isset($demo_users['staff'])): ?>
                'staff': {
                    email: '<?php echo htmlspecialchars($demo_users['staff']['email']); ?>',
                    password: '<?php echo htmlspecialchars($demo_users['staff']['password']); ?>'
                },
                <?php endif; ?>
            };
            
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