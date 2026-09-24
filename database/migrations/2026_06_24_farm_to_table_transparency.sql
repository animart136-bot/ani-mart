ALTER TABLE products
    DROP COLUMN freshness_date,
    ADD COLUMN harvest_date DATE NULL AFTER stock,
    ADD COLUMN harvest_photo VARCHAR(500) NULL AFTER harvest_date;

CREATE TABLE IF NOT EXISTS product_activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    user_id INT NOT NULL,
    action VARCHAR(255),
    old_value TEXT,
    new_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_product_activity_logs_product_id (product_id),
    INDEX idx_product_activity_logs_user_id (user_id),
    INDEX idx_product_activity_logs_created_at (created_at)
);

ALTER TABLE seller_profiles
    ADD COLUMN trust_score INT DEFAULT 100;
