<?php

namespace Modules\Forum;

use Core\Database;
use Core\Settings;

class HomeController {
    public function index(): void {
        $db         = Database::getInstance();
        $categories = \Core\Cache::remember('home_categories', 300, function() use ($db) {
            return $db->fetchAll(
                "SELECT c.*, (SELECT COUNT(*) FROM threads t WHERE t.category_id = c.id) AS thread_count
                 FROM categories c
                 ORDER BY c.position ASC"
            );
        });

        $theme = Settings::theme();
        require ROOT_PATH . "/themes/{$theme}/pages/home.php";
    }
}
