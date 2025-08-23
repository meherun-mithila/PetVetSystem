<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

$clinic_name = "Caring Paws Veterinary Clinic";
require_once '../config.php';

$staff = [];
$error_message = "";
$success_message = "";

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                try {
                    // Validate required fields
                    if (empty($_POST['name']) || empty($_POST['email']) || empty($_POST['phone']) || empty($_POST['role']) || empty($_POST['password'])) {
                        throw new Exception("All required fields must be filled");
                    }
                    
                    // Validate email format
                    if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
                        throw new Exception("Invalid email format");
                    }
                    
                    // Check if email already exists (case-insensitive)
                    $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM staff WHERE LOWER(email) = LOWER(?)");
                    $check_stmt->execute([$_POST['email']]);
                    if ($check_stmt->fetchColumn() > 0) {
                        // Get the existing email to show in error message
                        $existing_stmt = $pdo->prepare("SELECT email FROM staff WHERE LOWER(email) = LOWER(?) LIMIT 1");
                        $existing_stmt->execute([$_POST['email']]);
                        $existing_email = $existing_stmt->fetchColumn();
                        throw new Exception("Email address already exists: " . $existing_email);
                    }
                    
                    // Validate password length
                    if (strlen($_POST['password']) < 6) {
                        throw new Exception("Password must be at least 6 characters long");
                    }
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO staff (name, email, phone, role, password, extra_info)
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        trim($_POST['name']),
                        trim($_POST['email']),
                        trim($_POST['phone']),
                        $_POST['role'],
                        password_hash($_POST['password'], PASSWORD_DEFAULT),
                        trim($_POST['extra_info'] ?? '')
                    ]);
                    $success_message = "Staff member added successfully!";
                } catch(Exception $e) {
                    $error_message = "Failed to add staff member: " . $e->getMessage();
                }
                break;
                
            case 'edit':
                try {
                    // Build dynamic UPDATE query based on whether password is being changed
                    if (!empty($_POST['new_password'])) {
                        $stmt = $pdo->prepare("
                            UPDATE staff 
                            SET name = ?, email = ?, phone = ?, role = ?, password = ?, extra_info = ?
                            WHERE staff_id = ?
                        ");
                        $stmt->execute([
                            $_POST['name'],
                            $_POST['email'],
                            $_POST['phone'],
                            $_POST['role'],
                            password_hash($_POST['new_password'], PASSWORD_DEFAULT),
                            $_POST['extra_info'] ?? '',
                            $_POST['staff_id']
                        ]);
                    } else {
                        $stmt = $pdo->prepare("
                            UPDATE staff 
                            SET name = ?, email = ?, phone = ?, role = ?, extra_info = ?
                            WHERE staff_id = ?
                        ");
                        $stmt->execute([
                            $_POST['name'],
                            $_POST['email'],
                            $_POST['phone'],
                            $_POST['role'],
                            $_POST['extra_info'] ?? '',
                            $_POST['staff_id']
                        ]);
                    }
                    $success_message = "Staff member updated successfully!";
                } catch(PDOException $e) {
                    $error_message = "Failed to update staff member: " . $e->getMessage();
                }
                break;
                
            case 'delete':
                try {
                    $stmt = $pdo->prepare("DELETE FROM staff WHERE staff_id = ?");
                    $stmt->execute([$_POST['staff_id']]);
                    $success_message = "Staff member deleted successfully!";
                } catch(PDOException $e) {
                    $error_message = "Failed to delete staff member: " . $e->getMessage();
                }
                break;
        }
    }
}

try {
    // Pagination
    $valid_sizes = [10, 25, 50, 100];
    $staff_per_page = isset($_GET['size']) && in_array((int)$_GET['size'], $valid_sizes, true) ? (int)$_GET['size'] : 25;
    $current_page = isset($_GET['page']) && (int)$_GET['page'] > 0 ? (int)$_GET['page'] : 1;

    $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM staff");
    $count_stmt->execute();
    $total_staff = (int)$count_stmt->fetchColumn();

    $total_pages = max(1, (int)ceil($total_staff / $staff_per_page));
    if ($current_page > $total_pages) { $current_page = $total_pages; }
    $offset = ($current_page - 1) * $staff_per_page;

    $stmt = $pdo->prepare(
        "SELECT * FROM staff ORDER BY name LIMIT " . (int)$staff_per_page . " OFFSET " . (int)$offset
    );
    $stmt->execute();
    $staff = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error_message = "Failed to load staff: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Management - <?php echo $clinic_name; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <header class="bg-blue-600 text-white p-4">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold"><?php echo $clinic_name; ?> - Staff</h1>
            <a href="dashboard.php" class="bg-blue-700 px-4 py-2 rounded">Dashboard</a>
        </div>
    </header>

    <div class="max-w-7xl mx-auto p-6">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="text-3xl font-bold">Staff Management</h2>
                <p class="text-gray-600 mt-1">Showing page <?php echo (int)$current_page; ?> of <?php echo (int)$total_pages; ?> (<?php echo (int)$total_staff; ?> total)</p>
            </div>
            <div class="flex items-center space-x-4">
                <div>
                    <label class="text-sm text-gray-600 mr-2">Page size</label>
                    <select onchange="changePageSize(this.value)" class="border-gray-300 rounded-md px-2 py-1">
                        <?php foreach([10,25,50,100] as $size): ?>
                            <option value="<?php echo $size; ?>" <?php echo $staff_per_page===$size? 'selected' : ''; ?>><?php echo $size; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button onclick="showAddForm()" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                    Add New Staff
                </button>
            </div>
        </div>
        
        <?php if (!empty($error_message)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">Error</h3>
                        <div class="mt-2 text-sm text-red-700">
                            <?php echo htmlspecialchars($error_message); ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success_message)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-green-800">Success</h3>
                        <div class="mt-2 text-sm text-green-700">
                            <?php echo htmlspecialchars($success_message); ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Add Staff Form -->
        <div id="addStaffForm" class="hidden bg-white rounded-lg shadow p-6 mb-6">
            <h3 class="text-xl font-semibold mb-4">Add New Staff Member</h3>

            <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4" onsubmit="return validateAddForm()">
                <input type="hidden" name="action" value="add">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                    <input type="text" name="name" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" name="email" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                    <input type="tel" name="phone" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                    <select name="role" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Select Role</option>
                        <option value="Receptionist">Receptionist</option>
                        <option value="Veterinary Technician">Veterinary Technician</option>
                        <option value="Nurse">Nurse</option>
                        <option value="Assistant">Assistant</option>
                        <option value="Manager">Manager</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <input type="password" name="password" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter password for staff login">
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Extra Information (Optional)</label>
                    <textarea name="extra_info" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Additional notes, qualifications, or special skills"></textarea>
                </div>
                

                
                <div class="md:col-span-2 flex gap-2">
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                        Add Staff Member
                    </button>
                    <button type="button" onclick="hideAddForm()" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                        Cancel
                    </button>
                </div>
            </form>
        </div>

        <!-- Edit Staff Form -->
        <div id="editStaffForm" class="hidden bg-white rounded-lg shadow p-6 mb-6">
            <h3 class="text-xl font-semibold mb-4">Edit Staff Member</h3>
            <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="staff_id" id="edit_staff_id">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                    <input type="text" name="name" id="edit_name" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" name="email" id="edit_email" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                    <input type="tel" name="phone" id="edit_phone" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                    <select name="role" id="edit_role" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Select Role</option>
                        <option value="Receptionist">Receptionist</option>
                        <option value="Veterinary Technician">Veterinary Technician</option>
                        <option value="Nurse">Nurse</option>
                        <option value="Assistant">Assistant</option>
                        <option value="Manager">Manager</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">New Password (Leave blank to keep current)</label>
                    <input type="password" name="new_password" id="edit_new_password" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter new password or leave blank">
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Extra Information</label>
                    <textarea name="extra_info" id="edit_extra_info" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Additional notes, qualifications, or special skills"></textarea>
                </div>
                
                <div class="md:col-span-2 flex gap-2">
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                        Update Staff Member
                    </button>
                    <button type="button" onclick="hideEditForm()" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                        Cancel
                    </button>
                </div>
            </form>
        </div>

        <?php if (!empty($staff)): ?>
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <table class="min-w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Phone</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Extra Info</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach($staff as $member): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                <?php echo htmlspecialchars($member['name']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo htmlspecialchars($member['email']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo htmlspecialchars($member['phone'] ?? 'N/A'); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full">
                                    <?php echo htmlspecialchars($member['role']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                <div class="max-w-xs truncate">
                                    <?php echo htmlspecialchars($member['extra_info'] ?? 'N/A'); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <button onclick="editStaff(<?php echo htmlspecialchars(json_encode($member)); ?>)" 
                                        class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
                                <button onclick="deleteStaff(<?php echo $member['staff_id']; ?>)" 
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
                        $from = $total_staff ? ($offset + 1) : 0;
                        $to = min($offset + $staff_per_page, $total_staff);
                        echo $from . ' to ' . $to . ' of ' . $total_staff . ' staff members';
                    ?>
                </div>
                <div class="flex items-center space-x-1">
                    <?php
                        $queryBase = '?size=' . (int)$staff_per_page . '&page=';
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
                <p class="text-gray-500">No staff found.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3 text-center">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Confirm Delete</h3>
                <p class="text-sm text-gray-500 mb-4">Are you sure you want to delete this staff member? This action cannot be undone.</p>
                <div class="flex justify-center space-x-4">
                    <form method="POST" id="deleteForm">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="staff_id" id="delete_staff_id">
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
            document.getElementById('addStaffForm').classList.remove('hidden');
            document.getElementById('editStaffForm').classList.add('hidden');
            // Clear form when showing
            document.getElementById('addStaffForm').querySelector('form').reset();
        }
        
        function hideAddForm() {
            document.getElementById('addStaffForm').classList.add('hidden');
        }
        
        function editStaff(staff) {
            document.getElementById('edit_staff_id').value = staff.staff_id;
            document.getElementById('edit_name').value = staff.name;
            document.getElementById('edit_email').value = staff.email;
            document.getElementById('edit_phone').value = staff.phone || '';
            document.getElementById('edit_role').value = staff.role;
            document.getElementById('edit_extra_info').value = staff.extra_info || '';
            
            document.getElementById('addStaffForm').classList.add('hidden');
            document.getElementById('editStaffForm').classList.remove('hidden');
        }
        
        function hideEditForm() {
            document.getElementById('editStaffForm').classList.add('hidden');
        }
        
        function deleteStaff(staffId) {
            document.getElementById('delete_staff_id').value = staffId;
            document.getElementById('deleteModal').classList.remove('hidden');
        }
        
        function hideDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
        }
        

        
        // Form validation
        function validateAddForm() {
            const name = document.querySelector('input[name="name"]').value.trim();
            const email = document.querySelector('input[name="email"]').value.trim();
            const phone = document.querySelector('input[name="phone"]').value.trim();
            const role = document.querySelector('select[name="role"]').value;
            const password = document.querySelector('input[name="password"]').value;
            
            if (!name || !email || !phone || !role || !password) {
                alert('Please fill in all required fields');
                return false;
            }
            
            if (password.length < 6) {
                alert('Password must be at least 6 characters long');
                return false;
            }
            
            if (!email.includes('@')) {
                alert('Please enter a valid email address');
                return false;
            }
            
            return true;
        }
        
        // Auto-hide success/error messages after 5 seconds
        setTimeout(function() {
            const messages = document.querySelectorAll('.bg-green-100, .bg-red-100');
            messages.forEach(function(message) {
                message.style.display = 'none';
            });
        }, 5000);
        
        // If there's a success message, hide the add form
        <?php if (!empty($success_message)): ?>
        document.addEventListener('DOMContentLoaded', function() {
            hideAddForm();
        });
        <?php endif; ?>
    </script>
</body>
</html> 