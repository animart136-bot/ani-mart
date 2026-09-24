<?php

namespace App\Services;

use App\Models\Complaint;
use App\Models\ComplaintAction;
use App\Models\SellerProfile;

class ComplaintService
{
    public static function deductTrustForApprovedComplaint($sellerProfileId, $complaintCount = 1)
    {
        $amount = 5 + max(0, ($complaintCount - 1) * 2);
        return (new Complaint())->deductTrust($sellerProfileId, $amount);
    }

    public static function ensureTrustNonNegative($sellerProfileId)
    {
        return (new Complaint())->deductTrust($sellerProfileId, 0);
    }

    public static function createAdminAction($complaintId, $adminId, $action, $notes = null)
    {
        return (new ComplaintAction())->create($complaintId, $adminId, $action, $notes);
    }
}
