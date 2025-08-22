<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_type'] !== 'staff') {
	header("Location: ../index.php");
	exit();
}

$clinic_name = "Caring Paws Veterinary Clinic";
require_once '../config.php';

$error_message = "";
$success_message = "";
$staff = [];
$extra_col = null;

$staff_id = (int)($_SESSION['user_id'] ?? 0);
if ($staff_id <= 0) {
	$error_message = 'Invalid staff session.';
}

// Discover optional column for other information (no schema changes, just detect)
try {
	$result = $pdo->query("DESCRIBE staff");
	$cols = $result->fetchAll(PDO::FETCH_COLUMN);
	$candidates = ['extra_info', 'notes', 'about', 'additional_info', 'other_info', 'bio'];
	foreach ($candidates as $c) {
		if (in_array($c, $cols, true)) { $extra_col = $c; break; }
	}
} catch (PDOException $e) {
	// ignore; page still works without extra field
}

// Handle update of phone/address/other info
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_contact') {
	try {
		$phone = trim($_POST['phone'] ?? '');
		$address = trim($_POST['address'] ?? '');
		$other = trim($_POST['other_info'] ?? '');

		if ($extra_col) {
			$stmt = $pdo->prepare("UPDATE staff SET phone = ?, address = ?, {$extra_col} = ? WHERE staff_id = ?");
			$stmt->execute([$phone, $address, $other, $staff_id]);
		} else {
			$stmt = $pdo->prepare("UPDATE staff SET phone = ?, address = ? WHERE staff_id = ?");
			$stmt->execute([$phone, $address, $staff_id]);
		}

		$success_message = 'Details updated successfully.';
	} catch (PDOException $e) {
		$error_message = 'Failed to update details: ' . $e->getMessage();
	}
}

// Load staff info (for display and form defaults)
try {
	$stmt = $pdo->prepare("SELECT * FROM staff WHERE staff_id = ?");
	$stmt->execute([$staff_id]);
	$staff = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
} catch (PDOException $e) {
	$error_message = 'Failed to load profile: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>My Profile - <?php echo $clinic_name; ?></title>
	<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
	<header class="bg-blue-600 text-white p-4">
		<div class="max-w-7xl mx-auto flex justify-between items-center">
			<h1 class="text-2xl font-bold"><?php echo $clinic_name; ?> - My Profile</h1>
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
				<a href="medical_reports.php" class="text-gray-500 hover:text-blue-600 py-4 px-1 font-medium">Medical Reports</a>
				<a href="reports.php" class="text-gray-500 hover:text-blue-600 py-4 px-1 font-medium">Reports</a>
				<a href="profile.php" class="border-b-2 border-blue-600 text-blue-600 py-4 px-1 font-medium">Profile</a>
			</div>
		</div>
	</nav>

	<main class="max-w-3xl mx-auto px-6 py-8">
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

		<div class="bg-white rounded-lg shadow p-6 mb-6">
			<h2 class="text-xl font-semibold text-gray-900 mb-4">My Details</h2>
			<div class="grid grid-cols-1 gap-4">
				<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
					<div>
						<p class="text-sm text-gray-500">Full Name</p>
						<p class="text-gray-900 font-medium"><?php echo htmlspecialchars($staff['name'] ?? ($_SESSION['user_name'] ?? '')); ?></p>
					</div>
					<div>
						<p class="text-sm text-gray-500">Email</p>
						<p class="text-gray-900 font-medium"><?php echo htmlspecialchars($staff['email'] ?? ($_SESSION['user_email'] ?? '')); ?></p>
					</div>
				</div>
				<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
					<div>
						<p class="text-sm text-gray-500">Phone</p>
						<p class="text-gray-900"><?php echo htmlspecialchars($staff['phone'] ?? 'N/A'); ?></p>
					</div>
					<div>
						<p class="text-sm text-gray-500">Role</p>
						<p class="text-gray-900"><?php echo htmlspecialchars($staff['role'] ?? 'Staff'); ?></p>
					</div>
				</div>
				<div>
					<p class="text-sm text-gray-500">Address</p>
					<p class="text-gray-900 whitespace-pre-line"><?php echo htmlspecialchars($staff['address'] ?? 'N/A'); ?></p>
				</div>
				<?php if ($extra_col): ?>
				<div>
					<p class="text-sm text-gray-500">Other Information</p>
					<p class="text-gray-900 whitespace-pre-line"><?php echo htmlspecialchars($staff[$extra_col] ?? 'N/A'); ?></p>
				</div>
				<?php endif; ?>
			</div>
		</div>

		<!-- Editable Contact & Other Information -->
		<div class="bg-white rounded-lg shadow p-6">
			<h2 class="text-xl font-semibold text-gray-900 mb-4">Update Contact & Other Info</h2>
			<form method="POST" class="grid grid-cols-1 gap-4">
				<input type="hidden" name="action" value="update_contact" />
				<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
					<div>
						<label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
						<input type="text" name="phone" value="<?php echo htmlspecialchars($staff['phone'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md" />
					</div>
					<div>
						<label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
						<input type="text" name="address" value="<?php echo htmlspecialchars($staff['address'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md" />
					</div>
				</div>
				<?php if ($extra_col): ?>
				<div>
					<label class="block text-sm font-medium text-gray-700 mb-1">Other Information</label>
					<textarea name="other_info" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="Add any additional details..."><?php echo htmlspecialchars($staff[$extra_col] ?? ''); ?></textarea>
				</div>
				<?php endif; ?>
				<div class="flex gap-2">
					<button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Save Changes</button>
					<a href="profile.php" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">Cancel</a>
				</div>
			</form>
		</div>
	</main>
</body>
</html>
