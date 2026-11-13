<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use DateTimeImmutable;
use PDO;
use RuntimeException;
use Throwable;

final class Loan
{
    public function dashboard(int $institutionId): array
    {
        $db = Database::connection();
        $counts = $db->prepare(
            "SELECT
                (SELECT COUNT(*) FROM prestamos p
                 INNER JOIN usuarios u ON u.id = p.usuario_id AND u.institucion_id = p.institucion_id
                 WHERE u.institucion_id = :institution_active
                   AND p.institucion_id = u.institucion_id AND p.estado = 'activo') AS activos,
                (SELECT COUNT(*) FROM prestamos p
                 INNER JOIN usuarios u ON u.id = p.usuario_id
                 WHERE u.institucion_id = :institution_overdue
                   AND p.institucion_id = u.institucion_id AND p.estado = 'activo'
                   AND p.fecha_devolucion_prevista < CURRENT_TIMESTAMP) AS vencidos,
                (SELECT COUNT(*) FROM ejemplares
                 WHERE institucion_id = :institution_available AND estado = 'disponible'
                   AND condicion <> 'arreglar') AS disponibles,
                (SELECT COUNT(*) FROM usuarios WHERE institucion_id = :institution_users AND activo = 1)
                    AS usuarios_activos"
        );
        $counts->execute([
            'institution_active' => $institutionId,
            'institution_overdue' => $institutionId,
            'institution_available' => $institutionId,
            'institution_users' => $institutionId,
        ]);
        $countRow = $counts->fetch();
        $alertDays = $this->settingInt('dias_alerta_vencimiento', $institutionId);
        $cutoff = (new DateTimeImmutable('now'))
            ->modify('+' . $alertDays . ' days')
            ->format('Y-m-d H:i:s');

        $statement = $db->prepare(
            "SELECT p.id, p.fecha_devolucion_prevista, u.nombres, u.apellidos,
                    b.titulo, e.codigo_barras,
                    CASE
                        WHEN p.fecha_devolucion_prevista < CURRENT_TIMESTAMP THEN 'vencido'
                        WHEN p.fecha_devolucion_prevista <= :cutoff THEN 'por_vencer'
                        ELSE 'a_tiempo'
                    END AS alerta
             FROM prestamos p
             INNER JOIN usuarios u ON u.id = p.usuario_id AND u.institucion_id = p.institucion_id
             INNER JOIN ejemplares e ON e.id = p.ejemplar_id
             INNER JOIN libros b ON b.id = e.libro_id AND b.institucion_id = e.institucion_id
             WHERE p.estado = 'activo'
               AND p.institucion_id = :institution
               AND u.institucion_id = p.institucion_id
               AND p.fecha_devolucion_prevista <= :cutoff_list
             ORDER BY p.fecha_devolucion_prevista
             LIMIT 8"
        );
        $statement->execute([
            'cutoff' => $cutoff,
            'cutoff_list' => $cutoff,
            'institution' => $institutionId,
        ]);
        return [
            'counts' => $countRow ?: [],
            'alertas' => $statement->fetchAll(),
        ];
    }

    public function list(array $filters): array
    {
        $db = Database::connection();
        $institutionId = (int)$filters['institucion_id'];
        $alertDays = $this->settingInt('dias_alerta_vencimiento', $institutionId);
        $cutoff = (new DateTimeImmutable('now'))
            ->modify('+' . $alertDays . ' days')
            ->format('Y-m-d H:i:s');
        $sql = "SELECT p.id, p.lote_id, p.fecha_prestamo, p.fecha_devolucion_prevista,
                    p.fecha_devolucion_real, p.estado, p.nota,
                    u.id AS usuario_id, u.nombres, u.apellidos, u.documento,
                    g.nombre AS grado, b.titulo, e.codigo_barras,
                    CASE
                        WHEN p.estado = 'activo' AND p.fecha_devolucion_prevista < CURRENT_TIMESTAMP
                            THEN 'vencido'
                        WHEN p.estado = 'activo' AND p.fecha_devolucion_prevista <= :cutoff
                            THEN 'por_vencer'
                        WHEN p.estado = 'activo' THEN 'a_tiempo'
                        ELSE 'cerrado'
                    END AS alerta
                FROM prestamos p
                INNER JOIN usuarios u ON u.id = p.usuario_id AND u.institucion_id = p.institucion_id
                LEFT JOIN grados g ON g.id = u.grado_id
                INNER JOIN ejemplares e ON e.id = p.ejemplar_id AND e.institucion_id = p.institucion_id
                INNER JOIN libros b ON b.id = e.libro_id AND b.institucion_id = e.institucion_id
                WHERE p.institucion_id = :institution";
        $parameters = ['cutoff' => $cutoff, 'institution' => $institutionId];

        if (($filters['fecha_prestamo'] ?? '') !== '') {
            $loanDate = new DateTimeImmutable($filters['fecha_prestamo']);
            $sql .= ' AND p.fecha_prestamo >= :loan_start AND p.fecha_prestamo < :loan_end';
            $parameters['loan_start'] = $loanDate->format('Y-m-d 00:00:00');
            $parameters['loan_end'] = $loanDate->modify('+1 day')->format('Y-m-d 00:00:00');
        }
        if (($filters['fecha_devolucion'] ?? '') !== '') {
            $returnDate = new DateTimeImmutable($filters['fecha_devolucion']);
            $sql .= ' AND (
                (p.fecha_devolucion_real IS NULL AND p.fecha_devolucion_prevista >= :due_start
                    AND p.fecha_devolucion_prevista < :due_end)
                OR (p.fecha_devolucion_real >= :return_start AND p.fecha_devolucion_real < :return_end)
            )';
            $parameters['due_start'] = $returnDate->format('Y-m-d 00:00:00');
            $parameters['due_end'] = $returnDate->modify('+1 day')->format('Y-m-d 00:00:00');
            $parameters['return_start'] = $returnDate->format('Y-m-d 00:00:00');
            $parameters['return_end'] = $returnDate->modify('+1 day')->format('Y-m-d 00:00:00');
        }
        if (!empty($filters['grado_id'])) {
            $sql .= ' AND u.grado_id = :grade';
            $parameters['grade'] = (int)$filters['grado_id'];
        }
        $sql .= " ORDER BY CASE WHEN p.estado = 'activo' THEN 0 ELSE 1 END,
                          p.fecha_devolucion_prevista LIMIT 250";
        $statement = $db->prepare($sql);
        $statement->execute($parameters);
        $groups = [];
        foreach ($statement->fetchAll() as $loan) {
            $groupKey = $loan['lote_id'] === null
                ? 'loan-' . $loan['id']
                : 'batch-' . $loan['lote_id'];
            if (!isset($groups[$groupKey])) {
                $groups[$groupKey] = [
                    'fecha_prestamo' => $loan['fecha_prestamo'],
                    'fecha_devolucion_prevista' => $loan['fecha_devolucion_prevista'],
                    'usuario_id' => $loan['usuario_id'],
                    'nombres' => $loan['nombres'],
                    'apellidos' => $loan['apellidos'],
                    'documento' => $loan['documento'],
                    'grado' => $loan['grado'],
                    'items' => [],
                ];
            }
            $groups[$groupKey]['items'][] = $loan;
        }
        return array_values($groups);
    }

    public function choices(int $institutionId): array
    {
        $db = Database::connection();
        $booksStatement = $db->prepare(
            "SELECT b.id, b.titulo, COUNT(e.id) AS disponibles
             FROM libros b
             INNER JOIN ejemplares e ON e.libro_id = b.id
               AND e.institucion_id = b.institucion_id
             WHERE b.institucion_id = :institution
               AND b.activo = 1 AND e.estado = 'disponible' AND e.condicion <> 'arreglar'
             GROUP BY b.id, b.titulo
             ORDER BY b.titulo"
        );
        $booksStatement->execute(['institution' => $institutionId]);

        $usersStatement = $db->prepare(
            "SELECT u.id, u.documento, u.nombres, u.apellidos, COUNT(p.id) AS prestamos_activos
             FROM usuarios u
             LEFT JOIN prestamos p ON p.usuario_id = u.id
               AND p.institucion_id = u.institucion_id AND p.estado = 'activo'
             WHERE u.activo = 1 AND u.institucion_id = :institution
             GROUP BY u.id, u.documento, u.nombres, u.apellidos
             ORDER BY u.apellidos, u.nombres"
        );
        $usersStatement->execute(['institution' => $institutionId]);
        return [
            'usuarios' => $usersStatement->fetchAll(),
            'libros' => $booksStatement->fetchAll(),
            'limite_ejemplares' => $this->settingInt('limite_ejemplares', $institutionId),
        ];
    }

    public function createBatch(array $bookIds, int $patronId, int $staffId, int $institutionId, string $note): void
    {
        if ($bookIds === [] || count(array_unique($bookIds)) !== count($bookIds)) {
            throw new RuntimeException('Selecciona uno o más títulos distintos.');
        }
        foreach ($bookIds as $bookId) {
            if (!is_int($bookId) || $bookId < 1) {
                throw new RuntimeException('La selección de títulos no es válida.');
            }
        }
        sort($bookIds, SORT_NUMERIC);

        $db = Database::connection();
        $db->beginTransaction();
        try {
            $patronStatement = $db->prepare(
                'SELECT id, activo FROM usuarios
                 WHERE id = :id AND institucion_id = :institution'
            );
            $patronStatement->execute(['id' => $patronId, 'institution' => $institutionId]);
            $patron = $patronStatement->fetch();
            if ($patron === false || (int)$patron['activo'] !== 1) {
                throw new RuntimeException('El usuario no existe o está inactivo.');
            }

            $countStatement = $db->prepare(
                "SELECT COUNT(*) FROM prestamos
                 WHERE usuario_id = :id AND institucion_id = :institution AND estado = 'activo'"
            );
            $countStatement->execute(['id' => $patronId, 'institution' => $institutionId]);
            $limit = $this->settingInt('limite_ejemplares', $institutionId);
            $activeLoans = (int)$countStatement->fetchColumn();
            if ($activeLoans + count($bookIds) > $limit) {
                $remaining = max(0, $limit - $activeLoans);
                throw new RuntimeException(
                    'El usuario puede llevar como máximo ' . $remaining
                    . ' ejemplar(es) más según el límite configurado.'
                );
            }

            $days = $this->settingInt('dias_prestamo', $institutionId);
            $borrowedAt = new DateTimeImmutable('now');
            $dueAt = $borrowedAt->modify('+' . $days . ' days');

            $groupInsert = $db->prepare(
                'INSERT INTO lotes_prestamos (institucion_id) VALUES (:institution)'
            );
            $groupInsert->execute(['institution' => $institutionId]);
            $groupId = (int)$db->lastInsertId();

            $insert = $db->prepare(
                "INSERT INTO prestamos
                    (lote_id, institucion_id, ejemplar_id, usuario_id, registrado_por, fecha_prestamo,
                     fecha_devolucion_prevista, estado, nota)
                 VALUES (:batch, :institution, :copy, :patron, :staff, :borrowed, :due, 'activo', :note)"
            );
            $insertActive = $db->prepare(
                'INSERT INTO prestamos_activos (institucion_id, ejemplar_id, prestamo_id)
                 VALUES (:institution, :copy, :loan)'
            );
            $bookLock = $db->prepare(
                'SELECT activo FROM libros
                 WHERE id = :id AND institucion_id = :institution'
            );
            $copyStatement = $db->prepare(
                "SELECT id FROM ejemplares
                 WHERE libro_id = :book AND institucion_id = :institution
                   AND estado = 'disponible' AND condicion <> 'arreglar'
                 ORDER BY id LIMIT 1"
            );
            $update = $db->prepare(
                "UPDATE ejemplares SET estado = 'prestado'
                 WHERE id = :id AND institucion_id = :institution AND estado = 'disponible'"
            );

            foreach ($bookIds as $bookId) {
                $bookLock->execute(['id' => $bookId, 'institution' => $institutionId]);
                if ((int)$bookLock->fetchColumn() !== 1) {
                    throw new RuntimeException('Uno de los títulos no existe o fue dado de baja.');
                }

                $copyStatement->execute(['book' => $bookId, 'institution' => $institutionId]);
                $copyId = $copyStatement->fetchColumn();
                if ($copyId === false) {
                    throw new RuntimeException('Uno de los títulos ya no tiene ejemplares disponibles.');
                }

                $insert->execute([
                    'batch' => $groupId,
                    'institution' => $institutionId,
                    'copy' => (int)$copyId,
                    'patron' => $patronId,
                    'staff' => $staffId,
                    'borrowed' => $borrowedAt->format('Y-m-d H:i:s'),
                    'due' => $dueAt->format('Y-m-d H:i:s'),
                    'note' => $note !== '' ? $note : null,
                ]);

                $insertActive->execute([
                    'institution' => $institutionId,
                    'copy' => (int)$copyId,
                    'loan' => (int)$db->lastInsertId(),
                ]);

                $update->execute(['id' => $copyId, 'institution' => $institutionId]);
                if ($update->rowCount() !== 1) {
                    throw new RuntimeException('Un ejemplar cambió de estado. Actualiza la pantalla e inténtalo de nuevo.');
                }
            }

            $db->commit();
        } catch (Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $exception;
        }
    }

    public function returnCopy(int $loanId, int $institutionId, string $note): void
    {
        $db = Database::connection();
        $db->beginTransaction();
        try {
            $statement = $db->prepare(
                'SELECT p.id, p.ejemplar_id, p.estado
                 FROM prestamos p
                 INNER JOIN usuarios u ON u.id = p.usuario_id AND u.institucion_id = p.institucion_id
                 WHERE p.id = :id AND p.institucion_id = :institution
                   AND u.institucion_id = p.institucion_id'
            );
            $statement->execute(['id' => $loanId, 'institution' => $institutionId]);
            $loan = $statement->fetch();
            if ($loan === false || $loan['estado'] !== 'activo') {
                throw new RuntimeException('El préstamo no está activo o ya fue devuelto.');
            }

            $removeActive = $db->prepare(
                'DELETE FROM prestamos_activos
                 WHERE institucion_id = :institution
                   AND ejemplar_id = :copy AND prestamo_id = :loan'
            );
            $removeActive->execute([
                'institution' => $institutionId,
                'copy' => $loan['ejemplar_id'],
                'loan' => $loanId,
            ]);
            if ($removeActive->rowCount() !== 1) {
                throw new RuntimeException('No se encontró la referencia del préstamo activo.');
            }

            $returnedAt = new DateTimeImmutable('now');
            $updateLoan = $db->prepare(
                "UPDATE prestamos
                 SET estado = 'devuelto', fecha_devolucion_real = :returned_at,
                     nota_devolucion = :note
                 WHERE id = :id AND institucion_id = :institution AND estado = 'activo'"
            );
            $updateLoan->execute([
                'returned_at' => $returnedAt->format('Y-m-d H:i:s'),
                'note' => $note !== '' ? $note : null,
                'id' => $loanId,
                'institution' => $institutionId,
            ]);
            if ($updateLoan->rowCount() !== 1) {
                throw new RuntimeException('No se pudo completar la devolución.');
            }

            $updateCopy = $db->prepare(
                "UPDATE ejemplares SET estado = 'disponible'
                 WHERE id = :id AND institucion_id = :institution AND estado = 'prestado'"
            );
            $updateCopy->execute(['id' => $loan['ejemplar_id'], 'institution' => $institutionId]);
            if ($updateCopy->rowCount() !== 1) {
                throw new RuntimeException('El estado del ejemplar no coincide con el préstamo activo.');
            }
            $db->commit();
        } catch (Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $exception;
        }
    }

    private function settingInt(string $key, int $institutionId): int
    {
        $statement = Database::connection()->prepare(
            'SELECT valor FROM configuraciones
             WHERE institucion_id = :institution AND clave = :key LIMIT 1'
        );
        $statement->execute(['key' => $key, 'institution' => $institutionId]);
        $value = $statement->fetchColumn();
        $integer = filter_var($value, FILTER_VALIDATE_INT);
        if ($integer === false || ($key === 'dias_alerta_vencimiento' ? $integer < 0 : $integer < 1)) {
            throw new RuntimeException('El parámetro ' . $key . ' no existe o no contiene un entero válido.');
        }
        return (int)$integer;
    }
}
