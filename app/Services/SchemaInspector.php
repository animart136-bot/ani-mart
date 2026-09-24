<?php

namespace App\Services;

use App\Core\Database;
use PDO;

/**
 * Central schema capability checks for legacy compatibility paths.
 * New schema changes still belong in database/migrations; runtime code should
 * query this service instead of duplicating SHOW COLUMNS logic.
 */
final class SchemaInspector
{
    private static array $columns = [];
    private static array $tables = [];
    private static array $indexes = [];

    public static function hasTable(string $table): bool
    {
        self::assertIdentifier($table);
        if (!array_key_exists($table, self::$tables)) {
            $stmt = Database::connection()->prepare(
                'SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? LIMIT 1'
            );
            $stmt->execute([$table]);
            self::$tables[$table] = (bool) $stmt->fetchColumn();
        }
        return self::$tables[$table];
    }

    public static function hasColumn(string $table, string $column): bool
    {
        self::assertIdentifier($table);
        self::assertIdentifier($column);
        $key = $table . '.' . $column;
        if (!array_key_exists($key, self::$columns)) {
            $stmt = Database::connection()->prepare(
                'SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1'
            );
            $stmt->execute([$table, $column]);
            self::$columns[$key] = (bool) $stmt->fetchColumn();
        }
        return self::$columns[$key];
    }

    public static function requireTable(string $table): void
    {
        if (!self::hasTable($table)) {
            throw new \RuntimeException("Database table {$table} is missing. Run: php tools/migrate.php migrate");
        }
    }

    public static function requireColumns(string $table, array $columns): void
    {
        self::requireTable($table);
        $missing = array_values(array_filter($columns, static fn (string $column): bool => !self::hasColumn($table, $column)));
        if ($missing) {
            throw new \RuntimeException(
                'Database columns ' . $table . '.' . implode(', ' . $table . '.', $missing) .
                ' are missing. Run: php tools/migrate.php migrate'
            );
        }
    }

    public static function hasIndex(string $table, string $index): bool
    {
        self::assertIdentifier($table);
        self::assertIdentifier($index);
        $key = $table . '.' . $index;
        if (!array_key_exists($key, self::$indexes)) {
            $stmt = Database::connection()->prepare(
                'SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ? LIMIT 1'
            );
            $stmt->execute([$table, $index]);
            self::$indexes[$key] = (bool) $stmt->fetchColumn();
        }
        return self::$indexes[$key];
    }

    public static function requireIndexes(string $table, array $indexes): void
    {
        self::requireTable($table);
        $missing = array_values(array_filter($indexes, static fn (string $index): bool => !self::hasIndex($table, $index)));
        if ($missing) {
            throw new \RuntimeException(
                'Database indexes ' . $table . '.' . implode(', ' . $table . '.', $missing) .
                ' are missing. Run: php tools/migrate.php migrate'
            );
        }
    }

    private static function assertIdentifier(string $value): void
    {
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $value)) {
            throw new \InvalidArgumentException('Unsafe database identifier.');
        }
    }
}
