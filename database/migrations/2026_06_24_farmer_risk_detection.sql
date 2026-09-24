ALTER TABLE seller_profiles
    ADD COLUMN risk_score INT DEFAULT 0 AFTER trust_score,
    ADD COLUMN risk_level ENUM('low','medium','high') DEFAULT 'low' AFTER risk_score;

CREATE TABLE IF NOT EXISTS customer_complaints (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    seller_profile_id INT NOT NULL,
    order_id INT NULL,
    reason VARCHAR(255) NULL,
    details TEXT NULL,
    status ENUM('open','reviewed','resolved') DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_customer_complaints_seller_profile_id (seller_profile_id),
    INDEX idx_customer_complaints_created_at (created_at)
);
