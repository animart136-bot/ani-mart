<?php

namespace App\Services;

class MarketplaceRulesService
{
    public static function hasSingleSeller(array $sellerIds): bool
    {
        $sellerIds = array_values(array_unique(array_filter(array_map('intval', $sellerIds), fn($id) => $id > 0)));
        return count($sellerIds) === 1;
    }

    public static function canonicalPaymentMethod($paymentMethod): ?string
    {
        $paymentMethod = strtolower(trim((string) $paymentMethod));
        $paymentMethod = str_replace([' ', '-'], '_', $paymentMethod);
        return $paymentMethod === 'cash_on_delivery' ? $paymentMethod : null;
    }

    public static function canTransitionOrder(string $from, string $to): bool
    {
        $transitions = [
            'pending' => ['accepted'],
            'accepted' => ['preparing'],
            'preparing' => ['out_for_delivery'],
            'out_for_delivery' => ['delivered'],
            'delivered' => ['completed'],
        ];

        return in_array($to, $transitions[$from] ?? [], true);
    }

    public static function canTransitionSellerOrder(string $from, string $to): bool
    {
        $transitions = [
            'pending' => ['accepted', 'cancelled'],
            'accepted' => ['preparing', 'cancelled'],
            'preparing' => ['out_for_delivery'],
            'out_for_delivery' => ['delivered'],
            'delivered' => ['completed'],
        ];

        return in_array($to, $transitions[$from] ?? [], true);
    }

    public static function canRequestCancellation(string $status): bool
    {
        return in_array($status, ['pending', 'accepted'], true);
    }

    public static function canRequestReturn(string $status, ?string $deliveredAt = null): bool
    {
        if (!in_array($status, ['delivered', 'completed'], true)) {
            return false;
        }

        if ($deliveredAt === null || $deliveredAt === '') {
            return true;
        }

        try {
            $deliveryTime = new \DateTimeImmutable((string) $deliveredAt);
        } catch (\Exception) {
            return true;
        }

        $cutoff = $deliveryTime->add(new \DateInterval('PT24H'));
        return new \DateTimeImmutable('now') < $cutoff;
    }

    public static function orderNumber(?\DateTimeInterface $at = null): string
    {
        $at = $at ?? new \DateTimeImmutable();
        return 'ORD-' . $at->format('Ymd-His') . '-' . strtoupper(bin2hex(random_bytes(5)));
    }
}
