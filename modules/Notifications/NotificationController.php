<?php

namespace Modules\Notifications;

use Core\Auth;
use Core\Database;
use Core\Middleware;

class NotificationController {

    /**
     * Server-Sent Events endpoint — streams live events to authenticated user.
     * Works on shared hosting (no WebSockets needed).
     */
    public function stream(): void {
        Middleware::requireAuth();

        $userId = $_SESSION['user_id'];
        $db = Database::getInstance();

        // SSE headers
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no'); // Disable Nginx buffering
        @ob_end_flush();

        $lastId = (int)($_SERVER['HTTP_LAST_EVENT_ID'] ?? 0);
        $start  = time();
        $maxAge = 30; // seconds — close connection and let client reconnect

        while (time() - $start < $maxAge) {
            // Poll for new events
            $events = $db->fetchAll(
                "SELECT * FROM sse_events 
                 WHERE user_id = :uid AND dispatched = 0 AND id > :last
                 ORDER BY id ASC LIMIT 10",
                ['uid' => $userId, 'last' => $lastId]
            );

            foreach ($events as $event) {
                echo "id: {$event['id']}\n";
                echo "event: {$event['event_type']}\n";
                echo "data: " . $event['payload'] . "\n\n";
                $lastId = $event['id'];

                // Mark dispatched
                $db->query(
                    "UPDATE sse_events SET dispatched = 1 WHERE id = :id",
                    ['id' => $event['id']]
                );
            }

            // Heartbeat every 5s to keep connection alive
            echo ": heartbeat\n\n";
            flush();

            sleep(3);

            // Exit if client disconnected
            if (connection_aborted()) break;
        }

        // Tell client to reconnect
        echo "retry: 3000\n\n";
        flush();
    }

    /**
     * Fetch unread notification count (JSON).
     */
    public function count(): void {
        Middleware::requireAuth();
        $db = Database::getInstance();
        $row = $db->fetch(
            "SELECT COUNT(*) as cnt FROM notifications WHERE user_id = :uid AND is_read = 0",
            ['uid' => $_SESSION['user_id']]
        );
        header('Content-Type: application/json');
        echo json_encode(['count' => (int)($row['cnt'] ?? 0)]);
    }

    /**
     * Fetch recent notifications (JSON).
     */
    public function index(): void {
        Middleware::requireAuth();
        $db = Database::getInstance();
        $notifs = $db->fetchAll(
            "SELECT * FROM notifications WHERE user_id = :uid ORDER BY created_at DESC LIMIT 20",
            ['uid' => $_SESSION['user_id']]
        );
        foreach ($notifs as &$n) {
            $n['data'] = json_decode($n['data'], true);
        }
        header('Content-Type: application/json');
        echo json_encode($notifs);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllRead(): void {
        Middleware::requireAuth();
        Middleware::verifyCSRF();
        $db = Database::getInstance();
        $db->query(
            "UPDATE notifications SET is_read = 1 WHERE user_id = :uid",
            ['uid' => $_SESSION['user_id']]
        );
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    }

    /**
     * Dispatch a notification to a user (internal use).
     */
    public static function dispatch(int $userId, string $type, array $data): void {
        $db = Database::getInstance();

        // Insert into notifications table
        $db->insert('notifications', [
            'user_id' => $userId,
            'type'    => $type,
            'data'    => json_encode($data),
        ]);

        // Insert into SSE event queue for real-time push
        $db->insert('sse_events', [
            'user_id'    => $userId,
            'event_type' => $type,
            'payload'    => json_encode(array_merge($data, ['type' => $type])),
        ]);
    }
}
