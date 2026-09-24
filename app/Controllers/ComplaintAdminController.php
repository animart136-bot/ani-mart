<?php

namespace App\Controllers;

use App\Services\AuthGuard;
use App\Services\TrustCasePolicy;

use App\Core\Controller;
use App\Core\Session;
use App\Core\Database;
use App\Models\Complaint;
use App\Models\ComplaintEvidence;
use App\Models\ComplaintReply;
use App\Models\ComplaintAction;
use App\Models\SellerProfile;
use App\Services\AuditLogService;
use App\Services\CloudinaryService;
use App\Services\ComplaintService;
use App\Services\NotificationService;

class ComplaintAdminController extends Controller
{
    private const FINAL_STATUSES = ['resolved', 'rejected', 'closed'];

    public function index()
    {
        $this->requireAdmin();

        $filters = [
            'search' => trim($_GET['search'] ?? ''),
            'status' => trim($_GET['status'] ?? ''),
            'priority' => trim($_GET['priority'] ?? ''),
            'sort' => trim($_GET['sort'] ?? 'newest')
        ];

        $complaints = (new Complaint())->adminList($filters);

        return $this->view('complaints/admin/index', [
            'title' => 'Trust & Resolution Center',
            'bodyClass' => 'admin-complaints-page',
            'complaints' => $complaints,
            'filters' => $filters
        ]);
    }

    public function show()
    {
        $this->requireAdmin();

        $complaintId = (int) ($_GET['id'] ?? 0);
        $complaint = (new Complaint())->find($complaintId);

        if (!$complaint) {
            header('Location: /ani-mart/public/admin/complaints');
            exit;
        }

        $evidence = (new ComplaintEvidence())->findByComplaint($complaintId);
        $imageService = new CloudinaryService();
        foreach ($evidence as $item) {
            $asset = $imageService->findByUrl((string) $item->image_url);
            $item->asset_id = $asset->id ?? null;
        }
        $replies = (new ComplaintReply())->findByComplaint($complaintId);
        $actions = (new ComplaintAction())->findByComplaint($complaintId);

        return $this->view('complaints/admin/show', [
            'title' => "Dispute #{$complaint->id} Review",
            'bodyClass' => 'admin-complaint-show-page',
            'complaint' => $complaint,
            'evidence' => $evidence,
            'replies' => $replies,
            'actions' => $actions
        ]);
    }

    public function action()
    {
        csrf_verify();
        
        $this->requireAdmin();

        $complaintId = (int) ($_POST['complaint_id'] ?? 0);
        $action = trim($_POST['action'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        $allowedActions = [
            'approved',
            'rejected',
            'request_more_evidence',
            'warn_farmer',
            'suspend_farmer',
            'restore_trust',
            'close'
        ];

        if (!$complaintId || !in_array($action, $allowedActions, true)) {
            Session::flash('error', 'Invalid action.');
            header('Location: /ani-mart/public/admin/complaints');
            exit;
        }

        $db = Database::connection();
        $complaint = (new Complaint())->find($complaintId);

        if (!$complaint) {
            Session::flash('error', 'Dispute not found.');
            header('Location: /ani-mart/public/admin/complaints');
            exit;
        }

        if (in_array($complaint->status, self::FINAL_STATUSES, true)) {
            Session::flash('error', 'This complaint has already been finalized. Reopen it before recording another action.');
            header('Location: /ani-mart/public/admin/complaints/show?id=' . $complaintId);
            exit;
        }

        $admin = Session::get('user');
        $status = $complaint->status;

        (new Complaint())->assignAdmin($complaintId, $admin['id']);

        switch ($action) {
            case 'approved':
                $status = 'resolved';
                (new Complaint())->resolve($complaintId, $admin['id'], $status);
                ComplaintService::deductTrustForApprovedComplaint($complaint->seller_profile_id);
                NotificationService::send(
                    $complaint->seller_user_id,
                    'Dispute Approved',
                    "Dispute #{$complaint->id} has been approved and your trust score has been updated.",
                    'complaint',
                    'bi-exclamation-triangle',
                    'danger',
                    '/ani-mart/public/farmer/complaints/show?id=' . $complaintId,
                    'complaint',
                    $complaintId,
                    '/ani-mart/public/farmer/complaints/show?id=' . $complaintId
                );
                NotificationService::send(
                    $complaint->customer_id,
                    'Dispute Resolved',
                    "Dispute #{$complaint->id} has been approved. Thank you for working with us.",
                    'complaint',
                    'bi-exclamation-triangle',
                    'danger',
                    '/ani-mart/public/customer/complaints/show?id=' . $complaintId,
                    'complaint',
                    $complaintId,
                    '/ani-mart/public/customer/complaints/show?id=' . $complaintId
                );
                break;
            case 'rejected':
                $status = 'rejected';
                (new Complaint())->resolve($complaintId, $admin['id'], $status);
                NotificationService::send(
                    $complaint->customer_id,
                    'Dispute Rejected',
                    "Dispute #{$complaint->id} has been reviewed and rejected.",
                    'complaint',
                    'bi-x-circle',
                    'danger',
                    '/ani-mart/public/customer/complaints/show?id=' . $complaintId,
                    'complaint',
                    $complaintId,
                    '/ani-mart/public/customer/complaints/show?id=' . $complaintId
                );
                break;
            case 'request_more_evidence':
                $status = 'needs_more_evidence';
                (new Complaint())->updateStatus($complaintId, $status);
                NotificationService::send(
                    $complaint->customer_id,
                    'More Evidence Required',
                    "Please upload additional evidence for dispute #{$complaint->id}.",
                    'complaint',
                    'bi-exclamation-triangle',
                    'warning',
                    '/ani-mart/public/customer/complaints/show?id=' . $complaintId,
                    'complaint',
                    $complaintId,
                    '/ani-mart/public/customer/complaints/show?id=' . $complaintId
                );
                break;
            case 'warn_farmer':
                $status = 'under_review';
                (new Complaint())->updateStatus($complaintId, $status);
                NotificationService::send(
                    $complaint->seller_user_id,
                    'Dispute Warning Issued',
                    "A warning has been issued for dispute #{$complaint->id}. Please review the case and respond.",
                    'complaint',
                    'bi-exclamation-triangle',
                    'warning',
                    '/ani-mart/public/farmer/complaints/show?id=' . $complaintId,
                    'complaint',
                    $complaintId,
                    '/ani-mart/public/farmer/complaints/show?id=' . $complaintId
                );
                break;
            case 'suspend_farmer':
                $status = 'under_review';
                $stmt = $db->prepare("UPDATE seller_profiles SET is_suspended = 1, suspended_at = NOW() WHERE id = ?");
                $stmt->execute([$complaint->seller_profile_id]);
                (new Complaint())->updateStatus($complaintId, $status);
                NotificationService::send(
                    $complaint->seller_user_id,
                    'Farmer Suspension',
                    "The farmer has been suspended while dispute #{$complaint->id} is under review.",
                    'complaint',
                    'bi-exclamation-triangle',
                    'warning',
                    '/ani-mart/public/farmer/complaints/show?id=' . $complaintId,
                    'complaint',
                    $complaintId,
                    '/ani-mart/public/farmer/complaints/show?id=' . $complaintId
                );
                break;
            case 'restore_trust':
                $status = 'closed';
                (new Complaint())->resolve($complaintId, $admin['id'], $status);
                NotificationService::send(
                    $complaint->seller_user_id,
                    'Trust Restored',
                    "Dispute #{$complaint->id} has been closed and trust has been restored.",
                    'trust',
                    'bi-shield-check',
                    'success',
                    '/ani-mart/public/farmer/complaints/show?id=' . $complaintId,
                    'complaint',
                    $complaintId,
                    '/ani-mart/public/farmer/complaints/show?id=' . $complaintId
                );
                break;
            case 'close':
                $status = 'closed';
                (new Complaint())->resolve($complaintId, $admin['id'], $status);
                NotificationService::send(
                    $complaint->customer_id,
                    'Dispute Closed',
                    "Dispute #{$complaint->id} has been closed by the resolution team.",
                    'complaint',
                    'bi-check-circle',
                    'secondary',
                    '/ani-mart/public/customer/complaints/show?id=' . $complaintId,
                    'complaint',
                    $complaintId,
                    '/ani-mart/public/customer/complaints/show?id=' . $complaintId
                );
                NotificationService::send(
                    $complaint->seller_user_id,
                    'Dispute Closed',
                    "Dispute #{$complaint->id} has been closed by the resolution team.",
                    'complaint',
                    'bi-check-circle',
                    'secondary',
                    '/ani-mart/public/farmer/complaints/show?id=' . $complaintId,
                    'complaint',
                    $complaintId,
                    '/ani-mart/public/farmer/complaints/show?id=' . $complaintId
                );
                break;
        }

        if (in_array($status, self::FINAL_STATUSES, true)) {
            (new CloudinaryService())->scheduleEntityDeletion('complaint', $complaintId, TrustCasePolicy::retentionDays('complaint'));
        }

        (new ComplaintAction())->create($complaintId, $admin['id'], $action, mb_substr($notes, 0, 1000));

        AuditLogService::log(
            'complaint_admin_action',
            'complaint',
            (int) $complaintId,
            ['action' => $action, 'notes' => $notes],
            (int) $admin['id'],
            '/ani-mart/public/admin/complaints/action',
            'success'
        );

        Session::flash('success', 'Action recorded successfully.');
        header('Location: /ani-mart/public/admin/complaints/show?id=' . $complaintId);
        exit;
    }

    public function reopen()
    {
        csrf_verify();

        $this->requireAdmin();

        $complaintId = (int) ($_POST['complaint_id'] ?? 0);
        $complaint = $complaintId ? (new Complaint())->find($complaintId) : null;

        if (!$complaint) {
            Session::flash('error', 'Dispute not found.');
            header('Location: /ani-mart/public/admin/complaints');
            exit;
        }

        if (!in_array($complaint->status, self::FINAL_STATUSES, true)) {
            Session::flash('error', 'Only a finalized complaint can be reopened.');
            header('Location: /ani-mart/public/admin/complaints/show?id=' . $complaintId);
            exit;
        }

        $admin = Session::get('user');
        (new Complaint())->reopen($complaintId);
        (new CloudinaryService())->cancelEntityDeletion('complaint', $complaintId);
        (new Complaint())->assignAdmin($complaintId, $admin['id']);
        (new ComplaintAction())->create(
            $complaintId,
            $admin['id'],
            'reopened',
            'Complaint reopened for additional administrative review.'
        );

        NotificationService::send(
            $complaint->customer_id,
            'Dispute Reopened',
            "Dispute #{$complaint->id} has been reopened for additional review.",
            'complaint',
            'bi-arrow-counterclockwise',
            'info',
            '/ani-mart/public/customer/complaints/show?id=' . $complaintId,
            'complaint',
            $complaintId,
            '/ani-mart/public/customer/complaints/show?id=' . $complaintId
        );
        NotificationService::send(
            $complaint->seller_user_id,
            'Dispute Reopened',
            "Dispute #{$complaint->id} has been reopened for additional review.",
            'complaint',
            'bi-arrow-counterclockwise',
            'info',
            '/ani-mart/public/farmer/complaints/show?id=' . $complaintId,
            'complaint',
            $complaintId,
            '/ani-mart/public/farmer/complaints/show?id=' . $complaintId
        );

        AuditLogService::log(
            'complaint_reopened',
            'complaint',
            $complaintId,
            ['previous_status' => $complaint->status, 'new_status' => 'under_review'],
            (int) $admin['id'],
            '/ani-mart/public/admin/complaints/reopen',
            'success'
        );

        Session::flash('success', 'Complaint reopened for review.');
        header('Location: /ani-mart/public/admin/complaints/show?id=' . $complaintId);
        exit;
    }

    private function requireAdmin()
    {
        return AuthGuard::admin();
    }
}
