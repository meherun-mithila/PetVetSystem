<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_type'] !== 'user') {
    header("Location: ../index.php");
    exit();
}

$clinic_name = "Caring Paws Veterinary Clinic";
require_once '../config.php';

$doctors = [];
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
    <title>Doctor Locations - <?php echo $clinic_name; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        #map {
            height: 500px;
            width: 100%;
            border-radius: 0.5rem;
        }
    </style>
</head>
<body class="bg-gray-100">
    <header class="bg-blue-600 text-white p-4">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold"><?php echo $clinic_name; ?> - Doctor Locations</h1>
            <a href="dashboard.php" class="bg-blue-700 px-4 py-2 rounded">Dashboard</a>
        </div>
    </header>

    <nav class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-6">
            <div class="flex space-x-8">
                <a href="dashboard.php" class="text-gray-500 hover:text-blue-600 py-4 px-1 font-medium">Dashboard</a>
                <a href="pets.php" class="text-gray-500 hover:text-blue-600 py-4 px-1 font-medium">My Pets</a>
                <a href="appointments.php" class="text-gray-500 hover:text-blue-600 py-4 px-1 font-medium">Appointments</a>
                <a href="medical_records.php" class="text-gray-500 hover:text-blue-600 py-4 px-1 font-medium">Medical Records</a>
                <a href="locations.php" class="border-b-2 border-blue-600 text-blue-600 py-4 px-1 font-medium">Locations</a>
                <a href="profile.php" class="text-gray-500 hover:text-blue-600 py-4 px-1 font-medium">Profile</a>
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
                <h3 class="text-lg font-semibold text-gray-900">Available Doctors</h3>
                <p class="text-3xl font-bold text-blue-600"><?php echo count($doctors); ?></p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900">Cities Covered</h3>
                <p class="text-3xl font-bold text-green-600">
                    <?php 
                    $cities = array_unique(array_column($doctors, 'location_city'));
                    echo count(array_filter($cities));
                    ?>
                </p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900">Specializations</h3>
                <p class="text-3xl font-bold text-purple-600">
                    <?php 
                    $specializations = array_unique(array_column($doctors, 'specialization'));
                    echo count($specializations);
                    ?>
                </p>
            </div>
        </div>


        <!-- Doctor Locations List -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Available Doctors by Location</h3>
            </div>
            <div class="p-6">
                <?php if (!empty($doctors)): ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php foreach($doctors as $doctor): ?>
                            <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                                <div class="flex items-start space-x-3">
                                    <div class="flex-shrink-0">
                                        <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h4 class="text-lg font-semibold text-gray-900">
                                            Dr. <?php echo htmlspecialchars($doctor['name'] ?? 'Unknown'); ?>
                                        </h4>
                                        <p class="text-sm text-gray-600">
                                            <?php echo htmlspecialchars($doctor['specialization'] ?? 'General'); ?>
                                        </p>
                                        <p class="text-sm text-gray-500 mt-1">
                                            📍 <?php echo htmlspecialchars($doctor['location_city'] ?? $doctor['area'] ?? 'N/A'); ?>
                                        </p>
                                        <p class="text-xs text-gray-400 mt-1">
                                            📞 <?php echo htmlspecialchars($doctor['phone'] ?? 'N/A'); ?>
                                        </p>
                                        <?php if (!empty($doctor['address'])): ?>
                                            <p class="text-xs text-gray-400 mt-1">
                                                🏠 <?php echo htmlspecialchars($doctor['address']); ?>
                                            </p>
                                        <?php endif; ?>
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
    </main>

    <script>
        // Initialize map
        const map = L.map('map').setView([23.8103, 90.4125], 7); // Centered on Bangladesh

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        // Add markers for each doctor
        <?php foreach($doctors as $doctor): ?>
            <?php if (!empty($doctor['latitude']) && !empty($doctor['longitude'])): ?>
                const marker<?php echo $doctor['doctor_id']; ?> = L.marker([<?php echo $doctor['latitude']; ?>, <?php echo $doctor['longitude']; ?>]).addTo(map);
                
                marker<?php echo $doctor['doctor_id']; ?>.bindPopup(`
                    <div class="p-2">
                        <h3 class="font-semibold text-lg">Dr. <?php echo htmlspecialchars($doctor['name'] ?? 'Unknown'); ?></h3>
                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($doctor['specialization'] ?? 'General'); ?></p>
                        <p class="text-sm text-gray-500">📍 <?php echo htmlspecialchars($doctor['location_city'] ?? $doctor['area'] ?? 'N/A'); ?></p>
                        <p class="text-sm text-gray-500">📞 <?php echo htmlspecialchars($doctor['phone'] ?? 'N/A'); ?></p>
                        <?php if (!empty($doctor['address'])): ?>
                            <p class="text-sm text-gray-500">🏠 <?php echo htmlspecialchars($doctor['address']); ?></p>
                        <?php endif; ?>
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 mt-2">
                            Available
                        </span>
                    </div>
                `);
            <?php endif; ?>
        <?php endforeach; ?>
    </script>
</body>
</html> 