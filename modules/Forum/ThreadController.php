<?php

namespace Modules\Forum;

use Core\Database;
use Core\Auth;
use Core\Middleware;
use Core\Settings;

class ThreadController {
    
    private function verifyRecaptcha(): bool {
        if (Settings::get('enable_recaptcha') !== '1') {
            return true;
        }

        $secret = Settings::get('recaptcha_secret_key');
        if (empty($secret)) {
            return true;
        }

        $response = $_POST['g-recaptcha-response'] ?? '';
        if (empty($response)) {
            return false;
        }

        // Use a timeout for the curl request to prevent hanging
        $verify = curl_init();
        curl_setopt($verify, CURLOPT_URL, "https://www.google.com/recaptcha/api/siteverify");
        curl_setopt($verify, CURLOPT_POST, true);
        curl_setopt($verify, CURLOPT_POSTFIELDS, http_build_query([
            'secret'   => $secret,
            'response' => $response,
            'remoteip' => $_SERVER['REMOTE_ADDR']
        ]));
        curl_setopt($verify, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($verify, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($verify, CURLOPT_TIMEOUT, 5); // 5 seconds timeout
        $result = curl_exec($verify);
        curl_close($verify);

        if (!$result) return false;
        $decoded = json_decode($result, true);
        return $decoded['success'] ?? false;
    }

    private function backWithError(string $message) {
        $_SESSION['error'] = $message;
        $_SESSION['old_input'] = $_POST;
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit;
    }

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
        $loadEditor = true;
        require ROOT_PATH . "/themes/{$theme}/pages/thread.php";
    }

    public function create() {
        Middleware::requireAuth();

        $db = Database::getInstance();
        $categories = $db->fetchAll("SELECT * FROM categories ORDER BY position ASC");

        $error = $_SESSION['error'] ?? null;
        $old   = $_SESSION['old_input'] ?? [];
        unset($_SESSION['error'], $_SESSION['old_input']);

        $theme = 'antigravity';
        $loadEditor = true;
        require ROOT_PATH . "/themes/{$theme}/pages/thread-create.php";
    }

    public function store() {
        Middleware::requireAuth();
        Middleware::verifyCSRF();
        
        // Rate limiting with better handling
        try {
            Middleware::rateLimit('thread_create', 5, 300); // 5 threads per 5 min
        } catch (\Exception $e) {
            $this->backWithError("You are posting too fast. Please wait a few minutes.");
        }

        $title       = trim($_POST['title'] ?? '');
        $content     = trim($_POST['content'] ?? '');
        $category_id = (int)($_POST['category_id'] ?? 0);
        $user        = Auth::user();

        if (!$this->verifyRecaptcha()) {
            $this->backWithError("Please complete the reCAPTCHA verification.");
        }

        if (strlen($title) < 5 || strlen($title) > 200) {
            $this->backWithError("Title must be between 5 and 200 characters.");
        }
        if (strlen($content) < 10) {
            $this->backWithError("Content must be at least 10 characters.");
        }
        if ($category_id < 1) {
            $this->backWithError("A valid category is required.");
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
                'reply_count'  => 0
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
            if ($db->getPDO()->inTransaction()) {
                $db->getPDO()->rollBack();
            }
            error_log("Thread creation error: " . $e->getMessage());
            $this->backWithError("An error occurred while creating the thread. Please try again.");
        }
    }

    public function reply($slug) {
        Middleware::requireAuth();
        Middleware::verifyCSRF();
        
        try {
            Middleware::rateLimit('reply_create', 10, 60); // 10 replies per minute
        } catch (\Exception $e) {
            $this->backWithError("Slow down! Too many replies.");
        }

        $content = trim($_POST['content'] ?? '');
        $user    = Auth::user();

        if (!$this->verifyRecaptcha()) {
            $this->backWithError("Please complete the reCAPTCHA verification.");
        }

        if (strlen($content) < 2) {
            $this->backWithError("Reply content is too short.");
        }

        $db = Database::getInstance();
        $thread = $db->fetch(
            "SELECT id, user_id, title, slug, is_locked FROM threads WHERE slug = :slug",
            ['slug' => $slug]
        );

        if (!$thread) {
            http_response_code(404);
            $this->backWithError("Thread not found.");
        }

        if ($thread['is_locked']) {
            $this->backWithError("This thread is locked.");
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

            // Notify thread author
            if ($thread['user_id'] !== $user['id']) {
                $db->insert('notifications', [
                    'user_id'    => $thread['user_id'],
                    'type'       => 'new_reply',
                    'data'       => json_encode([
                        'thread_title'   => $thread['title'],
                        'thread_slug'    => $thread['slug'],
                        'post_id'        => $postId,
                        'replier_name'   => $user['username'],
                        'preview'        => mb_substr(strip_tags($content), 0, 80),
                    ]),
                ]);
            }

            $pdo->commit();

            header("Location: /thread/{$slug}#post-{$postId}");
            exit;
        } catch (\Exception $e) {
            if ($db->getPDO()->inTransaction()) {
                $db->getPDO()->rollBack();
            }
            error_log("Reply creation error: " . $e->getMessage());
            $this->backWithError("An error occurred while posting your reply.");
        }
    }
}
