ALTER TABLE products
    ADD COLUMN price_type VARCHAR(20) NOT NULL DEFAULT 'retail' AFTER description;

CREATE TABLE IF NOT EXISTS product_variants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    variant_name VARCHAR(255) NOT NULL,
    weight VARCHAR(80) DEFAULT NULL,
    unit VARCHAR(60) DEFAULT NULL,
    retail_price DECIMAL(10,2) DEFAULT NULL,
    wholesale_price DECIMAL(10,2) DEFAULT NULL,
    minimum_wholesale_quantity INT DEFAULT NULL,
    stock INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_product_variants_product_id (product_id),
    CONSTRAINT fk_product_variants_product_id FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS product_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    image_url VARCHAR(500) NOT NULL,
    image_public_id VARCHAR(255) DEFAULT NULL,
    display_order INT NOT NULL DEFAULT 0,
    is_cover TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_product_images_product_id (product_id),
    CONSTRAINT fk_product_images_product_id FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE cart
    ADD COLUMN product_variant_id INT NULL AFTER product_id,
    ADD COLUMN variant_label VARCHAR(255) NULL AFTER product_variant_id,
    ADD COLUMN variant_weight VARCHAR(80) NULL AFTER variant_label,
    ADD COLUMN variant_unit VARCHAR(60) NULL AFTER variant_weight,
    ADD COLUMN price_type VARCHAR(20) NULL AFTER variant_unit,
    ADD COLUMN unit_price DECIMAL(10,2) NULL AFTER price_type;

ALTER TABLE order_items
    ADD COLUMN product_variant_id INT NULL AFTER product_id,
    ADD COLUMN variant_label VARCHAR(255) NULL AFTER product_variant_id,
    ADD COLUMN variant_weight VARCHAR(80) NULL AFTER variant_label,
    ADD COLUMN variant_unit VARCHAR(60) NULL AFTER variant_weight,
    ADD COLUMN price_type VARCHAR(20) NULL AFTER variant_unit,
    ADD COLUMN unit_price DECIMAL(10,2) NULL AFTER price_type;
