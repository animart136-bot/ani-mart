<?php

namespace App\Models;

use App\Core\Model;
use App\Services\SensitiveDataCipher;

class ReportAction extends Model
{
    public function create($reportId, $adminId, $action, $notes = null)
    {
        $this->query(
            "INSERT INTO report_actions (report_id, admin_id, action, notes, notes_encrypted, created_at) VALUES (?, ?, ?, ?, ?, NOW())",
            [$reportId, $adminId, $action, $notes, (new SensitiveDataCipher())->encrypt($notes, 'report_actions.notes')]
        );
    }

    public function findByReport($reportId)
    {
        $records = $this->fetchAll(
            "SELECT ra.*, u.first_name AS admin_first_name, u.last_name AS admin_last_name
            FROM report_actions ra
            LEFT JOIN users u ON u.id = ra.admin_id
            WHERE ra.report_id = ?
            ORDER BY ra.created_at DESC",
            [$reportId]
        );
        return array_map(fn($record) => hydrate_sensitive_fields($record, 'report_actions', ['notes']), $records);
    }
}
