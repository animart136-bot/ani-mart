CREATE TABLE IF NOT EXISTS return_requests (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    customer_id INT NOT NULL,
    seller_profile_id INT NOT NULL,
    seller_user_id INT NOT NULL,
    order_payment_method VARCHAR(50) NOT NULL,
    reason VARCHAR(1200) NOT NULL,
    status ENUM('pending','approved','rejected','resolved') NOT NULL DEFAULT 'pending',
    requested_refund_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    approved_refund_amount DECIMAL(10,2) NULL,
    refunded_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    refund_method ENUM('cash','bank_transfer','e_wallet','other','none') NOT NULL DEFAULT 'none',
    refund_reference VARCHAR(255) NULL,
    decision_reason VARCHAR(1000) NULL,
    resolution_note VARCHAR(1200) NULL,
    decided_at TIMESTAMP NULL,
    resolved_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_return_request_order (order_id),
    KEY idx_return_customer (customer_id),
    KEY idx_return_seller_status (seller_user_id, status),
    CONSTRAINT fk_return_order FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE,
    CONSTRAINT fk_return_customer FOREIGN KEY (customer_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_return_seller_profile FOREIGN KEY (seller_profile_id) REFERENCES seller_profiles (id) ON DELETE CASCADE,
    CONSTRAINT fk_return_seller_user FOREIGN KEY (seller_user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS return_request_items (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    return_request_id BIGINT UNSIGNED NOT NULL,
    order_item_id INT NOT NULL,
    product_id INT NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    variant_label VARCHAR(255) NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    requested_quantity INT NOT NULL,
    approved_quantity INT NOT NULL DEFAULT 0,
    received_quantity INT NOT NULL DEFAULT 0,
    refund_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    UNIQUE KEY uniq_return_order_item (return_request_id, order_item_id),
    KEY idx_return_items_request (return_request_id),
    CONSTRAINT fk_return_item_request FOREIGN KEY (return_request_id) REFERENCES return_requests (id) ON DELETE CASCADE,
    CONSTRAINT fk_return_item_order_item FOREIGN KEY (order_item_id) REFERENCES order_items (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS return_evidence (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    return_request_id BIGINT UNSIGNED NOT NULL,
    user_id INT NOT NULL,
    uploader_role ENUM('customer','farmer','admin') NOT NULL,
    image_url VARCHAR(1024) NOT NULL,
    caption VARCHAR(500) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_return_evidence_request (return_request_id),
    CONSTRAINT fk_return_evidence_request FOREIGN KEY (return_request_id) REFERENCES return_requests (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS return_resolution_history (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    return_request_id BIGINT UNSIGNED NOT NULL,
    actor_user_id INT NOT NULL,
    actor_role ENUM('customer','farmer','admin','system') NOT NULL,
    event_type VARCHAR(60) NOT NULL,
    from_status VARCHAR(30) NULL,
    to_status VARCHAR(30) NOT NULL,
    note VARCHAR(1200) NOT NULL,
    amount DECIMAL(10,2) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_return_history_request (return_request_id, created_at),
    CONSTRAINT fk_return_history_request FOREIGN KEY (return_request_id) REFERENCES return_requests (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
