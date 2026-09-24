ALTER TABLE users
    ADD COLUMN IF NOT EXISTS phone_encrypted TEXT NULL AFTER phone,
    ADD COLUMN IF NOT EXISTS address_encrypted TEXT NULL AFTER address;

ALTER TABLE orders
    ADD COLUMN IF NOT EXISTS shipping_address_encrypted TEXT NULL AFTER shipping_address,
    ADD COLUMN IF NOT EXISTS delivery_address_encrypted TEXT NULL AFTER delivery_address,
    ADD COLUMN IF NOT EXISTS customer_note_encrypted TEXT NULL AFTER customer_note;

ALTER TABLE farmer_applications
    ADD COLUMN IF NOT EXISTS address_encrypted TEXT NULL AFTER address,
    ADD COLUMN IF NOT EXISTS contact_number_encrypted TEXT NULL AFTER contact_number,
    ADD COLUMN IF NOT EXISTS date_of_birth_encrypted TEXT NULL AFTER date_of_birth,
    ADD COLUMN IF NOT EXISTS rsbsa_number_encrypted TEXT NULL AFTER rsbsa_number,
    ADD COLUMN IF NOT EXISTS extracted_name_encrypted TEXT NULL AFTER extracted_name,
    ADD COLUMN IF NOT EXISTS extracted_rsbsa_number_encrypted TEXT NULL AFTER extracted_rsbsa_number;

ALTER TABLE seller_profiles
    ADD COLUMN IF NOT EXISTS farm_location_encrypted TEXT NULL AFTER farm_location,
    ADD COLUMN IF NOT EXISTS contact_number_encrypted TEXT NULL AFTER contact_number;
