<?php
require_once __DIR__ . '/bootstrap.php';

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$name = trim($_POST['name'] ?? '');
	$email = trim($_POST['email'] ?? '');
	$password = $_POST['password'] ?? '';
	$confirm_password = $_POST['confirm_password'] ?? '';

	if ($name === '' || $email === '' || $password === '' || $confirm_password === '') {
		$error_message = 'Please fill in all required fields.';
	} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
		$error_message = 'Please enter a valid email address.';
	} elseif ($password !== $confirm_password) {
		$error_message = 'Passwords do not match.';
	} else {
		try {
			$pdo->beginTransaction();

			// Check for existing user
			$stmt = $pdo->prepare('SELECT user_id FROM users WHERE email = ? LIMIT 1');
			$stmt->execute([$email]);
			$existing = $stmt->fetch(PDO::FETCH_ASSOC);

			if ($existing) {
				$pdo->rollBack();
				$error_message = 'An account with this email already exists.';
			} else {
				// Generate a unique user_id that won't conflict with deleted users
				$stmt = $pdo->prepare('SELECT MAX(user_id) as max_id FROM users');
				$stmt->execute();
				$result = $stmt->fetch(PDO::FETCH_ASSOC);
				$next_user_id = ($result['max_id'] ?? 0) + 1;
				
				$hashed = password_hash($password, PASSWORD_DEFAULT);
				// Create new user with explicit user_id to prevent reuse
				try {
					$insert = $pdo->prepare('INSERT INTO users (user_id, name, email, password, is_verified) VALUES (?, ?, ?, ?, 0)');
					$insert->execute([$next_user_id, $name, $email, $hashed]);
				} catch (PDOException $e) {
					$insert = $pdo->prepare('INSERT INTO users (user_id, name, email, password) VALUES (?, ?, ?, ?)');
					$insert->execute([$next_user_id, $name, $email, $hashed]);
				}

				$pdo->commit();

				// Redirect to login with success message
				$_SESSION['verification_success'] = 'Registration successful! Please sign in.';
				header('Location: ../index.php');
				exit;
			}
		} catch (Throwable $e) {
			if ($pdo->inTransaction()) {
				$pdo->rollBack();
			}
			$error_message = 'Registration failed. Please try again.';
		}
	}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Sign Up - PetVet</title>
	<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">
	<header class="bg-white shadow-sm border-b">
		<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
			<div class="flex justify-between items-center py-6">
				<h1 class="text-2xl font-bold text-gray-900">Create your account</h1>
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
						<label for="name" class="block text-sm font-medium text-gray-700">Full Name *</label>
						<input type="text" id="name" name="name" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
					</div>
					<div>
						<label for="email" class="block text-sm font-medium text-gray-700">Email Address *</label>
						<input type="email" id="email" name="email" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
					</div>
					<div>
						<label for="password" class="block text-sm font-medium text-gray-700">Password *</label>
						<input type="password" id="password" name="password" required minlength="6" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
					</div>
					<div>
						<label for="confirm_password" class="block text-sm font-medium text-gray-700">Confirm Password *</label>
						<input type="password" id="confirm_password" name="confirm_password" required minlength="6" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
					</div>
					<div>
						<button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">Sign Up</button>
					</div>
				</form>
				<p class="mt-4 text-sm text-gray-500">After signing up, go back to the login page and sign in as a Pet Owner.</p>
			</div>
		</div>
	</main>
</body>
</html>


