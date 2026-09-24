<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Services\AdminAnalyticsService;
use App\Services\AuthGuard;
use App\Models\Report;

class TrustSafetyController extends Controller
{
    public function dashboard()
    {
        $this->requireAdmin();

        $analytics = (new AdminAnalyticsService())->dashboard();
        $reportModel = new Report();
        $summary = $reportModel->reportCounts();

        return $this->view('trust-center/dashboard', [
            'title' => 'Trust & Safety Center',
            'bodyClass' => 'trust-safety-dashboard',
            'analytics' => $analytics,
            'reportSummary' => $summary
        ]);
    }

    private function requireAdmin()
    {
        return AuthGuard::admin();
    }
}
