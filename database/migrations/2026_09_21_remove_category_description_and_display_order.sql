-- Product categories are now identified and sorted by name only.
DROP INDEX IF EXISTS idx_categories_active_order ON categories;
ALTER TABLE categories
    DROP COLUMN IF EXISTS description,
    DROP COLUMN IF EXISTS display_order;

CREATE INDEX IF NOT EXISTS idx_categories_active_name
    ON categories (is_active, name);
