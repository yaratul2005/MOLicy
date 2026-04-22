<?php

namespace Core;

class Cache {
    private static string $cacheDir;
    private static int $defaultTtl = 300; // 5 minutes

    public static function init(): void {
        self::$cacheDir = ROOT_PATH . '/storage/cache/';
        if (!is_dir(self::$cacheDir)) {
            mkdir(self::$cacheDir, 0755, true);
        }
    }

    public static function get(string $key): mixed {
        // L1: APCu
        if (function_exists('apcu_fetch')) {
            $val = apcu_fetch('agf_' . $key, $success);
            if ($success) return $val;
        }
        // L2: File cache
        $file = self::filePath($key);
        if (!file_exists($file)) return null;
        $data = unserialize(file_get_contents($file));
        if ($data['expires'] < time()) {
            unlink($file);
            return null;
        }
        return $data['value'];
    }

    public static function set(string $key, mixed $value, int $ttl = 0): void {
        if (!isset(self::$cacheDir)) self::init();
        $ttl = $ttl ?: self::$defaultTtl;
        // L1: APCu
        if (function_exists('apcu_store')) {
            apcu_store('agf_' . $key, $value, $ttl);
        }
        // L2: File cache
        $data = serialize(['value' => $value, 'expires' => time() + $ttl]);
        file_put_contents(self::filePath($key), $data, LOCK_EX);
    }

    public static function forget(string $key): void {
        if (function_exists('apcu_delete')) {
            apcu_delete('agf_' . $key);
        }
        $file = self::filePath($key);
        if (file_exists($file)) unlink($file);
    }

    public static function flush(): void {
        if (function_exists('apcu_clear_cache')) apcu_clear_cache();
        $files = glob(self::$cacheDir . '*.cache');
        if ($files) foreach ($files as $f) unlink($f);
    }

    public static function remember(string $key, int $ttl, callable $callback): mixed {
        $val = self::get($key);
        if ($val !== null) return $val;
        $val = $callback();
        self::set($key, $val, $ttl);
        return $val;
    }

    private static function filePath(string $key): string {
        return self::$cacheDir . md5($key) . '.cache';
    }
}
