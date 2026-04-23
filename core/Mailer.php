<?php

namespace Core;

class Mailer {
    /**
     * Send an email using SMTP settings from the database.
     * Falls back to PHP mail() if SMTP is not configured.
     */
    public static function send(string $to, string $subject, string $htmlBody, string $textBody = ''): bool {
        $host = Settings::get('smtp_host');
        $port = (int)Settings::get('smtp_port', '587');
        $user = Settings::get('smtp_user');
        $pass = Settings::get('smtp_pass');
        $secure = Settings::get('smtp_secure', 'tls'); // tls, ssl, or empty
        $fromEmail = Settings::get('forum_email', 'noreply@' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
        $fromName = Settings::siteTitle();

        if (empty($textBody)) {
            $textBody = strip_tags($htmlBody);
        }

        $boundary = md5(uniqid(time()));
        $headers  = "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <{$fromEmail}>\r\n";
        $headers .= "Reply-To: {$fromEmail}\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";

        $body  = "--{$boundary}\r\n";
        $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $body .= chunk_split(base64_encode($textBody)) . "\r\n";

        $body .= "--{$boundary}\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $body .= chunk_split(base64_encode($htmlBody)) . "\r\n";
        $body .= "--{$boundary}--\r\n";

        if (empty($host) || empty($user) || empty($pass)) {
            // Fallback to native mail()
            return mail($to, "=?UTF-8?B?" . base64_encode($subject) . "?=", $body, $headers);
        }

        // Custom SMTP implementation using fsockopen
        return self::sendSMTP($to, $subject, $body, $headers, $host, $port, $user, $pass, $secure, $fromEmail);
    }

    private static function sendSMTP($to, $subject, $body, $headers, $host, $port, $user, $pass, $secure, $from) {
        $timeout = 10;
        
        // Add subject and to to headers for SMTP payload
        $payload = "To: {$to}\r\n";
        $payload .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
        $payload .= $headers . "\r\n" . $body . "\r\n.\r\n";

        if ($secure === 'ssl') {
            $host = 'ssl://' . $host;
        }

        $socket = @fsockopen($host, $port, $errno, $errstr, $timeout);
        if (!$socket) {
            error_log("SMTP Error: Could not connect to $host:$port ($errstr)");
            return false;
        }

        stream_set_timeout($socket, $timeout);
        
        $log = [];
        $res = self::readSMTP($socket, $log); // 220

        self::writeSMTP($socket, "EHLO " . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "\r\n");
        $res = self::readSMTP($socket, $log); // 250

        if ($secure === 'tls') {
            self::writeSMTP($socket, "STARTTLS\r\n");
            self::readSMTP($socket, $log);
            stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            
            self::writeSMTP($socket, "EHLO " . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "\r\n");
            self::readSMTP($socket, $log);
        }

        self::writeSMTP($socket, "AUTH LOGIN\r\n");
        self::readSMTP($socket, $log);

        self::writeSMTP($socket, base64_encode($user) . "\r\n");
        self::readSMTP($socket, $log);

        self::writeSMTP($socket, base64_encode($pass) . "\r\n");
        $authRes = self::readSMTP($socket, $log);

        if (strpos($authRes, '235') !== 0) {
            error_log("SMTP Auth Failed: " . $authRes);
            fclose($socket);
            return false;
        }

        self::writeSMTP($socket, "MAIL FROM:<{$from}>\r\n");
        self::readSMTP($socket, $log);

        self::writeSMTP($socket, "RCPT TO:<{$to}>\r\n");
        self::readSMTP($socket, $log);

        self::writeSMTP($socket, "DATA\r\n");
        self::readSMTP($socket, $log);

        self::writeSMTP($socket, $payload);
        $finalRes = self::readSMTP($socket, $log);

        self::writeSMTP($socket, "QUIT\r\n");
        fclose($socket);

        return strpos($finalRes, '250') === 0;
    }

    private static function readSMTP($socket, &$log) {
        $data = '';
        while ($str = fgets($socket, 515)) {
            $data .= $str;
            if (substr($str, 3, 1) === ' ') {
                break;
            }
        }
        $log[] = "S: " . trim($data);
        return $data;
    }

    private static function writeSMTP($socket, $data) {
        fwrite($socket, $data);
    }
}
