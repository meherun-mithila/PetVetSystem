<?php
// Minimal diagnostic script to troubleshoot OTP email sending
// Usage: http://localhost/PetVet/tools/test_otp_sender.php?email=user@example.com&mode=both

error_reporting(E_ALL);
ini_set('display_errors', '1');

header('Content-Type: text/html; charset=utf-8');

function mask_secret($value) {
	if (!is_string($value) || $value === '') { return ''; }
	$len = strlen($value);
	if ($len <= 6) { return str_repeat('*', $len); }
	return substr($value, 0, 4) . str_repeat('*', $len - 6) . substr($value, -2);
}

$root = realpath(__DIR__ . '/..');
$bootstrapPath = $root . DIRECTORY_SEPARATOR . 'auth_system' . DIRECTORY_SEPARATOR . 'bootstrap.php';
$mailerConfigPath = $root . DIRECTORY_SEPARATOR . 'auth_system' . DIRECTORY_SEPARATOR . 'mailer_config.php';

$email = isset($_GET['email']) ? trim((string)$_GET['email']) : '';
$mode = isset($_GET['mode']) ? trim((string)$_GET['mode']) : 'both'; // send, direct, both

$env = [
	'php_version' => PHP_VERSION,
	'curl_loaded' => extension_loaded('curl') ? 'yes' : 'no',
	'allow_url_fopen' => (filter_var(ini_get('allow_url_fopen'), FILTER_VALIDATE_BOOLEAN) ? 'yes' : 'no'),
	'bootstrap_exists' => file_exists($bootstrapPath) ? 'yes' : 'no',
	'config_exists' => file_exists($mailerConfigPath) ? 'yes' : 'no',
];

$conf = [
	'provider' => null,
	'custom_url' => null,
	'custom_key' => null,
	'custom_header' => null,
];

if (file_exists($mailerConfigPath)) {
	require_once $mailerConfigPath;
	$conf['provider'] = defined('MAIL_PROVIDER') ? MAIL_PROVIDER : '(not defined)';
	$conf['custom_url'] = defined('CUSTOM_MAIL_API_URL') ? CUSTOM_MAIL_API_URL : '';
	$conf['custom_key'] = defined('CUSTOM_MAIL_API_KEY') ? mask_secret(CUSTOM_MAIL_API_KEY) : '';
	$conf['custom_header'] = defined('CUSTOM_MAIL_AUTH_HEADER') ? CUSTOM_MAIL_AUTH_HEADER : '';
}

$results = [
	'network_probe' => null,
	'send_via_function' => null,
	'direct_call' => null,
];

// Attempt a quick reachability probe (DNS + TCP) if custom URL is set
if (!empty($conf['custom_url'])) {
	$parsed = @parse_url($conf['custom_url']);
	if (is_array($parsed) && isset($parsed['host'])) {
		$host = $parsed['host'];
		$port = isset($parsed['port']) ? (int)$parsed['port'] : (isset($parsed['scheme']) && strtolower($parsed['scheme']) === 'https' ? 443 : 80);
		$start = microtime(true);
		$fp = @fsockopen($host, $port, $errno, $errstr, 5);
		$elapsedMs = (int)((microtime(true) - $start) * 1000);
		if ($fp) {
			fclose($fp);
			$results['network_probe'] = "OK: $host:$port reachable in {$elapsedMs}ms";
		} else {
			$results['network_probe'] = "FAIL: $host:$port not reachable ({$errno}) {$errstr} in {$elapsedMs}ms";
		}
	}
}

$otp = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);

// Try the internal function
if ($email !== '' && in_array($mode, ['both', 'send'], true)) {
	if (file_exists($bootstrapPath)) {
		require_once $bootstrapPath; // defines sendOtpEmail
		$start = microtime(true);
		$ok = false;
		$err = null;
		try {
			$ok = sendOtpEmail($email, $otp);
		} catch (Throwable $e) {
			$err = $e->getMessage();
		}
		$elapsedMs = (int)((microtime(true) - $start) * 1000);
		$results['send_via_function'] = $ok ? ("OK in {$elapsedMs}ms") : ("FAIL in {$elapsedMs}ms" . ($err ? (' - ' . $err) : ''));
	}
}

// Try direct HTTP call to custom API (to see raw response)
if ($email !== '' && in_array($mode, ['both', 'direct'], true) && !empty($conf['custom_url']) && extension_loaded('curl')) {
	$payload = [
		'to' => $email,
		'otp' => $otp,
		'subject' => 'Test OTP from PetVet Diagnostic',
		'text' => 'Test OTP: ' . $otp,
		'html' => '<p>Test OTP: <strong>' . htmlspecialchars($otp, ENT_QUOTES, 'UTF-8') . '</strong></p>',
	];
	$headers = [ 'accept: application/json', 'content-type: application/json' ];
	if (defined('CUSTOM_MAIL_API_KEY') && CUSTOM_MAIL_API_KEY !== '') {
		$authHeaderName = defined('CUSTOM_MAIL_AUTH_HEADER') ? CUSTOM_MAIL_AUTH_HEADER : 'x-api-key';
		$headers[] = $authHeaderName . ': ' . CUSTOM_MAIL_API_KEY;
	}
	$ch = curl_init(CUSTOM_MAIL_API_URL);
	curl_setopt_array($ch, [
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_POST => true,
		CURLOPT_HTTPHEADER => $headers,
		CURLOPT_POSTFIELDS => json_encode($payload),
		CURLOPT_TIMEOUT => (defined('CUSTOM_MAIL_TIMEOUT') ? (int)CUSTOM_MAIL_TIMEOUT : 20),
	]);
	$resp = curl_exec($ch);
	$http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	$err = curl_error($ch);
	curl_close($ch);
	$results['direct_call'] = [
		'http_code' => $http,
		'curl_error' => $err,
		'body' => $resp,
	];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8" />
	<title>PetVet OTP Mail Diagnostic</title>
	<style>
		body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; margin: 24px; }
		code { background: #f4f4f4; padding: 2px 6px; border-radius: 4px; }
		pre { background: #f8fafc; padding: 12px; border-radius: 6px; overflow: auto; }
		.kv { margin-bottom: 8px; }
		.kv b { display: inline-block; width: 200px; }
		.section { margin-top: 24px; }
		form input[type=text] { width: 360px; padding: 6px 8px; }
		form select { padding: 6px 8px; }
		form button { padding: 8px 12px; }
	</style>
</head>
<body>
	<h1>PetVet OTP Mail Diagnostic</h1>

	<form method="get">
		<label>Email: <input type="text" name="email" value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>" /></label>
		<label>Mode:
			<select name="mode">
				<option value="both" <?php echo $mode==='both'?'selected':''; ?>>Both</option>
				<option value="send" <?php echo $mode==='send'?'selected':''; ?>>sendOtpEmail()</option>
				<option value="direct" <?php echo $mode==='direct'?'selected':''; ?>>Direct API</option>
			</select>
		</label>
		<button type="submit">Run</button>
	</form>

	<div class="section">
		<h2>Environment</h2>
		<div class="kv"><b>PHP Version</b> <code><?php echo htmlspecialchars($env['php_version']); ?></code></div>
		<div class="kv"><b>cURL Loaded</b> <code><?php echo htmlspecialchars($env['curl_loaded']); ?></code></div>
		<div class="kv"><b>allow_url_fopen</b> <code><?php echo htmlspecialchars($env['allow_url_fopen']); ?></code></div>
		<div class="kv"><b>bootstrap.php</b> <code><?php echo htmlspecialchars($env['bootstrap_exists']); ?></code> (<?php echo htmlspecialchars($bootstrapPath); ?>)</div>
		<div class="kv"><b>mailer_config.php</b> <code><?php echo htmlspecialchars($env['config_exists']); ?></code> (<?php echo htmlspecialchars($mailerConfigPath); ?>)</div>
	</div>

	<div class="section">
		<h2>Config</h2>
		<div class="kv"><b>MAIL_PROVIDER</b> <code><?php echo htmlspecialchars((string)$conf['provider']); ?></code></div>
		<div class="kv"><b>CUSTOM_MAIL_API_URL</b> <code><?php echo htmlspecialchars((string)$conf['custom_url']); ?></code></div>
		<div class="kv"><b>CUSTOM_MAIL_AUTH_HEADER</b> <code><?php echo htmlspecialchars((string)$conf['custom_header']); ?></code></div>
		<div class="kv"><b>CUSTOM_MAIL_API_KEY</b> <code><?php echo htmlspecialchars((string)$conf['custom_key']); ?></code></div>
	</div>

	<?php if ($results['network_probe'] !== null): ?>
	<div class="section">
		<h2>Network Probe</h2>
		<pre><?php echo htmlspecialchars($results['network_probe']); ?></pre>
	</div>
	<?php endif; ?>

	<?php if ($email !== ''): ?>
	<div class="section">
		<h2>Test Execution</h2>
		<div class="kv"><b>Test Email</b> <code><?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?></code></div>
		<div class="kv"><b>Generated OTP</b> <code><?php echo htmlspecialchars($otp, ENT_QUOTES, 'UTF-8'); ?></code></div>
		<?php if ($results['send_via_function'] !== null): ?>
		<div class="kv"><b>sendOtpEmail()</b> <code><?php echo htmlspecialchars($results['send_via_function']); ?></code></div>
		<?php endif; ?>
		<?php if (is_array($results['direct_call'])): ?>
		<div class="kv"><b>Direct API HTTP</b> <code><?php echo htmlspecialchars((string)$results['direct_call']['http_code']); ?></code></div>
		<div class="kv"><b>Direct API cURL Error</b> <code><?php echo htmlspecialchars((string)$results['direct_call']['curl_error']); ?></code></div>
		<div class="kv"><b>Direct API Body</b></div>
		<pre><?php echo htmlspecialchars((string)$results['direct_call']['body']); ?></pre>
		<?php endif; ?>
	</div>
	<?php endif; ?>

	<div class="section">
		<h2>Tips</h2>
		<ul>
			<li>Ensure CUSTOM_MAIL_API_URL is correct and reachable from this server.</li>
			<li>If your API expects different JSON keys, update sendOtpEmail() and this script accordingly.</li>
			<li>Check the PHP error log for lines beginning with "Custom mail API" or "SMTP".</li>
			<li>If HTTP code is 401/403, verify your API key and header name.</li>
		</ul>
	</div>

</body>
</html>


