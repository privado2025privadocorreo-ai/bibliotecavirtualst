<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Settings
{
    public function all(int $institutionId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT clave, valor, descripcion FROM configuraciones
             WHERE institucion_id = :institution ORDER BY clave'
        );
        $statement->execute(['institution' => $institutionId]);
        $settings = $statement->fetchAll();
        $keys = array_column($settings, 'clave');
        foreach (['dias_prestamo', 'limite_ejemplares', 'dias_alerta_vencimiento'] as $requiredKey) {
            if (!in_array($requiredKey, $keys, true)) {
                throw new \RuntimeException('Falta el parámetro requerido: ' . $requiredKey . '.');
            }
        }
        return $settings;
    }

    public function update(array $values, int $institutionId): void
    {
        $db = Database::connection();
        $db->beginTransaction();
        try {
            $statement = $db->prepare(
                "UPDATE configuraciones SET valor = :value
                 WHERE institucion_id = :institution AND clave = :key AND tipo = 'entero'"
            );
            foreach ($values as $key => $value) {
                $statement->execute([
                    'value' => (string)$value,
                    'key' => $key,
                    'institution' => $institutionId,
                ]);
                if ($statement->rowCount() === 0) {
                    $check = $db->prepare(
                        "SELECT COUNT(*) FROM configuraciones
                         WHERE institucion_id = :institution AND clave = :key
                           AND valor = :value AND tipo = 'entero'"
                    );
                    $check->execute([
                        'key' => $key,
                        'value' => (string)$value,
                        'institution' => $institutionId,
                    ]);
                    if ((int)$check->fetchColumn() === 0) {
                        throw new \RuntimeException('No se pudo guardar la configuración ' . $key . '.');
                    }
                }
            }
            $db->commit();
        } catch (\Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $exception;
        }
    }
}
