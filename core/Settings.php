<?php

namespace Core;

/**
 * Settings — Forum-wide configuration loader.
 * All settings are loaded once per request and cached in-process.
 * Admin can update them via the ACP Settings panel.
 */
class Settings {

    private static array $cache = [];
    private static bool  $loaded = false;

    /**
     * Load all settings from DB into memory.
     */
    public static function load(): void {
        if (self::$loaded) return;

        try {
            $db   = Database::getInstance();
            $rows = $db->fetchAll("SELECT setting_key, setting_value FROM settings");
            foreach ($rows as $row) {
                self::$cache[$row['setting_key']] = $row['setting_value'];
            }
        } catch (\Throwable $e) {
            // DB might not be ready — silently fail with defaults
        }

        self::$loaded = true;
    }

    /**
     * Get a setting value.
     */
    public static function get(string $key, mixed $default = ''): mixed {
        self::load();
        return self::$cache[$key] ?? $default;
    }

    /**
     * Get all settings as an associative array.
     */
    public static function all(): array {
        self::load();
        return self::$cache;
    }

    /**
     * Set/update a single setting in DB + cache.
     */
    public static function set(string $key, mixed $value): void {
        $db = Database::getInstance();
        $db->query(
            "INSERT INTO settings (setting_key, setting_value) VALUES (:k, :v)
             ON DUPLICATE KEY UPDATE setting_value = :v",
            ['k' => $key, 'v' => $value]
        );
        self::$cache[$key] = $value;
    }

    /**
     * Bulk save an associative array of settings.
     */
    public static function saveAll(array $data): void {
        foreach ($data as $key => $value) {
            self::set($key, $value);
        }
    }

    /**
     * Convenience: is registration enabled?
     */
    public static function registrationEnabled(): bool {
        return (bool)(int)self::get('registration_enabled', '1');
    }

    /**
     * Convenience: is maintenance mode active?
     */
    public static function maintenanceMode(): bool {
        return (bool)(int)self::get('maintenance_mode', '0');
    }

    /**
     * Site title.
     */
    public static function siteTitle(): string {
        return self::get('site_title', 'AntiGravity Forum');
    }

    /**
     * Site tagline.
     */
    public static function siteTagline(): string {
        return self::get('site_tagline', 'The most beautiful forum.');
    }

    /**
     * Active theme directory name.
     */
    public static function theme(): string {
        return self::get('site_theme', 'antigravity');
    }
}
