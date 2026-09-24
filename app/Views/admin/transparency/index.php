<?php

$title = 'Transparency Center';
$pageStyles = ['/ani-mart/public/assets/css/pages/admin-transparency.css'];

$logs = $logs ?? [];
$transparencyAlerts = $transparencyAlerts ?? [];
$selectedFarmer = $selectedFarmer ?? null;
$farmerProducts = $farmerProducts ?? [];
$farmerId = $farmerId ?? null;

$farmerName = $selectedFarmer
    ? trim(($selectedFarmer->first_name ?? '') . ' ' . ($selectedFarmer->last_name ?? ''))
    : '';
$activeProducts = (int) ($selectedFarmer->active_products ?? 0);
$currentEvidence = (int) ($selectedFarmer->current_evidence_products ?? 0);
$evidenceCoverage = $activeProducts > 0
    ? (int) round(($currentEvidence / $activeProducts) * 100)
    : 100;

$evidenceLabel = static function (string $status): string {
    return match ($status) {
        'current' => 'Current evidence',
        'missing_date' => 'Missing harvest date',
        'missing_photo' => 'Missing harvest photo',
        'outdated' => 'Harvest evidence outdated',
        'inactive' => 'Inactive listing',
        default => 'Needs review',
    };
};

$evidenceClass = static function (string $status): string {
    return match ($status) {
        'current' => 'is-current',
        'inactive' => 'is-neutral',
        default => 'is-warning',
    };
};

ob_start();

?>

<div class="container app-page-spacing transparency-page">
    <section class="admin-page-hero">
        <div class="admin-page-hero-copy">
            <span class="hero-badge">Ani-Mart Farm-to-Table Audit</span>
            <h1 class="hero-title">
                <?= $selectedFarmer ? htmlspecialchars($selectedFarmer->shop_name ?? 'Farmer record') : 'Transparency Center' ?>
            </h1>
            <p class="hero-subtitle">
                <?php if ($selectedFarmer): ?>
                    Review this farmer’s harvest evidence, listing coverage, and accountability history in one record.
                <?php else: ?>
                    Review harvest evidence, farmer accountability, and product verification history across the marketplace.
                <?php endif; ?>
            </p>
            <a href="/ani-mart/public/dashboard" class="admin-dashboard-back-link">
                <i class="bi bi-arrow-left" aria-hidden="true"></i>
                Admin dashboard
            </a>
        </div>

        <div class="admin-page-hero-actions">
            <?php if ($selectedFarmer): ?>
                <a class="btn btn-light" href="/ani-mart/public/admin/risk-monitoring">
                    <i class="bi bi-shield-exclamation me-1"></i> Risk monitoring
                </a>
                <a class="btn btn-outline-light" href="/ani-mart/public/admin/transparency">
                    View all records
                </a>
            <?php else: ?>
                <span class="transparency-hero-count">
                    <strong><?= count($transparencyAlerts) ?></strong>
                    active alert<?= count($transparencyAlerts) === 1 ? '' : 's' ?>
                </span>
            <?php endif; ?>
        </div>
    </section>

    <?php if ($farmerId !== null && !$selectedFarmer): ?>
        <div class="alert alert-warning rounded-4 border-0 transparency-notice" role="alert">
            <i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i>
            <div>
                <strong>Farmer record not found.</strong>
                No seller profile matches farmer ID #<?= (int) $farmerId ?>.
                <a href="/ani-mart/public/admin/risk-monitoring">Return to risk monitoring</a>.
            </div>
        </div>
    <?php endif; ?>

    <?php if ($selectedFarmer): ?>
        <section class="transparency-profile-card" aria-labelledby="farmer-record-heading">
            <div class="transparency-profile-main">
                <div class="transparency-avatar" aria-hidden="true">
                    <?= htmlspecialchars(strtoupper(substr($selectedFarmer->shop_name ?? 'F', 0, 1))) ?>
                </div>
                <div>
                    <div class="transparency-kicker">Farmer transparency record #<?= (int) $selectedFarmer->id ?></div>
                    <h2 id="farmer-record-heading"><?= htmlspecialchars($farmerName ?: ($selectedFarmer->shop_name ?? 'Unknown farmer')) ?></h2>
                    <div class="transparency-profile-meta">
                        <span><i class="bi bi-shop"></i><?= htmlspecialchars($selectedFarmer->shop_name ?? 'No shop name') ?></span>
                        <span><i class="bi bi-envelope"></i><?= htmlspecialchars($selectedFarmer->email ?? 'No email') ?></span>
                        <span><i class="bi bi-geo-alt"></i><?= htmlspecialchars(implode(', ', array_filter([$selectedFarmer->barangay ?? null, $selectedFarmer->city ?? null])) ?: 'Location not provided') ?></span>
                    </div>
                </div>
            </div>
            <div class="transparency-profile-status">
                <span class="status-pill <?= !empty($selectedFarmer->is_suspended) ? 'is-suspended' : 'is-approved' ?>">
                    <i class="bi <?= !empty($selectedFarmer->is_suspended) ? 'bi-slash-circle' : 'bi-patch-check-fill' ?>"></i>
                    <?= !empty($selectedFarmer->is_suspended) ? 'Suspended' : ucfirst($selectedFarmer->status ?? 'Active') ?>
                </span>
                <span class="verification-pill"><?= htmlspecialchars(ucfirst($selectedFarmer->verification_badge ?? 'new')) ?> seller</span>
            </div>
        </section>

        <section class="transparency-stats" aria-label="Farmer transparency summary">
            <article class="transparency-stat">
                <span class="stat-icon"><i class="bi bi-eye"></i></span>
                <div><small>Transparency score</small><strong><?= (int) ($selectedFarmer->transparency_score ?? 0) ?><span>/100</span></strong></div>
            </article>
            <article class="transparency-stat">
                <span class="stat-icon"><i class="bi bi-shield-check"></i></span>
                <div><small>Trust score</small><strong><?= (int) ($selectedFarmer->trust_score ?? 0) ?><span>/100</span></strong></div>
            </article>
            <article class="transparency-stat">
                <span class="stat-icon"><i class="bi bi-card-checklist"></i></span>
                <div><small>Evidence coverage</small><strong><?= $evidenceCoverage ?><span>%</span></strong></div>
            </article>
            <article class="transparency-stat <?= count($transparencyAlerts) ? 'has-alert' : '' ?>">
                <span class="stat-icon"><i class="bi bi-exclamation-diamond"></i></span>
                <div><small>Open alerts</small><strong><?= count($transparencyAlerts) ?></strong></div>
            </article>
        </section>

        <section class="transparency-card mb-4">
            <div class="transparency-card-header">
                <div>
                    <span class="section-eyebrow">Listing evidence</span>
                    <h2>Harvest record coverage</h2>
                    <p><?= $currentEvidence ?> of <?= $activeProducts ?> active listing<?= $activeProducts === 1 ? '' : 's' ?> currently have complete, recent evidence.</p>
                </div>
                <span class="record-count"><?= count($farmerProducts) ?> total listing<?= count($farmerProducts) === 1 ? '' : 's' ?></span>
            </div>

            <div class="table-responsive">
                <table class="table transparency-table align-middle mb-0">
                    <thead><tr><th>Product</th><th>Listing</th><th>Harvest date</th><th>Photo</th><th>Evidence status</th><th></th></tr></thead>
                    <tbody>
                    <?php if ($farmerProducts): ?>
                        <?php foreach ($farmerProducts as $product): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($product->name ?? 'Unnamed product') ?></strong><small>#<?= (int) $product->id ?></small></td>
                                <td><?= !empty($product->is_active) ? 'Active' : 'Inactive' ?><small><?= htmlspecialchars(ucfirst($product->approval_status ?? 'pending')) ?> approval</small></td>
                                <td><?= !empty($product->harvest_date) ? date('M d, Y', strtotime($product->harvest_date)) : 'Not provided' ?></td>
                                <td><?= !empty($product->harvest_photo) ? '<span class="text-success"><i class="bi bi-check-circle-fill"></i> Submitted</span>' : '<span class="text-warning-emphasis"><i class="bi bi-dash-circle"></i> Missing</span>' ?></td>
                                <td><span class="evidence-pill <?= $evidenceClass((string) $product->evidence_status) ?>"><?= htmlspecialchars($evidenceLabel((string) $product->evidence_status)) ?></span></td>
                                <td><a class="btn btn-sm btn-outline-success rounded-pill" href="/ani-mart/public/admin/products/view?id=<?= (int) $product->id ?>">Review</a></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6"><div class="transparency-empty"><i class="bi bi-basket"></i><strong>No product listings</strong><span>This farmer has not created a listing yet.</span></div></td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endif; ?>

    <section class="transparency-card mb-4">
        <div class="transparency-card-header">
            <div>
                <span class="section-eyebrow">Priority review</span>
                <h2>Listings needing attention</h2>
                <p>Active products with missing or outdated harvest evidence.</p>
            </div>
            <span class="record-count is-warning"><?= count($transparencyAlerts) ?> alert<?= count($transparencyAlerts) === 1 ? '' : 's' ?></span>
        </div>
        <div class="table-responsive">
            <table class="table transparency-table align-middle mb-0">
                <thead><tr><th>Product</th><?php if (!$selectedFarmer): ?><th>Shop</th><?php endif; ?><th>Alert</th><th>Harvest date</th><th>Photo</th><th></th></tr></thead>
                <tbody>
                <?php if ($transparencyAlerts): ?>
                    <?php foreach ($transparencyAlerts as $alert): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($alert->name ?? 'Unnamed product') ?></strong><small>#<?= (int) ($alert->id ?? 0) ?></small></td>
                            <?php if (!$selectedFarmer): ?><td><?= htmlspecialchars($alert->shop_name ?? 'Unknown shop') ?></td><?php endif; ?>
                            <td><span class="evidence-pill is-warning"><?= htmlspecialchars($alert->alert_reason ?? 'Review product record') ?></span></td>
                            <td><?= !empty($alert->harvest_date) ? date('M d, Y', strtotime($alert->harvest_date)) : 'Missing' ?></td>
                            <td><?= !empty($alert->harvest_photo) ? 'Submitted' : 'Missing' ?></td>
                            <td><a class="btn btn-sm btn-outline-success rounded-pill" href="/ani-mart/public/admin/products/view?id=<?= (int) ($alert->id ?? 0) ?>">Review</a></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="<?= $selectedFarmer ? 5 : 6 ?>"><div class="transparency-empty is-success"><i class="bi bi-shield-check"></i><strong>No transparency alerts</strong><span>Active listings have current harvest evidence.</span></div></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="transparency-card">
        <div class="transparency-card-header">
            <div>
                <span class="section-eyebrow">Audit trail</span>
                <h2>Harvest date activity</h2>
                <p>Every recorded harvest-date change remains visible for accountability.</p>
            </div>
            <span class="record-count"><?= count($logs) ?> record<?= count($logs) === 1 ? '' : 's' ?></span>
        </div>
        <div class="table-responsive">
            <table class="table transparency-table align-middle mb-0">
                <thead><tr><th>Product</th><?php if (!$selectedFarmer): ?><th>Farmer</th><?php endif; ?><th>Action</th><th>Previous value</th><th>New value</th><th>Date changed</th></tr></thead>
                <tbody>
                <?php if ($logs): ?>
                    <?php foreach ($logs as $log): ?>
                        <?php $logFarmer = trim(($log->first_name ?? '') . ' ' . ($log->last_name ?? '')); ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($log->product_name ?? 'Deleted product') ?></strong></td>
                            <?php if (!$selectedFarmer): ?><td><?= htmlspecialchars($logFarmer ?: ($log->shop_name ?? 'Unknown farmer')) ?></td><?php endif; ?>
                            <td><span class="evidence-pill is-current"><?= htmlspecialchars($log->action ?? 'Updated') ?></span></td>
                            <td><?= htmlspecialchars($log->old_value ?: 'Empty') ?></td>
                            <td><?= htmlspecialchars($log->new_value ?: 'Empty') ?></td>
                            <td><?= date('M d, Y · h:i A', strtotime($log->created_at ?? 'now')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="<?= $selectedFarmer ? 5 : 6 ?>"><div class="transparency-empty"><i class="bi bi-clock-history"></i><strong>No harvest-date changes yet</strong><span>Changes will appear here as an immutable activity trail.</span></div></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<?php

$content = ob_get_clean();
require ANI_MART_ROOT . '/app/Views/layouts/main.php';

?>
