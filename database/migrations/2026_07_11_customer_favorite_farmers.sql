-- Customer Favorite Farmers follow system

CREATE TABLE IF NOT EXISTS seller_followers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    seller_profile_id INT NOT NULL,
    created_at TIMESTAMP NULL,
    UNIQUE INDEX idx_seller_followers_customer_seller (customer_id, seller_profile_id),
    INDEX idx_seller_followers_seller (seller_profile_id),
    INDEX idx_seller_followers_customer (customer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
