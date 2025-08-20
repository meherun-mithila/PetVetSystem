<?php
// Copy PHPMailer library into auth_system/PHPMailer (or composer install) and then set:

// Enable SMTP sending via PHPMailer
define('SMTP_ENABLED', true);

// SMTP server configuration (Brevo SMTP as fallback)
define('SMTP_HOST', 'smtp-relay.brevo.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', '952bb2001@smtp-brevo.com');
define('SMTP_PASSWORD', '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');

// From details
define('SMTP_FROM_EMAIL', SMTP_USERNAME);
define('SMTP_FROM_NAME', 'PetVet');

// Encryption: 'tls' (587) or 'ssl' (465)
if (!defined('SMTP_SECURE')) { define('SMTP_SECURE', 'tls'); }

// Debug level: 0 = off, 2 = client/server messages (logs to PHP error_log)
if (!defined('SMTP_DEBUG')) { define('SMTP_DEBUG', 0); }

// Allow self-signed certs (not recommended). Set true only for testing.
if (!defined('SMTP_ALLOW_SELF_SIGNED')) { define('SMTP_ALLOW_SELF_SIGNED', false); }

// Provider selection: 'smtp' (PHPMailer), 'brevo' (HTTP API), or 'mailsso' (validation + send via SMTP/mail)
if (!defined('MAIL_PROVIDER')) { define('MAIL_PROVIDER', 'mailsso'); }

// Brevo (Sendinblue) API settings (used when MAIL_PROVIDER === 'brevo')
if (!defined('BREVO_API_KEY')) { define('BREVO_API_KEY', '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'); }
if (!defined('BREVO_FROM_EMAIL')) { define('BREVO_FROM_EMAIL', '952bb2001@smtp-brevo.com'); }
if (!defined('BREVO_FROM_NAME')) { define('BREVO_FROM_NAME', 'PetVet'); }

// mails.so validation API (optional)
if (!defined('MAILS_SO_API_KEY')) { define('MAILS_SO_API_KEY', '30f20e52-1803-4dd8-a0a9-91c1176f5232'); }
if (!defined('MAILS_SO_VALIDATE_ENABLED')) { define('MAILS_SO_VALIDATE_ENABLED', true); }




