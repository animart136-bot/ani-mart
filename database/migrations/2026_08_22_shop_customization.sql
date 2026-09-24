ALTER TABLE seller_profiles
    ADD COLUMN IF NOT EXISTS shop_theme VARCHAR(40) NOT NULL DEFAULT 'fresh-farm' AFTER description,
    ADD COLUMN IF NOT EXISTS farm_story TEXT NULL AFTER shop_theme,
    ADD COLUMN IF NOT EXISTS operating_hours TEXT NULL AFTER farm_story,
    ADD COLUMN IF NOT EXISTS delivery_information TEXT NULL AFTER operating_hours,
    ADD COLUMN IF NOT EXISTS is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER delivery_information;

CREATE TABLE IF NOT EXISTS seller_featured_products (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    seller_profile_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    display_order TINYINT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY seller_featured_product_unique (seller_profile_id, product_id),
    KEY seller_featured_products_seller_idx (seller_profile_id),
    KEY seller_featured_products_product_idx (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;