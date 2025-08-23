<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

$clinic_name = "Caring Paws Veterinary Clinic";
require_once '../config.php';

$patients = [];
$users = [];
$error_message = "";
$success_message = "";

// Get admin user information - use the correct session variable names
$admin_name = $_SESSION['user_name'] ?? $_SESSION['name'] ?? $_SESSION['email'] ?? 'Admin User';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO patients (animal_name, species, breed, age, gender, owner_id)
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $_POST['animal_name'],
                        $_POST['species'],
                        $_POST['breed'],
                        $_POST['age'],
                        $_POST['gender'],
                        $_POST['owner_id']
                    ]);
                    $success_message = "Patient added successfully!";
                } catch(PDOException $e) {
                    $error_message = "Failed to add patient: " . $e->getMessage();
                }
                break;
                
            case 'edit':
                try {
                    $stmt = $pdo->prepare("
                        UPDATE patients 
                        SET animal_name = ?, species = ?, breed = ?, age = ?, gender = ?, 
                            owner_id = ?
                        WHERE patient_id = ?
                    ");
                    $stmt->execute([
                        $_POST['animal_name'],
                        $_POST['species'],
                        $_POST['breed'],
                        $_POST['age'],
                        $_POST['gender'],
                        $_POST['owner_id'],
                        $_POST['patient_id']
                    ]);
                    $success_message = "Patient updated successfully!";
                } catch(PDOException $e) {
                    $error_message = "Failed to update patient: " . $e->getMessage();
                }
                break;
                
            case 'delete':
                try {
                    $stmt = $pdo->prepare("DELETE FROM patients WHERE patient_id = ?");
                    $stmt->execute([$_POST['patient_id']]);
                    $success_message = "Patient deleted successfully!";
                } catch(PDOException $e) {
                    $error_message = "Failed to delete patient: " . $e->getMessage();
                }
                break;
        }
    }
}

try {
    // Get all users for owner selection (without user_type filter since it doesn't exist)
    $stmt = $pdo->prepare("SELECT user_id, name, email, phone FROM users ORDER BY name");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Pagination
    $valid_sizes = [10, 25, 50, 100];
    $patients_per_page = isset($_GET['size']) && in_array((int)$_GET['size'], $valid_sizes, true) ? (int)$_GET['size'] : 25;
    $current_page = isset($_GET['page']) && (int)$_GET['page'] > 0 ? (int)$_GET['page'] : 1;

    $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM patients");
    $count_stmt->execute();
    $total_patients = (int)$count_stmt->fetchColumn();

    $total_pages = max(1, (int)ceil($total_patients / $patients_per_page));
    if ($current_page > $total_pages) { $current_page = $total_pages; }
    $offset = ($current_page - 1) * $patients_per_page;

    // Get patients with owner information (paged)
    $stmt = $pdo->prepare(
        "SELECT p.*, u.name as owner_name, u.email as owner_email, u.phone as owner_phone
         FROM patients p
         JOIN users u ON p.owner_id = u.user_id
         ORDER BY p.patient_id DESC
         LIMIT " . (int)$patients_per_page . " OFFSET " . (int)$offset
    );
    $stmt->execute();
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error_message = "Failed to load data: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patients Management - <?php echo $clinic_name; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <header class="bg-blue-600 text-white p-4">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold"><?php echo $clinic_name; ?> - Patients</h1>
            <div class="flex items-center space-x-4">
                <span class="text-sm">Welcome, <?php echo htmlspecialchars($admin_name); ?></span>
                <a href="dashboard.php" class="bg-blue-700 px-4 py-2 rounded">Dashboard</a>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto p-6">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="text-3xl font-bold">Patients Management</h2>
                <p class="text-gray-600 mt-1">Showing page <?php echo (int)$current_page; ?> of <?php echo (int)$total_pages; ?> (<?php echo (int)$total_patients; ?> total)</p>
            </div>
            <div class="flex items-center space-x-4">
                <div>
                    <label class="text-sm text-gray-600 mr-2">Page size</label>
                    <select onchange="changePageSize(this.value)" class="border-gray-300 rounded-md px-2 py-1">
                        <?php foreach([10,25,50,100] as $size): ?>
                            <option value="<?php echo $size; ?>" <?php echo $patients_per_page===$size? 'selected' : ''; ?>><?php echo $size; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button onclick="showAddForm()" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                    Add New Patient
                </button>
            </div>
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

        <!-- Add Patient Form -->
        <div id="addPatientForm" class="hidden bg-white rounded-lg shadow p-6 mb-6">
            <h3 class="text-xl font-semibold mb-4">Add New Patient</h3>
            <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <input type="hidden" name="action" value="add">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Pet Name</label>
                    <input type="text" name="animal_name" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Owner</label>
                    <select name="owner_id" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Select Owner</option>
                        <?php foreach($users as $user): ?>
                            <option value="<?php echo $user['user_id']; ?>">
                                <?php echo htmlspecialchars($user['name'] . ' (' . $user['email'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Species</label>
                    <input type="text" name="species" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Breed</label>
                    <input type="text" name="breed" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Age (Years)</label>
                    <input type="number" name="age" min="0" step="0.1" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Gender</label>
                    <select name="gender" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Select Gender</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                </div>
                

                
                <div class="md:col-span-2 flex gap-2">
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                        Add Patient
                    </button>
                    <button type="button" onclick="hideAddForm()" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                        Cancel
                    </button>
                </div>
            </form>
        </div>

        <!-- Edit Patient Form -->
        <div id="editPatientForm" class="hidden bg-white rounded-lg shadow p-6 mb-6">
            <h3 class="text-xl font-semibold mb-4">Edit Patient</h3>
            <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="patient_id" id="edit_patient_id">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Pet Name</label>
                    <input type="text" name="animal_name" id="edit_animal_name" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Owner</label>
                    <select name="owner_id" id="edit_owner_id" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Select Owner</option>
                        <?php foreach($users as $user): ?>
                            <option value="<?php echo $user['user_id']; ?>">
                                <?php echo htmlspecialchars($user['name'] . ' (' . $user['email'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Species</label>
                    <input type="text" name="species" id="edit_species" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Breed</label>
                    <input type="text" name="breed" id="edit_breed" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Age (Years)</label>
                    <input type="number" name="age" id="edit_age" min="0" step="0.1" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Gender</label>
                    <select name="gender" id="edit_gender" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Select Gender</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                </div>
                

                
                <div class="md:col-span-2 flex gap-2">
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                        Update Patient
                    </button>
                    <button type="button" onclick="hideEditForm()" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                        Cancel
                    </button>
                </div>
            </form>
        </div>

        <?php if (!empty($patients)): ?>
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <table class="min-w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pet Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Owner</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Species</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Breed</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Age</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Gender</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach($patients as $patient): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                <?php echo htmlspecialchars($patient['animal_name'] ?? 'Unknown'); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($patient['owner_name'] ?? 'Unknown'); ?></div>
                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($patient['owner_phone'] ?? 'N/A'); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo htmlspecialchars($patient['species'] ?? 'Unknown'); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo htmlspecialchars($patient['breed'] ?? 'Unknown'); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo htmlspecialchars($patient['age'] ?? 'N/A'); ?> years
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo htmlspecialchars($patient['gender'] ?? 'N/A'); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <button onclick="editPatient(<?php echo htmlspecialchars(json_encode($patient)); ?>)" 
                                        class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
                                <button onclick="deletePatient(<?php echo $patient['patient_id']; ?>)" 
                                        class="text-red-600 hover:text-red-900">Delete</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <!-- Pagination Controls -->
            <div class="flex items-center justify-between mt-4">
                <div class="text-sm text-gray-600">
                    Showing
                    <?php
                        $from = $total_patients ? ($offset + 1) : 0;
                        $to = min($offset + $patients_per_page, $total_patients);
                        echo $from . ' to ' . $to . ' of ' . $total_patients . ' patients';
                    ?>
                </div>
                <div class="flex items-center space-x-1">
                    <?php
                        $queryBase = '?size=' . (int)$patients_per_page . '&page=';
                        $prevDisabled = $current_page <= 1;
                        $nextDisabled = $current_page >= $total_pages;
                    ?>
                    <a href="<?php echo $prevDisabled ? '#' : $queryBase . ($current_page - 1); ?>" class="px-3 py-1 rounded border <?php echo $prevDisabled ? 'text-gray-400 border-gray-200 cursor-not-allowed' : 'text-gray-700 hover:bg-gray-50'; ?>">Prev</a>
                    <?php
                        $start = max(1, $current_page - 2);
                        $end = min($total_pages, $current_page + 2);
                        if ($start > 1) echo '<span class=\'px-2\'>...</span>';
                        for ($i=$start; $i<=$end; $i++) {
                            $active = $i === $current_page;
                            echo '<a href="' . $queryBase . $i . '" class="px-3 py-1 rounded border ' . ($active ? 'bg-blue-600 text-white border-blue-600' : 'text-gray-700 hover:bg-gray-50') . '">' . $i . '</a>';
                        }
                        if ($end < $total_pages) echo '<span class=\'px-2\'>...</span>';
                    ?>
                    <a href="<?php echo $nextDisabled ? '#' : $queryBase . ($current_page + 1); ?>" class="px-3 py-1 rounded border <?php echo $nextDisabled ? 'text-gray-400 border-gray-200 cursor-not-allowed' : 'text-gray-700 hover:bg-gray-50'; ?>">Next</a>
                </div>
            </div>
        <?php else: ?>
            <div class="text-center py-12">
                <p class="text-gray-500">No patients found.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3 text-center">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Confirm Delete</h3>
                <p class="text-sm text-gray-500 mb-4">Are you sure you want to delete this patient? This action cannot be undone.</p>
                <div class="flex justify-center space-x-4">
                    <form method="POST" id="deleteForm">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="patient_id" id="delete_patient_id">
                        <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
                            Delete
                        </button>
                    </form>
                    <button onclick="hideDeleteModal()" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function changePageSize(size) {
            const params = new URLSearchParams(window.location.search);
            params.set('size', size);
            params.set('page', '1');
            window.location.search = params.toString();
        }
        function showAddForm() {
            document.getElementById('addPatientForm').classList.remove('hidden');
            document.getElementById('editPatientForm').classList.add('hidden');
        }
        
        function hideAddForm() {
            document.getElementById('addPatientForm').classList.add('hidden');
        }
        
        function editPatient(patient) {
            document.getElementById('edit_patient_id').value = patient.patient_id;
            document.getElementById('edit_animal_name').value = patient.animal_name;
            document.getElementById('edit_owner_id').value = patient.owner_id;
            document.getElementById('edit_species').value = patient.species;
            document.getElementById('edit_breed').value = patient.breed || '';
            document.getElementById('edit_age').value = patient.age || '';
            document.getElementById('edit_gender').value = patient.gender;
            
            document.getElementById('addPatientForm').classList.add('hidden');
            document.getElementById('editPatientForm').classList.remove('hidden');
        }
        
        function hideEditForm() {
            document.getElementById('editPatientForm').classList.add('hidden');
        }
        
        function deletePatient(patientId) {
            document.getElementById('delete_patient_id').value = patientId;
            document.getElementById('deleteModal').classList.remove('hidden');
        }
        
        function hideDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
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