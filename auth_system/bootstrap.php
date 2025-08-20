<?php
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

// Reuse root DB config
require_once __DIR__ . '/../config.php';

// Ensure OTP table exists
try {
	$pdo->exec("CREATE TABLE IF NOT EXISTS `email_otps` (
		`id` INT(11) NOT NULL AUTO_INCREMENT,
		`user_id` INT(11) NOT NULL,
		`email` VARCHAR(255) NOT NULL,
		`otp_code` VARCHAR(10) NOT NULL,
		`is_used` TINYINT(1) DEFAULT 0,
		`attempts` INT DEFAULT 0,
		`created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
		`expires_at` TIMESTAMP NULL,
		PRIMARY KEY (`id`),
		KEY `email_idx` (`email`),
		KEY `user_idx` (`user_id`),
		KEY `otp_idx` (`otp_code`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
} catch (PDOException $e) {
	// Ignore table creation errors to avoid leaking details to users
}

// Ensure users.is_verified column exists for verification tracking
try {
	$pdo->exec("ALTER TABLE users ADD COLUMN is_verified TINYINT(1) DEFAULT 0");
} catch (PDOException $e) {
	// Ignore if column already exists
}

function sendOtpEmail($toEmail, $otpCode) {
	$subject = 'Your PetVet Verification Code';
	$textMessage = "Your One-Time Password (OTP) for PetVet is: $otpCode\r\n\r\n" .
		"This code will expire in 10 minutes. If you didn't request this, you can ignore this email.";
	$htmlMessage = "<p>Your One-Time Password (OTP) for PetVet is: <strong>$otpCode</strong></p>" .
		"<p>This code will expire in 10 minutes. If you didn't request this, you can ignore this email.</p>";

	try {
		// no-op
	} catch (Throwable $e) {
		// no-op
		// fall through to SMTP
	}

	// Optional: validate recipient email via mails.so before sending
	try {
		if (file_exists(__DIR__ . '/mailer_config.php')) {
			require_once __DIR__ . '/mailer_config.php';
			if (defined('MAILS_SO_VALIDATE_ENABLED') && MAILS_SO_VALIDATE_ENABLED && defined('MAILS_SO_API_KEY') && MAILS_SO_API_KEY !== '') {
				$validateUrl = 'https://api.mails.so/v1/validate?email=' . urlencode($toEmail);
				$opts = [
					'http' => [
						'header' => 'x-mails-api-key: ' . MAILS_SO_API_KEY,
						'method' => 'GET',
						'timeout' => 10,
					],
				];
				$ctx = stream_context_create($opts);
				$resp = @file_get_contents($validateUrl, false, $ctx);
				if ($resp !== false) {
					$data = json_decode($resp, true);
					if (is_array($data)) {
						// Accept if valid or unknown; block only explicitly invalid emails
						if (isset($data['result']) && strtolower((string)$data['result']) === 'invalid') {
							// Do not block sending; just log and continue to send
							error_log('mails.so validation: email marked invalid, proceeding to send anyway');
						}
					}
				}
			}
		}
	} catch (Throwable $e) {
		// ignore validation failure and continue
	}

	// Send via Brevo (Sendinblue) API if configured
	try {
		if (file_exists(__DIR__ . '/mailer_config.php')) {
			require_once __DIR__ . '/mailer_config.php';
			if (defined('MAIL_PROVIDER') && MAIL_PROVIDER === 'brevo' && defined('BREVO_API_KEY') && BREVO_API_KEY !== '') {
				$fromEmail = (defined('BREVO_FROM_EMAIL') && BREVO_FROM_EMAIL !== '') ? BREVO_FROM_EMAIL : (defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : 'no-reply@petvet.local');
				$fromName = defined('BREVO_FROM_NAME') ? BREVO_FROM_NAME : 'PetVet';
				$payload = [
					'sender' => [ 'email' => $fromEmail, 'name' => $fromName ],
					'to' => [[ 'email' => $toEmail ]],
					'subject' => $subject,
					'htmlContent' => $htmlMessage,
					'textContent' => $textMessage,
				];
				if (function_exists('curl_init')) {
					$ch = curl_init('https://api.brevo.com/v3/smtp/email');
					curl_setopt_array($ch, [
						CURLOPT_RETURNTRANSFER => true,
						CURLOPT_POST => true,
						CURLOPT_HTTPHEADER => [
							'api-key: ' . BREVO_API_KEY,
							'accept: application/json',
							'content-type: application/json',
						],
						CURLOPT_POSTFIELDS => json_encode($payload),
						CURLOPT_TIMEOUT => 20,
					]);
					$resp = curl_exec($ch);
					$http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
					$err = curl_error($ch);
					curl_close($ch);
					if ($err) { error_log('Brevo cURL error: ' . $err); return false; }
					if ($http >= 200 && $http < 300) { return true; }
					error_log('Brevo HTTP ' . $http . ' response: ' . $resp);
					return false;
				} else {
					error_log('cURL extension not enabled for Brevo API');
					return false;
				}
			}
		}
	} catch (Throwable $e) {
		error_log('Brevo send error: ' . $e->getMessage());
		// fall through to SMTP
	}

	// Try PHPMailer SMTP if available and configured
	try {
		// Detect PHPMailer in common locations
		$phpmailerCandidates = [
			__DIR__ . '/PHPMailer/src/PHPMailer.php',
			__DIR__ . '/PHPMailer-master/src/PHPMailer.php',
			__DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php',
		];
		$phpMailerPath = null;
		foreach ($phpmailerCandidates as $cand) {
			if (file_exists($cand)) { $phpMailerPath = $cand; break; }
		}

		$smtpConfigured = false;
		if (file_exists(__DIR__ . '/mailer_config.php')) {
			require_once __DIR__ . '/mailer_config.php';
			$smtpConfigured = defined('SMTP_ENABLED') && SMTP_ENABLED === true;
		}
		// If SMTP is configured but PHPMailer library is missing, fail early
		if ($smtpConfigured && !$phpMailerPath) {
			error_log('PHPMailer not found. Place PHPMailer in auth_system/PHPMailer or auth_system/PHPMailer-master, or install via Composer.');
			return false;
		}
		if ($smtpConfigured && $phpMailerPath) {
			$base = dirname($phpMailerPath);
			require_once $base . '/PHPMailer.php';
			require_once $base . '/SMTP.php';
			require_once $base . '/Exception.php';
			$mail = new PHPMailer\PHPMailer\PHPMailer(true);
			try {
				$mail->isSMTP();
				$mail->SMTPDebug = defined('SMTP_DEBUG') ? SMTP_DEBUG : 0;
				$mail->Host = SMTP_HOST;
				$mail->SMTPAuth = true;
				$mail->Username = SMTP_USERNAME;
				$mail->Password = SMTP_PASSWORD;
				if (defined('SMTP_SECURE') && SMTP_SECURE === 'ssl') {
					$mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
				} else {
					$mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
				}
				$mail->Port = SMTP_PORT;
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

				$mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
				$mail->addAddress($toEmail);
				$mail->isHTML(true);
				$mail->Subject = $subject;
				$mail->Body = $htmlMessage;
				$mail->AltBody = $textMessage;

				$mail->send();
				return true;
			} catch (Throwable $e) {
				error_log('SMTP send error: ' . $e->getMessage());
				if (isset($mail)) { error_log('PHPMailer ErrorInfo: ' . $mail->ErrorInfo); }
				return false;
			}
		}
	} catch (Throwable $e) {
		error_log('Mailer init error: ' . $e->getMessage());
	}

	// Final fallback to PHP mail() regardless of SMTP config
	$headers = 'From: no-reply@petvet.local' . "\r\n" .
		'MIME-Version: 1.0' . "\r\n" .
		'Content-Type: text/plain; charset=UTF-8';
	return @mail($toEmail, $subject, $textMessage, $headers);
}

?>


