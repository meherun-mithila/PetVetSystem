<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_type'] !== 'staff') {
    header("Location: ../index.php");
    exit();
}

$clinic_name = "Caring Paws Veterinary Clinic";
require_once '../config.php';

$medical_records = [];
$total_revenue = 0;
$pending_payments = 0;
$error_message = "";

try {
    // Check if medicalrecords table has record_date column
    $result = $pdo->query("DESCRIBE medicalrecords");
    $medical_columns = $result->fetchAll(PDO::FETCH_COLUMN);
    $date_column = in_array('record_date', $medical_columns) ? 'record_date' : 'date';
    
    // Get medical records with billing information
    $medical_records = $pdo->query("
        SELECT mr.*, p.animal_name, p.species, u.name as owner_name, u.phone, d.name as doctor_name
        FROM medicalrecords mr
        JOIN patients p ON mr.patient_id = p.patient_id
        JOIN users u ON p.owner_id = u.user_id
        JOIN doctors d ON mr.doctor_id = d.doctor_id
        ORDER BY mr.$date_column DESC
        LIMIT 50
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate total revenue
    $total_revenue = array_sum(array_column($medical_records, 'cost'));
    
    // Count pending payments (records with cost > 0)
    $pending_payments = count(array_filter($medical_records, function($record) {
        return ($record['cost'] ?? 0) > 0;
    }));
    
} catch(PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Billing - <?php echo $clinic_name; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <header class="bg-blue-600 text-white p-4">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold"><?php echo $clinic_name; ?> - Billing</h1>
            <a href="dashboard.php" class="bg-blue-700 px-4 py-2 rounded">Dashboard</a>
        </div>
    </header>

    <nav class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-6">
            <div class="flex space-x-8">
                <a href="dashboard.php" class="text-gray-500 hover:text-blue-600 py-4 px-1 font-medium">Dashboard</a>
                <a href="appointments.php" class="text-gray-500 hover:text-blue-600 py-4 px-1 font-medium">Appointments</a>
                <a href="patients.php" class="text-gray-500 hover:text-blue-600 py-4 px-1 font-medium">Patients</a>
                <a href="doctors.php" class="text-gray-500 hover:text-blue-600 py-4 px-1 font-medium">Doctors</a>
                <a href="billing.php" class="border-b-2 border-blue-600 text-blue-600 py-4 px-1 font-medium">Billing</a>
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
                <h3 class="text-lg font-semibold text-gray-900">Total Revenue</h3>
                <p class="text-3xl font-bold text-green-600">$<?php echo number_format($total_revenue, 2); ?></p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900">Medical Records</h3>
                <p class="text-3xl font-bold text-blue-600"><?php echo count($medical_records); ?></p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900">Pending Payments</h3>
                <p class="text-3xl font-bold text-yellow-600"><?php echo $pending_payments; ?></p>
            </div>
        </div>

        <!-- Billing Table -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Medical Records & Billing</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Patient</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Owner</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Doctor</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Diagnosis</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Treatment</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cost</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach($medical_records as $record): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo date('M j, Y', strtotime($record[$date_column] ?? 'now')); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($record['animal_name'] ?? 'Unknown'); ?>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        <?php echo htmlspecialchars($record['species'] ?? 'Unknown'); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <?php echo htmlspecialchars($record['owner_name'] ?? 'Unknown'); ?>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        <?php echo htmlspecialchars($record['phone'] ?? 'N/A'); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo htmlspecialchars($record['doctor_name'] ?? 'Unknown'); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo htmlspecialchars($record['diagnosis'] ?? 'N/A'); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo htmlspecialchars($record['treatment'] ?? 'N/A'); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    $<?php echo number_format($record['cost'] ?? 0, 2); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                        <?php echo ($record['cost'] ?? 0) > 0 ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800'; ?>">
                                        <?php echo ($record['cost'] ?? 0) > 0 ? 'Pending' : 'Paid'; ?>
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