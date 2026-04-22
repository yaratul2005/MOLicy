<?php

namespace Modules\Forum;

use Core\Database;
use Core\Auth;

class ThreadController {
    public function show($slug) {
        $db = Database::getInstance();
        
        // Fetch thread
        $thread = $db->fetch("SELECT t.*, u.username, c.name as category_name, c.slug as category_slug FROM threads t JOIN users u ON t.user_id = u.id JOIN categories c ON t.category_id = c.id WHERE t.slug = :slug", ['slug' => $slug]);
        if (!$thread) {
            http_response_code(404);
            echo "Thread not found.";
            return;
        }

        // Increment view count
        $db->query("UPDATE threads SET views = views + 1 WHERE id = :id", ['id' => $thread['id']]);

        // Fetch posts
        $posts = $db->fetchAll("SELECT p.*, u.username, u.reputation, u.trust_level FROM posts p JOIN users u ON p.user_id = u.id WHERE p.thread_id = :thread_id ORDER BY p.created_at ASC", [
            'thread_id' => $thread['id']
        ]);

        $theme = 'antigravity';
        $viewPath = ROOT_PATH . "/themes/{$theme}/pages/thread.php";
        
        if (file_exists($viewPath)) {
            require $viewPath;
        } else {
            echo "Thread view not found.";
        }
    }

    public function create() {
        if (!Auth::check()) {
            header('Location: /login');
            exit;
        }

        $db = Database::getInstance();
        $categories = $db->fetchAll("SELECT * FROM categories ORDER BY position ASC");

        $theme = 'antigravity';
        $viewPath = ROOT_PATH . "/themes/{$theme}/pages/thread-create.php";
        
        if (file_exists($viewPath)) {
            require $viewPath;
        } else {
            echo "Thread create view not found.";
        }
    }

    public function store() {
        if (!Auth::check()) {
            http_response_code(403);
            exit('Unauthorized');
        }

        $title = $_POST['title'] ?? '';
        $content = $_POST['content'] ?? '';
        $category_id = $_POST['category_id'] ?? 0;
        $user = Auth::user();

        if (empty($title) || empty($content) || empty($category_id)) {
            die("Title, content, and category are required.");
        }

        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title))) . '-' . uniqid();

        $db = Database::getInstance();
        
        try {
            $db->getPDO()->beginTransaction();

            // Insert thread
            $threadId = $db->insert('threads', [
                'category_id' => $category_id,
                'user_id' => $user['id'],
                'title' => $title,
                'slug' => $slug
            ]);

            // Insert OP post
            $db->insert('posts', [
                'thread_id' => $threadId,
                'user_id' => $user['id'],
                'content' => $content
            ]);

            $db->getPDO()->commit();

            header("Location: /thread/{$slug}");
            exit;
        } catch (\Exception $e) {
            $db->getPDO()->rollBack();
            die("Error creating thread: " . $e->getMessage());
        }
    }

    public function reply($slug) {
        if (!Auth::check()) {
            http_response_code(403);
            exit('Unauthorized');
        }

        $content = $_POST['content'] ?? '';
        $user = Auth::user();

        if (empty($content)) {
            die("Content is required.");
        }

        $db = Database::getInstance();
        $thread = $db->fetch("SELECT id FROM threads WHERE slug = :slug", ['slug' => $slug]);

        if (!$thread) {
            die("Thread not found.");
        }

        $db->insert('posts', [
            'thread_id' => $thread['id'],
            'user_id' => $user['id'],
            'content' => $content
        ]);

        header("Location: /thread/{$slug}");
        exit;
    }
}
