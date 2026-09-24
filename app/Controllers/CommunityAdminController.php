<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Models\CommunityReport;
use App\Models\CommunityPost;

class CommunityAdminController extends Controller
{
    public function reports()
    {
        $user = Session::get('user');

        if (!$user || $user['role'] !== 'admin') {
            $this->redirect('/ani-mart/public/dashboard');
        }

        $reports = (new CommunityReport())->allPending();

        $this->view('community/reports', [
            'title' => 'Community Reports',
            'reports' => $reports
        ]);
    }

    public function moderate()
    {
        csrf_verify();

        $user = Session::get('user');
        $reportId = $_POST['report_id'] ?? 0;
        $action = $_POST['action'] ?? null;

        if (!$user || $user['role'] !== 'admin' || !$reportId || !in_array($action, ['reviewed', 'dismissed'], true)) {
            $this->redirect('/ani-mart/public/admin/analytics');
        }

        $report = new CommunityReport();

        if ($action === 'reviewed') {
            $report->markReviewed($reportId);
        } else {
            $report->markDismissed($reportId);
        }

        $this->redirect('/ani-mart/public/community/reports');
    }
}
