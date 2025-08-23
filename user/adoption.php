<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_type'] !== 'user') {
    header("Location: ../index.php");
    exit();
}

$clinic_name = "Caring Paws Veterinary Clinic";
require_once '../config.php';
require_once '../includes/status_helper.php';

// Initialize variables
$available_pets = [];
$user_requests = [];
$success_message = "";
$error_message = "";

try {
    $user_id = $_SESSION['user_id'];
    
    // Handle adoption request submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'request_adoption') {
        $listing_id = $_POST['listing_id'] ?? null;
        
        if ($listing_id) {
            // Check if user already has a pending request for this listing
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM adoptionrequests WHERE listing_id = ? AND requested_by = ? AND status = 'Pending'");
            $stmt->execute([$listing_id, $user_id]);
            $existing_request = $stmt->fetchColumn();
            
            if ($existing_request > 0) {
                $error_message = "You already have a pending request for this pet.";
            } else {
                // Create new adoption request
                $stmt = $pdo->prepare("INSERT INTO adoptionrequests (listing_id, requested_by, status, date) VALUES (?, ?, 'Pending', CURDATE())");
                if ($stmt->execute([$listing_id, $user_id])) {
                    $success_message = "Adoption request submitted successfully! You will be notified once it's reviewed.";
                } else {
                    $error_message = "Failed to submit adoption request. Please try again.";
                }
            }
        }
    }
    
    // Get available pets for adoption
    $stmt = $pdo->prepare("
        SELECT al.*, u.name as posted_by_name, u.email as posted_by_email
        FROM adoptionlistings al
        JOIN users u ON al.posted_by = u.user_id
        WHERE al.status = 'Available'
        ORDER BY al.listing_id DESC
    ");
    $stmt->execute();
    $available_pets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get user's adoption requests
    $stmt = $pdo->prepare("
        SELECT ar.*, al.animal_name, al.species, al.age, al.description, u.name as posted_by_name
        FROM adoptionrequests ar
        JOIN adoptionlistings al ON ar.listing_id = al.listing_id
        JOIN users u ON al.posted_by = u.user_id
        WHERE ar.requested_by = ?
        ORDER BY ar.date DESC
    ");
    $stmt->execute([$user_id]);
    $user_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $error_message = "Database connection failed: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pet Adoption - <?php echo $clinic_name; ?></title>
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
                <a href="adoption.php" class="text-vet-blue font-semibold border-b-2 border-vet-blue pb-2">Adoption</a>
                <a href="medical_records.php" class="text-gray-600 hover:text-vet-blue transition-colors">Medical Records</a>
                <a href="locations.php" class="text-gray-600 hover:text-vet-blue transition-colors">Locations</a>
                <a href="profile.php" class="text-gray-600 hover:text-vet-blue transition-colors">Profile</a>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-6 py-8">
        <!-- Messages -->
        <?php if (!empty($success_message)): ?>
            <div id="successMessage" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
            <div id="errorMessage" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Pet Adoption Center</h1>
            <p class="text-gray-600">Find your perfect companion and give a loving home to pets in need.</p>
        </div>

        <!-- Available Pets for Adoption -->
        <div class="mb-12">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-800">Available Pets for Adoption</h2>
                <div class="text-sm text-gray-600"><?php echo count($available_pets); ?> pets available</div>
            </div>
            
            <?php if (!empty($available_pets)): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach($available_pets as $pet): ?>
                    <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
                        <div class="p-6">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <h3 class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($pet['animal_name']); ?></h3>
                                    <p class="text-gray-600"><?php echo htmlspecialchars($pet['species']); ?></p>
                                </div>
                                <span class="px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    Available
                                </span>
                            </div>
                            
                            <div class="space-y-2 text-sm text-gray-600 mb-4">
                                <p><strong>Age:</strong> <?php echo htmlspecialchars($pet['age']); ?> years</p>
                                <p><strong>Posted by:</strong> <?php echo htmlspecialchars($pet['posted_by_name']); ?></p>
                            </div>
                            
                            <div class="mb-4">
                                <p class="text-gray-700 text-sm">
                                    <?php echo htmlspecialchars($pet['description']); ?>
                                </p>
                            </div>
                            
                            <button onclick="requestAdoption(<?php echo $pet['listing_id']; ?>)" 
                                    class="w-full bg-vet-blue hover:bg-vet-dark-blue text-white py-2 px-4 rounded-lg transition-colors">
                                Request Adoption
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-12 bg-white rounded-lg shadow-md">
                    <div class="text-6xl mb-4">🐾</div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">No Pets Available</h3>
                    <p class="text-gray-600 mb-4">There are currently no pets available for adoption.</p>
                    <p class="text-sm text-gray-500">Check back later for new listings!</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- My Adoption Requests -->
        <div class="mb-12">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">My Adoption Requests</h2>
            
            <?php if (!empty($user_requests)): ?>
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pet</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Details</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Request Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Posted By</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach($user_requests as $request): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div>
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($request['animal_name']); ?></div>
                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($request['species']); ?></div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900">Age: <?php echo htmlspecialchars($request['age']); ?> years</div>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars(substr($request['description'], 0, 50)) . (strlen($request['description']) > 50 ? '...' : ''); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo date('M j, Y', strtotime($request['date'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php echo displayStatusBadge($request['status'], 'adoption'); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo htmlspecialchars($request['posted_by_name']); ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php else: ?>
                <div class="text-center py-12 bg-white rounded-lg shadow-md">
                    <div class="text-6xl mb-4">📋</div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">No Adoption Requests</h3>
                    <p class="text-gray-600 mb-4">You haven't made any adoption requests yet.</p>
                    <p class="text-sm text-gray-500">Browse the available pets above to get started!</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Information Section -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
            <h3 class="text-lg font-semibold text-blue-800 mb-3">How Adoption Works</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm text-blue-700">
                <div>
                    <div class="font-semibold mb-1">1. Browse Pets</div>
                    <p>Look through available pets and read their descriptions to find your perfect match.</p>
                </div>
                <div>
                    <div class="font-semibold mb-1">2. Submit Request</div>
                    <p>Click "Request Adoption" to submit your application. You'll be notified of the status.</p>
                </div>
                <div>
                    <div class="font-semibold mb-1">3. Get Approved</div>
                    <p>Once approved, you'll be contacted to arrange the adoption process and paperwork.</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto-hide messages after 5 seconds
        setTimeout(function() {
            const successMessage = document.getElementById('successMessage');
            const errorMessage = document.getElementById('errorMessage');
            
            if (successMessage) {
                successMessage.style.display = 'none';
            }
            if (errorMessage) {
                errorMessage.style.display = 'none';
            }
        }, 5000);

        function requestAdoption(listingId) {
            if (confirm('Are you sure you want to request adoption for this pet? You will be notified once your request is reviewed.')) {
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
    </script>
</body>
</html>
