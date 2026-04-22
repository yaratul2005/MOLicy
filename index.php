<?php
/**
 * AntiGravity Forum (AGF)
 * Main Application Entry Point
 */

define('AGF_START', microtime(true));
define('ROOT_PATH', __DIR__);

// Check if installer is still present
if (is_dir(ROOT_PATH . '/install') && !file_exists(ROOT_PATH . '/install/lock')) {
    header('Location: /install/');
    exit;
}

// Require the bootstrapper
if (!file_exists(ROOT_PATH . '/core/Bootstrap.php')) {
    die('Critical error: core/Bootstrap.php is missing.');
}

require ROOT_PATH . '/core/Bootstrap.php';

// Initialize the application
use Core\Bootstrap;

$app = new Bootstrap();
$app->run();
