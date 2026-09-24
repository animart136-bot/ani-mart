ALTER TABLE users
    ADD COLUMN IF NOT EXISTS first_name_encrypted TEXT NULL AFTER first_name,
    ADD COLUMN IF NOT EXISTS last_name_encrypted TEXT NULL AFTER last_name,
    ADD COLUMN IF NOT EXISTS email_encrypted TEXT NULL AFTER email,
    ADD COLUMN IF NOT EXISTS latitude_encrypted TEXT NULL AFTER latitude,
    ADD COLUMN IF NOT EXISTS longitude_encrypted TEXT NULL AFTER longitude,
    ADD COLUMN IF NOT EXISTS barangay_encrypted TEXT NULL AFTER barangay,
    ADD COLUMN IF NOT EXISTS municipality_encrypted TEXT NULL AFTER municipality,
    ADD COLUMN IF NOT EXISTS province_encrypted TEXT NULL AFTER province,
    ADD COLUMN IF NOT EXISTS suspension_reason_encrypted TEXT NULL AFTER suspension_reason;

ALTER TABLE farmer_applications
    ADD COLUMN IF NOT EXISTS first_name_encrypted TEXT NULL AFTER first_name,
    ADD COLUMN IF NOT EXISTS middle_name_encrypted TEXT NULL AFTER middle_name,
    ADD COLUMN IF NOT EXISTS last_name_encrypted TEXT NULL AFTER last_name,
    ADD COLUMN IF NOT EXISTS name_extension_encrypted TEXT NULL AFTER name_extension,
    ADD COLUMN IF NOT EXISTS sex_encrypted TEXT NULL AFTER sex,
    ADD COLUMN IF NOT EXISTS rsbsa_card_path_encrypted TEXT NULL AFTER rsbsa_card_path;

ALTER TABLE seller_profiles
    ADD COLUMN IF NOT EXISTS latitude_encrypted TEXT NULL AFTER latitude,
    ADD COLUMN IF NOT EXISTS longitude_encrypted TEXT NULL AFTER longitude,
    ADD COLUMN IF NOT EXISTS barangay_encrypted TEXT NULL AFTER barangay,
    ADD COLUMN IF NOT EXISTS city_encrypted TEXT NULL AFTER city,
    ADD COLUMN IF NOT EXISTS government_id_encrypted TEXT NULL AFTER government_id;

ALTER TABLE conversations
    ADD COLUMN IF NOT EXISTS last_message_encrypted TEXT NULL AFTER last_message;

ALTER TABLE messages
    ADD COLUMN IF NOT EXISTS message_encrypted LONGTEXT NULL AFTER message,
    ADD COLUMN IF NOT EXISTS image_encrypted TEXT NULL AFTER image;

ALTER TABLE message_requests
    ADD COLUMN IF NOT EXISTS message_encrypted LONGTEXT NULL AFTER message,
    ADD COLUMN IF NOT EXISTS image_encrypted TEXT NULL AFTER image;

ALTER TABLE complaints
    ADD COLUMN IF NOT EXISTS description_encrypted LONGTEXT NULL AFTER description,
    ADD COLUMN IF NOT EXISTS admin_notes_encrypted LONGTEXT NULL AFTER admin_notes;

ALTER TABLE complaint_actions
    ADD COLUMN IF NOT EXISTS notes_encrypted LONGTEXT NULL AFTER notes;

ALTER TABLE complaint_replies
    ADD COLUMN IF NOT EXISTS message_encrypted LONGTEXT NULL AFTER message;

ALTER TABLE reports
    ADD COLUMN IF NOT EXISTS description_encrypted LONGTEXT NULL AFTER description,
    ADD COLUMN IF NOT EXISTS admin_notes_encrypted LONGTEXT NULL AFTER admin_notes;

ALTER TABLE report_actions
    ADD COLUMN IF NOT EXISTS notes_encrypted LONGTEXT NULL AFTER notes;

ALTER TABLE appeals
    ADD COLUMN IF NOT EXISTS reason_encrypted LONGTEXT NULL AFTER reason;

ALTER TABLE audit_logs
    ADD COLUMN IF NOT EXISTS old_value_encrypted LONGTEXT NULL AFTER old_value,
    ADD COLUMN IF NOT EXISTS new_value_encrypted LONGTEXT NULL AFTER new_value,
    ADD COLUMN IF NOT EXISTS description_encrypted LONGTEXT NULL AFTER description,
    ADD COLUMN IF NOT EXISTS ip_address_encrypted TEXT NULL AFTER ip_address,
    ADD COLUMN IF NOT EXISTS user_agent_encrypted TEXT NULL AFTER user_agent;

ALTER TABLE bug_reports
    ADD COLUMN IF NOT EXISTS browser_encrypted TEXT NULL AFTER browser,
    ADD COLUMN IF NOT EXISTS device_encrypted TEXT NULL AFTER device,
    ADD COLUMN IF NOT EXISTS screenshot_encrypted TEXT NULL AFTER screenshot,
    ADD COLUMN IF NOT EXISTS description_encrypted LONGTEXT NULL AFTER description;

ALTER TABLE support_conversations
    ADD COLUMN IF NOT EXISTS subject_encrypted TEXT NULL AFTER subject,
    ADD COLUMN IF NOT EXISTS last_message_encrypted LONGTEXT NULL AFTER last_message;

ALTER TABLE support_messages
    ADD COLUMN IF NOT EXISTS message_encrypted LONGTEXT NULL AFTER message,
    ADD COLUMN IF NOT EXISTS attachment_url_encrypted TEXT NULL AFTER attachment_url;

ALTER TABLE support_notes
    ADD COLUMN IF NOT EXISTS note_encrypted LONGTEXT NULL AFTER note;

ALTER TABLE order_cancellation_requests
    ADD COLUMN IF NOT EXISTS request_reason_encrypted LONGTEXT NULL AFTER request_reason,
    ADD COLUMN IF NOT EXISTS response_reason_encrypted LONGTEXT NULL AFTER response_reason;

ALTER TABLE return_requests
    ADD COLUMN IF NOT EXISTS reason_encrypted LONGTEXT NULL AFTER reason,
    ADD COLUMN IF NOT EXISTS refund_reference_encrypted TEXT NULL AFTER refund_reference,
    ADD COLUMN IF NOT EXISTS decision_reason_encrypted LONGTEXT NULL AFTER decision_reason,
    ADD COLUMN IF NOT EXISTS resolution_note_encrypted LONGTEXT NULL AFTER resolution_note;

ALTER TABLE return_resolution_history
    ADD COLUMN IF NOT EXISTS note_encrypted LONGTEXT NULL AFTER note;

ALTER TABLE blocked_users
    ADD COLUMN IF NOT EXISTS reason_encrypted TEXT NULL AFTER reason;

ALTER TABLE orders
    ADD COLUMN IF NOT EXISTS delivery_latitude_encrypted TEXT NULL AFTER delivery_latitude,
    ADD COLUMN IF NOT EXISTS delivery_longitude_encrypted TEXT NULL AFTER delivery_longitude,
    ADD COLUMN IF NOT EXISTS delivery_barangay_encrypted TEXT NULL AFTER delivery_barangay,
    ADD COLUMN IF NOT EXISTS delivery_municipality_encrypted TEXT NULL AFTER delivery_municipality,
    ADD COLUMN IF NOT EXISTS delivery_province_encrypted TEXT NULL AFTER delivery_province;

ALTER TABLE farmer_applications
    ADD COLUMN IF NOT EXISTS ai_analysis_encrypted LONGTEXT NULL AFTER ai_analysis,
    ADD COLUMN IF NOT EXISTS ai_review_summary_encrypted LONGTEXT NULL AFTER ai_review_summary,
    ADD COLUMN IF NOT EXISTS ai_review_notes_encrypted LONGTEXT NULL AFTER ai_review_notes,
    ADD COLUMN IF NOT EXISTS rsbsa_verification_notes_encrypted LONGTEXT NULL AFTER rsbsa_verification_notes,
    ADD COLUMN IF NOT EXISTS verification_reason_encrypted LONGTEXT NULL AFTER verification_reason,
    ADD COLUMN IF NOT EXISTS rsbsa_reference_number_encrypted TEXT NULL AFTER rsbsa_reference_number,
    ADD COLUMN IF NOT EXISTS ai_verification_note_encrypted LONGTEXT NULL AFTER ai_verification_note;

ALTER TABLE notifications
    ADD COLUMN IF NOT EXISTS message_encrypted LONGTEXT NULL AFTER message;

ALTER TABLE support_notifications
    ADD COLUMN IF NOT EXISTS message_encrypted LONGTEXT NULL AFTER message;

ALTER TABLE verification_logs
    ADD COLUMN IF NOT EXISTS notes_encrypted LONGTEXT NULL AFTER notes,
    ADD COLUMN IF NOT EXISTS reason_encrypted LONGTEXT NULL AFTER reason;

ALTER TABLE products
    ADD COLUMN IF NOT EXISTS admin_note_encrypted LONGTEXT NULL AFTER admin_note,
    ADD COLUMN IF NOT EXISTS correction_reason_encrypted LONGTEXT NULL AFTER correction_reason,
    ADD COLUMN IF NOT EXISTS moderation_reason_encrypted LONGTEXT NULL AFTER moderation_reason;

ALTER TABLE product_approval_logs
    ADD COLUMN IF NOT EXISTS note_encrypted LONGTEXT NULL AFTER note;
