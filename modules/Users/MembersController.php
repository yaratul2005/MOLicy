<?php

namespace Modules\Users;

use Core\Database;
use Core\Settings;

class MembersController {
    public function index(): void {
        $db    = Database::getInstance();
        $page  = max(1, (int)($_GET['page'] ?? 1));
        $limit = 24;
        $offset= ($page - 1) * $limit;
        $sort  = $_GET['sort'] ?? 'joined';

        $orderBy = match($sort) {
            'reputation' => 'u.reputation DESC',
            'posts'      => 'u.post_count DESC',
            'activity'   => 'u.last_seen_at DESC',
            default      => 'u.created_at DESC',
        };

        $q = trim($_GET['q'] ?? '');
        $where = $q ? "WHERE u.username LIKE :q" : "";
        $params = $q ? ['q' => '%' . $q . '%'] : [];

        $members = $db->fetchAll(
            "SELECT u.id, u.username, u.avatar, u.trust_level, u.reputation, u.post_count, u.created_at, u.last_seen_at, u.bio
             FROM users u {$where}
             ORDER BY {$orderBy}
             LIMIT {$limit} OFFSET {$offset}",
            $params
        );

        $total = $db->fetch("SELECT COUNT(*) as c FROM users u {$where}", $params)['c'] ?? 0;
        $totalPages = (int)ceil($total / $limit);

        $theme = Settings::theme();
        require ROOT_PATH . "/themes/{$theme}/pages/members.php";
    }
}
