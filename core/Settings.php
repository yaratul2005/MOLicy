<?php

namespace Core;

/**
 * Settings — manages forum configuration with persistent caching
 */
class Settings {
    private static ?array $data = null;
    private static string $cacheKey = 'forum_settings_all';

    /**
     * Get all settings, using cache if available.
     */
    public static function all(): array {
        if (self::$data !== null) {
            return self::$data;
        }

        // Try cache first
        $cached = Cache::get(self::$cacheKey);
        if ($cached && is_array($cached)) {
            self::$data = $cached;
            return self::$data;
        }

        // Fetch from DB
        try {
            $db = Database::getInstance();
            $rows = $db->fetchAll("SELECT setting_key, setting_value FROM settings");
            self::$data = [];
            foreach ($rows as $r) {
                self::$data[$r['setting_key']] = $r['setting_value'];
            }
            
            // Cache for 1 hour
            Cache::set(self::$cacheKey, self::$data, 3600);
        } catch (\Throwable $e) {
            error_log("Settings load error: " . $e->getMessage());
            return [];
        }

        return self::$data;
    }

    /**
     * Get a specific setting by key.
     */
    public static function get(string $key, $default = null) {
        $all = self::all();
        return $all[$key] ?? $default;
    }

    /**
     * Save a single setting and clear cache.
     */
    public static function set(string $key, $value): void {
        $db = Database::getInstance();
        $existing = $db->fetch("SELECT id FROM settings WHERE setting_key = :k", ['k' => $key]);
        
        if ($existing) {
            $db->query("UPDATE settings SET setting_value = :v WHERE setting_key = :k", ['v' => $value, 'k' => $key]);
        } else {
            $db->insert('settings', ['setting_key' => $key, 'setting_value' => $value]);
        }
        
        self::clearCache();
    }

    /**
     * Save multiple settings at once and clear cache.
     */
    public static function saveAll(array $data): void {
        $db = Database::getInstance();
        foreach ($data as $key => $value) {
            $existing = $db->fetch("SELECT id FROM settings WHERE setting_key = :k", ['k' => $key]);
            if ($existing) {
                $db->query("UPDATE settings SET setting_value = :v WHERE setting_key = :k", ['v' => (string)$value, 'k' => $key]);
            } else {
                $db->insert('settings', ['setting_key' => $key, 'setting_value' => (string)$value]);
            }
        }
        self::clearCache();
    }

    public static function clearCache(): void {
        self::$data = null;
        Cache::forget(self::$cacheKey);
    }

    // ── Shorthand Helpers ─────────────────────────────────────────────

    public static function siteTitle(): string {
        return self::get('site_title', 'AntiGravity Forum');
    }

    public static function siteTagline(): string {
        return self::get('site_tagline', '');
    }

    public static function maintenanceMode(): bool {
        return self::get('maintenance_mode') === '1';
    }
}
