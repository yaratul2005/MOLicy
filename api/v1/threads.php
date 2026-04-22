<?php
/**
 * AGF REST API v1 — Threads
 * All responses are JSON. Authentication via session.
 */

namespace Api\V1;

use Core\Auth;
use Core\Database;
use Core\Middleware;

class ThreadsApi {

    public function index(): void {
        header('Content-Type: application/json');

        $db      = Database::getInstance();
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $perPage = min(50, max(5, (int)($_GET['per_page'] ?? 20)));
        $catSlug = $_GET['category'] ?? '';
        $sort    = in_array($_GET['sort'] ?? '', ['latest','popular','unanswered']) ? $_GET['sort'] : 'latest';

        $where  = [];
        $params = [];

        if ($catSlug) {
            $cat = $db->fetch("SELECT id FROM categories WHERE slug = :s", ['s' => $catSlug]);
            if ($cat) { $where[] = 't.category_id = :cat'; $params['cat'] = $cat['id']; }
        }

        $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $orderSQL = match($sort) {
            'popular'    => 'ORDER BY t.views DESC, t.reply_count DESC',
            'unanswered' => 'WHERE ' . ($where ? implode(' AND ', $where) . ' AND' : '') . ' t.reply_count = 0 ORDER BY t.created_at DESC',
            default      => 'ORDER BY t.is_pinned DESC, t.last_post_at DESC, t.created_at DESC',
        };

        // Override for unanswered
        if ($sort === 'unanswered') $whereSQL = '';

        // Cursor-based pagination (use last seen id)
        $cursor     = (int)($_GET['cursor'] ?? 0);
        $cursorSQL  = $cursor ? "AND t.id < {$cursor}" : '';

        $threads = $db->fetchAll(
            "SELECT t.id, t.title, t.slug, t.type, t.reply_count, t.views, t.is_pinned, t.is_locked,
                    t.is_solved, t.created_at, t.last_post_at,
                    u.username, u.avatar, u.trust_level,
                    c.name as category_name, c.slug as category_slug
             FROM threads t
             JOIN users u ON t.user_id = u.id
             JOIN categories c ON t.category_id = c.id
             {$whereSQL} {$cursorSQL}
             {$orderSQL}
             LIMIT :limit",
            array_merge($params, ['limit' => $perPage])
        );

        $nextCursor = count($threads) === $perPage ? end($threads)['id'] : null;

        echo json_encode([
            'data'        => $threads,
            'next_cursor' => $nextCursor,
            'per_page'    => $perPage,
        ]);
    }

    public function show(string $slug): void {
        header('Content-Type: application/json');
        $db = Database::getInstance();

        $thread = $db->fetch(
            "SELECT t.*, u.username, u.avatar, u.trust_level, u.reputation,
                    c.name as category_name, c.slug as category_slug
             FROM threads t
             JOIN users u ON t.user_id = u.id
             JOIN categories c ON t.category_id = c.id
             WHERE t.slug = :slug",
            ['slug' => $slug]
        );

        if (!$thread) {
            http_response_code(404);
            echo json_encode(['error' => 'Thread not found.']); return;
        }

        // Increment view
        $db->query("UPDATE threads SET views = views + 1 WHERE id = :id", ['id' => $thread['id']]);

        // Tags
        $thread['tags'] = $db->fetchAll(
            "SELECT tg.name, tg.slug, tg.color FROM tags tg
             JOIN thread_tags tt ON tt.tag_id = tg.id WHERE tt.thread_id = :tid",
            ['tid' => $thread['id']]
        );

        echo json_encode(['data' => $thread]);
    }

    public function store(): void {
        Middleware::requireAuth();
        Middleware::verifyCSRF();
        Middleware::rateLimit('thread_create', 5, 3600);

        header('Content-Type: application/json');

        $title    = trim($_POST['title'] ?? '');
        $content  = trim($_POST['content'] ?? '');
        $catId    = (int)($_POST['category_id'] ?? 0);
        $type     = in_array($_POST['type'] ?? '', ['discussion','question','poll','showcase','debate']) ? $_POST['type'] : 'discussion';
        $tags     = array_slice((array)($_POST['tags'] ?? []), 0, 5);

        if (strlen($title) < 5 || strlen($title) > 255) {
            http_response_code(422); echo json_encode(['error' => 'Title must be 5–255 characters.']); return;
        }
        if (strlen($content) < 10) {
            http_response_code(422); echo json_encode(['error' => 'Content too short.']); return;
        }

        $db   = Database::getInstance();
        $slug = $this->makeSlug($title);

        $threadId = $db->insert('threads', [
            'category_id' => $catId,
            'user_id'     => $_SESSION['user_id'],
            'title'       => $title,
            'slug'        => $slug,
            'type'        => $type,
            'last_post_at' => date('Y-m-d H:i:s'),
        ]);

        // First post (OP)
        $db->insert('posts', [
            'thread_id' => $threadId,
            'user_id'   => $_SESSION['user_id'],
            'content'   => $content,
        ]);

        // Update user post count
        $db->query("UPDATE users SET post_count = post_count + 1 WHERE id = :id", ['id' => $_SESSION['user_id']]);

        // Handle tags
        foreach ($tags as $tagName) {
            $tagName = substr(trim($tagName), 0, 50);
            if (!$tagName) continue;
            $tagSlug = preg_replace('/[^a-z0-9-]/', '-', strtolower($tagName));
            $tag = $db->fetch("SELECT id FROM tags WHERE slug = :s", ['s' => $tagSlug]);
            if (!$tag) {
                $tagId = $db->insert('tags', ['name' => $tagName, 'slug' => $tagSlug]);
            } else {
                $tagId = $tag['id'];
            }
            $db->query("UPDATE tags SET thread_count = thread_count + 1 WHERE id = :id", ['id' => $tagId]);
            $db->insert('thread_tags', ['thread_id' => $threadId, 'tag_id' => $tagId]);
        }

        \Core\EventEmitter::doAction('after_thread_created', $threadId, $_SESSION['user_id']);

        echo json_encode(['success' => true, 'slug' => $slug, 'id' => $threadId]);
    }

    public function reply(string $slug): void {
        Middleware::requireAuth();
        Middleware::verifyCSRF();
        Middleware::rateLimit('post_create', 10, 60);

        header('Content-Type: application/json');

        $content = trim($_POST['content'] ?? '');
        if (strlen($content) < 2) {
            http_response_code(422); echo json_encode(['error' => 'Content too short.']); return;
        }

        $db     = Database::getInstance();
        $thread = $db->fetch("SELECT id, is_locked, user_id FROM threads WHERE slug = :s", ['s' => $slug]);
        if (!$thread) {
            http_response_code(404); echo json_encode(['error' => 'Thread not found.']); return;
        }
        if ($thread['is_locked']) {
            http_response_code(403); echo json_encode(['error' => 'Thread is locked.']); return;
        }

        $postId = $db->insert('posts', [
            'thread_id' => $thread['id'],
            'user_id'   => $_SESSION['user_id'],
            'content'   => $content,
        ]);

        // Update thread stats
        $db->query(
            "UPDATE threads SET reply_count = reply_count + 1, last_post_at = NOW() WHERE id = :id",
            ['id' => $thread['id']]
        );
        $db->query("UPDATE users SET post_count = post_count + 1 WHERE id = :id", ['id' => $_SESSION['user_id']]);

        // Notify thread owner (if not self)
        if ($thread['user_id'] !== (int)$_SESSION['user_id']) {
            \Modules\Notifications\NotificationController::dispatch(
                $thread['user_id'],
                'new_reply',
                ['thread_slug' => $slug, 'thread_title' => $thread['id'], 'from_username' => $_SESSION['username']]
            );
        }

        // Process @mentions
        preg_match_all('/@([a-zA-Z0-9_]+)/', $content, $mentions);
        foreach (array_unique($mentions[1]) as $mentionName) {
            $mentioned = $db->fetch("SELECT id FROM users WHERE username = :u", ['u' => $mentionName]);
            if ($mentioned && $mentioned['id'] !== (int)$_SESSION['user_id']) {
                $db->insert('mentions', ['post_id' => $postId, 'mentioned_user_id' => $mentioned['id']]);
                \Modules\Notifications\NotificationController::dispatch(
                    $mentioned['id'],
                    'mention',
                    ['thread_slug' => $slug, 'from_username' => $_SESSION['username'], 'post_id' => $postId]
                );
            }
        }

        \Core\EventEmitter::doAction('after_post_created', $postId, $_SESSION['user_id']);

        echo json_encode(['success' => true, 'post_id' => $postId]);
    }

    private function makeSlug(string $title): string {
        $db   = Database::getInstance();
        $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9\s-]/', '', $title)));
        $slug = preg_replace('/[\s-]+/', '-', $slug);
        $slug = substr(trim($slug, '-'), 0, 100);

        $base  = $slug;
        $count = 1;
        while ($db->fetch("SELECT id FROM threads WHERE slug = :s", ['s' => $slug])) {
            $slug = $base . '-' . $count++;
        }
        return $slug;
    }
}
