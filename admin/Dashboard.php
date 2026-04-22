<?php

namespace Admin;

use Core\Auth;
use Core\Database;
use Core\Cache;
use Core\Middleware;

class Dashboard {

    public function __construct() {
        Middleware::requireAdmin();
    }

    public function index(): void {
        $db = Database::getInstance();

        // Real-time stats (cached for 30s)
        $stats = Cache::remember('acp_dashboard_stats', 30, function() use ($db) {
            $totalUsers   = $db->fetch("SELECT COUNT(*) as c FROM users")['c'] ?? 0;
            $totalThreads = $db->fetch("SELECT COUNT(*) as c FROM threads")['c'] ?? 0;
            $totalPosts   = $db->fetch("SELECT COUNT(*) as c FROM posts")['c'] ?? 0;
            $activeToday  = $db->fetch(
                "SELECT COUNT(*) as c FROM users WHERE last_seen_at > NOW() - INTERVAL 1 DAY"
            )['c'] ?? 0;
            $postsHour    = $db->fetch(
                "SELECT COUNT(*) as c FROM posts WHERE created_at > NOW() - INTERVAL 1 HOUR"
            )['c'] ?? 0;
            $newToday     = $db->fetch(
                "SELECT COUNT(*) as c FROM users WHERE created_at > NOW() - INTERVAL 1 DAY"
            )['c'] ?? 0;

            return compact('totalUsers','totalThreads','totalPosts','activeToday','postsHour','newToday');
        });

        // Recent activity
        $recentPosts = $db->fetchAll(
            "SELECT p.id, p.content, p.created_at, u.username, u.avatar,
                    t.title as thread_title, t.slug as thread_slug
             FROM posts p
             JOIN users u ON p.user_id = u.id
             JOIN threads t ON p.thread_id = t.id
             ORDER BY p.created_at DESC LIMIT 10"
        );

        // Recent registrations
        $recentUsers = $db->fetchAll(
            "SELECT id, username, email, trust_level, reputation, created_at
             FROM users ORDER BY created_at DESC LIMIT 8"
        );

        // Daily post activity (last 7 days)
        $chartData = $db->fetchAll(
            "SELECT DATE(created_at) as day, COUNT(*) as posts
             FROM posts
             WHERE created_at >= NOW() - INTERVAL 7 DAY
             GROUP BY DATE(created_at)
             ORDER BY day ASC"
        );

        // System health
        $health = [
            'php_version'  => phpversion(),
            'db_size'      => $this->getDbSize($db),
            'cache_driver' => function_exists('apcu_fetch') ? 'APCu' : 'File',
            'php_memory'   => ini_get('memory_limit'),
            'upload_max'   => ini_get('upload_max_filesize'),
        ];

        require ROOT_PATH . '/admin/views/dashboard.php';
    }

    private function getDbSize(Database $db): string {
        $row = $db->fetch(
            "SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size
             FROM information_schema.tables
             WHERE table_schema = DATABASE()"
        );
        return ($row['size'] ?? 0) . ' MB';
    }

    /**
     * Quick action: clear all caches (AJAX).
     */
    public function clearCache(): void {
        Middleware::verifyCSRF();
        Cache::flush();
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'All caches cleared.']);
    }

    /**
     * Quick action: pin/unpin thread (AJAX).
     */
    public function pinThread(): void {
        Middleware::verifyCSRF();
        $threadId = (int)($_POST['thread_id'] ?? 0);
        $pin      = (int)($_POST['pin'] ?? 1);
        $db = Database::getInstance();
        $db->query("UPDATE threads SET is_pinned = :p WHERE id = :id", ['p' => $pin, 'id' => $threadId]);
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    }
}
