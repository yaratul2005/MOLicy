<?php

namespace Modules\Forum;

use Core\Database;

class HomeController {
    public function index() {
        $db = Database::getInstance();
        $categories = $db->fetchAll("SELECT * FROM categories ORDER BY position ASC");

        // Load the view from the active theme
        $theme = 'antigravity'; // Could be dynamic
        $viewPath = ROOT_PATH . "/themes/{$theme}/pages/home.php";
        
        if (file_exists($viewPath)) {
            require $viewPath;
        } else {
            echo "Theme page not found.";
        }
    }
}
