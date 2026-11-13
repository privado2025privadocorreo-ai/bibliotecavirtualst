<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

final class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection !== null) {
            return self::$connection;
        }

        $databasePath = Env::get('DB_PATH');
        if ($databasePath === null || $databasePath === '') {
            $databasePath = self::defaultDatabasePath();
        }

        if ($databasePath !== ':memory:' && !self::isAbsolutePath($databasePath)) {
            $databasePath = dirname(__DIR__) . DIRECTORY_SEPARATOR
                . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $databasePath);
        }

        if ($databasePath !== ':memory:') {
            $directory = dirname($databasePath);
            if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
                throw new RuntimeException('No se pudo crear la carpeta de la base de datos SQLite.');
            }
        }

        try {
            $connection = new PDO(
                'sqlite:' . $databasePath,
                null,
                null,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
            $connection->exec('PRAGMA foreign_keys = ON');
            $connection->exec('PRAGMA busy_timeout = 5000');
            self::initializeSchema($connection);
            self::$connection = $connection;
        } catch (PDOException $exception) {
            error_log('Database connection failed: ' . $exception->getMessage());
            throw new RuntimeException(
                'No fue posible abrir SQLite. Verifica que PDO SQLite esté habilitado y que DB_PATH sea accesible.'
            );
        }

        return self::$connection;
    }

    private static function initializeSchema(PDO $connection): void
    {
        $table = $connection->query(
            "SELECT name FROM sqlite_master WHERE type = 'table' AND name = 'instituciones'"
        )->fetchColumn();
        if ($table !== false) {
            return;
        }

        $schemaPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'database'
            . DIRECTORY_SEPARATOR . '01_schema.sql';
        $schema = file_get_contents($schemaPath);
        if ($schema === false) {
            throw new RuntimeException('No se pudo leer el esquema SQLite de la aplicación.');
        }

        $connection->exec('BEGIN IMMEDIATE');
        try {
            $table = $connection->query(
                "SELECT name FROM sqlite_master WHERE type = 'table' AND name = 'instituciones'"
            )->fetchColumn();
            if ($table === false) {
                $connection->exec($schema);
            }
            $connection->exec('COMMIT');
        } catch (\Throwable $exception) {
            $connection->exec('ROLLBACK');
            throw $exception;
        }
    }

    private static function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, DIRECTORY_SEPARATOR)
            || preg_match('/^[A-Za-z]:[\\\\\/]/', $path) === 1;
    }

    private static function defaultDatabasePath(): string
    {
        $localAppData = Env::get('LOCALAPPDATA');
        if ($localAppData !== null && $localAppData !== '') {
            return $localAppData . DIRECTORY_SEPARATOR . 'BibliotecaVirtualST'
                . DIRECTORY_SEPARATOR . 'biblioteca.sqlite';
        }

        return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'database'
            . DIRECTORY_SEPARATOR . 'biblioteca.sqlite';
    }
}
