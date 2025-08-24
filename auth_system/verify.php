<?php
require_once __DIR__ . '/bootstrap.php';

$email = $_GET['email'] ?? ($_SESSION['pending_email'] ?? '');
$email = is_string($email) ? trim($email) : '';
$message = '';
$is_error = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$email = trim($_POST['email'] ?? '');
	$otp = trim($_POST['otp'] ?? '');

	if ($email === '' || $otp === '') {
		$message = 'Please enter both email and OTP code.';
		$is_error = true;
	} else {
		try {
			// Lookup latest unused OTP for this email
			$stmt = $pdo->prepare('SELECT eo.id, eo.user_id, eo.otp_code, eo.is_used, eo.attempts, eo.expires_at FROM email_otps eo WHERE eo.email = ? ORDER BY eo.id DESC LIMIT 1');
			$stmt->execute([$email]);
			$otpRow = $stmt->fetch(PDO::FETCH_ASSOC);

			if (!$otpRow) {
				$message = 'No OTP found for this email. Please sign up again.';
				$is_error = true;
			} else {
				$now = new DateTime();
				$expiresAt = $otpRow['expires_at'] ? new DateTime($otpRow['expires_at']) : null;

				if ((int)$otpRow['is_used'] === 1) {
					$message = 'This OTP has already been used. Please request a new one.';
					$is_error = true;
				} elseif ($expiresAt && $now > $expiresAt) {
					$message = 'This OTP has expired. Please request a new one.';
					$is_error = true;
				} elseif ($otpRow['otp_code'] !== $otp) {
					// Increment attempts
					$upd = $pdo->prepare('UPDATE email_otps SET attempts = attempts + 1 WHERE id = ?');
					$upd->execute([$otpRow['id']]);
					$message = 'Invalid OTP. Please try again.';
					$is_error = true;
				} else {
					$pdo->beginTransaction();
					try {
						// Mark OTP as used
						$upd = $pdo->prepare('UPDATE email_otps SET is_used = 1 WHERE id = ?');
						$upd->execute([$otpRow['id']]);

						// Mark user as verified
						try {
							$u = $pdo->prepare('UPDATE users SET is_verified = 1 WHERE user_id = ?');
							$u->execute([(int)$otpRow['user_id']]);
						} catch (PDOException $e) {
							// Ignore if column missing
						}

						$pdo->commit();
						unset($_SESSION['pending_email']);
						$message = 'Your email has been verified successfully. You can now log in.';
						$is_error = false;
					} catch (Throwable $e) {
						$pdo->rollBack();
						$message = 'Verification failed. Please try again.';
						$is_error = true;
					}
				}
			}
		} catch (Throwable $e) {
			$message = 'An error occurred. Please try again later.';
			$is_error = true;
		}
	}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Verify OTP - PetVet</title>
	<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">
	<header class="bg-white shadow-sm border-b">
		<div class="max-w-md mx-auto px-4 sm:px-6 lg:px-8">
			<div class="flex justify-between items-center py-6">
				<h1 class="text-2xl font-bold text-gray-900">Verify your email</h1>
				<nav>
					<a href="../index.php" class="text-gray-500 hover:text-gray-900 text-sm">Home</a>
				</nav>
			</div>
		</div>
	</header>

	<main class="max-w-md mx-auto py-6 sm:px-6 lg:px-8">
		<?php if ($message): ?>
			<div class="mb-6 <?php echo $is_error ? 'bg-red-50 border border-red-200 text-red-700' : 'bg-green-50 border border-green-200 text-green-700'; ?> px-4 py-3 rounded-lg"><?php echo htmlspecialchars($message); ?></div>
		<?php endif; ?>

		<div class="bg-white overflow-hidden shadow rounded-lg">
			<div class="px-4 py-5 sm:p-6">
				<form method="POST" class="space-y-4">
					<div>
						<label for="email" class="block text-sm font-medium text-gray-700">Email *</label>
						<input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
					</div>
					<div>
						<label for="otp" class="block text-sm font-medium text-gray-700">OTP Code *</label>
						<input type="text" id="otp" name="otp" pattern="\d{6}" maxlength="6" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="Enter 6-digit code">
					</div>
					<div>
						<button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">Verify</button>
					</div>
				</form>
				<p class="mt-4 text-sm text-gray-500">Didn't get a code? Sign up again to receive a new one.</p>
			</div>
		</div>
	</main>
</body>
</html>


