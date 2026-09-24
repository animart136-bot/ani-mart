<?php

namespace App\Models;

use App\Core\Model;
use App\Services\SensitiveDataCipher;

class ComplaintAction extends Model
{
    public function create($complaintId, $adminId, $action, $notes = null)
    {
        return $this->query(
            "
            INSERT INTO complaint_actions (
                complaint_id,
                admin_id,
                action,
                notes,
                notes_encrypted
            ) VALUES (?, ?, ?, ?, ?)
            ",
            [$complaintId, $adminId, $action, $notes, (new SensitiveDataCipher())->encrypt($notes, 'complaint_actions.notes')]
        );
    }

    public function findByComplaint($complaintId)
    {
        $records = $this->fetchAll(
            "
            SELECT ca.*, u.first_name, u.last_name
            FROM complaint_actions ca
            INNER JOIN users u ON u.id = ca.admin_id
            WHERE ca.complaint_id = ?
            ORDER BY ca.created_at ASC
            ",
            [$complaintId]
        );
        return array_map(fn($record) => hydrate_sensitive_fields($record, 'complaint_actions', ['notes']), $records);
    }
}
