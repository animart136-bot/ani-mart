-- Professional Farm Profile System
-- Run after the base Ani-Mart schema and verification badge migration.

ALTER TABLE seller_profiles
    ADD COLUMN slug VARCHAR(160) NULL AFTER shop_name,
    ADD COLUMN farm_banner VARCHAR(255) NULL AFTER description,
    ADD COLUMN farm_logo VARCHAR(255) NULL AFTER farm_banner,
    ADD COLUMN farm_location VARCHAR(255) NULL AFTER farm_logo,
    ADD COLUMN farming_method VARCHAR(120) NULL AFTER farm_location,
    ADD COLUMN years_experience INT DEFAULT 0 AFTER farming_method,
    ADD COLUMN response_rate TINYINT UNSIGNED DEFAULT 95 AFTER years_experience,
    ADD COLUMN average_delivery_time VARCHAR(80) NULL AFTER response_rate,
    ADD COLUMN updated_at TIMESTAMP NULL AFTER created_at;

UPDATE seller_profiles
SET slug = CONCAT(
    LOWER(TRIM(BOTH '-' FROM REGEXP_REPLACE(shop_name, '[^a-zA-Z0-9]+', '-'))),
    '-',
    id
)
WHERE slug IS NULL
    OR slug = '';

UPDATE seller_profiles
SET slug = CONCAT('farm-', id)
WHERE slug IS NULL
    OR slug = '';

ALTER TABLE seller_profiles
    MODIFY slug VARCHAR(160) NOT NULL,
    ADD UNIQUE INDEX idx_seller_profiles_slug (slug);

CREATE TABLE IF NOT EXISTS farm_profile_images (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    seller_profile_id INT NOT NULL,
    image_type ENUM('farm', 'harvest') NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    caption VARCHAR(160) NULL,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP NULL,
    INDEX idx_farm_profile_images_seller_type (seller_profile_id, image_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
