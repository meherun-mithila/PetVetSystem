<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_type'] !== 'staff') {
    header("Location: ../index.php");
    exit();
}

$clinic_name = "Caring Paws Veterinary Clinic";
require_once '../config.php';

$doctors = [];
$available_doctors = [];
$error_message = "";

try {
    // Check if doctors table has phone column
    $result = $pdo->query("DESCRIBE doctors");
    $doctor_columns = $result->fetchAll(PDO::FETCH_COLUMN);
    $phone_column = in_array('phone', $doctor_columns) ? 'phone' : 'contact';
    
    // Get all doctors with location information
    $doctors = $pdo->query("
        SELECT d.*, $phone_column as phone, l.city as location_city, d.address, d.latitude, d.longitude
        FROM doctors d
        LEFT JOIN locations l ON d.location_id = l.location_id
        ORDER BY d.name
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Get available doctors with location information
    $available_doctors = $pdo->query("
        SELECT d.*, $phone_column as phone, l.city as location_city, d.address, d.latitude, d.longitude
        FROM doctors d
        LEFT JOIN locations l ON d.location_id = l.location_id
        WHERE d.availability = 'Available'
        ORDER BY d.name
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
    <title>Doctors - <?php echo $clinic_name; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <header class="bg-blue-600 text-white p-4">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold"><?php echo $clinic_name; ?> - Doctors</h1>
            <a href="dashboard.php" class="bg-blue-700 px-4 py-2 rounded">Dashboard</a>
        </div>
    </header>

    <nav class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-6">
            <div class="flex space-x-8">
                <a href="dashboard.php" class="text-gray-500 hover:text-blue-600 py-4 px-1 font-medium">Dashboard</a>
                <a href="appointments.php" class="text-gray-500 hover:text-blue-600 py-4 px-1 font-medium">Appointments</a>
                <a href="patients.php" class="text-gray-500 hover:text-blue-600 py-4 px-1 font-medium">Patients</a>
                <a href="doctors.php" class="border-b-2 border-blue-600 text-blue-600 py-4 px-1 font-medium">Doctors</a>
                <a href="billing.php" class="text-gray-500 hover:text-blue-600 py-4 px-1 font-medium">Billing</a>
                <a href="reports.php" class="text-gray-500 hover:text-blue-600 py-4 px-1 font-medium">Reports</a>
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
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900">Total Doctors</h3>
                <p class="text-3xl font-bold text-blue-600"><?php echo count($doctors); ?></p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900">Available</h3>
                <p class="text-3xl font-bold text-green-600"><?php echo count($available_doctors); ?></p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900">Busy</h3>
                <p class="text-3xl font-bold text-red-600">
                    <?php echo count($doctors) - count($available_doctors); ?>
                </p>
            </div>
        </div>

        <!-- Available Doctors -->
        <div class="bg-white rounded-lg shadow mb-8">
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
                                        <p class="text-xs text-gray-400">
                                            <?php echo htmlspecialchars($doctor['phone'] ?? 'N/A'); ?>
                                        </p>
                                        <p class="text-xs text-gray-400">
                                            📍 <?php echo htmlspecialchars($doctor['location_city'] ?? $doctor['area'] ?? 'N/A'); ?>
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

        <!-- All Doctors Table -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">All Doctors</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Specialization</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Phone</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Location</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Address</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach($doctors as $doctor): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($doctor['name'] ?? 'Unknown'); ?>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        ID: <?php echo htmlspecialchars($doctor['doctor_id'] ?? 'N/A'); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo htmlspecialchars($doctor['specialization'] ?? 'General'); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo htmlspecialchars($doctor['phone'] ?? 'N/A'); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo htmlspecialchars($doctor['email'] ?? 'N/A'); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo htmlspecialchars($doctor['location_city'] ?? $doctor['area'] ?? 'N/A'); ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <div class="max-w-xs truncate">
                                        <?php echo htmlspecialchars($doctor['address'] ?? 'N/A'); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                        <?php echo ($doctor['availability'] ?? '') === 'Available' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                        <?php echo htmlspecialchars($doctor['availability'] ?? 'Unknown'); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html> 