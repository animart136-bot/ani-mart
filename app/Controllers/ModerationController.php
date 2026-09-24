<?php

namespace App\Controllers;

use App\Services\AuthGuard;
use App\Services\TrustCasePolicy;

use App\Core\Controller;
use App\Core\Session;
use App\Services\AuditLogService;
use App\Services\NotificationService;
use App\Services\CloudinaryService;
use App\Services\TrustScoreService;
use App\Core\Database;
use App\Models\Appeal;
use App\Models\Report;
use App\Models\ReportAction;
use App\Models\ReportEvidence;

class ModerationController extends Controller
{
    public function index()
    {
        $this->requireAdmin();

        $filters = [
            'search' => trim($_GET['search'] ?? ''),
            'status' => trim($_GET['status'] ?? ''),
            'priority' => trim($_GET['priority'] ?? ''),
            'target_type' => trim($_GET['target_type'] ?? ''),
            'sort' => trim($_GET['sort'] ?? 'newest')
        ];

        $reports = (new Report())->adminList($filters);

        return $this->view('trust-center/reports/index', [
            'title' => 'Moderation Reports',
            'bodyClass' => 'trust-safety-reports',
            'reports' => $reports,
            'filters' => $filters
        ]);
    }

    public function show()
    {
        $this->requireAdmin();

        $reportId = (int) ($_GET['id'] ?? 0);
        $report = (new Report())->find($reportId);

        if (!$report) {
            $this->redirect('/ani-mart/public/admin/trust-center/reports');
        }

        $evidence = (new ReportEvidence())->findByReport($reportId);
        $imageService = new CloudinaryService();
        foreach ($evidence as $item) {
            $asset = !empty($item->public_id)
                ? $imageService->findByPublicId((string) $item->public_id)
                : $imageService->findByUrl((string) $item->image_url);
            $item->asset_id = $asset->id ?? null;
        }
        $actions = (new ReportAction())->findByReport($reportId);
        $appeals = (new Appeal())->forReport($reportId);

        return $this->view('trust-center/reports/show', [
            'title' => "Report #{$report->id}",
            'bodyClass' => 'trust-safety-report-show',
            'report' => $report,
            'evidence' => $evidence,
            'actions' => $actions,
            'appeals' => $appeals
        ]);
    }

    public function action()
    {

        csrf_verify();
        
        $this->requireAdmin();

        $reportId = (int) ($_POST['report_id'] ?? 0);
        $admin = Session::get('user');
        $action = trim($_POST['action'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        $allowed = [
            'request_more_info',
            'warn_user',
            'delete_product',
            'delete_comment',
            'delete_post',
            'mute_user',
            'suspend_farmer',
            'suspend_customer',
            'temporary_ban',
            'permanent_ban',
            'reject_report',
            'resolve_report'
        ];

        if (!$reportId || !in_array($action, $allowed, true)) {
            Session::flash('error', 'Invalid moderation action.');
            $this->redirect('/ani-mart/public/admin/trust-center/reports');
        }

        $reportModel = new Report();
        $report = $reportModel->find($reportId);

        if (!$report) {
            Session::flash('error', 'Report not found.');
            $this->redirect('/ani-mart/public/admin/trust-center/reports');
        }

        $reportModel->assignAdmin($reportId, $admin['id']);
        $reportsUrl = '/ani-mart/public/reports-complaints#reports';
        $appealsUrl = '/ani-mart/public/reports-complaints#appeals';

        switch ($action) {
            case 'request_more_info':
                $reportModel->updateStatus($reportId, 'need_more_info');
                NotificationService::send(
                    $report->reporter_id,
                    'More Information Required',
                    "Your report #{$report->id} needs additional evidence.",
                    'trust',
                    'bi-info-circle',
                    'warning',
                    $reportsUrl,
                    'report',
                    $reportId,
                    $reportsUrl
                );
                break;
            case 'warn_user':
                $reportModel->updateStatus($reportId, 'investigation');
                NotificationService::send(
                    $report->reporter_id,
                    'Report Investigation',
                    "Your report #{$report->id} is under investigation.",
                    'trust',
                    'bi-shield-exclamation',
                    'primary',
                    $reportsUrl,
                    'report',
                    $reportId,
                    $reportsUrl
                );
                break;
            case 'reject_report':
                $reportModel->resolve($reportId, $admin['id'], 'rejected');
                NotificationService::send(
                    $report->reporter_id,
                    'Report Rejected',
                    "Report #{$report->id} has been rejected.",
                    'trust',
                    'bi-x-circle',
                    'danger',
                    $appealsUrl,
                    'report',
                    $reportId,
                    $appealsUrl
                );
                break;
            case 'resolve_report':
                $reportModel->resolve($reportId, $admin['id'], 'resolved');
                $this->deductTrustForValidatedSellerReport($report);
                NotificationService::send(
                    $report->reporter_id,
                    'Report Resolved',
                    "Report #{$report->id} has been resolved.",
                    'trust',
                    'bi-check-circle',
                    'success',
                    $reportsUrl,
                    'report',
                    $reportId,
                    $reportsUrl
                );
                break;
            default:
                $reportModel->query("UPDATE reports SET updated_at = NOW() WHERE id = ?", [$reportId]);
                break;
        }

        if (in_array($action, ['reject_report', 'resolve_report'], true)) {
            (new CloudinaryService())->scheduleEntityDeletion('report', $reportId, TrustCasePolicy::retentionDays('report'));
        }

        (new ReportAction())->create($reportId, $admin['id'], $action, mb_substr($notes, 0, 1000));

        AuditLogService::log(
            'moderation_admin_action',
            'report',
            (int) $reportId,
            ['action' => $action, 'notes' => $notes],
            (int) $admin['id'],
            '/ani-mart/public/admin/trust-center/reports/action',
            'success'
        );

        Session::flash('success', 'Moderation action recorded.');
        $this->redirect('/ani-mart/public/admin/trust-center/reports/show?id=' . $reportId);
    }

    private function requireAdmin()
    {
        return AuthGuard::admin();
    }

    private function deductTrustForValidatedSellerReport(object $report): void
    {
        $sellerProfileId = 0;
        $db = Database::connection();

        if (($report->target_type ?? '') === 'seller') {
            $sellerProfileId = (int) ($report->target_id ?? 0);
        } elseif (($report->target_type ?? '') === 'product') {
            $seller = $db->prepare('SELECT seller_profile_id FROM products WHERE id = ? LIMIT 1');
            $seller->execute([(int) ($report->target_id ?? 0)]);
            $sellerProfileId = (int) ($seller->fetch()->seller_profile_id ?? 0);
        }

        (new TrustScoreService())->deductForValidatedReport($sellerProfileId, (int) $report->id);
    }
}
