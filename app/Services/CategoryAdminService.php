<?php

namespace App\Services;

use App\Core\Database;
use InvalidArgumentException;
use PDO;

class CategoryAdminService
{
    private static bool $schemaReady = false;

    public function ensureSchema(): void
    {
        if (self::$schemaReady) return;
        SchemaInspector::requireColumns('categories', ['is_active', 'updated_at']);
        self::$schemaReady = true;
    }

    public function all(): array
    {
        $this->ensureSchema();
        return Database::connection()->query(
            'SELECT c.*, COUNT(p.id) AS product_count
             FROM categories c LEFT JOIN products p ON p.category_id = c.id
             GROUP BY c.id ORDER BY c.is_active DESC, c.name ASC'
        )->fetchAll();
    }

    public function save(array $input): int
    {
        $this->ensureSchema();
        $id = (int) ($input['id'] ?? 0);
        $name = trim((string) ($input['name'] ?? ''));
        if ($name === '' || mb_strlen($name) > 100) throw new InvalidArgumentException('Enter a category name up to 100 characters.');

        $db = Database::connection();
        $duplicate = $db->prepare('SELECT id FROM categories WHERE LOWER(name) = LOWER(?) AND id <> ? LIMIT 1');
        $duplicate->execute([$name, $id]);
        if ($duplicate->fetchColumn()) throw new InvalidArgumentException('A category with this name already exists.');

        if ($id > 0) {
            $hasSlug = SchemaInspector::hasColumn('categories', 'slug');
            if ($hasSlug) {
                $stmt = $db->prepare('UPDATE categories SET name = ?, slug = ?, updated_at = NOW() WHERE id = ?');
                $stmt->execute([$name, $this->uniqueSlug($db, $name, $id), $id]);
            } else {
                $stmt = $db->prepare('UPDATE categories SET name = ?, updated_at = NOW() WHERE id = ?');
                $stmt->execute([$name, $id]);
            }
            if ($stmt->rowCount() === 0 && !$this->find($id)) throw new InvalidArgumentException('Category not found.');
            return $id;
        }
        $hasSlug = SchemaInspector::hasColumn('categories', 'slug');
        if ($hasSlug) {
            $stmt = $db->prepare('INSERT INTO categories (name, slug, is_active, created_at, updated_at) VALUES (?, ?, 1, NOW(), NOW())');
            $stmt->execute([$name, $this->uniqueSlug($db, $name)]);
        } else {
            $stmt = $db->prepare('INSERT INTO categories (name, is_active, created_at, updated_at) VALUES (?, 1, NOW(), NOW())');
            $stmt->execute([$name]);
        }
        return (int) $db->lastInsertId();
    }

    public function setActive(int $id, bool $active): array
    {
        $this->ensureSchema();
        $category = $this->find($id);
        if (!$category) throw new InvalidArgumentException('Category not found.');
        $stmt = Database::connection()->prepare('UPDATE categories SET is_active = ?, updated_at = NOW() WHERE id = ?');
        $stmt->execute([$active ? 1 : 0, $id]);
        return ['name' => (string) $category->name, 'old_status' => !empty($category->is_active) ? 'active' : 'archived', 'new_status' => $active ? 'active' : 'archived'];
    }

    private function find(int $id)
    {
        $stmt = Database::connection()->prepare('SELECT * FROM categories WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    private function uniqueSlug(PDO $db, string $name, int $excludeId = 0): string
    {
        $base = strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', $name), '-')) ?: 'category';
        $slug = $base;
        $counter = 2;
        $stmt = $db->prepare('SELECT id FROM categories WHERE slug = ? AND id <> ? LIMIT 1');
        while (true) {
            $stmt->execute([$slug, $excludeId]);
            if (!$stmt->fetchColumn()) return $slug;
            $slug = $base . '-' . $counter++;
        }
    }
}
