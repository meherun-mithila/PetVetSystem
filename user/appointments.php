<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_type'] !== 'user') {
    header("Location: ../index.php");
    exit();
}

$clinic_name = "Caring Paws Veterinary Clinic";
require_once '../config.php';

$user_id = $_SESSION['user_id'];
$appointments = [];
$user_pets = [];
$doctors = [];
$error_message = "";
$success_message = "";

// Handle form submissions
if ($_POST) {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'book') {
            $patient_id = (int)$_POST['patient_id'];
            $doctor_id = (int)$_POST['doctor_id'];
            $appointment_date = $_POST['appointment_date'];
            $appointment_time = $_POST['appointment_time'];
            $reason = trim($_POST['reason']);
            
            if (empty($patient_id) || empty($doctor_id) || empty($appointment_date) || empty($appointment_time)) {
                $error_message = "All fields are required.";
            } else {
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, reason, status) 
                        VALUES (?, ?, ?, ?, ?, 'Scheduled')
                    ");
                    $stmt->execute([$patient_id, $doctor_id, $appointment_date, $appointment_time, $reason]);
                    $success_message = "Appointment booked successfully!";
                } catch(PDOException $e) {
                    $error_message = "Failed to book appointment.";
                }
            }
        } elseif ($_POST['action'] === 'cancel') {
            $appointment_id = (int)$_POST['appointment_id'];
            try {
                $stmt = $pdo->prepare("
                    UPDATE appointments 
                    SET status = 'Cancelled' 
                    WHERE appointment_id = ? AND patient_id IN (SELECT patient_id FROM patients WHERE owner_id = ?)
                ");
                $stmt->execute([$appointment_id, $user_id]);
                $success_message = "Appointment cancelled successfully!";
            } catch(PDOException $e) {
                $error_message = "Failed to cancel appointment.";
            }
        }
    }
}

// Get user's pets
try {
    $stmt = $pdo->prepare("SELECT * FROM patients WHERE owner_id = ? ORDER BY animal_name");
    $stmt->execute([$user_id]);
    $user_pets = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error_message = "Failed to load pets.";
}

// Get available doctors
try {
    $stmt = $pdo->prepare("SELECT * FROM doctors WHERE availability = 'Available' ORDER BY name");
    $stmt->execute();
    $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error_message = "Failed to load doctors.";
}

// Get user's appointments
try {
    $stmt = $pdo->prepare("
        SELECT a.*, p.animal_name, p.species, d.name as doctor_name, d.specialization
        FROM appointments a
        JOIN patients p ON a.patient_id = p.patient_id
        JOIN doctors d ON a.doctor_id = d.doctor_id
        WHERE p.owner_id = ?
        ORDER BY a.appointment_date DESC, a.appointment_time DESC
    ");
    $stmt->execute([$user_id]);
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error_message = "Failed to load appointments.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointments - <?php echo $clinic_name; ?></title>
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
                <a href="appointments.php" class="text-vet-blue font-semibold border-b-2 border-vet-blue pb-2">Appointments</a>
                <a href="medical_records.php" class="text-gray-600 hover:text-vet-blue transition-colors">Medical Records</a>
                <a href="profile.php" class="text-gray-600 hover:text-vet-blue transition-colors">Profile</a>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-6 py-8">
        <!-- Messages -->
        <?php if (!empty($error_message)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <!-- Header -->
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800">My Appointments</h1>
            <button onclick="showBookForm()" class="bg-vet-blue hover:bg-vet-dark-blue text-white px-6 py-3 rounded-lg transition-colors">
                + Book Appointment
            </button>
        </div>

        <!-- Book Appointment Form (Hidden by default) -->
        <div id="bookForm" class="hidden bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Book New Appointment</h2>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="book">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Select Pet *</label>
                        <select name="patient_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-vet-blue focus:border-transparent">
                            <option value="">Choose your pet</option>
                            <?php foreach($user_pets as $pet): ?>
                                <option value="<?php echo $pet['patient_id']; ?>">
                                    <?php echo htmlspecialchars($pet['animal_name']); ?> (<?php echo htmlspecialchars($pet['species']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Select Doctor *</label>
                        <select name="doctor_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-vet-blue focus:border-transparent">
                            <option value="">Choose a doctor</option>
                            <?php foreach($doctors as $doctor): ?>
                                <option value="<?php echo $doctor['doctor_id']; ?>">
                                    Dr. <?php echo htmlspecialchars($doctor['name']); ?> (<?php echo htmlspecialchars($doctor['specialization']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Date *</label>
                        <input type="date" name="appointment_date" required min="<?php echo date('Y-m-d'); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-vet-blue focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Time *</label>
                        <input type="time" name="appointment_time" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-vet-blue focus:border-transparent">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Reason for Visit</label>
                    <textarea name="reason" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-vet-blue focus:border-transparent" placeholder="Describe the reason for the appointment..."></textarea>
                </div>
                <div class="flex space-x-4">
                    <button type="submit" class="bg-vet-blue hover:bg-vet-dark-blue text-white px-6 py-2 rounded-lg transition-colors">
                        Book Appointment
                    </button>
                    <button type="button" onclick="hideBookForm()" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg transition-colors">
                        Cancel
                    </button>
                </div>
            </form>
        </div>

        <!-- Appointments List -->
        <?php if (!empty($appointments)): ?>
            <div class="space-y-4">
                <?php foreach($appointments as $appointment): ?>
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <div class="flex items-center space-x-4 mb-2">
                                <h3 class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($appointment['animal_name']); ?></h3>
                                <span class="px-2 py-1 rounded-full text-xs font-medium 
                                    <?php 
                                    switch($appointment['status']) {
                                        case 'Scheduled': echo 'bg-blue-100 text-blue-800'; break;
                                        case 'Completed': echo 'bg-green-100 text-green-800'; break;
                                        case 'Cancelled': echo 'bg-red-100 text-red-800'; break;
                                        default: echo 'bg-gray-100 text-gray-800';
                                    }
                                    ?>">
                                    <?php echo htmlspecialchars($appointment['status']); ?>
                                </span>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm text-gray-600">
                                <div>
                                    <strong>Doctor:</strong> Dr. <?php echo htmlspecialchars($appointment['doctor_name']); ?><br>
                                    <span class="text-gray-500"><?php echo htmlspecialchars($appointment['specialization']); ?></span>
                                </div>
                                <div>
                                    <strong>Date:</strong> <?php echo date('M j, Y', strtotime($appointment['appointment_date'])); ?><br>
                                    <strong>Time:</strong> <?php echo date('g:i A', strtotime($appointment['appointment_time'])); ?>
                                </div>
                                <div>
                                    <strong>Pet:</strong> <?php echo htmlspecialchars($appointment['animal_name']); ?><br>
                                    <span class="text-gray-500"><?php echo htmlspecialchars($appointment['species']); ?></span>
                                </div>
                            </div>
                            <?php if (!empty($appointment['reason'])): ?>
                                <div class="mt-3 p-3 bg-gray-50 rounded-lg">
                                    <strong class="text-gray-700">Reason:</strong>
                                    <p class="text-gray-600 mt-1"><?php echo htmlspecialchars($appointment['reason']); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="ml-4">
                            <?php if ($appointment['status'] === 'Scheduled'): ?>
                                <button onclick="cancelAppointment(<?php echo $appointment['appointment_id']; ?>)" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg transition-colors text-sm">
                                    Cancel
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-12">
                <div class="text-6xl mb-4">📅</div>
                <h2 class="text-2xl font-bold text-gray-800 mb-2">No Appointments</h2>
                <p class="text-gray-600 mb-6">You haven't booked any appointments yet.</p>
                <button onclick="showBookForm()" class="bg-vet-blue hover:bg-vet-dark-blue text-white px-6 py-3 rounded-lg transition-colors">
                    Book Your First Appointment
                </button>
            </div>
        <?php endif; ?>
    </div>

    <!-- Cancel Confirmation Modal -->
    <div id="cancelModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Confirm Cancellation</h2>
            <p class="text-gray-600 mb-6">Are you sure you want to cancel this appointment?</p>
            <form method="POST" class="flex space-x-4">
                <input type="hidden" name="action" value="cancel">
                <input type="hidden" name="appointment_id" id="cancel_appointment_id">
                <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded-lg transition-colors">
                    Cancel Appointment
                </button>
                <button type="button" onclick="hideCancelModal()" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg transition-colors">
                    Keep Appointment
                </button>
            </form>
        </div>
    </div>

    <script>
        function showBookForm() {
            document.getElementById('bookForm').classList.remove('hidden');
        }

        function hideBookForm() {
            document.getElementById('bookForm').classList.add('hidden');
        }

        function cancelAppointment(appointmentId) {
            document.getElementById('cancel_appointment_id').value = appointmentId;
            document.getElementById('cancelModal').classList.remove('hidden');
        }

        function hideCancelModal() {
            document.getElementById('cancelModal').classList.add('hidden');
        }

        // Close modal when clicking outside
        document.getElementById('cancelModal').addEventListener('click', function(e) {
            if (e.target === this) {
                hideCancelModal();
            }
        });
    </script>
</body>
</html> 