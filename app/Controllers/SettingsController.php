<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Session;
use App\Core\View;
use App\Models\Settings;
use App\Models\Staff;
use PDOException;
use RuntimeException;

final class SettingsController extends Controller
{
    public function index(string $method): void
    {
        Auth::requireRole('Administrador');
        $staffUser = Auth::user();
        $institutionId = (int)$staffUser['institucion_id'];
        $settingsModel = new Settings();
        $staffModel = new Staff();

        if ($method === 'POST') {
            $this->requirePost();
            $action = (string)($_POST['action'] ?? '');
            try {
                if ($action === 'settings') {
                    $values = $this->settingInput();
                    if ($values === null) {
                        $this->flash('error', 'Los días de préstamo y el límite deben ser enteros positivos; los días de alerta pueden ser cero.');
                    } else {
                        $settingsModel->update($values, $institutionId);
                        $this->flash('success', 'Parámetros de préstamo actualizados.');
                    }
                } elseif ($action === 'staff-save') {
                    $this->saveStaff($staffModel, $institutionId, (int)$staffUser['id']);
                } elseif ($action === 'staff-delete') {
                    $this->deleteStaff($staffModel, $institutionId, (int)$staffUser['id']);
                } elseif ($action === 'institution') {
                    $this->saveInstitution($staffModel, $institutionId);
                } elseif ($action === 'institution-delete') {
                    $this->deleteInstitution($staffModel, $institutionId);
                } else {
                    http_response_code(400);
                    $this->flash('error', 'La acción solicitada no es válida.');
                }
            } catch (PDOException $exception) {
                error_log('Settings update failed: ' . $exception->getMessage());
                $this->flash('error', 'No se pudieron guardar los cambios. Revisa los datos únicos y la conexión.');
            } catch (RuntimeException $exception) {
                $this->flash('error', $exception->getMessage());
            }
            View::redirect('settings');
        }

        if ($method !== 'GET') {
            http_response_code(405);
            header('Allow: GET, POST');
            return;
        }

        try {
            $this->render('settings/index', [
                'title' => 'Configuración',
                'settings' => $settingsModel->all($institutionId),
                'staff' => $staffModel->all($institutionId),
                'roles' => $staffModel->roles(),
                'institution' => $staffModel->institution($institutionId),
                'currentStaffId' => (int)$staffUser['id'],
            ]);
        } catch (\Throwable $exception) {
            error_log('Settings page load failed: ' . $exception->getMessage());
            http_response_code(503);
            $this->flash('error', 'No se pudo cargar la configuración.');
            $this->render('settings/index', [
                'title' => 'Configuración',
                'settings' => [],
                'staff' => [],
                'roles' => [],
                'institution' => [],
                'currentStaffId' => (int)$staffUser['id'],
            ]);
        }
    }

    private function settingInput(): ?array
    {
        $days = filter_var($_POST['dias_prestamo'] ?? null, FILTER_VALIDATE_INT);
        $limit = filter_var($_POST['limite_ejemplares'] ?? null, FILTER_VALIDATE_INT);
        $alert = filter_var($_POST['dias_alerta_vencimiento'] ?? null, FILTER_VALIDATE_INT);
        if ($days === false || $days < 1 || $limit === false || $limit < 1
            || $alert === false || $alert < 0) {
            return null;
        }
        return [
            'dias_prestamo' => $days,
            'limite_ejemplares' => $limit,
            'dias_alerta_vencimiento' => $alert,
        ];
    }

    private function saveStaff(Staff $model, int $institutionId, int $currentId): void
    {
        $id = $this->positiveId($_POST['id'] ?? null);
        $roleId = $this->positiveId($_POST['rol_id'] ?? null);
        $name = trim((string)($_POST['nombre'] ?? ''));
        $lastName = trim((string)($_POST['apellidos'] ?? ''));
        $email = trim((string)($_POST['correo'] ?? ''));
        $password = (string)($_POST['contrasena'] ?? '');
        if ($name === '' || strlen($name) > 100 || $lastName === '' || strlen($lastName) > 100
            || filter_var($email, FILTER_VALIDATE_EMAIL) === false || strlen($email) > 150
            || $roleId === null || ($id === null && strlen($password) < 8)
            || ($password !== '' && strlen($password) < 8)) {
            $this->flash('error', 'Completa nombres, apellidos, correo, rol y una contraseña de al menos 8 caracteres para crear o restablecer el acceso.');
            return;
        }

        $roleIds = array_map('intval', array_column($model->roles(), 'id'));
        if (!in_array($roleId, $roleIds, true)) {
            $this->flash('error', 'Selecciona un rol existente.');
            return;
        }
        if ($id !== null) {
            $existing = $model->find($id, $institutionId);
            if ($existing === null) {
                $this->flash('error', 'El usuario del sistema no existe en esta institución.');
                return;
            }
            if ($id === $currentId && (int)$existing['rol_id'] !== $roleId) {
                $this->flash('error', 'No puedes cambiar tu propio rol desde esta sesión.');
                return;
            }
            if ($id === $currentId && $password !== '') {
                $this->flash('error', 'Para cambiar tu contraseña, utiliza el formulario «Cambiar mi contraseña».');
                return;
            }
            $roleNames = [];
            foreach ($model->roles() as $role) {
                $roleNames[(int)$role['id']] = (string)$role['nombre'];
            }
            if ($existing['rol'] === 'Administrador'
                && ($roleNames[$roleId] ?? '') !== 'Administrador'
                && (int)$existing['activo'] === 1
                && $model->countActiveAdministrators($institutionId) <= 1) {
                $this->flash('error', 'Debe permanecer al menos una cuenta administradora activa.');
                return;
            }
        }
        $model->save($id, [
            'institucion_id' => $institutionId,
            'rol_id' => $roleId,
            'nombre' => $name,
            'apellidos' => $lastName,
            'correo' => $email,
            'contrasena' => $password,
        ], $institutionId);
        $this->flash('success', $id === null
            ? 'Cuenta de personal creada.'
            : ($password === '' ? 'Cuenta de personal actualizada.' : 'Cuenta actualizada y contraseña restablecida.'));
    }

    private function deleteStaff(Staff $model, int $institutionId, int $currentId): void
    {
        $id = $this->positiveId($_POST['id'] ?? null);
        if ($id === null) {
            $this->flash('error', 'El identificador de la cuenta no es válido.');
            return;
        }
        $model->delete($id, $institutionId, $currentId);
        $this->flash('success', 'La cuenta y su perfil se eliminaron de la base de datos.');
    }

    private function deleteInstitution(Staff $model, int $institutionId): void
    {
        if ((string)($_POST['confirmar_eliminacion'] ?? '') !== 'ELIMINAR') {
            $this->flash('error', 'Escribe ELIMINAR para confirmar la eliminación permanente de la institución.');
            return;
        }

        $logo = $model->deleteInstitution($institutionId);
        if ($logo !== null && str_starts_with($logo, 'uploads/logos/')) {
            $filename = basename($logo);
            if ($filename !== '.' && $filename !== '..') {
                $logoFile = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'uploads'
                    . DIRECTORY_SEPARATOR . 'logos' . DIRECTORY_SEPARATOR . $filename;
                if (is_file($logoFile) && !unlink($logoFile)) {
                    error_log('Deleted institution logo could not be removed: ' . $logoFile);
                }
            }
        }

        Session::destroy();
        View::render('auth/institution-deleted', ['title' => 'Institución eliminada'], false);
        exit;
    }

    private function saveInstitution(Staff $model, int $institutionId): void
    {
        $name = trim((string)($_POST['nombre'] ?? ''));
        $email = trim((string)($_POST['correo'] ?? ''));
        $address = trim((string)($_POST['direccion'] ?? ''));
        $current = $model->institution($institutionId);
        if ($name === '' || strlen($name) > 150
            || filter_var($email, FILTER_VALIDATE_EMAIL) === false || strlen($email) > 150
            || strlen($address) > 255) {
            $this->flash('error', 'Nombre y correo institucional son obligatorios; revisa también las longitudes máximas.');
            return;
        }

        $logo = (string)($current['logotipo'] ?? '');
        $upload = $_FILES['logotipo'] ?? null;
        if (is_array($upload) && (int)($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $logo = $this->storeLogo($upload);
        }
        $model->updateInstitution([
            'id' => $institutionId,
            'nombre' => $name,
            'correo' => $email,
            'direccion' => $address !== '' ? $address : null,
            'logotipo' => $logo !== '' ? $logo : null,
        ]);
        $this->flash('success', 'Datos institucionales actualizados.');
    }

    private function storeLogo(array $upload): string
    {
        if (($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK
            || !isset($upload['tmp_name']) || !is_uploaded_file((string)$upload['tmp_name'])
            || (int)($upload['size'] ?? 0) > 2 * 1024 * 1024) {
            throw new RuntimeException('El logotipo no se cargó correctamente o supera 2 MB.');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file((string)$upload['tmp_name']);
        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];
        if (!is_string($mime) || !isset($extensions[$mime])
            || getimagesize((string)$upload['tmp_name']) === false) {
            throw new RuntimeException('El logotipo debe ser una imagen PNG, JPG o WebP válida.');
        }

        $directory = dirname(__DIR__, 2) . '/uploads/logos';
        if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
            throw new RuntimeException('No se pudo preparar la carpeta de logotipos.');
        }
        $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
        if (!move_uploaded_file((string)$upload['tmp_name'], $directory . '/' . $filename)) {
            throw new RuntimeException('No se pudo guardar el logotipo.');
        }
        return 'uploads/logos/' . $filename;
    }

    private function positiveId($value): ?int
    {
        $id = filter_var($value, FILTER_VALIDATE_INT);
        return $id !== false && $id !== null && $id > 0 ? $id : null;
    }
}
