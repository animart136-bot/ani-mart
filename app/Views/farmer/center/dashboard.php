<?php

$title = 'Seller Centre Dashboard';

ob_start();

$activeShop = $activeShop ?? null;
$stats = $stats ?? [];
$shopThemes = \App\Services\SellerProfileService::themes();
$savedThemeKey = $activeShop->shop_theme ?? 'fresh-farm';
if (!isset($shopThemes[$savedThemeKey])) {
    $savedThemeKey = 'fresh-farm';
}
$shopTheme = $shopThemes[$savedThemeKey];

$shopName = $activeShop->shop_name ?? 'My Shop';
$shopLocation = $activeShop->farm_location
    ?: trim(($activeShop->barangay ?? '') . ', ' . ($activeShop->city ?? ''), ', ');

$shopLogo = $activeShop->farm_logo ?? $activeShop->logo ?? '';
$shopBanner = $activeShop->farm_banner ?? $activeShop->banner ?? '';

$verificationStatus = $activeShop->verification_status ?? 'pending';
$trustScore = (int) ($activeShop->trust_score ?? 0);

$shopInitial = strtoupper(substr($shopName, 0, 1));
?>

<div class="seller-dashboard container app-page-spacing" data-shop-theme="<?= htmlspecialchars($savedThemeKey) ?>" style="
    --shop-accent: <?= htmlspecialchars($shopTheme['accent']) ?>;
    --shop-secondary: <?= htmlspecialchars($shopTheme['secondary']) ?>;
    --shop-background: <?= htmlspecialchars($shopTheme['background']) ?>;
    --shop-text: <?= htmlspecialchars($shopTheme['text']) ?>;
">

    <!-- =====================================================
         SHOP HERO
         ===================================================== -->

    <section class="seller-hero">

        <?php if (!empty($shopBanner)): ?>

            <img
                src="<?= htmlspecialchars($shopBanner) ?>"
                alt="<?= htmlspecialchars($shopName) ?> banner"
                class="seller-hero-bg">

        <?php endif; ?>

        <div class="seller-hero-overlay"></div>

        <div class="seller-hero-content">

            <div class="seller-hero-main">

                <!-- SHOP LOGO -->

                <div class="seller-shop-logo">

                    <?php if (!empty($shopLogo)): ?>

                        <img
                            src="<?= htmlspecialchars($shopLogo) ?>"
                            alt="<?= htmlspecialchars($shopName) ?> logo">

                    <?php else: ?>

                        <span><?= htmlspecialchars($shopInitial) ?></span>

                    <?php endif; ?>

                </div>

                <!-- SHOP INFORMATION -->

                <div class="seller-hero-info">

                    <div class="seller-eyebrow">
                        <i class="bi bi-shop"></i>
                        Seller Centre
                    </div>

                    <h1>
                        <?= htmlspecialchars($shopName) ?>
                    </h1>

                    <?php if (!empty($shopLocation)): ?>

                        <div class="seller-location">
                            <i class="bi bi-geo-alt-fill"></i>
                            <?= htmlspecialchars($shopLocation) ?>
                        </div>

                    <?php endif; ?>

                    <div class="seller-verification">

                        <span class="verification-badge">
                            <i class="bi bi-patch-check-fill"></i>

                            <?= htmlspecialchars(
                                ucfirst($verificationStatus)
                            ) ?>
                        </span>

                        <span class="seller-status-text">
                            Seller account
                        </span>

                    </div>

                </div>

            </div>

            <!-- HERO ACTIONS -->

            <div class="seller-hero-actions">

                <a
                    href="/ani-mart/public/seller-center/shop/customize"
                    class="seller-btn seller-btn-light">

                    <i class="bi bi-palette"></i>
                    Customize Shop

                </a>

                <a
                    href="/ani-mart/public/seller-center/shops"
                    class="seller-btn seller-btn-outline">

                    <i class="bi bi-shop-window"></i>
                    Manage Shops

                </a>

            </div>

        </div>

    </section>


    <!-- =====================================================
         DASHBOARD STATS
         ===================================================== -->

    <section class="seller-stats">

        <!-- PRODUCTS -->

        <div class="seller-stat-card">

            <div class="seller-stat-header">

                <span class="seller-stat-label">
                    Products
                </span>

                <div class="seller-stat-icon products">
                    <i class="bi bi-box-seam"></i>
                </div>

            </div>

            <div class="seller-stat-value">
                <?= (int) ($stats['total_products'] ?? 0) ?>
            </div>

            <div class="seller-stat-caption">
                Active products
            </div>

        </div>


        <!-- ORDERS -->

        <div class="seller-stat-card">

            <div class="seller-stat-header">

                <span class="seller-stat-label">
                    Orders
                </span>

                <div class="seller-stat-icon orders">
                    <i class="bi bi-bag-check"></i>
                </div>

            </div>

            <div class="seller-stat-value">
                <?= (int) ($stats['total_orders'] ?? 0) ?>
            </div>

            <div class="seller-stat-caption">
                Completed orders
            </div>

        </div>


        <!-- REVENUE -->

        <div class="seller-stat-card">

            <div class="seller-stat-header">

                <span class="seller-stat-label">
                    Revenue
                </span>

                <div class="seller-stat-icon revenue">
                    ₱
                </div>

            </div>

            <div class="seller-stat-value revenue-value">
                ₱<?= number_format(
                    (float) ($stats['total_revenue'] ?? 0),
                    2
                ) ?>
            </div>

            <div class="seller-stat-caption">
                Shop earnings
            </div>

        </div>


        <!-- COMPLAINTS -->

        <div class="seller-stat-card">

            <div class="seller-stat-header">

                <span class="seller-stat-label">
                    Complaints
                </span>

                <div class="seller-stat-icon complaints">
                    <i class="bi bi-exclamation-circle"></i>
                </div>

            </div>

            <div class="seller-stat-value">
                <?= (int) ($stats['total_complaints'] ?? 0) ?>
            </div>

            <div class="seller-stat-caption">
                Customer disputes
            </div>

        </div>

    </section>


    <!-- =====================================================
         MAIN DASHBOARD CONTENT
         ===================================================== -->

    <section class="seller-dashboard-grid">

        <!-- SHOP OVERVIEW -->

        <div class="seller-panel seller-overview-panel">

            <div class="seller-panel-header">

                <div>

                    <span class="seller-section-eyebrow">
                        SHOP PROFILE
                    </span>

                    <h2>
                        Shop Overview
                    </h2>

                    <p>
                        Monitor your shop profile, trust score,
                        and verification status.
                    </p>

                </div>

                <a
                    href="/ani-mart/public/seller-center/analytics"
                    class="seller-outline-btn">

                    <i class="bi bi-bar-chart-line"></i>
                    Analytics

                </a>

            </div>


            <!-- OVERVIEW GRID -->

            <div class="seller-overview-grid">

                <!-- TRUST SCORE -->

                <div class="seller-info-card">

                    <div class="seller-info-icon trust">
                        <i class="bi bi-shield-check"></i>
                    </div>

                    <div class="seller-info-content">

                        <span>
                            Trust Score
                        </span>

                        <strong>
                            <?= number_format($trustScore, 0) ?>
                        </strong>

                        <small>
                            <?= $trustScore >= 80
                                ? 'Excellent shop standing'
                                : 'Earn 5 points from each completed order with a positive review and no complaints or seller reports' ?>
                        </small>

                    </div>

                </div>


                <!-- VERIFICATION -->

                <div class="seller-info-card">

                    <div class="seller-info-icon verification">
                        <i class="bi bi-patch-check"></i>
                    </div>

                    <div class="seller-info-content">

                        <span>
                            Verification
                        </span>

                        <strong class="text-capitalize">
                            <?= htmlspecialchars($verificationStatus) ?>
                        </strong>

                        <small>
                            Shop verification status
                        </small>

                    </div>

                </div>

            </div>


            <!-- QUICK INFORMATION -->

            <div class="seller-shop-details">

                <div class="seller-detail">

                    <div class="seller-detail-icon">
                        <i class="bi bi-shop"></i>
                    </div>

                    <div>

                        <span>Shop Name</span>

                        <strong>
                            <?= htmlspecialchars($shopName) ?>
                        </strong>

                    </div>

                </div>


                <div class="seller-detail">

                    <div class="seller-detail-icon">
                        <i class="bi bi-geo-alt"></i>
                    </div>

                    <div>

                        <span>Location</span>

                        <strong>
                            <?= htmlspecialchars(
                                $shopLocation ?: 'No location provided'
                            ) ?>
                        </strong>

                    </div>

                </div>

            </div>

        </div>


        <!-- CURRENT SHOP -->

        <div class="seller-panel current-shop-panel">

            <div class="seller-panel-header">

                <div>

                    <span class="seller-section-eyebrow">
                        ACTIVE PROFILE
                    </span>

                    <h2>
                        Current Shop
                    </h2>

                </div>

                <div class="current-shop-menu">
                    <i class="bi bi-three-dots"></i>
                </div>

            </div>


            <!-- SHOP PROFILE -->

            <div class="current-shop-profile">

                <div class="current-shop-logo">

                    <?php if (!empty($shopLogo)): ?>

                        <img
                            src="<?= htmlspecialchars($shopLogo) ?>"
                            alt="<?= htmlspecialchars($shopName) ?>">

                    <?php else: ?>

                        <?= htmlspecialchars($shopInitial) ?>

                    <?php endif; ?>

                </div>

                <div class="current-shop-info">

                    <h3>
                        <?= htmlspecialchars($shopName) ?>
                    </h3>

                    <p title="<?= htmlspecialchars($shopLocation ?: 'No location provided') ?>">

                        <i class="bi bi-geo-alt"></i>

                        <span>
                            <?= htmlspecialchars(
                                $shopLocation ?: 'No location provided'
                            ) ?>
                        </span>

                    </p>

                    <span class="current-shop-status">

                        <span class="status-dot"></span>

                        Active shop

                    </span>

                </div>

            </div>


            <!-- SHOP QUICK LINKS -->

            <div class="current-shop-links">

                <a
                    href="/ani-mart/public/seller-center/shop/customize"
                    class="current-shop-link">

                    <div class="link-icon">
                        <i class="bi bi-palette"></i>
                    </div>

                    <div>
                        <strong>Customize Shop</strong>
                        <span>Update your storefront</span>
                    </div>

                    <i class="bi bi-chevron-right"></i>

                </a>


                <a
                    href="/ani-mart/public/seller-center/shops"
                    class="current-shop-link">

                    <div class="link-icon">
                        <i class="bi bi-shop-window"></i>
                    </div>

                    <div>
                        <strong>Manage Shops</strong>
                        <span>Switch seller profiles</span>
                    </div>

                    <i class="bi bi-chevron-right"></i>

                </a>

            </div>

        </div>

    </section>

    <section class="seller-panel product-management-panel mt-4">
        <div class="seller-panel-header">
            <div>
                <span class="seller-section-eyebrow">PRODUCT MANAGEMENT</span>
                <h2>Manage Products</h2>
                <p>Add new products, update listings, adjust stock, or remove products from your shop.</p>
            </div>
            <a href="/ani-mart/public/farmer/products/create" class="seller-outline-btn">
                <i class="bi bi-plus-lg"></i>
                Add Product
            </a>
        </div>

        <div class="current-shop-links">
            <a href="/ani-mart/public/farmer/products/create" class="current-shop-link">
                <div class="link-icon"><i class="bi bi-plus-square"></i></div>
                <div>
                    <strong>Add Product</strong>
                    <span>Create a new listing with price, variants, images, and stock.</span>
                </div>
                <i class="bi bi-chevron-right"></i>
            </a>

            <a href="/ani-mart/public/farmer/products" class="current-shop-link">
                <div class="link-icon"><i class="bi bi-box-seam"></i></div>
                <div>
                    <strong>Manage Listings</strong>
                    <span>Edit product information, update inventory, or delete a listing.</span>
                </div>
                <i class="bi bi-chevron-right"></i>
            </a>
        </div>
    </section>

</div>




<?php

$content = ob_get_clean();

$pageCss = '/ani-mart/public/assets/css/pages/seller-dashboard.css?v='
    . filemtime(__DIR__ . '/../../../../public/assets/css/pages/seller-dashboard.css');
require __DIR__ . '/../../layouts/main.php';
?>
