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
	$name = trim((string)($body['name'] ?? ''));

	if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
		http_response_code(400);
		echo json_encode(['success' => false, 'message' => 'Valid email is required']);
		exit;
	}

	// Optional simple rate limit: 1 OTP per 60 seconds per email
	$recentStmt = $pdo->prepare('SELECT created_at FROM email_otps WHERE email = ? ORDER BY id DESC LIMIT 1');
	$recentStmt->execute([$email]);
	$lastOtp = $recentStmt->fetch(PDO::FETCH_ASSOC);
	if ($lastOtp) {
		$last = strtotime($lastOtp['created_at']);
		if ($last !== false && (time() - $last) < 60) {
			http_response_code(429);
			echo json_encode(['success' => false, 'message' => 'Please wait before requesting another code']);
			exit;
		}
	}

	// Ensure user exists; create unverified user if not
	$pdo->beginTransaction();
	try {
		$uStmt = $pdo->prepare('SELECT user_id, is_verified FROM users WHERE email = ? LIMIT 1');
		$uStmt->execute([$email]);
		$user = $uStmt->fetch(PDO::FETCH_ASSOC);

		if (!$user) {
			$displayName = $name !== '' ? $name : explode('@', $email)[0];
			$placeholderPass = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);
			try {
				$ins = $pdo->prepare('INSERT INTO users (name, email, password, is_verified) VALUES (?, ?, ?, 0)');
				$ins->execute([$displayName, $email, $placeholderPass]);
			} catch (PDOException $e) {
				$ins = $pdo->prepare('INSERT INTO users (name, email, password) VALUES (?, ?, ?)');
				$ins->execute([$displayName, $email, $placeholderPass]);
			}
			$userId = (int)$pdo->lastInsertId();
		} else {
			$userId = (int)$user['user_id'];
		}

		$otp = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
		$expiresAt = (new DateTime('+10 minutes'))->format('Y-m-d H:i:s');
		$insOtp = $pdo->prepare('INSERT INTO email_otps (user_id, email, otp_code, expires_at, is_used, attempts) VALUES (?, ?, ?, ?, 0, 0)');
		$insOtp->execute([$userId, $email, $otp, $expiresAt]);

		$pdo->commit();
	} catch (Throwable $e) {
		$pdo->rollBack();
		throw $e;
	}

	$sent = sendOtpEmail($email, $otp);
	if (!$sent) {
		http_response_code(500);
		echo json_encode(['success' => false, 'message' => 'Failed to send OTP']);
		exit;
	}

	echo json_encode([
		'success' => true,
		'message' => 'OTP sent successfully',
		'expires_in_minutes' => 10,
	]);
} catch (Throwable $e) {
	http_response_code(500);
	echo json_encode(['success' => false, 'message' => 'Server error']);
}


