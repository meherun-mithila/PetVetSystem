<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

$clinic_name = "Caring Paws Veterinary Clinic";
require_once '../config.php';

$appointments = [];
$error_message = "";

try {
    // Check if appointments table has the correct column names
    $result = $pdo->query("DESCRIBE appointments");
    $appointment_columns = $result->fetchAll(PDO::FETCH_COLUMN);
    
    $date_column = in_array('appointment_date', $appointment_columns) ? 'appointment_date' : 'date';
    $time_column = in_array('appointment_time', $appointment_columns) ? 'appointment_time' : 'time';
    $has_reason = in_array('reason', $appointment_columns);
    
    // Build the SELECT query with conditional reason column
    $reason_field = $has_reason ? 'a.reason' : 'NULL as reason';
    
    // Check if doctors table has phone column or contact column
    $result = $pdo->query("DESCRIBE doctors");
    $doctor_columns = $result->fetchAll(PDO::FETCH_COLUMN);
    $phone_column = in_array('phone', $doctor_columns) ? 'phone' : 'contact';
    
    $stmt = $pdo->prepare("
        SELECT a.*, $reason_field, p.animal_name, p.species, u.name as owner_name, u.phone, d.name as doctor_name, d.specialization
        FROM appointments a
        JOIN patients p ON a.patient_id = p.patient_id
        JOIN users u ON p.owner_id = u.user_id
        JOIN doctors d ON a.doctor_id = d.doctor_id
        ORDER BY a.$date_column DESC, a.$time_column DESC
    ");
    $stmt->execute();
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error_message = "Failed to load appointments: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointments Management - <?php echo $clinic_name; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <header class="bg-blue-600 text-white p-4">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold"><?php echo $clinic_name; ?> - Appointments</h1>
            <a href="dashboard.php" class="bg-blue-700 px-4 py-2 rounded">Dashboard</a>
        </div>
    </header>

    <div class="max-w-7xl mx-auto p-6">
        <h2 class="text-3xl font-bold mb-6">Appointments Management</h2>
        
        <?php if (!empty($error_message)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($appointments)): ?>
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <table class="min-w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pet</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Owner</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Doctor</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date & Time</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reason</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach($appointments as $appointment): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($appointment['animal_name'] ?? 'Unknown'); ?></div>
                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($appointment['species'] ?? 'Unknown'); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($appointment['owner_name'] ?? 'Unknown'); ?></div>
                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($appointment['phone'] ?? 'N/A'); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">Dr. <?php echo htmlspecialchars($appointment['doctor_name'] ?? 'Unknown'); ?></div>
                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($appointment['specialization'] ?? 'N/A'); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900"><?php 
                                    $date_field = $date_column === 'appointment_date' ? 'appointment_date' : 'date';
                                    echo isset($appointment[$date_field]) ? date('M j, Y', strtotime($appointment[$date_field])) : 'N/A'; 
                                ?></div>
                                <div class="text-sm text-gray-500"><?php 
                                    $time_field = $time_column === 'appointment_time' ? 'appointment_time' : 'time';
                                    echo isset($appointment[$time_field]) ? date('g:i A', strtotime($appointment[$time_field])) : 'N/A'; 
                                ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-medium rounded-full 
                                    <?php 
                                    switch($appointment['status'] ?? '') {
                                        case 'Scheduled': echo 'bg-blue-100 text-blue-800'; break;
                                        case 'Completed': echo 'bg-green-100 text-green-800'; break;
                                        case 'Cancelled': echo 'bg-red-100 text-red-800'; break;
                                        default: echo 'bg-gray-100 text-gray-800';
                                    }
                                    ?>">
                                    <?php echo htmlspecialchars($appointment['status'] ?? 'Unknown'); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                <?php echo htmlspecialchars($appointment['reason'] ?? 'No reason provided'); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-12">
                <p class="text-gray-500">No appointments found.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html> 