<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Session;
use App\Core\View;
use App\Models\Institution;
use App\Models\Staff;
use DomainException;
use PDOException;
use Throwable;

final class AuthController extends Controller
{
    public function login(string $method): void
    {
        if ($method !== 'GET' && $method !== 'POST') {
            http_response_code(405);
            header('Allow: GET, POST');
            return;
        }

        if (!(new Institution())->isConfigured()) {
            View::redirect('setup');
        }

        if ($method === 'POST') {
            $this->requirePost();
            $email = trim((string)($_POST['correo'] ?? ''));
            $password = (string)($_POST['contrasena'] ?? '');

            if ($email === '' || $password === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                $this->flash('error', 'Escribe un correo válido y tu contraseña.');
                View::redirect('login');
            }

            try {
                $staffModel = new Staff();
                $staff = $staffModel->findByEmail($email);
                if ($staff === null || (int)$staff['activo'] !== 1
                    || !password_verify($password, (string)$staff['contrasena_hash'])) {
                    $this->flash('error', 'No se pudo iniciar sesión. Revisa tus datos o consulta al administrador.');
                    View::redirect('login');
                }

                if (password_needs_rehash((string)$staff['contrasena_hash'], PASSWORD_DEFAULT)) {
                    $staffModel->updatePasswordHash(
                        (int)$staff['id'],
                        password_hash($password, PASSWORD_DEFAULT)
                    );
                }
                $staffModel->recordLogin((int)$staff['id']);
                session_regenerate_id(true);
                $_SESSION['staff'] = [
                    'id' => (int)$staff['id'],
                    'institucion_id' => (int)$staff['institucion_id'],
                    'rol_id' => (int)$staff['rol_id'],
                    'nombre' => (string)$staff['nombre'],
                    'apellidos' => (string)$staff['apellidos'],
                    'correo' => (string)$staff['correo'],
                    'rol' => (string)$staff['rol'],
                ];
                View::redirect('dashboard');
            } catch (PDOException $exception) {
                error_log('Login database error: ' . $exception->getMessage());
                $this->flash('error', 'No se pudo validar el acceso. Revisa la conexión con la base de datos.');
                View::redirect('login');
            }
        }

        if (Auth::user() !== null) {
            View::redirect('dashboard');
        }
        View::render('auth/login', ['title' => 'Iniciar sesión'], false);
    }

    public function setup(string $method): void
    {
        if (!$this->isLocalRequest()) {
            http_response_code(404);
            return;
        }

        if (isset($_SESSION['staff'])) {
            View::redirect('dashboard');
        }

        if ($method !== 'GET' && $method !== 'POST') {
            http_response_code(405);
            header('Allow: GET, POST');
            return;
        }

        $institution = new Institution();
        if ($institution->isConfigured()) {
            View::redirect('login');
        }

        if ($method === 'POST') {
            $this->requirePost();

            $institutionName = trim((string)($_POST['institution_name'] ?? ''));
            $institutionEmail = trim((string)($_POST['institution_email'] ?? ''));
            $institutionAddress = trim((string)($_POST['institution_address'] ?? ''));
            $adminFirstName = trim((string)($_POST['admin_first_name'] ?? ''));
            $adminLastName = trim((string)($_POST['admin_last_name'] ?? ''));
            $adminEmail = trim((string)($_POST['admin_email'] ?? ''));
            $password = (string)($_POST['password'] ?? '');
            $passwordConfirmation = (string)($_POST['password_confirmation'] ?? '');

            $errors = [];
            if ($institutionName === '' || strlen($institutionName) > 150) {
                $errors[] = 'Escribe el nombre de la institución (máximo 150 caracteres).';
            }
            if (filter_var($institutionEmail, FILTER_VALIDATE_EMAIL) === false
                || strlen($institutionEmail) > 150) {
                $errors[] = 'Escribe un correo válido para la institución (máximo 150 caracteres).';
            }
            if (strlen($institutionAddress) > 255) {
                $errors[] = 'La dirección no puede superar 255 caracteres.';
            }
            if ($adminFirstName === '' || strlen($adminFirstName) > 100
                || $adminLastName === '' || strlen($adminLastName) > 100) {
                $errors[] = 'Escribe el nombre y los apellidos del administrador (máximo 100 caracteres cada uno).';
            }
            if (filter_var($adminEmail, FILTER_VALIDATE_EMAIL) === false
                || strlen($adminEmail) > 150) {
                $errors[] = 'Escribe un correo válido para la cuenta administradora (máximo 150 caracteres).';
            }
            if (strlen($password) < 8) {
                $errors[] = 'La contraseña debe tener al menos 8 caracteres.';
            }
            if ($password !== $passwordConfirmation) {
                $errors[] = 'Las contraseñas no coinciden.';
            }
            if ($errors !== []) {
                $this->flash('error', implode(' ', $errors));
                View::redirect('setup');
            }

            try {
                $institution->createInitialSetup(
                    $institutionName,
                    $institutionEmail,
                    $institutionAddress !== '' ? $institutionAddress : null,
                    $adminFirstName,
                    $adminLastName,
                    $adminEmail,
                    $password
                );
            } catch (DomainException $exception) {
                $this->flash('error', $exception->getMessage());
                View::redirect('login');
            } catch (Throwable $exception) {
                error_log('Initial local setup failed: ' . $exception->getMessage());
                $this->flash('error', 'No se pudo completar la configuración inicial. Revisa los registros e inténtalo de nuevo.');
                View::redirect('setup');
            }

            $this->flash('success', 'Configuración inicial completada. Ya puedes iniciar sesión.');
            View::redirect('login');
        }

        View::render('auth/setup', ['title' => 'Configuración inicial'], false);
    }

    private function isLocalRequest(): bool
    {
        $remoteAddress = strtolower((string)($_SERVER['REMOTE_ADDR'] ?? ''));
        return $remoteAddress === '::1'
            || str_starts_with($remoteAddress, '127.')
            || str_starts_with($remoteAddress, '::ffff:127.');
    }

    public function logout(): void
    {
        $this->requirePost();
        Session::destroy();
        View::redirect('login');
    }
}
