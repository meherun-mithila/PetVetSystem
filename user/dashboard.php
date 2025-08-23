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
    
    // Get user's pets - check if patients table exists and has correct structure
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM patients 
            WHERE owner_id = ? 
            ORDER BY patient_id DESC
        ");
        $stmt->execute([$user_id]);
        $user_pets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        // If patients table doesn't exist, try alternative table names
        try {
            $stmt = $pdo->prepare("
                SELECT * FROM pets 
                WHERE owner_id = ? 
                ORDER BY pet_id DESC
            ");
            $stmt->execute([$user_id]);
            $user_pets = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e2) {
            $user_pets = [];
        }
    }
    
    // Get recent appointments - handle different table structures
    try {
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
    } catch(PDOException $e) {
        // Try alternative appointment table structure
        try {
            $stmt = $pdo->prepare("
                SELECT a.*, p.animal_name, p.species, d.name as doctor_name
                FROM appointments a
                JOIN patients p ON a.patient_id = p.patient_id
                JOIN doctors d ON a.doctor_id = d.doctor_id
                WHERE p.owner_id = ?
                ORDER BY a.date DESC, a.time DESC
                LIMIT 5
            ");
            $stmt->execute([$user_id]);
            $recent_appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e2) {
            $recent_appointments = [];
        }
    }
    
    // Get upcoming appointments
    try {
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
    } catch(PDOException $e) {
        // Try alternative appointment table structure
        try {
            $stmt = $pdo->prepare("
                SELECT a.*, p.animal_name, p.species, d.name as doctor_name
                FROM appointments a
                JOIN patients p ON a.patient_id = p.patient_id
                JOIN doctors d ON a.doctor_id = d.doctor_id
                WHERE p.owner_id = ? AND a.date >= CURDATE() AND a.status = 'Scheduled'
                ORDER BY a.date ASC, a.time ASC
                LIMIT 5
            ");
            $stmt->execute([$user_id]);
            $upcoming_appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e2) {
            $upcoming_appointments = [];
        }
    }
    
    // Get recent medical records - try different table names
    try {
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
        // Try alternative medical records table names
        try {
            $stmt = $pdo->prepare("
                SELECT mr.*, p.animal_name, p.species, d.name as doctor_name
                FROM medical_records mr
                JOIN patients p ON mr.patient_id = p.patient_id
                JOIN doctors d ON mr.doctor_id = d.doctor_id
                WHERE p.owner_id = ?
                ORDER BY mr.date DESC
                LIMIT 5
            ");
            $stmt->execute([$user_id]);
            $medical_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e2) {
            try {
                $stmt = $pdo->prepare("
                    SELECT mr.*, p.animal_name, p.species, d.name as doctor_name
                    FROM medical_reports mr
                    JOIN patients p ON mr.patient_id = p.patient_id
                    JOIN doctors d ON mr.doctor_id = d.doctor_id
                    WHERE p.owner_id = ?
                    ORDER BY mr.date DESC
                    LIMIT 5
                ");
                $stmt->execute([$user_id]);
                $medical_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch(PDOException $e3) {
                $medical_records = [];
            }
        }
    }
    
    // Get available pets for adoption - try different table names
    try {
        $stmt = $pdo->prepare("
            SELECT al.*, u.name as posted_by_name
            FROM adoptionlistings al
            JOIN users u ON al.posted_by = u.user_id
            WHERE al.status = 'Available'
            ORDER BY al.listing_id DESC
            LIMIT 3
        ");
        $stmt->execute();
        $available_pets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        try {
            $stmt = $pdo->prepare("
                SELECT al.*, u.name as posted_by_name
                FROM adoption_listings al
                JOIN users u ON al.posted_by = u.user_id
                WHERE al.status = 'Available'
                ORDER BY al.listing_id DESC
                LIMIT 3
            ");
            $stmt->execute();
            $available_pets = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e2) {
            $available_pets = [];
        }
    }
    
    // Get user's adoption requests - try different table names
    try {
        $stmt = $pdo->prepare("
            SELECT ar.*, al.animal_name, al.species, al.age
            FROM adoptionrequests ar
            JOIN adoptionlistings al ON ar.listing_id = al.listing_id
            WHERE ar.requested_by = ?
            ORDER BY ar.date DESC
            LIMIT 3
        ");
        $stmt->execute([$user_id]);
        $adoption_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        try {
            $stmt = $pdo->prepare("
                SELECT ar.*, al.animal_name, al.species, al.age
                FROM adoption_requests ar
                JOIN adoption_listings al ON ar.listing_id = al.listing_id
                WHERE ar.requested_by = ?
                ORDER BY ar.date DESC
                LIMIT 3
            ");
            $stmt->execute([$user_id]);
            $adoption_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e2) {
            $adoption_requests = [];
        }
    }
    
    // Get user's inquiries - try different table names
    try {
        $stmt = $pdo->prepare("
            SELECT i.*, u.name as user_name
            FROM inquiries i
            JOIN users u ON i.user_id = u.user_id
            WHERE i.user_id = ?
            ORDER BY i.created_at DESC
            LIMIT 5
        ");
        $stmt->execute([$user_id]);
        $user_inquiries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        try {
            $stmt = $pdo->prepare("
                SELECT i.*, u.name as user_name
                FROM inquiries i
                JOIN users u ON i.user_id = u.user_id
                WHERE i.user_id = ?
                ORDER BY i.date DESC
                LIMIT 5
            ");
            $stmt->execute([$user_id]);
            $user_inquiries = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e2) {
            $user_inquiries = [];
        }
    }
    
} catch(PDOException $e) {
    $error_message = "Database connection failed: " . $e->getMessage();
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
                <a href="adoption.php" class="text-gray-600 hover:text-vet-blue transition-colors">Adoption</a>
                <a href="notifications.php" class="text-gray-600 hover:text-vet-blue transition-colors">Notifications</a>
                <a href="medical_records.php" class="text-gray-600 hover:text-vet-blue transition-colors">Medical Records</a>
                <a href="vaccine_records.php" class="text-gray-600 hover:text-vet-blue transition-colors">Vaccine Records</a>
                <a href="locations.php" class="text-gray-600 hover:text-vet-blue transition-colors">Locations</a>
                <a href="profile.php" class="text-gray-600 hover:text-vet-blue transition-colors">Profile</a>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-6 py-8">

        <?php if (!empty($error_message)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <strong>Error:</strong> <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        

        
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

    <!-- Quick Actions -->
    <div class="mt-8 bg-white rounded-lg shadow-md">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-xl font-bold text-gray-800">Quick Actions</h2>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <a href="appointments.php?action=book" class="block p-4 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 transition-colors">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <div>
                            <h4 class="font-medium text-gray-900">Book Appointment</h4>
                            <p class="text-sm text-gray-500">Schedule a visit</p>
                        </div>
                    </div>
                </a>

                <a href="pets.php?action=add" class="block p-4 bg-green-50 border border-green-200 rounded-lg hover:bg-green-100 transition-colors">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <h4 class="font-medium text-gray-900">Register Pet</h4>
                            <p class="text-sm text-gray-500">Add new pet</p>
                        </div>
                    </div>
                </a>

                <a href="medical_records.php" class="block p-4 bg-purple-50 border border-purple-200 rounded-lg hover:bg-purple-100 transition-colors">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-purple-500 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <div>
                            <h4 class="font-medium text-gray-900">Medical Records</h4>
                            <p class="text-sm text-gray-500">View health history</p>
                        </div>
                    </div>
                </a>

                <a href="vaccine_records.php" class="block p-4 bg-orange-50 border border-orange-200 rounded-lg hover:bg-orange-100 transition-colors">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-orange-500 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.5 12h15m0 0l-6.75-6.75M19.5 12l-6.75 6.75"></path>
                            </svg>
                        </div>
                        <div>
                            <h4 class="font-medium text-gray-900">Vaccine Records</h4>
                            <p class="text-sm text-gray-500">View vaccinations</p>
                        </div>
                    </div>
                </a>

                <a href="adoption.php" class="block p-4 bg-pink-50 border border-pink-200 rounded-lg hover:bg-pink-100 transition-colors">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-pink-500 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <h4 class="font-medium text-gray-900">Adopt a Pet</h4>
                            <p class="text-sm text-gray-500">Browse available pets</p>
                        </div>
                    </div>
                </a>

                <a href="notifications.php" class="block p-4 bg-teal-50 border border-teal-200 rounded-lg hover:bg-teal-100 transition-colors">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-teal-500 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM4 19h6l-6 6v-6zM4 5h6l-6 6V5z"></path>
                            </svg>
                        </div>
                        <div>
                            <h4 class="font-medium text-gray-900">Notifications</h4>
                            <p class="text-sm text-gray-500">View updates</p>
                        </div>
                    </div>
                </a>

                <button onclick="showInquiryForm()" class="block p-4 bg-indigo-50 border border-indigo-200 rounded-lg hover:bg-indigo-100 transition-colors">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-indigo-500 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <h4 class="font-medium text-gray-900">Ask Question</h4>
                            <p class="text-sm text-gray-500">Get help & support</p>
                        </div>
                    </div>
                </button>
            </div>
        </div>
    </div>

    <!-- Inquiries Section -->
    <div class="mt-8 bg-white rounded-lg shadow-md">
        <div class="p-6 border-b border-gray-200 flex justify-between items-center">
            <h2 class="text-xl font-bold text-gray-800">My Inquiries & Questions</h2>
            <button onclick="showInquiryForm()" class="bg-vet-blue hover:bg-vet-dark-blue text-white px-4 py-2 rounded-lg transition-colors">
                + Ask New Question
            </button>
        </div>
        <div class="p-6">
            <?php if (!empty($user_inquiries)): ?>
                <div class="space-y-4">
                    <?php foreach($user_inquiries as $inquiry): ?>
                    <div class="border rounded-lg p-4 hover:shadow-md transition-shadow">
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <h4 class="font-semibold text-lg text-gray-800"><?php echo htmlspecialchars($inquiry['subject'] ?? 'No Subject'); ?></h4>
                                <p class="text-sm text-gray-600 mt-1"><?php echo htmlspecialchars($inquiry['message']); ?></p>
                                <div class="flex items-center space-x-4 mt-2 text-xs text-gray-500">
                                    <span>Asked: <?php echo date('M j, Y', strtotime($inquiry['created_at'] ?? $inquiry['date'] ?? 'now')); ?></span>
                                    <span class="px-2 py-1 rounded-full text-xs font-medium 
                                        <?php 
                                            $status = $inquiry['status'] ?? 'Pending';
                                            echo $status === 'Replied' ? 'bg-green-100 text-green-800' : 
                                                ($status === 'In Progress' ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800'); 
                                        ?>">
                                        <?php echo htmlspecialchars($status); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-8">
                    <div class="text-6xl mb-4">💬</div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No Inquiries Yet</h3>
                    <p class="text-gray-500 mb-4">Have a question about pet care, appointments, or services?</p>
                    <button onclick="showInquiryForm()" class="bg-vet-blue hover:bg-vet-dark-blue text-white px-4 py-2 rounded-lg transition-colors">
                        Ask Your First Question
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Inquiry Form Modal -->
    <div id="inquiryModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full">
                <div class="flex items-center justify-between p-6 border-b border-gray-200">
                    <h3 class="text-xl font-semibold text-gray-900">Ask a Question</h3>
                    <button onclick="hideInquiryForm()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <form id="inquiryForm" class="p-6 space-y-4">
                    <div>
                        <label for="subject" class="block text-sm font-medium text-gray-700 mb-2">Subject</label>
                        <input type="text" id="subject" name="subject" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-vet-blue focus:border-transparent"
                               placeholder="Brief description of your question">
                    </div>
                    
                    <div>
                        <label for="message" class="block text-sm font-medium text-gray-700 mb-2">Your Question</label>
                        <textarea id="message" name="message" rows="4" required
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-vet-blue focus:border-transparent"
                                  placeholder="Please describe your question or concern in detail..."></textarea>
                    </div>
                    
                    <div class="flex justify-end space-x-3 pt-4">
                        <button type="button" onclick="hideInquiryForm()" 
                                class="px-4 py-2 text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300 transition-colors">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 bg-vet-blue text-white rounded-md hover:bg-vet-dark-blue transition-colors">
                            Submit Question
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
        function requestAdoption(listingId) {
            if (confirm('Are you sure you want to request adoption for this pet? You will be notified once the request is reviewed.')) {
                // Create a form and submit it
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'adoption.php';
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'request_adoption';
                
                const listingInput = document.createElement('input');
                listingInput.type = 'hidden';
                listingInput.name = 'listing_id';
                listingInput.value = listingId;
                
                form.appendChild(actionInput);
                form.appendChild(listingInput);
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Inquiry form functions
        function showInquiryForm() {
            document.getElementById('inquiryModal').classList.remove('hidden');
        }
        
        function hideInquiryForm() {
            document.getElementById('inquiryModal').classList.add('hidden');
            document.getElementById('inquiryForm').reset();
        }
        
        // Handle inquiry form submission
        document.getElementById('inquiryForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'submit_inquiry');
            
            fetch('submit_inquiry.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Your question has been submitted successfully! We will get back to you soon.');
                    hideInquiryForm();
                    // Reload the page to show the new inquiry
                    location.reload();
                } else {
                    alert('Error submitting question: ' + (data.message || 'Please try again.'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error submitting question. Please try again.');
            });
        });
    </script>
</body>
</html> 