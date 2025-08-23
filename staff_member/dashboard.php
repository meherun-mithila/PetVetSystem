<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_type'] !== 'staff') {
    header("Location: ../index.php");
    exit();
}

require_once '../includes/status_helper.php';

$clinic_name = "Caring Paws Veterinary Clinic";

require_once '../config.php';

// Initialize variables with default values
$today_appointments = [];
$pending_appointments = [];
$recent_patients = [];
$error_message = "";
$staff_info = [];

try {
    $staff_id = $_SESSION['user_id'];
    
    // Get staff member information
    $stmt = $pdo->prepare("SELECT * FROM staff WHERE staff_id = ?");
    $stmt->execute([$staff_id]);
    $staff_info = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Check if appointments table has the new column names
    $result = $pdo->query("DESCRIBE appointments");
    $columns = $result->fetchAll(PDO::FETCH_COLUMN);
    
    $date_column = in_array('appointment_date', $columns) ? 'appointment_date' : 'date';
    $time_column = in_array('appointment_time', $columns) ? 'appointment_time' : 'time';
    
    // Get today's appointments
    $today_appointments = $pdo->query("
        SELECT a.*, p.animal_name, p.species, u.name as owner_name, u.phone, d.name as doctor_name
        FROM appointments a
        JOIN patients p ON a.patient_id = p.patient_id
        JOIN users u ON p.owner_id = u.user_id
        JOIN doctors d ON a.doctor_id = d.doctor_id
        WHERE DATE(a.$date_column) = CURDATE()
        ORDER BY a.$time_column
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Get pending appointments
    $pending_appointments = $pdo->query("
        SELECT a.*, p.animal_name, p.species, u.name as owner_name, u.phone, d.name as doctor_name
        FROM appointments a
        JOIN patients p ON a.patient_id = p.patient_id
        JOIN users u ON p.owner_id = u.user_id
        JOIN doctors d ON a.doctor_id = d.doctor_id
        WHERE a.status = 'Scheduled' AND a.$date_column >= CURDATE()
        ORDER BY a.$date_column, a.$time_column
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Check if patients table has created_at column
    $result = $pdo->query("DESCRIBE patients");
    $patient_columns = $result->fetchAll(PDO::FETCH_COLUMN);
    $order_by = in_array('created_at', $patient_columns) ? 'p.created_at DESC' : 'p.patient_id DESC';
    
    // Get recent patients
    $recent_patients = $pdo->query("
        SELECT p.*, u.name as owner_name, u.phone
        FROM patients p
        JOIN users u ON p.owner_id = u.user_id
        ORDER BY $order_by
        LIMIT 8
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
    <title>Staff Dashboard - <?php echo $clinic_name; ?></title>
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
    <header class="bg-gradient-to-r from-vet-blue to-vet-dark-blue text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-6 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center">
                    <span class="text-2xl mr-3">🐾</span>
                    <div>
                        <h1 class="text-xl font-bold"><?php echo $clinic_name; ?></h1>
                        <p class="text-blue-200 text-sm">Staff Dashboard</p>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="text-right">
                        <p class="font-semibold"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Staff Member'); ?></p>
                        <p class="text-blue-200 text-sm"><?php echo htmlspecialchars($staff_info['role'] ?? 'Staff'); ?></p>
                    </div>
                    <a href="../index.php?logout=1" class="bg-red-500 hover:bg-red-600 px-4 py-2 rounded-lg transition-colors">
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </header>

    <nav class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-6">
            <div class="flex space-x-8">
                <a href="dashboard.php" class="border-b-2 border-vet-blue text-vet-blue py-4 px-1 font-medium">Dashboard</a>
                <a href="appointments.php" class="text-gray-500 hover:text-vet-blue py-4 px-1 font-medium">Appointments</a>
                <a href="patients.php" class="text-gray-500 hover:text-vet-blue py-4 px-1 font-medium">Patients</a>
                <a href="doctors.php" class="text-gray-500 hover:text-vet-blue py-4 px-1 font-medium">Doctors</a>
                <a href="billing.php" class="text-gray-500 hover:text-vet-blue py-4 px-1 font-medium">Billing</a>
                <a href="medical_reports.php" class="text-gray-500 hover:text-vet-blue py-4 px-1 font-medium">Medical Reports</a>
                <a href="reports.php" class="text-gray-500 hover:text-vet-blue py-4 px-1 font-medium">Reports</a>
                <a href="profile.php" class="text-gray-500 hover:text-vet-blue py-4 px-1 font-medium">Profile</a>
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
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Pending</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo count($pending_appointments); ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Today's Appointments</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo count($today_appointments); ?></p>
                    </div>
                </div>
            </div>
        </div>



        <!-- Quick Actions -->
        <div class="mt-8 bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Quick Actions</h3>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <a href="appointments.php" class="block p-6 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 transition-colors">
                        <div class="flex items-center space-x-3">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-900">Schedule Appointment</h4>
                                <p class="text-sm text-gray-500">Manage appointments</p>
                            </div>
                        </div>
                    </a>

                    <a href="patients.php" class="block p-6 bg-green-50 border border-green-200 rounded-lg hover:bg-green-100 transition-colors">
                        <div class="flex items-center space-x-3">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-900">Register Patient</h4>
                                <p class="text-sm text-gray-500">Add new patients</p>
                            </div>
                        </div>
                    </a>

                    <a href="medical_reports.php" class="block p-6 bg-purple-50 border border-purple-200 rounded-lg hover:bg-purple-100 transition-colors">
                        <div class="flex items-center space-x-3">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 bg-purple-500 rounded-full flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-900">Medical Records</h4>
                                <p class="text-sm text-gray-500">View patient records</p>
                            </div>
                        </div>
                    </a>

                    <a href="vaccine_records.php" class="block p-6 bg-orange-50 border border-orange-200 rounded-lg hover:bg-orange-100 transition-colors">
                        <div class="flex items-center space-x-3">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 bg-orange-500 rounded-full flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.5 12h15m0 0l-6.75-6.75M19.5 12l-6.75 6.75"></path>
                                    </svg>
                                </div>
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-900">Vaccine Records</h4>
                                <p class="text-sm text-gray-500">Manage vaccinations</p>
                            </div>
                        </div>
                    </a>

                    <a href="billing.php" class="block p-6 bg-yellow-50 border border-yellow-200 rounded-lg hover:bg-yellow-100 transition-colors">
                        <div class="flex items-center space-x-3">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 bg-yellow-500 rounded-full flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                    </svg>
                                </div>
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-900">Billing</h4>
                                <p class="text-sm text-gray-500">Manage payments</p>
                            </div>
                        </div>
                    </a>

                    <a href="reports.php" class="block p-6 bg-red-50 border border-red-200 rounded-lg hover:bg-red-100 transition-colors">
                        <div class="flex items-center space-x-3">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 bg-red-500 rounded-full flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-900">Reports</h4>
                                <p class="text-sm text-gray-500">View analytics</p>
                            </div>
                        </div>
                    </a>

                    <a href="doctors.php" class="block p-6 bg-indigo-50 border border-indigo-200 rounded-lg hover:bg-indigo-100 transition-colors">
                        <div class="flex items-center space-x-3">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 bg-indigo-500 rounded-full flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-900">Doctors</h4>
                                <p class="text-sm text-gray-500">Manage doctors</p>
                            </div>
                        </div>
                    </a>

                    <a href="adoption.php" class="block p-6 bg-pink-50 border border-pink-200 rounded-lg hover:bg-pink-100 transition-colors">
                        <div class="flex items-center space-x-3">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 bg-pink-500 rounded-full flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-900">Adoption</h4>
                                <p class="text-sm text-gray-500">View adoption requests</p>
                            </div>
                        </div>
                    </a>

                    <button onclick="showArticlesModal()" class="block p-6 bg-teal-50 border border-teal-200 rounded-lg hover:bg-teal-100 transition-colors">
                        <div class="flex items-center space-x-3">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 bg-teal-500 rounded-full flex items-center justify-center">
                                    <span class="text-white text-lg">📚</span>
                                </div>
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-900">Articles & Inquiries</h4>
                                <p class="text-sm text-gray-500">Manage content & support</p>
                            </div>
                        </div>
                    </button>
                </div>
            </div>
        </div>
    </main>

    <!-- Articles & Inquiries Modal -->
    <div id="articlesModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-6xl w-full max-h-screen overflow-y-auto">
                <div class="flex items-center justify-between p-6 border-b border-gray-200">
                    <h3 class="text-2xl font-semibold text-gray-900">📚 Articles & Inquiries Management</h3>
                    <button onclick="hideArticlesModal()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <div class="p-6">
                    <!-- Tab Navigation -->
                    <div class="border-b border-gray-200 mb-6">
                        <nav class="flex space-x-8" aria-label="Tabs">
                            <button onclick="switchTab('articles')" id="articlesTab" class="border-b-2 border-teal-500 text-teal-600 whitespace-nowrap py-4 px-1 text-sm font-medium">
                                📖 Articles
                            </button>
                            <button onclick="switchTab('inquiries')" id="inquiriesTab" class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 text-sm font-medium">
                                💬 Inquiries
                            </button>
                        </nav>
                    </div>

                    <!-- Articles Tab Content -->
                    <div id="articlesContent" class="space-y-4">
                        <div class="flex justify-between items-center mb-4">
                            <h4 class="text-lg font-semibold text-gray-900">Manage Articles</h4>
                            <button onclick="showAddArticleForm()" class="bg-teal-600 text-white px-4 py-2 rounded hover:bg-teal-700">
                                + Add New Article
                            </button>
                        </div>
                        
                        <div id="articlesList" class="space-y-3">
                            <!-- Articles will be loaded here -->
                            <div class="text-center py-8">
                                <div class="text-4xl mb-2">📚</div>
                                <p class="text-gray-500">Loading articles...</p>
                            </div>
                        </div>
                    </div>

                    <!-- Inquiries Tab Content -->
                    <div id="inquiriesContent" class="hidden space-y-4">
                        <div class="flex justify-between items-center mb-4">
                            <h4 class="text-lg font-semibold text-gray-900">Manage Inquiries</h4>
                        </div>
                        
                        <div id="inquiriesList" class="space-y-3">
                            <!-- Inquiries will be loaded here -->
                            <div class="text-center py-8">
                                <div class="text-4xl mb-2">💬</div>
                                <p class="text-gray-500">Loading inquiries...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Article Modal -->
    <div id="addArticleModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-screen overflow-y-auto">
                <div class="flex items-center justify-between p-6 border-b border-gray-200">
                    <h3 class="text-xl font-semibold text-gray-900">Add New Article</h3>
                    <button onclick="hideAddArticleForm()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <div class="p-6">
                    <form id="addArticleForm" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Article Title *</label>
                            <input type="text" id="articleTitle" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500" placeholder="Enter article title">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Summary</label>
                            <textarea id="articleSummary" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500" placeholder="Brief summary of the article"></textarea>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Topic</label>
                            <select id="articleTopic" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500">
                                <option value="">Select a topic</option>
                                <option value="Pet Care Tips">Pet Care Tips</option>
                                <option value="Veterinary Medicine">Veterinary Medicine</option>
                                <option value="Pet Nutrition">Pet Nutrition</option>
                                <option value="Pet Behavior">Pet Behavior</option>
                                <option value="Emergency Care">Emergency Care</option>
                                <option value="Preventive Medicine">Preventive Medicine</option>
                                <option value="Surgery & Procedures">Surgery & Procedures</option>
                                <option value="Pet Health Issues">Pet Health Issues</option>
                                <option value="Breed Information">Breed Information</option>
                                <option value="Vaccination">Vaccination</option>
                                <option value="Dental Care">Dental Care</option>
                                <option value="Senior Pet Care">Senior Pet Care</option>
                                <option value="Puppy/Kitten Care">Puppy/Kitten Care</option>
                                <option value="Pet Safety">Pet Safety</option>
                                <option value="General Veterinary">General Veterinary</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tags</label>
                            <input type="text" id="articleTags" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500" placeholder="Enter tags separated by commas">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Article Content *</label>
                            <textarea id="articleContent" rows="10" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500" placeholder="Write your article content here..."></textarea>
                        </div>
                        
                        <div class="flex space-x-3 pt-4">
                            <button type="submit" class="bg-teal-600 text-white px-6 py-2 rounded hover:bg-teal-700">
                                Publish Article
                            </button>
                            <button type="button" onclick="hideAddArticleForm()" class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Articles & Inquiries Modal Functions
        function showArticlesModal() {
            document.getElementById('articlesModal').classList.remove('hidden');
            loadArticles();
            loadInquiries();
        }
        
        function hideArticlesModal() {
            document.getElementById('articlesModal').classList.add('hidden');
            document.getElementById('addArticleModal').classList.add('hidden');
        }
        
        function switchTab(tab) {
            if (tab === 'articles') {
                document.getElementById('articlesContent').classList.remove('hidden');
                document.getElementById('inquiriesContent').classList.add('hidden');
                document.getElementById('articlesTab').classList.add('border-teal-500', 'text-teal-600');
                document.getElementById('articlesTab').classList.remove('border-transparent', 'text-gray-500');
                document.getElementById('inquiriesTab').classList.remove('border-teal-500', 'text-teal-600');
                document.getElementById('inquiriesTab').classList.add('border-transparent', 'text-gray-500');
            } else {
                document.getElementById('inquiriesContent').classList.remove('hidden');
                document.getElementById('articlesContent').classList.add('hidden');
                document.getElementById('inquiriesTab').classList.add('border-teal-500', 'text-teal-600');
                document.getElementById('inquiriesTab').classList.remove('border-transparent', 'text-gray-500');
                document.getElementById('articlesTab').classList.remove('border-teal-500', 'text-teal-600');
                document.getElementById('articlesTab').classList.add('border-transparent', 'text-gray-500');
            }
        }
        
        function showAddArticleForm() {
            document.getElementById('addArticleModal').classList.remove('hidden');
        }
        
        function hideAddArticleForm() {
            document.getElementById('addArticleModal').classList.add('hidden');
        }
        
        function loadArticles() {
            fetch('../articles/api/get_articles.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayArticles(data.articles);
                    } else {
                        document.getElementById('articlesList').innerHTML = '<div class="text-center py-8"><p class="text-red-500">Failed to load articles</p></div>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('articlesList').innerHTML = '<div class="text-center py-8"><p class="text-red-500">Error loading articles</p></div>';
                });
        }
        
        function loadInquiries() {
            fetch('../articles/api/get_inquiries.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayInquiries(data.inquiries);
                    } else {
                        document.getElementById('inquiriesList').innerHTML = '<div class="text-center py-8"><p class="text-red-500">Failed to load inquiries</p></div>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('inquiriesList').innerHTML = '<div class="text-center py-8"><p class="text-red-500">Error loading inquiries</p></div>';
                });
        }
        
        function displayArticles(articles) {
            const container = document.getElementById('articlesList');
            if (articles.length === 0) {
                container.innerHTML = '<div class="text-center py-8"><p class="text-gray-500">No articles found</p></div>';
                return;
            }
            
            container.innerHTML = articles.map(article => `
                <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50">
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <h5 class="font-semibold text-gray-900 mb-2">${article.title}</h5>
                            <p class="text-sm text-gray-600 mb-2">${article.summary || 'No summary'}</p>
                            <div class="flex items-center space-x-4 text-xs text-gray-500">
                                <span>Topic: ${article.topic || 'General'}</span>
                                <span>Views: ${article.view_count || 0}</span>
                                <span>Created: ${new Date(article.created_at).toLocaleDateString()}</span>
                            </div>
                        </div>
                        <div class="flex space-x-2 ml-4">
                            <button onclick="editArticle(${article.article_id})" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                Edit
                            </button>
                            <button onclick="deleteArticle(${article.article_id})" class="text-red-600 hover:text-red-800 text-sm font-medium">
                                Delete
                            </button>
                        </div>
                    </div>
                </div>
            `).join('');
        }
        
        function displayInquiries(inquiries) {
            const container = document.getElementById('inquiriesList');
            if (inquiries.length === 0) {
                container.innerHTML = '<div class="text-center py-8"><p class="text-gray-500">No inquiries found</p></div>';
                return;
            }
            
            container.innerHTML = inquiries.map(inquiry => `
                <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50">
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <h5 class="font-semibold text-gray-900 mb-2">${inquiry.subject}</h5>
                            <p class="text-sm text-gray-600 mb-2">${inquiry.message}</p>
                            <div class="flex items-center space-x-4 text-xs text-gray-500">
                                <span>From: ${inquiry.user_name || 'Anonymous'}</span>
                                <span>Status: <span class="px-2 py-1 rounded-full ${getStatusClass(inquiry.status)}">${inquiry.status}</span></span>
                                <span>Date: ${new Date(inquiry.timestamp).toLocaleDateString()}</span>
                            </div>
                        </div>
                        <div class="flex space-x-2 ml-4">
                            <button onclick="replyToInquiry(${inquiry.inquiry_id})" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                Reply
                            </button>
                            <button onclick="updateInquiryStatus(${inquiry.inquiry_id}, '${inquiry.status === 'Pending' ? 'Replied' : 'Closed'}')" class="text-green-600 hover:text-green-800 text-sm font-medium">
                                ${inquiry.status === 'Pending' ? 'Mark Replied' : 'Close'}
                            </button>
                        </div>
                    </div>
                </div>
            `).join('');
        }
        
        <?php echo getStatusClassJS(); ?>
        
        // Handle form submission
        document.getElementById('addArticleForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData();
            formData.append('title', document.getElementById('articleTitle').value);
            formData.append('summary', document.getElementById('articleSummary').value);
            formData.append('topic', document.getElementById('articleTopic').value);
            formData.append('tags', document.getElementById('articleTags').value);
            formData.append('content', document.getElementById('articleContent').value);
            
            fetch('../articles/api/add_article.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Article added successfully!');
                    hideAddArticleForm();
                    loadArticles();
                    // Clear form
                    document.getElementById('addArticleForm').reset();
                } else {
                    alert('Failed to add article: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error adding article');
            });
        });
    </script>
</body>
</html> 