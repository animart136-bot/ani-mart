-- Customer product reviews after confirmed delivery.

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
