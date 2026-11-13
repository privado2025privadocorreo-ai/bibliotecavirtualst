<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class Book
{
    public function catalogs(int $institutionId): array
    {
        $db = Database::connection();
        $catalogs = [];
        $queries = [
            'editoriales' => 'SELECT id, nombre FROM editoriales WHERE institucion_id = :institution ORDER BY nombre',
            'autores' => 'SELECT id, nombres, apellidos FROM autores WHERE institucion_id = :institution ORDER BY apellidos, nombres',
            'categorias' => 'SELECT id, tipo, nombre FROM categorias WHERE institucion_id = :institution ORDER BY tipo, nombre',
            'formatos' => 'SELECT id, nombre FROM formatos WHERE institucion_id = :institution ORDER BY nombre',
        ];
        foreach ($queries as $key => $query) {
            $statement = $db->prepare($query);
            $statement->execute(['institution' => $institutionId]);
            $catalogs[$key] = $statement->fetchAll();
        }
        return [
            'editoriales' => $catalogs['editoriales'],
            'autores' => $catalogs['autores'],
            'categorias' => $catalogs['categorias'],
            'formatos' => $catalogs['formatos'],
        ];
    }

    public function list(array $filters, int $institutionId): array
    {
        $sql = "SELECT b.id, b.titulo, b.isbn, b.anio_publicacion, e.nombre AS editorial,
                    (SELECT GROUP_CONCAT(author_name, ', ') FROM (
                        SELECT TRIM(a.nombres || ' ' || COALESCE(a.apellidos, '')) AS author_name
                        FROM libro_autores la INNER JOIN autores a ON a.id = la.autor_id
                        WHERE la.libro_id = b.id AND la.institucion_id = b.institucion_id
                        ORDER BY la.orden
                    )) AS autores,
                    (SELECT GROUP_CONCAT(category_name, ', ') FROM (
                        SELECT DISTINCT c.nombre AS category_name
                        FROM libro_categorias lc INNER JOIN categorias c ON c.id = lc.categoria_id
                        WHERE lc.libro_id = b.id AND lc.institucion_id = b.institucion_id
                        ORDER BY c.nombre
                    )) AS categorias,
                    (SELECT COUNT(*) FROM ejemplares ex
                     WHERE ex.libro_id = b.id AND ex.institucion_id = b.institucion_id
                       AND ex.estado <> 'baja') AS ejemplares,
                    (SELECT COUNT(*) FROM ejemplares ex
                     WHERE ex.libro_id = b.id AND ex.institucion_id = b.institucion_id
                       AND ex.estado = 'disponible' AND ex.condicion <> 'arreglar') AS disponibles,
                    (SELECT COUNT(*) FROM ejemplares ex
                     WHERE ex.libro_id = b.id AND ex.institucion_id = b.institucion_id
                       AND ex.estado <> 'baja' AND ex.condicion = 'bueno') AS buenos,
                    (SELECT COUNT(*) FROM ejemplares ex
                     WHERE ex.libro_id = b.id AND ex.institucion_id = b.institucion_id
                       AND ex.estado <> 'baja' AND ex.condicion = 'intermedio') AS intermedios,
                    (SELECT COUNT(*) FROM ejemplares ex
                     WHERE ex.libro_id = b.id AND ex.institucion_id = b.institucion_id
                       AND ex.estado <> 'baja' AND ex.condicion = 'arreglar') AS por_arreglar,
                    (SELECT COUNT(*) FROM ejemplares ex
                     WHERE ex.libro_id = b.id AND ex.institucion_id = b.institucion_id
                       AND ex.estado = 'prestado') AS prestados
                FROM libros b
                LEFT JOIN editoriales e ON e.id = b.editorial_id AND e.institucion_id = b.institucion_id
                WHERE b.activo = 1 AND b.institucion_id = :institution";
        $parameters = ['institution' => $institutionId];

        if (($filters['q'] ?? '') !== '') {
            $sql .= " AND (
                b.titulo LIKE :title
                OR b.isbn LIKE :isbn
                OR EXISTS (
                    SELECT 1 FROM ejemplares ex
                    WHERE ex.libro_id = b.id AND ex.institucion_id = b.institucion_id
                      AND ex.codigo_barras LIKE :barcode
                )
            )";
            $term = '%' . $filters['q'] . '%';
            $parameters['title'] = $term;
            $parameters['isbn'] = $term;
            $parameters['barcode'] = $term;
        }
        if (!empty($filters['editorial_id'])) {
            $sql .= ' AND b.editorial_id = :publisher';
            $parameters['publisher'] = (int)$filters['editorial_id'];
        }
        if (!empty($filters['autor_id'])) {
            $sql .= ' AND EXISTS (SELECT 1 FROM libro_autores la
                WHERE la.libro_id = b.id AND la.institucion_id = b.institucion_id
                  AND la.autor_id = :author)';
            $parameters['author'] = (int)$filters['autor_id'];
        }
        if (!empty($filters['categoria_id'])) {
            $sql .= ' AND EXISTS (SELECT 1 FROM libro_categorias lc
                WHERE lc.libro_id = b.id AND lc.institucion_id = b.institucion_id
                  AND lc.categoria_id = :category)';
            $parameters['category'] = (int)$filters['categoria_id'];
        }
        $sql .= ' ORDER BY b.titulo LIMIT 200';

        $statement = Database::connection()->prepare($sql);
        $statement->execute($parameters);
        return $statement->fetchAll();
    }

    public function inventory(int $institutionId): array
    {
        $statement = Database::connection()->prepare(
            "SELECT b.titulo, b.isbn, b.activo AS libro_activo,
                    (SELECT GROUP_CONCAT(author_name, ', ') FROM (
                        SELECT TRIM(a.nombres || ' ' || COALESCE(a.apellidos, '')) AS author_name
                        FROM libro_autores la INNER JOIN autores a ON a.id = la.autor_id
                        WHERE la.libro_id = b.id AND la.institucion_id = b.institucion_id
                        ORDER BY la.orden
                    )) AS autores,
                    e.codigo_barras, e.condicion, e.estado AS estado_ejemplar,
                    e.ubicacion,
                    CASE
                        WHEN e.id IS NULL THEN 'Sin ejemplares'
                        WHEN e.estado = 'baja' THEN 'Baja'
                        WHEN e.estado = 'prestado' THEN 'En préstamo'
                        WHEN e.condicion = 'arreglar' THEN 'Arreglar'
                        WHEN e.estado = 'disponible' THEN 'Disponible'
                        ELSE 'Revisar'
                    END AS estado_inventario
             FROM libros b
             LEFT JOIN ejemplares e
               ON e.libro_id = b.id AND e.institucion_id = b.institucion_id
             WHERE b.institucion_id = :institution
             ORDER BY b.titulo, e.codigo_barras"
        );
        $statement->execute(['institution' => $institutionId]);
        return $statement->fetchAll();
    }

    public function find(int $id, int $institutionId): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT b.* FROM libros b
             WHERE b.id = :id AND b.institucion_id = :institution AND b.activo = 1 LIMIT 1'
        );
        $statement->execute(['id' => $id, 'institution' => $institutionId]);
        $book = $statement->fetch();
        if ($book === false) {
            return null;
        }

        $book['autor_ids'] = $this->relationIds(
            'SELECT autor_id FROM libro_autores WHERE libro_id = :id AND institucion_id = :institution ORDER BY orden',
            $id,
            $institutionId
        );
        $book['categoria_ids'] = $this->relationIds(
            'SELECT categoria_id FROM libro_categorias WHERE libro_id = :id AND institucion_id = :institution',
            $id,
            $institutionId
        );
        return $book;
    }

    public function copies(int $bookId, int $institutionId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT e.id, e.codigo_barras, e.ubicacion, e.estado, e.condicion, e.fecha_alta,
                    e.fecha_baja, e.observaciones, e.formato_id, f.nombre AS formato
             FROM ejemplares e
             LEFT JOIN formatos f ON f.id = e.formato_id AND f.institucion_id = e.institucion_id
             WHERE e.libro_id = :book AND e.institucion_id = :institution
             ORDER BY e.id'
        );
        $statement->execute(['book' => $bookId, 'institution' => $institutionId]);
        return $statement->fetchAll();
    }

    private function relationIds(string $sql, int $id, int $institutionId): array
    {
        $statement = Database::connection()->prepare($sql);
        $statement->execute(['id' => $id, 'institution' => $institutionId]);
        return array_map('intval', $statement->fetchAll(PDO::FETCH_COLUMN));
    }

    public function save(?int $id, array $data, int $institutionId): int
    {
        $db = Database::connection();
        $db->beginTransaction();
        try {
            if ($id === null) {
                $statement = $db->prepare(
                    'INSERT INTO libros
                        (institucion_id, editorial_id, titulo, subtitulo, isbn, anio_publicacion, idioma, descripcion)
                     VALUES
                        (:institution, :publisher, :title, :subtitle, :isbn, :year, :language, :description)'
                );
                $statement->execute($this->bookParameters($data) + ['institution' => $institutionId]);
                $id = (int)$db->lastInsertId();
            } else {
                $statement = $db->prepare(
                    'UPDATE libros SET editorial_id = :publisher, titulo = :title,
                        subtitulo = :subtitle, isbn = :isbn, anio_publicacion = :year,
                        idioma = :language, descripcion = :description
                     WHERE id = :id AND institucion_id = :institution AND activo = 1'
                );
                $statement->execute($this->bookParameters($data) + ['id' => $id, 'institution' => $institutionId]);
                if ($statement->rowCount() === 0 && $this->find($id, $institutionId) === null) {
                    throw new \RuntimeException('El libro ya no existe o fue dado de baja.');
                }
                $db->prepare(
                    'DELETE FROM libro_autores WHERE libro_id = :id AND institucion_id = :institution'
                )->execute(['id' => $id, 'institution' => $institutionId]);
                $db->prepare(
                    'DELETE FROM libro_categorias WHERE libro_id = :id AND institucion_id = :institution'
                )->execute(['id' => $id, 'institution' => $institutionId]);
            }

            $authorStatement = $db->prepare(
                'INSERT INTO libro_autores (institucion_id, libro_id, autor_id, orden)
                 VALUES (:institution, :book, :author, :sort)'
            );
            foreach ($data['autores'] as $index => $authorId) {
                $authorStatement->execute([
                    'institution' => $institutionId,
                    'book' => $id,
                    'author' => $authorId,
                    'sort' => $index + 1,
                ]);
            }

            $categoryStatement = $db->prepare(
                'INSERT INTO libro_categorias (institucion_id, libro_id, categoria_id)
                 VALUES (:institution, :book, :category)'
            );
            foreach ($data['categorias'] as $categoryId) {
                $categoryStatement->execute([
                    'institution' => $institutionId,
                    'book' => $id,
                    'category' => $categoryId,
                ]);
            }

            $copiesByCondition = $data['cantidad_ejemplares'] ?? [];
            $copyStatement = $db->prepare(
                'INSERT INTO ejemplares
                    (institucion_id, libro_id, formato_id, codigo_barras, ubicacion, estado, condicion, fecha_alta)
                 VALUES (:institution, :book, :format, :barcode, :location, \'disponible\', :condition, CURRENT_DATE)'
            );
            foreach (['bueno', 'intermedio', 'arreglar'] as $condition) {
                for ($index = 0; $index < (int)($copiesByCondition[$condition] ?? 0); $index++) {
                    $copyStatement->execute([
                        'institution' => $institutionId,
                        'book' => $id,
                        'format' => $data['formato_id'] !== '' ? $data['formato_id'] : null,
                        'barcode' => 'AUTO-' . strtoupper(bin2hex(random_bytes(12))),
                        'location' => $data['ubicacion'] !== '' ? $data['ubicacion'] : null,
                        'condition' => $condition,
                    ]);
                }
            }

            $db->commit();
            return $id;
        } catch (\Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $exception;
        }
    }

    private function bookParameters(array $data): array
    {
        return [
            'publisher' => $data['editorial_id'] !== '' ? $data['editorial_id'] : null,
            'title' => $data['titulo'],
            'subtitle' => $data['subtitulo'] !== '' ? $data['subtitulo'] : null,
            'isbn' => $data['isbn'] !== '' ? $data['isbn'] : null,
            'year' => $data['anio_publicacion'] !== '' ? $data['anio_publicacion'] : null,
            'language' => $data['idioma'] !== '' ? $data['idioma'] : null,
            'description' => $data['descripcion'] !== '' ? $data['descripcion'] : null,
        ];
    }

    public function addCopy(int $bookId, array $data, int $institutionId): void
    {
        if (!in_array($data['condicion'] ?? '', ['bueno', 'intermedio', 'arreglar'], true)) {
            throw new \RuntimeException('La condición física del ejemplar no es válida.');
        }
        $db = Database::connection();
        $statement = $db->prepare(
            'SELECT id FROM libros
             WHERE id = :id AND institucion_id = :institution AND activo = 1'
        );
        $db->beginTransaction();
        try {
            $statement->execute(['id' => $bookId, 'institution' => $institutionId]);
            if ($statement->fetchColumn() === false) {
                throw new \RuntimeException('El título no existe o fue dado de baja.');
            }
            $insert = $db->prepare(
                "INSERT INTO ejemplares
                (institucion_id, libro_id, formato_id, codigo_barras, ubicacion, observaciones, estado, condicion, fecha_alta)
                 VALUES (:institution, :book, :format, :barcode, :location, :notes, 'disponible', :condition, CURRENT_DATE)"
            );
            $insert->execute([
                'institution' => $institutionId,
                'book' => $bookId,
                'format' => $data['formato_id'] !== '' ? $data['formato_id'] : null,
                'barcode' => $data['codigo_barras'],
                'location' => $data['ubicacion'] !== '' ? $data['ubicacion'] : null,
                'notes' => $data['observaciones'] !== '' ? $data['observaciones'] : null,
                'condition' => $data['condicion'],
            ]);
            $db->commit();
        } catch (\Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $exception;
        }
    }

    public function updateCopyStatus(int $bookId, int $copyId, string $status, int $institutionId): void
    {
        if (!in_array($status, ['disponible', 'baja'], true)) {
            throw new \RuntimeException('El estado solicitado no es válido.');
        }
        $db = Database::connection();
        $db->beginTransaction();
        try {
            $book = $db->prepare(
                'SELECT id FROM libros
                 WHERE id = :book AND institucion_id = :institution AND activo = 1'
            );
            $book->execute(['book' => $bookId, 'institution' => $institutionId]);
            if ($book->fetchColumn() === false) {
                throw new \RuntimeException('El título no existe o fue dado de baja.');
            }
            $find = $db->prepare(
                'SELECT estado FROM ejemplares
                 WHERE id = :copy AND libro_id = :book AND institucion_id = :institution'
            );
            $find->execute([
                'copy' => $copyId,
                'book' => $bookId,
                'institution' => $institutionId,
            ]);
            $current = $find->fetchColumn();
            if ($current === false) {
                throw new \RuntimeException('El ejemplar no pertenece a este título.');
            }
            if ($current === 'prestado') {
                throw new \RuntimeException('No se puede cambiar el estado mientras el ejemplar está prestado.');
            }

            $update = $db->prepare(
                'UPDATE ejemplares
                 SET estado = :status,
                     fecha_baja = CASE WHEN :date_status = \'baja\' THEN CURRENT_DATE ELSE NULL END
                 WHERE id = :copy AND libro_id = :book AND institucion_id = :institution'
            );
            $update->execute([
                'status' => $status,
                'date_status' => $status,
                'copy' => $copyId,
                'book' => $bookId,
                'institution' => $institutionId,
            ]);
            $db->commit();
        } catch (\Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $exception;
        }
    }

    public function updateCopyDetails(int $bookId, int $copyId, array $data, int $institutionId): void
    {
        if (!in_array($data['condicion'] ?? '', ['bueno', 'intermedio', 'arreglar'], true)) {
            throw new \RuntimeException('La condición física del ejemplar no es válida.');
        }
        $db = Database::connection();
        $db->beginTransaction();
        try {
            $book = $db->prepare(
                'SELECT id FROM libros
                 WHERE id = :book AND institucion_id = :institution AND activo = 1'
            );
            $book->execute(['book' => $bookId, 'institution' => $institutionId]);
            if ($book->fetchColumn() === false) {
                throw new \RuntimeException('El título no existe o fue dado de baja.');
            }
            $copy = $db->prepare(
                'SELECT estado FROM ejemplares
                 WHERE id = :copy AND libro_id = :book AND institucion_id = :institution'
            );
            $copy->execute([
                'copy' => $copyId,
                'book' => $bookId,
                'institution' => $institutionId,
            ]);
            $state = $copy->fetchColumn();
            if ($state === false) {
                throw new \RuntimeException('El ejemplar no pertenece a este título.');
            }
            if ($state === 'prestado') {
                throw new \RuntimeException('No se puede editar un ejemplar mientras está prestado.');
            }

            $update = $db->prepare(
                'UPDATE ejemplares
                 SET codigo_barras = :barcode, formato_id = :format, condicion = :condition,
                     ubicacion = :location, observaciones = :notes
                 WHERE id = :copy AND libro_id = :book AND institucion_id = :institution'
            );
            $update->execute([
                'barcode' => $data['codigo_barras'],
                'format' => $data['formato_id'] !== '' ? $data['formato_id'] : null,
                'condition' => $data['condicion'],
                'location' => $data['ubicacion'] !== '' ? $data['ubicacion'] : null,
                'notes' => $data['observaciones'] !== '' ? $data['observaciones'] : null,
                'copy' => $copyId,
                'book' => $bookId,
                'institution' => $institutionId,
            ]);
            $db->commit();
        } catch (\Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $exception;
        }
    }

    public function deleteCopy(int $bookId, int $copyId, int $institutionId): void
    {
        $db = Database::connection();
        $db->beginTransaction();
        try {
            $book = $db->prepare(
                'SELECT id FROM libros
                 WHERE id = :book AND institucion_id = :institution AND activo = 1'
            );
            $book->execute(['book' => $bookId, 'institution' => $institutionId]);
            if ($book->fetchColumn() === false) {
                throw new \RuntimeException('El título no existe o fue dado de baja.');
            }

            $statement = $db->prepare(
                'SELECT estado FROM ejemplares
                 WHERE id = :copy AND libro_id = :book AND institucion_id = :institution'
            );
            $statement->execute([
                'copy' => $copyId,
                'book' => $bookId,
                'institution' => $institutionId,
            ]);
            $state = $statement->fetchColumn();
            if ($state === false) {
                throw new \RuntimeException('El ejemplar no pertenece a este título.');
            }
            if ($state === 'prestado') {
                throw new \RuntimeException('No se puede eliminar un ejemplar mientras está prestado.');
            }

            $history = $db->prepare(
                'SELECT 1 FROM prestamos
                 WHERE ejemplar_id = :copy AND institucion_id = :institution LIMIT 1'
            );
            $history->execute(['copy' => $copyId, 'institution' => $institutionId]);
            if ($history->fetchColumn() !== false) {
                throw new \RuntimeException('El ejemplar tiene historial de préstamos; usa «Dar de baja» para conservarlo.');
            }

            $delete = $db->prepare(
                'DELETE FROM ejemplares
                 WHERE id = :copy AND libro_id = :book AND institucion_id = :institution'
            );
            $delete->execute([
                'copy' => $copyId,
                'book' => $bookId,
                'institution' => $institutionId,
            ]);
            if ($delete->rowCount() !== 1) {
                throw new \RuntimeException('No se pudo eliminar el ejemplar.');
            }
            $db->commit();
        } catch (\Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $exception;
        }
    }

    public function deactivate(int $id, int $institutionId): bool
    {
        $db = Database::connection();
        $db->beginTransaction();
        try {
            $book = $db->prepare(
                'SELECT id FROM libros
                 WHERE id = :id AND institucion_id = :institution AND activo = 1'
            );
            $book->execute(['id' => $id, 'institution' => $institutionId]);
            if ($book->fetchColumn() === false) {
                $db->rollBack();
                return false;
            }

            $copies = $db->prepare(
                'SELECT id, estado FROM ejemplares
                 WHERE libro_id = :id AND institucion_id = :institution'
            );
            $copies->execute(['id' => $id, 'institution' => $institutionId]);
            foreach ($copies->fetchAll() as $copy) {
                if ($copy['estado'] === 'prestado') {
                    $db->rollBack();
                    return false;
                }
            }

            $db->prepare(
                "UPDATE ejemplares
                 SET estado = 'baja', fecha_baja = CURRENT_DATE
                 WHERE libro_id = :id AND institucion_id = :institution AND estado <> 'baja'"
            )->execute(['id' => $id, 'institution' => $institutionId]);
            $statement = $db->prepare(
                'UPDATE libros SET activo = 0
                 WHERE id = :id AND institucion_id = :institution AND activo = 1'
            );
            $statement->execute(['id' => $id, 'institution' => $institutionId]);
            $changed = $statement->rowCount() > 0;
            $db->commit();
            return $changed;
        } catch (\Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $exception;
        }
    }
}
