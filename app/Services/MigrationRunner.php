<?php

namespace App\Services;

use App\Core\Database;
use PDO;
use RuntimeException;
use Throwable;

/** Explicit, tracked database migration runner. Never runs during web requests. */
final class MigrationRunner
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::connection();
    }

    public function migrate(string $directory): array
    {
        $this->ensureRepository();
        $this->lock();
        try {
            $applied = $this->applied();
            $executed = [];
            foreach ($this->files($directory) as $file) {
                $name = basename($file);
                if (isset($applied[$name])) {
                    if (!hash_equals($applied[$name], hash_file('sha256', $file))) {
                        throw new RuntimeException("Applied migration was modified: {$name}");
                    }
                    continue;
                }

                $sql = trim((string) file_get_contents($file));
                if ($sql !== '') {
                    $this->db->exec($sql);
                }
                $stmt = $this->db->prepare('INSERT INTO schema_migrations (migration, checksum, applied_at) VALUES (?, ?, NOW())');
                $stmt->execute([$name, hash_file('sha256', $file)]);
                $executed[] = $name;
            }
            return $executed;
        } finally {
            $this->unlock();
        }
    }

    /** Mark existing files as applied for an already-provisioned legacy database. */
    public function baseline(string $directory): array
    {
        $this->ensureRepository();
        $this->lock();
        try {
            $applied = $this->applied();
            $recorded = [];
            $stmt = $this->db->prepare('INSERT INTO schema_migrations (migration, checksum, applied_at) VALUES (?, ?, NOW())');
            foreach ($this->files($directory) as $file) {
                $name = basename($file);
                if (isset($applied[$name])) {
                    continue;
                }
                $stmt->execute([$name, hash_file('sha256', $file)]);
                $recorded[] = $name;
            }
            return $recorded;
        } finally {
            $this->unlock();
        }
    }

    public function status(string $directory): array
    {
        $this->ensureRepository();
        $applied = $this->applied();
        return array_map(static function (string $file) use ($applied): array {
            $name = basename($file);
            return [
                'migration' => $name,
                'status' => isset($applied[$name]) ? 'applied' : 'pending',
                'checksum_valid' => !isset($applied[$name]) || hash_equals($applied[$name], hash_file('sha256', $file)),
            ];
        }, $this->files($directory));
    }

    private function ensureRepository(): void
    {
        $this->db->exec(
            'CREATE TABLE IF NOT EXISTS schema_migrations (' .
            'migration VARCHAR(255) PRIMARY KEY, checksum CHAR(64) NOT NULL, applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP' .
            ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    private function applied(): array
    {
        $rows = $this->db->query('SELECT migration, checksum FROM schema_migrations')->fetchAll(PDO::FETCH_KEY_PAIR);
        return is_array($rows) ? $rows : [];
    }

    private function files(string $directory): array
    {
        $files = glob(rtrim($directory, '/') . '/*.sql') ?: [];
        sort($files, SORT_NATURAL);
        return $files;
    }

    private function lock(): void
    {
        $stmt = $this->db->query("SELECT GET_LOCK('ani_mart_schema_migrations', 10)");
        if ((int) $stmt->fetchColumn() !== 1) {
            throw new RuntimeException('Could not acquire the migration lock.');
        }
    }

    private function unlock(): void
    {
        try {
            $this->db->query("SELECT RELEASE_LOCK('ani_mart_schema_migrations')");
        } catch (Throwable) {
            // The connection closing also releases the lock.
        }
    }
}
