<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use DomainException;
use PDOException;
use RuntimeException;
use Throwable;

final class Institution
{
    public function isConfigured(): bool
    {
        return Database::connection()->query(
            'SELECT 1 FROM instituciones LIMIT 1'
        )->fetchColumn() !== false;
    }

    public function createInitialSetup(
        string $name,
        string $email,
        ?string $address,
        string $adminFirstName,
        string $adminLastName,
        string $adminEmail,
        string $adminPassword
    ): void {
        $db = Database::connection();
        $db->beginTransaction();

        try {
            if ($db->query('SELECT 1 FROM instituciones LIMIT 1')->fetchColumn() !== false) {
                throw new DomainException('La configuración inicial ya fue completada.');
            }

            $seedRoleStatement = $db->prepare(
                'INSERT INTO roles (nombre, descripcion)
                 VALUES (:name, :description)
                 ON CONFLICT (nombre) DO NOTHING'
            );
            foreach ([
                ['Administrador', 'Acceso a la configuracion y administracion avanzada.'],
                ['Bibliotecario', 'Perfil operativo: libros, usuarios, prestamos y devoluciones.'],
            ] as [$roleName, $roleDescription]) {
                $seedRoleStatement->execute([
                    'name' => $roleName,
                    'description' => $roleDescription,
                ]);
            }

            $roleStatement = $db->prepare(
                'SELECT id FROM roles WHERE nombre = :name LIMIT 1'
            );
            $roleStatement->execute(['name' => 'Administrador']);
            $roleId = $roleStatement->fetchColumn();
            if ($roleId === false) {
                throw new RuntimeException('No se pudo preparar el rol Administrador.');
            }

            $institutionStatement = $db->prepare(
                'INSERT INTO instituciones (nombre, correo, direccion)
                 VALUES (:name, :email, :address)'
            );
            $institutionStatement->execute([
                'name' => $name,
                'email' => $email,
                'address' => $address,
            ]);
            $institutionId = (int)$db->lastInsertId();

            $administratorStatement = $db->prepare(
                'INSERT INTO usuarios_sistema
                    (institucion_id, rol_id, nombre, apellidos, correo, contrasena_hash, activo)
                 VALUES
                    (:institution, :role, :first_name, :last_name, :email, :password_hash, 1)'
            );
            $administratorStatement->execute([
                'institution' => $institutionId,
                'role' => (int)$roleId,
                'first_name' => $adminFirstName,
                'last_name' => $adminLastName,
                'email' => $adminEmail,
                'password_hash' => password_hash($adminPassword, PASSWORD_DEFAULT),
            ]);

            $gradeStatement = $db->prepare(
                'INSERT INTO grados (nombre, orden)
                 VALUES (:name, :sort_order)
                 ON CONFLICT (nombre) DO NOTHING'
            );
            foreach ([
                ['Preescolar', 0],
                ['1°', 1],
                ['2°', 2],
                ['3°', 3],
                ['4°', 4],
                ['5°', 5],
                ['6°', 6],
                ['7°', 7],
                ['8°', 8],
                ['9°', 9],
                ['10°', 10],
                ['11°', 11],
            ] as [$gradeName, $gradeOrder]) {
                $gradeStatement->execute([
                    'name' => $gradeName,
                    'sort_order' => $gradeOrder,
                ]);
            }

            $formatStatement = $db->prepare(
                'INSERT INTO formatos (institucion_id, nombre) VALUES (:institution, :name)'
            );
            foreach (['Tapa dura', 'Rustica', 'Bolsillo', 'Otro'] as $format) {
                $formatStatement->execute(['institution' => $institutionId, 'name' => $format]);
            }

            $categoryStatement = $db->prepare(
                'INSERT INTO categorias (institucion_id, tipo, nombre)
                 VALUES (:institution, :type, :name)'
            );
            foreach ([
                ['genero', 'Novela'],
                ['genero', 'Poesia'],
                ['genero', 'Teatro'],
                ['genero', 'Ensayo'],
                ['categoria', 'Literatura'],
                ['categoria', 'Ciencias naturales'],
                ['categoria', 'Ciencias sociales'],
                ['categoria', 'Matematicas'],
                ['categoria', 'Tecnologia'],
                ['tema', 'Historia de Colombia'],
                ['tema', 'Medio ambiente'],
                ['tema', 'Valores y convivencia'],
            ] as [$type, $category]) {
                $categoryStatement->execute([
                    'institution' => $institutionId,
                    'type' => $type,
                    'name' => $category,
                ]);
            }

            $settingsStatement = $db->prepare(
                'INSERT INTO configuraciones
                    (institucion_id, clave, valor, tipo, descripcion)
                 VALUES
                    (:institution, :key, :value, :type, :description)'
            );
            foreach ([
                ['dias_prestamo', '7', 'Dias predeterminados para la devolucion de un ejemplar.'],
                ['limite_ejemplares', '3', 'Maximo de ejemplares activos permitidos por usuario.'],
                ['dias_alerta_vencimiento', '3', 'Dias de antelacion para marcar un prestamo como proximo a vencer.'],
            ] as [$key, $value, $description]) {
                $settingsStatement->execute([
                    'institution' => $institutionId,
                    'key' => $key,
                    'value' => $value,
                    'type' => 'entero',
                    'description' => $description,
                ]);
            }

            $db->commit();
        } catch (PDOException $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $message = $exception->getMessage();
            if ($exception->getCode() === '23000'
                && (str_contains($message, 'UNIQUE constraint failed: instituciones.correo')
                    || str_contains($message, 'UNIQUE constraint failed: usuarios_sistema.correo'))) {
                throw new DomainException('Ya existe una institución o cuenta con ese correo.', 0, $exception);
            }
            throw $exception;
        } catch (Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $exception;
        }
    }
}
