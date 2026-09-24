<?php

$title = $title ?? 'My Appeals';
$bodyClass = $bodyClass ?? 'trust-safety-appeals';
$appeals = $appeals ?? [];
$eligibleReports = $eligibleReports ?? [];
$pageStyles[] = '/ani-mart/public/assets/css/pages/customer-appeals.css?v=' .
    filemtime(__DIR__ . '/../../../../public/assets/css/pages/customer-appeals.css');

$appealBadge = static function (string $status): string {
    return match ($status) {
        'accepted' => 'success',
        'rejected' => 'danger',
        default => 'warning text-dark',
    };
};

ob_start();
?>

<main class="container app-page-spacing customer-appeals-shell">
    <section class="dashboard-hero mb-5" aria-labelledby="customer-appeals-title">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <span class="hero-badge">Trust & Resolution Center</span>
                <h1 class="hero-title mt-4" id="customer-appeals-title">My Appeals</h1>
                <p class="hero-subtitle mt-3">Track requests to reconsider moderation decisions and review each appeal outcome.</p>
                <div class="customer-appeals-hero-actions">
                    <a href="/ani-mart/public/reports-complaints" class="hero-back-link">
                        <i class="bi bi-arrow-left" aria-hidden="true"></i> Resolution Center
                    </a>
                    <a href="/ani-mart/public/trust-center/reports/create" class="btn btn-light">
                        <i class="bi bi-plus-circle" aria-hidden="true"></i> Submit a new report
                    </a>
                </div>
            </div>
        </div>
    </section>

    <?php if ($eligibleReports): ?>
        <section class="card border-0 shadow-sm rounded-5 customer-appeals-card mb-4" aria-labelledby="eligible-appeals-title">
            <div class="card-body p-4">
                <header class="customer-appeals-heading mb-4">
                    <div>
                        <span class="section-badge">Action needed</span>
                        <h2 class="section-title mb-0" id="eligible-appeals-title">Eligible for appeal</h2>
                        <p>Provide a reason to ask the Trust & Safety team to reconsider a rejected report.</p>
                    </div>
                </header>
                <?php foreach ($eligibleReports as $report): ?>
                    <form class="appeal-submission-form border rounded-4 p-3 mb-3" method="post" action="<?= e(app_url('trust-center/appeals/store')) ?>">
                        <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="report_id" value="<?= (int) $report->id ?>">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <strong>Report #<?= (int) $report->id ?></strong>
                            <span class="badge bg-danger rounded-pill">Rejected</span>
                        </div>
                        <p class="text-muted small mb-3">Original report: <?= e((string) $report->reason) ?></p>
                        <label class="form-label" for="appeal-reason-<?= (int) $report->id ?>">Why should this decision be reconsidered?</label>
                        <textarea class="form-control" id="appeal-reason-<?= (int) $report->id ?>" name="reason" rows="3" maxlength="1200" required></textarea>
                        <button class="btn btn-success rounded-pill mt-3" type="submit">Submit appeal</button>
                    </form>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <section class="card border-0 shadow-sm rounded-5 customer-appeals-card" aria-labelledby="appeals-history-title">
        <div class="card-body p-4">
            <header class="customer-appeals-heading">
                <div>
                    <span class="section-badge">Decision history</span>
                    <h2 class="section-title mb-0" id="appeals-history-title">Appeal records</h2>
                    <p>Review the reason, submission date, and latest decision for every appeal.</p>
                </div>
                <span class="badge bg-success rounded-pill px-3 py-2"><?= count($appeals) ?> item(s)</span>
            </header>

            <?php if ($appeals): ?>
                <div class="table-responsive customer-appeals-table-wrap">
                    <table class="table align-middle mb-0 customer-appeals-table">
                        <thead>
                            <tr>
                                <th>Appeal</th>
                                <th>Report</th>
                                <th>Reason</th>
                                <th>Status</th>
                                <th>Submitted</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($appeals as $appeal): ?>
                                <tr>
                                    <td data-label="Appeal"><strong>#<?= (int) $appeal->id ?></strong></td>
                                    <td data-label="Report">#<?= (int) $appeal->report_id ?></td>
                                    <td data-label="Reason" class="appeal-reason"><?= htmlspecialchars(mb_strimwidth((string) $appeal->reason, 0, 120, '…')) ?></td>
                                    <td data-label="Status"><span class="badge bg-<?= $appealBadge((string) $appeal->status) ?> rounded-pill px-3 py-2 text-capitalize"><?= htmlspecialchars($appeal->status) ?></span></td>
                                    <td data-label="Submitted"><?= date('M j, Y', strtotime($appeal->created_at)) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="customer-appeals-empty">
                    <span><i class="bi bi-check2-circle" aria-hidden="true"></i></span>
                    <h3>No appeals found</h3>
                    <p>Appeals submitted for eligible moderation decisions will appear here.</p>
                    <a href="/ani-mart/public/reports-complaints" class="btn btn-outline-success rounded-pill px-4">Open Resolution Center</a>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<script>
document.querySelectorAll('.appeal-submission-form').forEach((form) => {
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const button = form.querySelector('button[type="submit"]');
        button.disabled = true;
        try {
            const response = await fetch(form.action, { method: 'POST', headers: { 'Accept': 'application/json' }, body: new FormData(form) });
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
?>
