<?php
/**
 * AGF REST API v1 — Search
 * Full-text MySQL search with filters and live suggestions.
 */

namespace Api\V1;

use Core\Database;
use Core\Cache;
use Core\Middleware;

class SearchApi {

    public function index(): void {
        header('Content-Type: application/json');
        Middleware::rateLimit('search', 30, 60);

        $q       = trim($_GET['q'] ?? '');
        $type    = in_array($_GET['type'] ?? '', ['threads','posts','users']) ? $_GET['type'] : 'threads';
        $catSlug = $_GET['category'] ?? '';
        $dateFrom = $_GET['date_from'] ?? '';
        $dateTo   = $_GET['date_to'] ?? '';
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 20;
        $offset  = ($page - 1) * $perPage;

        if (strlen($q) < 2) {
            echo json_encode(['error' => 'Query too short.', 'data' => [], 'total' => 0]); return;
        }

        $db      = Database::getInstance();
        $cacheKey = md5("search_{$type}_{$q}_{$catSlug}_{$dateFrom}_{$dateTo}_{$page}");
        
        $results = Cache::remember($cacheKey, 120, function() use ($db, $q, $type, $catSlug, $dateFrom, $dateTo, $perPage, $offset) {
            $where  = [];
            $params = ['q' => $q, 'limit' => $perPage, 'offset' => $offset];

            if ($type === 'threads') {
                $where[] = 'MATCH(t.title) AGAINST(:q IN BOOLEAN MODE)';
                if ($catSlug) {
                    $cat = $db->fetch("SELECT id FROM categories WHERE slug = :s", ['s' => $catSlug]);
                    if ($cat) { $where[] = 't.category_id = :cat'; $params['cat'] = $cat['id']; }
                }
                if ($dateFrom) { $where[] = 't.created_at >= :df'; $params['df'] = $dateFrom; }
                if ($dateTo)   { $where[] = 't.created_at <= :dt'; $params['dt'] = $dateTo; }
                $whereSQL = 'WHERE ' . implode(' AND ', $where);

                $rows = $db->fetchAll(
                    "SELECT t.id, t.title, t.slug, t.reply_count, t.views, t.created_at,
                            u.username, c.name as category_name, c.slug as category_slug,
                            MATCH(t.title) AGAINST(:q IN BOOLEAN MODE) as score
                     FROM threads t
                     JOIN users u ON t.user_id = u.id
                     JOIN categories c ON t.category_id = c.id
                     {$whereSQL}
                     ORDER BY score DESC, t.created_at DESC
                     LIMIT :limit OFFSET :offset",
                    $params
                );
                // Highlight query term
                foreach ($rows as &$row) {
                    $row['title_highlighted'] = preg_replace(
                        '/(' . preg_quote($q, '/') . ')/i',
                        '<mark>$1</mark>',
                        htmlspecialchars($row['title'])
                    );
                }
                return $rows;

            } elseif ($type === 'posts') {
                $rows = $db->fetchAll(
                    "SELECT p.id, p.content, p.created_at,
                            u.username, t.title as thread_title, t.slug as thread_slug,
                            MATCH(p.content) AGAINST(:q IN BOOLEAN MODE) as score
                     FROM posts p
                     JOIN users u ON p.user_id = u.id
                     JOIN threads t ON p.thread_id = t.id
                     WHERE MATCH(p.content) AGAINST(:q IN BOOLEAN MODE)
                     ORDER BY score DESC
                     LIMIT :limit OFFSET :offset",
                    $params
                );
                foreach ($rows as &$row) {
                    // Extract snippet around match
                    $pos = stripos($row['content'], $q);
                    $start = max(0, $pos - 80);
                    $snippet = mb_substr($row['content'], $start, 200);
                    $row['snippet'] = '...' . preg_replace(
                        '/(' . preg_quote($q, '/') . ')/i',
                        '<mark>$1</mark>',
                        htmlspecialchars($snippet)
                    ) . '...';
                }
                return $rows;

            } elseif ($type === 'users') {
                return $db->fetchAll(
                    "SELECT id, username, avatar, bio, reputation, trust_level
                     FROM users
                     WHERE username LIKE :q OR bio LIKE :q
                     LIMIT :limit OFFSET :offset",
                    ['q' => "%{$q}%", 'limit' => $perPage, 'offset' => $offset]
                );
            }
            return [];
        });

        // Log popular search (async-friendly)
        $this->logSearch($db, $q);

        echo json_encode(['data' => $results, 'query' => $q, 'type' => $type, 'page' => $page]);
    }

    /**
     * Live suggestions (debounced search-as-you-type).
     */
    public function suggest(): void {
        header('Content-Type: application/json');
        $q = trim($_GET['q'] ?? '');
        if (strlen($q) < 2) { echo json_encode([]); return; }

        $db = Database::getInstance();
        $cacheKey = 'suggest_' . md5($q);

        $suggestions = Cache::remember($cacheKey, 60, function() use ($db, $q) {
            $threads = $db->fetchAll(
                "SELECT title as text, slug, 'thread' as type
                 FROM threads WHERE title LIKE :q LIMIT 5",
                ['q' => "{$q}%"]
            );
            $users = $db->fetchAll(
                "SELECT username as text, username as slug, 'user' as type
                 FROM users WHERE username LIKE :q LIMIT 3",
                ['q' => "{$q}%"]
            );
            return array_merge($threads, $users);
        });

        echo json_encode($suggestions);
    }

    private function logSearch(Database $db, string $q): void {
        try {
            $existing = $db->fetch("SELECT id, setting_value FROM settings WHERE setting_key = :k", ['k' => 'popular_searches']);
            $searches = $existing ? (json_decode($existing['setting_value'], true) ?? []) : [];
            $searches[$q] = ($searches[$q] ?? 0) + 1;
            arsort($searches);
            $searches = array_slice($searches, 0, 100, true);
            $payload  = json_encode($searches);
            if ($existing) {
                $db->update('settings', ['setting_value' => $payload], ['setting_key' => 'popular_searches']);
            } else {
                $db->insert('settings', ['setting_key' => 'popular_searches', 'setting_value' => $payload]);
            }
        } catch (\Throwable $e) { /* non-critical */ }
    }
}
