<?php

namespace App\Services;

/** Shared lifecycle and retention rules for reports, complaints, and returns. */
final class TrustCasePolicy
{
    public const EVIDENCE_RETENTION_DAYS = 365;

    private const FINAL_STATUSES = [
        'complaint' => ['resolved', 'rejected', 'closed'],
        'report' => ['resolved', 'rejected'],
        'return_request' => ['resolved', 'rejected'],
    ];

    public static function isFinal(string $caseType, ?string $status): bool
    {
        return in_array(
            StatusPresenter::normalize($status),
            self::FINAL_STATUSES[$caseType] ?? [],
            true
        );
    }

    public static function canReopen(string $caseType, ?string $status): bool
    {
        return $caseType === 'complaint' && self::isFinal($caseType, $status);
    }

    public static function retentionDays(string $caseType): int
    {
        return isset(self::FINAL_STATUSES[$caseType]) ? self::EVIDENCE_RETENTION_DAYS : 0;
    }
}
