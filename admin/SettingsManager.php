<?php

namespace Admin;

use Core\Middleware;
use Core\Settings;
use Core\Database;

class SettingsManager {

    public function __construct() {
        Middleware::requireAdmin();
    }

    public function index(): void {
        $settings = Settings::all();
        $auditLog = Database::getInstance()->fetchAll(
            "SELECT a.*, u.username FROM audit_log a LEFT JOIN users u ON a.admin_id = u.id ORDER BY a.created_at DESC LIMIT 30"
        );
        require ROOT_PATH . '/admin/views/settings.php';
    }

    public function save(): void {
        Middleware::verifyCSRF();

        $allowed = [
            'site_title', 'site_tagline', 'site_logo', 'site_favicon',
            'site_theme', 'site_language', 'site_timezone',
            'registration_enabled', 'require_email_verify', 'allow_guest_view',
            'posts_per_page', 'threads_per_page',
            'max_upload_size', 'allowed_file_types',
            'maintenance_mode', 'maintenance_message',
            'custom_css', 'custom_js', 'footer_text',
            'google_analytics',
            'forum_email', 'smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass', 'smtp_secure',
            'home_welcome_enabled', 'home_welcome_title', 'home_welcome_text',
        ];

        $data = [];
        foreach ($allowed as $key) {
            // Checkboxes default to 0 if not present in POST
            if (in_array($key, ['registration_enabled','require_email_verify','allow_guest_view','maintenance_mode','noindex_search','home_welcome_enabled'])) {
                $data[$key] = isset($_POST[$key]) ? '1' : '0';
            } else {
                $data[$key] = $_POST[$key] ?? '';
            }
        }

        Settings::saveAll($data);

        // Log action
        $this->log('settings_update', null, null, ['keys' => array_keys($data)]);

        header('Location: /admin/settings?saved=1');
        exit;
    }

    private function log(string $action, ?string $type, ?int $targetId, array $details = []): void {
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
