<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

$clinic_name = "Caring Paws Veterinary Clinic";
require_once '../config.php';

$appointments = [];
$patients = [];
$doctors = [];
$users = [];
$error_message = "";
$success_message = "";

// Get admin user information
$admin_name = $_SESSION['user_name'] ?? $_SESSION['name'] ?? $_SESSION['email'] ?? 'Admin User';

// Handle appointment application form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'apply_appointment') {
        try {
            // Validate required fields
            if (empty($_POST['patient_id']) || empty($_POST['doctor_id']) || empty($_POST['appointment_date']) || empty($_POST['appointment_time'])) {
                $error_message = "Please fill in all required fields.";
            } else {
                // Check if the selected time slot is available
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) FROM appointments 
                    WHERE doctor_id = ? AND appointment_date = ? AND appointment_time = ? AND status != 'Cancelled'
                ");
                $stmt->execute([$_POST['doctor_id'], $_POST['appointment_date'], $_POST['appointment_time']]);
                
                if ($stmt->fetchColumn() > 0) {
                    $error_message = "This time slot is already booked. Please select another time.";
                } else {
                    // Insert the new appointment
                    $stmt = $pdo->prepare("
                        INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, reason, status, created_by)
                        VALUES (?, ?, ?, ?, ?, 'Scheduled', ?)
                    ");
                    $stmt->execute([
                        $_POST['patient_id'],
                        $_POST['doctor_id'],
                        $_POST['appointment_date'],
                        $_POST['appointment_time'],
                        $_POST['reason'] ?? '',
                        $_SESSION['user_id'] ?? 'admin'
                    ]);
                    
                    $success_message = "Appointment applied successfully!";
                    
                    // Refresh the page to show the new appointment
                    header("Location: " . $_SERVER['PHP_SELF']);
                    exit();
                }
            }
        } catch(PDOException $e) {
            $error_message = "Failed to apply appointment: " . $e->getMessage();
        }
    }
}

try {
    // Check if appointments table has the correct column names
    $result = $pdo->query("DESCRIBE appointments");
    $appointment_columns = $result->fetchAll(PDO::FETCH_COLUMN);
    
    $date_column = in_array('appointment_date', $appointment_columns) ? 'appointment_date' : 'date';
    $time_column = in_array('appointment_time', $appointment_columns) ? 'appointment_time' : 'time';
    $has_reason = in_array('reason', $appointment_columns);
    
    // Build the SELECT query with conditional reason column
    $reason_field = $has_reason ? 'a.reason' : 'NULL as reason';
    
    // Check if doctors table has phone column or contact column
    $result = $pdo->query("DESCRIBE doctors");
    $doctor_columns = $result->fetchAll(PDO::FETCH_COLUMN);
    $phone_column = in_array('phone', $doctor_columns) ? 'phone' : 'contact';
    
    // Get all appointments
    $stmt = $pdo->prepare("
        SELECT a.*, $reason_field, p.animal_name, p.species, u.name as owner_name, u.phone, d.name as doctor_name, d.specialization
        FROM appointments a
        JOIN patients p ON a.patient_id = p.patient_id
        JOIN users u ON p.owner_id = u.user_id
        JOIN doctors d ON a.doctor_id = d.doctor_id
        ORDER BY a.$date_column DESC, a.$time_column DESC
    ");
    $stmt->execute();
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get all patients for appointment application
    $stmt = $pdo->prepare("
        SELECT p.*, u.name as owner_name 
        FROM patients p 
        JOIN users u ON p.owner_id = u.user_id 
        ORDER BY p.animal_name
    ");
    $stmt->execute();
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get all doctors for appointment application
    $stmt = $pdo->prepare("SELECT * FROM doctors WHERE availability = 'Available' ORDER BY name");
    $stmt->execute();
    $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get all users for patient owner selection
    $stmt = $pdo->prepare("SELECT user_id, name, email, phone FROM users ORDER BY name");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $error_message = "Failed to load data: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointments Management - <?php echo $clinic_name; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <header class="bg-blue-600 text-white p-4">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold"><?php echo $clinic_name; ?> - Appointments</h1>
            <div class="flex items-center space-x-4">
                <span class="text-sm">Welcome, <?php echo htmlspecialchars($admin_name); ?></span>
                <a href="dashboard.php" class="bg-blue-700 px-4 py-2 rounded">Dashboard</a>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto p-6">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-3xl font-bold">Appointments Management</h2>
            <button onclick="showApplyForm()" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                Apply for Appointment
            </button>
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

        <!-- Apply for Appointment Form -->
        <div id="applyAppointmentForm" class="hidden bg-white rounded-lg shadow p-6 mb-6">
            <h3 class="text-xl font-semibold mb-4">Apply for New Appointment</h3>
            <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <input type="hidden" name="action" value="apply_appointment">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Patient *</label>
                    <select name="patient_id" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Select a patient</option>
                        <?php foreach($patients as $patient): ?>
                            <option value="<?php echo $patient['patient_id']; ?>">
                                <?php echo htmlspecialchars($patient['animal_name']); ?> 
                                (<?php echo htmlspecialchars($patient['species']); ?>) - 
                                <?php echo htmlspecialchars($patient['owner_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Doctor *</label>
                    <select name="doctor_id" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Select a doctor</option>
                        <?php foreach($doctors as $doctor): ?>
                            <option value="<?php echo $doctor['doctor_id']; ?>">
                                Dr. <?php echo htmlspecialchars($doctor['name']); ?> 
                                (<?php echo htmlspecialchars($doctor['specialization']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Appointment Date *</label>
                    <input type="date" name="appointment_date" required min="<?php echo date('Y-m-d'); ?>" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Appointment Time *</label>
                    <select name="appointment_time" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Select time</option>
                        <option value="09:00:00">9:00 AM</option>
                        <option value="09:30:00">9:30 AM</option>
                        <option value="10:00:00">10:00 AM</option>
                        <option value="10:30:00">10:30 AM</option>
                        <option value="11:00:00">11:00 AM</option>
                        <option value="11:30:00">11:30 AM</option>
                        <option value="14:00:00">2:00 PM</option>
                        <option value="14:30:00">2:30 PM</option>
                        <option value="15:00:00">3:00 PM</option>
                        <option value="15:30:00">3:30 PM</option>
                        <option value="16:00:00">4:00 PM</option>
                        <option value="16:30:00">4:30 PM</option>
                    </select>
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Reason for Visit</label>
                    <textarea name="reason" rows="3" placeholder="Describe the reason for the appointment..." 
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                </div>
                
                <div class="md:col-span-2 flex gap-2">
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                        Apply for Appointment
                    </button>
                    <button type="button" onclick="hideApplyForm()" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                        Cancel
                    </button>
                </div>
            </form>
        </div>

        <!-- Appointments List -->
        <?php if (!empty($appointments)): ?>
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">All Appointments</h3>
                </div>
                <table class="min-w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pet</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Owner</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Doctor</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date & Time</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reason</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach($appointments as $appointment): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($appointment['animal_name'] ?? 'Unknown'); ?></div>
                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($appointment['species'] ?? 'Unknown'); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($appointment['owner_name'] ?? 'Unknown'); ?></div>
                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($appointment['phone'] ?? 'N/A'); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">Dr. <?php echo htmlspecialchars($appointment['doctor_name'] ?? 'Unknown'); ?></div>
                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($appointment['specialization'] ?? 'N/A'); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900"><?php 
                                    $date_field = $date_column === 'appointment_date' ? 'appointment_date' : 'date';
                                    echo isset($appointment[$date_field]) ? date('M j, Y', strtotime($appointment[$date_field])) : 'N/A'; 
                                ?></div>
                                <div class="text-sm text-gray-500"><?php 
                                    $time_field = $time_column === 'appointment_time' ? 'appointment_time' : 'time';
                                    echo isset($appointment[$time_field]) ? date('g:i A', strtotime($appointment[$time_field])) : 'N/A'; 
                                ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-medium rounded-full 
                                    <?php 
                                    switch($appointment['status'] ?? '') {
                                        case 'Scheduled': echo 'bg-blue-100 text-blue-800'; break;
                                        case 'Completed': echo 'bg-green-100 text-green-800'; break;
                                        case 'Cancelled': echo 'bg-red-100 text-red-800'; break;
                                        default: echo 'bg-gray-100 text-gray-800';
                                    }
                                    ?>">
                                    <?php echo htmlspecialchars($appointment['status'] ?? 'Unknown'); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                <?php echo htmlspecialchars($appointment['reason'] ?? 'No reason provided'); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <button onclick="editAppointment(<?php echo htmlspecialchars(json_encode($appointment)); ?>)" 
                                        class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
                                <button onclick="deleteAppointment(<?php echo $appointment['appointment_id']; ?>)" 
                                        class="text-red-600 hover:text-red-900">Delete</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-12">
                <p class="text-gray-500">No appointments found.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3 text-center">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Confirm Delete</h3>
                <p class="text-sm text-gray-500 mb-4">Are you sure you want to delete this appointment? This action cannot be undone.</p>
                <div class="flex justify-center space-x-4">
                    <form method="POST" id="deleteForm">
                        <input type="hidden" name="action" value="delete_appointment">
                        <input type="hidden" name="appointment_id" id="delete_appointment_id">
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
        function showApplyForm() {
            document.getElementById('applyAppointmentForm').classList.remove('hidden');
        }
        
        function hideApplyForm() {
            document.getElementById('applyAppointmentForm').classList.add('hidden');
        }
        
        function editAppointment(appointment) {
            // TODO: Implement edit functionality
            alert('Edit functionality will be implemented here');
        }
        
        function deleteAppointment(appointmentId) {
            document.getElementById('delete_appointment_id').value = appointmentId;
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