<?php

namespace App\Models;

use App\Core\Model;
use App\Services\SensitiveDataCipher;

class SupportMessage extends Model
{
    public function create($data)
    {
        $sql = "INSERT INTO support_messages (
            conversation_id,
            sender_id,
            sender_role,
            message_type,
            message,
            message_encrypted,
            attachment_url,
            attachment_url_encrypted,
            is_read
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $cipher = new SensitiveDataCipher();
        $message = $data['message'] ?? null;
        $attachment = $data['attachment_url'] ?? null;

        $params = [
            $data['conversation_id'],
            $data['sender_id'],
            $data['sender_role'],
            $data['message_type'],
            $message,
            $cipher->encrypt($message, 'support_messages.message'),
            $attachment,
            $cipher->encrypt($attachment, 'support_messages.attachment_url'),
            $data['is_read'] ?? 0,
        ];

        $this->query($sql, $params);

        return $this->db->lastInsertId();
    }

    public function findByConversation($conversationId)
    {
        $records = $this->fetchAll(
            "SELECT sm.*, u.first_name, u.last_name, u.role
            FROM support_messages sm
            LEFT JOIN users u ON u.id = sm.sender_id
            WHERE sm.conversation_id = ?
            ORDER BY sm.created_at ASC",
            [$conversationId]
        );
        return array_map(fn($record) => hydrate_sensitive_fields($record, 'support_messages', ['message', 'attachment_url']), $records);
    }

    public function markReadForConversation($conversationId)
    {
        return $this->query(
            "UPDATE support_messages SET is_read = 1 WHERE conversation_id = ?",
            [$conversationId]
        );
    }
}
