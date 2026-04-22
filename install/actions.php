<?php
session_start();

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'check_reqs') {
    $reqs = [
        ['name' => 'PHP 8.2+', 'ok' => version_compare(PHP_VERSION, '8.2.0', '>=')],
        ['name' => 'PDO MySQL', 'ok' => extension_loaded('pdo_mysql')],
        ['name' => 'cURL Extension', 'ok' => extension_loaded('curl')],
        ['name' => 'JSON Extension', 'ok' => extension_loaded('json')],
        ['name' => 'MBString Extension', 'ok' => extension_loaded('mbstring')],
        ['name' => '/config.php Writable', 'ok' => is_writable(__DIR__ . '/../') || is_writable(__DIR__ . '/../config.php')]
    ];
    echo json_encode(['reqs' => $reqs]);
    exit;
}

if ($action === 'setup_db') {
    $host = $_POST['db_host'] ?? '';
    $name = $_POST['db_name'] ?? '';
    $user = $_POST['db_user'] ?? '';
    $pass = $_POST['db_pass'] ?? '';

    try {
        $dsn = "mysql:host={$host};charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        
        // Create DB if not exists
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `{$name}`");

        // Run migrations
        $sql = file_get_contents(__DIR__ . '/../migrations/0000_initial_schema.sql');
        if ($sql) {
            $pdo->exec($sql);
        }

        // Save DB config to session temporarily
        $_SESSION['db'] = compact('host', 'name', 'user', 'pass');
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'finalize') {
    $forum_name = $_POST['forum_name'] ?? '';
    $forum_tagline = $_POST['forum_tagline'] ?? '';
    $admin_user = $_POST['admin_user'] ?? '';
    $admin_email = $_POST['admin_email'] ?? '';
    $admin_pass = password_hash($_POST['admin_pass'] ?? '', PASSWORD_BCRYPT, ['cost' => 12]);

    if (!isset($_SESSION['db'])) {
        echo json_encode(['success' => false, 'error' => 'Database config lost. Please restart installer.']);
        exit;
    }

    $db = $_SESSION['db'];

    try {
        $dsn = "mysql:host={$db['host']};dbname={$db['name']};charset=utf8mb4";
        $pdo = new PDO($dsn, $db['user'], $db['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

        // Insert Admin User
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, trust_level) VALUES (?, ?, ?, 5)");
        $stmt->execute([$admin_user, $admin_email, $admin_pass]);

        // Insert Settings
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
        $stmt->execute(['forum_name', $forum_name]);
        $stmt->execute(['forum_tagline', $forum_tagline]);

        // Generate Config File
        $configContent = "<?php\n"
            . "define('DB_HOST', '{$db['host']}');\n"
            . "define('DB_NAME', '{$db['name']}');\n"
            . "define('DB_USER', '{$db['user']}');\n"
            . "define('DB_PASS', '{$db['pass']}');\n"
            . "define('APP_KEY', '" . bin2hex(random_bytes(32)) . "');\n";

        file_put_contents(__DIR__ . '/../config.php', $configContent);

        // Lock Installer
        file_put_contents(__DIR__ . '/lock', 'Locked on ' . date('Y-m-d H:i:s'));

        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}
