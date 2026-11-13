<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\View;
use App\Models\CatalogEntry;
use PDOException;
use RuntimeException;

final class CatalogController extends Controller
{
    private const TYPES = ['autor', 'editorial', 'categoria', 'formato'];

    public function index(string $method): void
    {
        $model = new CatalogEntry();
        $institutionId = (int)Auth::user()['institucion_id'];
        if ($method === 'POST') {
            $this->requirePost();
            $action = (string)($_POST['action'] ?? '');
            $type = (string)($_POST['tipo_catalogo'] ?? '');
            $id = $this->positiveId($_POST['id'] ?? null);
            if (!in_array($type, self::TYPES, true)) {
                $this->flash('error', 'El tipo de catálogo no es válido.');
                View::redirect('catalogs');
            }
            try {
                if ($action === 'delete' && $id !== null) {
                    $model->delete($type, $id, $institutionId);
                    $this->flash('success', 'Elemento eliminado.');
                } elseif ($action === 'save') {
                    $data = $this->input($type);
                    $errors = $this->validate($type, $data);
                    if ($errors !== []) {
                        $this->flash('error', implode(' ', $errors));
                    } else {
                        if ($id !== null && $model->find($type, $id, $institutionId) === null) {
                            throw new RuntimeException('El elemento ya no existe.');
                        }
                        $model->save($type, $id, $data, $institutionId);
                        $this->flash('success', $id === null ? 'Elemento agregado.' : 'Elemento actualizado.');
                    }
                } else {
                    http_response_code(400);
                    $this->flash('error', 'La acción no es válida.');
                }
            } catch (RuntimeException $exception) {
                $this->flash('error', $exception->getMessage());
            } catch (PDOException $exception) {
                error_log('Catalog entry operation failed: ' . $exception->getMessage());
                $this->flash('error', 'No se pudo guardar. Revisa si ya existe un elemento con ese nombre.');
            }
            View::redirect('catalogs');
        }

        if ($method !== 'GET') {
            http_response_code(405);
            header('Allow: GET, POST');
            return;
        }

        try {
            $entries = [];
            foreach (self::TYPES as $type) {
                $entries[$type] = $model->list($type, $institutionId);
            }
            $selectedType = (string)($_GET['tipo'] ?? '');
            if (!in_array($selectedType, self::TYPES, true)) {
                $selectedType = '';
            }
            $selectedId = $this->positiveId($_GET['id'] ?? null);
            $selected = $selectedType !== '' && $selectedId !== null
                ? $model->find($selectedType, $selectedId, $institutionId)
                : null;
            $this->render('catalogs/index', [
                'title' => 'Catálogos',
                'entries' => $entries,
                'selectedType' => $selectedType,
                'selected' => $selected ?? [],
            ]);
        } catch (PDOException $exception) {
            error_log('Catalogs listing failed: ' . $exception->getMessage());
            http_response_code(503);
            $this->flash('error', 'No se pudieron cargar los catálogos.');
            $this->render('catalogs/index', [
                'title' => 'Catálogos',
                'entries' => ['autor' => [], 'editorial' => [], 'categoria' => [], 'formato' => []],
                'selectedType' => '',
                'selected' => [],
            ]);
        }
    }

    private function input(string $type): array
    {
        return [
            'nombres' => trim((string)($_POST['nombres'] ?? '')),
            'apellidos' => trim((string)($_POST['apellidos'] ?? '')),
            'tipo' => trim((string)($_POST['tipo'] ?? '')),
            'nombre' => trim((string)($_POST['nombre'] ?? '')),
            'descripcion' => trim((string)($_POST['descripcion'] ?? '')),
        ];
    }

    private function validate(string $type, array $data): array
    {
        $errors = [];
        if ($type === 'autor') {
            if ($data['nombres'] === '' || strlen($data['nombres']) > 100
                || strlen($data['apellidos']) > 100) {
                $errors[] = 'El nombre del autor es obligatorio y admite hasta 100 caracteres.';
            }
        } else {
            $limit = $type === 'editorial' ? 150 : ($type === 'categoria' ? 100 : 50);
            if ($data['nombre'] === '' || strlen($data['nombre']) > $limit) {
                $errors[] = 'El nombre es obligatorio y supera el límite permitido.';
            }
        }
        if ($type === 'categoria'
            && !in_array($data['tipo'], ['genero', 'categoria', 'tema'], true)) {
            $errors[] = 'Selecciona un tipo de categoría válido.';
        }
        if (strlen($data['descripcion']) > 255) {
            $errors[] = 'La descripción admite hasta 255 caracteres.';
        }
        return $errors;
    }

    private function positiveId($value): ?int
    {
        $id = filter_var($value, FILTER_VALIDATE_INT);
        return $id !== false && $id !== null && $id > 0 ? $id : null;
    }
}
