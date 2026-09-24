-- Repair seller profiles created before submitted RSBSA information was
-- consistently awarded its 40-point starting trust score.
UPDATE seller_profiles sp
SET sp.trust_score = LEAST(100, COALESCE(sp.trust_score, 0) + 40)
WHERE COALESCE(sp.trust_score, 0) < 40
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
