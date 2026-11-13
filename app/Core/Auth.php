<?php
declare(strict_types=1);

namespace App\Core;

use App\Core\Database;

final class Auth
{
    private static bool $checked = false;
    private static ?array $cachedUser = null;

    public static function user(): ?array
    {
        if (self::$checked) {
            return self::$cachedUser;
        }
        self::$checked = true;
        $user = $_SESSION['staff'] ?? null;
        if (!is_array($user)) {
            return null;
        }

        $statement = Database::connection()->prepare(
            'SELECT us.id, us.institucion_id, us.rol_id, us.nombre, us.apellidos,
                    us.correo, r.nombre AS rol
             FROM usuarios_sistema us
             INNER JOIN roles r ON r.id = us.rol_id
             WHERE us.id = :id AND us.institucion_id = :institution AND us.activo = 1
             LIMIT 1'
        );
        $statement->execute([
            'id' => (int)($user['id'] ?? 0),
            'institution' => (int)($user['institucion_id'] ?? 0),
        ]);
        $current = $statement->fetch();
        if ($current === false) {
            unset($_SESSION['staff']);
            session_regenerate_id(true);
            $_SESSION['_flash'] = [
                'type' => 'error',
                'message' => 'La cuenta ya no está activa. Inicia sesión con una cuenta autorizada.',
            ];
            return null;
        }

        self::$cachedUser = [
            'id' => (int)$current['id'],
            'institucion_id' => (int)$current['institucion_id'],
            'rol_id' => (int)$current['rol_id'],
            'nombre' => (string)$current['nombre'],
            'apellidos' => (string)$current['apellidos'],
            'correo' => (string)$current['correo'],
            'rol' => (string)$current['rol'],
        ];
        $_SESSION['staff'] = self::$cachedUser;
        return self::$cachedUser;
    }

    public static function requireRole(string $role): void
    {
        $user = self::user();
        if ($user === null) {
            View::redirect('login');
        }

        if (($user['rol'] ?? '') !== $role) {
            http_response_code(403);
            View::render('errors/403', ['title' => 'Acceso denegado']);
            exit;
        }
    }
}
