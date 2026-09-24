<?php

namespace App\Services;

use App\Core\Database;
use PDO;

final class SlugService
{
    public static function uniqueSeller(string $value, int $excludeId = 0, string $fallback = 'shop', ?PDO $db = null): string
    {
        $db ??= Database::connection();
        $base = trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower(trim($value))), '-');
        $base = $base !== '' ? $base : $fallback;
        $slug = $base;
        $counter = 2;
        $stmt = $db->prepare('SELECT id FROM seller_profiles WHERE slug = ? AND id <> ? LIMIT 1');

        while (true) {
            $stmt->execute([$slug, $excludeId]);
            if (!$stmt->fetchColumn()) {
                return $slug;
            }
            $slug = $base . '-' . $counter++;
        }
    }
}
