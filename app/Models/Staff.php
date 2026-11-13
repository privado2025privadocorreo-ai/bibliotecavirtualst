<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
final class Staff
{
    public function findByEmail(string $email): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT us.id, us.institucion_id, us.rol_id, us.nombre, us.apellidos,
                    us.correo, us.contrasena_hash, us.activo, r.nombre AS rol
             FROM usuarios_sistema us
             INNER JOIN roles r ON r.id = us.rol_id
             WHERE us.correo = :email
             LIMIT 1'
        );
        $statement->execute(['email' => $email]);
        $staff = $statement->fetch();
        return $staff === false ? null : $staff;
    }

    public function recordLogin(int $id): void
    {
        $statement = Database::connection()->prepare(
            'UPDATE usuarios_sistema SET ultimo_acceso = CURRENT_TIMESTAMP WHERE id = :id'
        );
        $statement->execute(['id' => $id]);
    }

    public function updatePasswordHash(int $id, string $hash): void
    {
        $statement = Database::connection()->prepare(
            'UPDATE usuarios_sistema SET contrasena_hash = :hash WHERE id = :id'
        );
        $statement->execute(['hash' => $hash, 'id' => $id]);
    }

    public function changeOwnPassword(
        int $id,
        int $institutionId,
        string $currentPassword,
        string $newPassword
    ): void {
        if (strlen($newPassword) < 8) {
            throw new \RuntimeException('La nueva contraseña debe tener al menos 8 caracteres.');
        }

        $db = Database::connection();
        $statement = $db->prepare(
            'SELECT contrasena_hash FROM usuarios_sistema
             WHERE id = :id AND institucion_id = :institution AND activo = 1'
        );
        $statement->execute(['id' => $id, 'institution' => $institutionId]);
        $currentHash = $statement->fetchColumn();
        if (!is_string($currentHash)) {
            throw new \RuntimeException('La cuenta activa no está disponible.');
        }
        if (!password_verify($currentPassword, $currentHash)) {
            throw new \RuntimeException('La contraseña actual no es correcta.');
        }
        if (password_verify($newPassword, $currentHash)) {
            throw new \RuntimeException('La nueva contraseña debe ser diferente a la actual.');
        }

        $update = $db->prepare(
            'UPDATE usuarios_sistema SET contrasena_hash = :new_hash
             WHERE id = :id AND institucion_id = :institution
               AND activo = 1 AND contrasena_hash = :current_hash'
        );
        $update->execute([
            'new_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
            'id' => $id,
            'institution' => $institutionId,
            'current_hash' => $currentHash,
        ]);
        if ($update->rowCount() !== 1) {
            throw new \RuntimeException('La contraseña cambió en otra solicitud. Vuelve a intentarlo.');
        }
    }

    public function all(int $institutionId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT us.id, us.rol_id, us.nombre, us.apellidos, us.correo, us.activo,
                    us.ultimo_acceso, r.nombre AS rol
             FROM usuarios_sistema us
             INNER JOIN roles r ON r.id = us.rol_id
             WHERE us.institucion_id = :institution
             ORDER BY us.apellidos, us.nombre'
        );
        $statement->execute(['institution' => $institutionId]);
        return $statement->fetchAll();
    }

    public function find(int $id, int $institutionId): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT us.id, us.institucion_id, us.rol_id, us.nombre, us.apellidos,
                    us.correo, us.activo, r.nombre AS rol
             FROM usuarios_sistema us
             INNER JOIN roles r ON r.id = us.rol_id
             WHERE us.id = :id AND us.institucion_id = :institution LIMIT 1'
        );
        $statement->execute(['id' => $id, 'institution' => $institutionId]);
        $staff = $statement->fetch();
        return $staff === false ? null : $staff;
    }

    public function countActiveAdministrators(int $institutionId): int
    {
        $statement = Database::connection()->prepare(
            "SELECT COUNT(*) FROM usuarios_sistema us
             INNER JOIN roles r ON r.id = us.rol_id
             WHERE us.institucion_id = :institution AND us.activo = 1
               AND r.nombre = 'Administrador'"
        );
        $statement->execute(['institution' => $institutionId]);
        return (int)$statement->fetchColumn();
    }

    public function roles(): array
    {
        return Database::connection()->query(
            'SELECT id, nombre FROM roles ORDER BY id'
        )->fetchAll();
    }

    public function save(?int $id, array $data, int $institutionId): void
    {
        $db = Database::connection();
        if ($id === null) {
            $statement = $db->prepare(
                'INSERT INTO usuarios_sistema
                    (institucion_id, rol_id, nombre, apellidos, correo, contrasena_hash, activo)
                 VALUES
                    (:institution, :role, :first_name, :last_name, :email, :password_hash, 1)'
            );
            $statement->execute([
                'institution' => $institutionId,
                'role' => $data['rol_id'],
                'first_name' => $data['nombre'],
                'last_name' => $data['apellidos'],
                'email' => $data['correo'],
                'password_hash' => password_hash($data['contrasena'], PASSWORD_DEFAULT),
            ]);
            return;
        }

        $sql = 'UPDATE usuarios_sistema
                SET rol_id = :role, nombre = :first_name, apellidos = :last_name, correo = :email';
        $parameters = [
            'role' => $data['rol_id'],
            'first_name' => $data['nombre'],
            'last_name' => $data['apellidos'],
            'email' => $data['correo'],
            'id' => $id,
        ];
        if ($data['contrasena'] !== '') {
            $sql .= ', contrasena_hash = :password_hash';
            $parameters['password_hash'] = password_hash($data['contrasena'], PASSWORD_DEFAULT);
        }
        $sql .= ' WHERE id = :id AND institucion_id = :institution';
        $parameters['institution'] = $institutionId;
        $statement = $db->prepare($sql);
        $statement->execute($parameters);
    }

    public function delete(int $id, int $institutionId, int $currentId): void
    {
        $db = Database::connection();
        $db->beginTransaction();
        try {
            $institution = $db->prepare('SELECT id FROM instituciones WHERE id = :id');
            $institution->execute(['id' => $institutionId]);
            if ($institution->fetchColumn() === false) {
                throw new \RuntimeException('La institución ya no existe.');
            }

            if ($id === $currentId) {
                throw new \RuntimeException('No puedes eliminar la cuenta con la que iniciaste sesión.');
            }

            $target = $this->find($id, $institutionId);
            if ($target === null) {
                throw new \RuntimeException('La cuenta no existe en esta institución.');
            }
            if ($target['rol'] === 'Administrador' && (int)$target['activo'] === 1
                && $this->countActiveAdministrators($institutionId) <= 1) {
                throw new \RuntimeException('Debe permanecer al menos una cuenta administradora activa.');
            }

            $preserveLoans = $db->prepare(
                'UPDATE prestamos SET registrado_por = NULL
                 WHERE institucion_id = :institution AND registrado_por = :staff'
            );
            $preserveLoans->execute(['institution' => $institutionId, 'staff' => $id]);

            $statement = $db->prepare(
                'DELETE FROM usuarios_sistema WHERE id = :id AND institucion_id = :institution'
            );
            $statement->execute(['id' => $id, 'institution' => $institutionId]);
            if ($statement->rowCount() !== 1) {
                throw new \RuntimeException('No se pudo eliminar la cuenta de personal.');
            }
            $db->commit();
        } catch (\Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $exception;
        }
    }

    public function institution(int $id): array
    {
        $statement = Database::connection()->prepare(
            'SELECT id, nombre, correo, direccion, logotipo FROM instituciones WHERE id = :id'
        );
        $statement->execute(['id' => $id]);
        return $statement->fetch() ?: [];
    }

    public function updateInstitution(array $data): void
    {
        $statement = Database::connection()->prepare(
            'UPDATE instituciones
             SET nombre = :name, correo = :email, direccion = :address, logotipo = :logo
             WHERE id = :id'
        );
        $statement->execute([
            'name' => $data['nombre'],
            'email' => $data['correo'],
            'address' => $data['direccion'],
            'logo' => $data['logotipo'],
            'id' => $data['id'],
        ]);
    }

    public function deleteInstitution(int $institutionId): ?string
    {
        $db = Database::connection();
        $db->beginTransaction();
        try {
            $statement = $db->prepare(
                'SELECT logotipo FROM instituciones WHERE id = :id'
            );
            $statement->execute(['id' => $institutionId]);
            $logo = $statement->fetchColumn();
            if ($logo === false) {
                throw new \RuntimeException('La institución ya no existe.');
            }

            foreach ([
                'prestamos_activos',
                'prestamos',
                'lotes_prestamos',
                'libro_autores',
                'libro_categorias',
                'ejemplares',
                'libros',
                'usuarios',
                'usuarios_sistema',
                'configuraciones',
            ] as $table) {
                $delete = $db->prepare("DELETE FROM {$table} WHERE institucion_id = :institution");
                $delete->execute(['institution' => $institutionId]);
            }

            $db->prepare(
                'UPDATE categorias SET padre_id = NULL WHERE institucion_id = :institution'
            )->execute(['institution' => $institutionId]);
            foreach (['categorias', 'editoriales', 'autores', 'formatos'] as $table) {
                $delete = $db->prepare("DELETE FROM {$table} WHERE institucion_id = :institution");
                $delete->execute(['institution' => $institutionId]);
            }

            $deleteInstitution = $db->prepare('DELETE FROM instituciones WHERE id = :id');
            $deleteInstitution->execute(['id' => $institutionId]);
            if ($deleteInstitution->rowCount() !== 1) {
                throw new \RuntimeException('No se pudo eliminar la institución.');
            }
            $db->commit();
            return is_string($logo) && $logo !== '' ? $logo : null;
        } catch (\Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $exception;
        }
    }
}
