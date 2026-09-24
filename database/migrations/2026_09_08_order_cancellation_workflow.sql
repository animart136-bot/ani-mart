CREATE TABLE IF NOT EXISTS order_cancellation_requests (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id INT NOT NULL,
    customer_id INT NOT NULL,
    seller_user_id INT NOT NULL,
    request_reason VARCHAR(500) NOT NULL,
    status ENUM('pending', 'accepted', 'declined') NOT NULL DEFAULT 'pending',
    response_reason VARCHAR(500) NULL,
    requested_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    responded_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_order_cancellation_request (order_id),
    KEY idx_cancellation_seller_status (seller_user_id, status),
    KEY idx_cancellation_customer (customer_id),
    CONSTRAINT fk_cancellation_order FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE,
    CONSTRAINT fk_cancellation_customer FOREIGN KEY (customer_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_cancellation_seller FOREIGN KEY (seller_user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
