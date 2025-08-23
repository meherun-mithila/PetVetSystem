<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

$clinic_name = "Caring Paws Veterinary Clinic";
require_once '../config.php';

$doctors = [];
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
                        INSERT INTO doctors (name, specialization, area, contact, address, availability)
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $_POST['name'],
                        $_POST['specialization'],
                        $_POST['area'],
                        $_POST['contact'],
                        $_POST['address'],
                        $_POST['availability']
                    ]);
                    $success_message = "Doctor added successfully!";
                } catch(PDOException $e) {
                    $error_message = "Failed to add doctor: " . $e->getMessage();
                }
                break;
                
            case 'edit':
                try {
                    $stmt = $pdo->prepare("
                        UPDATE doctors 
                        SET name = ?, specialization = ?, area = ?, contact = ?, address = ?, 
                            availability = ?
                        WHERE doctor_id = ?
                    ");
                    $stmt->execute([
                        $_POST['name'],
                        $_POST['specialization'],
                        $_POST['area'],
                        $_POST['contact'],
                        $_POST['address'],
                        $_POST['availability'],
                        $_POST['doctor_id']
                    ]);
                    $success_message = "Doctor updated successfully!";
                } catch(PDOException $e) {
                    $error_message = "Failed to update doctor: " . $e->getMessage();
                }
                break;
                
            case 'delete':
                try {
                    $stmt = $pdo->prepare("DELETE FROM doctors WHERE doctor_id = ?");
                    $stmt->execute([$_POST['doctor_id']]);
                    $success_message = "Doctor deleted successfully!";
                } catch(PDOException $e) {
                    $error_message = "Failed to delete doctor: " . $e->getMessage();
                }
                break;
        }
    }
}

try {
    // Pagination
    $valid_sizes = [10, 25, 50, 100];
    $doctors_per_page = isset($_GET['size']) && in_array((int)$_GET['size'], $valid_sizes, true) ? (int)$_GET['size'] : 25;
    $current_page = isset($_GET['page']) && (int)$_GET['page'] > 0 ? (int)$_GET['page'] : 1;

    $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM doctors");
    $count_stmt->execute();
    $total_doctors = (int)$count_stmt->fetchColumn();

    $total_pages = max(1, (int)ceil($total_doctors / $doctors_per_page));
    if ($current_page > $total_pages) { $current_page = $total_pages; }
    $offset = ($current_page - 1) * $doctors_per_page;

    $stmt = $pdo->prepare(
        "SELECT * FROM doctors ORDER BY name LIMIT " . (int)$doctors_per_page . " OFFSET " . (int)$offset
    );
    $stmt->execute();
    $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error_message = "Failed to load doctors: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctors Management - <?php echo $clinic_name; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <header class="bg-blue-600 text-white p-4">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold"><?php echo $clinic_name; ?> - Doctors</h1>
            <div class="flex items-center space-x-4">
                <span class="text-sm">Welcome, <?php echo htmlspecialchars($admin_name); ?></span>
            <a href="dashboard.php" class="bg-blue-700 px-4 py-2 rounded">Dashboard</a>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto p-6">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="text-3xl font-bold">Doctors Management</h2>
                <p class="text-gray-600 mt-1">Showing page <?php echo (int)$current_page; ?> of <?php echo (int)$total_pages; ?> (<?php echo (int)$total_doctors; ?> total)</p>
            </div>
            <div class="flex items-center space-x-4">
                <div>
                    <label class="text-sm text-gray-600 mr-2">Page size</label>
                    <select onchange="changePageSize(this.value)" class="border-gray-300 rounded-md px-2 py-1">
                        <?php foreach([10,25,50,100] as $size): ?>
                            <option value="<?php echo $size; ?>" <?php echo $doctors_per_page===$size? 'selected' : ''; ?>><?php echo $size; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button onclick="showAddForm()" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                    Add New Doctor
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

        <!-- Add Doctor Form -->
        <div id="addDoctorForm" class="hidden bg-white rounded-lg shadow p-6 mb-6">
            <h3 class="text-xl font-semibold mb-4">Add New Doctor</h3>
            <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <input type="hidden" name="action" value="add">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                    <input type="text" name="name" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Specialization</label>
                    <input type="text" name="specialization" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Area</label>
                    <input type="text" name="area" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Contact</label>
                    <input type="tel" name="contact" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                    <input type="text" name="address" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Availability</label>
                    <select name="availability" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="Available">Available</option>
                        <option value="Busy">Busy</option>
                        <option value="Unavailable">Unavailable</option>
                    </select>
                </div>
                
                <div class="md:col-span-2 flex gap-2">
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                        Add Doctor
                    </button>
                    <button type="button" onclick="hideAddForm()" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                        Cancel
                    </button>
                </div>
            </form>
        </div>

        <!-- Edit Doctor Form -->
        <div id="editDoctorForm" class="hidden bg-white rounded-lg shadow p-6 mb-6">
            <h3 class="text-xl font-semibold mb-4">Edit Doctor</h3>
            <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="doctor_id" id="edit_doctor_id">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                    <input type="text" name="name" id="edit_name" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Specialization</label>
                    <input type="text" name="specialization" id="edit_specialization" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Area</label>
                    <input type="text" name="area" id="edit_area" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Contact</label>
                    <input type="tel" name="contact" id="edit_contact" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                    <input type="text" name="address" id="edit_address" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Availability</label>
                    <select name="availability" id="edit_availability" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="Available">Available</option>
                        <option value="Busy">Busy</option>
                        <option value="Unavailable">Unavailable</option>
                    </select>
                </div>
                
                <div class="md:col-span-2 flex gap-2">
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                        Update Doctor
                    </button>
                    <button type="button" onclick="hideEditForm()" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                        Cancel
                    </button>
                </div>
            </form>
        </div>

        <?php if (!empty($doctors)): ?>
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <table class="min-w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Specialization</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Area</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Contact</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Address</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Availability</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach($doctors as $doctor): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                Dr. <?php echo htmlspecialchars($doctor['name'] ?? 'Unknown'); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo htmlspecialchars($doctor['specialization'] ?? 'N/A'); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo htmlspecialchars($doctor['area'] ?? 'N/A'); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo htmlspecialchars($doctor['contact'] ?? 'N/A'); ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                <div class="max-w-xs truncate">
                                    <?php echo htmlspecialchars($doctor['address'] ?? 'N/A'); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-medium rounded-full 
                                    <?php echo ($doctor['availability'] ?? '') === 'Available' ? 'bg-green-100 text-green-800' : 
                                            (($doctor['availability'] ?? '') === 'Busy' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'); ?>">
                                    <?php echo htmlspecialchars($doctor['availability'] ?? 'Unknown'); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <button onclick="editDoctor(<?php echo htmlspecialchars(json_encode($doctor)); ?>)" 
                                        class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
                                <button onclick="deleteDoctor(<?php echo $doctor['doctor_id']; ?>)" 
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
                        $from = $total_doctors ? ($offset + 1) : 0;
                        $to = min($offset + $doctors_per_page, $total_doctors);
                        echo $from . ' to ' . $to . ' of ' . $total_doctors . ' doctors';
                    ?>
                </div>
                <div class="flex items-center space-x-1">
                    <?php
                        $queryBase = '?size=' . (int)$doctors_per_page . '&page=';
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
                <p class="text-gray-500">No doctors found.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3 text-center">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Confirm Delete</h3>
                <p class="text-sm text-gray-500 mb-4">Are you sure you want to delete this doctor? This action cannot be undone.</p>
                <div class="flex justify-center space-x-4">
                    <form method="POST" id="deleteForm">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="doctor_id" id="delete_doctor_id">
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
            document.getElementById('addDoctorForm').classList.remove('hidden');
            document.getElementById('editDoctorForm').classList.add('hidden');
        }
        
        function hideAddForm() {
            document.getElementById('addDoctorForm').classList.add('hidden');
        }
        
        function editDoctor(doctor) {
            document.getElementById('edit_doctor_id').value = doctor.doctor_id;
            document.getElementById('edit_name').value = doctor.name;
            document.getElementById('edit_specialization').value = doctor.specialization;
            document.getElementById('edit_area').value = doctor.area || '';
            document.getElementById('edit_contact').value = doctor.contact || '';
            document.getElementById('edit_address').value = doctor.address || '';
            document.getElementById('edit_availability').value = doctor.availability;
            
            document.getElementById('addDoctorForm').classList.add('hidden');
            document.getElementById('editDoctorForm').classList.remove('hidden');
        }
        
        function hideEditForm() {
            document.getElementById('editDoctorForm').classList.add('hidden');
        }
        
        function deleteDoctor(doctorId) {
            document.getElementById('delete_doctor_id').value = doctorId;
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
