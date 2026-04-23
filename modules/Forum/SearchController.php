<?php

namespace Modules\Forum;

use Core\Database;
use Core\Auth;
use Core\Settings;

class SearchController {
    public function index(): void {
        $q       = trim($_GET['q'] ?? '');
        $type    = $_GET['type'] ?? 'threads'; // threads | posts | users
        $results = [];
        $db      = Database::getInstance();

        if (strlen($q) >= 2) {
            $like = '%' . $q . '%';
            if ($type === 'threads') {
                $results = $db->fetchAll(
                    "SELECT t.title, t.slug, t.views, t.reply_count, t.created_at,
                            u.username, c.name as category_name, c.slug as category_slug
                     FROM threads t
                     JOIN users u ON t.user_id = u.id
                     JOIN categories c ON t.category_id = c.id
                     WHERE t.title LIKE :q OR MATCH(t.title) AGAINST (:raw IN BOOLEAN MODE)
                     ORDER BY t.last_post_at DESC
                     LIMIT 30",
                    ['q' => $like, 'raw' => $q . '*']
                );
            } elseif ($type === 'posts') {
                $results = $db->fetchAll(
                    "SELECT p.id, p.content, p.created_at,
                            u.username, t.title as thread_title, t.slug as thread_slug
                     FROM posts p
                     JOIN users u ON p.user_id = u.id
                     JOIN threads t ON p.thread_id = t.id
                     WHERE p.content LIKE :q
                     ORDER BY p.created_at DESC
                     LIMIT 30",
                    ['q' => $like]
                );
            } elseif ($type === 'users') {
                $results = $db->fetchAll(
                    "SELECT id, username, avatar, trust_level, reputation, post_count, created_at
                     FROM users WHERE username LIKE :q
                     ORDER BY reputation DESC LIMIT 30",
                    ['q' => $like]
                );
            }
        }

        $theme = Settings::theme();
        require ROOT_PATH . "/themes/{$theme}/pages/search.php";
    }
}
