<?php

namespace Admin;

use Core\Middleware;
use Core\Database;
use Core\Mailer;
use Core\Settings;

class EmailTools {

    public function __construct() {
        Middleware::requireAdmin();
    }

    /** Main view */
    public function index(): void {
        $db = Database::getInstance();

        // Unverified users awaiting email
        $unverified = $db->fetchAll(
            "SELECT id, username, email, created_at FROM users
             WHERE is_verified = 0 AND verification_token IS NOT NULL
             ORDER BY created_at DESC LIMIT 100"
        );

        // Recent email activity (last 50 audit entries related to email)
        $emailLogs = $db->fetchAll(
            "SELECT * FROM audit_log WHERE action LIKE 'email_%'
             ORDER BY created_at DESC LIMIT 50"
        ) ?: [];

        $smtpConfigured = !empty(Settings::get('smtp_host'))
                       && !empty(Settings::get('smtp_user'))
                       && !empty(Settings::get('smtp_pass'));

        $pageTitle  = 'Email Tools';
        $activeNav  = 'email-tools';
        require ROOT_PATH . '/admin/views/email-tools.php';
    }

    /** Send test email */
    public function sendTest(): void {
        Middleware::verifyCSRF();
        header('Content-Type: application/json');

        $to = trim($_POST['to'] ?? '');
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['error' => 'Invalid email address.']); return;
        }

        $siteTitle = Settings::siteTitle();
        $html = "
        <div style='font-family:sans-serif;padding:32px;background:#0f172a;color:#f1f5f9;border-radius:12px;max-width:500px;margin:auto'>
            <h2 style='color:#7c3aed;margin:0 0 12px'>✓ SMTP Test Successful</h2>
            <p style='color:#94a3b8;margin:0 0 8px'>This is a test email from <strong style='color:#f1f5f9'>{$siteTitle}</strong>.</p>
            <p style='color:#94a3b8;font-size:0.85rem'>If you received this, your SMTP settings are correctly configured.</p>
        </div>";

        $sent = Mailer::send($to, "SMTP Test — {$siteTitle}", $html, "SMTP Test: This is a test email from {$siteTitle}.");

        $this->auditLog('email_test_sent', null, null, ['to' => $to, 'result' => $sent]);
        echo json_encode(['success' => $sent, 'message' => $sent ? 'Test email sent successfully!' : 'Send failed — check server error log for SMTP details.']);
    }

    /** Resend verification to a specific user */
    public function resendVerification(): void {
        Middleware::verifyCSRF();
        header('Content-Type: application/json');

        $userId = (int)($_POST['user_id'] ?? 0);
        $db = Database::getInstance();
        $user = $db->fetch("SELECT * FROM users WHERE id = :id AND is_verified = 0", ['id' => $userId]);

        if (!$user) {
            echo json_encode(['error' => 'User not found or already verified.']); return;
        }

        $token = bin2hex(random_bytes(32));
        $db->query("UPDATE users SET verification_token = :t WHERE id = :id", ['t' => $token, 'id' => $userId]);

        $verifyUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/verify-email?token=' . $token;
        $siteTitle = Settings::siteTitle();

        $html = "
        <div style='font-family:sans-serif;padding:32px;background:#0f172a;color:#f1f5f9;max-width:520px;margin:auto'>
            <h2 style='color:#6366f1'>Verify Your Email — {$siteTitle}</h2>
            <p style='color:#94a3b8'>Hi <strong style='color:#f1f5f9'>{$user['username']}</strong>,</p>
            <p style='color:#94a3b8;margin-bottom:24px'>Please click the button below to verify your account.</p>
            <a href='{$verifyUrl}' style='display:inline-block;background:#6366f1;color:#fff;font-weight:700;padding:12px 28px;border-radius:8px;text-decoration:none'>Verify My Email</a>
            <p style='color:#475569;font-size:0.8rem;margin-top:24px'>Or: {$verifyUrl}</p>
        </div>";

        $sent = Mailer::send($user['email'], "Verify your email — {$siteTitle}", $html);
        $this->auditLog('email_resend_verify', 'user', $userId, ['email' => $user['email'], 'result' => $sent]);
        echo json_encode(['success' => $sent]);
    }

    /** Bulk resend to all unverified */
    public function bulkResend(): void {
        Middleware::verifyCSRF();
        header('Content-Type: application/json');

        $db = Database::getInstance();
        $users = $db->fetchAll("SELECT * FROM users WHERE is_verified = 0 AND verification_token IS NOT NULL LIMIT 50");

        $sent = 0; $failed = 0;
        $siteTitle = Settings::siteTitle();

        foreach ($users as $user) {
            $token = bin2hex(random_bytes(32));
            $db->query("UPDATE users SET verification_token = :t WHERE id = :id", ['t' => $token, 'id' => $user['id']]);
            $verifyUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/verify-email?token=' . $token;
            $html = "<p>Hi {$user['username']}, please verify: <a href='{$verifyUrl}'>{$verifyUrl}</a></p>";
            Mailer::send($user['email'], "Verify your email — {$siteTitle}", $html) ? $sent++ : $failed++;
            usleep(200000); // 200ms delay between sends to avoid throttling
        }

        $this->auditLog('email_bulk_resend', null, null, ['sent' => $sent, 'failed' => $failed]);
        echo json_encode(['success' => true, 'sent' => $sent, 'failed' => $failed]);
    }

    /** Manually verify a user (no email) */
    public function manualVerify(): void {
        Middleware::verifyCSRF();
        header('Content-Type: application/json');
        $userId = (int)($_POST['user_id'] ?? 0);
        $db = Database::getInstance();
        $db->query(
            "UPDATE users SET is_verified=1, verification_token=NULL, trust_level=GREATEST(trust_level,1) WHERE id=:id",
            ['id' => $userId]
        );
        $this->auditLog('email_manual_verify', 'user', $userId);
        echo json_encode(['success' => true]);
    }

    /** Send a custom broadcast to all (or filtered) users */
    public function broadcast(): void {
        Middleware::verifyCSRF();
        header('Content-Type: application/json');

        $subject = trim($_POST['subject'] ?? '');
        $body    = trim($_POST['body'] ?? '');
        $filter  = $_POST['filter'] ?? 'all'; // 'all', 'verified', 'unverified'

        if (empty($subject) || empty($body)) {
            echo json_encode(['error' => 'Subject and body are required.']); return;
        }

        $db = Database::getInstance();
        $sql = "SELECT email, username FROM users";
        if ($filter === 'verified')   $sql .= " WHERE is_verified = 1";
        if ($filter === 'unverified') $sql .= " WHERE is_verified = 0";
        $sql .= " LIMIT 200";
        $users = $db->fetchAll($sql);

        $sent = 0; $failed = 0;
        foreach ($users as $u) {
            $personalised = str_replace('{username}', $u['username'], $body);
            $html = nl2br(htmlspecialchars($personalised));
            Mailer::send($u['email'], $subject, $html) ? $sent++ : $failed++;
            usleep(100000);
        }

        $this->auditLog('email_broadcast', null, null, ['subject' => $subject, 'filter' => $filter, 'sent' => $sent]);
        echo json_encode(['success' => true, 'sent' => $sent, 'failed' => $failed]);
    }

    private function auditLog(string $action, ?string $type = null, ?int $targetId = null, array $details = []): void {
        $user = \Core\Auth::user();
        Database::getInstance()->insert('audit_log', [
            'admin_id'    => $user['id'] ?? null,
            'action'      => $action,
            'target_type' => $type,
            'target_id'   => $targetId,
            'details'     => json_encode($details),
            'ip'          => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    }
}
