-- Add verification_badge column to seller_profiles
ALTER TABLE seller_profiles
    ADD COLUMN verification_badge ENUM('new', 'verified', 'trusted', 'premium') DEFAULT 'new' AFTER trust_score;

-- Add index for faster queries
ALTER TABLE seller_profiles
    ADD INDEX idx_verification_badge (verification_badge);
