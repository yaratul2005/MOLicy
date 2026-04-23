<?php

namespace Admin;

use Core\Database;
use Core\Middleware;
use Core\Cache;

class SEOManager {

    public function __construct() {
        Middleware::requireAdmin();
    }

    public function index(): void {
        $db   = Database::getInstance();
        $meta = [];
        $rows = $db->fetchAll("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'seo_%'");
        foreach ($rows as $r) {
            $meta[str_replace('seo_', '', $r['setting_key'])] = $r['setting_value'];
        }
        require ROOT_PATH . '/admin/views/seo.php';
    }

    public function save(): void {
        Middleware::verifyCSRF();
        $db = Database::getInstance();

        $fields = [
            'seo_site_title'       => substr(trim($_POST['site_title'] ?? ''), 0, 70),
            'seo_meta_description' => substr(trim($_POST['meta_description'] ?? ''), 0, 160),
            'seo_og_image'         => filter_var(trim($_POST['og_image'] ?? ''), FILTER_VALIDATE_URL) ?: '',
            'seo_google_analytics' => preg_replace('/[^A-Z0-9-]/', '', strtoupper(trim($_POST['google_analytics'] ?? ''))),
            'seo_robots_txt'       => trim($_POST['robots_txt'] ?? ''),
            'seo_noindex_search'   => isset($_POST['noindex_search']) ? '1' : '0',
            'seo_canonical_domain' => filter_var(trim($_POST['canonical_domain'] ?? ''), FILTER_VALIDATE_URL) ?: '',
        ];

        foreach ($fields as $key => $value) {
            $existing = $db->fetch("SELECT id FROM settings WHERE setting_key = :k", ['k' => $key]);
            if ($existing) {
                $db->update('settings', ['setting_value' => $value], ['setting_key' => $key]);
            } else {
                $db->insert('settings', ['setting_key' => $key, 'setting_value' => $value]);
            }
        }

        Cache::forget('seo_settings');
        Cache::forget('sitemap_xml');

        header('Location: /admin/seo?saved=1');
    }

    /**
     * Generate XML sitemap on demand.
     */
    public function generateSitemap(): void {
        $db      = Database::getInstance();
        $baseUrl = $this->getSetting('canonical_domain', (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        // Home
        $xml .= $this->sitemapUrl($baseUrl . '/', '1.0', 'daily');

        // Categories
        $cats = $db->fetchAll("SELECT slug, created_at FROM categories");
        foreach ($cats as $c) {
            $xml .= $this->sitemapUrl("{$baseUrl}/category/{$c['slug']}", '0.8', 'weekly', $c['created_at']);
        }

        // Threads (paginated if large)
        $threads = $db->fetchAll(
            "SELECT slug, updated_at FROM threads WHERE is_pinned = 0 ORDER BY updated_at DESC LIMIT 5000"
        );
        foreach ($threads as $t) {
            $xml .= $this->sitemapUrl("{$baseUrl}/thread/{$t['slug']}", '0.6', 'weekly', $t['updated_at']);
        }

        $xml .= '</urlset>';

        // Cache and save
        $sitemapPath = ROOT_PATH . '/sitemap.xml';
        file_put_contents($sitemapPath, $xml);
        Cache::set('sitemap_xml', $xml, 3600);

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'url' => $baseUrl . '/sitemap.xml']);
    }

    /**
     * Get robots.txt content.
     */
    public function robotsTxt(): void {
        $custom  = $this->getSetting('robots_txt', '');
        $baseUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];

        header('Content-Type: text/plain');
        echo "User-agent: *\n";
        echo "Disallow: /admin/\n";
        echo "Disallow: /install/\n";
        echo "Disallow: /storage/\n";
        echo "Disallow: /api/v1/\n";
        echo "Allow: /\n\n";
        echo "Sitemap: {$baseUrl}/sitemap.xml\n\n";
        if ($custom) echo $custom . "\n";
    }

    /**
     * Output JSON-LD schema for a given page type.
     */
    public static function schema(string $type, array $data): string {
        $baseUrl   = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
        $siteName  = self::staticSetting('site_name', 'AntiGravity Forum');
        $siteTitle = self::staticSetting('seo_site_title', $siteName);

        switch ($type) {
            case 'website':
                $schema = [
                    '@context' => 'https://schema.org',
                    '@type'    => 'WebSite',
                    'name'     => $siteName,
                    'url'      => $baseUrl,
                    'potentialAction' => [
                        '@type'       => 'SearchAction',
                        'target'      => "{$baseUrl}/search?q={search_term_string}",
                        'query-input' => 'required name=search_term_string',
                    ],
                ];
                break;

            case 'thread':
                $schema = [
                    '@context'   => 'https://schema.org',
                    '@type'      => 'DiscussionForumPosting',
                    'headline'   => $data['title'] ?? '',
                    'author'     => ['@type' => 'Person', 'name' => $data['author'] ?? ''],
                    'datePublished' => $data['created_at'] ?? '',
                    'dateModified'  => $data['updated_at'] ?? $data['created_at'] ?? '',
                    'url'        => $baseUrl . '/thread/' . ($data['slug'] ?? ''),
                    'isPartOf'   => ['@type' => 'WebPage', 'url' => $baseUrl],
                ];
                break;

            case 'breadcrumb':
                $items = [];
                foreach (($data['items'] ?? []) as $i => $item) {
                    $items[] = [
                        '@type'    => 'ListItem',
                        'position' => $i + 1,
                        'name'     => $item['name'],
                        'item'     => $baseUrl . $item['url'],
                    ];
                }
                $schema = [
                    '@context'        => 'https://schema.org',
                    '@type'           => 'BreadcrumbList',
                    'itemListElement' => $items,
                ];
                break;

            case 'person':
                $schema = [
                    '@context'    => 'https://schema.org',
                    '@type'       => 'Person',
                    'name'        => $data['username'] ?? '',
                    'url'         => $baseUrl . '/u/' . ($data['username'] ?? ''),
                    'description' => $data['bio'] ?? '',
                ];
                break;

            default:
                return '';
        }

        return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES) . '</script>';
    }

    private function sitemapUrl(string $url, string $priority, string $changefreq, ?string $lastmod = null): string {
        $xml = "  <url>\n";
        $xml .= "    <loc>" . htmlspecialchars($url) . "</loc>\n";
        if ($lastmod) $xml .= "    <lastmod>" . date('Y-m-d', strtotime($lastmod)) . "</lastmod>\n";
        $xml .= "    <changefreq>{$changefreq}</changefreq>\n";
        $xml .= "    <priority>{$priority}</priority>\n";
        $xml .= "  </url>\n";
        return $xml;
    }

    private function getSetting(string $key, string $default = ''): string {
        return \Core\Settings::get($key, $default);
    }

    private static function staticSetting(string $key, string $default = ''): string {
        return \Core\Settings::get($key, $default);
    }
}
