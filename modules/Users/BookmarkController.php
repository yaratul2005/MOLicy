<?php

namespace Modules\Users;

use Core\Auth;
use Core\Database;
use Core\Middleware;
use Core\Settings;

class BookmarkController {

    public function toggle(): void {
        Middleware::requireAuth();
        Middleware::verifyCSRF();
        header('Content-Type: application/json');

        $threadId = (int)($_POST['thread_id'] ?? 0);
        $user     = Auth::user();
        $db       = Database::getInstance();

        if (!$threadId) { echo json_encode(['error' => 'Invalid thread']); return; }

        $exists = $db->fetch(
            "SELECT 1 FROM bookmarks WHERE user_id=:u AND thread_id=:t",
            ['u' => $user['id'], 't' => $threadId]
        );

        if ($exists) {
            $db->query("DELETE FROM bookmarks WHERE user_id=:u AND thread_id=:t", ['u' => $user['id'], 't' => $threadId]);
            echo json_encode(['bookmarked' => false]);
        } else {
            $db->insert('bookmarks', ['user_id' => $user['id'], 'thread_id' => $threadId]);
            echo json_encode(['bookmarked' => true]);
        }
    }

    public function index(): void {
        Middleware::requireAuth();
        $user  = Auth::user();
        $db    = Database::getInstance();

        $bookmarks = $db->fetchAll(
            "SELECT t.title, t.slug, t.reply_count, t.views, t.created_at,
                    u.username, c.name as category_name, b.created_at as bookmarked_at
             FROM bookmarks b
             JOIN threads t  ON b.thread_id  = t.id
             JOIN users u    ON t.user_id    = u.id
             JOIN categories c ON t.category_id = c.id
             WHERE b.user_id = :uid
             ORDER BY b.created_at DESC",
            ['uid' => $user['id']]
        );

        $theme = Settings::theme();
        require ROOT_PATH . "/themes/{$theme}/pages/bookmarks.php";
    }
}
