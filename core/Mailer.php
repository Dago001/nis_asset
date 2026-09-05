<?php
/**
 * Minimal mail helper.
 *
 * Uses PHPMailer over SMTP when the library is installed AND SMTP settings
 * are configured; otherwise falls back to PHP mail(); otherwise no-ops.
 * Callers must not depend on delivery — the forgotten-password flow returns
 * the same response whether or not an email was actually sent.
 */
class Mailer {

    public static function send($to, $subject, $body) {
        $to = filter_var($to, FILTER_VALIDATE_EMAIL);
        if (!$to) {
            return false;
        }

        $fromEmail = self::setting('from_email', getenv('MAIL_FROM') ?: null);
        $fromName  = self::setting('app_name', getenv('MAIL_FROM_NAME') ?: 'NIS Asset Management');
        $smtpHost  = self::setting('smtp_host', getenv('MAIL_HOST') ?: null);

        // 1. PHPMailer + SMTP (preferred).
        if ($smtpHost && class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
            try {
                $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                $mail->isSMTP();
                $mail->Host       = $smtpHost;
                $mail->Port       = (int) self::setting('smtp_port', getenv('MAIL_PORT') ?: 587);
                $user             = self::setting('smtp_username', getenv('MAIL_USERNAME') ?: '');
                $mail->SMTPAuth   = $user !== '';
                $mail->Username   = $user;
                $mail->Password   = self::setting('smtp_password', getenv('MAIL_PASSWORD') ?: '');
                $enc              = strtolower((string) self::setting('smtp_encryption', getenv('MAIL_ENCRYPTION') ?: 'tls'));
                $mail->SMTPSecure = $enc === 'ssl'
                    ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
                    : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                $mail->setFrom($fromEmail ?: ('no-reply@' . self::hostname()), $fromName);
                $mail->addAddress($to);
                $mail->Subject = $subject;
                $mail->Body    = $body;
                $mail->send();
                return true;
            } catch (\Throwable $e) {
                error_log('Mailer SMTP error: ' . $e->getMessage());
                return false;
            }
        }

        // 2. PHP mail() fallback.
        if (function_exists('mail')) {
            $from = $fromEmail ?: ('no-reply@' . self::hostname());
            $headers = 'From: ' . self::encodeHeader($fromName) . ' <' . $from . ">\r\n"
                . "MIME-Version: 1.0\r\n"
                . "Content-Type: text/plain; charset=UTF-8\r\n";
            return @mail($to, $subject, $body, $headers);
        }

        error_log('Mailer: no transport available; email to ' . $to . ' not sent.');
        return false;
    }

    private static function setting($key, $default = null) {
        try {
            if (class_exists('SettingsModel')) {
                $m = new SettingsModel();
                $v = $m->getSetting($key, null);
                if ($v !== null && $v !== '') {
                    return $v;
                }
            }
        } catch (\Throwable $e) { /* ignore */ }
        return $default;
    }

    private static function hostname() {
        $h = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return preg_replace('/[^a-z0-9.\-]/i', '', $h) ?: 'localhost';
    }

    private static function encodeHeader($text) {
        return preg_match('/[^\x20-\x7E]/', $text)
            ? '=?UTF-8?B?' . base64_encode($text) . '?='
            : $text;
    }
}
