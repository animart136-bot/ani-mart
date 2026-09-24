<?php

$title = $title ?? 'Resolution Center';
$bodyClass = $bodyClass ?? 'reports-complaints-page';
$role = $role ?? '';
$reports = $reports ?? [];
$disputes = $disputes ?? [];
$appeals = $appeals ?? [];
$eligibleAppealReports = $eligibleAppealReports ?? [];
$openReports = (int) ($openReports ?? 0);
$openDisputes = (int) ($openDisputes ?? 0);
$pendingAppeals = (int) ($pendingAppeals ?? 0);

$isFarmer = $role === 'farmer';
$disputeListUrl = $isFarmer ? app_url('farmer/complaints') : app_url('customer/complaints');
$disputeShowPath = $isFarmer ? 'farmer/complaints/show' : 'customer/complaints/show';
$statusClass = static function (string $status): string {
    return match ($status) {
        'resolved', 'accepted', 'closed' => 'success',
        'rejected' => 'danger',
        'need_more_info', 'needs_more_evidence', 'waiting_farmer_response' => 'warning text-dark',
        'investigation', 'under_review', 'assigned' => 'info text-dark',
        default => 'secondary',
    };
};
$formatStatus = static fn (string $status): string => ucwords(str_replace('_', ' ', $status));
$pageStyles[] = app_url('assets/css/pages/resolution-center.css?v=' . filemtime(ANI_MART_ROOT . '/public/assets/css/pages/resolution-center.css'));

ob_start();
?>

<main class="container app-page-spacing resolution-center-shell">
    <?php render_component('page-hero', [
        'class' => 'resolution-center-hero',
        'eyebrow' => 'Trust & Resolution',
        'title' => 'Resolution Center',
        'subtitle' => 'File safety reports, track order disputes, and review appeal decisions from one place.',
        'actions' => [
            ['href' => app_url('reports/create'), 'label' => 'File a safety report', 'class' => 'btn-light'],
            ['href' => $disputeListUrl, 'label' => $isFarmer ? 'View customer disputes' : 'Open an order dispute', 'class' => 'btn-outline-light'],
        ],
    ]); ?>

    <section class="resolution-summary-grid" aria-label="Case summary">
        <a href="#reports" class="resolution-summary-card">
            <span class="resolution-summary-icon"><i class="bi bi-flag"></i></span>
            <span><small>Open reports</small><strong><?= $openReports ?></strong></span>
        </a>
        <a href="#disputes" class="resolution-summary-card">
            <span class="resolution-summary-icon"><i class="bi bi-receipt"></i></span>
            <span><small><?= $isFarmer ? 'Active customer disputes' : 'Open order disputes' ?></small><strong><?= $openDisputes ?></strong></span>
        </a>
        <a href="#appeals" class="resolution-summary-card">
            <span class="resolution-summary-icon"><i class="bi bi-arrow-counterclockwise"></i></span>
            <span><small>Pending appeals</small><strong><?= $pendingAppeals ?></strong></span>
        </a>
    </section>

    <nav class="resolution-tabs" aria-label="Resolution Center sections">
        <a href="#reports"><i class="bi bi-flag"></i> Reports</a>
        <a href="#disputes"><i class="bi bi-receipt"></i> Order disputes</a>
        <a href="#appeals"><i class="bi bi-arrow-counterclockwise"></i> Appeals</a>
    </nav>

    <section id="reports" class="resolution-panel" aria-labelledby="reports-title">
        <header class="resolution-panel-heading">
            <div><span>Trust & Safety</span><h2 id="reports-title">My reports</h2><p>Safety and conduct concerns submitted to the moderation team.</p></div>
            <a href="<?= e(app_url('reports/create')) ?>" class="btn btn-success rounded-pill"><i class="bi bi-plus-circle"></i> New report</a>
        </header>
        <?php if ($reports): ?>
            <div class="resolution-record-list">
                <?php foreach (array_slice($reports, 0, 8) as $report): ?>
                    <article class="resolution-record">
                        <span class="resolution-record-id">#<?= (int) $report->id ?></span>
                        <div class="resolution-record-copy">
                            <strong><?= e((string) $report->reason) ?></strong>
                            <small><?= e(ucwords(str_replace('_', ' ', (string) $report->target_type))) ?> · <?= date('M j, Y', strtotime($report->created_at)) ?></small>
                        </div>
                        <span class="badge bg-<?= e($statusClass((string) $report->status)) ?> rounded-pill"><?= e($formatStatus((string) $report->status)) ?></span>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="resolution-empty"><i class="bi bi-shield-check"></i><div><strong>No reports submitted</strong><p>Your safety reports will appear here.</p></div></div>
        <?php endif; ?>
    </section>

    <section id="disputes" class="resolution-panel" aria-labelledby="disputes-title">
        <header class="resolution-panel-heading">
            <div><span>Orders & Transactions</span><h2 id="disputes-title"><?= $isFarmer ? 'Customer disputes' : 'My order disputes' ?></h2><p><?= $isFarmer ? 'Cases filed against any of your shops, regardless of the active shop.' : 'Issues connected to your delivered or completed orders.' ?></p></div>
            <a href="<?= e($disputeListUrl) ?>" class="btn btn-outline-success rounded-pill">View all disputes</a>
        </header>
        <?php if ($disputes): ?>
            <div class="resolution-record-list">
                <?php foreach (array_slice($disputes, 0, 8) as $dispute): ?>
                    <a class="resolution-record resolution-record-link" href="<?= e(app_url($disputeShowPath . '?id=' . (int) $dispute->id)) ?>">
                        <span class="resolution-record-id">#<?= (int) $dispute->id ?></span>
                        <div class="resolution-record-copy">
                            <strong><?= e((string) $dispute->category) ?></strong>
                            <small>Order <?= e((string) $dispute->order_number) ?><?= $isFarmer && !empty($dispute->shop_name) ? ' · ' . e((string) $dispute->shop_name) : '' ?></small>
                        </div>
                        <span class="badge bg-<?= e($statusClass((string) $dispute->status)) ?> rounded-pill"><?= e($formatStatus((string) $dispute->status)) ?></span>
                        <i class="bi bi-chevron-right"></i>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="resolution-empty"><i class="bi bi-receipt"></i><div><strong>No order disputes</strong><p><?= $isFarmer ? 'Disputes involving your shops will appear here.' : 'You currently have no order disputes.' ?></p></div></div>
        <?php endif; ?>
    </section>

    <section id="appeals" class="resolution-panel" aria-labelledby="appeals-title">
        <header class="resolution-panel-heading">
            <div><span>Decision Review</span><h2 id="appeals-title">My appeals</h2><p>Requests to reconsider an eligible moderation decision.</p></div>
            <a href="<?= e(app_url('trust-center/appeals')) ?>" class="btn btn-outline-success rounded-pill">View all appeals</a>
        </header>
        <?php if ($eligibleAppealReports): ?>
            <div class="resolution-record-list mb-3">
                <?php foreach ($eligibleAppealReports as $report): ?>
                    <details class="resolution-record">
                        <summary class="w-100 d-flex align-items-center gap-3">
                            <span class="resolution-record-id">#<?= (int) $report->id ?></span>
                            <div class="resolution-record-copy flex-grow-1">
                                <strong>Appeal rejected report</strong>
                                <small><?= e((string) $report->reason) ?></small>
                            </div>
                            <span class="badge bg-danger rounded-pill">Rejected</span>
                        </summary>
                        <form class="mt-3 appeal-submission-form" method="post" action="<?= e(app_url('trust-center/appeals/store')) ?>">
                            <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="report_id" value="<?= (int) $report->id ?>">
                            <label class="form-label" for="appeal-reason-<?= (int) $report->id ?>">Why should this decision be reconsidered?</label>
                            <textarea class="form-control" id="appeal-reason-<?= (int) $report->id ?>" name="reason" rows="3" maxlength="1200" required></textarea>
                            <button class="btn btn-success rounded-pill mt-2" type="submit">Submit appeal</button>
                        </form>
                    </details>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <?php if ($appeals): ?>
            <div class="resolution-record-list">
                <?php foreach (array_slice($appeals, 0, 8) as $appeal): ?>
                    <article class="resolution-record">
                        <span class="resolution-record-id">#<?= (int) $appeal->id ?></span>
                        <div class="resolution-record-copy"><strong>Appeal for report #<?= (int) $appeal->report_id ?></strong><small><?= e(mb_strimwidth((string) $appeal->reason, 0, 110, '…')) ?></small></div>
                        <span class="badge bg-<?= e($statusClass((string) $appeal->status)) ?> rounded-pill"><?= e($formatStatus((string) $appeal->status)) ?></span>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="resolution-empty"><i class="bi bi-arrow-counterclockwise"></i><div><strong>No appeals submitted</strong><p>Eligible report appeals will appear here.</p></div></div>
        <?php endif; ?>
    </section>

    <aside class="resolution-community-note">
        <i class="bi bi-people"></i>
        <div><strong>Reporting community content?</strong><p>Use the report action directly on the post or comment. This keeps the report attached to the correct content and prevents duplicate cases.</p></div>
        <a href="<?= e(app_url('community')) ?>" class="btn btn-sm btn-outline-success rounded-pill">Go to community</a>
    </aside>
</main>

<script>
document.querySelectorAll('.appeal-submission-form').forEach((form) => {
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const button = form.querySelector('button[type="submit"]');
        button.disabled = true;

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: { 'Accept': 'application/json' },
                body: new FormData(form),
            });
            const result = await response.json();

            if (!result.success) {
                alert(result.message || 'Unable to submit your appeal.');
                button.disabled = false;
                return;
            }

            window.location.reload();
        } catch (error) {
            alert('Unable to submit your appeal. Please try again.');
            button.disabled = false;
        }
    });
});
</script>

<?php
$content = ob_get_clean();
require ANI_MART_ROOT . '/app/Views/layouts/main.php';
