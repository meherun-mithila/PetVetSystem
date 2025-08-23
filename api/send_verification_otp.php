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

function sendViaBrevo(string $to, string $subject, string $text, string $html): bool {
	$configPath = __DIR__ . '/../auth_system/mailer_config.php';
	if (file_exists($configPath)) { require_once $configPath; }
	if (!defined('BREVO_API_KEY') || BREVO_API_KEY === '') { return false; }
	$fromEmail = defined('BREVO_FROM_EMAIL') ? BREVO_FROM_EMAIL : 'no-reply@petvet.local';
	$fromName = defined('BREVO_FROM_NAME') ? BREVO_FROM_NAME : 'PetVet';
	$payload = [
		'sender' => [ 'email' => $fromEmail, 'name' => $fromName ],
		'to' => [ [ 'email' => $to ] ],
		'subject' => $subject,
		'htmlContent' => $html,
		'textContent' => $text,
	];
	$ch = curl_init('https://api.brevo.com/v3/smtp/email');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, [
		'Content-Type: application/json',
		'api-key: ' . BREVO_API_KEY,
	]);
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
	curl_setopt($ch, CURLOPT_TIMEOUT, 20);
	$resp = curl_exec($ch);
	$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	if ($resp === false) {
		error_log('Brevo curl error: ' . curl_error($ch));
	}
	curl_close($ch);
	return $code >= 200 && $code < 300;
}

function sendOtpEmailRobust(string $to, string $otp): bool {
	$subject = 'Your PetVet Verification Code';
	$text = "Your One-Time Password (OTP) for PetVet is: $otp\r\n\r\nThis code will expire in 10 minutes.";
	$html = "<p>Your One-Time Password (OTP) for PetVet is: <strong>$otp</strong></p><p>This code will expire in 10 minutes.</p>";

	$configPath = __DIR__ . '/../auth_system/mailer_config.php';
	if (file_exists($configPath)) { require_once $configPath; }

	$smtpConfigured = defined('SMTP_ENABLED') && SMTP_ENABLED === true;
	$phpmailerCandidates = [
		__DIR__ . '/../auth_system/PHPMailer/src/PHPMailer.php',
		__DIR__ . '/../auth_system/PHPMailer-master/src/PHPMailer.php',
		__DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php',
	];
	$phpMailerPath = null;
	foreach ($phpmailerCandidates as $cand) {
		if (file_exists($cand)) { $phpMailerPath = $cand; break; }
	}

	// 1) Try SMTP via PHPMailer
	if ($smtpConfigured && $phpMailerPath) {
		$base = dirname($phpMailerPath);
		require_once $base . '/PHPMailer.php';
		require_once $base . '/SMTP.php';
		require_once $base . '/Exception.php';
		$mail = new PHPMailer\PHPMailer\PHPMailer(true);
		try {
			$mail->isSMTP();
			$mail->SMTPDebug = defined('SMTP_DEBUG') ? SMTP_DEBUG : 0;
			$mail->Host = defined('SMTP_HOST') ? SMTP_HOST : '';
			$cfgUsername = defined('SMTP_USERNAME') ? (string)SMTP_USERNAME : '';
			$cfgPassword = defined('SMTP_PASSWORD') ? (string)SMTP_PASSWORD : '';
			$mail->SMTPAuth = ($cfgUsername !== '' || $cfgPassword !== '');
			if ($mail->SMTPAuth) {
				$mail->Username = $cfgUsername;
				$mail->Password = $cfgPassword;
			}
			if (defined('SMTP_SECURE') && SMTP_SECURE === 'ssl') {
				$mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
			} elseif (defined('SMTP_SECURE') && (SMTP_SECURE === 'tls' || SMTP_SECURE === 'starttls')) {
				$mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
			}
			$mail->Port = defined('SMTP_PORT') ? SMTP_PORT : 587;
			$mail->SMTPAutoTLS = true;
			if ($mail->SMTPDebug > 0) { $mail->Debugoutput = 'error_log'; }
			if (defined('SMTP_ALLOW_SELF_SIGNED') && SMTP_ALLOW_SELF_SIGNED) {
				$mail->SMTPOptions = [
					'ssl' => [
						'verify_peer' => false,
						'verify_peer_name' => false,
						'allow_self_signed' => true,
					],
				];
			}

			$fromEmail = defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : (defined('BREVO_FROM_EMAIL') ? BREVO_FROM_EMAIL : 'no-reply@petvet.local');
			$fromName = defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : (defined('BREVO_FROM_NAME') ? BREVO_FROM_NAME : 'PetVet');
			$mail->setFrom($fromEmail, $fromName);
			$mail->addAddress($to);
			$mail->isHTML(true);
			$mail->Subject = $subject;
			$mail->Body = $html;
			$mail->AltBody = $text;

			$mail->send();
			return true;
		} catch (Throwable $e) {
			error_log('send_verification_otp SMTP error: ' . $e->getMessage());
			// continue to Brevo fallback
		}
	}

	// 2) Try Brevo HTTP API if configured
	if (sendViaBrevo($to, $subject, $text, $html)) {
		return true;
	}

	// 3) Fallback to PHP mail()
	$headers = 'From: no-reply@petvet.local' . "\r\n" .
		'MIME-Version: 1.0' . "\r\n" .
		'Content-Type: text/plain; charset=UTF-8';
	return @mail($to, $subject, $text, $headers);
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

	$sent = sendOtpEmailRobust($email, $otp);
	if (!$sent) {
		http_response_code(500);
		echo json_encode(['success' => false, 'message' => 'Failed to send email. Please check SMTP or Brevo configuration.']);
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



