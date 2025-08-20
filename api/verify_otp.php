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
	$otp = trim((string)($body['otp'] ?? ''));

	if ($email === '' || $otp === '') {
		http_response_code(400);
		echo json_encode(['success' => false, 'message' => 'Email and OTP are required']);
		exit;
	}

	$stmt = $pdo->prepare('SELECT id, user_id, otp_code, is_used, attempts, expires_at FROM email_otps WHERE email = ? ORDER BY id DESC LIMIT 1');
	$stmt->execute([$email]);
	$otpRow = $stmt->fetch(PDO::FETCH_ASSOC);

	if (!$otpRow) {
		http_response_code(404);
		echo json_encode(['success' => false, 'message' => 'No OTP found for this email']);
		exit;
	}

	$now = new DateTime();
	$expiresAt = $otpRow['expires_at'] ? new DateTime($otpRow['expires_at']) : null;

	if ((int)$otpRow['is_used'] === 1) {
		echo json_encode(['success' => false, 'message' => 'OTP already used']);
		exit;
	}

	if ($expiresAt && $now > $expiresAt) {
		echo json_encode(['success' => false, 'message' => 'OTP expired']);
		exit;
	}

	if ($otpRow['otp_code'] !== $otp) {
		$upd = $pdo->prepare('UPDATE email_otps SET attempts = attempts + 1 WHERE id = ?');
		$upd->execute([$otpRow['id']]);
		echo json_encode(['success' => false, 'message' => 'Invalid OTP']);
		exit;
	}

	$pdo->beginTransaction();
	try {
		$upd = $pdo->prepare('UPDATE email_otps SET is_used = 1 WHERE id = ?');
		$upd->execute([$otpRow['id']]);
		try {
			$u = $pdo->prepare('UPDATE users SET is_verified = 1 WHERE user_id = ?');
			$u->execute([(int)$otpRow['user_id']]);
		} catch (PDOException $e) { }
		$pdo->commit();
		echo json_encode(['success' => true, 'message' => 'Email verified successfully']);
	} catch (Throwable $e) {
		$pdo->rollBack();
		http_response_code(500);
		echo json_encode(['success' => false, 'message' => 'Verification failed']);
	}
} catch (Throwable $e) {
	http_response_code(500);
	echo json_encode(['success' => false, 'message' => 'Server error']);
}


