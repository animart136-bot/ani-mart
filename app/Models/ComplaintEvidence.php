<?php

namespace App\Models;

use App\Core\Model;

class ComplaintEvidence extends Model
{
    public function create($complaintId, $userId, $uploaderRole, $imageUrl)
    {
        return $this->query(
            "
            INSERT INTO complaint_evidence (
                complaint_id,
                user_id,
                uploader_role,
                image_url
            ) VALUES (?, ?, ?, ?)
            ",
            [$complaintId, $userId, $uploaderRole, $imageUrl]
        );
    }

    public function findByComplaint($complaintId)
    {
        return $this->fetchAll(
            "
            SELECT *
            FROM complaint_evidence
            WHERE complaint_id = ?
            ORDER BY created_at ASC
            ",
            [$complaintId]
        );
    }
}
