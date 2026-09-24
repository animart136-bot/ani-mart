<?php

$title = "Admin Analytics";
$bodyClass = "admin-page admin-analytics-page";
$pageStyles[] = '/ani-mart/public/assets/css/pages/admin-analytics.css?v=' .
    filemtime(__DIR__ . '/../../../../public/assets/css/pages/admin-analytics.css');

$analytics = $analytics ?? [];

$overview = $analytics['overview'] ?? [];
$sales = $analytics['sales'] ?? [];
$products = $analytics['products'] ?? [];
$farmers = $analytics['farmers'] ?? [];
$customers = $analytics['customers'] ?? [];
$complaints = $analytics['complaints'] ?? [];
$transparency = $analytics['transparency'] ?? [];
$health = $analytics['health'] ?? ['score' => 0, 'label' => 'Critical'];
$charts = $analytics['charts'] ?? [];

$peso = static fn ($value): string => '₱' . number_format((float) $value, 2);

$displayName = static function ($row): string {
    $fullName = trim(implode(' ', array_filter([
        trim((string) ($row->first_name ?? '')),
        trim((string) ($row->last_name ?? ''))
    ])));

    if ($fullName !== '') {
        return $fullName;
    }

    $shopName = trim((string) ($row->shop_name ?? ''));
    if ($shopName !== '') {
        return $shopName;
    }

    $email = trim((string) ($row->email ?? ''));
    if ($email !== '') {
        return strstr($email, '@', true) ?: $email;
    }

    return 'Unknown user';
};

$showMoreButton = static function (string $listId, int $total): void {
    if ($total <= 3) {
        return;
    }

    echo '<button type="button" class="analytics-show-more" data-analytics-show-more aria-controls="' . htmlspecialchars($listId) . '" aria-expanded="false">';
    echo '<span class="analytics-show-more-label">Show more</span>';
    echo '<span class="analytics-show-more-count">+' . ($total - 3) . '</span>';
    echo '<i class="bi bi-chevron-down" aria-hidden="true"></i>';
    echo '</button>';
};

$productList = static function ($rows, string $listId, string $valueField, string $emptyText, string $badgeClass = '') use ($showMoreButton): void {
    if (!count($rows)) {
        echo '<p class="analytics-empty-copy mb-0">' . htmlspecialchars($emptyText) . '</p>';
        return;
    }

    echo '<div id="' . htmlspecialchars($listId) . '">';
    foreach ($rows as $index => $row) {
        $hidden = $index >= 3 ? ' hidden' : '';
        $valueClass = $badgeClass !== '' ? 'badge ' . $badgeClass : 'text-muted';

        echo '<div class="analytics-collapsible-row d-flex justify-content-between border-bottom py-2"' . $hidden . '>';
        echo '<span>' . htmlspecialchars($row->name ?? 'Product') . '</span>';
        echo '<span class="' . htmlspecialchars($valueClass) . '">' . (int) ($row->{$valueField} ?? 0) . '</span>';
        echo '</div>';
    }
    echo '</div>';

    $showMoreButton($listId, count($rows));
};

$miniList = static function ($rows, string $listId, string $valueField, string $emptyText, bool $money = false) use ($peso, $displayName, $showMoreButton): void {
    if (!count($rows)) {
        echo '<p class="analytics-empty-copy mb-0">' . htmlspecialchars($emptyText) . '</p>';
        return;
    }

    echo '<div id="' . htmlspecialchars($listId) . '">';
    foreach ($rows as $index => $row) {
        $value =
            $money
                ? $peso($row->{$valueField} ?? 0)
                : number_format((float) ($row->{$valueField} ?? 0));
        $hidden = $index >= 3 ? ' hidden' : '';

        echo '<div class="analytics-list-row analytics-collapsible-row"' . $hidden . '>';
        echo '<span class="analytics-person-name">' . htmlspecialchars($displayName($row)) . '</span>';
        echo '<span class="text-muted">' . htmlspecialchars($value) . '</span>';
        echo '</div>';
    }
    echo '</div>';

    $showMoreButton($listId, count($rows));
};

ob_start();

?>

<main class="container admin-analytics-shell app-page-spacing">

    <section class="analytics-hero mb-4" aria-labelledby="analytics-title">
        <div class="analytics-hero-copy">
            <span class="analytics-eyebrow">Ani-Mart Analytics</span>
            <h1 id="analytics-title">Marketplace Insights</h1>
            <p>Monitor verified marketplace activity across orders, products, customers, sellers, and resolutions.</p>
            <a href="/ani-mart/public/dashboard" class="analytics-back-link"><i class="bi bi-arrow-left" aria-hidden="true"></i> Admin dashboard</a>
        </div>
        <div class="analytics-hero-summary">
            <span>Marketplace health</span>
            <strong><?= (int) $health['score'] ?><small>/100</small></strong>
            <div class="analytics-health-track" aria-hidden="true"><i style="width: <?= max(0, min(100, (int) $health['score'])) ?>%"></i></div>
            <p><?= htmlspecialchars($health['label']) ?> · Updated <?= date('M j, Y') ?></p>
        </div>
    </section>

    <section class="analytics-overview-section" aria-labelledby="analytics-overview-title">
        <div class="analytics-section-title">
            <div>
                <span>Live database totals</span>
                <h2 id="analytics-overview-title">Marketplace overview</h2>
            </div>
        </div>

    <div class="row g-3 analytics-overview-grid">
        <?php
        $overviewCards = [
            ['Total Revenue', $peso($overview['total_revenue'] ?? 0), 'bi-cash-stack', 'success'],
            ['Total Orders', number_format($overview['total_orders'] ?? 0), 'bi-bag-check', 'primary'],
            ['Active Farmers', number_format($overview['active_farmers'] ?? 0), 'bi-shop', 'success'],
            ['Marketplace Customers', number_format($overview['active_customers'] ?? 0), 'bi-people', 'info'],
            ['Active Products', number_format($overview['active_products'] ?? 0), 'bi-basket', 'warning']
        ];
        ?>

        <?php foreach ($overviewCards as $card): ?>
            <div class="col-xl col-lg-4 col-md-6">
                <div class="admin-stat-card h-100">
                    <div class="stat-icon bg-<?= $card[3] ?>-subtle text-<?= $card[3] ?>">
                        <i class="bi <?= $card[2] ?>"></i>
                    </div>
                    <div>
                        <small class="text-muted d-block"><?= $card[0] ?></small>
                        <h2 class="fw-bold mb-0"><?= $card[1] ?></h2>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    </section>

    <section class="analytics-section-block" aria-labelledby="analytics-trends-title">
        <div class="analytics-section-title">
            <span>Trends</span>
            <h2 id="analytics-trends-title">Marketplace activity</h2>
        </div>
        <div class="row g-4 analytics-primary-row">
        <div class="col-lg-6">
            <div class="card analytics-card border-0 shadow-sm rounded-5 h-100">
                <div class="card-body p-4">
                    <h4 class="fw-bold mb-4">Monthly Revenue Trend</h4>
                    <canvas id="monthlyRevenueChart" height="110"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card analytics-card border-0 shadow-sm rounded-5 h-100">
                <div class="card-body p-4">
                    <h4 class="fw-bold mb-4">Orders Per Month</h4>
                    <canvas id="ordersPerMonthChart" height="110"></canvas>
                </div>
            </div>
        </div>
        </div>
    </section>

    <section class="analytics-section-block" aria-labelledby="analytics-commerce-title">
        <div class="analytics-section-title">
            <span>Commerce</span>
            <h2 id="analytics-commerce-title">Sales and customers</h2>
        </div>
        <div class="row g-4 analytics-summary-row">
        <div class="col-lg-6">
            <div class="card analytics-card border-0 shadow-sm rounded-5 h-100">
                <div class="card-body p-4">
                    <h4 class="fw-bold mb-4">Sales Analytics</h4>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span>Revenue Today</span>
                        <strong><?= $peso($sales['revenue_today'] ?? 0) ?></strong>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span>Revenue This Week</span>
                        <strong><?= $peso($sales['revenue_week'] ?? 0) ?></strong>
                    </div>
                    <div class="d-flex justify-content-between py-2">
                        <span>Revenue This Month</span>
                        <strong><?= $peso($sales['revenue_month'] ?? 0) ?></strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card analytics-card border-0 shadow-sm rounded-5 h-100">
                <div class="card-body p-4">
                    <h4 class="fw-bold mb-4">Customer Analytics</h4>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span>New Marketplace Customers</span>
                        <strong><?= number_format($customers['new_customers_month'] ?? 0) ?></strong>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span>Repeat Customers</span>
                        <strong><?= number_format($customers['repeat_customers'] ?? 0) ?></strong>
                    </div>
                    <h6 class="fw-bold mt-4">Customers with Most Orders</h6>
                    <?php $topCustomers = $customers['most_orders'] ?? []; ?>
                    <?php if (!empty($topCustomers)): ?>
                        <div id="analyticsTopCustomers">
                        <?php foreach ($topCustomers as $customerIndex => $customer): ?>
                            <div class="analytics-customer-row analytics-collapsible-row d-flex justify-content-between border-bottom py-2"<?= $customerIndex >= 3 ? ' hidden' : '' ?>>
                                <span class="analytics-person-name"><?= htmlspecialchars($displayName($customer)) ?></span>
                                <span class="text-muted"><?= (int) $customer->total_orders ?></span>
                            </div>
                        <?php endforeach; ?>
                        </div>
                        <?php if (count($topCustomers) > 3): ?>
                            <button
                                type="button"
                                class="analytics-show-more"
                                data-analytics-show-more
                                aria-controls="analyticsTopCustomers"
                                aria-expanded="false"
                            >
                                <span class="analytics-show-more-label">Show more</span>
                                <span class="analytics-show-more-count">+<?= count($topCustomers) - 3 ?></span>
                                <i class="bi bi-chevron-down" aria-hidden="true"></i>
                            </button>
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="analytics-empty-copy mb-0">No customer order history yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        </div>
    </section>

    <section class="analytics-section-block" aria-labelledby="analytics-performance-title">
        <div class="analytics-section-title">
            <span>Performance</span>
            <h2 id="analytics-performance-title">Products and farmers</h2>
        </div>
        <div class="row g-4 analytics-breakdown-row">
        <div class="col-lg-6">
            <div class="card analytics-card border-0 shadow-sm rounded-5">
                <div class="card-body p-4">
                    <h4 class="fw-bold mb-4">Product Analytics</h4>
                    <div class="row g-3 analytics-breakdown-grid">
                        <div class="col-md-6">
                            <h6 class="fw-bold">Top 10 Best Selling Products</h6>
                            <?php $productList($products['top_selling'] ?? [], 'analyticsTopSellingProducts', 'total_sold', 'No product sales yet.'); ?>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold">Lowest Selling Products</h6>
                            <?php $productList($products['lowest_selling'] ?? [], 'analyticsLowestSellingProducts', 'total_sold', 'No product sales yet.'); ?>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold">Out of Stock Products</h6>
                            <?php $productList($products['out_of_stock'] ?? [], 'analyticsOutOfStockProducts', 'stock', 'All products are currently stocked.', 'bg-danger'); ?>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold">Near Out of Stock</h6>
                            <?php $productList($products['near_out_of_stock'] ?? [], 'analyticsNearOutOfStockProducts', 'stock', 'No low-stock products.', 'bg-warning text-dark'); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card analytics-card border-0 shadow-sm rounded-5">
                <div class="card-body p-4">
                    <h4 class="fw-bold mb-4">Farmer Analytics</h4>
                    <div class="row g-3 analytics-breakdown-grid">
                        <div class="col-md-6">
                            <h6 class="fw-bold">Highest Revenue Farmers</h6>
                            <?php $miniList($farmers['highest_revenue'] ?? [], 'analyticsHighestRevenueFarmers', 'revenue', 'No farmer revenue yet.', true); ?>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold">Highest Rated Farmers</h6>
                            <?php $miniList($farmers['highest_rated'] ?? [], 'analyticsHighestRatedFarmers', 'rating', 'No ratings yet.'); ?>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold">Most Complaints</h6>
                            <?php $miniList($farmers['most_complaints'] ?? [], 'analyticsMostComplaintsFarmers', 'complaints', 'No complaints yet.'); ?>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold">Highest Trust Score</h6>
                            <?php $miniList($farmers['highest_trust'] ?? [], 'analyticsHighestTrustFarmers', 'trust_score', 'No farmers yet.'); ?>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold">Lowest Trust Score</h6>
                            <?php $miniList($farmers['lowest_trust'] ?? [], 'analyticsLowestTrustFarmers', 'trust_score', 'No farmers yet.'); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </section>

    <section class="analytics-section-block" aria-labelledby="analytics-risk-title">
        <div class="analytics-section-title">
            <span>Quality and risk</span>
            <h2 id="analytics-risk-title">Complaints and transparency</h2>
        </div>
        <div class="row g-4 analytics-risk-row">
        <div class="col-xl-4 col-lg-6">
            <div class="card analytics-card border-0 shadow-sm rounded-5 h-100">
                <div class="card-body p-4">
                    <h4 class="fw-bold mb-4">Complaint Analytics</h4>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span>Total Complaints</span>
                        <strong><?= number_format($complaints['total'] ?? 0) ?></strong>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span>Open Complaints</span>
                        <strong><?= number_format($complaints['pending'] ?? 0) ?></strong>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span>Resolved Complaints</span>
                        <strong><?= number_format($complaints['resolved'] ?? 0) ?></strong>
                    </div>
                    <div class="pt-3">
                        <small class="text-muted d-block">Most Reported Seller</small>
                        <strong>
                            <?= !empty($complaints['most_reported_farmer'])
                                ? htmlspecialchars($displayName($complaints['most_reported_farmer']))
                                : 'No reports yet' ?>
                        </strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-lg-6">
            <div class="card analytics-card border-0 shadow-sm rounded-5 h-100">
                <div class="card-body p-4">
                    <h4 class="fw-bold mb-4">Transparency Analytics</h4>
                    <div class="d-flex justify-content-between border-bottom py-2">
                        <span>Harvest Date Changes This Month</span>
                        <strong><?= number_format($transparency['harvest_changes_month'] ?? 0) ?></strong>
                    </div>
                    <div class="d-flex justify-content-between border-bottom py-2">
                        <span>Average Trust Score</span>
                        <strong><?= number_format($transparency['average_trust_score'] ?? 0, 1) ?></strong>
                    </div>
                    <div class="d-flex justify-content-between border-bottom py-2">
                        <span>Suspicious Farmers</span>
                        <strong><?= number_format($transparency['suspicious_farmers'] ?? 0) ?></strong>
                    </div>
                    <div class="d-flex justify-content-between py-2">
                        <span>Verified Products</span>
                        <strong><?= number_format($transparency['verification_summary']['verified'] ?? 0) ?></strong>
                    </div>
                    <div class="d-flex justify-content-between py-2">
                        <span>Unverified Products</span>
                        <strong><?= number_format($transparency['verification_summary']['unverified'] ?? 0) ?></strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-lg-12">
            <div class="card analytics-card analytics-chart-card border-0 shadow-sm rounded-5 h-100">
                <div class="card-body p-4">
                    <h4 class="fw-bold mb-4">Complaint Categories</h4>
                    <canvas id="complaintCategoriesChart" height="160"></canvas>
                </div>
            </div>
        </div>
        </div>
    </section>

    <section class="analytics-section-block analytics-supporting-section" aria-labelledby="analytics-supporting-title">
        <div class="analytics-section-title">
            <span>Breakdowns</span>
            <h2 id="analytics-supporting-title">Supporting insights</h2>
        </div>
        <div class="row g-4 mb-5 analytics-chart-grid">
            <div class="col-lg-6">
                <div class="card analytics-card analytics-chart-card border-0 shadow-sm rounded-5 h-100">
                    <div class="card-body p-4">
                        <h4 class="fw-bold mb-4">Farmer Trust Distribution</h4>
                        <canvas id="trustDistributionChart" height="150"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card analytics-card analytics-chart-card border-0 shadow-sm rounded-5 h-100">
                    <div class="card-body p-4">
                        <h4 class="fw-bold mb-4">Top Selling Categories</h4>
                        <canvas id="topCategoriesChart" height="150"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </section>

</main>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    const chartData = <?= json_encode($charts, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

    document.querySelectorAll('[data-analytics-show-more]').forEach((button) => {
        button.addEventListener('click', () => {
            const list = document.getElementById(button.getAttribute('aria-controls'));
            const expanded = button.getAttribute('aria-expanded') === 'true';

            if (!list) {
                return;
            }

            list.querySelectorAll('.analytics-collapsible-row:nth-child(n + 4)').forEach((row) => {
                row.hidden = expanded;
            });

            button.setAttribute('aria-expanded', String(!expanded));
            button.querySelector('.analytics-show-more-label').textContent = expanded ? 'Show more' : 'Show less';
        });
    });

    if (typeof Chart === 'undefined') {
        document.querySelectorAll('.analytics-card canvas').forEach((canvas) => {
            const message = document.createElement('p');
            message.className = 'analytics-empty-copy mb-0';
            message.textContent = 'Chart preview is unavailable. The live totals above are still current.';
            canvas.replaceWith(message);
        });
    }

    function buildChart(id, type, labels, values, options = {}) {
        const element = document.getElementById(id);

        if (!element || typeof Chart === 'undefined') {
            return;
        }

        const skeletonId = `${id}-skeleton`;
        const skeleton = document.createElement('section');
        skeleton.id = skeletonId;
        skeleton.className = 'skeleton-chart skeleton-chart-overlay';
        skeleton.setAttribute('role', 'status');
        skeleton.setAttribute('aria-label', 'Loading chart');
        skeleton.innerHTML = '<div class="skeleton-stack"><span class="skeleton skeleton-title"></span><span class="skeleton skeleton-subtitle"></span></div><div class="skeleton-chart-bars"><span class="skeleton"></span><span class="skeleton"></span><span class="skeleton"></span><span class="skeleton"></span><span class="skeleton"></span><span class="skeleton"></span></div>';
        element.parentElement.style.position = 'relative';
        element.before(skeleton);

        return new Chart(element, {
            type,
            data: {
                labels,
                datasets: [{
                    data: values,
                    label: options.label || '',
                    borderColor: options.borderColor || '#14532d',
                    backgroundColor: options.backgroundColor || [
                        '#198754',
                        '#2ab673',
                        '#7ac7a5',
                        '#1d5c49',
                        '#58b98b',
                        '#9ad0b8'
                    ],
                    tension: 0.35
                }]
            },
            options: {
                indexAxis: options.indexAxis || 'x',
                responsive: true,
                animation: {
                    onComplete: function () {
                        hideSkeleton(skeletonId);
                    }
                },
                plugins: {
                    legend: {
                        display: options.legend ?? false
                    }
                },
                scales: options.scales ?? {}
            }
        });
    }

    buildChart(
        'monthlyRevenueChart',
        'line',
        chartData.monthly_revenue?.labels || [],
        chartData.monthly_revenue?.values || [],
        {
            label: 'Revenue',
            backgroundColor: 'rgba(25, 135, 84, 0.12)',
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    );

    buildChart(
        'ordersPerMonthChart',
        'bar',
        chartData.orders_per_month?.labels || [],
        chartData.orders_per_month?.values || [],
        {
            label: 'Orders',
            backgroundColor: '#198754',
            borderColor: '#198754'
        }
    );

    buildChart(
        'complaintCategoriesChart',
        'pie',
        chartData.complaint_categories?.labels || [],
        chartData.complaint_categories?.values || [],
        {
            legend: true
        }
    );

    buildChart(
        'trustDistributionChart',
        'doughnut',
        chartData.farmer_trust_distribution?.labels || [],
        chartData.farmer_trust_distribution?.values || [],
        {
            legend: true
        }
    );

    buildChart(
        'topCategoriesChart',
        'bar',
        chartData.top_selling_categories?.labels || [],
        chartData.top_selling_categories?.values || [],
        {
            indexAxis: 'y',
            label: 'Units Sold',
            backgroundColor: '#198754'
        }
    );
</script>

<?php

$content = ob_get_clean();

require ANI_MART_ROOT . '/app/Views/layouts/main.php';

?>
