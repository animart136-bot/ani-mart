ALTER TABLE price_references
    DROP INDEX IF EXISTS idx_price_reference_version,
    DROP COLUMN IF EXISTS dataset_version;

ALTER TABLE price_check_history
    DROP COLUMN IF EXISTS dataset_version;

UPDATE price_references
SET source = 'Saint Bernard Local Market Reference',
    updated_at = NOW()
WHERE source IN ('Prototype Reference Dataset', 'Ani-Mart Local Prototype Reference');
