-- Let farmer accounts choose whether a community comment represents themselves or their active shop.

ALTER TABLE community_comments
    ADD COLUMN IF NOT EXISTS author_type VARCHAR(20) NOT NULL DEFAULT 'customer' AFTER user_id,
    ADD COLUMN IF NOT EXISTS seller_profile_id INT NULL AFTER author_type,
    ADD INDEX IF NOT EXISTS idx_community_comments_seller_profile (seller_profile_id);
