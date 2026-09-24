<?php

$title = 'Shop Analytics';

$pageStyles[] = '/ani-mart/public/assets/css/pages/seller-analytics.css?v=' . filemtime(__DIR__ . '/../../../../public/assets/css/pages/seller-analytics.css');

ob_start();

$activeShop = $activeShop ?? (object) [];
$shopThemes = \App\Services\SellerProfileService::themes();
$savedThemeKey = $activeShop->shop_theme ?? 'fresh-farm';
if (!isset($shopThemes[$savedThemeKey])) {
    $savedThemeKey = 'fresh-farm';
}
$shopTheme = $shopThemes[$savedThemeKey];
$ordersSummary = $ordersSummary ?? null;
$productSummary = $productSummary ?? null;
$ratingSummary = $ratingSummary ?? null;
$complaintsByStatus = $complaintsByStatus ?? [];
$analyticsTrend = $analyticsTrend ?? [];
$topProducts = $topProducts ?? [];
$customerAnalytics = $customerAnalytics ?? (object) [];
$financialRecords = $financialRecords ?? [];

$startDate = $startDate ?? date('Y-m-d', strtotime('-29 days'));
$endDate = $endDate ?? date('Y-m-d');

$trendMax = max(
    1,
    ...array_map(
        static fn($row) => (float) ($row->revenue ?? 0),
        $analyticsTrend
    )
);

$totalRevenue = (float) ($ordersSummary->total_revenue ?? 0);
$totalOrders = (int) ($ordersSummary->total_orders ?? 0);
$totalProducts = (int) ($productSummary->total_products ?? 0);
$avgRating = (float) ($ratingSummary->avg_rating ?? 0);

$uniqueCustomers = (int) ($customerAnalytics->unique_customers ?? 0);
$returningCustomers = (int) ($customerAnalytics->returning_customers ?? 0);
$productsPurchased = (int) ($ordersSummary->products_purchased ?? 0);
$grossSales = (float) ($ordersSummary->gross_sales ?? $totalRevenue);
$cancelledOrders = (int) ($ordersSummary->cancelled_orders ?? 0);
$cancelledValue = (float) ($ordersSummary->cancelled_order_value ?? 0);
$returnedOrders = (int) ($ordersSummary->returned_orders ?? 0);
$refundedOrders = (int) ($ordersSummary->refunded_orders ?? 0);
$refundedAmount = (float) ($ordersSummary->refunded_amount ?? 0);
$deliveryFees = (float) ($ordersSummary->delivery_fees ?? 0);
$netEarnings = (float) ($ordersSummary->net_earnings ?? $grossSales);

$averageOrderValue = $totalOrders > 0
    ? $totalRevenue / $totalOrders
    : 0;

$returningRate = $uniqueCustomers > 0
    ? ($returningCustomers / $uniqueCustomers) * 100
    : 0;

    /* =========================================================
   SMART PRODUCT INSIGHTS
   ========================================================= */

$bestSeller = null;
$focusProduct = null;
$focusReason = '';
$focusAction = '';

if (!empty($topProducts)) {

    // Best seller = highest units sold
    $bestSeller = $topProducts[0];

    foreach ($topProducts as $product) {

        if (
            (int) ($product->units ?? 0)
            >
            (int) ($bestSeller->units ?? 0)
        ) {
            $bestSeller = $product;
        }
    }


    /*
     * Find a product that deserves attention.
     *
     * Priority:
     * 1. Good sales + low stock
     * 2. Good revenue
     * 3. Some sales but room for improvement
     */

    foreach ($topProducts as $product) {

        $units = (int) ($product->units ?? 0);
        $stock = (int) ($product->stock ?? 0);
        $revenue = (float) ($product->revenue ?? 0);

        if ($units > 0 && $stock <= 5) {

            $focusProduct = $product;

            $focusReason =
                'This product is selling well but has limited stock.';

            $focusAction =
                'Consider restocking this product before demand exceeds supply.';

            break;
        }
    }


    // If no low-stock product, use highest revenue product
    if (!$focusProduct) {

        $focusProduct = $topProducts[0];

        foreach ($topProducts as $product) {

            if (
                (float) ($product->revenue ?? 0)
                >
                (float) ($focusProduct->revenue ?? 0)
            ) {
                $focusProduct = $product;
            }
        }

        $focusReason =
            'This product is currently generating strong revenue for your shop.';

        $focusAction =
            'Consider promoting this product and keeping its stock available.';
    }
}
?>



<div class="analytics-page app-page-spacing"
    data-shop-theme="<?= htmlspecialchars($savedThemeKey) ?>"
    style="
        --analytics-accent: <?= htmlspecialchars($shopTheme['accent']) ?>;
        --analytics-secondary: <?= htmlspecialchars($shopTheme['secondary']) ?>;
        --analytics-background: <?= htmlspecialchars($shopTheme['background']) ?>;
        --analytics-text: <?= htmlspecialchars($shopTheme['text']) ?>;
    ">

    <!-- =====================================================
         HEADER / HERO
         ===================================================== -->

    <section class="analytics-hero">

        <?php if (!empty($activeShop->farm_banner ?? $activeShop->banner)): ?>

            <img
                src="<?= htmlspecialchars($activeShop->farm_banner ?? $activeShop->banner) ?>"
                alt="<?= htmlspecialchars($activeShop->shop_name ?? 'Shop') ?> banner"
                class="analytics-hero-image">

        <?php endif; ?>

        <div class="analytics-hero-overlay"></div>

        <div class="analytics-hero-content">

            <div class="analytics-shop-brand">

                <div class="analytics-shop-logo">

                    <?php if (!empty($activeShop->farm_logo ?? $activeShop->logo)): ?>

                        <img
                            src="<?= htmlspecialchars($activeShop->farm_logo ?? $activeShop->logo) ?>"
                            alt="<?= htmlspecialchars($activeShop->shop_name ?? 'Shop') ?> logo">

                    <?php else: ?>

                        <?= htmlspecialchars(
                            strtoupper(
                                substr(
                                    $activeShop->shop_name ?? 'S',
                                    0,
                                    1
                                )
                            )
                        ) ?>

                    <?php endif; ?>

                </div>

                <div class="analytics-hero-text">

                    <span class="analytics-eyebrow">
                        <i class="bi bi-bar-chart-line-fill"></i>
                        Seller Centre
                    </span>

                    <h1>
                        Shop Analytics
                    </h1>

                    <p>
                        <?= htmlspecialchars(
                            $activeShop->shop_name ?? 'Active Shop'
                        ) ?>
                    </p>

                    <div class="analytics-location">

                        <i class="bi bi-geo-alt-fill"></i>

                        <?= htmlspecialchars(
                            $activeShop->farm_location
                            ?: trim(
                                ($activeShop->barangay ?? '')
                                . ', '
                                . ($activeShop->city ?? ''),
                                ', '
                            )
                        ) ?>

                    </div>

                </div>

            </div>

            <div class="analytics-hero-actions">

                <a
                    href="/ani-mart/public/seller-center/analytics/download?start_date=<?= urlencode($startDate) ?>&amp;end_date=<?= urlencode($endDate) ?>&amp;period=<?= urlencode((string) ($period ?? 'custom')) ?>"
                    class="analytics-back-btn">

                    <i class="bi bi-download"></i>

                    Download Report

                </a>

                <a
                    href="/ani-mart/public/seller-center"
                    class="analytics-back-btn">

                    <i class="bi bi-arrow-left"></i>

                    Dashboard

                </a>

            </div>

        </div>

    </section>


    <!-- =====================================================
         FILTER BAR
         ===================================================== -->

    <section class="analytics-filter">

        <div class="filter-title">

            <div class="filter-icon">
                <i class="bi bi-calendar3"></i>
            </div>

            <div>
                <strong>Analytics Period</strong>
                <span>Select the period you want to analyze</span>
            </div>

        </div>

        <form
            method="GET"
            action="/ani-mart/public/seller-center/analytics"
            class="analytics-date-form">

            <div class="d-flex flex-wrap gap-2 w-100 mb-2">
                <?php foreach (['daily' => 'Today', 'weekly' => 'Last 7 Days', 'monthly' => 'Last 30 Days'] as $periodKey => $periodLabel): ?>
                    <a href="/ani-mart/public/seller-center/analytics?period=<?= $periodKey ?>" class="btn btn-sm rounded-pill <?= ($period ?? 'custom') === $periodKey ? 'btn-success' : 'btn-outline-success' ?>">
                        <?= $periodLabel ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <div class="date-field">

                <label>From</label>

                <input
                    type="date"
                    name="start_date"
                    value="<?= htmlspecialchars($startDate) ?>">

            </div>

            <div class="date-arrow">
                <i class="bi bi-arrow-right"></i>
            </div>

            <div class="date-field">

                <label>To</label>

                <input
                    type="date"
                    name="end_date"
                    value="<?= htmlspecialchars($endDate) ?>">

            </div>

            <button type="submit" class="analytics-apply-btn">

                <i class="bi bi-funnel-fill"></i>

                Apply Filter

            </button>

        </form>

    </section>


    <!-- =====================================================
         KPI CARDS
         ===================================================== -->

    <section class="analytics-kpis">

        <!-- REVENUE -->

        <div class="analytics-kpi revenue">

            <div class="kpi-top">

                <div>
                    <span class="kpi-label">Net Earnings</span>

                    <h2>
                        ₱<?= number_format($netEarnings, 2) ?>
                    </h2>
                </div>

                <div class="kpi-icon">
                    <i class="bi bi-cash-stack"></i>
                </div>

            </div>

            <div class="kpi-bottom">
                <span>
                    <i class="bi bi-graph-up-arrow"></i>
                    Gross sales + delivery fees − refunds
                </span>
            </div>

        </div>


        <!-- ORDERS -->

        <div class="analytics-kpi orders">

            <div class="kpi-top">

                <div>
                    <span class="kpi-label">Completed Orders</span>

                    <h2>
                        <?= number_format($totalOrders) ?>
                    </h2>
                </div>

                <div class="kpi-icon">
                    <i class="bi bi-bag-check-fill"></i>
                </div>

            </div>

            <div class="kpi-bottom">
                <span>
                    <i class="bi bi-check-circle-fill"></i>
                    Successful purchases
                </span>
            </div>

        </div>


        <!-- CUSTOMERS -->

        <div class="analytics-kpi customers">

            <div class="kpi-top">

                <div>
                    <span class="kpi-label">Customers</span>

                    <h2>
                        <?= number_format($uniqueCustomers) ?>
                    </h2>
                </div>

                <div class="kpi-icon">
                    <i class="bi bi-people-fill"></i>
                </div>

            </div>

            <div class="kpi-bottom">
                <span>
                    <i class="bi bi-person-check-fill"></i>
                    Unique customers
                </span>
            </div>

        </div>


        <!-- RATING -->

        <div class="analytics-kpi rating">

            <div class="kpi-top">

                <div>

                    <span class="kpi-label">Average Rating</span>

                    <h2>
                        <?= number_format($avgRating, 1) ?>
                        <small>/ 5</small>
                    </h2>

                </div>

                <div class="kpi-icon">
                    <i class="bi bi-star-fill"></i>
                </div>

            </div>

            <div class="kpi-bottom">

                <span class="rating-stars">

                    <?php for ($i = 1; $i <= 5; $i++): ?>

                        <i class="bi bi-star-fill <?= $i <= round($avgRating) ? 'filled' : '' ?>"></i>

                    <?php endfor; ?>

                </span>

            </div>

        </div>

    </section>


    <!-- =====================================================
         SECONDARY METRICS
         ===================================================== -->

    <section class="analytics-mini-grid">

        <div class="mini-metric green">

            <div class="mini-icon">
                <i class="bi bi-receipt"></i>
            </div>

            <div>

                <span>Average Order Value</span>

                <strong>
                    ₱<?= number_format($averageOrderValue, 2) ?>
                </strong>

            </div>

        </div>


        <div class="mini-metric blue">

            <div class="mini-icon">
                <i class="bi bi-basket2-fill"></i>
            </div>

            <div>

                <span>Products Purchased</span>

                <strong>
                    <?= number_format($productsPurchased) ?>
                </strong>

            </div>

        </div>


        <div class="mini-metric purple">

            <div class="mini-icon">
                <i class="bi bi-arrow-repeat"></i>
            </div>

            <div>

                <span>Returning Customers</span>

                <strong>
                    <?= number_format($returningCustomers) ?>
                </strong>

            </div>

        </div>


        <div class="mini-metric orange">

            <div class="mini-icon">
                <i class="bi bi-person-heart"></i>
            </div>

            <div>

                <span>Customer Return Rate</span>

                <strong>
                    <?= number_format($returningRate, 1) ?>%
                </strong>

            </div>

        </div>

    </section>

    <section class="analytics-panel mb-4">
        <div class="panel-header">
            <div>
                <span class="panel-eyebrow green">FINANCIAL RECORDS</span>
                <h3>Sales and adjustments</h3>
                <p>All amounts reconcile to net earnings for the selected period.</p>
            </div>
            <a href="/ani-mart/public/seller-center/analytics/download?start_date=<?= urlencode($startDate) ?>&amp;end_date=<?= urlencode($endDate) ?>&amp;period=<?= urlencode((string) ($period ?? 'custom')) ?>" class="btn btn-success rounded-pill px-4">
                <i class="bi bi-file-earmark-arrow-down me-2"></i>Download CSV
            </a>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-6 col-xl"><div class="border rounded-4 p-3 h-100"><span class="small text-muted d-block">Gross sales</span><strong class="fs-4 text-success">₱<?= number_format($grossSales, 2) ?></strong></div></div>
            <div class="col-6 col-xl"><div class="border rounded-4 p-3 h-100"><span class="small text-muted d-block">Delivery fees</span><strong class="fs-4">₱<?= number_format($deliveryFees, 2) ?></strong></div></div>
            <div class="col-6 col-xl"><div class="border rounded-4 p-3 h-100"><span class="small text-muted d-block">Cancelled</span><strong class="fs-4 text-danger"><?= number_format($cancelledOrders) ?></strong><small class="d-block text-muted">₱<?= number_format($cancelledValue, 2) ?> value</small></div></div>
            <div class="col-6 col-xl"><div class="border rounded-4 p-3 h-100"><span class="small text-muted d-block">Refunded</span><strong class="fs-4 text-danger">₱<?= number_format($refundedAmount, 2) ?></strong><small class="d-block text-muted"><?= number_format($refundedOrders) ?> of <?= number_format($returnedOrders) ?> return records</small></div></div>
            <div class="col-12 col-xl"><div class="border border-success rounded-4 p-3 h-100 bg-success-subtle"><span class="small text-muted d-block">Net earnings</span><strong class="fs-4 text-success">₱<?= number_format($netEarnings, 2) ?></strong></div></div>
        </div>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>Order</th><th>Status</th><th>Gross</th><th>Cancelled</th><th>Delivery</th><th>Refunded</th><th class="text-end">Net</th></tr></thead>
                <tbody>
                <?php if ($financialRecords): ?>
                    <?php foreach ($financialRecords as $record): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($record->order_number) ?></strong><div class="small text-muted"><?= date('M j, Y', strtotime($record->created_at)) ?> · <?= htmlspecialchars($record->customer_name ?: 'Customer') ?></div></td>
                            <td><span class="badge bg-light text-dark border"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $record->status))) ?></span></td>
                            <td>₱<?= number_format((float) $record->gross_sales, 2) ?></td>
                            <td class="text-danger">₱<?= number_format((float) $record->cancelled_value, 2) ?></td>
                            <td>₱<?= number_format((float) $record->delivery_fees, 2) ?></td>
                            <td class="text-danger">₱<?= number_format((float) $record->refunded_amount, 2) ?></td>
                            <td class="text-end fw-bold <?= (float) $record->net_earnings < 0 ? 'text-danger' : 'text-success' ?>">₱<?= number_format((float) $record->net_earnings, 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">No financial records in this period.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

<!-- =====================================================
     BEST SELLER + SMART RECOMMENDATION
     ===================================================== -->

<section class="seller-insights-grid mb-4">

    <!-- BEST SELLER -->

    <div class="best-seller-card">

        <div class="best-seller-glow"></div>

        <div class="best-seller-content">

            <div class="best-seller-top">

                <span class="best-seller-badge">
                    <i class="bi bi-trophy-fill"></i>
                    BEST SELLER
                </span>

                <span class="best-seller-period">
                    Selected Period
                </span>

            </div>


            <?php if ($bestSeller): ?>

                <div class="best-seller-product">

                    <div class="best-seller-rank">
                        #1
                    </div>

                    <div class="best-seller-info">

                        <span class="best-seller-label">
                            Top Performing Product
                        </span>

                        <h2>
                            <?= htmlspecialchars(
                                $bestSeller->name ?? 'Product'
                            ) ?>
                        </h2>

                        <div class="best-seller-stats">

                            <div>
                                <i class="bi bi-cart-check-fill"></i>

                                <strong>
                                    <?= number_format(
                                        (int) ($bestSeller->units ?? 0)
                                    ) ?>
                                </strong>

                                <span>units sold</span>
                            </div>

                            <div>
                                <i class="bi bi-cash-stack"></i>

                                <strong>
                                    ₱<?= number_format(
                                        (float) ($bestSeller->revenue ?? 0),
                                        2
                                    ) ?>
                                </strong>

                                <span>revenue</span>
                            </div>

                            <div>
                                <i class="bi bi-box-seam"></i>

                                <strong>
                                    <?= number_format(
                                        (int) ($bestSeller->stock ?? 0)
                                    ) ?>
                                </strong>

                                <span>in stock</span>
                            </div>

                        </div>

                    </div>

                    <div class="best-seller-trophy">
                        <i class="bi bi-trophy-fill"></i>
                    </div>

                </div>

            <?php else: ?>

                <div class="best-seller-empty">

                    <i class="bi bi-trophy"></i>

                    <strong>
                        No best seller yet
                    </strong>

                    <span>
                        Products with completed sales will appear here.
                    </span>

                </div>

            <?php endif; ?>

        </div>

    </div>


    <!-- SMART RECOMMENDATION -->

    <div class="seller-recommendation-card">

        <div class="recommendation-header">

            <div class="recommendation-icon">
                <i class="bi bi-lightbulb-fill"></i>
            </div>

            <div>

                <span>
                    SMART SELLER INSIGHT
                </span>

                <h3>
                    What should you focus on?
                </h3>

            </div>

        </div>


        <?php if ($focusProduct): ?>

            <div class="recommendation-product">

                <div class="recommendation-product-icon">
                    <i class="bi bi-arrow-up-circle-fill"></i>
                </div>

                <div>

                    <span>
                        Recommended Focus
                    </span>

                    <strong>
                        <?= htmlspecialchars(
                            $focusProduct->name ?? 'Product'
                        ) ?>
                    </strong>

                </div>

            </div>


            <div class="recommendation-reason">

                <div class="reason-icon">
                    <i class="bi bi-graph-up-arrow"></i>
                </div>

                <p>
                    <?= htmlspecialchars($focusReason) ?>
                </p>

            </div>


            <div class="recommendation-action">

                <i class="bi bi-check-circle-fill"></i>

                <span>
                    <?= htmlspecialchars($focusAction) ?>
                </span>

            </div>

        <?php else: ?>

            <div class="recommendation-empty">

                <i class="bi bi-lightbulb"></i>

                <strong>
                    Not enough sales data
                </strong>

                <span>
                    Keep selling products to generate personalized recommendations.
                </span>

            </div>

        <?php endif; ?>

    </div>

</section>


    <!-- =====================================================
         REVENUE CHART + CUSTOMER INSIGHTS
         ===================================================== -->

    <section class="analytics-main-grid">

        <!-- SALES CHART -->

        <div class="analytics-panel sales-panel">

            <div class="panel-header">

                <div>

                    <span class="panel-eyebrow">
                        PERFORMANCE
                    </span>

                    <h3>
                        Revenue Overview
                    </h3>

                    <p>
                        Daily completed sales during the selected period.
                    </p>

                </div>

                <div class="chart-legend">

                    <span>
                        <i></i>
                        Revenue
                    </span>

                </div>

            </div>


            <?php if (!empty($analyticsTrend)): ?>

                <div class="revenue-chart">

                    <div class="chart-y-axis">

                        <?php
                        $chartSteps = 5;

                        for ($i = $chartSteps; $i >= 0; $i--):
                            $value = ($trendMax / $chartSteps) * $i;
                        ?>

                            <span>
                                ₱<?= number_format($value / 1000, 0) ?>k
                            </span>

                        <?php endfor; ?>

                    </div>


                    <div class="chart-area">

                        <div class="chart-grid-lines">

                            <?php for ($i = 0; $i <= 5; $i++): ?>

                                <span></span>

                            <?php endfor; ?>

                        </div>


                        <div class="chart-bars">

                            <?php foreach ($analyticsTrend as $point): ?>

                                <?php

                                $revenue = (float) ($point->revenue ?? 0);

                                $height = max(
                                    5,
                                    ($revenue / $trendMax) * 100
                                );

                                ?>

                                <div class="chart-column">

                                    <div
                                        class="chart-bar"
                                        style="height: <?= $height ?>%;"
                                        title="₱<?= number_format($revenue, 2) ?>">

                                        <span class="chart-tooltip">
                                            ₱<?= number_format($revenue, 2) ?>
                                        </span>

                                    </div>

                                    <span class="chart-date">
                                        <?= htmlspecialchars(
                                            date(
                                                'M j',
                                                strtotime($point->sale_date)
                                            )
                                        ) ?>
                                    </span>

                                </div>

                            <?php endforeach; ?>

                        </div>

                    </div>

                </div>

            <?php else: ?>

                <div class="analytics-empty">

                    <div class="empty-chart-icon">
                        <i class="bi bi-graph-up"></i>
                    </div>

                    <strong>No sales data</strong>

                    <span>
                        No completed sales were recorded during this period.
                    </span>

                </div>

            <?php endif; ?>

        </div>


        <!-- CUSTOMER INSIGHTS -->

        <div class="analytics-panel customer-panel">

            <div class="panel-header">

                <div>

                    <span class="panel-eyebrow blue">
                        CUSTOMERS
                    </span>

                    <h3>
                        Customer Insights
                    </h3>

                    <p>
                        Understand your customer activity.
                    </p>

                </div>

            </div>


            <div class="customer-circle">

                <div class="customer-circle-inner">

                    <strong>
                        <?= number_format($uniqueCustomers) ?>
                    </strong>

                    <span>
                        Customers
                    </span>

                </div>

            </div>


            <div class="customer-stats">

                <div class="customer-stat">

                    <div class="customer-stat-icon green">
                        <i class="bi bi-person-check-fill"></i>
                    </div>

                    <div>
                        <span>Unique Customers</span>
                        <strong><?= number_format($uniqueCustomers) ?></strong>
                    </div>

                </div>


                <div class="customer-stat">

                    <div class="customer-stat-icon purple">
                        <i class="bi bi-arrow-repeat"></i>
                    </div>

                    <div>
                        <span>Returning</span>
                        <strong><?= number_format($returningCustomers) ?></strong>
                    </div>

                </div>


                <div class="customer-stat">

                    <div class="customer-stat-icon orange">
                        <i class="bi bi-percent"></i>
                    </div>

                    <div>
                        <span>Return Rate</span>
                        <strong><?= number_format($returningRate, 1) ?>%</strong>
                    </div>

                </div>

            </div>

        </div>

    </section>


    <!-- =====================================================
         PRODUCT PERFORMANCE
         ===================================================== -->

    <section class="analytics-panel product-panel">

        <div class="panel-header">

            <div>

                <span class="panel-eyebrow orange">
                    PRODUCTS
                </span>

                <h3>
                    Product Performance
                </h3>

                <p>
                    Your best-performing products based on sales.
                </p>

            </div>

            <a
                href="/ani-mart/public/farmer/products"
                class="panel-action">

                Manage Products

                <i class="bi bi-arrow-right"></i>

            </a>

        </div>


        <?php if (!empty($topProducts)): ?>

            <div class="product-performance">

                <?php

                $maxProductRevenue = max(
                    1,
                    ...array_map(
                        static fn($product) =>
                            (float) ($product->revenue ?? 0),
                        $topProducts
                    )
                );

                ?>

                <?php foreach ($topProducts as $index => $product): ?>

                    <?php

                    $productRevenue = (float) ($product->revenue ?? 0);

                    $productWidth = max(
                        5,
                        ($productRevenue / $maxProductRevenue) * 100
                    );

                    $stock = (int) ($product->stock ?? 0);

                    ?>

                    <div class="product-row">

                        <div class="product-rank">
                            <?= $index + 1 ?>
                        </div>

                        <div class="product-info">

                            <strong>
                                <?= htmlspecialchars($product->name ?? 'Product') ?>
                            </strong>

                            <span>
                                <?= number_format((int) ($product->units ?? 0)) ?>
                                units sold
                            </span>

                        </div>

                        <div class="product-progress">

                            <div class="product-progress-track">

                                <div
                                    class="product-progress-bar"
                                    style="width: <?= $productWidth ?>%">
                                </div>

                            </div>

                        </div>

                        <div class="product-revenue">

                            ₱<?= number_format($productRevenue, 2) ?>

                        </div>

                        <div class="product-stock <?= $stock <= 5 ? 'low' : '' ?>">

                            <i class="bi bi-box-seam"></i>

                            <?= $stock ?>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

        <?php else: ?>

            <div class="analytics-empty">

                <div class="empty-chart-icon">
                    <i class="bi bi-box-seam"></i>
                </div>

                <strong>No product performance data</strong>

                <span>
                    Active products with completed sales will appear here.
                </span>

            </div>

        <?php endif; ?>

    </section>


    <!-- =====================================================
         COMPLAINTS + QUICK ACTIONS
         ===================================================== -->

    <section class="analytics-bottom-grid">

        <!-- COMPLAINTS -->

        <div class="analytics-panel">

            <div class="panel-header">

                <div>

                    <span class="panel-eyebrow red">
                        TRUST & SAFETY
                    </span>

                    <h3>
                        Complaint Status
                    </h3>

                    <p>
                        Reports received for your shop.
                    </p>

                </div>

            </div>


            <?php if (!empty($complaintsByStatus)): ?>

                <div class="complaint-grid">

                    <?php foreach ($complaintsByStatus as $statusRow): ?>

                        <?php

                        $status = strtolower(
                            $statusRow->status ?? 'unknown'
                        );

                        $statusClass = match ($status) {

                            'resolved',
                            'closed' => 'green',

                            'pending',
                            'open' => 'orange',

                            'rejected',
                            'cancelled' => 'red',

                            default => 'blue'

                        };

                        ?>

                        <div class="complaint-card <?= $statusClass ?>">

                            <div class="complaint-icon">

                                <?php if ($statusClass === 'green'): ?>

                                    <i class="bi bi-check-circle-fill"></i>

                                <?php elseif ($statusClass === 'orange'): ?>

                                    <i class="bi bi-clock-fill"></i>

                                <?php elseif ($statusClass === 'red'): ?>

                                    <i class="bi bi-x-circle-fill"></i>

                                <?php else: ?>

                                    <i class="bi bi-info-circle-fill"></i>

                                <?php endif; ?>

                            </div>

                            <span>
                                <?= htmlspecialchars(
                                    ucfirst($status)
                                ) ?>
                            </span>

                            <strong>
                                <?= number_format(
                                    (int) ($statusRow->total ?? 0)
                                ) ?>
                            </strong>

                        </div>

                    <?php endforeach; ?>

                </div>

            <?php else: ?>

                <div class="no-complaints">

                    <div>
                        <i class="bi bi-shield-check"></i>
                    </div>

                    <strong>
                        No complaints
                    </strong>

                    <span>
                        Your shop has no recorded complaints for this period.
                    </span>

                </div>

            <?php endif; ?>

        </div>


        <!-- QUICK ACTIONS -->

        <div class="analytics-panel quick-panel">

            <div class="panel-header">

                <div>

                    <span class="panel-eyebrow purple">
                        MANAGEMENT
                    </span>

                    <h3>
                        Quick Actions
                    </h3>

                    <p>
                        Manage your seller account.
                    </p>

                </div>

            </div>


            <div class="quick-actions">

                <a
                    href="/ani-mart/public/farmer/products"
                    class="quick-action green">

                    <div>
                        <i class="bi bi-box-seam"></i>
                    </div>

                    <span>
                        <strong>Products</strong>
                        Manage listings
                    </span>

                    <i class="bi bi-chevron-right"></i>

                </a>


                <a
                    href="/ani-mart/public/farmer/orders"
                    class="quick-action orange">

                    <div>
                        <i class="bi bi-bag-check"></i>
                    </div>

                    <span>
                        <strong>Orders</strong>
                        View customer orders
                    </span>

                    <i class="bi bi-chevron-right"></i>

                </a>


                <a
                    href="/ani-mart/public/seller-center/shop/customize"
                    class="quick-action blue">

                    <div>
                        <i class="bi bi-shop"></i>
                    </div>

                    <span>
                        <strong>Shop Profile</strong>
                        Edit shop information
                    </span>

                    <i class="bi bi-chevron-right"></i>

                </a>

            </div>

        </div>

    </section>

</div>




<?php

$content = ob_get_clean();

require ANI_MART_ROOT . '/app/Views/layouts/main.php';
?>
