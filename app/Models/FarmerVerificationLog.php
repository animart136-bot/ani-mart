<?php

namespace App\Models;

use App\Core\Database;
use App\Services\SensitiveDataCipher;

class FarmerVerificationLog
{
    public static function recordManual(int $applicationId, ?int $adminId, string $type, string $status, string $notes): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO verification_logs (application_id, admin_id, verification_type, verification_status, notes, notes_encrypted, verified_at, reason, reason_encrypted, ai_status) VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?)'
        );

        $cipher = new SensitiveDataCipher();
        $stmt->execute([
            $applicationId, $adminId, $type, $status,
            $notes, $cipher->encrypt($notes, 'verification_logs.notes'),
            $notes, $cipher->encrypt($notes, 'verification_logs.reason'), 'pending',
        ]);
    }

    public static function record(int $applicationId, array $verification): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO verification_logs (application_id, confidence_score, ai_status, reason, reason_encrypted) VALUES (?, ?, ?, ?, ?)'
        );

        $stmt->execute([
            $applicationId,
            $verification['confidence_score'] ?? null,
            $verification['ai_status'] ?? 'pending',
            $verification['verification_reason'] ?? null,
            (new SensitiveDataCipher())->encrypt($verification['verification_reason'] ?? null, 'verification_logs.reason'),
        ]);
    }
}
