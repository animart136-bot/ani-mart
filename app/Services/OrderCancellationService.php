<?php

namespace App\Services;

use App\Core\Database;
use DomainException;
use Throwable;

class OrderCancellationService
{
    public function request(int $orderId, int $customerId, string $reason): object
    {
        (new OrderWorkflowSchemaService())->ensure();
        $reason = $this->validatedReason($reason, 'Please explain why you want to cancel the order.');
        $db = Database::connection();
        $db->beginTransaction();

        try {
            $stmt = $db->prepare("SELECT o.id, o.order_number, o.status,
                    sp.user_id AS seller_user_id
                FROM orders o
                INNER JOIN order_items oi ON oi.order_id = o.id
                INNER JOIN seller_profiles sp ON sp.id = oi.seller_profile_id
                WHERE o.id = ? AND o.user_id = ?
                LIMIT 1 FOR UPDATE");
            $stmt->execute([$orderId, $customerId]);
            $order = $stmt->fetch();

            if (!$order) {
                throw new DomainException('Order not found.');
            }

            if (!MarketplaceRulesService::canRequestCancellation((string) $order->status)) {
                throw new DomainException('Cancellation can only be requested before preparation starts.');
            }

            $existing = $db->prepare('SELECT status FROM order_cancellation_requests WHERE order_id = ? LIMIT 1 FOR UPDATE');
            $existing->execute([$orderId]);
            if ($existing->fetch()) {
                throw new DomainException('A cancellation request has already been submitted for this order.');
            }

            $insert = $db->prepare("INSERT INTO order_cancellation_requests
                (order_id, customer_id, seller_user_id, request_reason, request_reason_encrypted, status, requested_at)
                VALUES (?, ?, ?, ?, ?, 'pending', NOW())");
            $insert->execute([$orderId, $customerId, $order->seller_user_id, $reason, (new SensitiveDataCipher())->encrypt($reason, 'order_cancellation_requests.request_reason')]);
            $order->request_reason = $reason;
            $order->request_id = (int) $db->lastInsertId();
            $db->commit();

            return $order;
        } catch (Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $exception;
        }
    }

    public function respond(int $orderId, int $sellerUserId, string $decision, string $reason): object
    {
        (new OrderWorkflowSchemaService())->ensure();
        if (!in_array($decision, ['accepted', 'declined'], true)) {
            throw new DomainException('Choose whether to accept or decline the cancellation request.');
        }

        $reason = $decision === 'accepted'
            ? ''
            : $this->validatedReason($reason, 'Please provide a reason for declining this cancellation request.');
        $db = Database::connection();
        $db->beginTransaction();

        try {
            $stmt = $db->prepare("SELECT cr.*, o.order_number, o.status AS order_status
                FROM order_cancellation_requests cr
                INNER JOIN orders o ON o.id = cr.order_id
                WHERE cr.order_id = ? AND cr.seller_user_id = ?
                LIMIT 1 FOR UPDATE");
            $stmt->execute([$orderId, $sellerUserId]);
            $request = $stmt->fetch();
            $request = hydrate_sensitive_fields($request, 'order_cancellation_requests', ['request_reason', 'response_reason']);

            if (!$request) {
                throw new DomainException('Cancellation request not found.');
            }

            if ((string) $request->status !== 'pending') {
                throw new DomainException('This cancellation request has already been answered.');
            }

            if ($decision === 'accepted') {
                if (!MarketplaceRulesService::canRequestCancellation((string) $request->order_status)) {
                    throw new DomainException('This order has already entered preparation and can no longer be cancelled.');
                }

                $items = $db->prepare('SELECT product_id, product_variant_id, quantity FROM order_items WHERE order_id = ? FOR UPDATE');
                $items->execute([$orderId]);
                $restockedProductIds = [];
                foreach ($items->fetchAll() as $item) {
                    $restockedProductIds[(int) $item->product_id] = true;
                    $db->prepare('UPDATE products SET stock = stock + ? WHERE id = ?')
                        ->execute([(int) $item->quantity, (int) $item->product_id]);

                    if (!empty($item->product_variant_id)) {
                        $db->prepare('UPDATE product_variants SET stock = stock + ? WHERE id = ? AND product_id = ?')
                            ->execute([(int) $item->quantity, (int) $item->product_variant_id, (int) $item->product_id]);
                    }
                }

                $inventoryNotifier = new InventoryNotificationService();
                foreach (array_keys($restockedProductIds) as $restockedProductId) {
                    $inventoryNotifier->evaluateProduct((int) $restockedProductId);
                }

                $db->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?")
                    ->execute([$orderId]);
            }

            $db->prepare('UPDATE order_cancellation_requests
                    SET status = ?, response_reason = ?, response_reason_encrypted = ?, responded_at = NOW()
                    WHERE id = ? AND status = \'pending\'')
                ->execute([$decision, $reason, (new SensitiveDataCipher())->encrypt($reason, 'order_cancellation_requests.response_reason'), (int) $request->id]);

            $request->status = $decision;
            $request->response_reason = $reason;
            $db->commit();

            return $request;
        } catch (Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $exception;
        }
    }

    private function validatedReason(string $reason, string $emptyMessage): string
    {
        $reason = trim($reason);
        if ($reason === '') {
            throw new DomainException($emptyMessage);
        }
        if (mb_strlen($reason) < 5) {
            throw new DomainException('The reason must be at least 5 characters.');
        }
        if (mb_strlen($reason) > 500) {
            throw new DomainException('The reason cannot exceed 500 characters.');
        }

        return $reason;
    }
}
