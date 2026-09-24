<?php

namespace App\Services;

use App\Core\Database;
use DateTimeImmutable;
use PDO;
use RuntimeException;

class PriceReferenceImportService
{
    public const HEADERS = ['commodity', 'category', 'unit', 'reference_price', 'minimum_price', 'maximum_price', 'price_date'];
    private const UNITS = ['kg', 'g', 'piece', 'tray', 'dozen', 'liter', 'bundle', 'crate', 'sack'];

    public function validate(string $path, ?PDO $db = null): array
    {
        if (!is_readable($path)) throw new RuntimeException('CSV file is not readable.');
        $handle = fopen($path, 'rb');
        $headers = $handle ? fgetcsv($handle, 0, ',', '"', '') : false;
        if (!$handle || $headers === false || array_map('trim', $headers) !== self::HEADERS) {
            if (is_resource($handle)) fclose($handle);
            throw new RuntimeException('CSV headers do not match the required price-reference schema.');
        }
        $result = ['total_rows' => 0, 'valid_rows' => 0, 'invalid_rows' => 0, 'duplicate_rows' => 0, 'inserted_rows' => 0, 'updated_rows' => 0, 'skipped_rows' => 0, 'errors' => [], '_rows' => []];
        $seen = [];
        while (($row = fgetcsv($handle, 0, ',', '"', '')) !== false) {
            $result['total_rows']++;
            $line = $result['total_rows'] + 1;
            if (count($row) !== count(self::HEADERS)) { $this->error($result, $line, 'Wrong column count.'); continue; }
            $record = array_combine(self::HEADERS, array_map('trim', $row));
            $record['normalized_commodity'] = SmartPriceCheckService::normalizeCommodityName($record['commodity']);
            $record['unit'] = SmartPriceCheckService::normalizeUnit($record['unit']);
            $errors = [];
            if ($record['normalized_commodity'] === '' || $record['category'] === '') $errors[] = 'Commodity and category are required.';
            if (!in_array($record['unit'], self::UNITS, true)) $errors[] = 'Unsupported unit.';
            foreach (['reference_price', 'minimum_price', 'maximum_price'] as $price) if ($record[$price] !== '' && (!is_numeric($record[$price]) || (float) $record[$price] < 0)) $errors[] = "Invalid {$price}.";
            if ($record['reference_price'] === '' || (float) $record['reference_price'] <= 0) $errors[] = 'reference_price must be greater than zero.';
            if ($record['minimum_price'] !== '' && $record['maximum_price'] !== '' && (float) $record['minimum_price'] > (float) $record['maximum_price']) $errors[] = 'minimum_price cannot exceed maximum_price.';
            $date = DateTimeImmutable::createFromFormat('!Y-m-d', $record['price_date']);
            if (!$date || $date->format('Y-m-d') !== $record['price_date']) $errors[] = 'Invalid price_date.';
            $key = implode('|', [$record['normalized_commodity'], strtolower($record['category']), $record['unit'], $record['price_date']]);
            if (isset($seen[$key])) { $result['duplicate_rows']++; $errors[] = 'Duplicate reference row.'; }
            $seen[$key] = true;
            if ($errors) { $result['invalid_rows']++; $result['errors'][] = ['line' => $line, 'messages' => $errors]; continue; }
            $result['valid_rows']++;
            $result['_rows'][] = $record;
        }
        fclose($handle);
        if ($db !== null) $this->classifyRows($db, $result);
        return $result;
    }

    public function import(string $path): array
    {
        (new SmartPriceCheckService())->ensureSchema();
        $db = Database::connection();
        $result = $this->validate($path, $db);
        if ($result['invalid_rows'] || !$result['valid_rows']) return $result;
        $db->beginTransaction();
        try {
            $referenceService = new PriceReferenceService();
            usort($result['_rows'], static fn(array $a, array $b): int => strcmp($a['price_date'], $b['price_date']));
            $result['inserted_rows'] = 0;
            $result['updated_rows'] = 0;
            foreach ($result['_rows'] as $row) {
                $normalizedRow = [
                    'commodity' => $row['commodity'],
                    'normalized_commodity' => $row['normalized_commodity'],
                    'category' => $row['category'],
                    'unit' => $row['unit'],
                    'municipality' => 'Saint Bernard',
                    'reference_price' => (float) $row['reference_price'],
                    'minimum_price' => $row['minimum_price'] !== '' ? (float) $row['minimum_price'] : null,
                    'maximum_price' => $row['maximum_price'] !== '' ? (float) $row['maximum_price'] : null,
                    'price_date' => $row['price_date'],
                ];
                $saved = $referenceService->upsertReference($db, $normalizedRow);
                $result[$saved['action'] . '_rows']++;
            }
            $db->commit();
        } catch (\Throwable $e) { if ($db->inTransaction()) $db->rollBack(); throw $e; }
        unset($result['_rows']);
        return $result;
    }

    private function error(array &$result, int $line, string $message): void
    {
        $result['invalid_rows']++;
        $result['errors'][] = ['line' => $line, 'messages' => [$message]];
    }

    private function classifyRows(PDO $db, array &$result): void
    {
        $sql = 'SELECT id FROM price_references WHERE normalized_commodity = ? AND unit = ? AND category <=> ? AND municipality = ? AND price_date = ? LIMIT 1';
        $stmt = $db->prepare($sql);
        foreach ($result['_rows'] as $row) {
            $stmt->execute([
                $row['normalized_commodity'], $row['unit'], $row['category'] ?: null,
                'Saint Bernard', $row['price_date'],
            ]);
            $result[$stmt->fetchColumn() ? 'updated_rows' : 'inserted_rows']++;
        }
    }
}
