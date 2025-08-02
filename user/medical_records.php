<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_type'] !== 'user') {
    header("Location: ../index.php");
    exit();
}

$clinic_name = "Caring Paws Veterinary Clinic";
require_once '../config.php';

$user_id = $_SESSION['user_id'];
$medical_records = [];
$total_bills = 0;
$error_message = "";

// Get user's medical records
try {
    $stmt = $pdo->prepare("
        SELECT mr.*, p.animal_name, p.species, d.name as doctor_name, d.specialization
        FROM medicalrecords mr
        JOIN patients p ON mr.patient_id = p.patient_id
        JOIN doctors d ON mr.doctor_id = d.doctor_id
        WHERE p.owner_id = ?
        ORDER BY mr.date DESC
    ");
    $stmt->execute([$user_id]);
    $medical_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate total bills
    foreach($medical_records as $record) {
        $total_bills += $record['bills'] ?? 0;
    }
} catch(PDOException $e) {
    $error_message = "Failed to load medical records.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical Records - <?php echo $clinic_name; ?></title>
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
                        <p class="text-blue-200 text-sm">Pet Owner Dashboard</p>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="text-right">
                        <p class="font-semibold"><?php echo htmlspecialchars($_SESSION['user_name']); ?></p>
                        <p class="text-blue-200 text-sm">Pet Owner</p>
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
                <a href="dashboard.php" class="text-gray-600 hover:text-vet-blue transition-colors">Dashboard</a>
                <a href="pets.php" class="text-gray-600 hover:text-vet-blue transition-colors">My Pets</a>
                <a href="appointments.php" class="text-gray-600 hover:text-vet-blue transition-colors">Appointments</a>
                <a href="medical_records.php" class="text-vet-blue font-semibold border-b-2 border-vet-blue pb-2">Medical Records</a>
                <a href="profile.php" class="text-gray-600 hover:text-vet-blue transition-colors">Profile</a>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-6 py-8">
        <!-- Messages -->
        <?php if (!empty($error_message)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <!-- Header -->
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800">Medical Records</h1>
            <div class="text-right">
                <p class="text-sm text-gray-600">Total Bills</p>
                <p class="text-2xl font-bold text-vet-coral">৳<?php echo number_format($total_bills, 2); ?></p>
            </div>
        </div>

        <!-- Medical Records List -->
        <?php if (!empty($medical_records)): ?>
            <div class="space-y-6">
                <?php foreach($medical_records as $record): ?>
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($record['animal_name']); ?></h3>
                            <p class="text-gray-600"><?php echo htmlspecialchars($record['species']); ?></p>
                        </div>
                        <div class="text-right">
                            <p class="text-2xl font-bold text-vet-coral">৳<?php echo number_format($record['bills'], 2); ?></p>
                            <p class="text-sm text-gray-500"><?php echo date('M j, Y', strtotime($record['date'])); ?></p>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h4 class="font-semibold text-gray-800 mb-2">Diagnosis</h4>
                            <p class="text-gray-600"><?php echo htmlspecialchars($record['diagnosis']); ?></p>
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-800 mb-2">Treatment</h4>
                            <p class="text-gray-600"><?php echo htmlspecialchars($record['treatment']); ?></p>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4 text-sm">
                        <div>
                            <strong class="text-gray-700">Doctor:</strong><br>
                            <span class="text-gray-600">Dr. <?php echo htmlspecialchars($record['doctor_name']); ?></span><br>
                            <span class="text-gray-500"><?php echo htmlspecialchars($record['specialization']); ?></span>
                        </div>
                        <div>
                            <strong class="text-gray-700">Medication:</strong><br>
                            <span class="text-gray-600"><?php echo htmlspecialchars($record['medication'] ?: 'None prescribed'); ?></span>
                        </div>
                        <div>
                            <strong class="text-gray-700">Follow-up:</strong><br>
                            <span class="text-gray-600"><?php echo htmlspecialchars($record['follow_up'] ?: 'No follow-up required'); ?></span>
                        </div>
                    </div>
                    
                    <?php if (!empty($record['notes'])): ?>
                        <div class="mt-4 p-3 bg-gray-50 rounded-lg">
                            <strong class="text-gray-700">Notes:</strong>
                            <p class="text-gray-600 mt-1"><?php echo htmlspecialchars($record['notes']); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-12">
                <div class="text-6xl mb-4">📋</div>
                <h2 class="text-2xl font-bold text-gray-800 mb-2">No Medical Records</h2>
                <p class="text-gray-600 mb-6">No medical records found for your pets.</p>
                <a href="appointments.php" class="bg-vet-blue hover:bg-vet-dark-blue text-white px-6 py-3 rounded-lg transition-colors">
                    Book an Appointment
                </a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html> 