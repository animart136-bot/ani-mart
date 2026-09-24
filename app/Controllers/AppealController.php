<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Services\NotificationService;
use App\Services\AuthGuard;
use App\Models\Appeal;
use App\Models\Report;
use App\Models\ReportAction;

class AppealController extends Controller
{
    public function index()
    {
        $user = Session::get('user');

        if (!$user) {
            $this->redirect('/ani-mart/public/login');
        }

        $appeals = (new Appeal())->byUser($user['id']);
        $appealedReportIds = array_flip(array_map(static fn ($appeal) => (int) $appeal->report_id, $appeals));
        $eligibleReports = array_values(array_filter(
            (new Report())->forReporter((int) $user['id']),
            static fn ($report) => (string) $report->status === 'rejected' && !isset($appealedReportIds[(int) $report->id])
        ));

        return $this->view('trust-center/appeals/index', [
            'title' => 'My Appeals',
            'bodyClass' => 'trust-safety-appeals',
            'appeals' => $appeals,
            'eligibleReports' => $eligibleReports
        ]);
    }

    public function adminIndex()
    {
        $this->requireAdmin();

        $appeals = (new Appeal())->fetchAll(
            "SELECT a.*, r.reason AS report_reason, r.status AS report_status, r.reporter_id
             FROM appeals a
             LEFT JOIN reports r ON r.id = a.report_id
             ORDER BY a.created_at DESC"
        );

        return $this->view('trust-center/appeals/admin-index', [
            'title' => 'Appeals Review',
            'bodyClass' => 'trust-safety-appeals-admin',
            'appeals' => $appeals
        ]);
    }

    public function store()
    {
        csrf_verify();

        $user = Session::get('user');

        if (!$user) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $reportId = (int) ($_POST['report_id'] ?? 0);
        $reason = trim($_POST['reason'] ?? '');

        if (!$reportId || $reason === '') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Please provide a reason for your appeal.']);
            exit;
        }

        $report = (new Report())->find($reportId);

        if (!$report) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Report not found.']);
            exit;
        }

        if ((int) $report->reporter_id !== (int) $user['id'] || (string) $report->status !== 'rejected') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Only your rejected reports can be appealed.']);
            exit;
        }

        $appealModel = new Appeal();
        if ($appealModel->existsForUserAndReport((int) $user['id'], $reportId)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'An appeal has already been submitted for this report.']);
            exit;
        }
        $appealId = $appealModel->create([
            'report_id' => $reportId,
            'user_id' => $user['id'],
            'reason' => mb_substr($reason, 0, 1200)
        ]);

        NotificationService::send(
            1,
            'New Appeal Submitted',
            sprintf('%s submitted an appeal for report #%d.', $user['first_name'] ?? 'A user', $reportId),
            'trust',
            'bi-gavel',
            'info',
            '/ani-mart/public/admin/trust-center/appeals',
            'appeal',
            $appealId,
            '/ani-mart/public/admin/trust-center/appeals'
        );

        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }

    public function adminUpdate()
    {
        csrf_verify();
        
        $this->requireAdmin();

        $appealId = (int) ($_POST['appeal_id'] ?? 0);
        $status = trim($_POST['status'] ?? '');

        if (!$appealId || !in_array($status, ['accepted', 'rejected'], true)) {
            Session::flash('error', 'Invalid appeal action.');
            $this->redirect('/ani-mart/public/admin/trust-center/appeals');
        }

        $appeal = (new Appeal())->find($appealId);

        if (!$appeal) {
            Session::flash('error', 'Appeal not found.');
            $this->redirect('/ani-mart/public/admin/trust-center/appeals');
        }

        (new Appeal())->updateStatus($appealId, $status);
        (new ReportAction())->create($appeal->report_id, Session::get('user')['id'], $status === 'accepted' ? 'appeal_accepted' : 'appeal_rejected', null);

        NotificationService::send(
            $appeal->user_id,
            $status === 'accepted' ? 'Appeal Accepted' : 'Appeal Rejected',
            "Your appeal for report #{$appeal->report_id} has been {$status}.",
            'trust',
            $status === 'accepted' ? 'bi-check-circle' : 'bi-x-circle',
            $status === 'accepted' ? 'success' : 'danger',
            '/ani-mart/public/trust-center/appeals',
            'appeal',
            $appealId,
            '/ani-mart/public/trust-center/appeals'
        );

        Session::flash('success', 'Appeal status updated.');
        $this->redirect('/ani-mart/public/admin/trust-center/appeals');
    }

    private function requireAdmin()
    {
        return AuthGuard::admin();
    }
}
