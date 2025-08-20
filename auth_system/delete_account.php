<?php
require_once __DIR__ . '/bootstrap.php';

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$email = trim($_POST['email'] ?? '');
	$password = $_POST['password'] ?? '';

	if ($email === '' || $password === '') {
		$error_message = 'Please provide both email and password.';
	} else {
		try {
			$stmt = $pdo->prepare('SELECT user_id, password FROM users WHERE email = ? LIMIT 1');
			$stmt->execute([$email]);
			$user = $stmt->fetch(PDO::FETCH_ASSOC);

			if (!$user) {
				$error_message = 'No account found with this email.';
			} else {
				$valid = password_verify($password, $user['password']) || $password === $user['password'];
				if (!$valid) {
					$error_message = 'Incorrect password.';
				} else {
					// Prevent accidental deletion if there are dependent records
					$petCountStmt = $pdo->prepare('SELECT COUNT(*) FROM pets WHERE user_id = ?');
					try { $petCountStmt->execute([$user['user_id']]); } catch (Throwable $e) { $petCountStmt = null; }
					$pet_count = $petCountStmt ? (int)$petCountStmt->fetchColumn() : 0;

					$apptCountStmt = $pdo->prepare('SELECT COUNT(*) FROM appointments WHERE user_id = ?');
					try { $apptCountStmt->execute([$user['user_id']]); } catch (Throwable $e) { $apptCountStmt = null; }
					$appointment_count = $apptCountStmt ? (int)$apptCountStmt->fetchColumn() : 0;

					if ($pet_count > 0 || $appointment_count > 0) {
						$error_message = "Cannot delete account. You have $pet_count pet(s) and $appointment_count appointment(s) associated with this account.";
					} else {
						$pdo->beginTransaction();
						try {
							$delOtps = $pdo->prepare('DELETE FROM email_otps WHERE user_id = ?');
							$delOtps->execute([$user['user_id']]);

							$delVer = $pdo->prepare('DELETE FROM email_verifications WHERE user_id = ?');
							try { $delVer->execute([$user['user_id']]); } catch (Throwable $e) { }

							$delUser = $pdo->prepare('DELETE FROM users WHERE user_id = ?');
							$delUser->execute([$user['user_id']]);

							$pdo->commit();
							$success_message = 'Account deleted successfully.';
						} catch (Throwable $e) {
							$pdo->rollBack();
							$error_message = 'Account deletion failed. Please try again later.';
						}
					}
				}
			}
		} catch (Throwable $e) {
			$error_message = 'An error occurred. Please try again later.';
		}
	}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Delete Account - PetVet</title>
	<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">
	<header class="bg-white shadow-sm border-b">
		<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
			<div class="flex justify-between items-center py-6">
				<h1 class="text-2xl font-bold text-gray-900">Delete your account</h1>
				<nav>
					<a href="../index.php" class="text-gray-500 hover:text-gray-900 text-sm">Home</a>
				</nav>
			</div>
		</div>
	</header>

	<main class="max-w-2xl mx-auto py-6 sm:px-6 lg:px-8">
		<?php if ($error_message): ?>
			<div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg"><?php echo htmlspecialchars($error_message); ?></div>
		<?php endif; ?>
		<?php if ($success_message): ?>
			<div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg"><?php echo htmlspecialchars($success_message); ?></div>
		<?php endif; ?>

		<div class="bg-white overflow-hidden shadow rounded-lg">
			<div class="px-4 py-5 sm:p-6">
				<form method="POST" class="space-y-4">
					<div>
						<label for="email" class="block text-sm font-medium text-gray-700">Email Address *</label>
						<input type="email" id="email" name="email" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500 sm:text-sm">
					</div>
					<div>
						<label for="password" class="block text-sm font-medium text-gray-700">Password *</label>
						<input type="password" id="password" name="password" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500 sm:text-sm">
					</div>
					<div>
						<button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">Delete Account</button>
					</div>
				</form>
				<p class="mt-4 text-sm text-gray-500">Warning: This action is permanent.</p>
			</div>
		</div>
	</main>
</body>
</html>


