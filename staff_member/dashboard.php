<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_type'] !== 'staff') {
    header("Location: ../index.php");
    exit();
}

$clinic_name = "Caring Paws Veterinary Clinic";

require_once '../config.php';

// Initialize variables with default values
$today_appointments = [];
$pending_appointments = [];
$recent_patients = [];
$available_doctors = [];
$error_message = "";
$staff_info = [];

try {
    $staff_id = $_SESSION['user_id'];
    
    // Get staff member information
    $stmt = $pdo->prepare("SELECT * FROM staff WHERE staff_id = ?");
    $stmt->execute([$staff_id]);
    $staff_info = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Check if appointments table has the new column names
    $result = $pdo->query("DESCRIBE appointments");
    $columns = $result->fetchAll(PDO::FETCH_COLUMN);
    
    $date_column = in_array('appointment_date', $columns) ? 'appointment_date' : 'date';
    $time_column = in_array('appointment_time', $columns) ? 'appointment_time' : 'time';
    
    // Get today's appointments
    $today_appointments = $pdo->query("
        SELECT a.*, p.animal_name, p.species, u.name as owner_name, u.phone, d.name as doctor_name
        FROM appointments a
        JOIN patients p ON a.patient_id = p.patient_id
        JOIN users u ON p.owner_id = u.user_id
        JOIN doctors d ON a.doctor_id = d.doctor_id
        WHERE DATE(a.$date_column) = CURDATE()
        ORDER BY a.$time_column
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Get pending appointments
    $pending_appointments = $pdo->query("
        SELECT a.*, p.animal_name, p.species, u.name as owner_name, u.phone, d.name as doctor_name
        FROM appointments a
        JOIN patients p ON a.patient_id = p.patient_id
        JOIN users u ON p.owner_id = u.user_id
        JOIN doctors d ON a.doctor_id = d.doctor_id
        WHERE a.status = 'Scheduled' AND a.$date_column >= CURDATE()
        ORDER BY a.$date_column, a.$time_column
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Check if patients table has created_at column
    $result = $pdo->query("DESCRIBE patients");
    $patient_columns = $result->fetchAll(PDO::FETCH_COLUMN);
    $order_by = in_array('created_at', $patient_columns) ? 'p.created_at DESC' : 'p.patient_id DESC';
    
    // Get recent patients
    $recent_patients = $pdo->query("
        SELECT p.*, u.name as owner_name, u.phone
        FROM patients p
        JOIN users u ON p.owner_id = u.user_id
        ORDER BY $order_by
        LIMIT 8
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Get available doctors
    $available_doctors = $pdo->query("
        SELECT * FROM doctors 
        WHERE availability = 'Available'
        ORDER BY name
    ")->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - <?php echo $clinic_name; ?></title>
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
    <header class="bg-gradient-to-r from-vet-blue to-vet-dark-blue text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-6 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center">
                    <span class="text-2xl mr-3">🐾</span>
                    <div>
                        <h1 class="text-xl font-bold"><?php echo $clinic_name; ?></h1>
                        <p class="text-blue-200 text-sm">Staff Dashboard</p>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="text-right">
                        <p class="font-semibold"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Staff Member'); ?></p>
                        <p class="text-blue-200 text-sm"><?php echo htmlspecialchars($staff_info['role'] ?? 'Staff'); ?></p>
                    </div>
                    <a href="../index.php?logout=1" class="bg-red-500 hover:bg-red-600 px-4 py-2 rounded-lg transition-colors">
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </header>

    <nav class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-6">
            <div class="flex space-x-8">
                <a href="dashboard.php" class="border-b-2 border-vet-blue text-vet-blue py-4 px-1 font-medium">Dashboard</a>
                <a href="appointments.php" class="text-gray-500 hover:text-vet-blue py-4 px-1 font-medium">Appointments</a>
                <a href="patients.php" class="text-gray-500 hover:text-vet-blue py-4 px-1 font-medium">Patients</a>
                <a href="doctors.php" class="text-gray-500 hover:text-vet-blue py-4 px-1 font-medium">Doctors</a>
                <a href="billing.php" class="text-gray-500 hover:text-vet-blue py-4 px-1 font-medium">Billing</a>
                <a href="medical_reports.php" class="text-gray-500 hover:text-vet-blue py-4 px-1 font-medium">Medical Reports</a>
                <a href="reports.php" class="text-gray-500 hover:text-vet-blue py-4 px-1 font-medium">Reports</a>
                <a href="profile.php" class="text-gray-500 hover:text-vet-blue py-4 px-1 font-medium">Profile</a>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-6 py-8">
        <?php if (!empty($error_message)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <!-- Quick Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Today's Appointments</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo count($today_appointments); ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Pending</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo count($pending_appointments); ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Available Doctors</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo count($available_doctors); ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Recent Patients</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo count($recent_patients); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Today's Appointments -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Today's Appointments</h3>
                </div>
                <div class="p-6">
                    <?php if (!empty($today_appointments)): ?>
                        <div class="space-y-4">
                            <?php foreach($today_appointments as $appointment): ?>
                                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                    <div class="flex items-center space-x-4">
                                        <div class="flex-shrink-0">
                                            <div class="w-10 h-10 bg-vet-blue rounded-full flex items-center justify-center">
                                                <span class="text-white font-semibold">
                                                    <?php echo date('H:i', strtotime($appointment[$time_column] ?? '00:00')); ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-900">
                                                <?php echo htmlspecialchars($appointment['animal_name'] ?? 'Unknown'); ?>
                                            </p>
                                            <p class="text-sm text-gray-500">
                                                <?php echo htmlspecialchars($appointment['owner_name'] ?? 'Unknown'); ?> • 
                                                <?php echo htmlspecialchars($appointment['species'] ?? 'Unknown'); ?>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($appointment['doctor_name'] ?? 'Unknown'); ?>
                                        </p>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            <?php echo htmlspecialchars($appointment['status'] ?? 'Unknown'); ?>
                                        </span>
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
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Recent Patients</h3>
                </div>
                <div class="p-6">
                    <?php if (!empty($recent_patients)): ?>
                        <div class="space-y-4">
                            <?php foreach($recent_patients as $patient): ?>
                                <div class="flex items-center space-x-4 p-4 bg-gray-50 rounded-lg">
                                    <div class="flex-shrink-0">
                                        <div class="w-10 h-10 bg-vet-coral rounded-full flex items-center justify-center">
                                            <span class="text-white font-semibold">
                                                <?php echo strtoupper(substr($patient['animal_name'] ?? 'P', 0, 1)); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="font-medium text-gray-900 truncate">
                                            <?php echo htmlspecialchars($patient['animal_name'] ?? 'Unknown'); ?>
                                        </p>
                                        <p class="text-sm text-gray-500">
                                            <?php echo htmlspecialchars($patient['owner_name'] ?? 'Unknown'); ?> • 
                                            <?php echo htmlspecialchars($patient['species'] ?? 'Unknown'); ?>
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm text-gray-500">
                                            <?php echo htmlspecialchars($patient['breed'] ?? 'Unknown'); ?>
                                        </p>
                                        <p class="text-xs text-gray-400">
                                            <?php echo htmlspecialchars($patient['age'] ?? 'Unknown'); ?> years
                                        </p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-gray-500 text-center py-8">No recent patients found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Available Doctors -->
        <div class="mt-8 bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Available Doctors</h3>
            </div>
            <div class="p-6">
                <?php if (!empty($available_doctors)): ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <?php foreach($available_doctors as $doctor): ?>
                            <div class="p-4 border border-gray-200 rounded-lg">
                                <div class="flex items-center space-x-3">
                                    <div class="flex-shrink-0">
                                        <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="font-medium text-gray-900">
                                            <?php echo htmlspecialchars($doctor['name'] ?? 'Unknown'); ?>
                                        </p>
                                        <p class="text-sm text-gray-500">
                                            <?php echo htmlspecialchars($doctor['specialization'] ?? 'General'); ?>
                                        </p>
                                    </div>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Available
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-gray-500 text-center py-8">No doctors currently available.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="mt-8 bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Quick Actions</h3>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <a href="appointments.php" class="block p-6 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 transition-colors">
                        <div class="flex items-center space-x-3">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-900">Schedule Appointment</h4>
                                <p class="text-sm text-gray-500">Manage appointments</p>
                            </div>
                        </div>
                    </a>

                    <a href="patients.php" class="block p-6 bg-green-50 border border-green-200 rounded-lg hover:bg-green-100 transition-colors">
                        <div class="flex items-center space-x-3">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-900">Register Patient</h4>
                                <p class="text-sm text-gray-500">Add new patients</p>
                            </div>
                        </div>
                    </a>

                    <a href="medical_reports.php" class="block p-6 bg-purple-50 border border-purple-200 rounded-lg hover:bg-purple-100 transition-colors">
                        <div class="flex items-center space-x-3">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 bg-purple-500 rounded-full flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-900">Medical Records</h4>
                                <p class="text-sm text-gray-500">View patient records</p>
                            </div>
                        </div>
                    </a>

                    <a href="billing.php" class="block p-6 bg-yellow-50 border border-yellow-200 rounded-lg hover:bg-yellow-100 transition-colors">
                        <div class="flex items-center space-x-3">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 bg-yellow-500 rounded-full flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                    </svg>
                                </div>
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-900">Billing</h4>
                                <p class="text-sm text-gray-500">Manage payments</p>
                            </div>
                        </div>
                    </a>

                    <a href="reports.php" class="block p-6 bg-red-50 border border-red-200 rounded-lg hover:bg-red-100 transition-colors">
                        <div class="flex items-center space-x-3">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 bg-red-500 rounded-full flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-900">Reports</h4>
                                <p class="text-sm text-gray-500">View analytics</p>
                            </div>
                        </div>
                    </a>

                    <a href="doctors.php" class="block p-6 bg-indigo-50 border border-indigo-200 rounded-lg hover:bg-indigo-100 transition-colors">
                        <div class="flex items-center space-x-3">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 bg-indigo-500 rounded-full flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-900">Doctors</h4>
                                <p class="text-sm text-gray-500">Manage doctors</p>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </main>
</body>
</html> 