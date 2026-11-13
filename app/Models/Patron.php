<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Patron
{
    public function list(string $term, int $institutionId): array
    {
        $sql = 'SELECT u.id, u.documento, u.nombres, u.apellidos, u.correo,
                       u.telefono, u.tipo_usuario, u.activo, g.nombre AS grado
                FROM usuarios u
                LEFT JOIN grados g ON g.id = u.grado_id
                WHERE u.institucion_id = :institution';
        $parameters = ['institution' => $institutionId];
        if ($term !== '') {
            $sql .= ' AND (u.documento LIKE :document OR u.nombres LIKE :first_name
                      OR u.apellidos LIKE :last_name OR u.correo LIKE :email';
            $sql .= ')';
            $like = '%' . $term . '%';
            $parameters = [
                'institution' => $institutionId,
                'document' => $like,
                'first_name' => $like,
                'last_name' => $like,
                'email' => $like,
            ];
        }
        $sql .= ' ORDER BY u.apellidos, u.nombres LIMIT 200';
        $statement = Database::connection()->prepare($sql);
        $statement->execute($parameters);
        return $statement->fetchAll();
    }

    public function find(int $id, int $institutionId): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT * FROM usuarios WHERE id = :id AND institucion_id = :institution LIMIT 1'
        );
        $statement->execute(['id' => $id, 'institution' => $institutionId]);
        $patron = $statement->fetch();
        return $patron === false ? null : $patron;
    }

    public function grades(): array
    {
        return Database::connection()->query(
            'SELECT id, nombre FROM grados ORDER BY orden, nombre'
        )->fetchAll();
    }

    public function save(?int $id, array $data, int $institutionId): void
    {
        $fields = [
            'institution' => $institutionId,
            'grade' => $data['grado_id'] !== '' ? $data['grado_id'] : null,
            'document_type' => $data['tipo_documento'] !== '' ? $data['tipo_documento'] : null,
            'document' => $data['documento'],
            'first_name' => $data['nombres'],
            'last_name' => $data['apellidos'],
            'email' => $data['correo'] !== '' ? $data['correo'] : null,
            'phone' => $data['telefono'] !== '' ? $data['telefono'] : null,
            'address' => $data['direccion'] !== '' ? $data['direccion'] : null,
            'user_type' => $data['tipo_usuario'] !== '' ? $data['tipo_usuario'] : null,
        ];
        $db = Database::connection();
        if ($id === null) {
            $statement = $db->prepare(
                'INSERT INTO usuarios
                    (institucion_id, grado_id, tipo_documento, documento, nombres,
                     apellidos, correo, telefono, direccion, tipo_usuario, activo)
                 VALUES
                    (:institution, :grade, :document_type, :document, :first_name,
                     :last_name, :email, :phone, :address, :user_type, 1)'
            );
            $statement->execute($fields);
            return;
        }

        $fields['id'] = $id;
        $statement = $db->prepare(
            'UPDATE usuarios SET grado_id = :grade, tipo_documento = :document_type,
                documento = :document, nombres = :first_name, apellidos = :last_name,
                correo = :email, telefono = :phone, direccion = :address,
                tipo_usuario = :user_type WHERE id = :id AND institucion_id = :institution'
        );
        $statement->execute($fields);
    }

    public function deactivate(int $id, int $institutionId): bool
    {
        $db = Database::connection();
        $db->beginTransaction();
        try {
            $lock = $db->prepare(
                'SELECT activo FROM usuarios WHERE id = :id AND institucion_id = :institution'
            );
            $lock->execute(['id' => $id, 'institution' => $institutionId]);
            if ($lock->fetchColumn() === false) {
                $db->rollBack();
                return false;
            }

        $statement = Database::connection()->prepare(
            "SELECT COUNT(*) FROM prestamos p
             INNER JOIN usuarios u ON u.id = p.usuario_id
             WHERE u.id = :id AND u.institucion_id = :institution AND p.estado = 'activo'"
        );
        $statement->execute(['id' => $id, 'institution' => $institutionId]);
        if ((int)$statement->fetchColumn() > 0) {
                $db->rollBack();
            return false;
        }

        $statement = $db->prepare(
            'UPDATE usuarios SET activo = 0 WHERE id = :id AND institucion_id = :institution'
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
