<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap/app.php';

use App\Core\Database;
use App\Services\SensitiveDataCipher;

$apply = in_array('--apply', $argv, true);
$db = Database::connection();
$cipher = new SensitiveDataCipher();

$mappings = [
    'users' => [
        'first_name' => 'first_name_encrypted',
        'last_name' => 'last_name_encrypted',
        'email' => 'email_encrypted',
        'address' => 'address_encrypted',
        'phone' => 'phone_encrypted',
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
        'address' => 'address_encrypted',
        'contact_number' => 'contact_number_encrypted',
        'date_of_birth' => 'date_of_birth_encrypted',
        'rsbsa_number' => 'rsbsa_number_encrypted',
        'extracted_name' => 'extracted_name_encrypted',
        'extracted_rsbsa_number' => 'extracted_rsbsa_number_encrypted',
        'rsbsa_card_path' => 'rsbsa_card_path_encrypted',
        'ai_analysis' => 'ai_analysis_encrypted',
        'ai_review_summary' => 'ai_review_summary_encrypted',
        'ai_review_notes' => 'ai_review_notes_encrypted',
        'rsbsa_verification_notes' => 'rsbsa_verification_notes_encrypted',
        'verification_reason' => 'verification_reason_encrypted',
        'rsbsa_reference_number' => 'rsbsa_reference_number_encrypted',
        'ai_verification_note' => 'ai_verification_note_encrypted',
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
    'conversations' => [
        'last_message' => 'last_message_encrypted',
    ],
    'messages' => [
        'message' => 'message_encrypted',
        'image' => 'image_encrypted',
    ],
    'message_requests' => [
        'message' => 'message_encrypted',
        'image' => 'image_encrypted',
    ],
    'complaints' => [
        'description' => 'description_encrypted',
        'admin_notes' => 'admin_notes_encrypted',
    ],
    'complaint_actions' => ['notes' => 'notes_encrypted'],
    'complaint_replies' => ['message' => 'message_encrypted'],
    'reports' => [
        'description' => 'description_encrypted',
        'admin_notes' => 'admin_notes_encrypted',
    ],
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
        'browser' => 'browser_encrypted',
        'device' => 'device_encrypted',
        'screenshot' => 'screenshot_encrypted',
        'description' => 'description_encrypted',
    ],
    'support_conversations' => [
        'subject' => 'subject_encrypted',
        'last_message' => 'last_message_encrypted',
    ],
    'support_messages' => [
        'message' => 'message_encrypted',
        'attachment_url' => 'attachment_url_encrypted',
    ],
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
    'return_resolution_history' => [
        'note' => 'note_encrypted',
    ],
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

$totalEligible = 0;
$totalWritten = 0;

foreach ($mappings as $table => $fields) {
    $available = $db->query("SHOW COLUMNS FROM {$table}")->fetchAll(PDO::FETCH_COLUMN);
    $fields = array_filter(
        $fields,
        static fn(string $encrypted, string $plain): bool => in_array($plain, $available, true)
            && in_array($encrypted, $available, true),
        ARRAY_FILTER_USE_BOTH
    );
    if ($fields === []) {
        continue;
    }

    $selectColumns = array_merge(['id'], array_keys($fields), array_values($fields));
    $rows = $db->query('SELECT ' . implode(', ', $selectColumns) . " FROM {$table}")->fetchAll(PDO::FETCH_ASSOC);
    $tableEligible = 0;
    $tableWritten = 0;

    if ($apply) {
        $db->beginTransaction();
    }

    try {
        foreach ($rows as $row) {
            $updates = [];
            $values = [];
            foreach ($fields as $plain => $encrypted) {
                $plainValue = $row[$plain] ?? null;
                $encryptedValue = $row[$encrypted] ?? null;
                if ($plainValue === null || $plainValue === '' || (is_string($encryptedValue) && $encryptedValue !== '')) {
                    continue;
                }

                $tableEligible++;
                $totalEligible++;
                if ($apply) {
                    $context = $table . '.' . $plain;
                    $newValue = $cipher->encrypt((string) $plainValue, $context);
                    if ($cipher->decrypt($newValue, $context) !== (string) $plainValue) {
                        throw new RuntimeException("Encryption verification failed for {$table}.{$plain} record " . (int) $row['id']);
                    }
                    $updates[] = $encrypted . ' = ?';
                    $values[] = $newValue;
                }
            }

            if ($apply && $updates !== []) {
                $values[] = (int) $row['id'];
                $statement = $db->prepare('UPDATE ' . $table . ' SET ' . implode(', ', $updates) . ' WHERE id = ?');
                $statement->execute($values);
                $tableWritten += count($updates);
                $totalWritten += count($updates);
            }
        }

        if ($apply) {
            $db->commit();
        }
    } catch (Throwable $exception) {
        if ($apply && $db->inTransaction()) {
            $db->rollBack();
        }
        throw $exception;
    }

    echo sprintf(
        "%s: %d eligible field value(s)%s\n",
        $table,
        $tableEligible,
        $apply ? ", {$tableWritten} encrypted" : ''
    );
}

echo $apply
    ? "Encryption backfill complete: {$totalWritten} field value(s) written and verified.\n"
    : "Dry run complete: {$totalEligible} field value(s) can be encrypted. Run again with --apply to write them.\n";
