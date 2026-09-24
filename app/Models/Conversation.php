<?php

namespace App\Models;

use App\Core\Model;
use App\Services\SensitiveDataCipher;

class Conversation extends Model
{
    public function findById($id)
    {
        return $this->hydrateSensitive($this->fetchOne(
            "
            SELECT *
            FROM conversations
            WHERE id = ?
            LIMIT 1
            ",
            [$id]
        ));
    }

    public function findExisting($customerId, $farmerId, $productId = null, $orderId = null)
    {
        return $this->hydrateSensitive($this->fetchOne(
            "SELECT *
             FROM conversations
             WHERE (
                    (customer_id = ? AND farmer_id = ?)
                    OR (customer_id = ? AND farmer_id = ?)
                 )
                 AND is_archived = 0
             ORDER BY COALESCE(last_message_at, created_at) DESC, id DESC
             LIMIT 1",
            [$customerId, $farmerId, $farmerId, $customerId]
        ));
    }

    public function create($data)
    {
        $sql = "
            INSERT INTO conversations (
                customer_id,
                farmer_id,
                product_id,
                order_id,
                last_message,
                last_message_encrypted,
                last_message_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ";

        $message = $data['last_message'] ?? null;

        $params = [
            $data['customer_id'],
            $data['farmer_id'],
            $data['product_id'] ?? null,
            $data['order_id'] ?? null,
            $message,
            (new SensitiveDataCipher())->encrypt($message, 'conversations.last_message'),
            $data['last_message_at'] ?? null,
        ];

        $this->query($sql, $params);

        return $this->db->lastInsertId();
    }

    public function updateLastMessage($conversationId, $message)
    {
        return $this->query(
            "
            UPDATE conversations
            SET last_message = ?, last_message_encrypted = ?, last_message_at = NOW(), updated_at = NOW()
            WHERE id = ?
            ",
            [$message, (new SensitiveDataCipher())->encrypt($message, 'conversations.last_message'), $conversationId]
        );
    }

    public function deleteForParticipant($conversationId, $participantId)
    {
        $ownsTransaction = !$this->db->inTransaction();

        if ($ownsTransaction) {
            $this->db->beginTransaction();
        }

        try {
            $conversation = $this->fetchOne(
                "SELECT id
                 FROM conversations
                 WHERE id = ?
                   AND (customer_id = ? OR farmer_id = ?)
                 FOR UPDATE",
                [$conversationId, $participantId, $participantId]
            );

            if (!$conversation) {
                if ($ownsTransaction) {
                    $this->db->rollBack();
                }
                return false;
            }

            $this->query('DELETE FROM message_requests WHERE conversation_id = ?', [$conversationId]);
            $this->query('DELETE FROM conversation_settings WHERE conversation_id = ?', [$conversationId]);
            $this->query('DELETE FROM messages WHERE conversation_id = ?', [$conversationId]);
            $statement = $this->query(
                'DELETE FROM conversations WHERE id = ? AND (customer_id = ? OR farmer_id = ?)',
                [$conversationId, $participantId, $participantId]
            );

            if ($ownsTransaction) {
                $this->db->commit();
            }

            return $statement->rowCount() === 1;
        } catch (\Throwable $e) {
            if ($ownsTransaction && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function updateContext($conversationId, $productId = null, $orderId = null)
    {
        return $this->query(
            "UPDATE conversations
             SET product_id = ?, order_id = ?, updated_at = NOW()
             WHERE id = ?",
            [$productId ?: null, $orderId ?: null, $conversationId]
        );
    }

    public function markArchived($conversationId, $value = 1)
    {
        return $this->query(
            "
            UPDATE conversations
            SET is_archived = ?, updated_at = NOW()
            WHERE id = ?
            ",
            [$value ? 1 : 0, $conversationId]
        );
    }

    public function markResolved($conversationId)
    {
        return $this->query(
            "
            UPDATE conversations
            SET is_resolved = 1, updated_at = NOW()
            WHERE id = ?
            ",
            [$conversationId]
        );
    }

    public function listForUser($userId, $role, $search = null, $archived = false)
    {
        $archivedCondition = $archived ? 'AND c.is_archived = 1' : 'AND c.is_archived = 0';

        $sql = "
            SELECT
                c.*,
                TRIM(CONCAT(cu.first_name, ' ', cu.last_name)) AS customer_name,
                TRIM(CONCAT(fu.first_name, ' ', fu.last_name)) AS farmer_name,
                cu.role AS customer_role,
                fu.role AS farmer_role,
                cu.profile_image AS customer_image,
                fu.profile_image AS farmer_image,
                p.name AS product_name,
                o.id AS order_id,
                o.order_number AS order_number,
                o.status AS order_status,
                IFNULL(
                    (
                        SELECT COUNT(*)
                        FROM messages m
                        WHERE m.conversation_id = c.id
                            AND m.sender_id != ?
                            AND m.is_read = 0
                    ), 0
                ) AS unread_count
            FROM conversations c
            INNER JOIN users cu ON cu.id = c.customer_id
            INNER JOIN users fu ON fu.id = c.farmer_id
            LEFT JOIN products p ON p.id = c.product_id
            LEFT JOIN orders o ON o.id = c.order_id
            WHERE (c.customer_id = ? OR c.farmer_id = ?)
            {$archivedCondition}
            AND EXISTS (
                SELECT 1
                FROM messages started_message
                WHERE started_message.conversation_id = c.id
            )
            AND NOT EXISTS (
                SELECT 1
                FROM conversations newer
                WHERE (
                    (newer.customer_id = c.customer_id AND newer.farmer_id = c.farmer_id)
                    OR (newer.customer_id = c.farmer_id AND newer.farmer_id = c.customer_id)
                )
                    AND newer.is_archived = c.is_archived
                    AND EXISTS (
                        SELECT 1
                        FROM messages newer_started_message
                        WHERE newer_started_message.conversation_id = newer.id
                    )
                    AND (
                        COALESCE(newer.last_message_at, newer.created_at) > COALESCE(c.last_message_at, c.created_at)
                        OR (
                            COALESCE(newer.last_message_at, newer.created_at) = COALESCE(c.last_message_at, c.created_at)
                            AND newer.id > c.id
                        )
                    )
            )
        ";
        $params = [$userId, $userId, $userId];

        if ($search) {
            $searchTerm = '%' . $search . '%';
            $sql .= " AND (
                COALESCE(p.name, '') LIKE ? OR
                COALESCE(CAST(o.id AS CHAR), '') LIKE ? OR
                COALESCE(o.order_number, '') LIKE ? OR
                COALESCE(TRIM(CONCAT(cu.first_name, ' ', cu.last_name)), '') LIKE ? OR
                COALESCE(TRIM(CONCAT(fu.first_name, ' ', fu.last_name)), '') LIKE ? OR
                COALESCE(c.last_message, '') LIKE ?
            )";
            $params = array_merge($params, array_fill(0, 6, $searchTerm));
        }

        $sql .= " ORDER BY c.last_message_at DESC, c.created_at DESC";

        $records = $this->fetchAll($sql, $params);
        return array_map(function ($record) use ($userId) {
            $isFirstParticipant = (int) ($record->customer_id ?? 0) === (int) $userId;
            $record->partner_name = $isFirstParticipant ? $record->farmer_name : $record->customer_name;
            $record->partner_role = $isFirstParticipant ? $record->farmer_role : $record->customer_role;
            $record->partner_image = $isFirstParticipant ? $record->farmer_image : $record->customer_image;
            return $this->hydrateSensitive($record);
        }, $records);
    }

    public function conversationForParticipants($customerId, $farmerId, $orderId = null)
    {
        return $this->hydrateSensitive($this->fetchOne(
            "
            SELECT * FROM conversations
            WHERE customer_id = ?
                AND farmer_id = ?
                AND order_id = ?
            LIMIT 1
            ",
            [$customerId, $farmerId, $orderId]
        ));
    }

    private function hydrateSensitive($record)
    {
        return hydrate_sensitive_fields($record, 'conversations', ['last_message']);
    }
}
