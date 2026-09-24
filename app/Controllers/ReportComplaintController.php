<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Models\Appeal;
use App\Models\Complaint;
use App\Models\Report;

class ReportComplaintController extends Controller
{
    public function index()
    {
        $user = Session::get('user');

        if (!$user) {
            return $this->redirect('/ani-mart/public/login');
        }

        $role = strtolower((string) ($user['role'] ?? ''));

        if ($role === 'admin') {
            return $this->redirect('/ani-mart/public/admin/trust-center');
        }

        $reports = (new Report())->forReporter((int) $user['id']);
        $appeals = (new Appeal())->byUser((int) $user['id']);
        $appealedReportIds = array_flip(array_map(static fn ($appeal) => (int) $appeal->report_id, $appeals));
        $eligibleAppealReports = array_values(array_filter($reports, static fn ($report) =>
            (string) $report->status === 'rejected' && !isset($appealedReportIds[(int) $report->id])
        ));
        $complaintModel = new Complaint();
        $disputes = $role === 'farmer'
            ? $complaintModel->forSellerUser((int) $user['id'])
            : $complaintModel->forCustomer((int) $user['id']);

        $openReportStatuses = ['submitted', 'pending_review', 'assigned', 'investigation', 'need_more_info'];
        $openDisputeStatuses = ['pending', 'under_review', 'waiting_farmer_response', 'needs_more_evidence'];

        return $this->view('trust-center/reports-complaints', [
            'title' => 'Resolution Center',
            'bodyClass' => 'reports-complaints-page',
            'role' => $role,
            'reports' => $reports,
            'disputes' => $disputes,
            'appeals' => $appeals,
            'eligibleAppealReports' => $eligibleAppealReports,
            'openReports' => count(array_filter($reports, static fn ($report) => in_array((string) $report->status, $openReportStatuses, true))),
            'openDisputes' => count(array_filter($disputes, static fn ($dispute) => in_array((string) $dispute->status, $openDisputeStatuses, true))),
            'pendingAppeals' => count(array_filter($appeals, static fn ($appeal) => (string) $appeal->status === 'pending')),
        ]);
    }
}
