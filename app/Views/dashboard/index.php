<?php

use App\Services\BadgeCalculationService;
use App\Core\Session;

$role = strtolower((string) ($role ?? 'customer'));

$title = match ($role) {
    'admin' => 'Admin Dashboard',
    'farmer' => 'Farmer Dashboard',
    'customer' => 'Customer Dashboard',
    default => 'Dashboard',
};

$pageCss = '/ani-mart/public/assets/css/pages/dashboard.css?v=' .
    filemtime(__DIR__ . '/../../../public/assets/css/pages/dashboard.css');

$user = Session::get('user');

/*
|--------------------------------------------------------------------------
| ADMIN DATA
|--------------------------------------------------------------------------
*/

$applications = $applications ?? 0;
$farmers = $farmers ?? 0;
$orders = $orders ?? 0;
$pendingProducts = $pendingProducts ?? 0;
$revenue = $revenue ?? 0;

$latestOrders = $latestOrders ?? [];
$recentFarmers = $recentFarmers ?? [];
$lowTrustFarmers = $lowTrustFarmers ?? [];
$recentHarvestChanges = $recentHarvestChanges ?? [];
$transparencyAlerts = $transparencyAlerts ?? [];

$riskSummary = $riskSummary ?? [
    'high' => 0,
    'medium' => 0,
    'low' => 0,
];

$topRiskFarmers = $topRiskFarmers ?? [];
$recentRiskAlerts = $recentRiskAlerts ?? [];

$badgeDistribution = $badgeDistribution ?? [
    'new' => 0,
    'verified' => 0,
    'trusted' => 0,
];

/*
|--------------------------------------------------------------------------
| FARMER DATA
|--------------------------------------------------------------------------
*/

$activeShop = $activeShop ?? null;

$shopTheme = $activeShop?->shop_theme ?? 'fresh-farm';

$totalProducts = $totalProducts ?? 0;
$totalOrders = $totalOrders ?? 0;
$totalRevenue = $totalRevenue ?? 0;

$recentFarmerOrders = $recentFarmerOrders ?? [];
$products = $products ?? [];

/*
|--------------------------------------------------------------------------
| CUSTOMER DATA
|--------------------------------------------------------------------------
*/

$stats = $stats ?? [
    'following' => 0,
    'orders' => 0,
    'total_spent' => 0,
    'pending_orders' => 0,
];

$recentOrders = $recentOrders ?? [];
$dashboardAnalytics = $dashboardAnalytics ?? [
    'trend' => [],
    'statuses' => [],
    'top' => [],
    'has_activity' => false,
    'trend_title' => 'Orders and revenue',
    'amount_label' => 'Revenue',
    'top_title' => 'Top categories',
];

/*
|--------------------------------------------------------------------------
| FARMER SHOP THEMES
|--------------------------------------------------------------------------
*/

$shopThemes = [

    'fresh-farm' => [
        'accent' => '#2F7D32',
        'secondary' => '#A8D5A2',
        'background' => '#F2F8F4',
        'text' => '#183C2A',
    ],

    'harvest-gold' => [
        'accent' => '#C47A17',
        'secondary' => '#F2C866',
        'background' => '#FFF8E8',
        'text' => '#49360D',
    ],

    'tomato-harvest' => [
        'accent' => '#D94A3A',
        'secondary' => '#F28A7E',
        'background' => '#FFF3F1',
        'text' => '#4A1D18',
    ],

    'nature-blue' => [
        'accent' => '#2878A8',
        'secondary' => '#78B8D4',
        'background' => '#F0F8FC',
        'text' => '#183746',
    ],

    'mint-garden' => [
        'accent' => '#2A9D8F',
        'secondary' => '#83D6C9',
        'background' => '#EFFAF8',
        'text' => '#174C46',
    ],

    'carrot-orange' => [
        'accent' => '#E76F27',
        'secondary' => '#F5B27A',
        'background' => '#FFF5ED',
        'text' => '#542A12',
    ],

    'berry-garden' => [
        'accent' => '#7952A8',
        'secondary' => '#B89AD8',
        'background' => '#F7F2FB',
        'text' => '#30203F',
    ],

    'olive-grove' => [
        'accent' => '#687A2E',
        'secondary' => '#B8C77A',
        'background' => '#F6F8EC',
        'text' => '#343D18',
    ],

    'peach-blossom' => [
        'accent' => '#E58F65',
        'secondary' => '#F3C0A5',
        'background' => '#FFF6F1',
        'text' => '#542E20',
    ],

    'earthen-farm' => [
        'accent' => '#8A6845',
        'secondary' => '#CDB28F',
        'background' => '#F8F3EC',
        'text' => '#3F3021',
    ],
];

$currentTheme = $shopThemes[$shopTheme] ?? $shopThemes['fresh-farm'];

/*
|--------------------------------------------------------------------------
| HELPERS
|--------------------------------------------------------------------------
*/

$getInitials = static function ($firstName = '', $lastName = ''): string {

    $firstName = trim((string) $firstName);
    $lastName = trim((string) $lastName);

    $initials = '';

    if ($firstName !== '') {
        $initials .= strtoupper(substr($firstName, 0, 1));
    }

    if ($lastName !== '') {
        $initials .= strtoupper(substr($lastName, 0, 1));
    }

    return $initials !== '' ? $initials : 'GC';
};

ob_start();

?>




<?php if (
    $role === 'admin' ||
    $role === 'farmer' ||
    $role === 'customer'
): ?>

    <div class="<?= $role === 'admin'
        ? 'admin-dashboard'
        : ($role === 'farmer'
            ? 'farmer-dashboard'
            : 'customer-dashboard') ?> container app-page-spacing" <?php if ($role === 'farmer'): ?>
            style="--shop-accent: <?= htmlspecialchars($currentTheme['accent']) ?>; --shop-secondary: <?= htmlspecialchars($currentTheme['secondary']) ?>; --shop-background: <?= htmlspecialchars($currentTheme['background']) ?>; --shop-text: <?= htmlspecialchars($currentTheme['text']) ?>;"
        <?php endif; ?>>


        <!-- =========================================================
         HERO
         ========================================================= -->

        <section class="dashboard-hero mb-4 mb-lg-5">

            <?php if ($role === 'admin'): ?>

                <div class="dashboard-hero-content">

                    <span class="hero-badge">
                        <i class="bi bi-shield-check me-1"></i>
                        Ani-Mart Administration
                    </span>

                    <h1 class="hero-title mt-3 mb-2">
                        Marketplace Control Center
                    </h1>

                    <p class="hero-subtitle mb-0">
                        Monitor marketplace activity, manage sellers,
                        products, and customer transactions.
                    </p>

                </div>


            <?php elseif ($role === 'farmer'): ?>

                <div class="dashboard-hero-content">

                    <span class="hero-badge">
                        Ani-Mart Farmer Panel
                    </span>

                    <h1 class="hero-title mt-3">
                        Welcome back,
                        <?= htmlspecialchars(
                            $user['first_name'] ?? 'Farmer'
                        ) ?>
                    </h1>

                    <p class="hero-subtitle">
                        Manage your products, monitor orders,
                        and grow your local farm marketplace.
                    </p>

                </div>


            <?php else: ?>

                <div class="dashboard-hero-content">

                    <span class="hero-badge">
                        Customer Dashboard
                    </span>

                    <h1 class="hero-title mt-3">
                        Welcome back,
                        <?= htmlspecialchars(
                            $user['first_name'] ?? 'Customer'
                        ) ?>
                    </h1>

                    <p class="hero-subtitle">
                        Track your orders, discover farms,
                        and shop fresh from local sellers.
                    </p>

                </div>

            <?php endif; ?>

        </section>

        <!-- =========================================================
         PERFORMANCE ANALYTICS
         ========================================================= -->

        <section class="dashboard-analytics-grid mb-4 mb-lg-5" aria-labelledby="dashboardAnalyticsTitle">
            <div class="dashboard-section-card dashboard-chart-card">
                <div class="section-heading section-heading-actions">
                    <div>
                        <span class="section-kicker">Analytics</span>
                        <h3 class="fw-bold mb-1" id="dashboardAnalyticsTitle">
                            <?= htmlspecialchars($dashboardAnalytics['trend_title']) ?>
                        </h3>
                        <p class="text-muted mb-0">Real activity recorded during the last six months.</p>
                    </div>
                    <span class="analytics-period">Last 6 months</span>
                </div>

                <?php if (empty($dashboardAnalytics['has_activity'])): ?>
                    <div class="analytics-empty-note">
                        <i class="bi bi-bar-chart-line"></i>
                        <span>No order activity yet. This chart will fill automatically after orders are placed.</span>
                    </div>
                <?php endif; ?>

                <div class="dashboard-chart-wrap">
                    <canvas id="dashboardTrendChart" aria-label="Six-month order and amount trend"></canvas>
                </div>
            </div>

            <div class="dashboard-analytics-side">
                <div class="dashboard-section-card dashboard-status-card">
                    <div class="section-heading mb-2">
                        <div>
                            <span class="section-kicker">Orders</span>
                            <h4 class="fw-bold mb-1">Status overview</h4>
                        </div>
                    </div>
                    <?php if (!empty($dashboardAnalytics['statuses'])): ?>
                        <div class="dashboard-doughnut-wrap"><canvas id="dashboardStatusChart"
                                aria-label="Order status distribution"></canvas></div>
                        <div class="dashboard-status-legend">
                            <?php foreach ($dashboardAnalytics['statuses'] as $index => $status): ?>
                                <div><span
                                        class="status-dot status-dot-<?= $index % 7 ?>"></span><span><?= htmlspecialchars($status['label']) ?></span><strong><?= (int) $status['total'] ?></strong>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state analytics-compact-empty"><i class="bi bi-pie-chart"></i>
                            <p>No order statuses yet.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="dashboard-section-card dashboard-top-card">
                    <div class="section-heading mb-2">
                        <div>
                            <span class="section-kicker">Performance</span>
                            <h4 class="fw-bold mb-1"><?= htmlspecialchars($dashboardAnalytics['top_title']) ?></h4>
                        </div>
                    </div>
                    <?php if (!empty($dashboardAnalytics['top'])): ?>
                        <div class="analytics-ranking">
                            <?php foreach ($dashboardAnalytics['top'] as $index => $item): ?>
                                <div class="analytics-ranking-row">
                                    <span class="analytics-rank"><?= $index + 1 ?></span>
                                    <span class="analytics-rank-name"><?= htmlspecialchars($item['label']) ?></span>
                                    <span class="analytics-rank-value"><?= (int) $item['quantity'] ?> sold</span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state analytics-compact-empty"><i class="bi bi-basket"></i>
                            <p>Sales rankings will appear here.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>


        <!-- =========================================================
         STATS
         ========================================================= -->

        <section class="dashboard-stats mb-4 mb-lg-5">


            <?php if ($role === 'admin'): ?>

                <!-- ADMIN APPLICATIONS -->

                <div class="dashboard-stat-card">

                    <div class="stat-icon bg-warning-subtle text-warning">
                        <i class="bi bi-person-plus-fill"></i>
                    </div>

                    <div class="stat-content">

                        <small class="text-muted d-block">
                            Applications
                        </small>

                        <h2 class="fw-bold mb-0">
                            <?= (int) $applications ?>
                        </h2>

                    </div>

                </div>


                <!-- ADMIN FARMERS -->

                <div class="dashboard-stat-card">

                    <div class="stat-icon bg-success-subtle text-success">
                        <i class="bi bi-shop"></i>
                    </div>

                    <div class="stat-content">

                        <small class="text-muted d-block">
                            Farmers
                        </small>

                        <h2 class="fw-bold mb-0">
                            <?= (int) $farmers ?>
                        </h2>

                    </div>

                </div>


                <!-- ADMIN ORDERS -->

                <div class="dashboard-stat-card">

                    <div class="stat-icon bg-primary-subtle text-primary">
                        <i class="bi bi-bag-check-fill"></i>
                    </div>

                    <div class="stat-content">

                        <small class="text-muted d-block">
                            Orders
                        </small>

                        <h2 class="fw-bold mb-0">
                            <?= (int) $orders ?>
                        </h2>

                    </div>

                </div>


                <!-- ADMIN REVENUE -->

                <div class="dashboard-stat-card">

                    <div class="stat-icon bg-success-subtle text-success">
                        <i class="bi bi-cash-stack"></i>
                    </div>

                    <div class="stat-content">

                        <small class="text-muted d-block">
                            Revenue
                        </small>

                        <h2 class="fw-bold mb-0">
                            ₱<?= number_format(
                                (float) $revenue,
                                2
                            ) ?>
                        </h2>

                    </div>

                </div>


                <!-- ADMIN PENDING PRODUCTS -->

                <a href="/ani-mart/public/admin/products" class="dashboard-stat-card dashboard-stat-link text-decoration-none">

                    <div class="stat-icon bg-danger-subtle text-danger">
                        <i class="bi bi-basket3"></i>
                    </div>

                    <div class="stat-content">

                        <small class="text-muted d-block">
                            Pending Products
                        </small>

                        <h2 class="fw-bold mb-0">
                            <?= (int) $pendingProducts ?>
                        </h2>

                    </div>

                    <i class="bi bi-arrow-up-right stat-link-arrow"></i>

                </a>


            <?php elseif ($role === 'farmer'): ?>

                <!-- FARMER PRODUCTS -->

                <div class="dashboard-stat-card">

                    <div class="stat-icon">
                        <i class="bi bi-basket"></i>
                    </div>

                    <div class="stat-content">

                        <h3 class="fw-bold mb-0">
                            <?= (int) $totalProducts ?>
                        </h3>

                        <p class="text-muted mb-0">
                            Products
                        </p>

                    </div>

                </div>


                <!-- FARMER ORDERS -->

                <div class="dashboard-stat-card">

                    <div class="stat-icon">
                        <i class="bi bi-box-seam"></i>
                    </div>

                    <div class="stat-content">

                        <h3 class="fw-bold mb-0">
                            <?= (int) $totalOrders ?>
                        </h3>

                        <p class="text-muted mb-0">
                            Orders
                        </p>

                    </div>

                </div>


                <!-- FARMER REVENUE -->

                <div class="dashboard-stat-card">

                    <div class="stat-icon">
                        <i class="bi bi-cash-stack"></i>
                    </div>

                    <div class="stat-content">

                        <h3 class="fw-bold mb-0">
                            ₱<?= number_format(
                                (float) $totalRevenue,
                                2
                            ) ?>
                        </h3>

                        <p class="text-muted mb-0">
                            Total Revenue
                        </p>

                    </div>

                </div>


            <?php else: ?>

                <!-- CUSTOMER FOLLOWING -->

                <div class="dashboard-stat-card">

                    <div class="stat-icon bg-success-subtle">
                        <i class="bi bi-heart text-success"></i>
                    </div>

                    <div class="stat-content">

                        <h3 class="fw-bold">
                            <?= (int) (
                                $stats['following'] ?? 0
                            ) ?>
                        </h3>

                        <p class="text-muted mb-0">
                            Followed Farms
                        </p>

                    </div>

                </div>


                <!-- CUSTOMER ORDERS -->

                <div class="dashboard-stat-card">

                    <div class="stat-icon bg-warning-subtle">
                        <i class="bi bi-bag-check text-warning"></i>
                    </div>

                    <div class="stat-content">

                        <h3 class="fw-bold">
                            <?= (int) (
                                $stats['orders'] ?? 0
                            ) ?>
                        </h3>

                        <p class="text-muted mb-0">
                            Orders
                        </p>

                    </div>

                </div>


                <!-- CUSTOMER SPENT -->

                <div class="dashboard-stat-card">

                    <div class="stat-icon bg-primary-subtle">
                        <i class="bi bi-currency-dollar text-primary"></i>
                    </div>

                    <div class="stat-content">

                        <h3 class="fw-bold">
                            ₱<?= number_format(
                                (float) (
                                    $stats['total_spent'] ?? 0
                                ),
                                2
                            ) ?>
                        </h3>

                        <p class="text-muted mb-0">
                            Total Spent
                        </p>

                    </div>

                </div>


                <!-- CUSTOMER PENDING -->

                <div class="dashboard-stat-card">

                    <div class="stat-icon bg-danger-subtle">
                        <i class="bi bi-clock-history text-danger"></i>
                    </div>

                    <div class="stat-content">

                        <h3 class="fw-bold">
                            <?= (int) (
                                $stats['pending_orders'] ?? 0
                            ) ?>
                        </h3>

                        <p class="text-muted mb-0">
                            Pending
                        </p>

                    </div>

                </div>

            <?php endif; ?>

        </section>


        <!-- =========================================================
         ADMIN QUICK ACTIONS
         ========================================================= -->

        <?php if ($role === 'admin'): ?>

            <section class="dashboard-section-card mb-4 mb-lg-5">

                <div class="section-heading">

                    <div>

                        <span class="section-kicker">
                            Management
                        </span>

                        <h3 class="fw-bold mb-1">
                            Quick Actions
                        </h3>

                        <p class="text-muted mb-0">
                            Access the most-used administration tools.
                        </p>

                    </div>

                </div>


                <div class="dashboard-quick-actions">

                    <a href="/ani-mart/public/admin/applications" class="dashboard-action dashboard-action-eden">
                        <i class="bi bi-people"></i>
                        <span>Farmer Management</span>
                        <i class="bi bi-arrow-right action-arrow"></i>
                    </a>


                    <a href="/ani-mart/public/admin/products" class="dashboard-action dashboard-action-lime">
                        <i class="bi bi-basket3"></i>
                        <span>Product Management</span>
                        <i class="bi bi-arrow-right action-arrow"></i>
                    </a>


                    <a href="/ani-mart/public/admin/analytics" class="dashboard-action dashboard-action-eco">
                        <i class="bi bi-graph-up"></i>
                        <span>Analytics</span>
                        <i class="bi bi-arrow-right action-arrow"></i>
                    </a>


                    <a href="/ani-mart/public/admin/orders" class="dashboard-action dashboard-action-emerald">
                        <i class="bi bi-view-list"></i>
                        <span>View Orders</span>
                        <i class="bi bi-arrow-right action-arrow"></i>
                    </a>


                    <a href="/ani-mart/public/admin/transparency" class="dashboard-action dashboard-action-rich">
                        <i class="bi bi-globe"></i>
                        <span>Transparency Center</span>
                        <i class="bi bi-arrow-right action-arrow"></i>
                    </a>

                </div>

            </section>

        <?php endif; ?>


        <!-- =========================================================
         ADMIN VERIFICATION BADGES
         ========================================================= -->

        <?php if ($role === 'admin'): ?>

            <section class="dashboard-section-card mb-4 mb-lg-5">

                <div class="section-heading section-heading-actions">

                    <div>

                        <span class="section-kicker">
                            Trust & Safety
                        </span>

                        <h3 class="fw-bold mb-1">
                            Verification Badge Distribution
                        </h3>

                        <p class="text-muted mb-0">
                            Overview of farmer verification levels.
                        </p>

                    </div>


                    <a href="/ani-mart/public/admin/verifications" class="btn btn-outline-primary rounded-pill px-3 px-md-4">
                        <i class="bi bi-award me-1"></i>
                        <span class="d-none d-sm-inline">
                            Manage
                        </span>
                        Badges
                    </a>

                </div>


                <div class="verification-grid">

                    <div class="verification-stat-card trusted-card">

                        <div class="stat-icon">
                            <i class="bi bi-shield-check"></i>
                        </div>

                        <div>

                            <small>
                                Trusted Farmers
                            </small>

                            <h3 class="fw-bold mb-0">
                                <?= (int) (
                                    $badgeDistribution['trusted'] ?? 0
                                ) ?>
                            </h3>

                        </div>

                    </div>


                    <div class="verification-stat-card verified-card">

                        <div class="stat-icon">
                            <i class="bi bi-patch-check-fill"></i>
                        </div>

                        <div>

                            <small>
                                Verified Farmers
                            </small>

                            <h3 class="fw-bold mb-0">
                                <?= (int) (
                                    $badgeDistribution['verified'] ?? 0
                                ) ?>
                            </h3>

                        </div>

                    </div>


                    <div class="verification-stat-card new-card">

                        <div class="stat-icon">
                            <i class="bi bi-person-plus"></i>
                        </div>

                        <div>

                            <small>
                                New Farmers
                            </small>

                            <h3 class="fw-bold mb-0">
                                <?= (int) (
                                    $badgeDistribution['new'] ?? 0
                                ) ?>
                            </h3>

                        </div>

                    </div>

                </div>

            </section>

        <?php endif; ?>


        <!-- =========================================================
         ADMIN RISK MONITORING
         ========================================================= -->

        <?php if ($role === 'admin'): ?>

            <section class="dashboard-section-card mb-4 mb-lg-5">

                <div class="section-heading section-heading-actions">

                    <div>

                        <span class="section-kicker">
                            Monitoring
                        </span>

                        <h3 class="fw-bold mb-1">
                            Marketplace Risk Monitoring
                        </h3>

                        <p class="text-muted mb-0">
                            Fraud detection, trust monitoring,
                            and farmer accountability.
                        </p>

                    </div>


                    <a href="/ani-mart/public/admin/risk-monitoring" class="btn btn-outline-danger rounded-pill px-3 px-md-4">
                        <i class="bi bi-shield-exclamation me-1"></i>
                        <span class="d-none d-sm-inline">
                            Open
                        </span>
                        Risk Dashboard
                    </a>

                </div>


                <div class="risk-summary-grid mb-4">

                    <div class="dashboard-stat-card">

                        <div class="stat-icon bg-danger-subtle text-danger">
                            <i class="bi bi-exclamation-octagon"></i>
                        </div>

                        <div class="stat-content">

                            <small class="text-muted d-block">
                                High Risk Farmers
                            </small>

                            <h2 class="fw-bold mb-0">
                                <?= (int) (
                                    $riskSummary['high'] ?? 0
                                ) ?>
                            </h2>

                        </div>

                    </div>


                    <div class="dashboard-stat-card">

                        <div class="stat-icon bg-warning-subtle text-warning">
                            <i class="bi bi-exclamation-triangle"></i>
                        </div>

                        <div class="stat-content">

                            <small class="text-muted d-block">
                                Medium Risk Farmers
                            </small>

                            <h2 class="fw-bold mb-0">
                                <?= (int) (
                                    $riskSummary['medium'] ?? 0
                                ) ?>
                            </h2>

                        </div>

                    </div>


                    <div class="dashboard-stat-card">

                        <div class="stat-icon bg-success-subtle text-success">
                            <i class="bi bi-check-circle"></i>
                        </div>

                        <div class="stat-content">

                            <small class="text-muted d-block">
                                Low Risk Farmers
                            </small>

                            <h2 class="fw-bold mb-0">
                                <?= (int) (
                                    $riskSummary['low'] ?? 0
                                ) ?>
                            </h2>

                        </div>

                    </div>

                </div>


                <div class="risk-detail-grid">


                    <div>

                        <h5 class="fw-bold mb-3">
                            Top Suspicious Farmers
                        </h5>


                        <?php if (count($topRiskFarmers)): ?>

                            <div class="risk-list">

                                <?php foreach (
                                    $topRiskFarmers as $farmer
                                ): ?>

                                    <?php

                                    $riskLevel = strtolower(
                                        (string) (
                                            $farmer->risk_level ?? 'low'
                                        )
                                    );

                                    $riskClass = match ($riskLevel) {

                                        'high' =>
                                            'bg-danger',

                                        'medium' =>
                                            'bg-warning text-dark',

                                        default =>
                                            'bg-success',
                                    };

                                    ?>

                                    <div class="risk-list-item">

                                        <div class="risk-farmer-info">

                                            <div class="risk-avatar">
                                                <i class="bi bi-shop"></i>
                                            </div>

                                            <div>

                                                <div class="fw-semibold">

                                                    <?= htmlspecialchars(
                                                        $farmer->shop_name
                                                        ?? 'No Shop'
                                                    ) ?>

                                                </div>

                                                <small class="text-muted">

                                                    Risk
                                                    <?= (int) (
                                                        $farmer->risk_score ?? 0
                                                    ) ?>

                                                    ·

                                                    <?= (int) (
                                                        $farmer->cancelled_orders
                                                        ?? 0
                                                    ) ?>

                                                    cancelled orders

                                                    ·

                                                    <?= (int) (
                                                        $farmer->harvest_changes
                                                        ?? 0
                                                    ) ?>

                                                    harvest changes

                                                </small>

                                            </div>

                                        </div>


                                        <span class="badge <?= $riskClass ?> px-3 py-2">
                                            <?= strtoupper(
                                                htmlspecialchars(
                                                    $riskLevel
                                                )
                                            ) ?>
                                        </span>

                                    </div>

                                <?php endforeach; ?>

                            </div>


                        <?php else: ?>

                            <div class="empty-state">

                                <i class="bi bi-shield-check"></i>

                                <p>
                                    No suspicious farmers detected.
                                </p>

                            </div>

                        <?php endif; ?>

                    </div>


                    <div>

                        <h5 class="fw-bold mb-3">
                            Recent Risk Alerts
                        </h5>


                        <?php if (count($recentRiskAlerts)): ?>

                            <?php foreach (
                                $recentRiskAlerts as $alert
                            ): ?>

                                <div class="alert-item">

                                    <i class="bi bi-exclamation-circle"></i>

                                    <span>
                                        <?= htmlspecialchars(
                                            $alert->message ?? ''
                                        ) ?>
                                    </span>

                                </div>

                            <?php endforeach; ?>


                        <?php else: ?>

                            <div class="empty-state">

                                <i class="bi bi-check2-circle"></i>

                                <p>
                                    No recent risk alerts.
                                </p>

                            </div>

                        <?php endif; ?>

                    </div>

                </div>

            </section>

        <?php endif; ?>


        <!-- =========================================================
         ADMIN TRANSPARENCY WIDGETS
         ========================================================= -->

        <?php if ($role === 'admin'): ?>

            <section class="dashboard-widget-grid mb-4 mb-lg-5">


                <!-- LOW TRUST FARMERS -->

                <div class="dashboard-section-card h-100">

                    <div class="section-heading">

                        <div>

                            <span class="section-kicker">
                                Trust
                            </span>

                            <h4 class="fw-bold mb-1">
                                Low Trust Farmers
                            </h4>

                        </div>

                    </div>


                    <?php if (count($lowTrustFarmers)): ?>

                        <div class="compact-list">

                            <?php foreach (
                                $lowTrustFarmers as $farmer
                            ): ?>

                                <div class="compact-list-item">

                                    <div>

                                        <div class="fw-semibold">

                                            <?= htmlspecialchars(
                                                $farmer->shop_name
                                                ?? 'No Shop'
                                            ) ?>

                                        </div>

                                        <small class="text-muted">

                                            <?= htmlspecialchars(
                                                trim(
                                                    ($farmer->first_name ?? '')
                                                    . ' '
                                                    . ($farmer->last_name ?? '')
                                                )
                                            ) ?>

                                        </small>

                                    </div>


                                    <span class="badge bg-warning text-dark px-3 py-2">
                                        <?= (int) (
                                            $farmer->trust_score ?? 0
                                        ) ?>
                                    </span>

                                </div>

                            <?php endforeach; ?>

                        </div>


                    <?php else: ?>

                        <div class="empty-state">

                            <i class="bi bi-shield-check"></i>

                            <p>
                                No farmers below 80 trust points.
                            </p>

                        </div>

                    <?php endif; ?>

                </div>


                <!-- RECENT HARVEST CHANGES -->

                <div class="dashboard-section-card h-100">

                    <div class="section-heading section-heading-actions">

                        <div>

                            <span class="section-kicker">
                                Transparency
                            </span>

                            <h4 class="fw-bold mb-1">
                                Recent Harvest Changes
                            </h4>

                        </div>


                        <a href="/ani-mart/public/admin/transparency" class="btn btn-sm btn-outline-success rounded-pill">
                            View All
                        </a>

                    </div>


                    <?php if (count($recentHarvestChanges)): ?>

                        <div class="compact-list">

                            <?php foreach (
                                $recentHarvestChanges as $change
                            ): ?>

                                <div class="compact-list-item compact-list-stack">

                                    <div class="fw-semibold">

                                        <?= htmlspecialchars(
                                            $change->product_name
                                            ?? 'Deleted Product'
                                        ) ?>

                                    </div>

                                    <small class="text-muted">

                                        <?= htmlspecialchars(
                                            $change->old_value ?: 'Empty'
                                        ) ?>

                                        <i class="bi bi-arrow-right mx-1"></i>

                                        <?= htmlspecialchars(
                                            $change->new_value ?: 'Empty'
                                        ) ?>

                                    </small>

                                </div>

                            <?php endforeach; ?>

                        </div>


                    <?php else: ?>

                        <div class="empty-state">

                            <i class="bi bi-calendar-check"></i>

                            <p>
                                No harvest date changes recorded.
                            </p>

                        </div>

                    <?php endif; ?>

                </div>


                <!-- TRANSPARENCY ALERTS -->

                <div class="dashboard-section-card h-100">

                    <div class="section-heading">

                        <div>

                            <span class="section-kicker">
                                Alerts
                            </span>

                            <h4 class="fw-bold mb-1">
                                Transparency Alerts
                            </h4>

                        </div>

                    </div>


                    <?php if (count($transparencyAlerts)): ?>

                        <div class="compact-list">

                            <?php foreach (
                                $transparencyAlerts as $alert
                            ): ?>

                                <div class="compact-list-item compact-list-stack">

                                    <div class="fw-semibold">

                                        <?= htmlspecialchars(
                                            $alert->name ?? 'Product'
                                        ) ?>

                                    </div>

                                    <small class="text-muted">

                                        <?= htmlspecialchars(
                                            $alert->shop_name
                                            ?? 'Unknown Farm'
                                        ) ?>

                                        ·

                                        <?= htmlspecialchars(
                                            $alert->alert_reason
                                            ?? 'Review product record'
                                        ) ?>

                                    </small>

                                </div>

                            <?php endforeach; ?>

                        </div>


                    <?php else: ?>

                        <div class="empty-state">

                            <i class="bi bi-check2-circle"></i>

                            <p>
                                No transparency alerts at this time.
                            </p>

                        </div>

                    <?php endif; ?>

                </div>

            </section>

        <?php endif; ?>


        <!-- =========================================================
         FARMER SHOP OVERVIEW
         ========================================================= -->

        <?php if (
            $role === 'farmer' &&
            $activeShop
        ): ?>

            <section class="dashboard-section-card farmer-shop-overview mb-4 mb-lg-5">

                <div class="section-heading section-heading-actions">

                    <div>

                        <span class="section-kicker">
                            My Shop
                        </span>

                        <h3 class="fw-bold mb-1">

                            <?= htmlspecialchars(
                                $activeShop->shop_name
                                ?? $activeShop->name
                                ?? 'Farm Shop'
                            ) ?>

                        </h3>

                        <p class="text-muted mb-0">
                            Manage your farm shop and seller information.
                        </p>

                    </div>


                    <a href="/ani-mart/public/seller-center/shop/customize" class="manage-shop-btn">
                        <i class="bi bi-shop" aria-hidden="true"></i>
                        <span>Manage Shop</span>
                    </a>

                </div>


                <div class="shop-overview-grid">


                    <!-- SHOP STATUS -->

                    <div class="shop-overview-item">

                        <div class="shop-overview-icon">
                            <i class="bi bi-shop"></i>
                        </div>

                        <div>

                            <small>
                                Shop Status
                            </small>

                            <strong>
                                <?= htmlspecialchars(
                                    $activeShop->status
                                    ?? $activeShop->shop_status
                                    ?? 'Approved'
                                ) ?>
                            </strong>

                        </div>

                    </div>


                    <!-- VERIFICATION -->

                    <div class="shop-overview-item">

                        <div class="shop-overview-icon">
                            <i class="bi bi-patch-check-fill"></i>
                        </div>

                        <div>

                            <small>
                                Verification
                            </small>

                            <strong>
                                Verified Seller
                            </strong>

                        </div>

                    </div>


                    <!-- SHOP THEME -->

                    <div class="shop-overview-item">

                        <div class="shop-overview-icon">
                            <i class="bi bi-palette"></i>
                        </div>

                        <div>

                            <small>
                                Shop Theme
                            </small>

                            <strong>

                                <?= htmlspecialchars(
                                    ucwords(
                                        str_replace(
                                            '-',
                                            ' ',
                                            $shopTheme
                                        )
                                    )
                                ) ?>

                            </strong>

                        </div>

                    </div>

                </div>

            </section>

        <?php endif; ?>


        <!-- =========================================================
         FARMER QUICK ACTIONS
         ========================================================= -->

        <?php if ($role === 'farmer'): ?>

            <section class="dashboard-section-card mb-4 mb-lg-5">

                <div class="section-heading">

                    <div>

                        <span class="section-kicker">
                            Seller Center
                        </span>

                        <h3 class="fw-bold mb-1">
                            Quick Actions
                        </h3>

                        <p class="text-muted mb-0">
                            Manage your farm marketplace activities.
                        </p>

                    </div>

                </div>


                <div class="dashboard-quick-actions">


                    <a href="/ani-mart/public/farmer/products/create" class="dashboard-action dashboard-action-eden">

                        <i class="bi bi-plus-circle"></i>

                        <span>
                            Add Product
                        </span>

                        <i class="bi bi-arrow-right action-arrow"></i>

                    </a>


                    <a href="/ani-mart/public/farmer/products" class="dashboard-action dashboard-action-lime">

                        <i class="bi bi-basket3"></i>

                        <span>
                            Manage Products
                        </span>

                        <i class="bi bi-arrow-right action-arrow"></i>

                    </a>


                    <a href="/ani-mart/public/farmer/orders" class="dashboard-action dashboard-action-eco">

                        <i class="bi bi-box-seam"></i>

                        <span>
                            Manage Orders
                        </span>

                        <i class="bi bi-arrow-right action-arrow"></i>

                    </a>


                    <a href="/ani-mart/public/seller-center/shops" class="dashboard-action dashboard-action-emerald">
                        <i class="bi bi-shop"></i>
                        <span>My Shop</span>
                        <i class="bi bi-arrow-right action-arrow"></i>
                    </a>


                    <a href="/ani-mart/public/seller-center/analytics" class="dashboard-action dashboard-action-rich">

                        <i class="bi bi-graph-up"></i>

                        <span>
                            Analytics
                        </span>

                        <i class="bi bi-arrow-right action-arrow"></i>

                    </a>

                </div>

            </section>


            <!-- =========================================================
         FARMER RECENT ORDERS
         ========================================================= -->

            <section class="dashboard-section-card mb-4 mb-lg-5">

                <div class="section-heading section-heading-actions">

                    <div>

                        <span class="section-kicker">
                            Transactions
                        </span>

                        <h3 class="fw-bold mb-1">
                            Recent Orders
                        </h3>

                        <p class="text-muted mb-0">
                            Latest orders from your customers.
                        </p>

                    </div>


                    <a href="/ani-mart/public/farmer/orders" class="btn btn-sm btn-outline-success rounded-pill">
                        View All
                    </a>

                </div>


                <?php if (!empty($recentFarmerOrders)): ?>

                    <div class="table-responsive latest-orders-table-wrap">

                        <table class="table align-middle latest-orders-table mb-0">

                            <thead>

                                <tr>
                                    <th>Order</th>
                                    <th>Customer</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>

                            </thead>


                            <tbody>

                                <?php foreach (
                                    $recentFarmerOrders as $order
                                ): ?>

                                    <?php

                                    $status = strtolower(
                                        (string) (
                                            $order->status ?? 'pending'
                                        )
                                    );

                                    $badge = match ($status) {

                                        'completed',
                                        'delivered'
                                        => 'success',

                                        'cancelled'
                                        => 'danger',

                                        'processing',
                                        'accepted',
                                        'preparing'
                                        => 'primary',

                                        default
                                        => 'warning',
                                    };

                                    ?>

                                    <tr>

                                        <td class="fw-semibold">

                                            #
                                            <?= htmlspecialchars(
                                                (string) (
                                                    $order->id ?? ''
                                                )
                                            ) ?>

                                        </td>


                                        <td>
                                            Customer
                                        </td>


                                        <td class="fw-semibold">

                                            ₱<?= number_format(
                                                (float) (
                                                    $order->total_amount ?? 0
                                                ),
                                                2
                                            ) ?>

                                        </td>


                                        <td>

                                            <span class="badge bg-<?= $badge ?>">

                                                <?= htmlspecialchars(
                                                    ucfirst(
                                                        str_replace(
                                                            '_',
                                                            ' ',
                                                            $status
                                                        )
                                                    )
                                                ) ?>

                                            </span>

                                        </td>


                                        <td>

                                            <?= date(
                                                'M d, Y',
                                                strtotime(
                                                    $order->created_at ?? 'now'
                                                )
                                            ) ?>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>


                <?php else: ?>

                    <div class="empty-state py-5">

                        <i class="bi bi-bag-x"></i>

                        <p>
                            No recent orders yet.
                        </p>

                    </div>

                <?php endif; ?>

            </section>


            <!-- =========================================================
         FARMER RECENT PRODUCTS
         ========================================================= -->

            <div class="farmer-recent-products-header d-flex justify-content-between align-items-center mt-5">

                <div>

                    <h2 class="fw-bold mb-2">
                        Recent Products
                    </h2>

                    <p class="text-muted mb-0">
                        Newly added products from your farm.
                    </p>

                </div>


                <a href="/ani-mart/public/farmer/products/create" class="btn btn-success rounded-pill px-4">
                    + Add Product
                </a>

            </div>


            <div class="row g-4" id="farmerProductsContainer">

                <?php if (!empty($products)): ?>

                    <?php foreach ($products as $product): ?>

                        <?php

                        $productId = (int) (
                            $product->id ?? 0
                        );

                        $approvalStatus =
                            $product->display_approval_status
                            ?? $product->approval_status
                            ?? 'approved';

                        $approvalClass = match (
                        $approvalStatus
                        ) {

                            'approved' =>
                                'bg-success-subtle text-success',

                            'needs_correction' =>
                                'bg-warning-subtle text-warning-emphasis',

                            'rejected' =>
                                'bg-danger-subtle text-danger',

                            default =>
                                'bg-info-subtle text-info-emphasis',
                        };


                        $productImage = !empty(
                            $product->image
                        )
                            ? $product->image
                            : '/ani-mart/public/assets/img/product-placeholder.svg';


                        $shopName =
                            $activeShop?->shop_name
                            ?? $activeShop?->name
                            ?? $product->shop_name
                            ?? $product->store_name
                            ?? $user['shop_name']
                            ?? $user['store_name']
                            ?? 'Farm Shop';


                        $rating = (float) (
                            $product->avg_rating ?? 0
                        );

                        $reviewCount = (int) (
                            $product->review_count ?? 0
                        );

                        ?>

                        <div class="col-12 col-xl-3 col-lg-4 col-md-6 farmer-product-item">

                            <div class="marketplace-card product-card h-100">


                                <!-- PRODUCT IMAGE -->

                                <div class="marketplace-card-carousel">

                                    <div class="carousel-inner">

                                        <div class="carousel-item active">

                                            <img src="<?= htmlspecialchars(
                                                $productImage
                                            ) ?>" class="d-block w-100 marketplace-image" alt="<?= htmlspecialchars(
                                                 $product->name
                                                 ?? 'Product'
                                             ) ?>">

                                        </div>

                                    </div>

                                </div>


                                <div class="marketplace-card-content">


                                    <!-- PRODUCT TOP -->

                                    <div class="marketplace-card-top">

                                        <span class="marketplace-category">

                                            <?= htmlspecialchars(
                                                $product->category_name
                                                ?? 'Other'
                                            ) ?>

                                        </span>


                                        <span class="marketplace-price">

                                            ₱<?= number_format(
                                                (float) (
                                                    $product->price ?? 0
                                                ),
                                                2
                                            ) ?>

                                        </span>

                                    </div>


                                    <!-- PRODUCT NAME -->

                                    <h5 class="marketplace-product-name">

                                        <?= htmlspecialchars(
                                            $product->name
                                            ?? 'Unnamed Product'
                                        ) ?>

                                    </h5>


                                    <!-- SELLER -->

                                    <div class="marketplace-seller">

                                        <i class="bi bi-shop"></i>

                                        <span>

                                            <?= htmlspecialchars(
                                                $shopName
                                            ) ?>

                                        </span>


                                        <?php if (!empty($product->verification_badge)): ?>

                                            <i class="bi <?= BadgeCalculationService::getBadgeIcon($product->verification_badge) ?> marketplace-verified"
                                                title="<?= htmlspecialchars(
                                                    BadgeCalculationService::getBadgeLabel(
                                                        $product->verification_badge
                                                    )
                                                ) ?>"
                                                aria-label="<?= htmlspecialchars(BadgeCalculationService::getBadgeLabel($product->verification_badge)) ?>"></i>

                                        <?php endif; ?>

                                    </div>


                                    <!-- APPROVAL STATUS -->

                                    <div class="farmer-product-status">

                                        <span class="badge <?= $approvalClass ?> rounded-pill text-capitalize">

                                            <?= htmlspecialchars(
                                                str_replace(
                                                    '_',
                                                    ' ',
                                                    $approvalStatus
                                                )
                                            ) ?>

                                        </span>

                                    </div>


                                    <!-- STOCK -->

                                    <div class="marketplace-stock">

                                        <?php if (
                                            (int) (
                                                $product->stock ?? 0
                                            ) > 0
                                        ): ?>

                                            <?php if (
                                                (int) $product->stock <= 3
                                            ): ?>

                                                <span class="stock-low">

                                                    Only
                                                    <?= (int) (
                                                        $product->stock
                                                    ) ?>
                                                    left

                                                </span>

                                            <?php else: ?>

                                                <span class="stock-available">

                                                    Available stock:
                                                    <?= (int) (
                                                        $product->stock
                                                    ) ?>

                                                </span>

                                            <?php endif; ?>


                                        <?php else: ?>

                                            <span class="stock-out">
                                                Out of stock
                                            </span>

                                        <?php endif; ?>

                                    </div>


                                    <!-- RATING -->

                                    <div class="marketplace-rating">

                                        <i class="bi bi-star-fill"></i>

                                        <strong>
                                            <?= number_format(
                                                $rating,
                                                1
                                            ) ?>
                                        </strong>

                                        <span>
                                            (<?= $reviewCount ?> Reviews)
                                        </span>

                                    </div>


                                    <!-- ACTIONS -->

                                    <div class="marketplace-card-actions product-management-actions">

                                        <a href="/ani-mart/public/farmer/products/edit?id=<?= $productId ?>"
                                            class="btn product-edit-btn">

                                            <i class="bi bi-pencil-square"></i>

                                            <span>
                                                Edit
                                            </span>

                                        </a>


                                        <a href="/ani-mart/public/farmer/products/delete?id=<?= $productId ?>"
                                            class="btn product-delete-btn"
                                            data-confirm="Are you sure you want to delete this product? This action cannot be undone."
                                            data-confirm-title="Delete product"
                                            data-confirm-text="Delete"
                                            data-confirm-class="btn-danger">

                                            <i class="bi bi-trash3"></i>

                                            <span>
                                                Delete
                                            </span>

                                        </a>

                                    </div>

                                </div>

                            </div>

                        </div>

                    <?php endforeach; ?>


                <?php else: ?>

                    <div class="col-12">

                        <div class="card border-0 shadow-sm rounded-4 p-5 text-center">

                            <h4 class="fw-bold mb-3">
                                No products yet
                            </h4>

                            <p class="text-muted mb-4">
                                Start selling your fresh farm products
                                on Ani-Mart.
                            </p>

                            <div>

                                <a href="/ani-mart/public/farmer/products/create" class="btn btn-success rounded-pill px-4">
                                    Add First Product
                                </a>

                            </div>

                        </div>

                    </div>

                <?php endif; ?>

            </div>

        <?php endif; ?>


        <!-- =========================================================
         ADMIN LATEST ORDERS + RECENT FARMERS
         ========================================================= -->

        <?php if ($role === 'admin'): ?>

            <section class="dashboard-bottom-grid">


                <!-- LATEST ORDERS -->

                <div class="dashboard-section-card latest-orders-card">

                    <div class="section-heading section-heading-actions">

                        <div>

                            <span class="section-kicker">
                                Transactions
                            </span>

                            <h4 class="fw-bold mb-1">
                                Latest Orders
                            </h4>

                            <p class="text-muted mb-0">
                                Customer identities are protected
                                with initials only.
                            </p>

                        </div>


                        <a href="/ani-mart/public/admin/orders" class="btn btn-sm btn-outline-success rounded-pill">
                            View All
                        </a>

                    </div>


                    <?php if (count($latestOrders)): ?>


                        <!-- DESKTOP -->

                        <div class="table-responsive latest-orders-table-wrap">

                            <table class="table align-middle latest-orders-table mb-0">

                                <thead>

                                    <tr>

                                        <th>
                                            Order #
                                        </th>

                                        <th>
                                            Customer
                                        </th>

                                        <th>
                                            Total
                                        </th>

                                        <th>
                                            Status
                                        </th>

                                        <th>
                                            Date
                                        </th>

                                    </tr>

                                </thead>


                                <tbody>

                                    <?php foreach (
                                        $latestOrders as $order
                                    ): ?>

                                        <?php

                                        $status = strtolower(
                                            (string) (
                                                $order->status
                                                ?? 'pending'
                                            )
                                        );

                                        $badge = match ($status) {

                                            'completed',
                                            'delivered'
                                            => 'success',

                                            'cancelled'
                                            => 'danger',

                                            'processing',
                                            'accepted',
                                            'preparing'
                                            => 'primary',

                                            default
                                            => 'warning',
                                        };

                                        $initials = $getInitials(
                                            $order->first_name ?? '',
                                            $order->last_name ?? ''
                                        );

                                        ?>

                                        <tr>

                                            <td class="fw-semibold">

                                                #
                                                <?= htmlspecialchars(
                                                    (string) (
                                                        $order->id ?? ''
                                                    )
                                                ) ?>

                                            </td>


                                            <td>

                                                <div class="customer-private">

                                                    <span class="customer-initials">

                                                        <?= htmlspecialchars(
                                                            $initials
                                                        ) ?>

                                                    </span>

                                                    <span class="customer-private-label">

                                                        Customer
                                                        <?= htmlspecialchars(
                                                            $initials
                                                        ) ?>

                                                    </span>

                                                </div>

                                            </td>


                                            <td class="fw-semibold">

                                                ₱<?= number_format(
                                                    (float) (
                                                        $order->total_amount
                                                        ?? 0
                                                    ),
                                                    2
                                                ) ?>

                                            </td>


                                            <td>

                                                <span class="badge bg-<?= $badge ?>">

                                                    <?= htmlspecialchars(
                                                        ucfirst(
                                                            $status
                                                        )
                                                    ) ?>

                                                </span>

                                            </td>


                                            <td>

                                                <?= date(
                                                    'M d, Y',
                                                    strtotime(
                                                        $order->created_at
                                                        ?? 'now'
                                                    )
                                                ) ?>

                                            </td>

                                        </tr>

                                    <?php endforeach; ?>

                                </tbody>

                            </table>

                        </div>


                        <!-- MOBILE -->

                        <div class="latest-orders-mobile">

                            <?php foreach (
                                $latestOrders as $order
                            ): ?>

                                <?php

                                $status = strtolower(
                                    (string) (
                                        $order->status
                                        ?? 'pending'
                                    )
                                );

                                $badge = match ($status) {

                                    'completed',
                                    'delivered'
                                    => 'success',

                                    'cancelled'
                                    => 'danger',

                                    'processing',
                                    'accepted',
                                    'preparing'
                                    => 'primary',

                                    default
                                    => 'warning',
                                };

                                $initials = $getInitials(
                                    $order->first_name ?? '',
                                    $order->last_name ?? ''
                                );

                                ?>

                                <article class="order-mobile-card">

                                    <div class="order-mobile-top">

                                        <div>

                                            <span class="order-number">

                                                #
                                                <?= htmlspecialchars(
                                                    (string) (
                                                        $order->id ?? ''
                                                    )
                                                ) ?>

                                            </span>

                                            <span class="order-private-note">
                                                Private customer
                                            </span>

                                        </div>


                                        <span class="badge bg-<?= $badge ?>">
                                            <?= htmlspecialchars(
                                                ucfirst($status)
                                            ) ?>
                                        </span>

                                    </div>


                                    <div class="order-mobile-customer">

                                        <span class="customer-initials customer-initials-lg">
                                            <?= htmlspecialchars(
                                                $initials
                                            ) ?>
                                        </span>


                                        <div>

                                            <strong>
                                                Customer
                                                <?= htmlspecialchars(
                                                    $initials
                                                ) ?>
                                            </strong>

                                            <small>
                                                Personal details hidden
                                                for privacy
                                            </small>

                                        </div>

                                    </div>


                                    <div class="order-mobile-meta">

                                        <div>

                                            <small>
                                                Total
                                            </small>

                                            <strong>

                                                ₱<?= number_format(
                                                    (float) (
                                                        $order->total_amount
                                                        ?? 0
                                                    ),
                                                    2
                                                ) ?>

                                            </strong>

                                        </div>


                                        <div>

                                            <small>
                                                Date
                                            </small>

                                            <strong>

                                                <?= date(
                                                    'M d, Y',
                                                    strtotime(
                                                        $order->created_at
                                                        ?? 'now'
                                                    )
                                                ) ?>

                                            </strong>

                                        </div>

                                    </div>

                                </article>

                            <?php endforeach; ?>

                        </div>


                    <?php else: ?>

                        <div class="empty-state py-5">

                            <i class="bi bi-bag-x"></i>

                            <p>
                                No orders yet.
                            </p>

                        </div>

                    <?php endif; ?>

                </div>


                <!-- RECENT FARMERS -->

                <div class="dashboard-section-card recent-farmers-card">

                    <div class="section-heading">

                        <div>

                            <span class="section-kicker">
                                Community
                            </span>

                            <h4 class="fw-bold mb-1">
                                Recent Farmers
                            </h4>

                        </div>

                    </div>


                    <?php if (count($recentFarmers)): ?>

                        <div class="recent-farmers-list">

                            <?php foreach (
                                $recentFarmers as $farmer
                            ): ?>

                                <div class="recent-farmer">

                                    <div class="d-flex align-items-center gap-3">

                                        <div class="farmer-avatar">

                                            <?= htmlspecialchars(
                                                strtoupper(
                                                    substr(
                                                        $farmer->first_name
                                                        ?? 'F',
                                                        0,
                                                        1
                                                    )
                                                )
                                            ) ?>

                                        </div>


                                        <div class="farmer-info">

                                            <div class="fw-bold">

                                                <?= htmlspecialchars(
                                                    $farmer->shop_name
                                                    ?? 'No Shop'
                                                ) ?>

                                            </div>

                                            <small class="text-muted">

                                                <?= htmlspecialchars(
                                                    trim(
                                                        ($farmer->first_name ?? '')
                                                        . ' '
                                                        . ($farmer->last_name ?? '')
                                                    )
                                                ) ?>

                                            </small>

                                        </div>

                                    </div>


                                    <span class="badge bg-success">
                                        Approved
                                    </span>

                                </div>

                            <?php endforeach; ?>

                        </div>


                    <?php else: ?>

                        <div class="empty-state py-5">

                            <i class="bi bi-shop"></i>

                            <p>
                                No farmers found.
                            </p>

                        </div>

                    <?php endif; ?>

                </div>

            </section>

        <?php endif; ?>


        <!-- =========================================================
         CUSTOMER QUICK ACTIONS
         ========================================================= -->

        <?php if ($role === 'customer'): ?>

            <section class="dashboard-section-card mb-4 mb-lg-5">

                <div class="section-heading">

                    <div>

                        <span class="section-kicker">
                            Shopping
                        </span>

                        <h3 class="fw-bold mb-1">
                            Quick Actions
                        </h3>

                        <p class="text-muted mb-0">
                            Quickly access your Ani-Mart activities.
                        </p>

                    </div>

                </div>


                <div class="dashboard-quick-actions">


                    <a href="/ani-mart/public/marketplace" class="dashboard-action dashboard-action-eden">

                        <i class="bi bi-shop"></i>

                        <span>
                            Browse Marketplace
                        </span>

                        <i class="bi bi-arrow-right action-arrow"></i>

                    </a>


                    <a href="/ani-mart/public/customer/orders" class="dashboard-action dashboard-action-lime">

                        <i class="bi bi-bag-check"></i>

                        <span>
                            My Orders
                        </span>

                        <i class="bi bi-arrow-right action-arrow"></i>

                    </a>


                    <a href="/ani-mart/public/reviews" class="dashboard-action dashboard-action-eco">

                        <i class="bi bi-star"></i>

                        <span>
                            My Reviews
                        </span>

                        <i class="bi bi-arrow-right action-arrow"></i>

                    </a>

                </div>

            </section>


            <!-- =========================================================
         CUSTOMER RECENT ORDERS
         ========================================================= -->

            <section class="dashboard-section-card mb-4 mb-lg-5">

                <div class="section-heading">

                    <div>

                        <span class="section-kicker">
                            Shopping
                        </span>

                        <h3 class="fw-bold mb-1">
                            Recent Orders
                        </h3>

                        <p class="text-muted mb-0">
                            Latest marketplace activity.
                        </p>

                    </div>


                    <a href="/ani-mart/public/customer/orders" class="btn btn-sm btn-outline-success rounded-pill">
                        View All
                    </a>

                </div>


                <div class="table-responsive">

                    <table class="table align-middle">

                        <thead>

                            <tr>

                                <th>
                                    Order ID
                                </th>

                                <th>
                                    Status
                                </th>

                                <th>
                                    Total
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                            <?php if (count($recentOrders) > 0): ?>

                                <?php foreach (
                                    $recentOrders as $order
                                ): ?>

                                    <?php

                                    $orderStatus = strtolower(
                                        (string) (
                                            $order->status
                                            ?? 'pending'
                                        )
                                    );

                                    $orderBadge = match (
                                    $orderStatus
                                    ) {

                                        'pending' =>
                                            'bg-warning text-dark',

                                        'accepted' =>
                                            'bg-primary',

                                        'preparing' =>
                                            'bg-info text-dark',

                                        'out_for_delivery' =>
                                            'bg-secondary',

                                        'delivered',
                                        'completed' =>
                                            'bg-success',

                                        'cancelled' =>
                                            'bg-danger',

                                        default =>
                                            'bg-light text-dark',
                                    };

                                    ?>

                                    <tr>

                                        <td>

                                            <?= htmlspecialchars(
                                                $order->order_number
                                                ?? (
                                                    '#'
                                                    . (
                                                        $order->id
                                                        ?? ''
                                                    )
                                                )
                                            ) ?>

                                        </td>


                                        <td>

                                            <span class="badge <?= $orderBadge ?>">

                                                <?= htmlspecialchars(
                                                    ucfirst(
                                                        str_replace(
                                                            '_',
                                                            ' ',
                                                            $orderStatus
                                                        )
                                                    )
                                                ) ?>

                                            </span>

                                        </td>


                                        <td>

                                            ₱<?= number_format(
                                                (float) (
                                                    $order->total_amount
                                                    ?? 0
                                                ),
                                                2
                                            ) ?>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>


                            <?php else: ?>

                                <tr>

                                    <td colspan="3" class="text-center py-5 text-muted">
                                        No orders found.
                                    </td>

                                </tr>

                            <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </section>

        <?php endif; ?>


    </div>

<?php endif; ?>


<?php

$content = ob_get_clean();

$pageCss =
    '/ani-mart/public/assets/css/pages/dashboard.css?v=' .
    filemtime(__DIR__ . '/../../../public/assets/css/pages/dashboard.css');

ob_start();
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
    (() => {
        if (typeof Chart === 'undefined') return;
        const analytics = <?= json_encode($dashboardAnalytics, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        const trendCanvas = document.getElementById('dashboardTrendChart');
        const dashboard = document.querySelector('.<?= $role === 'farmer' ? 'farmer-dashboard' : ($role === 'admin' ? 'admin-dashboard' : 'customer-dashboard') ?>');
        const dashboardStyles = getComputedStyle(dashboard);
        const accent = dashboardStyles.getPropertyValue('<?= $role === 'farmer' ? '--shop-accent' : '--primary' ?>').trim() || '#1f7a4d';
        const secondary = dashboardStyles.getPropertyValue('--secondary').trim() || '#0f766e';
        const highlight = dashboardStyles.getPropertyValue('--accent').trim() || '#d97706';
        const border = dashboardStyles.getPropertyValue('--border').trim() || '#dde5dc';
        const money = new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' });

        if (trendCanvas) {
            new Chart(trendCanvas, {
                data: {
                    labels: analytics.trend.map(item => item.label),
                    datasets: [
                        {
                            type: 'bar',
                            label: analytics.amount_label,
                            data: analytics.trend.map(item => item.amount),
                            backgroundColor: accent + '2e',
                            borderColor: accent,
                            borderWidth: 1.5,
                            borderRadius: 8,
                            yAxisID: 'amount'
                        },
                        {
                            type: 'line',
                            label: 'Orders',
                            data: analytics.trend.map(item => item.orders),
                            borderColor: highlight,
                            backgroundColor: highlight,
                            pointRadius: 4,
                            pointHoverRadius: 6,
                            tension: .35,
                            yAxisID: 'orders'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8, padding: 18 } },
                        tooltip: { callbacks: { label: context => context.dataset.yAxisID === 'amount' ? `${context.dataset.label}: ${money.format(context.raw)}` : `Orders: ${context.raw}` } }
                    },
                    scales: {
                        amount: { beginAtZero: true, position: 'left', grid: { color: border }, ticks: { callback: value => value >= 1000 ? `₱${(value / 1000).toFixed(1)}k` : `₱${value}` } },
                        orders: { beginAtZero: true, position: 'right', grid: { display: false }, ticks: { precision: 0 } }
                    }
                }
            });
        }

        const statusCanvas = document.getElementById('dashboardStatusChart');
        if (statusCanvas && analytics.statuses.length) {
            new Chart(statusCanvas, {
                type: 'doughnut',
                data: {
                    labels: analytics.statuses.map(item => item.label),
                    datasets: [{ data: analytics.statuses.map(item => item.total), backgroundColor: [accent, highlight, secondary, '#2878a8', '#7952a8', '#d94a3a', '#8a6845'], borderWidth: 0, hoverOffset: 4 }]
                },
                options: { responsive: true, maintainAspectRatio: false, cutout: '68%', plugins: { legend: { display: false } } }
            });
        }
    })();
</script>
<?php
$pageScript = ob_get_clean();

require ANI_MART_ROOT . '/app/Views/layouts/main.php';
