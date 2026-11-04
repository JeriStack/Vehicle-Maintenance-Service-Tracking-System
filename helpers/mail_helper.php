<?php
// helpers/mail_helper.php
require_once __DIR__ . '/../config/app.php';

class MailHelper {

    /**
     * Send an email using configured driver (SMTP, mail, or log fallback)
     *
     * @param string $to Recipient email address
     * @param string $toName Recipient display name
     * @param string $subject Email subject line
     * @param string $htmlBody HTML content
     * @param string $plainText Optional plain text alternative
     * @return array ['success' => bool, 'mode' => string, 'message' => string]
     */
    public static function send($to, $toName, $subject, $htmlBody, $plainText = '') {
        $fromEmail = MAIL_FROM_ADDRESS ?: (SMTP_USER ?: 'noreply@vmstracker.com');
        $fromName  = MAIL_FROM_NAME ?: 'VMS Tracker';

        // 1. If SMTP is configured with host and username, attempt real SMTP delivery
        if (MAIL_DRIVER === 'smtp' && !empty(SMTP_HOST) && !empty(SMTP_USER) && !empty(SMTP_PASS)) {
            try {
                $result = self::sendSmtp($to, $toName, $fromEmail, $fromName, $subject, $htmlBody);
                if ($result === true) {
                    self::logMail("SENT via SMTP to {$to} [Subject: {$subject}]");
                    return ['success' => true, 'mode' => 'smtp', 'message' => 'Email sent successfully via SMTP.'];
                }
            } catch (\Throwable $e) {
                self::logMail("SMTP Failed ({$e->getMessage()}). Falling back to mail.log.");
            }
        }

        // 2. If PHP mail() is chosen
        if (MAIL_DRIVER === 'mail') {
            $headers  = "MIME-Version: 1.0\r\n";
            $headers .= "Content-type: text/html; charset=UTF-8\r\n";
            $headers .= "From: {$fromName} <{$fromEmail}>\r\n";
            $headers .= "Reply-To: {$fromEmail}\r\n";
            $headers .= "X-Mailer: PHP/" . phpversion();

            $sent = @mail($to, $subject, $htmlBody, $headers);
            if ($sent) {
                self::logMail("SENT via native mail() to {$to} [Subject: {$subject}]");
                return ['success' => true, 'mode' => 'mail', 'message' => 'Email sent via PHP mail().'];
            }
        }

        // 3. Fallback / Dev Mode: Log email to logs/mail.log so password resets can always be tested
        self::logMail("LOGGED (No active SMTP / dev fallback) to {$to} [Subject: {$subject}]\nBody snippet: " . substr(strip_tags($htmlBody), 0, 300) . "...");
        return [
            'success' => true,
            'mode'    => 'log',
            'message' => 'Email logged to logs/mail.log (configure config/mail.php for live SMTP).'
        ];
    }

    /**
     * Send a branded Password Reset Email
     */
    public static function sendPasswordReset($toEmail, $recipientName, $resetLink) {
        $appName = APP_NAME;
        $subject = "Password Reset Request - {$appName}";

        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Password Reset Request</title>
</head>
<body style="font-family: 'Inter', -apple-system, BlinkMacSystemFont, Arial, sans-serif; background-color: #f1f5f9; margin: 0; padding: 40px 15px;">
    <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 560px; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.06);">
        <tr>
            <td style="background: linear-gradient(135deg, #0ea5e9, #0284c7); padding: 32px 24px; text-align: center; color: #ffffff;">
                <h2 style="margin: 0; font-size: 24px; font-weight: 700; letter-spacing: 0.5px;">{$appName}</h2>
                <p style="margin: 6px 0 0 0; opacity: 0.9; font-size: 14px;">Vehicle Maintenance Service Tracking</p>
            </td>
        </tr>
        <tr>
            <td style="padding: 36px 32px; color: #334155;">
                <h3 style="margin-top: 0; color: #0f172a; font-size: 18px;">Hello, {$recipientName}!</h3>
                <p style="font-size: 15px; line-height: 1.6; color: #475569;">
                    We received a request to reset the password for your account associated with <strong>{$toEmail}</strong>.
                </p>
                <p style="font-size: 15px; line-height: 1.6; color: #475569;">
                    Click the button below to choose a new password. This link is secure and will expire in <strong>60 minutes</strong>.
                </p>
                <div style="text-align: center; margin: 32px 0;">
                    <a href="{$resetLink}" style="background: linear-gradient(135deg, #0ea5e9, #0284c7); color: #ffffff; padding: 14px 28px; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 15px; display: inline-block; box-shadow: 0 4px 12px rgba(14, 165, 233, 0.35);">
                        Reset Password
                    </a>
                </div>
                <p style="font-size: 13px; color: #64748b; line-height: 1.5;">
                    If the button above does not work, copy and paste this URL into your browser:<br>
                    <a href="{$resetLink}" style="color: #0284c7; word-break: break-all;">{$resetLink}</a>
                </p>
                <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 28px 0;">
                <p style="font-size: 12px; color: #94a3b8; margin-bottom: 0;">
                    If you did not request a password reset, you can safely ignore this email. Your password will remain unchanged.
                </p>
            </td>
        </tr>
        <tr>
            <td style="background-color: #f8fafc; padding: 16px 24px; text-align: center; font-size: 12px; color: #94a3b8; border-top: 1px solid #e2e8f0;">
                &copy; {$appName}. All rights reserved.
            </td>
        </tr>
    </table>
</body>
</html>
HTML;

        return self::send($toEmail, $recipientName, $subject, $html);
    }

    /**
     * Pure native PHP SMTP socket client implementation (RFC 5321)
     */
    private static function sendSmtp($to, $toName, $fromEmail, $fromName, $subject, $htmlBody) {
        $host = SMTP_HOST;
        $port = SMTP_PORT;
        $timeout = 10;
        $useSsl = (SMTP_ENCRYPTION === 'ssl' || $port === 465);

        $address = ($useSsl ? 'ssl://' : '') . $host . ':' . $port;

        $socket = @stream_socket_client($address, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT);
        if (!$socket) {
            throw new \Exception("Could not connect to SMTP server {$host}:{$port} - {$errstr} ({$errno})");
        }

        stream_set_timeout($socket, $timeout);
        self::expectResponse($socket, 220);

        // Send EHLO
        self::sendCommand($socket, "EHLO " . (gethostname() ?: 'localhost'), 250);

        // STARTTLS if TLS port
        if (!empty(SMTP_ENCRYPTION) && strtolower(SMTP_ENCRYPTION) === 'tls' && !$useSsl) {
            self::sendCommand($socket, "STARTTLS", 220);
            $crypto = @stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            if (!$crypto) {
                throw new \Exception("STARTTLS negotiation failed");
            }
            self::sendCommand($socket, "EHLO " . (gethostname() ?: 'localhost'), 250);
        }

        // Authenticate
        self::sendCommand($socket, "AUTH LOGIN", 334);
        self::sendCommand($socket, base64_encode(SMTP_USER), 334);
        self::sendCommand($socket, base64_encode(SMTP_PASS), 235);

        // MAIL FROM & RCPT TO
        self::sendCommand($socket, "MAIL FROM:<{$fromEmail}>", 250);
        self::sendCommand($socket, "RCPT TO:<{$to}>", 250);

        // DATA
        self::sendCommand($socket, "DATA", 354);

        $dateHeader = date('r');
        $msgId = '<' . md5(uniqid(microtime(), true)) . '@' . (gethostname() ?: 'localhost') . '>';

        $headers  = "Date: {$dateHeader}\r\n";
        $headers .= "Message-ID: {$msgId}\r\n";
        $headers .= "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <{$fromEmail}>\r\n";
        $headers .= "To: =?UTF-8?B?" . base64_encode($toName) . "?= <{$to}>\r\n";
        $headers .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "Content-Transfer-Encoding: base64\r\n";

        $body = chunk_split(base64_encode($htmlBody));

        $emailData = $headers . "\r\n" . $body . "\r\n.\r\n";

        @fwrite($socket, $emailData);
        self::expectResponse($socket, 250);

        // QUIT
        self::sendCommand($socket, "QUIT", 221);
        @fclose($socket);

        return true;
    }

    private static function sendCommand($socket, $cmd, $expectedCode) {
        @fwrite($socket, $cmd . "\r\n");
        return self::expectResponse($socket, $expectedCode);
    }

    private static function expectResponse($socket, $expectedCode) {
        $response = '';
        while ($line = @fgets($socket, 512)) {
            $response .= $line;
            // When character 4 is a space, multi-line response is finished (RFC 5321)
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }

        $code = (int)substr($response, 0, 3);
        if ($code !== (int)$expectedCode) {
            throw new \Exception("SMTP Error: Expected {$expectedCode}, received: " . trim($response));
        }

        return $response;
    }

    private static function logMail($message) {
        $logPath = __DIR__ . '/../logs/mail.log';
        $entry = "[" . date('Y-m-d H:i:s') . "] " . $message . PHP_EOL;
        @file_put_contents($logPath, $entry, FILE_APPEND);
    }
}

