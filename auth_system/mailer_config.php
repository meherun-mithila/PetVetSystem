<?php
// Copy PHPMailer library into auth_system/PHPMailer (or composer install) and then set:

// Enable SMTP sending via PHPMailer
define('SMTP_ENABLED', true);

// Gmail SMTP server configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 465);
define('SMTP_USERNAME', 'mrsbh461@gmail.com');
define('SMTP_PASSWORD', 'qnvozxbjipqcfprb');

// From details
define('SMTP_FROM_EMAIL', 'mrsbh461@gmail.com');
define('SMTP_FROM_NAME', 'PetVet - Caring Paws Veterinary Clinic');

// Encryption: SSL for port 465
define('SMTP_SECURE', 'ssl');

// Debug level: 0 = off, 2 = client/server messages (logs to PHP error_log)
define('SMTP_DEBUG', 0);

// Allow self-signed certs (not recommended for production)
define('SMTP_ALLOW_SELF_SIGNED', false);

// Provider selection: 'smtp' (PHPMailer), 'brevo' (HTTP API), 'custom' (your own HTTP API), or 'mailsso' (validation + send via SMTP/mail)
define('MAIL_PROVIDER', 'smtp');

// Brevo (Sendinblue) API settings (used when MAIL_PROVIDER === 'brevo')
define('BREVO_API_KEY', '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');
define('BREVO_FROM_EMAIL', '952bb2001@smtp-brevo.com');
define('BREVO_FROM_NAME', 'PetVet');

// mails.so validation API (optional)
define('MAILS_SO_API_KEY', '30f20e52-1803-4dd8-a0a9-91c1176f5232');
define('MAILS_SO_VALIDATE_ENABLED', true);





