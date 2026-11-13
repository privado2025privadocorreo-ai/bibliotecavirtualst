<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\View;
use App\Models\Staff;
use PDOException;
use RuntimeException;

final class AccountController extends Controller
{
    public function password(string $method): void
    {
        $staff = Auth::user();
        if ($staff === null) {
            View::redirect('login');
        }

        if ($method === 'POST') {
            $this->requirePost();
            $currentPassword = (string)($_POST['contrasena_actual'] ?? '');
            $newPassword = (string)($_POST['contrasena_nueva'] ?? '');
            $confirmation = (string)($_POST['confirmar_contrasena'] ?? '');

            if ($currentPassword === '') {
                $this->flash('error', 'Escribe tu contraseña actual.');
            } elseif (strlen($newPassword) < 8) {
                $this->flash('error', 'La nueva contraseña debe tener al menos 8 caracteres.');
            } elseif ($newPassword !== $confirmation) {
                $this->flash('error', 'La nueva contraseña y su confirmación no coinciden.');
            } else {
                try {
                    (new Staff())->changeOwnPassword(
                        (int)$staff['id'],
                        (int)$staff['institucion_id'],
                        $currentPassword,
                        $newPassword
                    );
                    if (!session_regenerate_id(true)) {
                        throw new RuntimeException(
                            'La contraseña se cambió, pero no se pudo renovar la sesión. Cierra sesión e inicia de nuevo.'
                        );
                    }
                    $this->flash('success', 'Tu contraseña fue cambiada.');
                } catch (PDOException $exception) {
                    error_log('Password change failed: ' . $exception->getMessage());
                    $this->flash('error', 'No se pudo cambiar la contraseña. Inténtalo de nuevo más tarde.');
                } catch (RuntimeException $exception) {
                    $this->flash('error', $exception->getMessage());
                }
            }

            View::redirect('account/password');
        }

        if ($method !== 'GET') {
            http_response_code(405);
            header('Allow: GET, POST');
            return;
        }

        $this->render('account/password', ['title' => 'Cambiar contraseña']);
    }
}
