<?php

namespace App\Services;

final class StatusPresenter
{
    private const FINALIZED = ['resolved', 'rejected', 'closed'];

    private const COLORS = [
        'approved' => 'success',
        'accepted' => 'success',
        'completed' => 'success',
        'resolved' => 'success',
        'verified' => 'success',
        'pending' => 'warning text-dark',
        'pending_review' => 'warning text-dark',
        'pending_admin_review' => 'warning text-dark',
        'under_review' => 'info text-dark',
        'investigating' => 'info text-dark',
        'investigation' => 'warning text-dark',
        'assigned' => 'warning text-dark',
        'need_more_info' => 'info text-dark',
        'waiting_customer' => 'primary',
        'waiting_seller' => 'primary',
        'needs_correction' => 'warning text-dark',
        'rejected' => 'danger',
        'cancelled' => 'danger',
        'suspended' => 'danger',
        'closed' => 'secondary',
        'submitted' => 'secondary',
    ];

    public static function normalize(?string $status): string
    {
        return strtolower(trim((string) $status));
    }

    public static function label(?string $status): string
    {
        $normalized = self::normalize($status);
        return $normalized === '' ? 'Unknown' : ucwords(str_replace('_', ' ', $normalized));
    }

    public static function badgeClass(?string $status): string
    {
        return 'bg-' . (self::COLORS[self::normalize($status)] ?? 'secondary');
    }

    public static function isFinalized(?string $status): bool
    {
        return in_array(self::normalize($status), self::FINALIZED, true);
    }

    public static function finalizedStatuses(): array
    {
        return self::FINALIZED;
    }
}
