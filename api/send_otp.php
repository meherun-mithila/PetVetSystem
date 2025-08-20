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

function readRequestData(): array {
	$raw = file_get_contents('php://input');
	$data = json_decode($raw, true);
	if (is_array($data)) { return $data; }
	if (!empty($_POST)) { return $_POST; }
	if (!empty($_GET)) { return $_GET; }
	return [];
}

try {
	if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
		http_response_code(405);
		echo json_encode(['success' => false, 'message' => 'Method not allowed']);
		exit;
	}

	$body = readRequestData();
	$email = trim((string)($body['email'] ?? ($_SESSION['user_email'] ?? '')));

	if ($email === '') {
		http_response_code(400);
		echo json_encode(['success' => false, 'message' => 'Email is required']);
		exit;
	}

	// Ensure user exists
	$stmt = $pdo->prepare('SELECT user_id FROM users WHERE email = ? LIMIT 1');
	$stmt->execute([$email]);
	$user = $stmt->fetch(PDO::FETCH_ASSOC);
	if (!$user) {
		http_response_code(404);
		echo json_encode(['success' => false, 'message' => 'User not found']);
		exit;
	}

	$userId = (int)$user['user_id'];
	$otp = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
	$expiresAt = (new DateTime('+10 minutes'))->format('Y-m-d H:i:s');

	$insOtp = $pdo->prepare('INSERT INTO email_otps (user_id, email, otp_code, expires_at, is_used, attempts) VALUES (?, ?, ?, ?, 0, 0)');
	$insOtp->execute([$userId, $email, $otp, $expiresAt]);

	$sent = sendOtpEmail($email, $otp);
	if ($sent) {
		echo json_encode(['success' => true, 'message' => 'OTP sent successfully']);
	} else {
		http_response_code(500);
		echo json_encode(['success' => false, 'message' => 'Failed to send email. Check SMTP/mail configuration.']);
	}
} catch (Throwable $e) {
	http_response_code(500);
	echo json_encode(['success' => false, 'message' => 'Failed to send OTP']);
}


