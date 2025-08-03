<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

$clinic_name = "Caring Paws Veterinary Clinic";
require_once '../config.php';

// Handle admin account management
$admin_message = "";
if ($_POST && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_admin') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($name) || empty($email) || empty($password)) {
            $admin_message = "Please fill in all fields";
        } else {
            try {
                // Check if email already exists
                $stmt = $pdo->prepare("SELECT admin_id FROM admin WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    $admin_message = "Email already exists";
                } else {
                    // Add new admin
                    $stmt = $pdo->prepare("INSERT INTO admin (name, email, password) VALUES (?, ?, ?)");
                    $stmt->execute([$name, $email, $password]);
                    $admin_message = "Admin account created successfully";
                }
            } catch(PDOException $e) {
                $admin_message = "Error creating admin account: " . $e->getMessage();
            }
        }
    } elseif ($_POST['action'] === 'delete_admin') {
        $admin_id = $_POST['admin_id'] ?? 0;
        if ($admin_id > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM admin WHERE admin_id = ?");
                $stmt->execute([$admin_id]);
                $admin_message = "Admin account deleted successfully";
            } catch(PDOException $e) {
                $admin_message = "Error deleting admin account: " . $e->getMessage();
            }
        }
    }
}

// Initialize variables
$total_patients = 0;
$total_appointments = 0;
$total_doctors = 0;
$total_users = 0;
$recent_appointments = [];
$today_appointments = [];
$pending_appointments = [];
$recent_patients = [];
$error_message = "";
$all_admins = [];

try {
    // Get all admin accounts
    $stmt = $pdo->query("SELECT admin_id, name, email, password FROM admin ORDER BY admin_id");
    $all_admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total counts
    $total_patients = $pdo->query("SELECT COUNT(*) FROM patients")->fetchColumn();
    $total_appointments = $pdo->query("SELECT COUNT(*) FROM appointments")->fetchColumn();
    $total_doctors = $pdo->query("SELECT COUNT(*) FROM doctors")->fetchColumn();
    $total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    
    // Check if appointments table has the correct column names
    $result = $pdo->query("DESCRIBE appointments");
    $appointment_columns = $result->fetchAll(PDO::FETCH_COLUMN);
    
    $date_column = in_array('appointment_date', $appointment_columns) ? 'appointment_date' : 'date';
    $time_column = in_array('appointment_time', $appointment_columns) ? 'appointment_time' : 'time';
    
    // Get today's appointments
    $stmt = $pdo->prepare("
        SELECT a.*, p.animal_name, p.species, u.name as owner_name, u.phone, d.name as doctor_name
        FROM appointments a
        JOIN patients p ON a.patient_id = p.patient_id
        JOIN users u ON p.owner_id = u.user_id
        JOIN doctors d ON a.doctor_id = d.doctor_id
        WHERE DATE(a.$date_column) = CURDATE()
        ORDER BY a.$time_column
        LIMIT 5
    ");
    $stmt->execute();
    $today_appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get pending appointments
    $stmt = $pdo->prepare("
        SELECT a.*, p.animal_name, p.species, u.name as owner_name, u.phone, d.name as doctor_name
        FROM appointments a
        JOIN patients p ON a.patient_id = p.patient_id
        JOIN users u ON p.owner_id = u.user_id
        JOIN doctors d ON a.doctor_id = d.doctor_id
        WHERE a.status = 'Scheduled' AND a.$date_column >= CURDATE()
        ORDER BY a.$date_column, a.$time_column
        LIMIT 5
    ");
    $stmt->execute();
    $pending_appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Check if patients table has created_at column
    $result = $pdo->query("DESCRIBE patients");
    $patient_columns = $result->fetchAll(PDO::FETCH_COLUMN);
    
    $order_by = in_array('created_at', $patient_columns) ? 'p.created_at DESC' : 'p.patient_id DESC';
    
    // Get recent patients
    $stmt = $pdo->prepare("
        SELECT p.*, u.name as owner_name, u.phone
        FROM patients p
        JOIN users u ON p.owner_id = u.user_id
        ORDER BY $order_by
        LIMIT 5
    ");
    $stmt->execute();
    $recent_patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $error_message = "Database connection failed: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo $clinic_name; ?></title>
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
                        <p class="text-blue-200 text-sm">Administration Dashboard</p>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="text-right">
                        <p class="font-semibold"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></p>
                        <p class="text-blue-200 text-sm">Administrator</p>
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
                <a href="dashboard.php" class="text-vet-blue font-semibold border-b-2 border-vet-blue pb-2">Dashboard</a>
                <a href="patients.php" class="text-gray-600 hover:text-vet-blue transition-colors">Patients</a>
                <a href="appointments.php" class="text-gray-600 hover:text-vet-blue transition-colors">Appointments</a>
                <a href="doctors.php" class="text-gray-600 hover:text-vet-blue transition-colors">Doctors</a>
                <a href="users.php" class="text-gray-600 hover:text-vet-blue transition-colors">Users</a>
                <a href="staff.php" class="text-gray-600 hover:text-vet-blue transition-colors">Staff</a>
                <a href="medical_records.php" class="text-gray-600 hover:text-vet-blue transition-colors">Medical Records</a>
                <a href="reports.php" class="text-gray-600 hover:text-vet-blue transition-colors">Reports</a>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-6 py-8">
        <?php if (!empty($error_message)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($admin_message)): ?>
            <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded mb-6">
                <?php echo htmlspecialchars($admin_message); ?>
            </div>
        <?php endif; ?>

        <!-- Admin Accounts Management Section -->
        <div class="bg-white rounded-lg shadow-md mb-8">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-xl font-bold text-gray-800">Admin Accounts Management</h2>
                <p class="text-gray-600 text-sm mt-1">Manage all administrator accounts and credentials</p>
            </div>
            <div class="p-6">
                <!-- Add New Admin Form -->
                <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Add New Admin Account</h3>
                    <form method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <input type="hidden" name="action" value="add_admin">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                            <input type="text" name="name" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-vet-blue focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                            <input type="email" name="email" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-vet-blue focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                            <input type="password" name="password" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-vet-blue focus:border-transparent">
                        </div>
                        <div class="flex items-end">
                            <button type="submit" class="w-full bg-vet-blue hover:bg-vet-dark-blue text-white font-semibold py-2 px-4 rounded-lg transition-colors">
                                Add Admin
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Admin Accounts Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white border border-gray-200 rounded-lg">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Password</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (!empty($all_admins)): ?>
                                <?php foreach($all_admins as $admin): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($admin['admin_id']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($admin['name']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($admin['email']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <span class="font-mono bg-gray-100 px-2 py-1 rounded text-xs"><?php echo htmlspecialchars($admin['password']); ?></span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php if ($admin['admin_id'] != $_SESSION['user_id']): ?>
                                        <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this admin account?')">
                                            <input type="hidden" name="action" value="delete_admin">
                                            <input type="hidden" name="admin_id" value="<?php echo $admin['admin_id']; ?>">
                                            <button type="submit" class="text-red-600 hover:text-red-900 font-medium">Delete</button>
                                        </form>
                                        <?php else: ?>
                                        <span class="text-gray-400">Current User</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-4 text-center text-gray-500">No admin accounts found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="text-3xl text-blue-500 mr-4">🐕</div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-700">Total Patients</h3>
                        <p class="text-3xl font-bold text-vet-blue"><?php echo $total_patients; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="text-3xl text-green-500 mr-4">📅</div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-700">Total Appointments</h3>
                        <p class="text-3xl font-bold text-vet-blue"><?php echo $total_appointments; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="text-3xl text-purple-500 mr-4">👨‍⚕️</div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-700">Doctors</h3>
                        <p class="text-3xl font-bold text-vet-blue"><?php echo $total_doctors; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="text-3xl text-yellow-500 mr-4">👥</div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-700">Pet Owners</h3>
                        <p class="text-3xl font-bold text-vet-blue"><?php echo $total_users; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Today's Appointments -->
            <div class="bg-white rounded-lg shadow-md">
                <div class="p-6 border-b border-gray-200 flex justify-between items-center">
                    <h2 class="text-xl font-bold text-gray-800">Today's Appointments</h2>
                    <a href="appointments.php" class="text-vet-blue hover:text-vet-dark-blue text-sm">View All</a>
                </div>
                <div class="p-6">
                    <?php if (!empty($today_appointments)): ?>
                        <div class="space-y-4">
                            <?php foreach($today_appointments as $appointment): ?>
                            <div class="border rounded-lg p-4 hover:shadow-md transition-shadow">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h4 class="font-semibold text-lg text-gray-800"><?php echo htmlspecialchars($appointment['animal_name'] ?? 'Unknown'); ?></h4>
                                        <p class="text-sm text-gray-600">Owner: <?php echo htmlspecialchars($appointment['owner_name'] ?? 'Unknown'); ?></p>
                                        <p class="text-sm text-gray-500">Dr. <?php echo htmlspecialchars($appointment['doctor_name'] ?? 'Unknown'); ?></p>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-semibold text-vet-coral"><?php 
                                            $time_field = $time_column === 'appointment_time' ? 'appointment_time' : 'time';
                                            echo isset($appointment[$time_field]) ? date('g:i A', strtotime($appointment[$time_field])) : 'N/A'; 
                                        ?></p>
                                        <span class="px-2 py-1 rounded-full text-xs font-medium 
                                            <?php echo ($appointment['status'] ?? '') === 'Scheduled' ? 'bg-blue-100 text-blue-800' : 
                                                    (($appointment['status'] ?? '') === 'Completed' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'); ?>">
                                            <?php echo htmlspecialchars($appointment['status'] ?? 'Unknown'); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-gray-500 text-center py-8">No appointments scheduled for today.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Patients -->
            <div class="bg-white rounded-lg shadow-md">
                <div class="p-6 border-b border-gray-200 flex justify-between items-center">
                    <h2 class="text-xl font-bold text-gray-800">Recent Patients</h2>
                    <a href="patients.php" class="text-vet-blue hover:text-vet-dark-blue text-sm">View All</a>
                </div>
                <div class="p-6">
                    <?php if (!empty($recent_patients)): ?>
                        <div class="space-y-4">
                            <?php foreach($recent_patients as $patient): ?>
                            <div class="border rounded-lg p-4 hover:shadow-md transition-shadow">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h4 class="font-semibold text-lg text-gray-800"><?php echo htmlspecialchars($patient['animal_name'] ?? 'Unknown'); ?></h4>
                                        <p class="text-sm text-gray-600">Owner: <?php echo htmlspecialchars($patient['owner_name'] ?? 'Unknown'); ?></p>
                                        <p class="text-sm text-gray-500"><?php echo htmlspecialchars($patient['species'] ?? 'Unknown'); ?> • <?php echo htmlspecialchars($patient['breed'] ?? 'Unknown'); ?></p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-xs text-gray-500"><?php echo isset($patient['created_at']) ? date('M j, Y', strtotime($patient['created_at'])) : 'N/A'; ?></p>
                                        <span class="px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            Active
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-gray-500 text-center py-8">No patients registered yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="mt-8 bg-white rounded-lg shadow-md">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-xl font-bold text-gray-800">Quick Actions</h2>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <a href="patients.php?action=add" class="bg-vet-blue hover:bg-vet-dark-blue text-white p-4 rounded-lg text-center transition-colors">
                        <div class="text-2xl mb-2">🐕</div>
                        <div class="font-semibold">Add Patient</div>
                    </a>
                    <a href="appointments.php?action=add" class="bg-green-600 hover:bg-green-700 text-white p-4 rounded-lg text-center transition-colors">
                        <div class="text-2xl mb-2">📅</div>
                        <div class="font-semibold">Schedule Appointment</div>
                    </a>
                    <a href="doctors.php?action=add" class="bg-purple-600 hover:bg-purple-700 text-white p-4 rounded-lg text-center transition-colors">
                        <div class="text-2xl mb-2">👨‍⚕️</div>
                        <div class="font-semibold">Add Doctor</div>
                    </a>
                    <a href="reports.php" class="bg-yellow-600 hover:bg-yellow-700 text-white p-4 rounded-lg text-center transition-colors">
                        <div class="text-2xl mb-2">📊</div>
                        <div class="font-semibold">View Reports</div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 