<?php

namespace Core;

use Core\Cache;
use Core\Middleware;
use Core\EventEmitter;
use Core\Settings;

class Bootstrap {
    public function __construct() {
        // Start output buffering with gzip compression
        if (extension_loaded('zlib') && !headers_sent()) {
            ob_start('ob_gzhandler');
        } else {
            ob_start();
        }

        // Load configuration
        $this->loadConfig();

        // Autoloader for Core and Modules
        spl_autoload_register([$this, 'autoload']);

        // Start Session securely
        $this->startSession();
    }

    private function loadConfig() {
        $configFile = ROOT_PATH . '/config.php';
        if (file_exists($configFile)) {
            require $configFile;
        } else {
            die('Configuration file not found. Please run the installer.');
        }
    }

    private function autoload($className) {
        // e.g., Core\Router -> core/Router.php
        // e.g., Modules\Forum\Thread -> modules/Forum/Thread.php
        $path = str_replace('\\', '/', $className);
        // Map Core to core, Modules to modules, etc.
        $path = lcfirst($path) . '.php';
        
        $fullPath = ROOT_PATH . '/' . $path;
        if (file_exists($fullPath)) {
            require $fullPath;
        }
    }

    private function startSession() {
        if (session_status() == PHP_SESSION_NONE) {
            session_set_cookie_params([
                'lifetime' => 86400,
                'path' => '/',
                'domain' => $_SERVER['HTTP_HOST'] ?? '',
                'secure' => isset($_SERVER['HTTPS']),
                'httponly' => true,
                'samesite' => 'Strict'
            ]);
            session_start();
        }
    }

    public function run() {
        // Initialize core services
        $db   = Database::getInstance();
        $auth = new Auth();
        Cache::init();
        Middleware::securityHeaders();
        Settings::load();

        // ── Maintenance Mode ─────────────────────────────────────────────
        if (Settings::maintenanceMode() && !Auth::check()) {
            $isAdminUser = isset($_SESSION['user_id']) && ($_SESSION['trust_level'] ?? 0) >= 5;
            if (!$isAdminUser) {
                $uri = $_SERVER['REQUEST_URI'] ?? '/';
                $allowed = ['/login', '/logout', '/verify-email'];
                $isAllowed = false;
                foreach ($allowed as $a) { if (str_starts_with($uri, $a)) { $isAllowed = true; break; } }
                if (!$isAllowed) {
                    http_response_code(503);
                    $msg = htmlspecialchars(Settings::get('maintenance_message', 'We are performing scheduled maintenance. Please check back soon.'));
                    $title = htmlspecialchars(Settings::siteTitle());
                    die("<!DOCTYPE html><html><head><title>{$title} — Maintenance</title><style>body{font-family:sans-serif;background:#07070d;color:#f1f5f9;display:flex;align-items:center;justify-content:center;min-height:100vh;text-align:center}h1{font-size:2rem}p{color:#94a3b8;max-width:480px}</style></head><body><div><h1>🚧 Under Maintenance</h1><p>{$msg}</p></div></body></html>");
                }
            }
        }

        $router = new Router();

        // ── Auth ────────────────────────────────────────────────────────
        $router->get('/login',    'Modules\Users\AuthController@showLogin');
        $router->post('/login',   'Modules\Users\AuthController@login');
        $router->get('/register', 'Modules\Users\AuthController@showRegister');
        $router->post('/register','Modules\Users\AuthController@register');
        $router->get('/logout',   'Modules\Users\AuthController@logout');
        $router->get('/verify-email', 'Modules\Users\AuthController@verifyEmail');

        // ── Forum ───────────────────────────────────────────────────────
        $router->get('/',                        'Modules\Forum\HomeController@index');
        $router->get('/category/{slug}',         'Modules\Forum\CategoryController@show');
        $router->get('/thread/create',           'Modules\Forum\ThreadController@create');
        $router->post('/thread/create',          'Modules\Forum\ThreadController@store');
        $router->get('/thread/{slug}',           'Modules\Forum\ThreadController@show');
        $router->post('/thread/{slug}/reply',    'Modules\Forum\ThreadController@reply');

        // ── Media ───────────────────────────────────────────────────────
        $router->post('/media/upload',           'Modules\Media\UploadController@upload');

        // ── Users / Profiles ────────────────────────────────────────────
        $router->get('/u/{username}',            'Modules\Users\ProfileController@show');
        $router->get('/profile/edit',            'Modules\Users\ProfileController@edit');
        $router->post('/profile/update',         'Modules\Users\ProfileController@update');
        $router->post('/vote',                   'Modules\Users\ProfileController@vote');
        $router->post('/react',                  'Modules\Users\ProfileController@react');

        // ── Notifications (SSE) ──────────────────────────────────────────
        $router->get('/notifications/stream',    'Modules\Notifications\NotificationController@stream');
        $router->get('/notifications',           'Modules\Notifications\NotificationController@index');
        $router->get('/notifications/count',     'Modules\Notifications\NotificationController@count');
        $router->post('/notifications/read-all', 'Modules\Notifications\NotificationController@markAllRead');

        // ── Search ───────────────────────────────────────────────────────
        $router->get('/search',                  'Modules\\Forum\\SearchController@index');

        // ── Members directory ────────────────────────────────────────────
        $router->get('/members',                 'Modules\\Users\\MembersController@index');

        // ── Bookmarks ────────────────────────────────────────────────────
        $router->post('/bookmark',               'Modules\\Users\\BookmarkController@toggle');
        $router->get('/bookmarks',               'Modules\\Users\\BookmarkController@index');

        // ── Subscribe / Flag ─────────────────────────────────────────────
        $router->post('/subscribe',              'Modules\\Forum\\ThreadController@subscribe');
        $router->post('/flag',                   'Modules\\Forum\\ThreadController@flag');

        // ── Thread edit ──────────────────────────────────────────────────
        $router->get('/thread/{slug}/edit',      'Modules\\Forum\\ThreadController@edit');
        $router->post('/thread/{slug}/edit',     'Modules\\Forum\\ThreadController@update');
        $router->post('/post/{id}/delete',       'Modules\\Forum\\ThreadController@deletePost');
        $router->post('/post/{id}/edit',         'Modules\\Forum\\ThreadController@editPost');

        // ── REST API v1 ──────────────────────────────────────────────────
        $router->get('/api/v1/threads',          'Api\V1\ThreadsApi@index');
        $router->post('/api/v1/threads',         'Api\V1\ThreadsApi@store');
        $router->get('/api/v1/threads/{slug}',   'Api\V1\ThreadsApi@show');
        $router->post('/api/v1/threads/{slug}/reply', 'Api\V1\ThreadsApi@reply');
        $router->get('/api/v1/search',           'Api\V1\SearchApi@index');
        $router->get('/api/v1/search/suggest',   'Api\V1\SearchApi@suggest');

        // ── Admin CP ─────────────────────────────────────────────────────
        $router->get('/admin',                        'Admin\Dashboard@index');
        $router->post('/admin/quick-action',          'Admin\Dashboard@clearCache');

        $router->get('/admin/users',                  'Admin\UserManager@index');
        $router->post('/admin/users/update',          'Admin\UserManager@update');
        $router->post('/admin/users/ban',             'Admin\UserManager@ban');
        $router->post('/admin/users/delete',          'Admin\UserManager@delete');
        $router->get('/admin/users/export',           'Admin\UserManager@export');

        $router->get('/admin/content',                'Admin\ContentManager@threads');
        $router->get('/admin/categories',             'Admin\ContentManager@categories');
        $router->post('/admin/categories/create',     'Admin\ContentManager@createCategory');
        $router->post('/admin/categories/delete',     'Admin\ContentManager@deleteCategory');
        $router->post('/admin/categories/reorder',    'Admin\ContentManager@reorderCategories');
        $router->post('/admin/content/thread/delete', 'Admin\ContentManager@deleteThread');
        $router->post('/admin/content/thread/lock',   'Admin\ContentManager@lockThread');
        $router->post('/admin/content/thread/move',   'Admin\ContentManager@moveThread');
        $router->get('/admin/moderation',             'Admin\ContentManager@moderationQueue');
        $router->post('/admin/moderation/approve',    'Admin\ContentManager@approvePost');
        $router->post('/admin/moderation/delete',     'Admin\ContentManager@deletePost');

        $router->get('/admin/seo',                   'Admin\SEOManager@index');
        $router->post('/admin/seo/save',             'Admin\SEOManager@save');
        $router->post('/admin/seo/sitemap',          'Admin\SEOManager@generateSitemap');

        $router->get('/admin/updates',               'Admin\UpdateCenter@index');
        $router->post('/admin/updates/perform',      'Admin\UpdateCenter@perform');

        $router->get('/admin/settings',              'Admin\SettingsManager@index');
        $router->post('/admin/settings/save',        'Admin\SettingsManager@save');

        $router->get('/admin/analytics',             'Admin\Analytics@index');

        // ── SEO Endpoints ────────────────────────────────────────────────
        $router->get('/robots.txt',                  'Admin\SEOManager@robotsTxt');
        $router->get('/sitemap.xml',                 function() {
            $file = ROOT_PATH . '/sitemap.xml';
            header('Content-Type: application/xml');
            if (file_exists($file)) {
                readfile($file);
            } else {
                (new \Admin\SEOManager())->generateSitemap();
            }
        });

        // ── Plugin hook: let plugins register routes ──────────────────────
        EventEmitter::doAction('register_routes', $router);

        $router->dispatch($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);
    }
}
