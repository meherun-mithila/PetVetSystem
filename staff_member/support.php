<?php
require_once __DIR__ . '/../config.php';

$clinic_name = "Caring Paws Veterinary Clinic";
$error_message = '';
$receptionists = [];

try {
	// Try to find staff with receptionist-like roles
	$query = "SELECT name, email, role FROM staff WHERE LOWER(role) IN ('receptionist','front desk','frontdesk','front-desk') ORDER BY name";
	$stmt = $pdo->query($query);
	$receptionists = $stmt->fetchAll(PDO::FETCH_ASSOC);

	// Fallback: if no explicit receptionist found, show first staff member
	if (!$receptionists) {
		$fallback = $pdo->query("SELECT name, email, role FROM staff ORDER BY staff_id LIMIT 1");
		$tmp = $fallback->fetch(PDO::FETCH_ASSOC);
		if ($tmp) { $receptionists = [$tmp]; }
	}
} catch (Throwable $e) {
	$error_message = 'Unable to load support contacts.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Contact Support - <?php echo htmlspecialchars($clinic_name); ?></title>
	<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
	<header class="bg-gradient-to-r from-vet-blue to-vet-dark-blue text-white shadow">
		<div class="max-w-5xl mx-auto px-6 py-4">
			<div class="flex items-center justify-between">
				<div class="flex items-center">
					<span class="text-2xl mr-3">🐾</span>
					<h1 class="text-xl font-bold">Contact Support</h1>
				</div>
				<a href="../index.php" class="text-sm underline hover:text-blue-200">Back to Login</a>
			</div>
		</div>
	</header>

	<main class="max-w-5xl mx-auto px-6 py-8">
		<p class="text-gray-700 mb-6">Need help? Reach our front desk team for quick assistance.</p>

		<?php if ($error_message): ?>
			<div class="bg-red-100 border border-red-300 text-red-700 px-4 py-3 rounded mb-6"><?php echo htmlspecialchars($error_message); ?></div>
		<?php endif; ?>

		<?php if ($receptionists): ?>
			<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
				<?php foreach ($receptionists as $person): ?>
					<div class="bg-white rounded-lg shadow p-6">
						<h2 class="text-lg font-semibold text-gray-800"><?php echo htmlspecialchars($person['name'] ?? 'Reception'); ?></h2>
						<p class="text-sm text-gray-500 mb-4"><?php echo htmlspecialchars(ucfirst($person['role'] ?? 'Receptionist')); ?></p>
						<div class="space-y-2 text-sm">
							<p><strong>Email:</strong> <a class="text-vet-blue hover:text-vet-dark-blue underline" href="mailto:<?php echo htmlspecialchars($person['email'] ?? ''); ?>"><?php echo htmlspecialchars($person['email'] ?? ''); ?></a></p>
							<p><strong>Phone:</strong> <a class="text-vet-blue hover:text-vet-dark-blue underline" href="tel:<?php echo htmlspecialchars($person['phone'] ?? ''); ?>"><?php echo htmlspecialchars($person['phone'] ?? 'N/A'); ?></a></p>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		<?php else: ?>
			<div class="bg-white rounded-lg shadow p-6">
				<p class="text-gray-700">No support contacts are available at the moment. Please check back later.</p>
			</div>
		<?php endif; ?>
	</main>
</body>
</html>


