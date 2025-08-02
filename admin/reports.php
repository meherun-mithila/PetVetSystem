<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

$clinic_name = "Caring Paws Veterinary Clinic";
require_once '../config.php';

$total_patients = 0;
$total_appointments = 0;
$total_doctors = 0;
$total_users = 0;
$total_revenue = 0;
$monthly_appointments = [];
$error_message = "";

try {
    // Get total counts
    $total_patients = $pdo->query("SELECT COUNT(*) FROM patients")->fetchColumn();
    $total_appointments = $pdo->query("SELECT COUNT(*) FROM appointments")->fetchColumn();
    $total_doctors = $pdo->query("SELECT COUNT(*) FROM doctors")->fetchColumn();
    $total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    
    // Get total revenue
    $total_revenue = $pdo->query("SELECT SUM(bills) FROM medicalrecords")->fetchColumn() ?: 0;
    
    // Check if appointments table has the correct column names
    $result = $pdo->query("DESCRIBE appointments");
    $appointment_columns = $result->fetchAll(PDO::FETCH_COLUMN);
    
    $date_column = in_array('appointment_date', $appointment_columns) ? 'appointment_date' : 'date';
    
    // Get monthly appointments for the last 6 months
    $stmt = $pdo->prepare("
        SELECT DATE_FORMAT($date_column, '%Y-%m') as month, COUNT(*) as count
        FROM appointments
        WHERE $date_column >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT($date_column, '%Y-%m')
        ORDER BY month DESC
    ");
    $stmt->execute();
    $monthly_appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $error_message = "Failed to load reports: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - <?php echo $clinic_name; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <header class="bg-blue-600 text-white p-4">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold"><?php echo $clinic_name; ?> - Reports</h1>
            <a href="dashboard.php" class="bg-blue-700 px-4 py-2 rounded">Dashboard</a>
        </div>
    </header>

    <div class="max-w-7xl mx-auto p-6">
        <h2 class="text-3xl font-bold mb-6">Reports & Analytics</h2>
        
        <?php if (!empty($error_message)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="text-3xl text-blue-500 mr-4">🐕</div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-700">Total Patients</h3>
                        <p class="text-3xl font-bold text-blue-600"><?php echo $total_patients; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="text-3xl text-green-500 mr-4">📅</div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-700">Total Appointments</h3>
                        <p class="text-3xl font-bold text-green-600"><?php echo $total_appointments; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="text-3xl text-purple-500 mr-4">👨‍⚕️</div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-700">Doctors</h3>
                        <p class="text-3xl font-bold text-purple-600"><?php echo $total_doctors; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="text-3xl text-yellow-500 mr-4">💰</div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-700">Total Revenue</h3>
                        <p class="text-3xl font-bold text-yellow-600">৳<?php echo number_format($total_revenue, 2); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Monthly Appointments Chart -->
        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <h3 class="text-xl font-bold text-gray-800 mb-4">Monthly Appointments (Last 6 Months)</h3>
            <?php if (!empty($monthly_appointments)): ?>
                <div class="space-y-4">
                    <?php foreach($monthly_appointments as $month): ?>
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-700">
                            <?php echo date('F Y', strtotime($month['month'] . '-01')); ?>
                        </span>
                        <div class="flex items-center">
                            <div class="w-32 bg-gray-200 rounded-full h-2 mr-2">
                                <div class="bg-blue-600 h-2 rounded-full" style="width: <?php echo min(100, ($month['count'] / max(array_column($monthly_appointments, 'count'))) * 100); ?>%"></div>
                            </div>
                            <span class="text-sm font-medium text-gray-900"><?php echo $month['count']; ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-gray-500 text-center py-8">No appointment data available.</p>
            <?php endif; ?>
        </div>

        <!-- Quick Reports -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-xl font-bold text-gray-800 mb-4">System Overview</h3>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Pet Owners:</span>
                        <span class="font-medium"><?php echo $total_users; ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Active Patients:</span>
                        <span class="font-medium"><?php echo $total_patients; ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Available Doctors:</span>
                        <span class="font-medium"><?php echo $total_doctors; ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Total Appointments:</span>
                        <span class="font-medium"><?php echo $total_appointments; ?></span>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-xl font-bold text-gray-800 mb-4">Financial Summary</h3>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Total Revenue:</span>
                        <span class="font-medium text-green-600">৳<?php echo number_format($total_revenue, 2); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Average per Record:</span>
                        <span class="font-medium">
                            ৳<?php 
                            try {
                                $medical_records_count = $pdo->query("SELECT COUNT(*) FROM medicalrecords")->fetchColumn();
                                echo $total_revenue > 0 ? number_format($total_revenue / max(1, $medical_records_count), 2) : '0.00';
                            } catch(PDOException $e) {
                                echo '0.00';
                            }
                            ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 