<?php

namespace App\Services;

use App\Core\Database;
use App\Services\PriceAnalysis\PriceAnalyzerInterface;
use App\Services\PriceAnalysis\RuleBasedPriceAnalyzer;
use PDO;

class SmartPriceCheckService
{
    private PriceAnalyzerInterface $analyzer;
    private array $config;

    public function __construct(?PriceAnalyzerInterface $analyzer = null, ?array $config = null)
    {
        $this->config = $config ?? $this->loadConfig();
        $analyzerClass = $this->config['analyzer'] ?? RuleBasedPriceAnalyzer::class;
        $this->analyzer = $analyzer ?? new $analyzerClass();
    }

    public function check(array $input): array
    {
        $this->ensureSchema();

        unset(
            $input['reference_price'],
            $input['assessment'],
            $input['price_deviation_percentage'],
            $input['price_difference'],
            $input['approval_status']
        );

        $commodity = trim((string) ($input['commodity'] ?? $input['name'] ?? ''));
        $category = trim((string) ($input['category'] ?? ''));
        $sellerPrice = (float) ($input['seller_price'] ?? $input['price'] ?? 0);
        $unit = strtolower(trim((string) ($input['unit'] ?? 'kg')));
        $weight = isset($input['weight']) ? (float) $input['weight'] : null;
        $region = trim((string) ($input['region'] ?? $input['province'] ?? ''));
        $municipality = trim((string) ($input['municipality'] ?? $input['city'] ?? ''));
        $date = $this->resolveDate($input['date'] ?? null);
        $season = $this->seasonForDate($date);
        $stock = isset($input['stock']) ? (int) $input['stock'] : null;

        $reference = $this->findReference($commodity, $category, $region, $municipality, $unit, $date);
        $comparablePrice = $this->comparableSellerPrice($sellerPrice, $unit, $weight, $reference['unit'] ?? $unit);
        $analysis = $this->analyzer->analyze(
            $reference ? $comparablePrice : null,
            $reference ? (float) $reference['reference_price'] : null,
            $this->config
        );

        $result = [
            'commodity' => $commodity,
            'category' => $category,
            'seller_price' => $sellerPrice,
            'comparable_price' => $comparablePrice,
            'unit' => $unit,
            'weight' => $weight,
            'region' => $region,
            'municipality' => $municipality,
            'date' => $date,
            'season' => $season,
            'stock' => $stock,
            'reference_available' => (bool) $reference,
            'reference' => $reference,
            'reference_price' => $reference ? (float) $reference['reference_price'] : null,
            'reference_unit' => $reference['unit'] ?? null,
            'reference_date' => $reference['price_date'] ?? null,
            'reference_source' => $reference['source'] ?? null,
            'reference_source_url' => $reference['source_url'] ?? null,
            'reference_age_days' => $reference['age_days'] ?? null,
            'reference_freshness' => $reference['freshness_status'] ?? null,
            'match_level' => $reference['match_level'] ?? null,
            'assessment' => $analysis['assessment'],
            'price_difference' => $analysis['price_difference'],
            'price_deviation_percentage' => $analysis['price_deviation_percentage'],
            'requires_warning' => (bool) $analysis['requires_warning'],
            'notify_seller' => (bool) $analysis['notify_seller'],
            'confidence' => $analysis['confidence'],
            'analyzer' => $analysis['analyzer'],
            'message' => $this->messageFor($analysis, $reference, $sellerPrice, $unit),
        ];

        $result['history_id'] = $this->recordHistory($result, $input);

        return $result;
    }

    public static function normalizeCommodityName(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/\b(fresh|organic|native|local|premium)\b/', '', $value);
        return trim((string) preg_replace('/[^a-z0-9]+/', ' ', $value));
    }

    public static function normalizeUnit(string $value): string
    {
        return match (strtolower(trim($value))) {
            'kilogram', 'kilograms', 'kilo', 'kilos' => 'kg',
            'gram', 'grams' => 'g',
            'pc', 'pcs', 'piece', 'pieces' => 'piece',
            'dozens' => 'dozen',
            default => strtolower(trim($value)),
        };
    }

    public function attachProduct(int $historyId, int $productId, ?string $sellerAction = null): void
    {
        if ($historyId <= 0 || $productId <= 0) {
            return;
        }

        $db = Database::connection();
        $stmt = $db->prepare(
            'UPDATE price_check_history SET product_id = ?, seller_action = COALESCE(?, seller_action) WHERE id = ?'
        );
        $stmt->execute([$productId, $sellerAction, $historyId]);
        $db->prepare('UPDATE products SET last_price_check_id = ? WHERE id = ?')->execute([$historyId, $productId]);
    }

    public function recordSellerAction(int $historyId, string $action): void
    {
        if ($historyId <= 0) {
            return;
        }

        $db = Database::connection();
        $stmt = $db->prepare('UPDATE price_check_history SET seller_action = ? WHERE id = ?');
        $stmt->execute([$action, $historyId]);
    }

    public function recordAdminDecision(int $productId, string $decision): void
    {
        $db = Database::connection();
        $stmt = $db->prepare(
            'UPDATE price_check_history SET admin_decision = ? WHERE product_id = ? ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute([$decision, $productId]);
    }

    public function resolveApprovalStatus(array $check, bool $warningAcknowledged): string
    {
        $assessment = (string) ($check['assessment'] ?? 'REFERENCE_UNAVAILABLE');

        if (!empty($check['requires_warning']) && $warningAcknowledged) {
            return 'pending_admin_review';
        }

        if (!empty($check['reference_available']) && $assessment === 'NORMAL') {
            return 'approved';
        }

        return 'pending';
    }

    public function requiresAcknowledgement(array $check, bool $warningAcknowledged): bool
    {
        return !empty($check['requires_warning']) && !$warningAcknowledged;
    }

    public function findReference(
        string $commodity,
        string $category,
        string $region,
        string $municipality,
        string $unit,
        string $date
    ): ?array {
        $this->ensureSchema();

        $normalizedCommodity = self::normalizeCommodityName($commodity);
        if ($normalizedCommodity === '') {
            return null;
        }

        $db = Database::connection();
        $maxAgeDays = max(1, (int) ($this->config['reference_max_age_days'] ?? 30));
        $oldestAllowedDate = date('Y-m-d', strtotime($date . ' -' . $maxAgeDays . ' days'));
           $stmt = $db->prepare(
            "SELECT *
             FROM price_references
               WHERE is_active = 1
                AND price_date <= ?
                AND price_date >= ?
               AND reference_price > 0
                AND normalized_commodity LIKE ?
               ORDER BY price_date DESC, id DESC"
        );
           $like = '%' . $normalizedCommodity . '%';
           $stmt->execute([$date, $oldestAllowedDate, $like]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$rows) {
            return null;
        }

        $best = null;
        $bestScore = -1;

        foreach ($rows as $row) {
            $score = 0;
            $matchLevel = 'official_fallback';

            $rowMunicipality = $this->normalizePlace($row['municipality'] ?? '');
            $rowRegion = $this->normalizePlace($row['region'] ?? '');
            $rowUnit = self::normalizeUnit((string) ($row['unit'] ?? ''));
            $inputMunicipality = $this->normalizePlace($municipality);
            $inputRegion = $this->normalizePlace($region);
            $inputUnit = self::normalizeUnit($unit);

            if (!$this->unitsCompatible($inputUnit, $rowUnit)) continue;

            if ($inputMunicipality !== '' && $rowMunicipality !== '' && $rowMunicipality !== $inputMunicipality) {
                if ($inputRegion === '' || $rowRegion !== $inputRegion) continue;
            } elseif ($inputRegion !== '' && $rowRegion !== '' && $rowRegion !== $inputRegion) {
                continue;
            }

            if ($inputMunicipality !== '' && $rowMunicipality !== '' && $rowMunicipality === $inputMunicipality) {
                $score += 40;
                $matchLevel = 'municipality';
            } elseif ($inputRegion !== '' && $rowRegion !== '' && $rowRegion === $inputRegion) {
                $score += 25;
                $matchLevel = 'region';
            } else {
                $score += 5;
            }

            if (self::normalizeCommodityName((string) $row['commodity']) === $normalizedCommodity) {
                $score += 20;
            } else {
                $score += 8;
            }

            if ($rowUnit !== '') {
                $score += 15;
            }

            $daysOld = max(0, (int) ((strtotime($date) - strtotime((string) $row['price_date'])) / 86400));
            $score += max(0, 10 - (int) floor($daysOld / 30));

            if ($score > $bestScore) {
                $bestScore = $score;
                $row['match_level'] = $matchLevel;
                $row['age_days'] = $daysOld;
                $row['freshness_status'] = PriceReferenceService::freshnessStatus($daysOld);
                $best = $row;
            }
        }

        return $best;
    }

    public function ensureSchema(): void
    {
        $db = Database::connection();

        if ($this->ensureColumn($db, 'products', 'approval_status', "VARCHAR(40) NOT NULL DEFAULT 'approved'")) {
            $db->exec("ALTER TABLE products MODIFY approval_status VARCHAR(40) NOT NULL DEFAULT 'pending'");
        }
        $this->ensureColumn($db, 'products', 'price_warning_shown', 'TINYINT(1) NOT NULL DEFAULT 0');
        $this->ensureColumn($db, 'products', 'price_warning_acknowledged', 'TINYINT(1) NOT NULL DEFAULT 0');
        $this->ensureColumn($db, 'products', 'price_warning_acknowledged_at', 'DATETIME NULL');
        $this->ensureColumn($db, 'products', 'last_price_assessment', 'VARCHAR(40) NULL');
        $this->ensureColumn($db, 'products', 'last_reference_price', 'DECIMAL(10,2) NULL');
        $this->ensureColumn($db, 'products', 'last_price_deviation', 'DECIMAL(10,2) NULL');
        $this->ensureColumn($db, 'products', 'last_price_check_id', 'INT UNSIGNED NULL');
        $this->ensureColumn($db, 'products', 'admin_note', 'TEXT NULL');
        $this->ensureColumn($db, 'products', 'correction_reason', 'TEXT NULL');
        $this->ensureColumn($db, 'products', 'price_justification', 'TEXT NULL');
        $this->ensureColumn($db, 'products', 'moderation_reason', 'TEXT NULL');

        $db->exec("CREATE TABLE IF NOT EXISTS price_references (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            commodity VARCHAR(180) NOT NULL,
            normalized_commodity VARCHAR(180) NOT NULL,
            category VARCHAR(120) NULL,
            municipality VARCHAR(180) NOT NULL DEFAULT 'Saint Bernard',
            unit VARCHAR(40) NOT NULL DEFAULT 'kg',
            reference_price DECIMAL(10,2) NOT NULL,
            minimum_price DECIMAL(10,2) NULL,
            maximum_price DECIMAL(10,2) NULL,
            price_date DATE NOT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_price_ref_commodity (commodity),
            INDEX idx_price_ref_location (municipality),
            INDEX idx_price_ref_unit_date (unit, price_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        foreach (['normalized_commodity' => "VARCHAR(180) NOT NULL DEFAULT ''", 'minimum_price' => 'DECIMAL(10,2) NULL', 'maximum_price' => 'DECIMAL(10,2) NULL', 'is_active' => 'TINYINT(1) NOT NULL DEFAULT 1'] as $column => $definition) $this->ensureColumn($db, 'price_references', $column, $definition);
        $db->exec("UPDATE price_references SET normalized_commodity = LOWER(TRIM(commodity)) WHERE normalized_commodity = ''");

        $db->exec("CREATE TABLE IF NOT EXISTS price_check_history (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            product_id INT UNSIGNED NULL,
            seller_profile_id INT UNSIGNED NULL,
            commodity VARCHAR(180) NOT NULL,
            category VARCHAR(120) NULL,
            seller_price DECIMAL(10,2) NOT NULL,
            comparable_price DECIMAL(10,2) NULL,
            reference_price DECIMAL(10,2) NULL,
            reference_id INT UNSIGNED NULL,
            deviation_percentage DECIMAL(10,2) NULL,
            price_difference DECIMAL(10,2) NULL,
            region VARCHAR(180) NULL,
            municipality VARCHAR(180) NULL,
            unit VARCHAR(40) NULL,
            season VARCHAR(40) NULL,
            stock INT NULL,
            assessment VARCHAR(40) NOT NULL,
            analyzer VARCHAR(80) NOT NULL DEFAULT 'rule_based',
            seller_action VARCHAR(40) NULL,
            admin_decision VARCHAR(40) NULL,
            match_level VARCHAR(40) NULL,
            recorded_on DATE NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_price_check_product (product_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $this->ensureColumn($db, 'price_check_history', 'source', 'VARCHAR(255) NULL');

        $db->exec("CREATE TABLE IF NOT EXISTS product_approval_logs (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            product_id INT UNSIGNED NOT NULL,
            admin_id INT UNSIGNED NULL,
            decision VARCHAR(40) NOT NULL,
            note TEXT NULL,
            seller_price DECIMAL(10,2) NULL,
            reference_price DECIMAL(10,2) NULL,
            deviation_percentage DECIMAL(10,2) NULL,
            assessment VARCHAR(40) NULL,
            previous_status VARCHAR(40) NULL,
            new_status VARCHAR(40) NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_product_approval_product (product_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    private function recordHistory(array $result, array $input): ?int
    {
        $db = Database::connection();
        $stmt = $db->prepare(
            'INSERT INTO price_check_history (
                product_id, seller_profile_id, commodity, category, seller_price, comparable_price,
                reference_price, reference_id, deviation_percentage, price_difference, region, municipality,
                unit, season, stock, assessment, analyzer, seller_action, match_level, recorded_on,
                source
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );

        $stmt->execute([
            $input['product_id'] ?? null,
            $input['seller_profile_id'] ?? null,
            $result['commodity'],
            $result['category'] ?: null,
            $result['seller_price'],
            $result['comparable_price'],
            $result['reference_price'],
            $result['reference']['id'] ?? null,
            $result['price_deviation_percentage'],
            $result['price_difference'],
            $result['region'] ?: null,
            $result['municipality'] ?: null,
            $result['unit'] ?: null,
            $result['season'],
            $result['stock'],
            $result['assessment'],
            $result['analyzer'],
            $input['seller_action'] ?? 'checked',
            $result['match_level'],
            $result['date'],
            $result['reference']['source'] ?? null,
        ]);

        return (int) $db->lastInsertId();
    }

    private function comparableSellerPrice(float $sellerPrice, string $sellerUnit, ?float $weight, string $referenceUnit): float
    {
        $sellerPerKg = $this->toPerKilogram($sellerPrice, $sellerUnit, $weight);
        $referenceIsKg = $this->unitsCompatible($referenceUnit, 'kg');

        if ($sellerPerKg !== null && $referenceIsKg) {
            return $sellerPerKg;
        }

        return $sellerPrice;
    }

    private function toPerKilogram(float $price, string $unit, ?float $weight): ?float
    {
        $unit = strtolower(trim($unit));

        if ($unit === 'kg') {
            $kg = $weight && $weight > 0 ? $weight : 1;
            return $kg > 0 ? round($price / $kg, 2) : $price;
        }

        if ($unit === 'g') {
            $grams = $weight && $weight > 0 ? $weight : 1000;
            $kg = $grams / 1000;
            return $kg > 0 ? round($price / $kg, 2) : null;
        }

        return null;
    }

    private function unitsCompatible(string $left, string $right): bool
    {
        $left = strtolower(trim($left));
        $right = strtolower(trim($right));

        if ($left === $right) {
            return true;
        }

        $mass = ['g', 'kg', 'kilo', 'kilos', 'kilogram', 'kilograms', 'gram', 'grams'];

        return false;
    }

    private function normalizePlace(string $value): string
    {
        return strtolower(trim(preg_replace('/[^a-z0-9]+/i', ' ', $value)));
    }

    private function resolveDate($date): string
    {
        if (is_string($date) && trim($date) !== '') {
            $timestamp = strtotime($date);
            if ($timestamp !== false) {
                return date('Y-m-d', $timestamp);
            }
        }

        return date('Y-m-d');
    }

    private function seasonForDate(string $date): string
    {
        $month = (int) date('n', strtotime($date));

        if (in_array($month, [12, 1, 2], true)) {
            return 'dry_cool';
        }

        if (in_array($month, [3, 4, 5], true)) {
            return 'hot_dry';
        }

        if (in_array($month, [6, 7, 8], true)) {
            return 'wet';
        }

        return 'late_wet';
    }

    private function messageFor(array $analysis, ?array $reference, float $sellerPrice, string $unit): array
    {
        if (!$reference) {
            return [
                'title' => 'Reference Unavailable',
                'body' => 'No current reference price was found for this product. You can still submit it, but it will require the normal administrator approval workflow.',
                'tone' => 'neutral',
            ];
        }

        $formattedRef = '₱' . number_format((float) $reference['reference_price'], 2) . '/' . ($reference['unit'] ?? 'kg');
        $formattedSeller = '₱' . number_format($sellerPrice, 2) . '/' . $unit;
        $deviation = $analysis['price_deviation_percentage'];
        $signed = ($deviation > 0 ? '+' : '') . number_format((float) $deviation, 1) . '%';
        $referenceNote = ' Source: ' . ($reference['source'] ?? 'Recorded reference') . ', as of ' . date('F j, Y', strtotime((string) $reference['price_date'])) . '.';
        if (($reference['freshness_status'] ?? '') === 'needs_update') {
            $referenceNote .= ' This reference is more than 7 days old and should be updated when newer verified data is available.';
        }

        if ($analysis['assessment'] === 'NORMAL') {
            return [
                'title' => 'Price Check',
                'body' => 'Your submitted price is within the expected reference range.' . $referenceNote,
                'tone' => 'success',
            ];
        }

        if ($analysis['requires_warning']) {
            return [
                'title' => 'Price Review Recommended',
                'body' => 'Your submitted price of ' . $formattedSeller . ' differs significantly from the current reference market price of ' . $formattedRef . '. Difference: ' . $signed . '. You may keep your price; explain what makes the product worth more and acknowledge the comparison before submitting.' . $referenceNote,
                'tone' => 'warning',
            ];
        }

        return [
            'title' => 'Price Review Recommended',
                'body' => 'Your price differs from the current reference market price of ' . $formattedRef . ' (' . $signed . '). This is guidance only—you remain free to keep your price.' . $referenceNote,
            'tone' => 'info',
        ];
    }

    private function loadConfig(): array
    {
        $path = dirname(__DIR__, 2) . '/config/smart_price_check.php';

        if (is_file($path)) {
            return require $path;
        }

        return [];
    }

    private function ensureColumn($db, string $table, string $column, string $definition): bool
    {
        $stmt = $db->prepare(
            'SELECT 1 FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1'
        );
        $stmt->execute([$table, $column]);

        if ($stmt->fetch()) {
            return false;
        }

        $db->exec("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");

        return true;
    }
}
