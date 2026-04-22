<?php

namespace Admin;

use Core\Auth;
use Core\Database;
use Core\Middleware;
use Core\Cache;

class UserManager {

    public function __construct() {
        Middleware::requireAdmin();
    }

    /**
     * Paginated, filterable user list.
     */
    public function index(): void {
        $db      = Database::getInstance();
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 25;
        $offset  = ($page - 1) * $perPage;
        $search  = trim($_GET['q'] ?? '');
        $role    = $_GET['role'] ?? '';

        $where  = [];
        $params = [];

        if ($search) {
            $where[]  = '(username LIKE :q OR email LIKE :q)';
            $params['q'] = "%{$search}%";
        }
        if ($role && in_array($role, ['member','moderator','admin'])) {
            $where[]   = 'role = :role';
            $params['role'] = $role;
        }

        $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $total = $db->fetch("SELECT COUNT(*) as c FROM users {$whereSQL}", $params)['c'] ?? 0;
        $users = $db->fetchAll(
            "SELECT id, username, email, avatar, trust_level, reputation, role, post_count,
                    created_at, last_seen_at, email_verified_at
             FROM users {$whereSQL}
             ORDER BY created_at DESC
             LIMIT :limit OFFSET :offset",
            array_merge($params, ['limit' => $perPage, 'offset' => $offset])
        );

        $totalPages = (int)ceil($total / $perPage);
        require ROOT_PATH . '/admin/views/users.php';
    }

    /**
     * Inline user update (AJAX — trust level, role).
     */
    public function update(): void {
        Middleware::verifyCSRF();
        header('Content-Type: application/json');

        $userId = (int)($_POST['user_id'] ?? 0);
        $field  = $_POST['field'] ?? '';
        $value  = $_POST['value'] ?? '';

        $allowed = ['trust_level', 'role', 'reputation'];
        if (!in_array($field, $allowed) || !$userId) {
            echo json_encode(['error' => 'Invalid.']); return;
        }

        $db = Database::getInstance();
        $db->update('users', [$field => $value], ['id' => $userId]);
        Cache::forget('user_' . $userId);
        echo json_encode(['success' => true]);
    }

    /**
     * Ban a user (temp/permanent/shadow).
     */
    public function ban(): void {
        Middleware::verifyCSRF();
        header('Content-Type: application/json');

        $userId   = (int)($_POST['user_id'] ?? 0);
        $type     = $_POST['ban_type'] ?? 'temp';
        $duration = (int)($_POST['duration'] ?? 86400);

        if (!$userId) {
            echo json_encode(['error' => 'Invalid user.']); return;
        }

        $db   = Database::getInstance();
        $data = [
            'role'       => 'banned',
            'trust_level' => -1,
        ];
        $db->update('users', $data, ['id' => $userId]);

        // Log ban in settings table as JSON
        $banKey = "ban_{$userId}";
        $banData = json_encode([
            'type'       => $type,
            'expires_at' => $type === 'permanent' ? null : date('Y-m-d H:i:s', time() + $duration),
            'by_admin'   => $_SESSION['user_id'],
        ]);
        $existing = $db->fetch("SELECT id FROM settings WHERE setting_key = :k", ['k' => $banKey]);
        if ($existing) {
            $db->update('settings', ['setting_value' => $banData], ['setting_key' => $banKey]);
        } else {
            $db->insert('settings', ['setting_key' => $banKey, 'setting_value' => $banData]);
        }

        Cache::forget('user_' . $userId);
        echo json_encode(['success' => true, 'message' => "User banned ({$type})."]);
    }

    /**
     * Delete a user permanently.
     */
    public function delete(): void {
        Middleware::verifyCSRF();
        header('Content-Type: application/json');

        $userId = (int)($_POST['user_id'] ?? 0);
        if (!$userId || $userId === (int)$_SESSION['user_id']) {
            echo json_encode(['error' => 'Cannot delete self or invalid ID.']); return;
        }

        $db = Database::getInstance();
        $db->query("DELETE FROM users WHERE id = :id", ['id' => $userId]);
        Cache::forget('user_' . $userId);
        echo json_encode(['success' => true]);
    }

    /**
     * Export users to CSV.
     */
    public function export(): void {
        $db    = Database::getInstance();
        $users = $db->fetchAll(
            "SELECT id, username, email, role, trust_level, reputation, post_count, created_at FROM users ORDER BY id"
        );

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="agf-users-' . date('Ymd') . '.csv"');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['ID','Username','Email','Role','Trust Level','Reputation','Posts','Joined']);
        foreach ($users as $u) {
            fputcsv($out, [$u['id'],$u['username'],$u['email'],$u['role'],$u['trust_level'],$u['reputation'],$u['post_count'],$u['created_at']]);
        }
        fclose($out);
    }
}
