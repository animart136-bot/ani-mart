-- Track once-only seller trust deductions caused by validated reports.
CREATE TABLE IF NOT EXISTS seller_trust_adjustments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    seller_profile_id INT NOT NULL,
    entity_type VARCHAR(40) NOT NULL,
    entity_id INT NOT NULL,
    adjustment_type VARCHAR(60) NOT NULL,
    points TINYINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE INDEX idx_seller_trust_adjustment_unique (seller_profile_id, entity_type, entity_id, adjustment_type),
    INDEX idx_seller_trust_adjustments_seller (seller_profile_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Apply the new 1–3-star review penalty to already completed orders once.
INSERT IGNORE INTO seller_trust_rewards (order_id, seller_profile_id, reward_type, points, created_at)
SELECT DISTINCT oi.order_id, oi.seller_profile_id, 'negative_review', 5, NOW()
FROM reviews r
INNER JOIN order_items oi ON oi.id = r.order_item_id
INNER JOIN order_seller_statuses oss
    ON oss.order_id = oi.order_id
    AND oss.seller_profile_id = oi.seller_profile_id
WHERE oss.status = 'completed'
  AND r.rating BETWEEN 1 AND 3;

-- Apply one five-point penalty for each completed shop order with a 1–3-star review.
UPDATE seller_profiles sp
INNER JOIN (
    SELECT seller_profile_id, COUNT(*) AS penalty_count
    FROM seller_trust_rewards
    WHERE reward_type = 'negative_review'
    GROUP BY seller_profile_id
) penalties ON penalties.seller_profile_id = sp.id
SET sp.trust_score = GREATEST(0, sp.trust_score - (penalties.penalty_count * 5));
