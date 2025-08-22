<?php
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, x-api-key');

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
	$to = trim((string)($body['to'] ?? ''));
	$otp = trim((string)($body['otp'] ?? ''));
	$subject = trim((string)($body['subject'] ?? 'Your PetVet Verification Code'));
	$text = (string)($body['text'] ?? ("Your One-Time Password (OTP) for PetVet is: $otp\r\n\r\nThis code will expire in 10 minutes."));
	$html = (string)($body['html'] ?? ("<p>Your One-Time Password (OTP) for PetVet is: <strong>$otp</strong></p><p>This code will expire in 10 minutes.</p>"));

	if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
		http_response_code(400);
		echo json_encode(['success' => false, 'message' => 'Valid recipient email is required']);
		exit;
	}
	if ($otp === '') {
		http_response_code(400);
		echo json_encode(['success' => false, 'message' => 'OTP value is required']);
		exit;
	}

	// Load mailer config
	$configPath = __DIR__ . '/../auth_system/mailer_config.php';
	if (file_exists($configPath)) { require_once $configPath; }

	// Attempt via PHPMailer SMTP first if configured
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

	$phpMailerError = null;
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
			echo json_encode(['success' => true, 'message' => 'OTP sent via SMTP']);
			return;
		} catch (Throwable $e) {
			error_log('Custom API SMTP send error: ' . $e->getMessage());
			if (isset($mail)) { $phpMailerError = $mail->ErrorInfo; error_log('Custom API PHPMailer ErrorInfo: ' . $mail->ErrorInfo); }
			// continue to mail() fallback
		}
	}

	// Fallback to PHP mail()
	$headers = 'From: no-reply@petvet.local' . "\r\n" .
		'MIME-Version: 1.0' . "\r\n" .
		'Content-Type: text/plain; charset=UTF-8';
	$sent = @mail($to, $subject, $text, $headers);
	if ($sent) {
		echo json_encode(['success' => true, 'message' => 'OTP sent via mail()']);
	} else {
		http_response_code(500);
		$response = ['success' => false, 'message' => 'Failed to send OTP via mail()'];
		if ($phpMailerError) { $response['smtp_error'] = $phpMailerError; }
		echo json_encode($response);
	}
} catch (Throwable $e) {
	http_response_code(500);
	echo json_encode(['success' => false, 'message' => 'Server error', 'error' => $e->getMessage()]);
}


