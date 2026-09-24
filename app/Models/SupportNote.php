<?php

namespace App\Models;

use App\Core\Model;
use App\Services\SensitiveDataCipher;

class SupportNote extends Model
{
    public function create($data)
    {
        $sql = "INSERT INTO support_notes (conversation_id, admin_id, note, note_encrypted) VALUES (?, ?, ?, ?)";
        $params = [
            $data['conversation_id'],
            $data['admin_id'],
            $data['note'],
            (new SensitiveDataCipher())->encrypt($data['note'], 'support_notes.note'),
        ];

        $this->query($sql, $params);

        return $this->db->lastInsertId();
    }

    public function findByConversation($conversationId)
    {
        $records = $this->fetchAll(
            "SELECT sn.*, u.first_name, u.last_name
            FROM support_notes sn
            LEFT JOIN users u ON u.id = sn.admin_id
            WHERE sn.conversation_id = ?
            ORDER BY sn.created_at DESC",
            [$conversationId]
        );
        return array_map(fn($record) => hydrate_sensitive_fields($record, 'support_notes', ['note']), $records);
    }
}
