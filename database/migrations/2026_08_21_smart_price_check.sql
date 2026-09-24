-- Smart Price Check: official reference prices, product approval, and ML-ready history.
-- Reference prices are market references only. They are not legal maximums.

SET @db = DATABASE();

-- Existing marketplace listings remain visible after approval is introduced.
SELECT IF(COUNT(*) = 0,
    'ALTER TABLE products ADD COLUMN approval_status VARCHAR(40) NOT NULL DEFAULT ''approved'' AFTER is_active',
    'SELECT 1'
) INTO @sql
FROM information_schema.columns
WHERE table_schema = @db AND table_name = 'products' AND column_name = 'approval_status';
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

ALTER TABLE products
    MODIFY approval_status VARCHAR(40) NOT NULL DEFAULT 'pending';

SELECT IF(COUNT(*) = 0,
    'ALTER TABLE products ADD COLUMN price_warning_shown TINYINT(1) NOT NULL DEFAULT 0 AFTER approval_status',
    'SELECT 1'
) INTO @sql
FROM information_schema.columns
WHERE table_schema = @db AND table_name = 'products' AND column_name = 'price_warning_shown';
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT IF(COUNT(*) = 0,
    'ALTER TABLE products ADD COLUMN price_warning_acknowledged TINYINT(1) NOT NULL DEFAULT 0 AFTER price_warning_shown',
    'SELECT 1'
) INTO @sql
FROM information_schema.columns
WHERE table_schema = @db AND table_name = 'products' AND column_name = 'price_warning_acknowledged';
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT IF(COUNT(*) = 0,
    'ALTER TABLE products ADD COLUMN price_warning_acknowledged_at DATETIME NULL AFTER price_warning_acknowledged',
    'SELECT 1'
) INTO @sql
FROM information_schema.columns
WHERE table_schema = @db AND table_name = 'products' AND column_name = 'price_warning_acknowledged_at';
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT IF(COUNT(*) = 0,
    'ALTER TABLE products ADD COLUMN last_price_assessment VARCHAR(40) NULL AFTER price_warning_acknowledged_at',
    'SELECT 1'
) INTO @sql
FROM information_schema.columns
WHERE table_schema = @db AND table_name = 'products' AND column_name = 'last_price_assessment';
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT IF(COUNT(*) = 0,
    'ALTER TABLE products ADD COLUMN last_reference_price DECIMAL(10,2) NULL AFTER last_price_assessment',
    'SELECT 1'
) INTO @sql
FROM information_schema.columns
WHERE table_schema = @db AND table_name = 'products' AND column_name = 'last_reference_price';
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT IF(COUNT(*) = 0,
    'ALTER TABLE products ADD COLUMN last_price_deviation DECIMAL(10,2) NULL AFTER last_reference_price',
    'SELECT 1'
) INTO @sql
FROM information_schema.columns
WHERE table_schema = @db AND table_name = 'products' AND column_name = 'last_price_deviation';
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT IF(COUNT(*) = 0,
    'ALTER TABLE products ADD COLUMN last_price_check_id INT UNSIGNED NULL AFTER last_price_deviation',
    'SELECT 1'
) INTO @sql
FROM information_schema.columns
WHERE table_schema = @db AND table_name = 'products' AND column_name = 'last_price_check_id';
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT IF(COUNT(*) = 0,
    'ALTER TABLE products ADD COLUMN admin_note TEXT NULL AFTER last_price_check_id',
    'SELECT 1'
) INTO @sql
FROM information_schema.columns
WHERE table_schema = @db AND table_name = 'products' AND column_name = 'admin_note';
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT IF(COUNT(*) = 0,
    'ALTER TABLE products ADD COLUMN correction_reason TEXT NULL AFTER admin_note',
    'SELECT 1'
) INTO @sql
FROM information_schema.columns
WHERE table_schema = @db AND table_name = 'products' AND column_name = 'correction_reason';
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

CREATE TABLE IF NOT EXISTS price_references (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    commodity VARCHAR(180) NOT NULL,
    category VARCHAR(120) NULL,
    region VARCHAR(180) NULL,
    municipality VARCHAR(180) NULL,
    market VARCHAR(180) NULL,
    unit VARCHAR(40) NOT NULL DEFAULT 'kg',
    reference_price DECIMAL(10,2) NOT NULL,
    price_date DATE NOT NULL,
    source VARCHAR(60) NOT NULL DEFAULT 'ADMIN_IMPORTED',
    source_url VARCHAR(500) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_price_ref_commodity (commodity),
    INDEX idx_price_ref_location (region, municipality),
    INDEX idx_price_ref_unit_date (unit, price_date),
    INDEX idx_price_ref_lookup (commodity, municipality, region, unit, price_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS price_check_history (
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
    INDEX idx_price_check_product (product_id),
    INDEX idx_price_check_commodity_date (commodity, recorded_on),
    INDEX idx_price_check_assessment (assessment)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS product_approval_logs (
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
    INDEX idx_product_approval_product (product_id),
    INDEX idx_product_approval_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
