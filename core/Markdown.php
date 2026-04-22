<?php

namespace Core;

class Markdown {
    public static function render($text) {
        // Escaping output first to prevent XSS, but allowing our generated HTML
        $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

        // Bold
        $text = preg_replace('/\*\*(.*?)\*\*/s', '<strong>$1</strong>', $text);
        
        // Italic
        $text = preg_replace('/\*([^\*]+)\*/', '<em>$1</em>', $text);
        
        // Images
        $text = preg_replace('/!\[(.*?)\]\((.*?)\)/', '<img src="$2" alt="$1" style="max-width: 100%; border-radius: var(--radius-sm); margin: 10px 0;">', $text);
        
        // Links
        $text = preg_replace('/\[(.*?)\]\((.*?)\)/', '<a href="$2" target="_blank" style="color: var(--color-cyan); text-decoration: none;">$1</a>', $text);
        
        // Code Blocks
        $text = preg_replace('/```(.*?)```/s', '<pre style="background: var(--color-surface-1); padding: 15px; border-radius: var(--radius-sm); overflow-x: auto;"><code class="language-none">$1</code></pre>', $text);

        // Inline Code
        $text = preg_replace('/`(.*?)`/', '<code style="background: var(--color-surface-1); padding: 2px 5px; border-radius: 4px;">$1</code>', $text);

        // Convert newlines to breaks
        $text = nl2br($text);

        return $text;
    }
}
