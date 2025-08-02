<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

$clinic_name = "Caring Paws Veterinary Clinic";
require_once '../config.php';

$medical_records = [];
$error_message = "";

try {
    $stmt = $pdo->prepare("
        SELECT mr.*, p.animal_name, p.species, u.name as owner_name, d.name as doctor_name, d.specialization
        FROM medicalrecords mr
        JOIN patients p ON mr.patient_id = p.patient_id
        JOIN users u ON p.owner_id = u.user_id
        JOIN doctors d ON mr.doctor_id = d.doctor_id
        ORDER BY mr.date DESC
    ");
    $stmt->execute();
    $medical_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error_message = "Failed to load medical records.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical Records Management - <?php echo $clinic_name; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <header class="bg-blue-600 text-white p-4">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold"><?php echo $clinic_name; ?> - Medical Records</h1>
            <a href="dashboard.php" class="bg-blue-700 px-4 py-2 rounded">Dashboard</a>
        </div>
    </header>

    <div class="max-w-7xl mx-auto p-6">
        <h2 class="text-3xl font-bold mb-6">Medical Records Management</h2>
        
        <?php if (!empty($error_message)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($medical_records)): ?>
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <table class="min-w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pet</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Owner</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Doctor</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Diagnosis</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bill</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach($medical_records as $record): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($record['animal_name']); ?></div>
                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($record['species']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo htmlspecialchars($record['owner_name']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">Dr. <?php echo htmlspecialchars($record['doctor_name']); ?></div>
                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($record['specialization']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo date('M j, Y', strtotime($record['date'])); ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                <?php echo htmlspecialchars(substr($record['diagnosis'], 0, 50)) . (strlen($record['diagnosis']) > 50 ? '...' : ''); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-green-600">
                                ৳<?php echo number_format($record['bills'], 2); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-12">
                <p class="text-gray-500">No medical records found.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html> 