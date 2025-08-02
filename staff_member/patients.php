<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_type'] !== 'staff') {
    header("Location: ../index.php");
    exit();
}

$clinic_name = "Caring Paws Veterinary Clinic";
require_once '../config.php';

$patients = [];
$error_message = "";

try {
    // Check if patients table has created_at column
    $result = $pdo->query("DESCRIBE patients");
    $patient_columns = $result->fetchAll(PDO::FETCH_COLUMN);
    $order_by = in_array('created_at', $patient_columns) ? 'p.created_at DESC' : 'p.patient_id DESC';
    
    // Get all patients with owner information
    $patients = $pdo->query("
        SELECT p.*, u.name as owner_name, u.phone as owner_phone
        FROM patients p
        JOIN users u ON p.owner_id = u.user_id
        ORDER BY $order_by
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
    <title>Patients - <?php echo $clinic_name; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <header class="bg-blue-600 text-white p-4">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold"><?php echo $clinic_name; ?> - Patients</h1>
            <a href="dashboard.php" class="bg-blue-700 px-4 py-2 rounded">Dashboard</a>
        </div>
    </header>

    <nav class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-6">
            <div class="flex space-x-8">
                <a href="dashboard.php" class="text-gray-500 hover:text-blue-600 py-4 px-1 font-medium">Dashboard</a>
                <a href="appointments.php" class="text-gray-500 hover:text-blue-600 py-4 px-1 font-medium">Appointments</a>
                <a href="patients.php" class="border-b-2 border-blue-600 text-blue-600 py-4 px-1 font-medium">Patients</a>
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
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900">Total Patients</h3>
                <p class="text-3xl font-bold text-blue-600"><?php echo count($patients); ?></p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900">Dogs</h3>
                <p class="text-3xl font-bold text-green-600">
                    <?php echo count(array_filter($patients, function($p) { return ($p['species'] ?? '') === 'Dog'; })); ?>
                </p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900">Cats</h3>
                <p class="text-3xl font-bold text-yellow-600">
                    <?php echo count(array_filter($patients, function($p) { return ($p['species'] ?? '') === 'Cat'; })); ?>
                </p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900">Other</h3>
                <p class="text-3xl font-bold text-purple-600">
                    <?php echo count(array_filter($patients, function($p) { 
                        $species = $p['species'] ?? '';
                        return $species !== 'Dog' && $species !== 'Cat'; 
                    })); ?>
                </p>
            </div>
        </div>

        <!-- Patients Table -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">All Patients</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Patient</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Owner</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Species</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Breed</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Age</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Gender</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Registered</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach($patients as $patient): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($patient['animal_name'] ?? 'Unknown'); ?>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        ID: <?php echo htmlspecialchars($patient['patient_id'] ?? 'N/A'); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <?php echo htmlspecialchars($patient['owner_name'] ?? 'Unknown'); ?>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        <?php echo htmlspecialchars($patient['owner_phone'] ?? 'N/A'); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo htmlspecialchars($patient['species'] ?? 'Unknown'); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo htmlspecialchars($patient['breed'] ?? 'Unknown'); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo htmlspecialchars($patient['age'] ?? 'Unknown'); ?> years
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo htmlspecialchars($patient['gender'] ?? 'Unknown'); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php 
                                    if (isset($patient['created_at'])) {
                                        echo date('M j, Y', strtotime($patient['created_at']));
                                    } else {
                                        echo 'N/A';
                                    }
                                    ?>
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