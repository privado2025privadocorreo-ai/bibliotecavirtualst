<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\View;
use App\Models\Patron;
use PDOException;
use RuntimeException;

final class PatronController extends Controller
{
    public function index(): void
    {
        $staff = Auth::user();
        $model = new Patron();
        $term = trim((string)($_GET['q'] ?? ''));
        try {
            $this->render('patrons/index', [
                'title' => 'Usuarios',
                'patrons' => $model->list($term, (int)$staff['institucion_id']),
                'term' => $term,
            ]);
        } catch (PDOException $exception) {
            error_log('Patron listing failed: ' . $exception->getMessage());
            http_response_code(503);
            $this->flash('error', 'No se pudo cargar la lista de usuarios.');
            $this->render('patrons/index', ['title' => 'Usuarios', 'patrons' => [], 'term' => $term]);
        }
    }

    public function create(string $method): void
    {
        $staff = Auth::user();
        $model = new Patron();
        $id = $this->positiveId($method === 'POST' ? ($_POST['id'] ?? null) : ($_GET['id'] ?? null));

        if ($method === 'POST') {
            $this->requirePost();
            $data = $this->input();
            $errors = $this->validate($data);
            if ($id !== null && $model->find($id, (int)$staff['institucion_id']) === null) {
                http_response_code(404);
                View::render('errors/404', ['title' => 'Usuario no encontrado']);
                return;
            }
            if ($errors === []) {
                try {
                    $model->save($id, $data, (int)$staff['institucion_id']);
                    $this->flash('success', $id === null ? 'Usuario registrado.' : 'Cambios guardados.');
                    View::redirect('patrons');
                } catch (PDOException $exception) {
                    error_log('Patron save failed: ' . $exception->getMessage());
                    $this->flash('error', 'No se pudo guardar. Revisa si el documento o correo ya están registrados.');
                }
            }
            $this->render('patrons/form', [
                'title' => $id === null ? 'Registrar usuario' : 'Editar usuario',
                'patron' => $data,
                'grades' => $model->grades(),
                'errors' => $errors,
            ]);
            return;
        }

        if ($method !== 'GET') {
            http_response_code(405);
            header('Allow: GET, POST');
            return;
        }
        $patron = $id === null ? [] : $model->find($id, (int)$staff['institucion_id']);
        if ($id !== null && $patron === null) {
            http_response_code(404);
            View::render('errors/404', ['title' => 'Usuario no encontrado']);
            return;
        }
        $this->render('patrons/form', [
            'title' => $id === null ? 'Registrar usuario' : 'Editar usuario',
            'patron' => $patron ?? [],
            'grades' => $model->grades(),
            'errors' => [],
        ]);
    }

    public function deactivate(): void
    {
        $this->requirePost();
        $id = $this->positiveId($_POST['id'] ?? null);
        $staff = Auth::user();
        if ($id === null) {
            $this->flash('error', 'Identificador de usuario inválido.');
            View::redirect('patrons');
        }
        try {
            $success = (new Patron())->deactivate($id, (int)$staff['institucion_id']);
            $this->flash($success ? 'success' : 'error', $success
                ? 'Usuario dado de baja.'
                : 'No se puede dar de baja mientras tenga préstamos activos, o el usuario ya no existe.');
        } catch (PDOException $exception) {
            error_log('Patron deactivation failed: ' . $exception->getMessage());
            $this->flash('error', 'No se pudo dar de baja el usuario.');
        }
        View::redirect('patrons');
    }

    private function input(): array
    {
        return [
            'tipo_documento' => trim((string)($_POST['tipo_documento'] ?? '')),
            'documento' => trim((string)($_POST['documento'] ?? '')),
            'nombres' => trim((string)($_POST['nombres'] ?? '')),
            'apellidos' => trim((string)($_POST['apellidos'] ?? '')),
            'correo' => trim((string)($_POST['correo'] ?? '')),
            'telefono' => trim((string)($_POST['telefono'] ?? '')),
            'direccion' => trim((string)($_POST['direccion'] ?? '')),
            'grado_id' => trim((string)($_POST['grado_id'] ?? '')),
            'tipo_usuario' => trim((string)($_POST['tipo_usuario'] ?? '')),
        ];
    }

    private function validate(array $data): array
    {
        $errors = [];
        if ($data['documento'] === '' || strlen($data['documento']) > 30) {
            $errors[] = 'El documento es obligatorio y no debe superar 30 caracteres.';
        }
        if ($data['nombres'] === '' || strlen($data['nombres']) > 100
            || $data['apellidos'] === '' || strlen($data['apellidos']) > 100) {
            $errors[] = 'Nombres y apellidos son obligatorios (máximo 100 caracteres cada uno).';
        }
        if ($data['correo'] !== '' && filter_var($data['correo'], FILTER_VALIDATE_EMAIL) === false) {
            $errors[] = 'El correo no tiene un formato válido.';
        }
        if (strlen($data['tipo_documento']) > 20 || strlen($data['correo']) > 150
            || strlen($data['telefono']) > 30 || strlen($data['direccion']) > 255
            || strlen($data['tipo_usuario']) > 30) {
            $errors[] = 'Uno o más campos superan la longitud permitida.';
        }
        if ($data['grado_id'] !== '' && $this->positiveId($data['grado_id']) === null) {
            $errors[] = 'Selecciona un grado válido.';
        }
        return $errors;
    }

    private function positiveId($value): ?int
    {
        $id = filter_var($value, FILTER_VALIDATE_INT);
        return $id !== false && $id !== null && $id > 0 ? $id : null;
    }
}
