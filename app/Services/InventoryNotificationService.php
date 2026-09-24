<?php

namespace App\Services;

use App\Core\Database;

class InventoryNotificationService
{
    public const LOW_STOCK_THRESHOLD = 3;

    public static function levelFor(int $stock): string
    {
        if ($stock <= 0) return 'out';
        if ($stock <= self::LOW_STOCK_THRESHOLD) return 'low';
        return 'healthy';
    }

    public function evaluateProduct(int $productId): void
    {
        $db = Database::connection();
        $stmt = $db->prepare('SELECT p.id, p.name, p.stock, p.seller_profile_id, sp.user_id AS seller_user_id
            FROM products p INNER JOIN seller_profiles sp ON sp.id = p.seller_profile_id
            WHERE p.id = ? LIMIT 1');
        $stmt->execute([$productId]);
        $product = $stmt->fetch();
        if (!$product) return;

        $this->evaluateEntity(
            (int) $product->seller_user_id,
            (int) $product->seller_profile_id,
            (int) $product->id,
            'product',
            (int) $product->id,
            (string) $product->name,
            (int) $product->stock
        );
    }

    private function evaluateEntity(int $sellerUserId, int $sellerProfileId, int $productId, string $type, int $entityId, string $label, int $stock): void
    {
        $db = Database::connection();
        $level = self::levelFor($stock);
        $stmt = $db->prepare('SELECT id, stock_level FROM inventory_stock_alerts WHERE entity_type = ? AND entity_id = ? LIMIT 1');
        $stmt->execute([$type, $entityId]);
        $existing = $stmt->fetch();
        $shouldNotify = !$existing ? $level !== 'healthy' : (string) $existing->stock_level !== $level && $level !== 'healthy';

        if ($existing) {
            $db->prepare('UPDATE inventory_stock_alerts SET seller_user_id = ?, product_id = ?, stock_level = ?, last_stock = ?, last_notified_at = CASE WHEN ? = 1 THEN NOW() ELSE last_notified_at END WHERE id = ?')
                ->execute([$sellerUserId, $productId, $level, $stock, $shouldNotify ? 1 : 0, (int) $existing->id]);
        } else {
            $db->prepare('INSERT INTO inventory_stock_alerts (seller_user_id, product_id, entity_type, entity_id, stock_level, last_stock, last_notified_at, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())')
                ->execute([$sellerUserId, $productId, $type, $entityId, $level, $stock, $shouldNotify ? date('Y-m-d H:i:s') : null]);
        }

        if (!$shouldNotify) return;
        $url = '/ani-mart/public/farmer/products/edit?id=' . $productId
            . '&shop_id=' . $sellerProfileId;
        if ($level === 'out') {
            NotificationService::send($sellerUserId, 'Out of Stock', '“' . $label . '” is now out of stock. Restock it to make it available to customers.', 'product', 'bi-box-seam', 'danger', $url, 'product', $productId, $url);
            return;
        }

        NotificationService::send($sellerUserId, 'Low Stock', '“' . $label . '” has only ' . $stock . ' ' . ($stock === 1 ? 'unit' : 'units') . ' remaining.', 'product', 'bi-exclamation-triangle', 'warning', $url, 'product', $productId, $url);
    }

}
