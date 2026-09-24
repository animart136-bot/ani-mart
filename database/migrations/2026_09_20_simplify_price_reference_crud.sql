-- Keep price references aligned with the admin CRUD form.
-- The reference date represents the day the record was added.
UPDATE price_references
SET municipality = 'Saint Bernard',
    price_date = DATE(created_at);

ALTER TABLE price_references
    DROP INDEX IF EXISTS idx_price_reference_location,
    DROP INDEX IF EXISTS idx_price_ref_location,
    DROP INDEX IF EXISTS idx_price_ref_lookup;

ALTER TABLE price_references
    DROP COLUMN IF EXISTS region,
    DROP COLUMN IF EXISTS province,
    DROP COLUMN IF EXISTS source,
    DROP COLUMN IF EXISTS source_url,
    DROP COLUMN IF EXISTS market,
    DROP COLUMN IF EXISTS variety;

ALTER TABLE price_references
    MODIFY municipality VARCHAR(180) NOT NULL DEFAULT 'Saint Bernard';

CREATE INDEX IF NOT EXISTS idx_price_ref_municipality
    ON price_references (municipality);
