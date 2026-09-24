-- Seller trust starts at 0 for regular sellers and 40 for sellers who submit
-- RSBSA information. Positive reviewed orders are rewarded once per shop/order.

ALTER TABLE seller_profiles
    MODIFY COLUMN trust_score INT NOT NULL DEFAULT 0;

CREATE TABLE IF NOT EXISTS seller_trust_rewards (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    seller_profile_id INT NOT NULL,
    reward_type VARCHAR(40) NOT NULL,
    points TINYINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE INDEX idx_seller_trust_reward_unique (order_id, seller_profile_id, reward_type),
    INDEX idx_seller_trust_rewards_seller (seller_profile_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

UPDATE seller_profiles sp
SET sp.trust_score = CASE
    WHEN EXISTS (
        SELECT 1
        FROM farmer_applications fa
        WHERE fa.user_id = sp.user_id
          AND (
              NULLIF(TRIM(COALESCE(fa.rsbsa_number, '')), '') IS NOT NULL
              OR NULLIF(TRIM(COALESCE(fa.rsbsa_card_path, '')), '') IS NOT NULL
              OR fa.rsbsa_verification_status IN ('submitted', 'verified')
          )
    ) THEN 40
    ELSE 0
END
WHERE sp.trust_score IS NULL OR sp.trust_score = 100;

UPDATE seller_profiles sp
SET sp.verification_badge = 'verified'
WHERE sp.verification_badge = 'new'
  AND EXISTS (
      SELECT 1
      FROM farmer_applications fa
      WHERE fa.user_id = sp.user_id
        AND (
            NULLIF(TRIM(COALESCE(fa.rsbsa_number, '')), '') IS NOT NULL
            OR NULLIF(TRIM(COALESCE(fa.rsbsa_card_path, '')), '') IS NOT NULL
            OR fa.rsbsa_verification_status IN ('submitted', 'verified')
        )
  );

-- Remove legacy high-trust badges that came only from the former 100-point
-- default. Preserve the RSBSA badge when matching application data exists.
UPDATE seller_profiles sp
SET sp.verification_badge = CASE
    WHEN EXISTS (
        SELECT 1
        FROM farmer_applications fa
        WHERE fa.user_id = sp.user_id
          AND (
              NULLIF(TRIM(COALESCE(fa.rsbsa_number, '')), '') IS NOT NULL
              OR NULLIF(TRIM(COALESCE(fa.rsbsa_card_path, '')), '') IS NOT NULL
              OR fa.rsbsa_verification_status IN ('submitted', 'verified')
          )
    ) THEN 'verified'
    ELSE 'new'
END
WHERE sp.trust_score <= 80
  AND sp.verification_badge IN ('trusted', 'premium');
