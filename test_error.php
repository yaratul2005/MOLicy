<?php
define('ROOT_PATH', __DIR__);
require __DIR__ . '/core/Bootstrap.php';
$db = \Core\Database::getInstance();

try {
    $db->insert('audit_log', [
        'admin_id'    => 1,
        'action'      => 'settings_update',
        'target_type' => null,
        'target_id'   => null,
        'details'     => json_encode(['keys' => ['test']]),
        'ip'          => '127.0.0.1',
    ]);
    echo "Audit log insert OK.\n";
} catch (\Throwable $e) {
    echo "Audit log Error: " . $e->getMessage() . "\n";
}

try {
    $db->query("INSERT INTO settings (setting_key, setting_value) VALUES ('test', '1') ON DUPLICATE KEY UPDATE setting_value = '1'");
    echo "Settings insert OK.\n";
} catch (\Throwable $e) {
    echo "Settings Error: " . $e->getMessage() . "\n";
}
