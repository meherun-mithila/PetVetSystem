<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_type'] !== 'staff') {
	header("Location: ../index.php");
	exit();
}

$clinic_name = "Caring Paws Veterinary Clinic";
require_once '../config.php';

$error_message = "";
$records = [];
$doctors = [];
$species_list = [];

// Filters
$filter_doctor = $_GET['doctor_id'] ?? '';
$filter_species = $_GET['species'] ?? '';
$filter_from = $_GET['from'] ?? '';
$filter_to = $_GET['to'] ?? '';

try {
	// Discover medicalrecords table schema
	$result = $pdo->query("DESCRIBE medicalrecords");
	$medical_columns = $result->fetchAll(PDO::FETCH_COLUMN);
	
	$date_col = in_array('record_date', $medical_columns) ? 'record_date' : (in_array('date', $medical_columns) ? 'date' : null);
	$cost_col = in_array('cost', $medical_columns) ? 'cost' : (in_array('bills', $medical_columns) ? 'bills' : null);
	$diagnosis_col = in_array('diagnosis', $medical_columns) ? 'diagnosis' : null;
	$treatment_col = in_array('treatment', $medical_columns) ? 'treatment' : null;
	$notes_col = in_array('notes', $medical_columns) ? 'notes' : null;
	$patient_fk = in_array('patient_id', $medical_columns) ? 'patient_id' : null;
	$doctor_fk = in_array('doctor_id', $medical_columns) ? 'doctor_id' : null;
	$record_id_col = in_array('record_id', $medical_columns) ? 'record_id' : (in_array('id', $medical_columns) ? 'id' : null);
	
	if ($date_col === null || $patient_fk === null) {
		throw new PDOException('medicalrecords table missing required columns');
	}
	
	// Build base query
	$query = "
		SELECT mr.*, 
			p.animal_name, p.species, u.name AS owner_name, 
			d.name AS doctor_name
		FROM medicalrecords mr
		JOIN patients p ON mr.$patient_fk = p.patient_id
		JOIN users u ON p.owner_id = u.user_id
		" . ($doctor_fk ? "LEFT JOIN doctors d ON mr.$doctor_fk = d.doctor_id" : "LEFT JOIN doctors d ON 1=0") . "
		WHERE 1=1
	";
	$params = [];
	
	// Apply filters
	if (!empty($filter_doctor) && $doctor_fk) {
		$query .= " AND mr.$doctor_fk = ?";
		$params[] = $filter_doctor;
	}
	if (!empty($filter_species)) {
		$query .= " AND p.species = ?";
		$params[] = $filter_species;
	}
	if (!empty($filter_from)) {
		$query .= " AND DATE(mr.$date_col) >= ?";
		$params[] = $filter_from;
	}
	if (!empty($filter_to)) {
		$query .= " AND DATE(mr.$date_col) <= ?";
		$params[] = $filter_to;
	}
	
	$query .= " ORDER BY mr.$date_col DESC";
	
	$stmt = $pdo->prepare($query);
	$stmt->execute($params);
	$records = $stmt->fetchAll(PDO::FETCH_ASSOC);
	
	// Doctors for filter
	$doctors = $pdo->query("SELECT doctor_id, name FROM doctors ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
	
	// Species list for filter
	$species_list = $pdo->query("SELECT DISTINCT species FROM patients WHERE species IS NOT NULL AND species <> '' ORDER BY species")->fetchAll(PDO::FETCH_COLUMN);
	
} catch(PDOException $e) {
	$error_message = "Failed to load medical reports: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Medical Reports - <?php echo $clinic_name; ?></title>
	<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
	<header class="bg-blue-600 text-white p-4">
		<div class="max-w-7xl mx-auto flex justify-between items-center">
			<h1 class="text-2xl font-bold"><?php echo $clinic_name; ?> - Medical Reports</h1>
			<a href="dashboard.php" class="bg-blue-700 px-4 py-2 rounded">Dashboard</a>
		</div>
	</header>

	<nav class="bg-white shadow-sm border-b">
		<div class="max-w-7xl mx-auto px-6">
			<div class="flex space-x-8">
				<a href="dashboard.php" class="text-gray-500 hover:text-blue-600 py-4 px-1 font-medium">Dashboard</a>
				<a href="appointments.php" class="text-gray-500 hover:text-blue-600 py-4 px-1 font-medium">Appointments</a>
				<a href="patients.php" class="text-gray-500 hover:text-blue-600 py-4 px-1 font-medium">Patients</a>
				<a href="doctors.php" class="text-gray-500 hover:text-blue-600 py-4 px-1 font-medium">Doctors</a>
				<a href="billing.php" class="text-gray-500 hover:text-blue-600 py-4 px-1 font-medium">Billing</a>
				<a href="medical_reports.php" class="border-b-2 border-blue-600 text-blue-600 py-4 px-1 font-medium">Medical Reports</a>
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

		<!-- Filters -->
		<div class="bg-white rounded-lg shadow p-6 mb-6">
			<h3 class="text-lg font-semibold text-gray-900 mb-4">Filter Medical Records</h3>
			<form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
				<div>
					<label class="block text-sm font-medium text-gray-700 mb-1">Doctor</label>
					<select name="doctor_id" class="w-full px-3 py-2 border border-gray-300 rounded-md">
						<option value="">All Doctors</option>
						<?php foreach($doctors as $doc): ?>
							<option value="<?php echo $doc['doctor_id']; ?>" <?php echo ($filter_doctor == $doc['doctor_id']) ? 'selected' : ''; ?>>
								<?php echo htmlspecialchars($doc['name']); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>
				<div>
					<label class="block text-sm font-medium text-gray-700 mb-1">Species</label>
					<select name="species" class="w-full px-3 py-2 border border-gray-300 rounded-md">
						<option value="">All Species</option>
						<?php foreach($species_list as $sp): ?>
							<option value="<?php echo htmlspecialchars($sp); ?>" <?php echo ($filter_species === $sp) ? 'selected' : ''; ?>>
								<?php echo htmlspecialchars($sp); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>
				<div>
					<label class="block text-sm font-medium text-gray-700 mb-1">From</label>
					<input type="date" name="from" value="<?php echo htmlspecialchars($filter_from); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md">
				</div>
				<div>
					<label class="block text-sm font-medium text-gray-700 mb-1">To</label>
					<input type="date" name="to" value="<?php echo htmlspecialchars($filter_to); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md">
				</div>
				<div class="md:col-span-4 flex items-center gap-2">
					<button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Apply Filters</button>
					<a href="medical_reports.php" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">Reset</a>
				</div>
			</form>
		</div>

		<!-- Medical Records Table -->
		<div class="bg-white rounded-lg shadow overflow-hidden">
			<div class="px-6 py-4 border-b border-gray-200">
				<h3 class="text-lg font-semibold text-gray-900">Medical Records</h3>
			</div>
			<div class="overflow-x-auto">
				<table class="min-w-full">
					<thead class="bg-gray-50">
						<tr>
							<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
							<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pet</th>
							<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Owner</th>
							<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Doctor</th>
							<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Diagnosis</th>
							<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Treatment</th>
							<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cost</th>
						</tr>
					</thead>
					<tbody class="divide-y divide-gray-200">
						<?php foreach($records as $rec): ?>
						<tr class="hover:bg-gray-50">
							<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
								<?php echo isset($rec[$date_col]) ? date('M j, Y', strtotime($rec[$date_col])) : 'N/A'; ?>
							</td>
							<td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
								<?php echo htmlspecialchars($rec['animal_name'] ?? 'Unknown'); ?>
								<span class="text-xs text-gray-500 ml-1">(<?php echo htmlspecialchars($rec['species'] ?? ''); ?>)</span>
							</td>
							<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($rec['owner_name'] ?? 'Unknown'); ?></td>
							<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($rec['doctor_name'] ?? 'N/A'); ?></td>
							<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($diagnosis_col && isset($rec[$diagnosis_col]) ? $rec[$diagnosis_col] : 'N/A'); ?></td>
							<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($treatment_col && isset($rec[$treatment_col]) ? $rec[$treatment_col] : 'N/A'); ?></td>
							<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-semibold">
								<?php echo $cost_col && isset($rec[$cost_col]) ? '$' . number_format((float)$rec[$cost_col], 2) : 'N/A'; ?>
							</td>
						</tr>
						<?php endforeach; ?>
						<?php if (empty($records)): ?>
						<tr>
							<td colspan="7" class="px-6 py-6 text-center text-gray-500">No medical records found for the selected filters.</td>
						</tr>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
		</div>
	</main>
</body>
</html>
