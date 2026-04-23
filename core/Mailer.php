<?php

namespace Core;

/**
 * Mailer — robust SMTP client for AntiGravity Forum
 * Supports TLS (STARTTLS on port 587) and SSL (direct on port 465)
 */
class Mailer {

    /**
     * Send an email. Uses configured SMTP or falls back to PHP mail().
     * Logs errors to PHP error_log for server-side debugging.
     */
    public static function send(string $to, string $subject, string $htmlBody, string $textBody = ''): bool {
        $host      = Settings::get('smtp_host');
        $port      = (int)(Settings::get('smtp_port') ?: 587);
        $user      = Settings::get('smtp_user');
        $pass      = Settings::get('smtp_pass');
        $secure    = strtolower(trim(Settings::get('smtp_secure', 'tls'))); // 'tls', 'ssl', or ''
        $fromEmail = Settings::get('forum_email') ?: ('noreply@' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
        $fromName  = Settings::siteTitle();

        if (empty($textBody)) {
            $textBody = strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>', '</div>'], "\n", $htmlBody));
        }

        if (empty($host) || empty($user) || empty($pass)) {
            error_log("[Mailer] SMTP not configured — falling back to php mail()");
            return self::sendNativeMail($to, $subject, $htmlBody, $textBody, $fromEmail, $fromName);
        }

        $result = self::sendSMTP($to, $subject, $htmlBody, $textBody, $host, $port, $user, $pass, $secure, $fromEmail, $fromName);

        if (!$result) {
            error_log("[Mailer] SMTP failed — falling back to php mail()");
            return self::sendNativeMail($to, $subject, $htmlBody, $textBody, $fromEmail, $fromName);
        }

        return $result;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Native mail() fallback
    // ──────────────────────────────────────────────────────────────────────────
    private static function sendNativeMail(string $to, string $subject, string $html, string $text, string $from, string $fromName): bool {
        $boundary = md5(uniqid((string)time()));
        $headers  = "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <{$from}>\r\n";
        $headers .= "Reply-To: {$from}\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"";

        $body  = "--{$boundary}\r\n";
        $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $body .= chunk_split(base64_encode($text)) . "\r\n";
        $body .= "--{$boundary}\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $body .= chunk_split(base64_encode($html)) . "\r\n";
        $body .= "--{$boundary}--";

        $encodedSubject = "=?UTF-8?B?" . base64_encode($subject) . "?=";
        return @mail($to, $encodedSubject, $body, $headers);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Core SMTP sender
    // ──────────────────────────────────────────────────────────────────────────
    private static function sendSMTP(
        string $to, string $subject, string $html, string $text,
        string $host, int $port, string $user, string $pass, string $secure,
        string $from, string $fromName
    ): bool {
        $timeout = 15;

        // Choose connection scheme
        if ($secure === 'ssl') {
            $connHost = "ssl://{$host}";
        } else {
            $connHost = "tcp://{$host}";
        }

        $socket = @stream_socket_client("{$connHost}:{$port}", $errno, $errstr, $timeout);
        if (!$socket) {
            error_log("[Mailer SMTP] Cannot connect to {$connHost}:{$port} — {$errstr} ({$errno})");
            return false;
        }

        stream_set_timeout($socket, $timeout);

        // Read server greeting
        $greeting = self::smtpRead($socket);
        if (!self::smtpCodeIs($greeting, 220)) {
            error_log("[Mailer SMTP] Bad greeting: {$greeting}");
            fclose($socket);
            return false;
        }

        // EHLO
        $domain = $_SERVER['HTTP_HOST'] ?? 'localhost';
        self::smtpWrite($socket, "EHLO {$domain}\r\n");
        $ehlo = self::smtpRead($socket);

        // STARTTLS upgrade for port 587
        if ($secure === 'tls') {
            self::smtpWrite($socket, "STARTTLS\r\n");
            $tlsResp = self::smtpRead($socket);
            if (!self::smtpCodeIs($tlsResp, 220)) {
                error_log("[Mailer SMTP] STARTTLS failed: {$tlsResp}");
                fclose($socket);
                return false;
            }
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                error_log("[Mailer SMTP] TLS handshake failed");
                fclose($socket);
                return false;
            }
            // Re-EHLO after TLS
            self::smtpWrite($socket, "EHLO {$domain}\r\n");
            self::smtpRead($socket);
        }

        // AUTH LOGIN
        self::smtpWrite($socket, "AUTH LOGIN\r\n");
        self::smtpRead($socket); // 334 Username:

        self::smtpWrite($socket, base64_encode($user) . "\r\n");
        self::smtpRead($socket); // 334 Password:

        self::smtpWrite($socket, base64_encode($pass) . "\r\n");
        $authResp = self::smtpRead($socket);

        if (!self::smtpCodeIs($authResp, 235)) {
            error_log("[Mailer SMTP] Auth failed: {$authResp}");
            fclose($socket);
            return false;
        }

        // MAIL FROM
        self::smtpWrite($socket, "MAIL FROM:<{$from}>\r\n");
        $mfResp = self::smtpRead($socket);
        if (!self::smtpCodeIs($mfResp, 250)) {
            error_log("[Mailer SMTP] MAIL FROM rejected: {$mfResp}");
            fclose($socket);
            return false;
        }

        // RCPT TO
        self::smtpWrite($socket, "RCPT TO:<{$to}>\r\n");
        $rtResp = self::smtpRead($socket);
        if (!self::smtpCodeIs($rtResp, 250) && !self::smtpCodeIs($rtResp, 251)) {
            error_log("[Mailer SMTP] RCPT TO rejected: {$rtResp}");
            fclose($socket);
            return false;
        }

        // DATA
        self::smtpWrite($socket, "DATA\r\n");
        $dataPrompt = self::smtpRead($socket);
        if (!self::smtpCodeIs($dataPrompt, 354)) {
            error_log("[Mailer SMTP] DATA command rejected: {$dataPrompt}");
            fclose($socket);
            return false;
        }

        // Build complete RFC 2822 message
        $boundary = md5(uniqid((string)time()));
        $message  = "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <{$from}>\r\n";
        $message .= "To: {$to}\r\n";
        $message .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
        $message .= "MIME-Version: 1.0\r\n";
        $message .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";
        $message .= "X-Mailer: AntiGravity-Forum/1.0\r\n";
        $message .= "\r\n"; // blank line separating headers from body
        $message .= "--{$boundary}\r\n";
        $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $message .= chunk_split(base64_encode($text)) . "\r\n";
        $message .= "--{$boundary}\r\n";
        $message .= "Content-Type: text/html; charset=UTF-8\r\n";
        $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $message .= chunk_split(base64_encode($html)) . "\r\n";
        $message .= "--{$boundary}--\r\n";
        $message .= "\r\n.\r\n"; // end of DATA

        self::smtpWrite($socket, $message);
        $sendResp = self::smtpRead($socket);

        self::smtpWrite($socket, "QUIT\r\n");
        fclose($socket);

        if (!self::smtpCodeIs($sendResp, 250)) {
            error_log("[Mailer SMTP] Send rejected: {$sendResp}");
            return false;
        }

        return true;
    }

    private static function smtpRead($socket): string {
        $data = '';
        while (!feof($socket)) {
            $line = fgets($socket, 1024);
            if ($line === false) break;
            $data .= $line;
            // Multi-line response ends when 4th char is a space
            if (strlen($line) >= 4 && $line[3] === ' ') {
                break;
            }
        }
        return $data;
    }

    private static function smtpWrite($socket, string $data): void {
        fwrite($socket, $data);
    }

    private static function smtpCodeIs(string $response, int $code): bool {
        return str_starts_with(trim($response), (string)$code);
    }
}
