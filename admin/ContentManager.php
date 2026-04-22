<?php

namespace Admin;

use Core\Database;
use Core\Middleware;
use Core\Cache;

class ContentManager {

    public function __construct() {
        Middleware::requireAdmin();
    }

    // ─── CATEGORIES ────────────────────────────────────────────────

    public function categories(): void {
        $db   = Database::getInstance();
        $cats = $db->fetchAll(
            "SELECT c.*, p.name as parent_name,
                    (SELECT COUNT(*) FROM threads t WHERE t.category_id = c.id) as thread_count
             FROM categories c
             LEFT JOIN categories p ON c.parent_id = p.id
             ORDER BY c.position ASC, c.id ASC"
        );
        require ROOT_PATH . '/admin/views/categories.php';
    }

    public function createCategory(): void {
        Middleware::verifyCSRF();
        header('Content-Type: application/json');

        $name      = trim($_POST['name'] ?? '');
        $slug      = preg_replace('/[^a-z0-9-]/', '-', strtolower(trim($_POST['slug'] ?? $name)));
        $parentId  = (int)($_POST['parent_id'] ?? 0) ?: null;
        $desc      = trim($_POST['description'] ?? '');
        $position  = (int)($_POST['position'] ?? 0);

        if (!$name || !$slug) {
            echo json_encode(['error' => 'Name and slug required.']); return;
        }

        $db = Database::getInstance();
        $id = $db->insert('categories', [
            'name' => $name, 'slug' => $slug,
            'description' => $desc, 'parent_id' => $parentId, 'position' => $position
        ]);
        Cache::forget('categories_tree');
        echo json_encode(['success' => true, 'id' => $id]);
    }

    public function deleteCategory(): void {
        Middleware::verifyCSRF();
        $id = (int)($_POST['id'] ?? 0);
        $db = Database::getInstance();
        $db->query("DELETE FROM categories WHERE id = :id", ['id' => $id]);
        Cache::forget('categories_tree');
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    }

    /**
     * Drag-and-drop reorder categories.
     */
    public function reorderCategories(): void {
        Middleware::verifyCSRF();
        $order = json_decode(file_get_contents('php://input'), true)['order'] ?? [];
        $db = Database::getInstance();
        foreach ($order as $position => $id) {
            $db->query("UPDATE categories SET position = :p WHERE id = :id",
                ['p' => $position, 'id' => (int)$id]);
        }
        Cache::forget('categories_tree');
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    }

    // ─── THREADS ───────────────────────────────────────────────────

    public function threads(): void {
        $db    = Database::getInstance();
        $page  = max(1, (int)($_GET['page'] ?? 1));
        $q     = trim($_GET['q'] ?? '');
        $catId = (int)($_GET['category_id'] ?? 0);

        $where  = [];
        $params = ['limit' => 30, 'offset' => ($page - 1) * 30];

        if ($q) {
            $where[]    = 'MATCH(t.title) AGAINST(:q IN BOOLEAN MODE)';
            $params['q'] = $q . '*';
        }
        if ($catId) {
            $where[]         = 't.category_id = :cat';
            $params['cat']   = $catId;
        }
        $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $threads = $db->fetchAll(
            "SELECT t.id, t.title, t.slug, t.is_pinned, t.is_locked, t.reply_count, t.views, t.created_at,
                    u.username, c.name as category_name
             FROM threads t
             JOIN users u ON t.user_id = u.id
             JOIN categories c ON t.category_id = c.id
             {$whereSQL}
             ORDER BY t.created_at DESC LIMIT :limit OFFSET :offset",
            $params
        );
        $categories = $db->fetchAll("SELECT id, name FROM categories ORDER BY name");
        require ROOT_PATH . '/admin/views/threads.php';
    }

    public function deleteThread(): void {
        Middleware::verifyCSRF();
        $id = (int)($_POST['id'] ?? 0);
        $db = Database::getInstance();
        $db->query("DELETE FROM threads WHERE id = :id", ['id' => $id]);
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    }

    public function lockThread(): void {
        Middleware::verifyCSRF();
        $id   = (int)($_POST['id'] ?? 0);
        $lock = (int)($_POST['lock'] ?? 1);
        $db = Database::getInstance();
        $db->query("UPDATE threads SET is_locked = :l WHERE id = :id", ['l' => $lock, 'id' => $id]);
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    }

    public function moveThread(): void {
        Middleware::verifyCSRF();
        $threadId = (int)($_POST['thread_id'] ?? 0);
        $catId    = (int)($_POST['category_id'] ?? 0);
        $db = Database::getInstance();
        $db->query("UPDATE threads SET category_id = :c WHERE id = :id", ['c' => $catId, 'id' => $threadId]);
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    }

    // ─── MODERATION QUEUE ──────────────────────────────────────────

    public function moderationQueue(): void {
        // Placeholder: posts flagged by users
        $db   = Database::getInstance();
        $flags = $db->fetchAll(
            "SELECT p.id, p.content, p.created_at, u.username,
                    t.title as thread_title, t.slug as thread_slug
             FROM posts p
             JOIN users u ON p.user_id = u.id
             JOIN threads t ON p.thread_id = t.id
             WHERE p.is_flagged = 1
             ORDER BY p.created_at ASC
             LIMIT 50"
        ) ?: [];
        require ROOT_PATH . '/admin/views/moderation.php';
    }

    public function approvePost(): void {
        Middleware::verifyCSRF();
        $id = (int)($_POST['post_id'] ?? 0);
        $db = Database::getInstance();
        $db->query("UPDATE posts SET is_flagged = 0 WHERE id = :id", ['id' => $id]);
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    }

    public function deletePost(): void {
        Middleware::verifyCSRF();
        $id = (int)($_POST['post_id'] ?? 0);
        $db = Database::getInstance();
        $db->query("DELETE FROM posts WHERE id = :id", ['id' => $id]);
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    }
}
