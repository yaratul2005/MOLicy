<?php

namespace Admin;

use Core\Middleware;
use Core\Database;
use Core\Cache;

class Analytics {

    public function __construct() {
        Middleware::requireAdmin();
    }

    public function index(): void {
        $db = Database::getInstance();

        // 30-day post activity
        $postChart = $db->fetchAll(
            "SELECT DATE(created_at) as day, COUNT(*) as count
             FROM posts WHERE created_at >= NOW() - INTERVAL 30 DAY
             GROUP BY DATE(created_at) ORDER BY day ASC"
        );

        // 30-day registrations
        $regChart = $db->fetchAll(
            "SELECT DATE(created_at) as day, COUNT(*) as count
             FROM users WHERE created_at >= NOW() - INTERVAL 30 DAY
             GROUP BY DATE(created_at) ORDER BY day ASC"
        );

        // Top categories by thread count
        $topCategories = $db->fetchAll(
            "SELECT c.name, c.slug, COUNT(t.id) as thread_count, SUM(t.views) as total_views
             FROM categories c LEFT JOIN threads t ON t.category_id = c.id
             GROUP BY c.id ORDER BY thread_count DESC LIMIT 10"
        );

        // Top threads by views
        $topThreads = $db->fetchAll(
            "SELECT t.title, t.slug, t.views, t.reply_count, u.username, t.created_at
             FROM threads t JOIN users u ON t.user_id = u.id
             ORDER BY t.views DESC LIMIT 10"
        );

        // Top posters
        $topPosters = $db->fetchAll(
            "SELECT u.username, u.avatar, u.reputation,
                    COUNT(p.id) as post_count_30d
             FROM posts p JOIN users u ON p.user_id = u.id
             WHERE p.created_at >= NOW() - INTERVAL 30 DAY
             GROUP BY u.id ORDER BY post_count_30d DESC LIMIT 10"
        );

        // Summary totals
        $totals = $db->fetch(
            "SELECT (SELECT COUNT(*) FROM users) as users,
                    (SELECT COUNT(*) FROM threads) as threads,
                    (SELECT COUNT(*) FROM posts) as posts,
                    (SELECT COUNT(*) FROM users WHERE created_at >= NOW() - INTERVAL 7 DAY) as new_users_week,
                    (SELECT COUNT(*) FROM posts WHERE created_at >= NOW() - INTERVAL 7 DAY) as new_posts_week,
                    (SELECT COUNT(*) FROM threads WHERE created_at >= NOW() - INTERVAL 7 DAY) as new_threads_week"
        );

        require ROOT_PATH . '/admin/views/analytics.php';
    }
}
