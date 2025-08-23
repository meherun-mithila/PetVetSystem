<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_type'] !== 'admin') {
	header("Location: ../index.php");
	exit();
}

$clinic_name = "Caring Paws Veterinary Clinic";
require_once '../config.php';

$vaccines = [];
$patients = [];
$error_message = "";
$success_message = "";

// Get admin user information
$admin_name = $_SESSION['user_name'] ?? $_SESSION['name'] ?? $_SESSION['email'] ?? 'Admin User';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (isset($_POST['action'])) {
		switch ($_POST['action']) {
			case 'add':
				try {
					$stmt = $pdo->prepare("INSERT INTO vaccine_records (patient_id, vaccine_name, vaccine_type, date_administered, next_due_date, administered_by, batch_number, manufacturer, notes, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
					$stmt->execute([
						$_POST['patient_id'],
						$_POST['vaccine_name'],
						$_POST['vaccine_type'],
						$_POST['date_administered'],
						$_POST['next_due_date'] ?: null,
						$_POST['administered_by'] ?: null,
						$_POST['batch_number'] ?: null,
						$_POST['manufacturer'] ?: null,
						$_POST['notes'] ?: null,
						$_POST['status']
					]);
					$success_message = "Vaccine record added successfully!";
				} catch(PDOException $e) {
					$error_message = "Failed to add vaccine record: " . $e->getMessage();
				}
				break;
			case 'edit':
				try {
					$stmt = $pdo->prepare("UPDATE vaccine_records SET patient_id = ?, vaccine_name = ?, vaccine_type = ?, date_administered = ?, next_due_date = ?, administered_by = ?, batch_number = ?, manufacturer = ?, notes = ?, status = ? WHERE vaccine_id = ?");
					$stmt->execute([
						$_POST['patient_id'],
						$_POST['vaccine_name'],
						$_POST['vaccine_type'],
						$_POST['date_administered'],
						$_POST['next_due_date'] ?: null,
						$_POST['administered_by'] ?: null,
						$_POST['batch_number'] ?: null,
						$_POST['manufacturer'] ?: null,
						$_POST['notes'] ?: null,
						$_POST['status'],
						$_POST['vaccine_id']
					]);
					$success_message = "Vaccine record updated successfully!";
				} catch(PDOException $e) {
					$error_message = "Failed to update vaccine record: " . $e->getMessage();
				}
				break;
			case 'delete':
				try {
					$stmt = $pdo->prepare("DELETE FROM vaccine_records WHERE vaccine_id = ?");
					$stmt->execute([$_POST['vaccine_id']]);
					$success_message = "Vaccine record deleted successfully!";
				} catch(PDOException $e) {
					$error_message = "Failed to delete vaccine record: " . $e->getMessage();
				}
				break;
		}
	}
}

try {
	// Fetch patients for dropdown (with owner details)
	$stmt = $pdo->prepare("SELECT p.patient_id, p.animal_name, u.name AS owner_name FROM patients p JOIN users u ON p.owner_id = u.user_id ORDER BY p.animal_name");
	$stmt->execute();
	$patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

	// Pagination
	$valid_sizes = [10, 25, 50, 100];
	$records_per_page = isset($_GET['size']) && in_array((int)$_GET['size'], $valid_sizes, true) ? (int)$_GET['size'] : 25;
	$current_page = isset($_GET['page']) && (int)$_GET['page'] > 0 ? (int)$_GET['page'] : 1;

	$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM vaccine_records");
	$count_stmt->execute();
	$total_records = (int)$count_stmt->fetchColumn();

	$total_pages = max(1, (int)ceil($total_records / $records_per_page));
	if ($current_page > $total_pages) { $current_page = $total_pages; }
	$offset = ($current_page - 1) * $records_per_page;

	// Fetch vaccine records joined with patient and owner
	$stmt = $pdo->prepare(
		"SELECT vr.*, p.animal_name, u.name AS owner_name
		 FROM vaccine_records vr
		 JOIN patients p ON vr.patient_id = p.patient_id
		 JOIN users u ON p.owner_id = u.user_id
		 ORDER BY vr.date_administered DESC, vr.next_due_date ASC, vr.vaccine_id DESC
		 LIMIT " . (int)$records_per_page . " OFFSET " . (int)$offset
	);
	$stmt->execute();
	$vaccines = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
</head>
<body class="bg-gray-100">
	<header class="bg-blue-600 text-white p-4">
		<div class="max-w-7xl mx-auto flex justify-between items-center">
			<h1 class="text-2xl font-bold"><?php echo $clinic_name; ?> - Vaccine Records</h1>
			<div class="flex items-center space-x-4">
				<span class="text-sm">Welcome, <?php echo htmlspecialchars($admin_name); ?></span>
				<a href="dashboard.php" class="bg-blue-700 px-4 py-2 rounded">Dashboard</a>
			</div>
		</div>
	</header>

	<div class="max-w-7xl mx-auto p-6">
		<div class="flex justify-between items-center mb-6">
			<div>
				<h2 class="text-3xl font-bold">Vaccine Records Management</h2>
				<p class="text-gray-600 mt-1">Showing page <?php echo (int)$current_page; ?> of <?php echo (int)$total_pages; ?> (<?php echo (int)$total_records; ?> total)</p>
			</div>
			<div class="flex items-center space-x-4">
				<div>
					<label class="text-sm text-gray-600 mr-2">Page size</label>
					<select onchange="changePageSize(this.value)" class="border-gray-300 rounded-md px-2 py-1">
						<?php foreach([10,25,50,100] as $size): ?>
							<option value="<?php echo $size; ?>" <?php echo $records_per_page===$size? 'selected' : ''; ?>><?php echo $size; ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<button onclick="showAddForm()" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">Add Vaccine Record</button>
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

		<!-- Add Vaccine Form -->
		<div id="addVaccineForm" class="hidden bg-white rounded-lg shadow p-6 mb-6">
			<h3 class="text-xl font-semibold mb-4">Add Vaccine Record</h3>
			<form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
				<input type="hidden" name="action" value="add">

				<div>
					<label class="block text-sm font-medium text-gray-700 mb-1">Patient</label>
					<select name="patient_id" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
						<option value="">Select Patient</option>
						<?php foreach($patients as $p): ?>
							<option value="<?php echo $p['patient_id']; ?>"><?php echo htmlspecialchars(($p['animal_name'] ?? 'Unknown') . ' - ' . ($p['owner_name'] ?? '')); ?></option>
						<?php endforeach; ?>
					</select>
				</div>

				<div>
					<label class="block text-sm font-medium text-gray-700 mb-1">Vaccine Name</label>
					<input type="text" name="vaccine_name" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
				</div>

				<div>
					<label class="block text-sm font-medium text-gray-700 mb-1">Vaccine Type</label>
					<select name="vaccine_type" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
						<option value="Core Vaccine">Core Vaccine</option>
						<option value="Non-Core Vaccine">Non-Core Vaccine</option>
						<option value="Other">Other</option>
					</select>
				</div>

				<div>
					<label class="block text-sm font-medium text-gray-700 mb-1">Date Administered</label>
					<input type="date" name="date_administered" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
				</div>

				<div>
					<label class="block text-sm font-medium text-gray-700 mb-1">Next Due Date</label>
					<input type="date" name="next_due_date" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
				</div>

				<div>
					<label class="block text-sm font-medium text-gray-700 mb-1">Administered By</label>
					<input type="text" name="administered_by" placeholder="Doctor or Staff" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
				</div>

				<div>
					<label class="block text-sm font-medium text-gray-700 mb-1">Batch Number</label>
					<input type="text" name="batch_number" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
				</div>

				<div>
					<label class="block text-sm font-medium text-gray-700 mb-1">Manufacturer</label>
					<input type="text" name="manufacturer" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
				</div>

				<div class="md:col-span-2">
					<label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
					<textarea name="notes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
				</div>

				<div>
					<label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
					<select name="status" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
						<option value="Completed">Completed</option>
						<option value="Scheduled">Scheduled</option>
						<option value="Overdue">Overdue</option>
					</select>
				</div>

				<div class="md:col-span-2 flex gap-2">
					<button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Add Record</button>
					<button type="button" onclick="hideAddForm()" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">Cancel</button>
				</div>
			</form>
		</div>

		<!-- Edit Vaccine Form -->
		<div id="editVaccineForm" class="hidden bg-white rounded-lg shadow p-6 mb-6">
			<h3 class="text-xl font-semibold mb-4">Edit Vaccine Record</h3>
			<form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
				<input type="hidden" name="action" value="edit">
				<input type="hidden" name="vaccine_id" id="edit_vaccine_id">

				<div>
					<label class="block text-sm font-medium text-gray-700 mb-1">Patient</label>
					<select name="patient_id" id="edit_patient_id" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
						<option value="">Select Patient</option>
						<?php foreach($patients as $p): ?>
							<option value="<?php echo $p['patient_id']; ?>"><?php echo htmlspecialchars(($p['animal_name'] ?? 'Unknown') . ' - ' . ($p['owner_name'] ?? '')); ?></option>
						<?php endforeach; ?>
					</select>
				</div>

				<div>
					<label class="block text-sm font-medium text-gray-700 mb-1">Vaccine Name</label>
					<input type="text" name="vaccine_name" id="edit_vaccine_name" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
				</div>

				<div>
					<label class="block text-sm font-medium text-gray-700 mb-1">Vaccine Type</label>
					<select name="vaccine_type" id="edit_vaccine_type" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
						<option value="Core Vaccine">Core Vaccine</option>
						<option value="Non-Core Vaccine">Non-Core Vaccine</option>
						<option value="Other">Other</option>
					</select>
				</div>

				<div>
					<label class="block text-sm font-medium text-gray-700 mb-1">Date Administered</label>
					<input type="date" name="date_administered" id="edit_date_administered" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
				</div>

				<div>
					<label class="block text-sm font-medium text-gray-700 mb-1">Next Due Date</label>
					<input type="date" name="next_due_date" id="edit_next_due_date" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
				</div>

				<div>
					<label class="block text-sm font-medium text-gray-700 mb-1">Administered By</label>
					<input type="text" name="administered_by" id="edit_administered_by" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
				</div>

				<div>
					<label class="block text-sm font-medium text-gray-700 mb-1">Batch Number</label>
					<input type="text" name="batch_number" id="edit_batch_number" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
				</div>

				<div>
					<label class="block text-sm font-medium text-gray-700 mb-1">Manufacturer</label>
					<input type="text" name="manufacturer" id="edit_manufacturer" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
				</div>

				<div class="md:col-span-2">
					<label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
					<textarea name="notes" id="edit_notes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
				</div>

				<div>
					<label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
					<select name="status" id="edit_status" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
						<option value="Completed">Completed</option>
						<option value="Scheduled">Scheduled</option>
						<option value="Overdue">Overdue</option>
					</select>
				</div>

				<div class="md:col-span-2 flex gap-2">
					<button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Update Record</button>
					<button type="button" onclick="hideEditForm()" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">Cancel</button>
				</div>
			</form>
		</div>

		<?php if (!empty($vaccines)): ?>
			<div class="bg-white rounded-lg shadow overflow-hidden">
				<table class="min-w-full">
					<thead class="bg-gray-50">
						<tr>
							<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pet</th>
							<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Owner</th>
							<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vaccine</th>
							<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
							<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
							<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Next Due</th>
							<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
							<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Administered By</th>
							<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
						</tr>
					</thead>
					<tbody class="divide-y divide-gray-200">
						<?php foreach($vaccines as $v): ?>
						<tr class="hover:bg-gray-50">
							<td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($v['animal_name'] ?? 'Unknown'); ?></td>
							<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?php echo htmlspecialchars($v['owner_name'] ?? ''); ?></td>
							<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?php echo htmlspecialchars($v['vaccine_name']); ?></td>
							<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?php echo htmlspecialchars($v['vaccine_type']); ?></td>
							<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?php echo htmlspecialchars($v['date_administered']); ?></td>
							<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?php echo htmlspecialchars($v['next_due_date'] ?? ''); ?></td>
							<td class="px-6 py-4 whitespace-nowrap">
								<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php 
									echo $v['status']==='Completed' ? 'bg-green-100 text-green-800' : ($v['status']==='Scheduled' ? 'bg-blue-100 text-blue-800' : 'bg-red-100 text-red-800');
								?>"><?php echo htmlspecialchars($v['status']); ?></span>
							</td>
							<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?php echo htmlspecialchars($v['administered_by'] ?? ''); ?></td>
							<td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
								<button onclick='editVaccine(<?php echo htmlspecialchars(json_encode($v), ENT_QUOTES, "UTF-8"); ?>)' class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
								<button onclick="deleteVaccine(<?php echo (int)$v['vaccine_id']; ?>)" class="text-red-600 hover:text-red-900">Delete</button>
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
						$from = $total_records ? ($offset + 1) : 0;
						$to = min($offset + $records_per_page, $total_records);
						echo $from . ' to ' . $to . ' of ' . $total_records . ' records';
					?>
				</div>
				<div class="flex items-center space-x-1">
					<?php
						$queryBase = '?size=' . (int)$records_per_page . '&page=';
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
				<p class="text-gray-500">No vaccine records found.</p>
			</div>
		<?php endif; ?>
	</div>

	<!-- Delete Confirmation Modal -->
	<div id="deleteModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
		<div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
			<div class="mt-3 text-center">
				<h3 class="text-lg font-medium text-gray-900 mb-4">Confirm Delete</h3>
				<p class="text-sm text-gray-500 mb-4">Are you sure you want to delete this vaccine record? This action cannot be undone.</p>
				<div class="flex justify-center space-x-4">
					<form method="POST" id="deleteForm">
						<input type="hidden" name="action" value="delete">
						<input type="hidden" name="vaccine_id" id="delete_vaccine_id">
						<button type="submit" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">Delete</button>
					</form>
					<button onclick="hideDeleteModal()" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">Cancel</button>
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
			document.getElementById('addVaccineForm').classList.remove('hidden');
			document.getElementById('editVaccineForm').classList.add('hidden');
		}
		function hideAddForm() {
			document.getElementById('addVaccineForm').classList.add('hidden');
		}
		function editVaccine(v) {
			document.getElementById('edit_vaccine_id').value = v.vaccine_id;
			document.getElementById('edit_patient_id').value = v.patient_id;
			document.getElementById('edit_vaccine_name').value = v.vaccine_name;
			document.getElementById('edit_vaccine_type').value = v.vaccine_type;
			document.getElementById('edit_date_administered').value = v.date_administered;
			document.getElementById('edit_next_due_date').value = v.next_due_date || '';
			document.getElementById('edit_administered_by').value = v.administered_by || '';
			document.getElementById('edit_batch_number').value = v.batch_number || '';
			document.getElementById('edit_manufacturer').value = v.manufacturer || '';
			document.getElementById('edit_notes').value = v.notes || '';
			document.getElementById('edit_status').value = v.status;

			document.getElementById('addVaccineForm').classList.add('hidden');
			document.getElementById('editVaccineForm').classList.remove('hidden');
		}
		function hideEditForm() {
			document.getElementById('editVaccineForm').classList.add('hidden');
		}
		function deleteVaccine(id) {
			document.getElementById('delete_vaccine_id').value = id;
			document.getElementById('deleteModal').classList.remove('hidden');
		}
		function hideDeleteModal() {
			document.getElementById('deleteModal').classList.add('hidden');
		}
		setTimeout(function() {
			const messages = document.querySelectorAll('.bg-green-100, .bg-red-100');
			messages.forEach(function(message) { message.style.display = 'none'; });
		}, 5000);
	</script>
</body>
</html>
