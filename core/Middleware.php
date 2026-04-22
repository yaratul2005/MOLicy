<?php

namespace Core;

class Middleware {
    /**
     * Require authenticated user. Redirects to /login if not.
     */
    public static function requireAuth(): void {
        if (!Auth::check()) {
            header('Location: /login');
            exit;
        }
    }

    /**
     * Require admin/trust level. Sends 403 if insufficient.
     */
    public static function requireAdmin(): void {
        self::requireAuth();
        $user = Auth::user();
        if (!$user || $user['trust_level'] < 5) {
            http_response_code(403);
            die('Access Denied.');
        }
    }

    /**
     * Verify CSRF token for POST/PUT/DELETE requests.
     * Double-submit cookie pattern.
     */
    public static function verifyCSRF(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') return;
        $token = $_POST['_csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
            http_response_code(419);
            die('CSRF token mismatch.');
        }
    }

    /**
     * Generate a CSRF token for this session.
     */
    public static function getCSRFToken(): string {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Return an HTML hidden CSRF field.
     */
    public static function csrfField(): string {
        return '<input type="hidden" name="_csrf_token" value="' . self::getCSRFToken() . '">';
    }

    /**
     * Per-IP + per-user rate limiter (file-based).
     * @param string $action  Identifier for the action (e.g., 'post_create')
     * @param int    $limit   Max allowed hits
     * @param int    $window  Time window in seconds
     */
    public static function rateLimit(string $action, int $limit = 10, int $window = 60): void {
        $id = Auth::check() ? 'u' . $_SESSION['user_id'] : 'ip' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        $key = md5("rl_{$action}_{$id}");
        $file = ROOT_PATH . '/storage/rate/' . $key . '.json';

        if (!is_dir(dirname($file))) mkdir(dirname($file), 0755, true);

        $data = ['hits' => 0, 'window_start' => time()];
        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true);
        }

        if (time() - $data['window_start'] > $window) {
            $data = ['hits' => 0, 'window_start' => time()];
        }

        $data['hits']++;
        file_put_contents($file, json_encode($data), LOCK_EX);

        if ($data['hits'] > $limit) {
            http_response_code(429);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Too many requests. Please slow down.']);
            exit;
        }
    }

    /**
     * Set security response headers.
     */
    public static function securityHeaders(): void {
        header('X-Frame-Options: SAMEORIGIN');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
    }
}
