<?php
/**
 * Temporary Migration Fixer
 * Run this once to apply Phase 4 schemas if you installed before the installer was patched.
 */
require __DIR__ . '/core/Bootstrap.php';

try {
    $db = \Core\Database::getInstance();
    $sql = file_get_contents(__DIR__ . '/migrations/0001_phase4_user_system.sql');
    
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            try {
                $db->query($statement);
            } catch (Exception $e) {
                // Ignore errors like "Column already exists" or "Table already exists"
            }
        }
    }
    
    echo "<h1>✅ Database Successfully Upgraded!</h1>";
    echo "<p>Phase 4 schemas (notifications, votes, badges, etc.) have been applied.</p>";
    echo "<p><a href='/admin/'>Click here to return to the Admin Dashboard</a></p>";
    echo "<p style='color:red; font-size: 0.8rem;'>Note: You can safely delete this fix-db.php file later.</p>";
    
} catch (Exception $e) {
    echo "<h1>❌ Error</h1>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
