<?php
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
require_once __DIR__ . '/../auth_system/bootstrap.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	http_response_code(204);
	exit;
}

function readJsonOrForm(): array {
	$raw = file_get_contents('php://input');
	if (is_string($raw) && $raw !== '') {
		$decoded = json_decode($raw, true);
		if (is_array($decoded)) { return $decoded; }
	}
	return !empty($_POST) ? $_POST : (!empty($_GET) ? $_GET : []);
}

try {
	if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
		http_response_code(405);
		echo json_encode(['success' => false, 'message' => 'Method not allowed']);
		exit;
	}

	$body = readJsonOrForm();
	$email = trim((string)($body['email'] ?? ''));
	$code = trim((string)($body['otp'] ?? $body['code'] ?? ''));

	if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
		http_response_code(400);
		echo json_encode(['success' => false, 'message' => 'Valid email is required']);
		exit;
	}
	if ($code === '' || !preg_match('/^\d{4,8}$/', $code)) {
		http_response_code(400);
		echo json_encode(['success' => false, 'message' => 'Valid OTP is required']);
		exit;
	}

	// Find latest unused OTP for this email
	$stmt = $pdo->prepare('SELECT * FROM email_otps WHERE email = ? AND is_used = 0 ORDER BY id DESC LIMIT 1');
	$stmt->execute([$email]);
	$otpRow = $stmt->fetch(PDO::FETCH_ASSOC);
	if (!$otpRow) {
		http_response_code(400);
		echo json_encode(['success' => false, 'message' => 'No active code. Please request a new OTP.']);
		exit;
	}

	$now = new DateTime();
	$expiresAt = new DateTime((string)$otpRow['expires_at']);
	$attempts = (int)($otpRow['attempts'] ?? 0);

	// Optional attempt limit
	if ($attempts >= 5) {
		http_response_code(429);
		echo json_encode(['success' => false, 'message' => 'Too many attempts. Request a new OTP.']);
		exit;
	}

	// Check expiry
	if ($now > $expiresAt) {
		// mark used to prevent reuse
		$upd = $pdo->prepare('UPDATE email_otps SET is_used = 1 WHERE id = ?');
		$upd->execute([$otpRow['id']]);
		http_response_code(400);
		echo json_encode(['success' => false, 'message' => 'Code expired. Request a new OTP.']);
		exit;
	}

	// Compare
	if (hash_equals((string)$otpRow['otp_code'], $code)) {
		$pdo->beginTransaction();
		try {
			$use = $pdo->prepare('UPDATE email_otps SET is_used = 1 WHERE id = ?');
			$use->execute([$otpRow['id']]);

			// Set user verified if exists
			if (!empty($otpRow['user_id'])) {
				try {
					$verifyUser = $pdo->prepare('UPDATE users SET is_verified = 1 WHERE user_id = ?');
					$verifyUser->execute([(int)$otpRow['user_id']]);
				} catch (Throwable $e) {
					// users table may not have is_verified; ignore
				}
			}
			$pdo->commit();
			echo json_encode(['success' => true, 'message' => 'OTP verified successfully']);
			return;
		} catch (Throwable $e) {
			$pdo->rollBack();
			throw $e;
		}
	}

	// Wrong code: increment attempts
	$inc = $pdo->prepare('UPDATE email_otps SET attempts = attempts + 1 WHERE id = ?');
	$inc->execute([$otpRow['id']]);
	http_response_code(400);
	echo json_encode(['success' => false, 'message' => 'Invalid code. Please try again.']);
} catch (Throwable $e) {
	http_response_code(500);
	echo json_encode(['success' => false, 'message' => 'Server error']);
}


