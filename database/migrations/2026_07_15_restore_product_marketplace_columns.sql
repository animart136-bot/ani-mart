-- Bring older products tables up to the current marketplace/product schema.

SET @db = DATABASE();

SELECT IF(COUNT(*) = 0,
    'ALTER TABLE products ADD COLUMN seller_profile_id INT UNSIGNED NULL AFTER seller_id',
    'SELECT 1'
) INTO @sql
FROM information_schema.columns
WHERE table_schema = @db AND table_name = 'products' AND column_name = 'seller_profile_id';
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT IF(COUNT(*) = 0,
    'ALTER TABLE products ADD COLUMN category_id INT UNSIGNED NULL AFTER seller_profile_id',
    'SELECT 1'
) INTO @sql
FROM information_schema.columns
WHERE table_schema = @db AND table_name = 'products' AND column_name = 'category_id';
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT IF(COUNT(*) = 0,
    'ALTER TABLE products ADD COLUMN slug VARCHAR(180) NULL AFTER name',
    'SELECT 1'
) INTO @sql
FROM information_schema.columns
WHERE table_schema = @db AND table_name = 'products' AND column_name = 'slug';
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT IF(COUNT(*) = 0,
    'ALTER TABLE products ADD COLUMN image VARCHAR(500) NULL AFTER description',
    'SELECT 1'
) INTO @sql
FROM information_schema.columns
WHERE table_schema = @db AND table_name = 'products' AND column_name = 'image';
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT IF(COUNT(*) = 0,
    'ALTER TABLE products ADD COLUMN image_public_id VARCHAR(255) NULL AFTER image',
    'SELECT 1'
) INTO @sql
FROM information_schema.columns
WHERE table_schema = @db AND table_name = 'products' AND column_name = 'image_public_id';
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT IF(COUNT(*) = 0,
    'ALTER TABLE products ADD COLUMN price_type VARCHAR(20) NOT NULL DEFAULT ''retail'' AFTER price',
    'SELECT 1'
) INTO @sql
FROM information_schema.columns
WHERE table_schema = @db AND table_name = 'products' AND column_name = 'price_type';
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT IF(COUNT(*) = 0,
    'ALTER TABLE products ADD COLUMN harvest_date DATE NULL AFTER price_type',
    'SELECT 1'
) INTO @sql
FROM information_schema.columns
WHERE table_schema = @db AND table_name = 'products' AND column_name = 'harvest_date';
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT IF(COUNT(*) = 0,
    'ALTER TABLE products ADD COLUMN harvest_photo VARCHAR(500) NULL AFTER harvest_date',
    'SELECT 1'
) INTO @sql
FROM information_schema.columns
WHERE table_schema = @db AND table_name = 'products' AND column_name = 'harvest_photo';
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT IF(COUNT(*) = 0,
    'ALTER TABLE products ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER harvest_photo',
    'SELECT 1'
) INTO @sql
FROM information_schema.columns
WHERE table_schema = @db AND table_name = 'products' AND column_name = 'is_active';
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT IF(COUNT(*) = 0,
    'ALTER TABLE products ADD COLUMN updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at',
    'SELECT 1'
) INTO @sql
FROM information_schema.columns
WHERE table_schema = @db AND table_name = 'products' AND column_name = 'updated_at';
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE products
SET slug = LOWER(REPLACE(TRIM(name), ' ', '-'))
WHERE (slug IS NULL OR slug = '')
    AND name IS NOT NULL
    AND name <> '';

UPDATE products
INNER JOIN seller_profiles
    ON seller_profiles.user_id = products.seller_id
SET products.seller_profile_id = seller_profiles.id
WHERE products.seller_profile_id IS NULL;

UPDATE products
INNER JOIN seller_profiles
    ON seller_profiles.id = products.seller_id
SET products.seller_profile_id = seller_profiles.id
WHERE products.seller_profile_id IS NULL;

UPDATE products
SET category_id = (
        SELECT id
        FROM categories
        ORDER BY id ASC
        LIMIT 1
    )
WHERE category_id IS NULL;

CREATE TABLE IF NOT EXISTS reviews (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    order_item_id INT NULL,
    user_id INT NOT NULL,
    rating TINYINT UNSIGNED NOT NULL,
    freshness_rating TINYINT UNSIGNED NULL,
    seller_responsiveness_rating TINYINT UNSIGNED NULL,
    product_condition_rating TINYINT UNSIGNED NULL,
    driver_friendliness_rating TINYINT UNSIGNED NULL,
    delivery_speed_rating TINYINT UNSIGNED NULL,
    comment TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    INDEX idx_reviews_product_id (product_id),
    INDEX idx_reviews_order_item_id (order_item_id),
    INDEX idx_reviews_user_id (user_id),
    UNIQUE INDEX idx_reviews_order_item_unique (order_item_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
