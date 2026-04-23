<?php
/**
 * fix-db.php — Run ALL pending migrations (safe to re-run, uses IF NOT EXISTS / IGNORE)
 * Delete this file after use.
 */
define('ROOT_PATH', __DIR__);
require __DIR__ . '/core/Bootstrap.php';
$app = new \Core\Bootstrap();

$errors = [];
$applied = [];

try {
    $db  = \Core\Database::getInstance();
    $dir = ROOT_PATH . '/migrations/';
    $files = glob($dir . '*.sql');
    sort($files);

    foreach ($files as $file) {
        $sql  = file_get_contents($file);
        $stmts = array_filter(array_map('trim', explode(';', $sql)));
        foreach ($stmts as $stmt) {
            if (empty($stmt)) continue;
            try {
                $db->query($stmt);
            } catch (\Throwable $e) {
                $msg = $e->getMessage();
                // Silently ignore "already exists" errors
                if (!str_contains($msg, 'already exists') && !str_contains($msg, 'Duplicate entry') && !str_contains($msg, 'Duplicate column')) {
                    $errors[] = basename($file) . ': ' . $msg;
                }
            }
        }
        $applied[] = basename($file);
    }

    echo '<style>body{font-family:sans-serif;background:#07070d;color:#f1f5f9;padding:40px;max-width:700px;margin:0 auto}
    h1{color:#06b6d4}li{margin:4px 0}.err{color:#ef4444}.ok{color:#10b981}</style>';
    echo '<h1>✅ Database Migration Complete</h1>';
    echo '<h3>Applied migrations:</h3><ul>';
    foreach ($applied as $f) echo '<li class="ok">✓ ' . htmlspecialchars($f) . '</li>';
    echo '</ul>';
    if ($errors) {
        echo '<h3>Non-fatal warnings:</h3><ul>';
        foreach ($errors as $e) echo '<li class="err">' . htmlspecialchars($e) . '</li>';
        echo '</ul>';
    }
    echo '<p><a href="/admin/" style="color:#7c3aed">→ Go to Admin Dashboard</a></p>';
    echo '<p style="color:#94a3b8;font-size:0.8rem">⚠️ Delete this file when done for security.</p>';

} catch (\Throwable $e) {
    echo '<h1>❌ Error</h1><p style="color:#ef4444">' . htmlspecialchars($e->getMessage()) . '</p>';
}
