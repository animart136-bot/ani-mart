-- Restore order item rows used by marketplace rankings, orders, analytics, and reviews.

CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    seller_profile_id INT UNSIGNED NULL,
    quantity INT NOT NULL DEFAULT 1,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    product_variant_id INT NULL,
    variant_label VARCHAR(255) NULL,
    variant_weight VARCHAR(80) NULL,
    variant_unit VARCHAR(60) NULL,
    price_type VARCHAR(20) NULL,
    unit_price DECIMAL(10,2) NULL,
    review_status VARCHAR(30) NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_order_items_order_id (order_id),
    INDEX idx_order_items_product_id (product_id),
    INDEX idx_order_items_seller_profile_id (seller_profile_id),
    INDEX idx_order_items_review_status (review_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
