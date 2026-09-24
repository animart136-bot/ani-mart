<?php

namespace App\Models;

use App\Core\Model;
use App\Services\SensitiveDataCipher;

class ComplaintReply extends Model
{
    public function create($complaintId, $userId, $message)
    {
        return $this->query(
            "
            INSERT INTO complaint_replies (
                complaint_id,
                user_id,
                message,
                message_encrypted
            ) VALUES (?, ?, ?, ?)
            ",
            [$complaintId, $userId, $message, (new SensitiveDataCipher())->encrypt($message, 'complaint_replies.message')]
        );
    }

    public function findByComplaint($complaintId)
    {
        $records = $this->fetchAll(
            "
            SELECT cr.*, u.first_name, u.last_name, u.role
            FROM complaint_replies cr
            INNER JOIN users u ON u.id = cr.user_id
            WHERE cr.complaint_id = ?
            ORDER BY cr.created_at ASC
            ",
            [$complaintId]
        );
        return array_map(fn($record) => hydrate_sensitive_fields($record, 'complaint_replies', ['message']), $records);
    }
}
