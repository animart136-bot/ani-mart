<?php

$title = $title ?? 'Trust & Safety Center';
$bodyClass = $bodyClass ?? 'trust-safety-dashboard';

ob_start();
?>

<main class="container app-page-spacing admin-trust-center-page">
    <div class="admin-hero mb-5">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <span class="hero-badge">Marketplace Protection</span>
                <h1 class="hero-title mt-4">Trust & Safety Center</h1>
                <p class="hero-subtitle mt-3">Centralized moderation, analytics, reports, and appeals for Ani-Mart.</p>
                <a href="/ani-mart/public/dashboard" class="admin-dashboard-back-link"><i class="bi bi-arrow-left"
                        aria-hidden="true"></i> Admin dashboard</a>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <?php $counts = $reportSummary ?? (object) ['submitted' => 0, 'pending_review' => 0, 'assigned' => 0, 'investigation' => 0, 'need_more_info' => 0, 'resolved' => 0, 'rejected' => 0]; ?>
        <div class="col-sm-6 col-xl-3">
            <div class="card rounded-4 shadow-sm p-4 h-100">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div>
                        <h2 class="h4 mb-1"><?= (int) $counts->submitted ?></h2>
                        <p class="text-muted mb-0">Submitted Reports</p>
                    </div>
                    <span class="badge bg-secondary">New</span>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card rounded-4 shadow-sm p-4 h-100">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div>
                        <h2 class="h4 mb-1"><?= (int) $counts->resolved ?></h2>
                        <p class="text-muted mb-0">Resolved Reports</p>
                    </div>
                    <span class="badge bg-success">Resolved</span>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card rounded-4 shadow-sm p-4 h-100">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div>
                        <h2 class="h4 mb-1"><?= (int) $counts->rejected ?></h2>
                        <p class="text-muted mb-0">Rejected Reports</p>
                    </div>
                    <span class="badge bg-danger">Rejected</span>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card rounded-4 shadow-sm p-4 h-100">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div>
                        <h2 class="h4 mb-1"><?= (int) $counts->need_more_info ?></h2>
                        <p class="text-muted mb-0">Evidence Requests</p>
                    </div>
                    <span class="badge bg-warning text-dark">In Progress</span>
                </div>
            </div>
        </div>
    </div>

    <section class="card rounded-4 shadow-sm p-4 mb-4" aria-labelledby="trust-center-quick-actions-title">
        <h5 class="fw-semibold mb-3" id="trust-center-quick-actions-title">Quick Actions</h5>
        <div class="row g-3">
            <div class="col-md-6 col-xl d-grid">
                <a href="/ani-mart/public/admin/trust-center/reports" class="btn btn-primary">Review Reports</a>
            </div>
            <div class="col-md-6 col-xl d-grid">
                <a href="/ani-mart/public/community/reports" class="btn btn-outline-primary">Community Moderation</a>
            </div>
            <div class="col-md-6 col-xl d-grid">
                <a href="/ani-mart/public/admin/complaints" class="btn btn-outline-warning">Review Customer Disputes</a>
            </div>
            <div class="col-md-6 col-xl d-grid">
                <a href="/ani-mart/public/admin/trust-center/appeals" class="btn btn-outline-success">Review Appeals</a>
            </div>
            <div class="col-md-6 col-xl d-grid">
                <a href="/ani-mart/public/reports/create" class="btn btn-outline-secondary">Create Report</a>
            </div>
        </div>
    </section>

    <div class="row g-4">
        <div class="col-12">
            <div class="card rounded-4 shadow-sm p-4 h-100">
                <h5 class="fw-semibold mb-3">Moderation Workflow</h5>
                <ol class="list-group list-group-numbered">
                    <li class="list-group-item border-0 rounded-4 mb-2 shadow-sm">Submitted</li>
                    <li class="list-group-item border-0 rounded-4 mb-2 shadow-sm">Pending Review</li>
                    <li class="list-group-item border-0 rounded-4 mb-2 shadow-sm">Assigned</li>
                    <li class="list-group-item border-0 rounded-4 mb-2 shadow-sm">Investigation</li>
                    <li class="list-group-item border-0 rounded-4 mb-2 shadow-sm">Need More Information</li>
                    <li class="list-group-item border-0 rounded-4 shadow-sm">Resolved / Rejected</li>
                </ol>
            </div>
        </div>
    </div>
</main>

<?php
$content = ob_get_clean();
require ANI_MART_ROOT . '/app/Views/layouts/main.php';
