<?php
// config/mail.php
/**
 * Mail & SMTP Configuration
 * Configure this file to use a real email account (e.g. Gmail, Outlook, Amazon SES, SendGrid, Mailtrap)
 */

if (!defined('MAIL_DRIVER')) {
    // Options: 'smtp' (send via SMTP server), 'mail' (PHP native mail()), 'log' (write to logs/mail.log)
    define('MAIL_DRIVER', getenv('MAIL_DRIVER') ?: 'smtp');
}

if (!defined('SMTP_HOST')) {
    // e.g. 'smtp.gmail.com', 'smtp.office365.com', 'sandbox.smtp.mailtrap.io'
    define('SMTP_HOST', getenv('SMTP_HOST') ?: 'smtp.gmail.com');
}

if (!defined('SMTP_PORT')) {
    // 587 for TLS, 465 for SSL, 2525 for Mailtrap
    define('SMTP_PORT', (int)(getenv('SMTP_PORT') ?: 587));
}

if (!defined('SMTP_USER')) {
    // Your full email address (e.g. 'your-account@gmail.com')
    define('SMTP_USER', getenv('SMTP_USER') ?: '');
}

if (!defined('SMTP_PASS')) {
    // Your email password or Google App Password (recommended for Gmail)
    define('SMTP_PASS', getenv('SMTP_PASS') ?: '');
}

if (!defined('SMTP_ENCRYPTION')) {
    // 'tls' or 'ssl'
    define('SMTP_ENCRYPTION', getenv('SMTP_ENCRYPTION') ?: 'tls');
}

if (!defined('MAIL_FROM_ADDRESS')) {
    define('MAIL_FROM_ADDRESS', getenv('MAIL_FROM_ADDRESS') ?: 'noreply@vmstracker.com');
}

if (!defined('MAIL_FROM_NAME')) {
    define('MAIL_FROM_NAME', getenv('MAIL_FROM_NAME') ?: 'VMS Tracker');
}

