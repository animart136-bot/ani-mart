<?php

namespace App\Services;

use App\Core\Database;
use DateTimeImmutable;
use PDO;

class DashboardAnalyticsService
{
    public function forRole(string $role, int $userId, ?int $sellerProfileId = null): array
    {
        $role = strtolower($role);
        $months = $this->months(6);

        try {
            $trendRows = $this->trendRows($role, $userId, $sellerProfileId);
            $statusRows = $this->statusRows($role, $userId, $sellerProfileId);
            $topRows = $this->topRows($role, $userId, $sellerProfileId);
        } catch (\Throwable $e) {
            $trendRows = $statusRows = $topRows = [];
        }

        $trend = [];
        foreach ($months as $key => $label) {
            $row = $trendRows[$key] ?? ['orders' => 0, 'amount' => 0];
            $trend[] = [
                'month' => $key,
                'label' => $label,
                'orders' => (int) ($row['orders'] ?? 0),
                'amount' => round((float) ($row['amount'] ?? 0), 2),
            ];
        }

        $statuses = [];
        foreach ($statusRows as $row) {
            $status = strtolower((string) ($row['status'] ?? 'pending'));
            $statuses[] = [
                'status' => $status,
                'label' => ucwords(str_replace('_', ' ', $status)),
                'total' => (int) ($row['total'] ?? 0),
            ];
        }

        $top = array_map(static fn(array $row): array => [
            'label' => (string) ($row['label'] ?? 'Other'),
            'quantity' => (int) ($row['quantity'] ?? 0),
            'amount' => round((float) ($row['amount'] ?? 0), 2),
        ], $topRows);

        return [
            'trend' => $trend,
            'statuses' => $statuses,
            'top' => $top,
            'has_activity' => array_sum(array_column($trend, 'orders')) > 0,
            'trend_title' => $role === 'customer' ? 'Orders and spending' : 'Orders and revenue',
            'amount_label' => $role === 'customer' ? 'Amount spent' : 'Revenue',
            'top_title' => $role === 'farmer' ? 'Top-selling products' : 'Top categories',
        ];
    }

    private function trendRows(string $role, int $userId, ?int $sellerProfileId): array
    {
        $db = Database::connection();
        $start = (new DateTimeImmutable('first day of -5 months'))->format('Y-m-d 00:00:00');

        if ($role === 'farmer' && $sellerProfileId) {
            $stmt = $db->prepare(
                "SELECT DATE_FORMAT(o.created_at, '%Y-%m') month_key,
                        COUNT(DISTINCT o.id) orders,
                        SUM(oi.price * oi.quantity) amount
                 FROM orders o INNER JOIN order_items oi ON oi.order_id = o.id
                 WHERE oi.seller_profile_id = ? AND o.created_at >= ?
                   AND LOWER(COALESCE(o.status, 'pending')) <> 'cancelled'
                 GROUP BY month_key"
            );
            $stmt->execute([$sellerProfileId, $start]);
        } elseif ($role === 'customer') {
            $stmt = $db->prepare(
                "SELECT DATE_FORMAT(created_at, '%Y-%m') month_key,
                        COUNT(*) orders, SUM(total_amount) amount
                 FROM orders WHERE user_id = ? AND created_at >= ?
                   AND LOWER(COALESCE(status, 'pending')) <> 'cancelled'
                 GROUP BY month_key"
            );
            $stmt->execute([$userId, $start]);
        } else {
            $stmt = $db->prepare(
                "SELECT DATE_FORMAT(created_at, '%Y-%m') month_key,
                        COUNT(*) orders, SUM(total_amount) amount
                 FROM orders WHERE created_at >= ?
                   AND LOWER(COALESCE(status, 'pending')) <> 'cancelled'
                 GROUP BY month_key"
            );
            $stmt->execute([$start]);
        }

        $result = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) $result[$row['month_key']] = $row;
        return $result;
    }

    private function statusRows(string $role, int $userId, ?int $sellerProfileId): array
    {
        $db = Database::connection();
        if ($role === 'farmer' && $sellerProfileId) {
            $stmt = $db->prepare(
                "SELECT LOWER(COALESCE(o.status, 'pending')) status, COUNT(DISTINCT o.id) total
                 FROM orders o INNER JOIN order_items oi ON oi.order_id = o.id
                 WHERE oi.seller_profile_id = ? GROUP BY status ORDER BY total DESC"
            );
            $stmt->execute([$sellerProfileId]);
        } elseif ($role === 'customer') {
            $stmt = $db->prepare("SELECT LOWER(COALESCE(status, 'pending')) status, COUNT(*) total FROM orders WHERE user_id = ? GROUP BY status ORDER BY total DESC");
            $stmt->execute([$userId]);
        } else {
            $stmt = $db->query("SELECT LOWER(COALESCE(status, 'pending')) status, COUNT(*) total FROM orders GROUP BY status ORDER BY total DESC");
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function topRows(string $role, int $userId, ?int $sellerProfileId): array
    {
        $db = Database::connection();
        $where = '';
        $params = [];
        $label = "COALESCE(c.name, 'Uncategorized')";

        if ($role === 'farmer' && $sellerProfileId) {
            $where = 'WHERE oi.seller_profile_id = ?';
            $params[] = $sellerProfileId;
            $label = "COALESCE(p.name, 'Product')";
        } elseif ($role === 'customer') {
            $where = 'WHERE o.user_id = ?';
            $params[] = $userId;
        }

        $stmt = $db->prepare(
            "SELECT {$label} label, SUM(oi.quantity) quantity, SUM(oi.price * oi.quantity) amount
             FROM order_items oi
             INNER JOIN orders o ON o.id = oi.order_id
             LEFT JOIN products p ON p.id = oi.product_id
             LEFT JOIN categories c ON c.id = p.category_id
             {$where}
             GROUP BY label ORDER BY quantity DESC, amount DESC LIMIT 5"
        );
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function months(int $count): array
    {
        $months = [];
        $cursor = new DateTimeImmutable('first day of -' . ($count - 1) . ' months');
        for ($i = 0; $i < $count; $i++) {
            $months[$cursor->format('Y-m')] = $cursor->format('M Y');
            $cursor = $cursor->modify('+1 month');
        }
        return $months;
    }
}
