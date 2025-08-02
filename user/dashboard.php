<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_type'] !== 'user') {
    header("Location: ../index.php");
    exit();
}

$clinic_name = "Caring Paws Veterinary Clinic";
require_once '../config.php';

// Initialize variables
$user_pets = [];
$recent_appointments = [];
$medical_records = [];
$upcoming_appointments = [];
$error_message = "";

try {
    $user_id = $_SESSION['user_id'];
    
    // Get user's pets
    $stmt = $pdo->prepare("
        SELECT * FROM patients 
        WHERE owner_id = ? 
        ORDER BY created_at DESC
    ");
    $stmt->execute([$user_id]);
    $user_pets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get recent appointments
    $stmt = $pdo->prepare("
        SELECT a.*, p.animal_name, p.species, d.name as doctor_name
        FROM appointments a
        JOIN patients p ON a.patient_id = p.patient_id
        JOIN doctors d ON a.doctor_id = d.doctor_id
        WHERE p.owner_id = ?
        ORDER BY a.appointment_date DESC, a.appointment_time DESC
        LIMIT 5
    ");
    $stmt->execute([$user_id]);
    $recent_appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get upcoming appointments
    $stmt = $pdo->prepare("
        SELECT a.*, p.animal_name, p.species, d.name as doctor_name
        FROM appointments a
        JOIN patients p ON a.patient_id = p.patient_id
        JOIN doctors d ON a.doctor_id = d.doctor_id
        WHERE p.owner_id = ? AND a.appointment_date >= CURDATE() AND a.status = 'Scheduled'
        ORDER BY a.appointment_date ASC, a.appointment_time ASC
        LIMIT 5
    ");
    $stmt->execute([$user_id]);
    $upcoming_appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get recent medical records
    $stmt = $pdo->prepare("
        SELECT mr.*, p.animal_name, p.species, d.name as doctor_name
        FROM medicalrecords mr
        JOIN patients p ON mr.patient_id = p.patient_id
        JOIN doctors d ON mr.doctor_id = d.doctor_id
        WHERE p.owner_id = ?
        ORDER BY mr.date DESC
        LIMIT 5
    ");
    $stmt->execute([$user_id]);
    $medical_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $error_message = "Database connection failed.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - <?php echo $clinic_name; ?></title>
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
                <a href="dashboard.php" class="text-vet-blue font-semibold border-b-2 border-vet-blue pb-2">Dashboard</a>
                <a href="pets.php" class="text-gray-600 hover:text-vet-blue transition-colors">My Pets</a>
                <a href="appointments.php" class="text-gray-600 hover:text-vet-blue transition-colors">Appointments</a>
                <a href="medical_records.php" class="text-gray-600 hover:text-vet-blue transition-colors">Medical Records</a>
                <a href="locations.php" class="text-gray-600 hover:text-vet-blue transition-colors">Locations</a>
                <a href="profile.php" class="text-gray-600 hover:text-vet-blue transition-colors">Profile</a>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-6 py-8">
        <!-- Quick Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="text-3xl text-blue-500 mr-4">🐕</div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-700">My Pets</h3>
                        <p class="text-3xl font-bold text-vet-blue"><?php echo count($user_pets); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="text-3xl text-green-500 mr-4">📅</div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-700">Upcoming</h3>
                        <p class="text-3xl font-bold text-vet-blue"><?php echo count($upcoming_appointments); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="text-3xl text-purple-500 mr-4">📋</div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-700">Medical Records</h3>
                        <p class="text-3xl font-bold text-vet-blue"><?php echo count($medical_records); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="text-3xl text-yellow-500 mr-4">💰</div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-700">Total Bills</h3>
                        <p class="text-3xl font-bold text-vet-blue">৳<?php 
                            $total_bills = 0;
                            foreach($medical_records as $record) {
                                $total_bills += $record['bills'] ?? 0;
                            }
                            echo number_format($total_bills, 2);
                        ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- My Pets -->
            <div class="bg-white rounded-lg shadow-md">
                <div class="p-6 border-b border-gray-200 flex justify-between items-center">
                    <h2 class="text-xl font-bold text-gray-800">My Pets</h2>
                    <a href="pets.php" class="text-vet-blue hover:text-vet-dark-blue text-sm">View All</a>
                </div>
                <div class="p-6">
                    <?php if (!empty($user_pets)): ?>
                        <div class="space-y-4">
                            <?php foreach(array_slice($user_pets, 0, 3) as $pet): ?>
                            <div class="border rounded-lg p-4 hover:shadow-md transition-shadow">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h4 class="font-semibold text-lg text-gray-800"><?php echo htmlspecialchars($pet['animal_name']); ?></h4>
                                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($pet['species']); ?> • <?php echo htmlspecialchars($pet['breed']); ?></p>
                                        <p class="text-sm text-gray-500">Age: <?php echo htmlspecialchars($pet['age']); ?> years</p>
                                    </div>
                                    <div class="text-right">
                                        <span class="px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            Active
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-gray-500 text-center py-8">No pets registered yet.</p>
                        <div class="text-center">
                            <a href="pets.php?action=add" class="bg-vet-blue hover:bg-vet-dark-blue text-white px-4 py-2 rounded-lg transition-colors">
                                Register Pet
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Upcoming Appointments -->
            <div class="bg-white rounded-lg shadow-md">
                <div class="p-6 border-b border-gray-200 flex justify-between items-center">
                    <h2 class="text-xl font-bold text-gray-800">Upcoming Appointments</h2>
                    <a href="appointments.php" class="text-vet-blue hover:text-vet-dark-blue text-sm">View All</a>
                </div>
                <div class="p-6">
                    <?php if (!empty($upcoming_appointments)): ?>
                        <div class="space-y-4">
                            <?php foreach($upcoming_appointments as $appointment): ?>
                            <div class="border rounded-lg p-4 hover:shadow-md transition-shadow">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h4 class="font-semibold text-lg text-gray-800"><?php echo htmlspecialchars($appointment['animal_name']); ?></h4>
                                        <p class="text-sm text-gray-600">Dr. <?php echo htmlspecialchars($appointment['doctor_name']); ?></p>
                                        <p class="text-sm text-gray-500"><?php echo date('M j, Y', strtotime($appointment['appointment_date'])); ?></p>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-semibold text-vet-coral"><?php echo date('g:i A', strtotime($appointment['appointment_time'])); ?></p>
                                        <span class="px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            Scheduled
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-gray-500 text-center py-8">No upcoming appointments.</p>
                        <div class="text-center">
                            <a href="appointments.php?action=book" class="bg-vet-blue hover:bg-vet-dark-blue text-white px-4 py-2 rounded-lg transition-colors">
                                Book Appointment
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Medical Records -->
        <div class="mt-8 bg-white rounded-lg shadow-md">
            <div class="p-6 border-b border-gray-200 flex justify-between items-center">
                <h2 class="text-xl font-bold text-gray-800">Recent Medical Records</h2>
                <a href="medical_records.php" class="text-vet-blue hover:text-vet-dark-blue text-sm">View All</a>
            </div>
            <div class="p-6">
                <?php if (!empty($medical_records)): ?>
                    <div class="space-y-4">
                        <?php foreach($medical_records as $record): ?>
                        <div class="border-l-4 border-vet-blue pl-4 py-2">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h4 class="font-semibold text-gray-800"><?php echo htmlspecialchars($record['animal_name']); ?></h4>
                                    <p class="text-sm text-gray-600"><?php echo htmlspecialchars($record['diagnosis']); ?></p>
                                    <p class="text-sm text-gray-500">Dr. <?php echo htmlspecialchars($record['doctor_name']); ?></p>
                                </div>
                                <div class="text-right">
                                    <p class="font-semibold text-vet-coral">৳<?php echo number_format($record['bills'], 2); ?></p>
                                    <p class="text-xs text-gray-500"><?php echo date('M j, Y', strtotime($record['date'])); ?></p>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-gray-500 text-center py-8">No medical records found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html> 