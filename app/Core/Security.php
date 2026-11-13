<?php
declare(strict_types=1);

namespace App\Core;

final class Security
{
    public static function token(): string
    {
        if (!isset($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }

        return (string)$_SESSION['_csrf'];
    }

    public static function validToken(?string $token): bool
    {
        return $token !== null
            && isset($_SESSION['_csrf'])
            && hash_equals((string)$_SESSION['_csrf'], $token);
    }

    public static function escape(string|int|float|null $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function url(string $route, array $parameters = []): string
    {
        $query = http_build_query(array_merge(['r' => $route], $parameters));
        return self::basePath() . '/index.php?' . $query;
    }

    public static function asset(string $path): string
    {
        return self::basePath() . '/' . ltrim($path, '/');
    }

    private static function basePath(): string
    {
        $basePath = Env::get('APP_BASE_PATH', '') ?? '';
        if ($basePath === '') {
            $basePath = str_replace('\\', '/', dirname((string)($_SERVER['SCRIPT_NAME'] ?? '')));
            $basePath = $basePath === '/' || $basePath === '.' ? '' : rtrim($basePath, '/');
        }
        return rtrim($basePath, '/');
    }
}
