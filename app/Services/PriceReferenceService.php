<?php

namespace App\Services;

use App\Core\Database;
use DateTimeImmutable;
use InvalidArgumentException;
use PDO;

class PriceReferenceService
{
    public const CURRENT_DAYS = 7;
    public const MAX_AGE_DAYS = 30;

    public function pageData(array $input = []): array
    {
        (new SmartPriceCheckService())->ensureSchema();
        $db = Database::connection();
        $filters = [
            'q' => mb_substr(trim((string) ($input['q'] ?? '')), 0, 120),
            'category' => mb_substr(trim((string) ($input['category'] ?? '')), 0, 120),
            'status' => in_array(($input['status'] ?? ''), ['new', 'current', 'needs_update', 'outdated'], true)
                ? (string) $input['status']
                : '',
        ];
        $where = ['is_active = 1'];
        $params = [];
        if ($filters['q'] !== '') {
            $searchColumns = ['commodity', 'category', 'municipality'];
            $where[] = '(' . implode(' OR ', array_map(static fn(string $column): string => "COALESCE({$column}, '') LIKE ?", $searchColumns)) . ')';
            $search = '%' . $filters['q'] . '%';
            $params = array_merge($params, array_fill(0, count($searchColumns), $search));
        }
        if ($filters['category'] !== '') {
            $where[] = 'category = ?';
            $params[] = $filters['category'];
        }
        if ($filters['status'] === 'new') $where[] = 'DATE(created_at) = CURDATE()';
        if ($filters['status'] === 'current') $where[] = 'DATEDIFF(CURDATE(), price_date) <= 7';
        if ($filters['status'] === 'needs_update') $where[] = 'DATEDIFF(CURDATE(), price_date) BETWEEN 8 AND 30';
        if ($filters['status'] === 'outdated') $where[] = 'DATEDIFF(CURDATE(), price_date) > 30';
        $whereSql = implode(' AND ', $where);

        $recordStmt = $db->prepare(
            "SELECT *, DATEDIFF(CURDATE(), price_date) AS age_days,
                    DATE(created_at) = CURDATE() AS is_new_today
             FROM price_references
             WHERE {$whereSql}
             ORDER BY is_new_today DESC, updated_at DESC, price_date DESC, commodity ASC, id DESC"
        );
        $recordStmt->execute($params);
        $records = $recordStmt->fetchAll(PDO::FETCH_ASSOC);

        $countStmt = $db->prepare("SELECT COUNT(*) FROM price_references WHERE {$whereSql}");
        $countStmt->execute($params);
        $filteredCount = (int) $countStmt->fetchColumn();
        (new CategoryAdminService())->ensureSchema();
        $categories = $db->query(
            "SELECT name FROM categories
             WHERE is_active = 1
             ORDER BY name ASC"
        )->fetchAll(PDO::FETCH_COLUMN);

        $totals = $db->query(
            'SELECT COUNT(*) AS total,
                    SUM(DATEDIFF(CURDATE(), price_date) <= 7) AS current_total,
                    SUM(DATEDIFF(CURDATE(), price_date) BETWEEN 8 AND 30) AS needs_update_total,
                    SUM(DATEDIFF(CURDATE(), price_date) > 30) AS outdated_total,
                    SUM(DATE(created_at) = CURDATE()) AS new_today_total,
                    MAX(updated_at) AS last_updated
             FROM price_references WHERE is_active = 1'
        )->fetch(PDO::FETCH_ASSOC) ?: [];
        $summary = [
            'current' => (int) ($totals['current_total'] ?? 0),
            'needs_update' => (int) ($totals['needs_update_total'] ?? 0),
            'outdated' => (int) ($totals['outdated_total'] ?? 0),
        ];
        foreach ($records as &$record) {
            $record['freshness_status'] = self::freshnessStatus((int) $record['age_days']);
        }
        unset($record);
        return [
            'count' => (int) ($totals['total'] ?? 0),
            'newToday' => (int) ($totals['new_today_total'] ?? 0),
            'lastUpdated' => $totals['last_updated'] ?? null,
            'records' => $records,
            'filteredCount' => $filteredCount,
            'referenceCategories' => $categories,
            'filters' => $filters,
            'freshnessSummary' => $summary,
        ];
    }

    public function addManual(array $input): array
    {
        (new SmartPriceCheckService())->ensureSchema();
        $row = $this->validatedManualRow($input, date('Y-m-d'));

        $db = Database::connection();
        $db->beginTransaction();
        try {
            $saved = $this->upsertReference($db, $row);
            $db->commit();
            return $saved;
        } catch (\Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            throw $e;
        }
    }

    public function updateManual(int $id, array $input): array
    {
        (new SmartPriceCheckService())->ensureSchema();
        if ($id <= 0) throw new InvalidArgumentException('Reference-price record not found.');

        $db = Database::connection();
        $db->beginTransaction();
        try {
            $stmt = $db->prepare('SELECT * FROM price_references WHERE id = ? FOR UPDATE');
            $stmt->execute([$id]);
            $original = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$original) throw new InvalidArgumentException('Reference-price record not found.');
            if (empty($original['is_active'])) throw new InvalidArgumentException('Only a current reference price can be edited.');
            $row = $this->validatedManualRow($input, (string) $original['price_date']);

            [$keySql, $keyValues] = $this->referenceKey($row);
            $duplicate = $db->prepare('SELECT id FROM price_references WHERE id <> ? AND price_date = ? AND ' . $keySql . ' LIMIT 1');
            $duplicate->execute([$id, $row['price_date'], ...$keyValues]);
            if ($duplicate->fetchColumn()) throw new InvalidArgumentException('A matching reference already exists for this price date. Edit that record instead.');

            $deactivate = $db->prepare('UPDATE price_references SET is_active = 0, updated_at = NOW() WHERE id <> ? AND is_active = 1 AND ' . $keySql);
            $deactivate->execute([$id, ...$keyValues]);
            $update = $db->prepare('UPDATE price_references SET commodity = ?, normalized_commodity = ?, category = ?, unit = ?, municipality = ?, reference_price = ?, minimum_price = ?, maximum_price = ?, price_date = ?, is_active = 1, updated_at = NOW() WHERE id = ?');
            $update->execute([...array_values($row), $id]);
            $db->commit();

            return [
                'old_record' => $original,
                'new_record' => array_merge($row, ['id' => $id, 'is_active' => 1]),
            ];
        } catch (\Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            throw $e;
        }
    }

    public function deleteManual(int $id): array
    {
        (new SmartPriceCheckService())->ensureSchema();
        if ($id <= 0) throw new InvalidArgumentException('Reference-price record not found.');

        $db = Database::connection();
        $db->beginTransaction();
        try {
            $stmt = $db->prepare('SELECT * FROM price_references WHERE id = ? FOR UPDATE');
            $stmt->execute([$id]);
            $record = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$record || empty($record['is_active'])) {
                throw new InvalidArgumentException('The reference price has already been deleted or does not exist.');
            }

            $delete = $db->prepare('UPDATE price_references SET is_active = 0, updated_at = NOW() WHERE id = ?');
            $delete->execute([$id]);
            $db->commit();

            return $record;
        } catch (\Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            throw $e;
        }
    }

    public function prepareActivation(PDO $db, array $row): int
    {
        [$keySql, $values] = $this->referenceKey($row);
        $newer = $db->prepare('SELECT 1 FROM price_references WHERE is_active = 1 AND ' . $keySql . ' AND price_date > ? LIMIT 1');
        $newer->execute([...$values, $row['price_date']]);
        if ($newer->fetchColumn()) return 0;

        $stmt = $db->prepare(
            'UPDATE price_references SET is_active = 0, updated_at = NOW()
             WHERE is_active = 1 AND ' . $keySql
        );
        $stmt->execute($values);
        return 1;
    }

    public function upsertReference(PDO $db, array $row): array
    {
        [$keySql, $keyValues] = $this->referenceKey($row);
        $existing = $db->prepare('SELECT id FROM price_references WHERE price_date = ? AND ' . $keySql . ' ORDER BY id DESC LIMIT 1 FOR UPDATE');
        $existing->execute([$row['price_date'], ...$keyValues]);
        $existingId = (int) ($existing->fetchColumn() ?: 0);
        $isActive = $this->prepareActivation($db, $row);

        if ($existingId > 0) {
            $update = $db->prepare('UPDATE price_references SET commodity = ?, normalized_commodity = ?, category = ?, unit = ?, municipality = ?, reference_price = ?, minimum_price = ?, maximum_price = ?, price_date = ?, is_active = ?, updated_at = NOW() WHERE id = ?');
            $update->execute([...array_values($row), $isActive, $existingId]);
            return ['id' => $existingId, 'action' => 'updated'];
        }

        $insert = $db->prepare('INSERT INTO price_references (commodity, normalized_commodity, category, unit, municipality, reference_price, minimum_price, maximum_price, price_date, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
        $insert->execute([...array_values($row), $isActive]);
        return ['id' => (int) $db->lastInsertId(), 'action' => 'inserted'];
    }

    public static function freshnessStatus(int $ageDays): string
    {
        if ($ageDays <= self::CURRENT_DAYS) return 'current';
        if ($ageDays <= self::MAX_AGE_DAYS) return 'needs_update';
        return 'outdated';
    }

    public function sendStaleReminders(): int
    {
        $data = $this->pageData();
        $staleCount = $data['freshnessSummary']['needs_update'] + $data['freshnessSummary']['outdated'];
        if ($data['count'] > 0 && $staleCount === 0) return 0;

        $db = Database::connection();
        $admins = $db->query("SELECT id FROM users WHERE LOWER(role) = 'admin'")->fetchAll(PDO::FETCH_COLUMN);
        $title = $data['count'] === 0 ? 'Price References Required' : 'Price References Need Updating';
        $message = $data['count'] === 0
            ? 'Smart Price Check has no active reference prices. Add local reference prices for the available categories.'
            : $staleCount . ' active reference price' . ($staleCount === 1 ? '' : 's') . ' should be reviewed or updated.';
        $sent = 0;
        $alreadySent = $db->prepare('SELECT 1 FROM notifications WHERE user_id = ? AND title = ? AND DATE(created_at) = CURDATE() LIMIT 1');
        foreach ($admins as $adminId) {
            $alreadySent->execute([(int) $adminId, $title]);
            if ($alreadySent->fetchColumn()) continue;
            NotificationService::send((int) $adminId, $title, $message, 'system', 'bi-currency-exchange', 'warning', '/ani-mart/public/admin/reference-data/prices', 'price_reference', null, '/ani-mart/public/admin/reference-data/prices');
            $sent++;
        }
        return $sent;
    }

    private function nullablePrice($value): ?float
    {
        if ($value === null || trim((string) $value) === '') return null;
        $price = filter_var($value, FILTER_VALIDATE_FLOAT);
        if ($price === false || (float) $price < 0) throw new InvalidArgumentException('Minimum and maximum prices cannot be negative.');
        return (float) $price;
    }

    private function validatedManualRow(array $input, string $priceDate): array
    {
        $commodity = trim((string) ($input['commodity'] ?? ''));
        $category = trim((string) ($input['category'] ?? ''));
        $unit = SmartPriceCheckService::normalizeUnit((string) ($input['unit'] ?? 'kg'));
        $price = filter_var($input['reference_price'] ?? null, FILTER_VALIDATE_FLOAT);
        $minimum = $this->nullablePrice($input['minimum_price'] ?? null);
        $maximum = $this->nullablePrice($input['maximum_price'] ?? null);
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $priceDate);

        if ($commodity === '') throw new InvalidArgumentException('Commodity is required.');
        if ($category === '') throw new InvalidArgumentException('Select a category.');
        (new CategoryAdminService())->ensureSchema();
        $categoryStmt = Database::connection()->prepare('SELECT name FROM categories WHERE name = ? AND is_active = 1 LIMIT 1');
        $categoryStmt->execute([$category]);
        $category = (string) ($categoryStmt->fetchColumn() ?: '');
        if ($category === '') throw new InvalidArgumentException('Select an available category.');
        if (!in_array($unit, ['kg', 'g', 'piece', 'tray', 'dozen', 'liter', 'bundle', 'crate', 'sack'], true)) throw new InvalidArgumentException('Select a supported unit.');
        if ($price === false || (float) $price <= 0) throw new InvalidArgumentException('Reference price must be greater than zero.');
        if (!$date || $date->format('Y-m-d') !== $priceDate || $date > new DateTimeImmutable('today')) throw new InvalidArgumentException('The reference date is invalid.');
        if ($minimum !== null && $maximum !== null && $minimum > $maximum) throw new InvalidArgumentException('Minimum price cannot exceed maximum price.');

        return [
            'commodity' => $commodity,
            'normalized_commodity' => SmartPriceCheckService::normalizeCommodityName($commodity),
            'category' => $category,
            'unit' => $unit,
            'municipality' => 'Saint Bernard',
            'reference_price' => (float) $price,
            'minimum_price' => $minimum,
            'maximum_price' => $maximum,
            'price_date' => $date->format('Y-m-d'),
        ];
    }

    private function referenceKey(array $row): array
    {
        return [
            'normalized_commodity = ? AND unit = ? AND category <=> ? AND municipality = ?',
            [
                $row['normalized_commodity'], $row['unit'], $row['category'] ?: null,
                $row['municipality'],
            ],
        ];
    }
}
