-- Add the review modification timestamp to databases created from an older schema.

ALTER TABLE reviews
    ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NULL DEFAULT NULL AFTER created_at;
