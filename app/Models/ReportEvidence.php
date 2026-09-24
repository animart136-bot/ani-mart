<?php

namespace App\Models;

use App\Core\Model;

class ReportEvidence extends Model
{
    public function create($reportId, $imageUrl, $publicId = null)
    {
        $this->query(
            "INSERT INTO report_evidence (report_id, image_url, public_id, created_at) VALUES (?, ?, ?, NOW())",
            [$reportId, $imageUrl, $publicId]
        );
    }

    public function findByReport($reportId)
    {
        return $this->fetchAll(
            "SELECT * FROM report_evidence WHERE report_id = ? ORDER BY created_at DESC",
            [$reportId]
        );
    }
}
