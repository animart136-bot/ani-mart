-- Complete the variant-aware purchase path on installations that predate it.
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
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_product_variants_product_id (product_id),
    CONSTRAINT fk_product_variants_product_id
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE cart
    ADD COLUMN IF NOT EXISTS product_variant_id INT NULL AFTER product_id,
    ADD COLUMN IF NOT EXISTS variant_label VARCHAR(255) NULL AFTER product_variant_id,
    ADD COLUMN IF NOT EXISTS variant_weight VARCHAR(80) NULL AFTER variant_label,
    ADD COLUMN IF NOT EXISTS variant_unit VARCHAR(60) NULL AFTER variant_weight,
    ADD COLUMN IF NOT EXISTS price_type VARCHAR(20) NULL AFTER variant_unit,
    ADD COLUMN IF NOT EXISTS unit_price DECIMAL(10,2) NULL AFTER price_type;

ALTER TABLE order_items
    ADD COLUMN IF NOT EXISTS product_variant_id INT NULL AFTER product_id,
    ADD COLUMN IF NOT EXISTS variant_label VARCHAR(255) NULL AFTER product_variant_id,
    ADD COLUMN IF NOT EXISTS variant_weight VARCHAR(80) NULL AFTER variant_label,
    ADD COLUMN IF NOT EXISTS variant_unit VARCHAR(60) NULL AFTER variant_weight,
    ADD COLUMN IF NOT EXISTS price_type VARCHAR(20) NULL AFTER variant_unit,
    ADD COLUMN IF NOT EXISTS unit_price DECIMAL(10,2) NULL AFTER price_type;

ALTER TABLE products
    ADD COLUMN IF NOT EXISTS price_type VARCHAR(20) NOT NULL DEFAULT 'retail' AFTER description;

ALTER TABLE orders
    ADD COLUMN IF NOT EXISTS requested_delivery_date DATE NULL AFTER delivery_fee,
    ADD COLUMN IF NOT EXISTS delivery_slot VARCHAR(30) NULL AFTER requested_delivery_date,
    ADD COLUMN IF NOT EXISTS substitution_preference VARCHAR(30) NOT NULL DEFAULT 'contact_me' AFTER delivery_slot,
    ADD COLUMN IF NOT EXISTS customer_note VARCHAR(500) NULL AFTER substitution_preference;
