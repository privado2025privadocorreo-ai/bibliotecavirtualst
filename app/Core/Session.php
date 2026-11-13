<?php
declare(strict_types=1);

namespace App\Core;

final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        $cookiePath = Env::get('APP_BASE_PATH', '') ?? '';
        if ($cookiePath === '') {
            $cookiePath = str_replace('\\', '/', dirname((string)($_SERVER['SCRIPT_NAME'] ?? '/')));
            if ($cookiePath === '.' || $cookiePath === '') {
                $cookiePath = '/';
            }
        }
        session_set_cookie_params([
            'httponly' => true,
            'secure' => self::isHttps(),
            'samesite' => 'Lax',
            'path' => $cookiePath,
        ]);
        session_start();
    }

    public static function isHttps(): bool
    {
        return isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    }

    public static function destroy(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $parameters = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $parameters['path'],
                'domain' => $parameters['domain'],
                'secure' => $parameters['secure'],
                'httponly' => $parameters['httponly'],
                'samesite' => $parameters['samesite'] ?? 'Lax',
            ]);
        }
        if (!session_destroy()) {
            throw new \RuntimeException('No se pudo cerrar la sesión.');
        }
    }
}
