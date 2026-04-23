<?php

namespace Modules\Forum;

use Core\Database;

class CategoryController {
    public function show($slug) {
        $db = Database::getInstance();

        $category = $db->fetch("SELECT * FROM categories WHERE slug = :slug", ['slug' => $slug]);
        if (!$category) {
            http_response_code(404);
            require ROOT_PATH . '/themes/antigravity/pages/404.php';
            return;
        }

        // Sort support
        $sort = $_GET['sort'] ?? 'latest';
        $orderBy = match($sort) {
            'hot'   => 't.reply_count DESC, t.views DESC',
            'views' => 't.views DESC',
            default => 't.is_pinned DESC, COALESCE(t.last_post_at, t.created_at) DESC',
        };

        // Cursor pagination
        $page   = max(1, (int)($_GET['page'] ?? 1));
        $limit  = 20;
        $offset = ($page - 1) * $limit;

        $threads = $db->fetchAll(
            "SELECT t.*, u.username
             FROM threads t
             JOIN users u ON t.user_id = u.id
             WHERE t.category_id = :cat_id
             ORDER BY {$orderBy}
             LIMIT {$limit} OFFSET {$offset}",
            ['cat_id' => $category['id']]
        );

        $theme = 'antigravity';
        require ROOT_PATH . "/themes/{$theme}/pages/category.php";
    }
}
