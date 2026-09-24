<?php

namespace App\Services;

use App\Core\Database;
use DomainException;
use Throwable;

class ReturnService
{
    public function create(int $orderId, int $customerId, string $reason, array $quantities): object
    {
        (new OrderWorkflowSchemaService())->ensure();
        $reason = $this->text($reason, 10, 1200, 'Please provide a detailed return reason.');
        $db = Database::connection();
        $db->beginTransaction();

        try {
            $orderStmt = $db->prepare("SELECT o.*, oi.seller_profile_id, sp.user_id AS seller_user_id
                FROM orders o
                INNER JOIN order_items oi ON oi.order_id = o.id
                INNER JOIN seller_profiles sp ON sp.id = oi.seller_profile_id
                WHERE o.id = ? AND o.user_id = ? LIMIT 1 FOR UPDATE");
            $orderStmt->execute([$orderId, $customerId]);
            $order = $orderStmt->fetch();

            if (!$order || !MarketplaceRulesService::canRequestReturn((string) $order->status, (string) ($order->delivered_at ?? $order->completed_at ?? ''))) {
                throw new DomainException('Only delivered or completed orders within the 24-hour return window are eligible for return.');
            }

            $existing = $db->prepare('SELECT id FROM return_requests WHERE order_id = ? LIMIT 1 FOR UPDATE');
            $existing->execute([$orderId]);
            if ($existing->fetch()) {
                throw new DomainException('A return record already exists for this order.');
            }

            $itemsStmt = $db->prepare('SELECT oi.*, p.name AS product_name FROM order_items oi
                INNER JOIN products p ON p.id = oi.product_id
                WHERE oi.order_id = ? AND oi.seller_profile_id = ? FOR UPDATE');
            $itemsStmt->execute([$orderId, (int) $order->seller_profile_id]);
            $selected = [];
            $requestedAmount = 0.0;

            foreach ($itemsStmt->fetchAll() as $item) {
                $quantity = (int) ($quantities[(int) $item->id] ?? 0);
                if ($quantity < 0 || $quantity > (int) $item->quantity) {
                    throw new DomainException('A returned quantity exceeds the quantity purchased.');
                }
                if ($quantity === 0) {
                    continue;
                }
                $unitPrice = (float) ($item->unit_price ?? $item->price);
                $requestedAmount += $unitPrice * $quantity;
                $selected[] = [$item, $quantity, $unitPrice];
            }

            if ($selected === []) {
                throw new DomainException('Select at least one product and quantity to return.');
            }

            $insert = $db->prepare("INSERT INTO return_requests
                (order_id, customer_id, seller_profile_id, seller_user_id, order_payment_method,
                 reason, reason_encrypted, status, requested_refund_amount, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?, NOW())");
            $insert->execute([
                $orderId, $customerId, $order->seller_profile_id, $order->seller_user_id,
                (string) ($order->payment_method ?? 'cash_on_delivery'), $reason,
                (new SensitiveDataCipher())->encrypt($reason, 'return_requests.reason'), $requestedAmount,
            ]);
            $returnId = (int) $db->lastInsertId();

            $itemInsert = $db->prepare('INSERT INTO return_request_items
                (return_request_id, order_item_id, product_id, product_name, variant_label, unit_price, requested_quantity)
                VALUES (?, ?, ?, ?, ?, ?, ?)');
            foreach ($selected as [$item, $quantity, $unitPrice]) {
                $itemInsert->execute([
                    $returnId, $item->id, $item->product_id, $item->product_name,
                    $item->variant_label ?? null, $unitPrice, $quantity,
                ]);
            }

            $this->history($db, $returnId, $customerId, 'customer', 'request_submitted', null, 'pending', $reason, $requestedAmount);
            $db->commit();
            $order->return_id = $returnId;
            $order->requested_refund_amount = $requestedAmount;
            return $order;
        } catch (Throwable $exception) {
            if ($db->inTransaction()) $db->rollBack();
            throw $exception;
        }
    }

    public function decide(int $returnId, int $sellerUserId, string $decision, string $reason, ?float $approvedAmount): object
    {
        (new OrderWorkflowSchemaService())->ensure();
        if (!in_array($decision, ['approved', 'rejected'], true)) {
            throw new DomainException('Choose approve or reject.');
        }
        $reason = $this->text($reason, 5, 1000, 'Please provide a reason for the decision.');
        $db = Database::connection();
        $db->beginTransaction();

        try {
            $stmt = $db->prepare('SELECT * FROM return_requests WHERE id = ? AND seller_user_id = ? LIMIT 1 FOR UPDATE');
            $stmt->execute([$returnId, $sellerUserId]);
            $return = $stmt->fetch();
            if (!$return) throw new DomainException('Return record not found.');
            if ((string) $return->status !== 'pending') throw new DomainException('This return request has already been decided.');

            $amount = null;
            if ($decision === 'approved') {
                $amount = $approvedAmount ?? (float) $return->requested_refund_amount;
                if ($amount < 0 || $amount > (float) $return->requested_refund_amount) {
                    throw new DomainException('The approved refund must be between zero and the requested amount.');
                }
                $db->prepare('UPDATE return_request_items SET approved_quantity = requested_quantity WHERE return_request_id = ?')
                    ->execute([$returnId]);
            }

            $db->prepare('UPDATE return_requests SET status = ?, approved_refund_amount = ?, decision_reason = ?, decision_reason_encrypted = ?, decided_at = NOW() WHERE id = ?')
                ->execute([$decision, $amount, $reason, (new SensitiveDataCipher())->encrypt($reason, 'return_requests.decision_reason'), $returnId]);
            $this->history($db, $returnId, $sellerUserId, 'farmer', $decision, 'pending', $decision, $reason, $amount);
            $db->commit();
            $return->status = $decision;
            $return->approved_refund_amount = $amount;
            $return->decision_reason = $reason;
            return $return;
        } catch (Throwable $exception) {
            if ($db->inTransaction()) $db->rollBack();
            throw $exception;
        }
    }

    public function resolve(int $returnId, int $sellerUserId, array $receivedQuantities, float $refundedAmount, string $refundMethod, string $reference, string $note): object
    {
        (new OrderWorkflowSchemaService())->ensure();
        $note = trim($note);
        $resolutionNote = $note !== '' ? mb_substr($note, 0, 1200) : null;
        if (!in_array($refundMethod, ['cash', 'bank_transfer', 'e_wallet', 'other', 'none'], true)) {
            throw new DomainException('Select a valid refund method.');
        }
        $db = Database::connection();
        $db->beginTransaction();

        try {
            $stmt = $db->prepare('SELECT * FROM return_requests WHERE id = ? AND seller_user_id = ? LIMIT 1 FOR UPDATE');
            $stmt->execute([$returnId, $sellerUserId]);
            $return = $stmt->fetch();
            if (!$return) throw new DomainException('Return record not found.');
            if ((string) $return->status !== 'approved') throw new DomainException('Only an approved return can be resolved.');
            if ($refundedAmount < 0 || $refundedAmount > (float) $return->approved_refund_amount) {
                throw new DomainException('The refunded amount cannot exceed the approved refund.');
            }
            if ($refundedAmount > 0 && $refundMethod === 'none') {
                throw new DomainException('Select how the refund was issued.');
            }

            $items = $db->prepare('SELECT * FROM return_request_items WHERE return_request_id = ? FOR UPDATE');
            $items->execute([$returnId]);
            $receivedTotalValue = 0.0;
            $receivedItems = [];
            foreach ($items->fetchAll() as $item) {
                $quantity = (int) ($receivedQuantities[(int) $item->id] ?? 0);
                if ($quantity < 0 || $quantity > (int) $item->approved_quantity) {
                    throw new DomainException('A received quantity exceeds the approved return quantity.');
                }
                $lineAmount = (float) $item->unit_price * $quantity;
                $receivedTotalValue += $lineAmount;
                $receivedItems[] = [$item, $quantity, $lineAmount];
            }

            if ($receivedTotalValue <= 0 && $refundedAmount > 0) {
                throw new DomainException('Record at least one received product before recording a refund.');
            }

            $allocatedRefund = 0.0;
            $lastIndex = count($receivedItems) - 1;
            foreach ($receivedItems as $index => [$item, $quantity, $lineAmount]) {
                $itemRefund = 0.0;
                if ($receivedTotalValue > 0 && $refundedAmount > 0) {
                    $itemRefund = $index === $lastIndex
                        ? round($refundedAmount - $allocatedRefund, 2)
                        : round($refundedAmount * ($lineAmount / $receivedTotalValue), 2);
                }
                $allocatedRefund += $itemRefund;
                $db->prepare('UPDATE return_request_items SET received_quantity = ?, refund_amount = ? WHERE id = ?')
                    ->execute([$quantity, $itemRefund, (int) $item->id]);
            }

            $reference = trim($reference);
            $db->prepare("UPDATE return_requests SET status = 'resolved', refunded_amount = ?, refund_method = ?,
                    refund_reference = ?, refund_reference_encrypted = ?, resolution_note = ?, resolution_note_encrypted = ?, resolved_at = NOW() WHERE id = ?")
                ->execute([
                    $refundedAmount,
                    $refundMethod,
                    $reference !== '' ? mb_substr($reference, 0, 255) : null,
                    (new SensitiveDataCipher())->encrypt($reference !== '' ? mb_substr($reference, 0, 255) : null, 'return_requests.refund_reference'),
                    $resolutionNote,
                    (new SensitiveDataCipher())->encrypt($resolutionNote, 'return_requests.resolution_note'),
                    $returnId,
                ]);
            $this->history($db, $returnId, $sellerUserId, 'farmer', 'resolved', 'approved', 'resolved', $resolutionNote ?? 'Return received and refund recorded.', $refundedAmount);
            $db->commit();
            $return->status = 'resolved';
            $return->refunded_amount = $refundedAmount;
            $return->refund_method = $refundMethod;
            $return->resolution_note = $resolutionNote;
            return $return;
        } catch (Throwable $exception) {
            if ($db->inTransaction()) $db->rollBack();
            throw $exception;
        }
    }

    private function history($db, int $returnId, int $actorId, string $actorRole, string $event, ?string $from, string $to, string $note, ?float $amount): void
    {
        $db->prepare('INSERT INTO return_resolution_history
            (return_request_id, actor_user_id, actor_role, event_type, from_status, to_status, note, note_encrypted, amount, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())')
            ->execute([$returnId, $actorId, $actorRole, $event, $from, $to, $note, (new SensitiveDataCipher())->encrypt($note, 'return_resolution_history.note'), $amount]);
    }

    private function text(string $value, int $minimum, int $maximum, string $emptyMessage): string
    {
        $value = trim($value);
        if ($value === '') throw new DomainException($emptyMessage);
        if (mb_strlen($value) < $minimum) throw new DomainException("Please enter at least {$minimum} characters.");
        if (mb_strlen($value) > $maximum) throw new DomainException("The text cannot exceed {$maximum} characters.");
        return $value;
    }
}
