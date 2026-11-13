<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\ExcelExport;
use App\Core\Security;
use App\Core\View;
use App\Models\Book;
use PDOException;
use RuntimeException;

final class BookController extends Controller
{
    public function index(): void
    {
        $model = new Book();
        $institutionId = (int)Auth::user()['institucion_id'];
        $filters = [
            'q' => trim((string)($_GET['q'] ?? '')),
            'editorial_id' => $this->positiveId($_GET['editorial_id'] ?? null),
            'autor_id' => $this->positiveId($_GET['autor_id'] ?? null),
            'categoria_id' => $this->positiveId($_GET['categoria_id'] ?? null),
        ];
        foreach (['editorial_id', 'autor_id', 'categoria_id'] as $filterKey) {
            if (isset($_GET[$filterKey]) && $_GET[$filterKey] !== ''
                && $filters[$filterKey] === null) {
                $this->flash('error', 'Uno de los filtros del catálogo no es válido.');
                break;
            }
        }
        try {
            $this->render('books/index', [
                'title' => 'Libros',
                'books' => $model->list($filters, $institutionId),
                'catalogs' => $model->catalogs($institutionId),
                'filters' => $filters,
            ]);
        } catch (PDOException $exception) {
            error_log('Book listing failed: ' . $exception->getMessage());
            http_response_code(503);
            $this->flash('error', 'No se pudo cargar el catálogo. Revisa la conexión con la base de datos.');
            $this->render('books/index', [
                'title' => 'Libros',
                'books' => [],
                'catalogs' => ['editoriales' => [], 'autores' => [], 'categorias' => [], 'formatos' => []],
                'filters' => $filters,
            ]);
        }
    }

    public function export(): void
    {
        if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'GET') {
            http_response_code(405);
            header('Allow: GET');
            return;
        }

        try {
            $rows = (new Book())->inventory((int)Auth::user()['institucion_id']);
        } catch (PDOException $exception) {
            error_log('Book inventory export failed: ' . $exception->getMessage());
            http_response_code(503);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'No se pudo exportar el inventario. Revisa la conexión con la base de datos.';
            return;
        }

        $exportRows = [];
        foreach ($rows as $row) {
            $exportRows[] = [
                (string)$row['titulo'],
                (string)($row['isbn'] ?? ''),
                (string)($row['autores'] ?? ''),
                (int)$row['libro_activo'] === 1 ? 'Activo' : 'Inactivo',
                (string)($row['codigo_barras'] ?? ''),
                (string)($row['condicion'] ?? ''),
                (string)($row['estado_inventario'] ?? ''),
                (string)($row['ubicacion'] ?? ''),
            ];
        }

        $temporaryFile = tempnam(sys_get_temp_dir(), 'biblioteca-inventario-');
        if ($temporaryFile === false) {
            throw new RuntimeException('No se pudo preparar el archivo de inventario.');
        }

        try {
            ExcelExport::write($temporaryFile, [
                'Título',
                'ISBN',
                'Autor(es)',
                'Estado del título',
                'Código de barras',
                'Condición física',
                'Estado del inventario',
                'Ubicación',
            ], $exportRows);

            $fileSize = filesize($temporaryFile);
            if ($fileSize === false) {
                throw new RuntimeException('No se pudo determinar el tamaño del archivo de inventario.');
            }
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="inventario-biblioteca.xlsx"');
            header('X-Content-Type-Options: nosniff');
            header('Content-Length: ' . $fileSize);
            if (readfile($temporaryFile) === false) {
                throw new RuntimeException('No se pudo enviar el archivo de inventario.');
            }
        } finally {
            if (is_file($temporaryFile) && !unlink($temporaryFile)) {
                error_log('Unable to remove temporary inventory export.');
            }
        }
    }

    public function create(string $method): void
    {
        $model = new Book();
        $institutionId = (int)Auth::user()['institucion_id'];
        $id = $this->positiveId($method === 'POST' ? ($_POST['id'] ?? null) : ($_GET['id'] ?? null));
        if ($method === 'POST') {
            $this->requirePost();
            $data = $this->input();
            $errors = $this->validate($data);
            $book = $id === null ? null : $model->find($id, $institutionId);
            if ($id !== null && $book === null) {
                http_response_code(404);
                View::render('errors/404', ['title' => 'Libro no encontrado']);
                return;
            }
            if ($errors === []) {
                try {
                    $model->save($id, $data, $institutionId);
                    $this->flash('success', $id === null ? 'Libro registrado.' : 'Cambios guardados.');
                    View::redirect('books');
                } catch (PDOException $exception) {
                    error_log('Book save failed: ' . $exception->getMessage());
                    $this->flash('error', 'No se pudo guardar. Comprueba que el ISBN y el código de barras no estén duplicados.');
                } catch (RuntimeException $exception) {
                    $this->flash('error', $exception->getMessage());
                }
            }
            $this->render('books/form', [
                'title' => $id === null ? 'Registrar libro' : 'Editar libro',
                'book' => $data,
                'catalogs' => $model->catalogs($institutionId),
                'copies' => $id === null ? [] : $model->copies($id, $institutionId),
                'errors' => $errors,
            ]);
            return;
        }

        if ($method !== 'GET') {
            http_response_code(405);
            header('Allow: GET, POST');
            return;
        }
        $book = $id === null ? [] : $model->find($id, $institutionId);
        if ($id !== null && $book === null) {
            http_response_code(404);
            View::render('errors/404', ['title' => 'Libro no encontrado']);
            return;
        }
        $this->render('books/form', [
            'title' => $id === null ? 'Registrar libro' : 'Editar libro',
            'book' => $book ?? [],
            'catalogs' => $model->catalogs($institutionId),
            'copies' => $id === null ? [] : $model->copies($id, $institutionId),
            'errors' => [],
        ]);
    }

    public function copy(): void
    {
        $this->requirePost();
        $bookId = $this->positiveId($_POST['book_id'] ?? null);
        $institutionId = (int)Auth::user()['institucion_id'];
        $action = (string)($_POST['action'] ?? '');
        if ($bookId === null) {
            $this->flash('error', 'El título seleccionado no es válido.');
            View::redirect('books');
        }

        try {
            $model = new Book();
            if ($action === 'add') {
                $data = [
                    'codigo_barras' => trim((string)($_POST['codigo_barras'] ?? '')),
                    'formato_id' => trim((string)($_POST['formato_id'] ?? '')),
                    'ubicacion' => trim((string)($_POST['ubicacion'] ?? '')),
                    'observaciones' => trim((string)($_POST['observaciones'] ?? '')),
                    'condicion' => (string)($_POST['condicion'] ?? ''),
                ];
                if ($data['codigo_barras'] === '' || strlen($data['codigo_barras']) > 50
                    || strlen($data['ubicacion']) > 100 || strlen($data['observaciones']) > 255
                    || !in_array($data['condicion'], ['bueno', 'intermedio', 'arreglar'], true)
                    || ($data['formato_id'] !== '' && $this->positiveId($data['formato_id']) === null)) {
                    $this->flash('error', 'El código de barras es obligatorio; revisa condición, formato, ubicación y observaciones.');
                } else {
                    $model->addCopy($bookId, $data, $institutionId);
                    $this->flash('success', 'Ejemplar registrado como disponible.');
                }
            } elseif ($action === 'status') {
                $copyId = $this->positiveId($_POST['copy_id'] ?? null);
                $state = (string)($_POST['estado'] ?? '');
                if ($copyId === null || !in_array($state, ['disponible', 'baja'], true)) {
                    $this->flash('error', 'Los datos del ejemplar no son válidos.');
                } else {
                    $model->updateCopyStatus($bookId, $copyId, $state, $institutionId);
                    $this->flash('success', $state === 'baja' ? 'Ejemplar dado de baja.' : 'Ejemplar reactivado.');
                }
            } elseif ($action === 'update') {
                $copyId = $this->positiveId($_POST['copy_id'] ?? null);
                $data = [
                    'codigo_barras' => trim((string)($_POST['codigo_barras'] ?? '')),
                    'formato_id' => trim((string)($_POST['formato_id'] ?? '')),
                    'ubicacion' => trim((string)($_POST['ubicacion'] ?? '')),
                    'observaciones' => trim((string)($_POST['observaciones'] ?? '')),
                    'condicion' => (string)($_POST['condicion'] ?? ''),
                ];
                if ($copyId === null || $data['codigo_barras'] === ''
                    || strlen($data['codigo_barras']) > 50 || strlen($data['ubicacion']) > 100
                    || strlen($data['observaciones']) > 255
                    || !in_array($data['condicion'], ['bueno', 'intermedio', 'arreglar'], true)
                    || ($data['formato_id'] !== '' && $this->positiveId($data['formato_id']) === null)) {
                    $this->flash('error', 'Revisa código de barras, condición, formato, ubicación y observaciones.');
                } else {
                    $model->updateCopyDetails($bookId, $copyId, $data, $institutionId);
                    $this->flash('success', 'Datos del ejemplar actualizados.');
                }
            } elseif ($action === 'delete') {
                $copyId = $this->positiveId($_POST['copy_id'] ?? null);
                if ($copyId === null) {
                    $this->flash('error', 'El identificador del ejemplar no es válido.');
                } else {
                    $model->deleteCopy($bookId, $copyId, $institutionId);
                    $this->flash('success', 'Ejemplar eliminado del inventario.');
                }
            } else {
                $this->flash('error', 'La acción solicitada no es válida.');
            }
        } catch (RuntimeException $exception) {
            $this->flash('error', $exception->getMessage());
        } catch (PDOException $exception) {
            error_log('Copy update failed: ' . $exception->getMessage());
            $this->flash('error', 'No se pudo guardar el ejemplar. Comprueba que el código de barras sea único.');
        }
        View::redirect('books/create', ['id' => $bookId]);
    }

    public function deactivate(string $method): void
    {
        $this->requirePost();
        $id = $this->positiveId($_POST['id'] ?? null);
        $institutionId = (int)Auth::user()['institucion_id'];
        if ($id === null) {
            $this->flash('error', 'Identificador de libro inválido.');
            View::redirect('books');
        }
        try {
            $success = (new Book())->deactivate($id, $institutionId);
            $this->flash($success ? 'success' : 'error', $success
                ? 'Libro dado de baja.'
                : 'No se puede dar de baja mientras conserve préstamos activos, o el libro ya no existe.');
        } catch (PDOException $exception) {
            error_log('Book deactivation failed: ' . $exception->getMessage());
            $this->flash('error', 'No se pudo dar de baja el libro.');
        }
        View::redirect('books');
    }

    private function input(): array
    {
        return [
            'titulo' => trim((string)($_POST['titulo'] ?? '')),
            'subtitulo' => trim((string)($_POST['subtitulo'] ?? '')),
            'isbn' => trim((string)($_POST['isbn'] ?? '')),
            'anio_publicacion' => trim((string)($_POST['anio_publicacion'] ?? '')),
            'idioma' => trim((string)($_POST['idioma'] ?? '')),
            'descripcion' => trim((string)($_POST['descripcion'] ?? '')),
            'editorial_id' => trim((string)($_POST['editorial_id'] ?? '')),
            'autores' => $this->idList($_POST['autores'] ?? []),
            'categorias' => $this->idList($_POST['categorias'] ?? []),
            'formato_id' => trim((string)($_POST['formato_id'] ?? '')),
            'ubicacion' => trim((string)($_POST['ubicacion'] ?? '')),
            'cantidad_ejemplares' => [
                'bueno' => trim((string)($_POST['cantidad_bueno'] ?? '0')),
                'intermedio' => trim((string)($_POST['cantidad_intermedio'] ?? '0')),
                'arreglar' => trim((string)($_POST['cantidad_arreglar'] ?? '0')),
            ],
        ];
    }

    private function validate(array $data): array
    {
        $errors = [];
        if ($data['titulo'] === '' || strlen($data['titulo']) > 255) {
            $errors[] = 'El título es obligatorio y no debe superar 255 caracteres.';
        }
        if (strlen($data['subtitulo']) > 255 || strlen($data['isbn']) > 20
            || strlen($data['idioma']) > 30 || strlen($data['ubicacion']) > 100) {
            $errors[] = 'Uno o más campos superan la longitud permitida.';
        }
        if ($data['anio_publicacion'] !== ''
            && (!ctype_digit($data['anio_publicacion'])
                || (int)$data['anio_publicacion'] < 0
                || (int)$data['anio_publicacion'] > 2100)) {
            $errors[] = 'El año debe estar entre 0 y 2100.';
        }
        if ($data['editorial_id'] !== '' && $this->positiveId($data['editorial_id']) === null) {
            $errors[] = 'Selecciona una editorial válida.';
        }
        if ($data['formato_id'] !== '' && $this->positiveId($data['formato_id']) === null) {
            $errors[] = 'Selecciona un formato válido.';
        }
        $totalCopies = 0;
        foreach ($data['cantidad_ejemplares'] as $quantity) {
            if ($quantity === '' || !ctype_digit($quantity) || (int)$quantity > 999) {
                $errors[] = 'La cantidad de ejemplares por condición debe ser un número entero entre 0 y 999.';
                break;
            }
            $totalCopies += (int)$quantity;
        }
        if ($totalCopies > 999) {
            $errors[] = 'La cantidad total de ejemplares no puede superar 999.';
        }
        if (!is_array($_POST['autores'] ?? [])) {
            $errors[] = 'La selección de autores no es válida.';
        }
        if (!is_array($_POST['categorias'] ?? [])) {
            $errors[] = 'La selección de categorías no es válida.';
        }
        return $errors;
    }

    private function idList($values): array
    {
        if (!is_array($values)) {
            return [];
        }
        $ids = [];
        foreach ($values as $value) {
            $id = $this->positiveId($value);
            if ($id !== null) {
                $ids[] = $id;
            }
        }
        return array_values(array_unique($ids));
    }

    private function positiveId($value): ?int
    {
        $id = filter_var($value, FILTER_VALIDATE_INT);
        return $id !== false && $id !== null && $id > 0 ? $id : null;
    }

}
