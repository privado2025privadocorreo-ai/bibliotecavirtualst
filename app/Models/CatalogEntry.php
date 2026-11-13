<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use RuntimeException;

final class CatalogEntry
{
    private const TABLES = [
        'autor' => ['table' => 'autores', 'reference' => 'libro_autores', 'column' => 'autor_id'],
        'editorial' => ['table' => 'editoriales', 'reference' => 'libros', 'column' => 'editorial_id'],
        'categoria' => ['table' => 'categorias', 'reference' => 'libro_categorias', 'column' => 'categoria_id'],
        'formato' => ['table' => 'formatos', 'reference' => 'ejemplares', 'column' => 'formato_id'],
    ];

    public function list(string $type, int $institutionId): array
    {
        $table = $this->table($type);
        if ($type === 'autor') {
            $query = 'SELECT id, nombres AS nombre, apellidos FROM autores
                      WHERE institucion_id = :institution ORDER BY apellidos, nombres';
        } elseif ($type === 'categoria') {
            $query = 'SELECT id, tipo, nombre, descripcion FROM categorias
                      WHERE institucion_id = :institution ORDER BY tipo, nombre';
        } else {
            $query = 'SELECT id, nombre FROM ' . $table
                . ' WHERE institucion_id = :institution ORDER BY nombre';
        }
        $statement = Database::connection()->prepare($query);
        $statement->execute(['institution' => $institutionId]);
        return $statement->fetchAll();
    }

    public function find(string $type, int $id, int $institutionId): ?array
    {
        $table = $this->table($type);
        $statement = Database::connection()->prepare(
            'SELECT * FROM ' . $table . ' WHERE id = :id AND institucion_id = :institution LIMIT 1'
        );
        $statement->execute(['id' => $id, 'institution' => $institutionId]);
        $entry = $statement->fetch();
        return $entry === false ? null : $entry;
    }

    public function save(string $type, ?int $id, array $data, int $institutionId): void
    {
        $table = $this->table($type);
        if ($type === 'autor') {
            $fields = ['nombres' => $data['nombres'], 'apellidos' => $data['apellidos'] ?: null];
        } elseif ($type === 'categoria') {
            $fields = [
                'tipo' => $data['tipo'],
                'nombre' => $data['nombre'],
                'descripcion' => $data['descripcion'] ?: null,
            ];
        } else {
            $fields = ['nombre' => $data['nombre']];
        }
        $fields['institucion_id'] = $institutionId;

        if ($id === null) {
            $columns = array_keys($fields);
            $placeholders = array_map(static function (string $column): string {
                return ':' . $column;
            }, $columns);
            $statement = Database::connection()->prepare(
                'INSERT INTO ' . $table . ' (' . implode(', ', $columns) . ')
                 VALUES (' . implode(', ', $placeholders) . ')'
            );
            $statement->execute($fields);
            return;
        }

        $assignments = [];
        unset($fields['institucion_id']);
        foreach (array_keys($fields) as $column) {
            $assignments[] = $column . ' = :' . $column;
        }
        $fields['id'] = $id;
        $fields['institution'] = $institutionId;
        $statement = Database::connection()->prepare(
            'UPDATE ' . $table . ' SET ' . implode(', ', $assignments)
            . ' WHERE id = :id AND institucion_id = :institution'
        );
        $statement->execute($fields);
    }

    public function delete(string $type, int $id, int $institutionId): void
    {
        $definition = self::TABLES[$type] ?? null;
        if ($definition === null) {
            throw new RuntimeException('Tipo de catálogo inválido.');
        }
        $db = Database::connection();
        if ($type === 'categoria') {
            $statement = $db->prepare(
                'SELECT COUNT(*) FROM libro_categorias
                 WHERE categoria_id = :id AND institucion_id = :institution'
            );
            $statement->execute(['id' => $id, 'institution' => $institutionId]);
            $linked = (int)$statement->fetchColumn();
            $children = $db->prepare(
                'SELECT COUNT(*) FROM categorias WHERE padre_id = :id AND institucion_id = :institution'
            );
            $children->execute(['id' => $id, 'institution' => $institutionId]);
            $linked += (int)$children->fetchColumn();
        } else {
            $statement = $db->prepare(
                'SELECT COUNT(*) FROM ' . $definition['reference']
                . ' WHERE ' . $definition['column'] . ' = :id AND institucion_id = :institution'
            );
            $statement->execute(['id' => $id, 'institution' => $institutionId]);
            $linked = (int)$statement->fetchColumn();
        }
        if ($linked > 0) {
            throw new RuntimeException('No se puede eliminar: el elemento está asociado a libros o ejemplares.');
        }

        $statement = $db->prepare(
            'DELETE FROM ' . $definition['table'] . ' WHERE id = :id AND institucion_id = :institution'
        );
        $statement->execute(['id' => $id, 'institution' => $institutionId]);
        if ($statement->rowCount() !== 1) {
            throw new RuntimeException('El elemento no existe o ya fue eliminado.');
        }
    }

    private function table(string $type): string
    {
        $definition = self::TABLES[$type] ?? null;
        if ($definition === null) {
            throw new RuntimeException('Tipo de catálogo inválido.');
        }
        return $definition['table'];
    }
}
