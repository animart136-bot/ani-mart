<?php

use App\Core\Session;

$title = 'Administration Console | Ani-Mart';
$bodyClass = 'admin-console-page';
$pageCss = '/ani-mart/public/assets/css/pages/admin-dashboard.css?v=' . filemtime(__DIR__ . '/../../../../public/assets/css/pages/admin-dashboard.css');
$pageStyles[] = '/ani-mart/public/assets/css/pages/admin-dashboard-brand.css?v=' . filemtime(__DIR__ . '/../../../../public/assets/css/pages/admin-dashboard-brand.css');
$user = Session::get('user') ?? [];

$applications = (int) ($applications ?? 0);
$farmers = (int) ($farmers ?? 0);
$pendingProducts = (int) ($pendingProducts ?? 0);
$recentFarmers = $recentFarmers ?? [];
$transparencyAlertCount = (int) ($transparencyAlertCount ?? 0);
$recentRiskAlerts = $recentRiskAlerts ?? [];
$riskSummary = $riskSummary ?? ['high' => 0, 'medium' => 0, 'low' => 0];

$highRisk = (int) ($riskSummary['high'] ?? 0);
$attentionTotal = $applications + $pendingProducts + $highRisk + $transparencyAlertCount;
$adminName = trim((string) (($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')));
$adminName = $adminName !== '' ? $adminName : 'Administrator';

$adminLinks = [
    ['label' => 'Overview', 'icon' => 'bi-grid-1x2-fill', 'href' => '/ani-mart/public/dashboard', 'active' => true],
    ['label' => 'Seller applications', 'icon' => 'bi-person-check', 'href' => '/ani-mart/public/admin/applications'],
    ['label' => 'Product review', 'icon' => 'bi-box-seam', 'href' => '/ani-mart/public/admin/products'],
    ['label' => 'Categories', 'icon' => 'bi-tags', 'href' => '/ani-mart/public/admin/categories'],
    ['label' => 'User access', 'icon' => 'bi-person-lock', 'href' => '/ani-mart/public/admin/users'],
    ['label' => 'Price references', 'icon' => 'bi-currency-exchange', 'href' => '/ani-mart/public/admin/reference-data/prices'],
    ['label' => 'Trust & safety', 'icon' => 'bi-shield-exclamation', 'href' => '/ani-mart/public/admin/trust-center'],
    ['label' => 'Community reports', 'icon' => 'bi-flag', 'href' => '/ani-mart/public/community/reports'],
    ['label' => 'Analytics', 'icon' => 'bi-bar-chart-line', 'href' => '/ani-mart/public/admin/analytics'],
    ['label' => 'Audit logs', 'icon' => 'bi-journal', 'href' => '/ani-mart/public/admin/audit-logs'],
];

ob_start();
?>

<main class="admin-console container-fluid app-page-spacing">
    <?php render_component('page-hero', [
        'class' => 'admin-dashboard-hero',
        'eyebrow' => 'Restricted administration area',
        'title' => 'Marketplace Operations',
        'subtitle' => 'Review marketplace health, resolve priority work, and manage Ani-Mart from one dedicated console.',
        'stats' => [['label' => 'Needs attention', 'value' => $attentionTotal]],
    ]); ?>

    <div class="admin-console-layout">
        <aside class="admin-console-sidebar" aria-label="Administration navigation">
            <div class="admin-sidebar-heading">Administration</div>
            <nav>
                <?php foreach ($adminLinks as $link): ?>
                    <a href="<?= htmlspecialchars($link['href']) ?>" class="<?= !empty($link['active']) ? 'active' : '' ?>">
                        <i class="bi <?= htmlspecialchars($link['icon']) ?>"></i>
                        <span><?= htmlspecialchars($link['label']) ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>
            <div class="admin-sidebar-note">
                <i class="bi bi-eye"></i>
                <span>Administrative actions are recorded in the audit log.</span>
            </div>
        </aside>

        <div class="admin-console-main">
            <section class="admin-priority-banner <?= $attentionTotal > 0 ? 'has-work' : '' ?>">
                <span class="admin-priority-icon"><i class="bi <?= $attentionTotal > 0 ? 'bi-inbox-fill' : 'bi-check2-circle' ?>"></i></span>
                <div>
                    <strong><?= $attentionTotal > 0 ? number_format($attentionTotal) . ' items need attention' : 'All priority queues are clear' ?></strong>
                    <p><?= $attentionTotal > 0 ? 'Open a queue below to review pending applications, listings, safety risks, or transparency concerns.' : 'There are no pending application, product, risk, or transparency items right now.' ?></p>
                </div>
                <time datetime="<?= date('Y-m-d') ?>"><?= date('l, M j') ?></time>
            </section>

            <section class="admin-metric-grid" aria-label="Marketplace overview">
                <a class="admin-metric-card" href="/ani-mart/public/admin/farmers">
                    <span class="metric-icon metric-green"><i class="bi bi-shop"></i></span>
                    <span><small>Registered sellers</small><strong><?= number_format($farmers) ?></strong><em>Manage seller accounts</em></span>
                </a>
                <a class="admin-metric-card" href="/ani-mart/public/admin/applications">
                    <span class="metric-icon metric-blue"><i class="bi bi-person-vcard"></i></span>
                    <span><small>Seller applications</small><strong><?= number_format($applications) ?></strong><em>Waiting for review</em></span>
                </a>
                <a class="admin-metric-card" href="/ani-mart/public/admin/products">
                    <span class="metric-icon metric-violet"><i class="bi bi-box2-heart"></i></span>
                    <span><small>Product reviews</small><strong><?= number_format($pendingProducts) ?></strong><em>Pending a decision</em></span>
                </a>
            </section>

            <section class="admin-section" id="admin-work-queues">
                <div class="admin-section-heading">
                    <div><span>Work queues</span><h2>Needs your review</h2></div>
                    <small>Prioritized administrative actions</small>
                </div>
                <div class="admin-queue-grid">
                    <a href="/ani-mart/public/admin/applications" class="admin-queue-card">
                        <i class="bi bi-person-vcard"></i><span><strong>Seller applications</strong><small>Identity and eligibility review</small></span><b><?= $applications ?></b>
                    </a>
                    <a href="/ani-mart/public/admin/products" class="admin-queue-card">
                        <i class="bi bi-box2-heart"></i><span><strong>Product approvals</strong><small>Pending or needs correction</small></span><b><?= $pendingProducts ?></b>
                    </a>
                    <a href="/ani-mart/public/admin/risk-monitoring" class="admin-queue-card <?= $highRisk > 0 ? 'urgent' : '' ?>">
                        <i class="bi bi-shield-fill-exclamation"></i><span><strong>High-risk sellers</strong><small>Trust and safety review</small></span><b><?= $highRisk ?></b>
                    </a>
                    <a href="/ani-mart/public/admin/transparency" class="admin-queue-card">
                        <i class="bi bi-eye-fill"></i><span><strong>Transparency alerts</strong><small>Listing records to verify</small></span><b><?= $transparencyAlertCount ?></b>
                    </a>
                </div>
            </section>

            <div class="admin-content-grid admin-content-grid-lower">
                <section class="admin-section">
                    <div class="admin-section-heading">
                        <div><span>Seller network</span><h2>Recently registered</h2></div>
                        <a href="/ani-mart/public/admin/farmers">Manage sellers <i class="bi bi-arrow-right"></i></a>
                    </div>
                    <?php if ($recentFarmers): ?>
                        <div class="admin-seller-list">
                            <?php foreach ($recentFarmers as $farmer): ?>
                                <div><span class="admin-seller-avatar"><?= htmlspecialchars(strtoupper(substr((string) ($farmer->shop_name ?? 'S'), 0, 1))) ?></span><span><strong><?= htmlspecialchars($farmer->shop_name ?? 'Unnamed shop') ?></strong><small><?= htmlspecialchars(trim((string) (($farmer->first_name ?? '') . ' ' . ($farmer->last_name ?? ''))) ?: 'Seller') ?></small></span><i class="bi bi-check-circle-fill"></i></div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="admin-empty"><i class="bi bi-shop-window"></i><p>No registered sellers to show.</p></div>
                    <?php endif; ?>
                </section>

                <section class="admin-section">
                    <div class="admin-section-heading"><div><span>Governance</span><h2>Control center</h2></div></div>
                    <div class="admin-control-links">
                        <a href="/ani-mart/public/admin/complaints"><i class="bi bi-chat-square-dots"></i><span><strong>Dispute resolution</strong><small>Review complaints and decisions</small></span></a>
                        <a href="/ani-mart/public/community/reports"><i class="bi bi-flag"></i><span><strong>Community moderation</strong><small>Review reported posts and comments</small></span></a>
                        <a href="/ani-mart/public/admin/verifications"><i class="bi bi-patch-check"></i><span><strong>Seller verification</strong><small>Manage marketplace credentials</small></span></a>
                        <a href="/ani-mart/public/admin/reference-data/prices"><i class="bi bi-database-check"></i><span><strong>Reference data</strong><small>Maintain pricing benchmarks</small></span></a>
                        <a href="/ani-mart/public/admin/audit-logs"><i class="bi bi-clock-history"></i><span><strong>Audit trail</strong><small>Inspect administrative changes</small></span></a>
                    </div>
                </section>
            </div>
        </div>
    </div>
</main>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../layouts/main.php';
