<?php

namespace Modules\Users;

use Core\Auth;
use Core\Database;
use Core\Middleware;
use Core\Cache;

class ProfileController {

    /**
     * Public user profile page.
     */
    public function show(string $username): void {
        $db   = Database::getInstance();
        $user = $db->fetch(
            "SELECT id, username, avatar, bio, website, location, reputation, trust_level,
                    role, post_count, created_at, last_seen_at
             FROM users WHERE username = :username",
            ['username' => $username]
        );

        if (!$user) {
            http_response_code(404);
            echo "User not found.";
            return;
        }

        // Recent threads
        $threads = $db->fetchAll(
            "SELECT t.id, t.title, t.slug, t.created_at, t.reply_count, t.views,
                    c.name as category_name, c.slug as category_slug
             FROM threads t
             JOIN categories c ON t.category_id = c.id
             WHERE t.user_id = :uid
             ORDER BY t.created_at DESC LIMIT 10",
            ['uid' => $user['id']]
        );

        // Recent posts
        $posts = $db->fetchAll(
            "SELECT p.id, p.content, p.created_at,
                    t.title as thread_title, t.slug as thread_slug
             FROM posts p
             JOIN threads t ON p.thread_id = t.id
             WHERE p.user_id = :uid
             ORDER BY p.created_at DESC LIMIT 10",
            ['uid' => $user['id']]
        );

        // Badges
        $badges = $db->fetchAll(
            "SELECT b.name, b.slug, b.icon, b.tier, ub.awarded_at
             FROM user_badges ub
             JOIN badges b ON ub.badge_id = b.id
             WHERE ub.user_id = :uid
             ORDER BY b.tier DESC",
            ['uid' => $user['id']]
        );

        $theme    = 'antigravity';
        $viewPath = ROOT_PATH . "/themes/{$theme}/pages/profile.php";
        require $viewPath;
    }

    /**
     * Edit profile form (own profile only).
     */
    public function edit(): void {
        Middleware::requireAuth();
        $user = Auth::user();
        $theme = 'antigravity';
        require ROOT_PATH . "/themes/{$theme}/pages/profile-edit.php";
    }

    /**
     * Save profile changes.
     */
    public function update(): void {
        Middleware::requireAuth();
        Middleware::verifyCSRF();
        Middleware::rateLimit('profile_update', 5, 60);

        $db     = Database::getInstance();
        $userId = $_SESSION['user_id'];

        $bio      = substr(trim($_POST['bio'] ?? ''), 0, 500);
        $website  = filter_var(trim($_POST['website'] ?? ''), FILTER_VALIDATE_URL) ?: null;
        $location = substr(trim($_POST['location'] ?? ''), 0, 100);

        // Handle avatar upload
        $avatarPath = null;
        if (!empty($_FILES['avatar']['tmp_name'])) {
            $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            $mime    = mime_content_type($_FILES['avatar']['tmp_name']);
            if (!in_array($mime, $allowed)) {
                die(json_encode(['error' => 'Invalid image type.']));
            }
            $ext        = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
            $filename   = 'avatar_' . $userId . '_' . time() . '.' . strtolower($ext);
            $uploadDir  = ROOT_PATH . '/storage/avatars/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            move_uploaded_file($_FILES['avatar']['tmp_name'], $uploadDir . $filename);
            $avatarPath = '/storage/avatars/' . $filename;
        }

        $fields = ['bio' => $bio, 'website' => $website, 'location' => $location];
        if ($avatarPath) $fields['avatar'] = $avatarPath;

        $db->update('users', $fields, ['id' => $userId]);

        // Clear user cache
        Cache::forget('user_' . $userId);

        header('Location: /u/' . urlencode($_SESSION['username']));
    }

    /**
     * Vote on a post (upvote/downvote).
     */
    public function vote(): void {
        Middleware::requireAuth();
        Middleware::verifyCSRF();
        Middleware::rateLimit('vote', 30, 60);

        header('Content-Type: application/json');

        $postId = (int)($_POST['post_id'] ?? 0);
        $value  = (int)($_POST['value'] ?? 1);
        $value  = in_array($value, [-1, 1]) ? $value : 1;

        if (!$postId) {
            echo json_encode(['error' => 'Invalid post.']); return;
        }

        $db     = Database::getInstance();
        $userId = $_SESSION['user_id'];

        // Check if already voted
        $existing = $db->fetch(
            "SELECT id, value FROM votes WHERE user_id = :uid AND votable_type = 'post' AND votable_id = :pid",
            ['uid' => $userId, 'pid' => $postId]
        );

        if ($existing) {
            if ($existing['value'] === $value) {
                // Undo vote
                $db->query("DELETE FROM votes WHERE id = :id", ['id' => $existing['id']]);
                $delta = -$value;
            } else {
                // Flip vote
                $db->query("UPDATE votes SET value = :v WHERE id = :id", ['v' => $value, 'id' => $existing['id']]);
                $delta = $value * 2;
            }
        } else {
            $db->insert('votes', [
                'user_id'      => $userId,
                'votable_type' => 'post',
                'votable_id'   => $postId,
                'value'        => $value,
            ]);
            $delta = $value;
        }

        // Update post author reputation
        $post = $db->fetch("SELECT user_id FROM posts WHERE id = :id", ['id' => $postId]);
        if ($post) {
            $db->query(
                "UPDATE users SET reputation = reputation + :delta WHERE id = :uid",
                ['delta' => $delta, 'uid' => $post['user_id']]
            );
        }

        // New vote count
        $score = $db->fetch(
            "SELECT COALESCE(SUM(value), 0) as score FROM votes WHERE votable_type = 'post' AND votable_id = :pid",
            ['pid' => $postId]
        );

        echo json_encode(['success' => true, 'score' => (int)$score['score']]);
    }

    /**
     * React to a post with an emoji.
     */
    public function react(): void {
        Middleware::requireAuth();
        Middleware::verifyCSRF();
        Middleware::rateLimit('react', 20, 60);

        header('Content-Type: application/json');

        $postId = (int)($_POST['post_id'] ?? 0);
        $emoji  = mb_substr(trim($_POST['emoji'] ?? ''), 0, 10);

        if (!$postId || !$emoji) {
            echo json_encode(['error' => 'Invalid data.']); return;
        }

        $db     = Database::getInstance();
        $userId = $_SESSION['user_id'];

        $existing = $db->fetch(
            "SELECT id FROM reactions WHERE user_id = :uid AND post_id = :pid AND emoji = :emoji",
            ['uid' => $userId, 'pid' => $postId, 'emoji' => $emoji]
        );

        if ($existing) {
            $db->query("DELETE FROM reactions WHERE id = :id", ['id' => $existing['id']]);
            $added = false;
        } else {
            $db->insert('reactions', ['user_id' => $userId, 'post_id' => $postId, 'emoji' => $emoji]);
            $added = true;
        }

        $counts = $db->fetchAll(
            "SELECT emoji, COUNT(*) as count FROM reactions WHERE post_id = :pid GROUP BY emoji",
            ['pid' => $postId]
        );

        echo json_encode(['success' => true, 'added' => $added, 'reactions' => $counts]);
    }
}
