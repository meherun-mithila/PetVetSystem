<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_type'] !== 'user') {
    header("Location: ../index.php");
    exit();
}

$clinic_name = "Caring Paws Veterinary Clinic";
require_once '../config.php';

$vaccines = [];
$error_message = "";
$success_message = "";

try {
    $user_id = $_SESSION['user_id'];
    
    // Get user's pets and their vaccine records
    $stmt = $pdo->prepare("
        SELECT vr.*, p.animal_name, p.species, p.breed, p.age, p.gender
        FROM vaccine_records vr
        JOIN patients p ON vr.patient_id = p.patient_id
        WHERE p.owner_id = ?
        ORDER BY vr.date_administered DESC, vr.next_due_date ASC, vr.vaccine_id DESC
    ");
    $stmt->execute([$user_id]);
    $vaccines = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get user's pets for summary
    $stmt = $pdo->prepare("
        SELECT p.*, COUNT(vr.vaccine_id) as vaccine_count
        FROM patients p
        LEFT JOIN vaccine_records vr ON p.patient_id = vr.patient_id
        WHERE p.owner_id = ?
        GROUP BY p.patient_id
        ORDER BY p.animal_name
    ");
    $stmt->execute([$user_id]);
    $pets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $error_message = "Failed to load data: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vaccine Records - <?php echo $clinic_name; ?></title>
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
                        <p class="text-blue-200 text-sm">Pet Owner Dashboard - Vaccine Records</p>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="text-right">
                        <p class="font-semibold"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Pet Owner'); ?></p>
                        <p class="text-blue-200 text-sm">Pet Owner</p>
                    </div>
                    <a href="dashboard.php" class="bg-blue-700 hover:bg-blue-800 px-4 py-2 rounded-lg transition-colors">Dashboard</a>
                    <a href="../index.php?logout=1" class="bg-red-500 hover:bg-red-600 px-4 py-2 rounded-lg transition-colors">Logout</a>
                </div>
            </div>
        </div>
    </header>

    <nav class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-6">
            <div class="flex space-x-8">
                <a href="dashboard.php" class="text-gray-500 hover:text-vet-blue py-4 px-1 font-medium">Dashboard</a>
                <a href="appointments.php" class="text-gray-500 hover:text-vet-blue py-4 px-1 font-medium">Appointments</a>
                <a href="pets.php" class="text-gray-500 hover:text-vet-blue py-4 px-1 font-medium">My Pets</a>
                <a href="medical_records.php" class="text-gray-500 hover:text-vet-blue py-4 px-1 font-medium">Medical Records</a>
                <a href="vaccine_records.php" class="border-b-2 border-vet-blue text-vet-blue py-4 px-1 font-medium">Vaccine Records</a>
                <a href="adoption.php" class="text-gray-500 hover:text-vet-blue py-4 px-1 font-medium">Adoption</a>
                <a href="notifications.php" class="text-gray-500 hover:text-vet-blue py-4 px-1 font-medium">Notifications</a>
                <a href="profile.php" class="text-gray-500 hover:text-vet-blue py-4 px-1 font-medium">Profile</a>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto p-6">
        <div class="mb-6">
            <h2 class="text-3xl font-bold text-gray-900">Vaccine Records</h2>
            <p class="text-gray-600 mt-1">View vaccination history for all your pets</p>
        </div>

        <?php if (!empty($error_message)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <!-- Pets Summary -->
        <?php if (!empty($pets)): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                <?php foreach($pets as $pet): ?>
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center space-x-3 mb-4">
                            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                                <span class="text-2xl">
                                    <?php echo $pet['species'] === 'Dog' ? '🐕' : ($pet['species'] === 'Cat' ? '🐱' : '🐾'); ?>
                                </span>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($pet['animal_name']); ?></h3>
                                <p class="text-sm text-gray-600"><?php echo htmlspecialchars($pet['species'] . ' • ' . $pet['breed']); ?></p>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Age:</span>
                                <span class="font-medium"><?php echo htmlspecialchars($pet['age'] ?? 'N/A'); ?> years</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Gender:</span>
                                <span class="font-medium"><?php echo htmlspecialchars($pet['gender'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Vaccines:</span>
                                <span class="font-medium text-blue-600"><?php echo (int)$pet['vaccine_count']; ?> records</span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Vaccine Records Table -->
        <?php if (!empty($vaccines)): ?>
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Vaccination History</h3>
                    <p class="text-sm text-gray-600 mt-1">Complete vaccination records for all your pets</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pet</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vaccine</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Given</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Next Due</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Given By</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Details</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach($vaccines as $vaccine): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                                                <span class="text-sm">
                                                    <?php echo $vaccine['species'] === 'Dog' ? '🐕' : ($vaccine['species'] === 'Cat' ? '🐱' : '🐾'); ?>
                                                </span>
                                            </div>
                                            <div>
                                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($vaccine['animal_name']); ?></div>
                                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($vaccine['species'] . ' • ' . $vaccine['breed']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($vaccine['vaccine_name']); ?></div>
                                        <?php if (!empty($vaccine['batch_number'])): ?>
                                            <div class="text-xs text-gray-500">Batch: <?php echo htmlspecialchars($vaccine['batch_number']); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                            <?php echo $vaccine['vaccine_type'] === 'Core Vaccine' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'; ?>">
                                            <?php echo htmlspecialchars($vaccine['vaccine_type']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars(date('M j, Y', strtotime($vaccine['date_administered']))); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php if (!empty($vaccine['next_due_date'])): ?>
                                            <?php 
                                                $next_due = strtotime($vaccine['next_due_date']);
                                                $today = time();
                                                $days_until = ceil(($next_due - $today) / (60 * 60 * 24));
                                                $status_class = $days_until < 0 ? 'text-red-600 font-semibold' : ($days_until <= 30 ? 'text-yellow-600 font-semibold' : 'text-gray-900');
                                            ?>
                                            <span class="<?php echo $status_class; ?>">
                                                <?php echo htmlspecialchars(date('M j, Y', $next_due)); ?>
                                            </span>
                                            <?php if ($days_until < 0): ?>
                                                <div class="text-xs text-red-500">Overdue by <?php echo abs($days_until); ?> days</div>
                                            <?php elseif ($days_until <= 30): ?>
                                                <div class="text-xs text-yellow-500">Due in <?php echo $days_until; ?> days</div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-gray-400">Not scheduled</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                            <?php 
                                                echo $vaccine['status'] === 'Completed' ? 'bg-green-100 text-green-800' : 
                                                    ($vaccine['status'] === 'Scheduled' ? 'bg-blue-100 text-blue-800' : 'bg-red-100 text-red-800'); 
                                            ?>">
                                            <?php echo htmlspecialchars($vaccine['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($vaccine['administered_by'] ?? 'N/A'); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php if (!empty($vaccine['manufacturer'])): ?>
                                            <div>Manufacturer: <?php echo htmlspecialchars($vaccine['manufacturer']); ?></div>
                                        <?php endif; ?>
                                        <?php if (!empty($vaccine['notes'])): ?>
                                            <div class="mt-1">
                                                <button onclick="showNotes('<?php echo htmlspecialchars(addslashes($vaccine['notes'])); ?>')" 
                                                        class="text-blue-600 hover:text-blue-800 text-xs">
                                                    View Notes
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php else: ?>
            <div class="text-center py-12 bg-white rounded-lg shadow">
                <div class="text-6xl mb-4">💉</div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No Vaccine Records Found</h3>
                <p class="text-gray-500 mb-4">Your pets don't have any vaccine records yet.</p>
                <p class="text-sm text-gray-400">Vaccine records will appear here once your veterinarian adds them to the system.</p>
            </div>
        <?php endif; ?>

        <!-- Vaccine Information -->
        <div class="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-6">
            <h3 class="text-lg font-semibold text-blue-900 mb-3">💡 About Vaccinations</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm text-blue-800">
                <div>
                    <h4 class="font-medium mb-2">Core Vaccines</h4>
                    <p class="mb-2">Essential vaccines that all pets should receive for protection against serious diseases.</p>
                    <ul class="list-disc list-inside space-y-1">
                        <li>Dogs: DHPP, Rabies</li>
                        <li>Cats: FVRCP, Rabies</li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-medium mb-2">Non-Core Vaccines</h4>
                    <p class="mb-2">Optional vaccines recommended based on your pet's lifestyle and risk factors.</p>
                    <ul class="list-disc list-inside space-y-1">
                        <li>Dogs: Bordetella, Lyme Disease</li>
                        <li>Cats: Feline Leukemia</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Notes Modal -->
    <div id="notesModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Vaccine Notes</h3>
                <p id="notesContent" class="text-sm text-gray-600 mb-4"></p>
                <div class="text-center">
                    <button onclick="hideNotes()" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showNotes(notes) {
            document.getElementById('notesContent').textContent = notes;
            document.getElementById('notesModal').classList.remove('hidden');
        }
        
        function hideNotes() {
            document.getElementById('notesModal').classList.add('hidden');
        }
        
        // Auto-hide success/error messages after 5 seconds
        setTimeout(function() {
            const messages = document.querySelectorAll('.bg-green-100, .bg-red-100');
            messages.forEach(function(message) {
                message.style.display = 'none';
            });
        }, 5000);
    </script>
</body>
</html>
