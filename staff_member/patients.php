<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_type'] !== 'staff') {
    header("Location: ../index.php");
    exit();
}

$clinic_name = "Caring Paws Veterinary Clinic";
require_once '../config.php';

$patients = [];
$owners = [];
$error_message = "";
$success_message = "";

// Handle register patient
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'register_patient') {
    try {
        $owner_id = trim($_POST['owner_id'] ?? '');
        $animal_name = trim($_POST['animal_name'] ?? '');
        $species = trim($_POST['species'] ?? '');
        $breed = trim($_POST['breed'] ?? '');
        $age = trim($_POST['age'] ?? '');
        $gender = trim($_POST['gender'] ?? '');
        $color = trim($_POST['color'] ?? '');
        $weight = trim($_POST['weight'] ?? '');
        $medical_history = trim($_POST['medical_history'] ?? '');

        if ($owner_id === '' || $animal_name === '' || $species === '') {
            throw new Exception('Owner, Animal Name, and Species are required.');
        }

        // Build insert addressing optional columns
        $result = $pdo->query("DESCRIBE patients");
        $cols = $result->fetchAll(PDO::FETCH_COLUMN);

        $fields = ['owner_id', 'animal_name', 'species'];
        $placeholders = ['?', '?', '?'];
        $values = [$owner_id, $animal_name, $species];

        if (in_array('breed', $cols)) { $fields[] = 'breed'; $placeholders[] = '?'; $values[] = $breed; }
        if (in_array('age', $cols)) { $fields[] = 'age'; $placeholders[] = '?'; $values[] = ($age !== '' ? (int)$age : null); }
        if (in_array('gender', $cols)) { $fields[] = 'gender'; $placeholders[] = '?'; $values[] = $gender; }
        if (in_array('color', $cols)) { $fields[] = 'color'; $placeholders[] = '?'; $values[] = $color; }
        if (in_array('weight', $cols)) { $fields[] = 'weight'; $placeholders[] = '?'; $values[] = ($weight !== '' ? (float)$weight : null); }
        if (in_array('medical_history', $cols)) { $fields[] = 'medical_history'; $placeholders[] = '?'; $values[] = $medical_history; }

        $sql = 'INSERT INTO patients (' . implode(',', $fields) . ') VALUES (' . implode(',', $placeholders) . ')';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);

        $success_message = 'Patient registered successfully.';
        header('Location: ' . $_SERVER['PHP_SELF'] . '#register');
        exit();
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    } catch (PDOException $e) {
        $error_message = 'Failed to register patient: ' . $e->getMessage();
    }
}

try {
    // Check if patients table has created_at column
    $result = $pdo->query("DESCRIBE patients");
    $patient_columns = $result->fetchAll(PDO::FETCH_COLUMN);
    $order_by = in_array('created_at', $patient_columns) ? 'p.created_at DESC' : 'p.patient_id DESC';
    
    // Get all patients with owner information
    $patients = $pdo->query("
        SELECT p.*, u.name as owner_name, u.phone as owner_phone
        FROM patients p
        JOIN users u ON p.owner_id = u.user_id
        ORDER BY $order_by
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Owners for selection
    $stmt = $pdo->prepare("SELECT user_id, name, email, phone FROM users ORDER BY name");
    $stmt->execute();
    $owners = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patients - <?php echo $clinic_name; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <header class="bg-blue-600 text-white p-4">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold"><?php echo $clinic_name; ?> - Patients</h1>
            <a href="dashboard.php" class="bg-blue-700 px-4 py-2 rounded">Dashboard</a>
        </div>
    </header>

    <nav class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-6">
            <div class="flex space-x-8">
                <a href="dashboard.php" class="text-gray-500 hover:text-blue-600 py-4 px-1 font-medium">Dashboard</a>
                <a href="appointments.php" class="text-gray-500 hover:text-blue-600 py-4 px-1 font-medium">Appointments</a>
                <a href="patients.php" class="border-b-2 border-blue-600 text-blue-600 py-4 px-1 font-medium">Patients</a>
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

        <!-- Register Patient Form -->
        <div id="register" class="bg-white rounded-lg shadow p-6 mb-8">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Register New Patient</h3>
            <form method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <input type="hidden" name="action" value="register_patient" />
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Owner *</label>
                    <select name="owner_id" required class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        <option value="">Select owner</option>
                        <?php foreach ($owners as $o): ?>
                        <option value="<?php echo $o['user_id']; ?>"><?php echo htmlspecialchars($o['name']); ?> (<?php echo htmlspecialchars($o['phone'] ?? $o['email']); ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Animal Name *</label>
                    <input type="text" name="animal_name" required class="w-full px-3 py-2 border border-gray-300 rounded-md" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Species *</label>
                    <select name="species" required class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        <option value="Dog">Dog</option>
                        <option value="Cat">Cat</option>
                        <option value="Bird">Bird</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Breed</label>
                    <input type="text" name="breed" class="w-full px-3 py-2 border border-gray-300 rounded-md" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Age</label>
                    <input type="number" min="0" name="age" class="w-full px-3 py-2 border border-gray-300 rounded-md" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Gender</label>
                    <select name="gender" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Color</label>
                    <input type="text" name="color" class="w-full px-3 py-2 border border-gray-300 rounded-md" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Weight (kg)</label>
                    <input type="number" step="0.01" min="0" name="weight" class="w-full px-3 py-2 border border-gray-300 rounded-md" />
                </div>
                <div class="md:col-span-3">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Medical History</label>
                    <textarea name="medical_history" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="Optional"></textarea>
                </div>
                <div class="md:col-span-3">
                    <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">Register Patient</button>
                </div>
            </form>
        </div>

        <!-- Patients Table -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">All Patients</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Patient</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Owner</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Species</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Breed</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Age</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Gender</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Registered</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach($patients as $patient): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($patient['animal_name'] ?? 'Unknown'); ?>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        ID: <?php echo htmlspecialchars($patient['patient_id'] ?? 'N/A'); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <?php echo htmlspecialchars($patient['owner_name'] ?? 'Unknown'); ?>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        <?php echo htmlspecialchars($patient['owner_phone'] ?? 'N/A'); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo htmlspecialchars($patient['species'] ?? 'Unknown'); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo htmlspecialchars($patient['breed'] ?? 'Unknown'); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo htmlspecialchars($patient['age'] ?? 'Unknown'); ?> years
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo htmlspecialchars($patient['gender'] ?? 'Unknown'); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php 
                                    if (isset($patient['created_at'])) {
                                        echo date('M j, Y', strtotime($patient['created_at']));
                                    } else {
                                        echo 'N/A';
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html> 