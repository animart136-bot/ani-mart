CREATE TABLE IF NOT EXISTS inventory_stock_alerts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    seller_user_id INT NOT NULL,
    product_id INT NOT NULL,
    entity_type ENUM('product','variant') NOT NULL,
    entity_id BIGINT UNSIGNED NOT NULL,
    stock_level ENUM('healthy','low','out') NOT NULL,
    last_stock INT NOT NULL,
    last_notified_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_inventory_stock_entity (entity_type, entity_id),
    KEY idx_inventory_alert_seller_level (seller_user_id, stock_level),
    KEY idx_inventory_alert_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
