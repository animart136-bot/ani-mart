<?php

$title = $title ?? 'Appeals Review';
$bodyClass = $bodyClass ?? 'trust-safety-appeals-admin';

ob_start();
?>

<main class="container app-page-spacing">
    <section class="admin-page-hero" aria-labelledby="appeals-review-title">
        <div class="admin-page-hero-copy">
            <span class="hero-badge">Case Review</span>
            <h1 class="hero-title" id="appeals-review-title">Appeals Review</h1>
            <p class="hero-subtitle">Review incoming appeals and update outcomes for contested reports.</p>
        </div>
        <div class="admin-page-hero-actions">
            <a href="/ani-mart/public/admin/trust-center" class="btn btn-light">Trust &amp; Safety Dashboard</a>
        </div>
    </section>

    <div class="card rounded-4 shadow-sm overflow-hidden">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>#</th>
                        <th>Report</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Reporter</th>
                        <th>Submitted</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($appeals)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">No appeals have been submitted yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($appeals as $appeal): ?>
                            <tr>
                                <td><?= (int) $appeal->id ?></td>
                                <td>#<?= (int) $appeal->report_id ?> <span class="badge bg-secondary text-capitalize"><?= htmlspecialchars(str_replace('_', ' ', $appeal->report_status)) ?></span></td>
                                <td><?= htmlspecialchars(mb_strimwidth($appeal->reason, 0, 80, '...')) ?></td>
                                <td><span class="badge bg-<?= $appeal->status === 'accepted' ? 'success' : ($appeal->status === 'rejected' ? 'danger' : 'secondary') ?> text-capitalize"><?= htmlspecialchars($appeal->status) ?></span></td>
                                <td>User #<?= (int) $appeal->user_id ?></td>
                                <td><?= date('M j, Y', strtotime($appeal->created_at)) ?></td>
                                <td>
                                    <?php if ($appeal->status === 'pending'): ?>
                                        <form method="POST" action="/ani-mart/public/admin/trust-center/appeals/update" class="d-flex gap-2">
                                            <input 
    type="hidden" 
    name="_token" 
    value="<?= htmlspecialchars(csrf_token()) ?>">
                                        <input type="hidden" name="appeal_id" value="<?= (int) $appeal->id ?>">
                                            <button name="status" value="accepted" class="btn btn-sm btn-success">Accept</button>
                                            <button name="status" value="rejected" class="btn btn-sm btn-danger">Reject</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted small">No action needed</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php
$content = ob_get_clean();
require ANI_MART_ROOT . '/app/Views/layouts/main.php';
