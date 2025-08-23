<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_type'] !== 'staff') {
    header("Location: ../index.php");
    exit();
}

require_once '../includes/status_helper.php';

$clinic_name = "Caring Paws Veterinary Clinic";
require_once '../config.php';

$today_appointments = [];
$pending_appointments = [];
$all_appointments = [];
$patients = [];
$doctors = [];
$error_message = "";
$success_message = "";

$staff_role = 'staff';
$allowed_roles_for_changes = ['manager', 'assistant', 'receptionist'];

// Fetch staff role
try {
    $stmt = $pdo->prepare("SELECT role FROM staff WHERE staff_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $staff_role = $stmt->fetchColumn() ?: 'staff';
} catch (PDOException $e) {
    // ignore role error; default restricts actions
}

// Handle create/cancel actions for permitted roles
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array(strtolower($staff_role), $allowed_roles_for_changes, true)) {
    $action = $_POST['action'] ?? '';
    try {
        // Discover appointment columns for compatibility
        $result = $pdo->query("DESCRIBE appointments");
        $columns = $result->fetchAll(PDO::FETCH_COLUMN);
        $date_column = in_array('appointment_date', $columns) ? 'appointment_date' : 'date';
        $time_column = in_array('appointment_time', $columns) ? 'appointment_time' : 'time';
        $has_reason = in_array('reason', $columns);
        $has_created_by = in_array('created_by', $columns);

        if ($action === 'create_appointment') {
            $patient_id = trim($_POST['patient_id'] ?? '');
            $doctor_id = trim($_POST['doctor_id'] ?? '');
            $appt_date = trim($_POST['appointment_date'] ?? '');
            $appt_time = trim($_POST['appointment_time'] ?? '');
            $reason = trim($_POST['reason'] ?? '');

            if ($patient_id === '' || $doctor_id === '' || $appt_date === '' || $appt_time === '') {
                $error_message = 'Please fill in all required fields.';
            } else {
                // no double-booking for same doctor/time
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id = ? AND {$date_column} = ? AND {$time_column} = ? AND status != 'Cancelled'");
                $stmt->execute([$doctor_id, $appt_date, $appt_time]);
                if ($stmt->fetchColumn() > 0) {
                    $error_message = 'Selected time is already booked for this doctor.';
                } else {
                    // Build insert with available columns
                    $fields = ['patient_id', 'doctor_id', $date_column, $time_column, 'status'];
                    $placeholders = ['?', '?', '?', '?', "'Scheduled'"];
                    $values = [$patient_id, $doctor_id, $appt_date, $appt_time];

                    if ($has_reason) {
                        $fields[] = 'reason';
                        $placeholders[] = '?';
                        $values[] = $reason;
                    }
                    if ($has_created_by) {
                        $fields[] = 'created_by';
                        $placeholders[] = '?';
                        $values[] = $_SESSION['user_id'];
                    }

                    $sql = 'INSERT INTO appointments (' . implode(',', $fields) . ') VALUES (' . implode(',', $placeholders) . ')';
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($values);
                    $success_message = 'Appointment scheduled successfully.';
                    header('Location: ' . $_SERVER['PHP_SELF']);
                    exit();
                }
            }
        } elseif ($action === 'cancel_appointment') {
            $appointment_id = intval($_POST['appointment_id'] ?? 0);
            if ($appointment_id > 0) {
                $stmt = $pdo->prepare("UPDATE appointments SET status = 'Cancelled' WHERE appointment_id = ?");
                $stmt->execute([$appointment_id]);
                $success_message = 'Appointment cancelled.';
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit();
            } else {
                $error_message = 'Invalid appointment id.';
            }
        }
    } catch (PDOException $e) {
        $error_message = 'Operation failed: ' . $e->getMessage();
    }
}

try {
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
        LIMIT 20
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Get all appointments
    $all_appointments = $pdo->query("
        SELECT a.*, p.animal_name, p.species, u.name as owner_name, u.phone, d.name as doctor_name
        FROM appointments a
        JOIN patients p ON a.patient_id = p.patient_id
        JOIN users u ON p.owner_id = u.user_id
        JOIN doctors d ON a.doctor_id = d.doctor_id
        ORDER BY a.$date_column DESC, a.$time_column DESC
        LIMIT 50
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Patients for scheduling
    $stmt = $pdo->prepare("SELECT p.patient_id, p.animal_name, p.species, u.name AS owner_name FROM patients p JOIN users u ON p.owner_id = u.user_id ORDER BY p.animal_name");
    $stmt->execute();
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Doctors (available preferred)
    $result = $pdo->query("DESCRIBE doctors");
    $doctor_columns = $result->fetchAll(PDO::FETCH_COLUMN);
    $has_availability = in_array('availability', $doctor_columns);
    if ($has_availability) {
        $stmt = $pdo->prepare("SELECT doctor_id, name, specialization FROM doctors WHERE availability = 'Available' ORDER BY name");
    } else {
        $stmt = $pdo->prepare("SELECT doctor_id, name, specialization FROM doctors ORDER BY name");
    }
    $stmt->execute();
    $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointments - <?php echo $clinic_name; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <header class="bg-blue-600 text-white p-4">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold"><?php echo $clinic_name; ?> - Appointments</h1>
            <a href="dashboard.php" class="bg-blue-700 px-4 py-2 rounded">Dashboard</a>
        </div>
    </header>

    <nav class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-6">
            <div class="flex space-x-8">
                <a href="dashboard.php" class="text-gray-500 hover:text-blue-600 py-4 px-1 font-medium">Dashboard</a>
                <a href="appointments.php" class="border-b-2 border-blue-600 text-blue-600 py-4 px-1 font-medium">Appointments</a>
                <a href="patients.php" class="text-gray-500 hover:text-blue-600 py-4 px-1 font-medium">Patients</a>
                <a href="doctors.php" class="text-gray-500 hover:text-blue-600 py-4 px-1 font-medium">Doctors</a>
                <a href="billing.php" class="text-gray-500 hover:text-blue-600 py-4 px-1 font-medium">Billing</a>
                <a href="reports.php" class="text-gray-500 hover:text-blue-600 py-4 px-1 font-medium">Reports</a>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-6 py-8">
        <?php if (!empty($error_message)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($success_message)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <!-- Quick Actions for permitted roles -->
        <?php if (in_array(strtolower($staff_role), $allowed_roles_for_changes, true)): ?>
        <div class="mb-6 flex justify-end">
            <button onclick="document.getElementById('scheduleForm').classList.toggle('hidden');" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">Schedule Appointment</button>
        </div>

        <div id="scheduleForm" class="hidden bg-white rounded-lg shadow p-6 mb-8">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">New Appointment</h3>
            <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <input type="hidden" name="action" value="create_appointment" />
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Patient *</label>
                    <select name="patient_id" required class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        <option value="">Select patient</option>
                        <?php foreach ($patients as $p): ?>
                            <option value="<?php echo $p['patient_id']; ?>"><?php echo htmlspecialchars($p['animal_name']); ?> (<?php echo htmlspecialchars($p['species']); ?>) - <?php echo htmlspecialchars($p['owner_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Doctor *</label>
                    <select name="doctor_id" required class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        <option value="">Select doctor</option>
                        <?php foreach ($doctors as $d): ?>
                            <option value="<?php echo $d['doctor_id']; ?>">Dr. <?php echo htmlspecialchars($d['name']); ?> (<?php echo htmlspecialchars($d['specialization'] ?? ''); ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date *</label>
                    <input type="date" name="appointment_date" required min="<?php echo date('Y-m-d'); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Time *</label>
                    <select name="appointment_time" required class="w-full px-3 py-2 border border-gray-300 rounded-md">
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
                    <label class="block text-sm font-medium text-gray-700 mb-1">Reason</label>
                    <textarea name="reason" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="Reason for visit (optional)"></textarea>
                </div>
                <div class="md:col-span-2 flex gap-2">
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Create</button>
                    <button type="button" onclick="document.getElementById('scheduleForm').classList.add('hidden');" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">Cancel</button>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <!-- Quick Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900">Today's Appointments</h3>
                <p class="text-3xl font-bold text-blue-600"><?php echo count($today_appointments); ?></p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900">Pending</h3>
                <p class="text-3xl font-bold text-yellow-600"><?php echo count($pending_appointments); ?></p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900">Total</h3>
                <p class="text-3xl font-bold text-green-600"><?php echo count($all_appointments); ?></p>
            </div>
        </div>

        <!-- Today's Appointments -->
        <div class="bg-white rounded-lg shadow mb-8">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Today's Appointments</h3>
            </div>
            <div class="p-6">
                <?php if (!empty($today_appointments)): ?>
                    <div class="space-y-4">
                        <?php foreach($today_appointments as $appointment): ?>
                            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                <div>
                                    <p class="font-medium text-gray-900">
                                        <?php echo htmlspecialchars($appointment['animal_name'] ?? 'Unknown'); ?>
                                    </p>
                                    <p class="text-sm text-gray-500">
                                        <?php echo htmlspecialchars($appointment['owner_name'] ?? 'Unknown'); ?> • 
                                        <?php echo htmlspecialchars($appointment['species'] ?? 'Unknown'); ?>
                                    </p>
                                    <p class="text-xs text-gray-400">
                                        Time: <?php echo date('H:i', strtotime($appointment[$time_column] ?? '00:00')); ?>
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($appointment['doctor_name'] ?? 'Unknown'); ?>
                                    </p>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        <?php echo htmlspecialchars($appointment['status'] ?? 'Unknown'); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-gray-500 text-center py-8">No appointments scheduled for today.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- All Appointments Table -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">All Appointments</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date & Time</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Patient</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Owner</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Doctor</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <?php if (in_array(strtolower($staff_role), $allowed_roles_for_changes, true)): ?>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach($all_appointments as $appointment): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo date('M j, Y', strtotime($appointment[$date_column] ?? 'now')); ?><br>
                                    <span class="text-gray-500"><?php echo date('H:i', strtotime($appointment[$time_column] ?? '00:00')); ?></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($appointment['animal_name'] ?? 'Unknown'); ?>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        <?php echo htmlspecialchars($appointment['species'] ?? 'Unknown'); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <?php echo htmlspecialchars($appointment['owner_name'] ?? 'Unknown'); ?>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        <?php echo htmlspecialchars($appointment['phone'] ?? 'N/A'); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo htmlspecialchars($appointment['doctor_name'] ?? 'Unknown'); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php echo displayStatusBadge($appointment['status'], 'appointment'); ?>
                                </td>
                                <?php if (in_array(strtolower($staff_role), $allowed_roles_for_changes, true)): ?>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <?php if (($appointment['status'] ?? '') === 'Scheduled'): ?>
                                    <form method="POST" onsubmit="return confirm('Cancel this appointment?');" class="inline">
                                        <input type="hidden" name="action" value="cancel_appointment" />
                                        <input type="hidden" name="appointment_id" value="<?php echo (int)$appointment['appointment_id']; ?>" />
                                        <button type="submit" class="text-red-600 hover:text-red-900">Cancel</button>
                                    </form>
                                    <?php else: ?>
                                    <span class="text-gray-400">—</span>
                                    <?php endif; ?>
                                </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html> 