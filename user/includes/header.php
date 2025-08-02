<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_type'] !== 'user') {
    header("Location: ../index.php");
    exit();
}

$clinic_name = "Caring Paws Veterinary Clinic";
require_once '../config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'User Dashboard'; ?> - <?php echo $clinic_name; ?></title>
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
                <a href="dashboard.php" class="<?php echo $current_page === 'dashboard' ? 'text-vet-blue font-semibold border-b-2 border-vet-blue pb-2' : 'text-gray-600 hover:text-vet-blue transition-colors'; ?>">Dashboard</a>
                <a href="pets.php" class="<?php echo $current_page === 'pets' ? 'text-vet-blue font-semibold border-b-2 border-vet-blue pb-2' : 'text-gray-600 hover:text-vet-blue transition-colors'; ?>">My Pets</a>
                <a href="appointments.php" class="<?php echo $current_page === 'appointments' ? 'text-vet-blue font-semibold border-b-2 border-vet-blue pb-2' : 'text-gray-600 hover:text-vet-blue transition-colors'; ?>">Appointments</a>
                <a href="medical_records.php" class="<?php echo $current_page === 'medical_records' ? 'text-vet-blue font-semibold border-b-2 border-vet-blue pb-2' : 'text-gray-600 hover:text-vet-blue transition-colors'; ?>">Medical Records</a>
                <a href="profile.php" class="<?php echo $current_page === 'profile' ? 'text-vet-blue font-semibold border-b-2 border-vet-blue pb-2' : 'text-gray-600 hover:text-vet-blue transition-colors'; ?>">Profile</a>
            </div>
        </div>
    </nav> 