<?php
// includes/cache.php - Simple caching layer
class Cache {
    private static $cache = [];
    private static $ttl = [];

    public static function set(string $key, $value, int $ttl = 3600): void {
        self::$cache[$key] = $value;
        self::$ttl[$key] = time() + $ttl;
    }

    public static function get(string $key) {
        if (!isset(self::$cache[$key])) return null;
        if (time() > self::$ttl[$key]) {
            unset(self::$cache[$key], self::$ttl[$key]);
            return null;
        }
        return self::$cache[$key];
    }

    public static function has(string $key): bool {
        return self::get($key) !== null;
    }

    public static function forget(string $key): void {
        unset(self::$cache[$key], self::$ttl[$key]);
    }

    public static function flush(): void {
        self::$cache = [];
        self::$ttl = [];
    }
}
