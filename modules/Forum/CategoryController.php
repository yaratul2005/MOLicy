<?php

namespace Modules\Forum;

use Core\Database;

class CategoryController {
    public function show($slug) {
        $db = Database::getInstance();
        
        // Fetch category
        $category = $db->fetch("SELECT * FROM categories WHERE slug = :slug", ['slug' => $slug]);
        if (!$category) {
            http_response_code(404);
            echo "Category not found.";
            return;
        }

        // Pagination setup (Cursor-based simulated via OFFSET/LIMIT for simplicity here, but would use ID > cursor for true cursor pagination)
        $threads = $db->fetchAll("SELECT t.*, u.username FROM threads t JOIN users u ON t.user_id = u.id WHERE t.category_id = :cat_id ORDER BY t.is_pinned DESC, t.updated_at DESC LIMIT 20", [
            'cat_id' => $category['id']
        ]);

        $theme = 'antigravity';
        $viewPath = ROOT_PATH . "/themes/{$theme}/pages/category.php";
        
        if (file_exists($viewPath)) {
            require $viewPath;
        } else {
            echo "Category view not found.";
        }
    }
}
