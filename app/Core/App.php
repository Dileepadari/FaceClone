<?php
namespace App\Core;

/**
 * Application container: configuration, paths and URL helpers.
 */
final class App
{
    private static array $config = [];
    private static string $basePath = '';

    public static function boot(string $basePath): void
    {
        self::$basePath = rtrim($basePath, '/');
        self::$config   = require self::$basePath . '/config/config.php';

        date_default_timezone_set(self::$config['app']['timezone'] ?? 'UTC');

        if (self::$config['app']['debug'] ?? false) {
            error_reporting(E_ALL);
            ini_set('display_errors', '1');
        } else {
            error_reporting(E_ALL & ~E_DEPRECATED);
            ini_set('display_errors', '0');
        }

        Database::boot(self::$config['db']);
    }

    public static function config(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return self::$config;
        }
        $node = self::$config;
        foreach (explode('.', $key) as $segment) {
            if (!is_array($node) || !array_key_exists($segment, $node)) {
                return $default;
            }
            $node = $node[$segment];
        }
        return $node;
    }

    public static function basePath(string $append = ''): string
    {
        return self::$basePath . ($append === '' ? '' : '/' . ltrim($append, '/'));
    }

    public static function isDebug(): bool
    {
        return (bool) self::config('app.debug', false);
    }

    /** Absolute URL for an application path. */
    public static function url(string $path = ''): string
    {
        return '/' . ltrim($path, '/');
    }

    /** URL for a file under public/, cache-busted by mtime. */
    public static function asset(string $path): string
    {
        $path = ltrim($path, '/');
        $file = self::basePath('public/' . $path);
        $ver  = is_file($file) ? filemtime($file) : 0;
        return '/' . $path . ($ver ? '?v=' . $ver : '');
    }
}
