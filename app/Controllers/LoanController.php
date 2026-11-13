<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\View;
use App\Models\Loan;
use App\Models\Patron;
use PDOException;
use RuntimeException;
use Throwable;

final class LoanController extends Controller
{
    public function index(): void
    {
        $staff = Auth::user();
        $loanDateInput = is_string($_GET['fecha_prestamo'] ?? null)
            ? trim($_GET['fecha_prestamo'])
            : '';
        $returnDateInput = is_string($_GET['fecha_devolucion'] ?? null)
            ? trim($_GET['fecha_devolucion'])
            : '';
        $gradeInput = $_GET['grado_id'] ?? '';
        $filters = [
            'fecha_prestamo' => $this->validDate($loanDateInput),
            'fecha_devolucion' => $this->validDate($returnDateInput),
            'grado_id' => $this->positiveId($gradeInput),
            'institucion_id' => (int)$staff['institucion_id'],
        ];
        if (($loanDateInput !== '' && $filters['fecha_prestamo'] === '')
            || ($returnDateInput !== '' && $filters['fecha_devolucion'] === '')
            || ($gradeInput !== '' && $filters['grado_id'] === null)) {
            $this->flash('error', 'Uno de los filtros no tiene un formato válido.');
        }

        try {
            $this->render('loans/index', [
                'title' => 'En préstamo',
                'loans' => (new Loan())->list($filters),
                'grades' => (new Patron())->grades(),
                'filters' => $filters,
            ]);
        } catch (Throwable $exception) {
            error_log('Loan listing failed: ' . $exception->getMessage());
            http_response_code(503);
            $this->flash('error', 'No se pudieron cargar los préstamos. Revisa la conexión y configuración de la base de datos.');
            $this->render('loans/index', [
                'title' => 'En préstamo',
                'loans' => [],
                'grades' => [],
                'filters' => $filters,
            ]);
        }
    }

    public function create(string $method): void
    {
        $model = new Loan();
        $staff = Auth::user();
        $selectedBookIds = [];
        $selectedPatronId = null;
        $note = '';
        if ($method === 'POST') {
            $this->requirePost();
            $selectedPatronId = $this->positiveId($_POST['usuario_id'] ?? null);
            $rawBookIds = $_POST['libro_ids'] ?? null;
            $note = trim((string)($_POST['nota'] ?? ''));
            $invalidBookSelection = !is_array($rawBookIds);
            if (is_array($rawBookIds)) {
                foreach ($rawBookIds as $rawBookId) {
                    $bookId = $this->positiveId($rawBookId);
                    if ($bookId === null || in_array($bookId, $selectedBookIds, true)) {
                        $invalidBookSelection = true;
                        continue;
                    }
                    $selectedBookIds[] = $bookId;
                }
            }

            if ($selectedPatronId === null || $selectedBookIds === []
                || $invalidBookSelection || strlen($note) > 500) {
                $this->flash('error', 'Selecciona un usuario y títulos distintos; la nota admite hasta 500 caracteres.');
            } else {
                try {
                    $model->createBatch(
                        $selectedBookIds,
                        $selectedPatronId,
                        (int)$staff['id'],
                        (int)$staff['institucion_id'],
                        $note
                    );
                    $this->flash(
                        'success',
                        'Préstamo registrado con ' . count($selectedBookIds) . ' título(s).'
                    );
                    View::redirect('loans');
                } catch (PDOException $exception) {
                    error_log('Loan creation failed: ' . $exception->getMessage());
                    $this->flash('error', 'No se pudo registrar el préstamo. Actualiza la pantalla e inténtalo de nuevo.');
                } catch (RuntimeException $exception) {
                    $this->flash('error', $exception->getMessage());
                }
            }
        } elseif ($method !== 'GET') {
            http_response_code(405);
            header('Allow: GET, POST');
            return;
        }

        try {
            $this->render('loans/create', [
                'title' => 'Nuevo préstamo',
                'choices' => $model->choices((int)$staff['institucion_id']),
                'selectedBookIds' => $selectedBookIds,
                'selectedPatronId' => $selectedPatronId,
                'note' => $note,
            ]);
        } catch (Throwable $exception) {
            error_log('Loan form data failed: ' . $exception->getMessage());
            http_response_code(503);
            $this->flash('error', 'No se pudo cargar el formulario. Verifica la conexión con la base de datos.');
            $this->render('loans/create', [
                'title' => 'Nuevo préstamo',
                'choices' => ['usuarios' => [], 'libros' => [], 'limite_ejemplares' => 0],
                'selectedBookIds' => $selectedBookIds,
                'selectedPatronId' => $selectedPatronId,
                'note' => $note,
            ]);
        }
    }

    public function returnCopy(): void
    {
        $this->requirePost();
        $id = $this->positiveId($_POST['id'] ?? null);
        $note = trim((string)($_POST['nota_devolucion'] ?? ''));
        $staff = Auth::user();
        if ($id === null || strlen($note) > 500) {
            $this->flash('error', 'Los datos de devolución no son válidos.');
            View::redirect('loans');
        }
        try {
            (new Loan())->returnCopy($id, (int)$staff['institucion_id'], $note);
            $this->flash('success', 'Devolución registrada.');
        } catch (PDOException $exception) {
            error_log('Loan return failed: ' . $exception->getMessage());
            $this->flash('error', 'No se pudo registrar la devolución.');
        } catch (RuntimeException $exception) {
            $this->flash('error', $exception->getMessage());
        }
        View::redirect('loans');
    }

    private function validDate($value): string
    {
        if (!is_string($value) || $value === '') {
            return '';
        }
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $date !== false && $date->format('Y-m-d') === $value ? $value : '';
    }

    private function positiveId($value): ?int
    {
        $id = filter_var($value, FILTER_VALIDATE_INT);
        return $id !== false && $id !== null && $id > 0 ? $id : null;
    }
}
