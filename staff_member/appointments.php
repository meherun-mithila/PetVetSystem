<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_type'] !== 'staff') {
    header("Location: ../index.php");
    exit();
}

$clinic_name = "Caring Paws Veterinary Clinic";
require_once '../config.php';

$today_appointments = [];
$pending_appointments = [];
$all_appointments = [];
$error_message = "";

try {
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
        LIMIT 20
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Get all appointments
    $all_appointments = $pdo->query("
        SELECT a.*, p.animal_name, p.species, u.name as owner_name, u.phone, d.name as doctor_name
        FROM appointments a
        JOIN patients p ON a.patient_id = p.patient_id
        JOIN users u ON p.owner_id = u.user_id
        JOIN doctors d ON a.doctor_id = d.doctor_id
        ORDER BY a.$date_column DESC, a.$time_column DESC
        LIMIT 50
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
    <title>Appointments - <?php echo $clinic_name; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <header class="bg-blue-600 text-white p-4">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold"><?php echo $clinic_name; ?> - Appointments</h1>
            <a href="dashboard.php" class="bg-blue-700 px-4 py-2 rounded">Dashboard</a>
        </div>
    </header>

    <nav class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-6">
            <div class="flex space-x-8">
                <a href="dashboard.php" class="text-gray-500 hover:text-blue-600 py-4 px-1 font-medium">Dashboard</a>
                <a href="appointments.php" class="border-b-2 border-blue-600 text-blue-600 py-4 px-1 font-medium">Appointments</a>
                <a href="patients.php" class="text-gray-500 hover:text-blue-600 py-4 px-1 font-medium">Patients</a>
                <a href="doctors.php" class="text-gray-500 hover:text-blue-600 py-4 px-1 font-medium">Doctors</a>
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
                <h3 class="text-lg font-semibold text-gray-900">Today's Appointments</h3>
                <p class="text-3xl font-bold text-blue-600"><?php echo count($today_appointments); ?></p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900">Pending</h3>
                <p class="text-3xl font-bold text-yellow-600"><?php echo count($pending_appointments); ?></p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900">Total</h3>
                <p class="text-3xl font-bold text-green-600"><?php echo count($all_appointments); ?></p>
            </div>
        </div>

        <!-- Today's Appointments -->
        <div class="bg-white rounded-lg shadow mb-8">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Today's Appointments</h3>
            </div>
            <div class="p-6">
                <?php if (!empty($today_appointments)): ?>
                    <div class="space-y-4">
                        <?php foreach($today_appointments as $appointment): ?>
                            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                <div>
                                    <p class="font-medium text-gray-900">
                                        <?php echo htmlspecialchars($appointment['animal_name'] ?? 'Unknown'); ?>
                                    </p>
                                    <p class="text-sm text-gray-500">
                                        <?php echo htmlspecialchars($appointment['owner_name'] ?? 'Unknown'); ?> • 
                                        <?php echo htmlspecialchars($appointment['species'] ?? 'Unknown'); ?>
                                    </p>
                                    <p class="text-xs text-gray-400">
                                        Time: <?php echo date('H:i', strtotime($appointment[$time_column] ?? '00:00')); ?>
                                    </p>
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

        <!-- All Appointments Table -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">All Appointments</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date & Time</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Patient</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Owner</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Doctor</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach($all_appointments as $appointment): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo date('M j, Y', strtotime($appointment[$date_column] ?? 'now')); ?><br>
                                    <span class="text-gray-500"><?php echo date('H:i', strtotime($appointment[$time_column] ?? '00:00')); ?></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($appointment['animal_name'] ?? 'Unknown'); ?>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        <?php echo htmlspecialchars($appointment['species'] ?? 'Unknown'); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <?php echo htmlspecialchars($appointment['owner_name'] ?? 'Unknown'); ?>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        <?php echo htmlspecialchars($appointment['phone'] ?? 'N/A'); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo htmlspecialchars($appointment['doctor_name'] ?? 'Unknown'); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                        <?php echo ($appointment['status'] ?? '') === 'Completed' ? 'bg-green-100 text-green-800' : 
                                                   (($appointment['status'] ?? '') === 'Scheduled' ? 'bg-blue-100 text-blue-800' : 
                                                   'bg-yellow-100 text-yellow-800'); ?>">
                                        <?php echo htmlspecialchars($appointment['status'] ?? 'Unknown'); ?>
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