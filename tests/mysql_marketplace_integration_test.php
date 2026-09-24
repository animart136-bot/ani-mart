<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

$socket = getenv('ANI_MART_TEST_DB_SOCKET') ?: '/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock';
$database = getenv('ANI_MART_TEST_DB_NAME') ?: 'ani_mart';
$user = getenv('ANI_MART_TEST_DB_USER') ?: 'root';
$password = getenv('ANI_MART_TEST_DB_PASSWORD') ?: '';

$db = new PDO(
    "mysql:unix_socket={$socket};dbname={$database};charset=utf8mb4",
    $user,
    $password,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ]
);

$failures = [];
$assert = static function (bool $condition, string $message) use (&$failures): void {
    if (!$condition) {
        $failures[] = $message;
    }
};

$requiredColumns = [
    'users' => [
        'first_name_encrypted', 'last_name_encrypted', 'email_encrypted',
        'phone_encrypted', 'address_encrypted', 'latitude_encrypted',
        'longitude_encrypted', 'barangay_encrypted', 'municipality_encrypted',
        'province_encrypted', 'suspension_reason_encrypted',
    ],
    'cart' => ['product_variant_id', 'unit_price'],
    'order_items' => ['product_variant_id', 'variant_label', 'unit_price'],
    'orders' => [
        'requested_delivery_date',
        'delivery_slot',
        'substitution_preference',
        'shipping_address_encrypted',
        'delivery_address_encrypted',
        'customer_note_encrypted',
        'delivery_latitude_encrypted', 'delivery_longitude_encrypted',
        'delivery_barangay_encrypted', 'delivery_municipality_encrypted',
        'delivery_province_encrypted',
    ],
    'farmer_applications' => [
        'first_name_encrypted', 'middle_name_encrypted', 'last_name_encrypted',
        'name_extension_encrypted', 'sex_encrypted', 'rsbsa_card_path_encrypted',
        'ai_analysis_encrypted', 'ai_review_summary_encrypted', 'ai_review_notes_encrypted',
        'rsbsa_verification_notes_encrypted', 'verification_reason_encrypted',
        'rsbsa_reference_number_encrypted', 'ai_verification_note_encrypted',
        'address_encrypted',
        'contact_number_encrypted',
        'date_of_birth_encrypted',
        'rsbsa_number_encrypted',
        'extracted_name_encrypted',
        'extracted_rsbsa_number_encrypted',
    ],
    'seller_profiles' => [
        'farm_location_encrypted', 'contact_number_encrypted', 'latitude_encrypted',
        'longitude_encrypted', 'barangay_encrypted', 'city_encrypted',
        'government_id_encrypted',
    ],
    'conversations' => ['last_message_encrypted'],
    'messages' => ['message_encrypted', 'image_encrypted'],
    'message_requests' => ['message_encrypted', 'image_encrypted'],
    'complaints' => ['description_encrypted', 'admin_notes_encrypted'],
    'complaint_actions' => ['notes_encrypted'],
    'complaint_replies' => ['message_encrypted'],
    'reports' => ['description_encrypted', 'admin_notes_encrypted'],
    'report_actions' => ['notes_encrypted'],
    'appeals' => ['reason_encrypted'],
    'audit_logs' => [
        'old_value_encrypted', 'new_value_encrypted', 'description_encrypted',
        'ip_address_encrypted', 'user_agent_encrypted',
    ],
    'bug_reports' => ['browser_encrypted', 'device_encrypted', 'screenshot_encrypted', 'description_encrypted'],
    'support_conversations' => ['subject_encrypted', 'last_message_encrypted'],
    'support_messages' => ['message_encrypted', 'attachment_url_encrypted'],
    'support_notes' => ['note_encrypted'],
    'order_cancellation_requests' => ['request_reason_encrypted', 'response_reason_encrypted'],
    'return_requests' => [
        'reason_encrypted', 'refund_reference_encrypted',
        'decision_reason_encrypted', 'resolution_note_encrypted',
    ],
    'return_resolution_history' => ['note_encrypted'],
    'blocked_users' => ['reason_encrypted'],
    'notifications' => ['message_encrypted'],
    'support_notifications' => ['message_encrypted'],
    'verification_logs' => ['notes_encrypted', 'reason_encrypted'],
    'products' => ['admin_note_encrypted', 'correction_reason_encrypted', 'moderation_reason_encrypted'],
    'product_approval_logs' => ['note_encrypted'],
];
foreach ($requiredColumns as $table => $columns) {
    $actual = $db->query("SHOW COLUMNS FROM `{$table}`")->fetchAll();
    $actualNames = array_map(static fn($column) => $column->Field, $actual);
    foreach ($columns as $column) {
        $assert(in_array($column, $actualNames, true), "{$table}.{$column} exists");
    }
}

$encryptedMappings = [
    'users' => [
        'first_name' => 'first_name_encrypted',
        'last_name' => 'last_name_encrypted',
        'email' => 'email_encrypted',
        'phone' => 'phone_encrypted',
        'address' => 'address_encrypted',
        'latitude' => 'latitude_encrypted',
        'longitude' => 'longitude_encrypted',
        'barangay' => 'barangay_encrypted',
        'municipality' => 'municipality_encrypted',
        'province' => 'province_encrypted',
        'suspension_reason' => 'suspension_reason_encrypted',
    ],
    'orders' => [
        'shipping_address' => 'shipping_address_encrypted',
        'delivery_address' => 'delivery_address_encrypted',
        'customer_note' => 'customer_note_encrypted',
        'delivery_latitude' => 'delivery_latitude_encrypted',
        'delivery_longitude' => 'delivery_longitude_encrypted',
        'delivery_barangay' => 'delivery_barangay_encrypted',
        'delivery_municipality' => 'delivery_municipality_encrypted',
        'delivery_province' => 'delivery_province_encrypted',
    ],
    'farmer_applications' => [
        'first_name' => 'first_name_encrypted',
        'middle_name' => 'middle_name_encrypted',
        'last_name' => 'last_name_encrypted',
        'name_extension' => 'name_extension_encrypted',
        'sex' => 'sex_encrypted',
        'rsbsa_card_path' => 'rsbsa_card_path_encrypted',
        'ai_analysis' => 'ai_analysis_encrypted',
        'ai_review_summary' => 'ai_review_summary_encrypted',
        'ai_review_notes' => 'ai_review_notes_encrypted',
        'rsbsa_verification_notes' => 'rsbsa_verification_notes_encrypted',
        'verification_reason' => 'verification_reason_encrypted',
        'rsbsa_reference_number' => 'rsbsa_reference_number_encrypted',
        'ai_verification_note' => 'ai_verification_note_encrypted',
        'address' => 'address_encrypted',
        'contact_number' => 'contact_number_encrypted',
        'date_of_birth' => 'date_of_birth_encrypted',
        'rsbsa_number' => 'rsbsa_number_encrypted',
        'extracted_name' => 'extracted_name_encrypted',
        'extracted_rsbsa_number' => 'extracted_rsbsa_number_encrypted',
    ],
    'seller_profiles' => [
        'farm_location' => 'farm_location_encrypted',
        'contact_number' => 'contact_number_encrypted',
        'latitude' => 'latitude_encrypted',
        'longitude' => 'longitude_encrypted',
        'barangay' => 'barangay_encrypted',
        'city' => 'city_encrypted',
        'government_id' => 'government_id_encrypted',
    ],
    'conversations' => ['last_message' => 'last_message_encrypted'],
    'messages' => ['message' => 'message_encrypted', 'image' => 'image_encrypted'],
    'message_requests' => ['message' => 'message_encrypted', 'image' => 'image_encrypted'],
    'complaints' => ['description' => 'description_encrypted', 'admin_notes' => 'admin_notes_encrypted'],
    'complaint_actions' => ['notes' => 'notes_encrypted'],
    'complaint_replies' => ['message' => 'message_encrypted'],
    'reports' => ['description' => 'description_encrypted', 'admin_notes' => 'admin_notes_encrypted'],
    'report_actions' => ['notes' => 'notes_encrypted'],
    'appeals' => ['reason' => 'reason_encrypted'],
    'audit_logs' => [
        'old_value' => 'old_value_encrypted',
        'new_value' => 'new_value_encrypted',
        'description' => 'description_encrypted',
        'ip_address' => 'ip_address_encrypted',
        'user_agent' => 'user_agent_encrypted',
    ],
    'bug_reports' => [
        'browser' => 'browser_encrypted', 'device' => 'device_encrypted',
        'screenshot' => 'screenshot_encrypted', 'description' => 'description_encrypted',
    ],
    'support_conversations' => ['subject' => 'subject_encrypted', 'last_message' => 'last_message_encrypted'],
    'support_messages' => ['message' => 'message_encrypted', 'attachment_url' => 'attachment_url_encrypted'],
    'support_notes' => ['note' => 'note_encrypted'],
    'order_cancellation_requests' => [
        'request_reason' => 'request_reason_encrypted',
        'response_reason' => 'response_reason_encrypted',
    ],
    'return_requests' => [
        'reason' => 'reason_encrypted',
        'refund_reference' => 'refund_reference_encrypted',
        'decision_reason' => 'decision_reason_encrypted',
        'resolution_note' => 'resolution_note_encrypted',
    ],
    'return_resolution_history' => ['note' => 'note_encrypted'],
    'blocked_users' => ['reason' => 'reason_encrypted'],
    'notifications' => ['message' => 'message_encrypted'],
    'support_notifications' => ['message' => 'message_encrypted'],
    'verification_logs' => ['notes' => 'notes_encrypted', 'reason' => 'reason_encrypted'],
    'products' => [
        'admin_note' => 'admin_note_encrypted',
        'correction_reason' => 'correction_reason_encrypted',
        'moderation_reason' => 'moderation_reason_encrypted',
    ],
    'product_approval_logs' => ['note' => 'note_encrypted'],
];

foreach ($encryptedMappings as $table => $fields) {
    foreach ($fields as $plain => $encrypted) {
        $missing = (int) $db->query(
            "SELECT COUNT(*) FROM `{$table}` WHERE `{$plain}` IS NOT NULL AND `{$plain}` <> '' AND (`{$encrypted}` IS NULL OR `{$encrypted}` = '')"
        )->fetchColumn();
        $invalid = (int) $db->query(
            "SELECT COUNT(*) FROM `{$table}` WHERE `{$encrypted}` IS NOT NULL AND `{$encrypted}` <> '' AND `{$encrypted}` NOT LIKE 'enc:v1:%'"
        )->fetchColumn();
        $assert($missing === 0, "{$table}.{$plain} has an encrypted companion for every stored value");
        $assert($invalid === 0, "{$table}.{$encrypted} uses the versioned ciphertext format");
    }
}

$variant = $db->query("
    SELECT pv.id, pv.product_id, pv.stock
    FROM product_variants pv
    INNER JOIN products p ON p.id = pv.product_id
    WHERE pv.is_active = 1 AND pv.stock > 0
    ORDER BY pv.id LIMIT 1
")->fetch();
$assert($variant !== false, 'At least one in-stock variant is available for the inventory test');

if ($variant) {
    $before = (int) $variant->stock;
    $db->beginTransaction();
    try {
        $lock = $db->prepare('SELECT stock FROM product_variants WHERE id = ? FOR UPDATE');
        $lock->execute([$variant->id]);
        $lockedStock = (int) $lock->fetchColumn();
        $assert($lockedStock === $before, 'The selected variant is locked at the expected stock level');

        $update = $db->prepare('UPDATE product_variants SET stock = stock - 1 WHERE id = ? AND stock >= 1');
        $update->execute([$variant->id]);
        $assert($update->rowCount() === 1, 'A guarded stock decrement succeeds');
        $during = (int) $db->query('SELECT stock FROM product_variants WHERE id = ' . (int) $variant->id)->fetchColumn();
        $assert($during === $before - 1, 'The transaction decrements exactly one unit');
    } finally {
        $db->rollBack();
    }

    $after = (int) $db->query('SELECT stock FROM product_variants WHERE id = ' . (int) $variant->id)->fetchColumn();
    $assert($after === $before, 'The integration test leaves inventory unchanged');
}

if ($failures !== []) {
    fwrite(STDERR, "MySQL marketplace integration checks failed:\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "MySQL marketplace integration checks passed.\n";
