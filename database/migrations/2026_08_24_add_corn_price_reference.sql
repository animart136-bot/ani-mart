-- Add a Saint Bernard local-market benchmark so Corn price checks have a reference.
INSERT INTO price_references (
    commodity,
    normalized_commodity,
    category,
    variety,
    province,
    region,
    municipality,
    market,
    unit,
    reference_price,
    minimum_price,
    maximum_price,
    price_date,
    source,
    is_active,
    created_at,
    updated_at
)
SELECT
    'Corn',
    'corn',
    'Grains',
    NULL,
    'Southern Leyte',
    'Eastern Visayas',
    'Saint Bernard',
    'Saint Bernard Public Market',
    'kg',
    80.00,
    60.00,
    100.00,
    CURDATE(),
    'Saint Bernard Local Market Reference',
    1,
    NOW(),
    NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM price_references
    WHERE normalized_commodity = 'corn'
      AND municipality = 'Saint Bernard'
      AND is_active = 1
);
