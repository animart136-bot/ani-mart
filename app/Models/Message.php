<?php

namespace App\Models;

use App\Core\Model;
use App\Services\SensitiveDataCipher;

class Message extends Model
{
    public function create($data)
    {
        $sql = "
            INSERT INTO messages (
                conversation_id,
                sender_id,
                message,
                message_encrypted,
                image,
                image_encrypted,
                is_read
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ";

        $cipher = new SensitiveDataCipher();
        $message = $data['message'] ?? null;
        $image = $data['image'] ?? null;

        $params = [
            $data['conversation_id'],
            $data['sender_id'],
            $message,
            $cipher->encrypt($message, 'messages.message'),
            $image,
            $cipher->encrypt($image, 'messages.image'),
            $data['is_read'] ?? 0,
        ];

        $this->query($sql, $params);

        return $this->db->lastInsertId();
    }

    public function findByConversation($conversationId)
    {
        $records = $this->fetchAll(
            "
            SELECT *
            FROM messages
            WHERE conversation_id = ?
            ORDER BY created_at ASC
            ",
            [$conversationId]
        );

        return array_map(fn($record) => $this->hydrateSensitive($record), $records);
    }

    public function update($messageId, $senderId, $message)
    {
        return $this->query(
            "
            UPDATE messages
            SET message = ?, message_encrypted = ?, updated_at = NOW()
            WHERE id = ?
                AND sender_id = ?
            ",
            [$message, (new SensitiveDataCipher())->encrypt($message, 'messages.message'), $messageId, $senderId]
        );
    }

    public function markReadForConversation($conversationId, $readerId)
    {
        return $this->query(
            "
            UPDATE messages
            SET is_read = 1
            WHERE conversation_id = ?
                AND sender_id != ?
                AND is_read = 0
            ",
            [$conversationId, $readerId]
        );
    }

    public function countUnreadForConversation($conversationId, $participantId)
    {
        $result = $this->fetchOne(
            "
            SELECT COUNT(*) AS total
            FROM messages
            WHERE conversation_id = ?
                AND sender_id != ?
                AND is_read = 0
            ",
            [$conversationId, $participantId]
        );

        return $result->total ?? 0;
    }

    public function unreadCountForUser($userId)
    {
        $result = $this->fetchOne(
            "
            SELECT COUNT(*) AS total
            FROM messages m
            INNER JOIN conversations c ON c.id = m.conversation_id
            WHERE m.sender_id != ?
                AND m.is_read = 0
                AND c.is_archived = 0
                AND (
                    c.customer_id = ?
                    OR c.farmer_id = ?
                )
                AND NOT EXISTS (
                    SELECT 1
                    FROM conversations newer
                    WHERE newer.customer_id = c.customer_id
                        AND newer.farmer_id = c.farmer_id
                        AND newer.is_archived = c.is_archived
                        AND (
                            COALESCE(newer.last_message_at, newer.created_at) > COALESCE(c.last_message_at, c.created_at)
                            OR (
                                COALESCE(newer.last_message_at, newer.created_at) = COALESCE(c.last_message_at, c.created_at)
                                AND newer.id > c.id
                            )
                        )
                )
            ",
            [$userId, $userId, $userId]
        );

        return $result->total ?? 0;
    }

    public function findById($id)
    {
        return $this->hydrateSensitive($this->fetchOne(
            "
            SELECT *
            FROM messages
            WHERE id = ?
            LIMIT 1
            ",
            [$id]
        ));
    }

    private function hydrateSensitive($record)
    {
        return hydrate_sensitive_fields($record, 'messages', ['message', 'image']);
    }
}
