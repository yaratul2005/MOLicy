<?php

namespace Modules\Forum;

use Core\Database;
use Core\Auth;
use Core\Middleware;

class ThreadController {
    public function show($slug) {
        $db = Database::getInstance();

        $thread = $db->fetch(
            "SELECT t.*, u.username, u.avatar, c.name as category_name, c.slug as category_slug
             FROM threads t
             JOIN users u ON t.user_id = u.id
             JOIN categories c ON t.category_id = c.id
             WHERE t.slug = :slug",
            ['slug' => $slug]
        );
        if (!$thread) {
            http_response_code(404);
            include ROOT_PATH . '/themes/antigravity/pages/404.php';
            return;
        }

        // Increment view count
        $db->query("UPDATE threads SET views = views + 1 WHERE id = :id", ['id' => $thread['id']]);

        // Fetch posts with user data
        $posts = $db->fetchAll(
            "SELECT p.*, u.username, u.avatar, u.reputation, u.trust_level, u.post_count
             FROM posts p
             JOIN users u ON p.user_id = u.id
             WHERE p.thread_id = :thread_id
             ORDER BY p.created_at ASC",
            ['thread_id' => $thread['id']]
        );

        $theme = 'antigravity';
        require ROOT_PATH . "/themes/{$theme}/pages/thread.php";
    }

    public function create() {
        Middleware::requireAuth();

        $db = Database::getInstance();
        $categories = $db->fetchAll("SELECT * FROM categories ORDER BY position ASC");

        $theme = 'antigravity';
        require ROOT_PATH . "/themes/{$theme}/pages/thread-create.php";
    }

    public function store() {
        Middleware::requireAuth();
        Middleware::verifyCSRF();
        Middleware::rateLimit('thread_create', 5, 300); // 5 threads per 5 min

        $title       = trim($_POST['title'] ?? '');
        $content     = trim($_POST['content'] ?? '');
        $category_id = (int)($_POST['category_id'] ?? 0);
        $user        = Auth::user();

        if (strlen($title) < 5 || strlen($title) > 200) {
            die("Title must be between 5 and 200 characters.");
        }
        if (strlen($content) < 10) {
            die("Content must be at least 10 characters.");
        }
        if ($category_id < 1) {
            die("A valid category is required.");
        }

        // Slug generation: lowercase, hyphens, no repeating hyphens
        $slugBase = preg_replace('/[^a-z0-9]+/', '-', strtolower($title));
        $slug     = trim($slugBase, '-') . '-' . substr(uniqid(), -6);

        $db = Database::getInstance();

        try {
            $pdo = $db->getPDO();
            $pdo->beginTransaction();

            $threadId = $db->insert('threads', [
                'category_id'  => $category_id,
                'user_id'      => $user['id'],
                'title'        => $title,
                'slug'         => $slug,
                'last_post_at' => date('Y-m-d H:i:s'),
            ]);

            $db->insert('posts', [
                'thread_id' => $threadId,
                'user_id'   => $user['id'],
                'content'   => $content,
            ]);

            // Update user post count
            $db->query("UPDATE users SET post_count = post_count + 1 WHERE id = :id", ['id' => $user['id']]);

            $pdo->commit();

            header("Location: /thread/{$slug}");
            exit;
        } catch (\Exception $e) {
            $db->getPDO()->rollBack();
            error_log("Thread creation error: " . $e->getMessage());
            die("An error occurred while creating the thread. Please try again.");
        }
    }

    public function reply($slug) {
        Middleware::requireAuth();
        Middleware::verifyCSRF();
        Middleware::rateLimit('reply_create', 10, 60); // 10 replies per minute

        $content = trim($_POST['content'] ?? '');
        $user    = Auth::user();

        if (strlen($content) < 2) {
            die("Reply content is required.");
        }

        $db = Database::getInstance();
        $thread = $db->fetch(
            "SELECT id, user_id, title, slug, is_locked FROM threads WHERE slug = :slug",
            ['slug' => $slug]
        );

        if (!$thread) {
            http_response_code(404);
            die("Thread not found.");
        }

        if ($thread['is_locked']) {
            http_response_code(403);
            die("This thread is locked.");
        }

        try {
            $pdo = $db->getPDO();
            $pdo->beginTransaction();

            $postId = $db->insert('posts', [
                'thread_id' => $thread['id'],
                'user_id'   => $user['id'],
                'content'   => $content,
            ]);

            // Update reply count and last_post_at on thread
            $db->query(
                "UPDATE threads SET reply_count = reply_count + 1, last_post_at = NOW() WHERE id = :id",
                ['id' => $thread['id']]
            );

            // Update user post count
            $db->query(
                "UPDATE users SET post_count = post_count + 1 WHERE id = :id",
                ['id' => $user['id']]
            );

            // Notify thread author (if not replying to own thread)
            if ($thread['user_id'] !== $user['id']) {
                $db->insert('notifications', [
                    'user_id'    => $thread['user_id'],
                    'type'       => 'new_reply',
                    'data'       => json_encode([
                        'thread_title'   => $thread['title'],
                        'thread_slug'    => $thread['slug'],
                        'post_id'        => $postId,
                        'replier_name'   => $user['username'],
                        'replier_avatar' => $user['avatar'] ?? null,
                        'preview'        => mb_substr(strip_tags($content), 0, 80),
                    ]),
                ]);
            }

            // Parse @mentions and notify
            preg_match_all('/@([a-zA-Z0-9_]{3,20})/', $content, $mentions);
            foreach (array_unique($mentions[1]) as $mentionedUsername) {
                $mentioned = $db->fetch(
                    "SELECT id FROM users WHERE username = :u",
                    ['u' => $mentionedUsername]
                );
                if ($mentioned && $mentioned['id'] !== $user['id']) {
                    $db->insert('notifications', [
                        'user_id' => $mentioned['id'],
                        'type'    => 'mention',
                        'data'    => json_encode([
                            'thread_title'  => $thread['title'],
                            'thread_slug'   => $thread['slug'],
                            'post_id'       => $postId,
                            'mentioner'     => $user['username'],
                            'preview'       => mb_substr(strip_tags($content), 0, 80),
                        ]),
                    ]);
                }
            }

            $pdo->commit();

            header("Location: /thread/{$slug}#post-{$postId}");
            exit;
        } catch (\Exception $e) {
            $db->getPDO()->rollBack();
            error_log("Reply creation error: " . $e->getMessage());
            die("An error occurred while posting your reply. Please try again.");
        }
    }
}
