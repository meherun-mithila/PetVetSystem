<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_type'] !== 'staff') {
    header("Location: ../index.php");
    exit();
}

$clinic_name = "Caring Paws Veterinary Clinic";
require_once '../config.php';

$monthly_appointments = [];
$revenue_data = [];
$patient_stats = [];
$doctor_stats = [];
$error_message = "";

try {
    // Check if appointments table has the new column names
    $result = $pdo->query("DESCRIBE appointments");
    $columns = $result->fetchAll(PDO::FETCH_COLUMN);
    $date_column = in_array('appointment_date', $columns) ? 'appointment_date' : 'date';
    
    // Get monthly appointments for current year
    $monthly_appointments = $pdo->query("
        SELECT MONTH($date_column) as month, COUNT(*) as count
        FROM appointments 
        WHERE YEAR($date_column) = YEAR(CURDATE())
        GROUP BY MONTH($date_column)
        ORDER BY month
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Check if medicalrecords table has record_date column
    $result = $pdo->query("DESCRIBE medicalrecords");
    $medical_columns = $result->fetchAll(PDO::FETCH_COLUMN);
    $record_date_column = in_array('record_date', $medical_columns) ? 'record_date' : 'date';
    
    // Get revenue data
    $revenue_data = $pdo->query("
        SELECT MONTH($record_date_column) as month, SUM(cost) as total_revenue
        FROM medicalrecords 
        WHERE YEAR($record_date_column) = YEAR(CURDATE())
        GROUP BY MONTH($record_date_column)
        ORDER BY month
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Get patient statistics
    $patient_stats = $pdo->query("
        SELECT species, COUNT(*) as count
        FROM patients
        GROUP BY species
        ORDER BY count DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Get doctor statistics
    $doctor_stats = $pdo->query("
        SELECT d.name, COUNT(a.appointment_id) as appointment_count
        FROM doctors d
        LEFT JOIN appointments a ON d.doctor_id = a.doctor_id
        GROUP BY d.doctor_id, d.name
        ORDER BY appointment_count DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate total revenue
    $total_revenue = array_sum(array_column($revenue_data, 'total_revenue'));
    
    // Get total appointments this month
    $current_month_appointments = $pdo->query("
        SELECT COUNT(*) as count
        FROM appointments 
        WHERE MONTH($date_column) = MONTH(CURDATE()) AND YEAR($date_column) = YEAR(CURDATE())
    ")->fetchColumn();
    
    // Get total patients
    $total_patients = $pdo->query("SELECT COUNT(*) FROM patients")->fetchColumn();
    
    // Get total doctors
    $total_doctors = $pdo->query("SELECT COUNT(*) FROM doctors")->fetchColumn();
    
} catch(PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - <?php echo $clinic_name; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-100">
    <header class="bg-blue-600 text-white p-4">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold"><?php echo $clinic_name; ?> - Reports</h1>
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
                <a href="billing.php" class="text-gray-500 hover:text-blue-600 py-4 px-1 font-medium">Billing</a>
                <a href="reports.php" class="border-b-2 border-blue-600 text-blue-600 py-4 px-1 font-medium">Reports</a>
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
                <h3 class="text-lg font-semibold text-gray-900">Total Revenue</h3>
                <p class="text-3xl font-bold text-green-600">$<?php echo number_format($total_revenue, 2); ?></p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900">This Month Appointments</h3>
                <p class="text-3xl font-bold text-blue-600"><?php echo $current_month_appointments; ?></p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900">Total Patients</h3>
                <p class="text-3xl font-bold text-purple-600"><?php echo $total_patients; ?></p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900">Total Doctors</h3>
                <p class="text-3xl font-bold text-yellow-600"><?php echo $total_doctors; ?></p>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Monthly Appointments Chart -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Monthly Appointments</h3>
                <canvas id="appointmentsChart" width="400" height="200"></canvas>
            </div>

            <!-- Revenue Chart -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Monthly Revenue</h3>
                <canvas id="revenueChart" width="400" height="200"></canvas>
            </div>
        </div>

        <!-- Statistics Tables -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Patient Statistics -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Patient Statistics</h3>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <?php foreach($patient_stats as $stat): ?>
                            <div class="flex justify-between items-center">
                                <span class="text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($stat['species'] ?? 'Unknown'); ?>
                                </span>
                                <span class="text-sm text-gray-500">
                                    <?php echo $stat['count']; ?> patients
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Doctor Statistics -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Doctor Statistics</h3>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <?php foreach($doctor_stats as $stat): ?>
                            <div class="flex justify-between items-center">
                                <span class="text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($stat['name'] ?? 'Unknown'); ?>
                                </span>
                                <span class="text-sm text-gray-500">
                                    <?php echo $stat['appointment_count']; ?> appointments
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Monthly Appointments Chart
        const appointmentsCtx = document.getElementById('appointmentsChart').getContext('2d');
        new Chart(appointmentsCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_map(function($item) {
                    $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                    return $months[$item['month'] - 1] ?? 'Unknown';
                }, $monthly_appointments)); ?>,
                datasets: [{
                    label: 'Appointments',
                    data: <?php echo json_encode(array_column($monthly_appointments, 'count')); ?>,
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Revenue Chart
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        new Chart(revenueCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_map(function($item) {
                    $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                    return $months[$item['month'] - 1] ?? 'Unknown';
                }, $revenue_data)); ?>,
                datasets: [{
                    label: 'Revenue ($)',
                    data: <?php echo json_encode(array_column($revenue_data, 'total_revenue')); ?>,
                    backgroundColor: 'rgba(34, 197, 94, 0.8)',
                    borderColor: 'rgb(34, 197, 94)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html> 