<?php

use App\Core\Session;

$user = Session::get('user');

$currentUserId = $user['id'] ?? 0;

$currentRole = strtolower(
    $user['role'] ?? 'guest'
);

$isSeller = ($currentRole === 'farmer');

use App\Services\BadgeCalculationService;

$seller = $seller ?? (object) [];
$isStoreOwner = $currentUserId > 0
    && (int) ($seller->user_id ?? 0) === (int) $currentUserId;
$messageSellerPath = '/ani-mart/public/messages/start?seller_profile_id=' . (int) ($seller->id ?? 0);
$metrics = $metrics ?? [];
$customerStats = $customerStats ?? [];
$farmGallery = $farmGallery ?? [];
$harvestGallery = $harvestGallery ?? [];
$products = $products ?? [];
$featuredProducts = $featuredProducts ?? [];
$followersCount = $followersCount ?? 0;
$isFollowing = $isFollowing ?? false;
$communityPosts = $communityPosts ?? [];
$activeShop = $activeShop ?? null;
$bodyClass = 'community-feed-page';
$pageStyles[] = '/ani-mart/public/assets/css/pages/community.css?v=' . filemtime(__DIR__ . '/../../../public/assets/css/pages/community.css');
/* =========================================================
   FARM SHOP THEME
   Resolve the saved theme from the seller's shop_theme.
========================================================= */

$themes = \App\Services\SellerProfileService::themes();
$savedThemeKey = $seller->shop_theme ?? 'fresh-farm';
if (!isset($themes[$savedThemeKey])) {
    $savedThemeKey = 'fresh-farm';
}
$theme = $themes[$savedThemeKey];
$featuredProducts = $featuredProducts ?? [];
$shopDescription = preg_replace('/\*\*(.*?)\*\*/s', '$1', (string) ($seller->description ?? ''));
$farmStory = preg_replace('/\*\*(.*?)\*\*/s', '$1', (string) ($seller->farm_story ?? ''));

$title =
    ($seller->shop_name ?? 'Farm') . ' | Ani-Mart';

ob_start();

?>

<div class="farm-storefront farm-theme-<?= htmlspecialchars($savedThemeKey) ?>" style="
        --shop-accent: <?= htmlspecialchars($theme['accent']) ?>;
        --shop-secondary: <?= htmlspecialchars($theme['secondary']) ?>;
        --shop-background: <?= htmlspecialchars($theme['background']) ?>;
        --shop-text: <?= htmlspecialchars($theme['text']) ?>;
    ">

    <div class="container farm-breadcrumb">
        <a href="/ani-mart/public/marketplace">Marketplace</a>
        <i class="bi bi-chevron-right" aria-hidden="true"></i>
        <span><?= htmlspecialchars($seller->shop_name ?? 'Farm store') ?></span>
    </div>
    <section class="farm-public-hero">

        <?php if (!empty($seller->farm_banner)): ?>

            <img src="<?= htmlspecialchars($seller->farm_banner) ?>"
                alt="<?= htmlspecialchars($seller->shop_name) ?> banner">

        <?php endif; ?>

        <div class="farm-public-hero-shade"></div>

        <div class="container farm-public-hero-content">

            <div class="farm-public-logo">

                <?php if (!empty($seller->farm_logo)): ?>

                    <img src="<?= htmlspecialchars($seller->farm_logo) ?>"
                        alt="<?= htmlspecialchars($seller->shop_name) ?> logo">

                <?php else: ?>

                    <?= htmlspecialchars(strtoupper(substr($seller->shop_name ?? 'F', 0, 1))) ?>

                <?php endif; ?>

            </div>

            <div class="farm-public-title">
                <p class="farm-eyebrow">LOCAL GROWERS · FRESH FINDS</p>

                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">

                    <h1 class="text-white mb-0"><?= htmlspecialchars($seller->shop_name ?? '') ?></h1>

                    <?php if (!empty($seller->verification_badge)): ?>

                        <span
                            class="badge farm-verification-badge rounded-pill px-3 py-2" 
                            title="<?= htmlspecialchars(BadgeCalculationService::getBadgeLabel($seller->verification_badge)) ?>"
                            aria-label="<?= htmlspecialchars(BadgeCalculationService::getBadgeLabel($seller->verification_badge)) ?>">
                            <i class="bi <?= BadgeCalculationService::getBadgeIcon($seller->verification_badge) ?>"></i>
                            <?= htmlspecialchars(BadgeCalculationService::getBadgeLabel($seller->verification_badge)) ?>
                        </span>

                    <?php else: ?>

                        <span class="badge bg-warning-subtle text-warning-emphasis rounded-pill px-3 py-2">New Seller · Building Trust</span>

                    <?php endif; ?>

                </div>

                <p class="text-white-50 mb-3">
                    <i class="bi bi-geo-alt me-1"></i>
                    <?= htmlspecialchars($seller->farm_location ?: trim(($seller->barangay ?? '') . ', ' . ($seller->city ?? ''), ', ')) ?>
                </p>

            </div>
            <div class="farm-hero-actions">
                <a href="#farm-products" class="farm-shop-link">Shop the harvest <i class="bi bi-arrow-down" aria-hidden="true"></i></a>

                <?php if (!$isStoreOwner): ?>
                    <a
                        href="<?= $user ? htmlspecialchars($messageSellerPath, ENT_QUOTES, 'UTF-8') : '/ani-mart/public/login' ?>"
                        class="farm-message-link">
                        <i class="bi bi-chat-left-text" aria-hidden="true"></i>
                        Message Seller
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <nav class="farm-section-nav" aria-label="Farm sections">
        <div class="container">
            <a href="#farm-products">Shop products</a>
            <a href="#farm-about">About the farm</a>
            <a href="#farm-community">Community</a>
            <a href="/ani-mart/public/marketplace" class="farm-marketplace-link">All farms <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
        </div>
    </nav>

    <div class="container py-5">
        <div class="farm-public-stats mb-5">

            <div><span>Trust Score</span><strong><?= (int) ($seller->trust_score ?? 0) ?>%</strong></div>
            <div><span>Customer
                    Rating</span><strong><?= number_format((float) ($metrics['customer_rating'] ?? 0), 1) ?></strong>
            </div>
            <div><span>Total Orders</span><strong><?= (int) ($metrics['total_orders'] ?? 0) ?></strong></div>
            <div><span>Total Products</span><strong><?= (int) ($metrics['total_products'] ?? 0) ?></strong></div>
            <div><span>Response Rate</span><strong><?= isset($metrics['response_rate']) ? (int) $metrics['response_rate'] . '%' : 'No data' ?></strong></div>
            <div><span>Avg.
                    Delivery</span><strong><?= htmlspecialchars($metrics['average_delivery_time'] ?? 'No data') ?></strong>
            </div>

        </div>


        <section id="farm-products" class="farm-catalog">
        <?php if (count($featuredProducts) > 0): ?>

            <!-- =========================================================
         FEATURED HARVESTS
    ========================================================== -->

            <section class="mb-5">

                <div class="d-flex justify-content-between align-items-center gap-3 mb-4">
                    <div>
                        <h2 class="h3 fw-bold mb-1">Featured harvests</h2>
                        <p class="text-muted mb-0">
                            Selected by this farm.
                        </p>
                    </div>
                </div>


                <div class="row g-4 farm-products-grid">

                    <?php foreach ($featuredProducts as $product): ?>

                        <div class="col-6 col-md-6 col-lg-4 col-xl-3">

                            <div class="marketplace-card product-card h-100 farm-featured-product">

                                <!-- PRODUCT IMAGE -->
                                <div class="marketplace-card-image">

                                    <img src="<?= htmlspecialchars(
                                                    $product->image ?: '/ani-mart/public/assets/img/product-placeholder.svg'
                                                ) ?>" class="marketplace-image" alt="<?= htmlspecialchars($product->name) ?>">

                                </div>


                                <!-- PRODUCT CONTENT -->
                                <div class="marketplace-card-content">

                                    <!-- CATEGORY + PRICE -->
                                    <div class="marketplace-card-top">

                                        <span class="marketplace-category">
                                            <?= htmlspecialchars(
                                                $product->category_name ?? 'Other'
                                            ) ?>
                                        </span>

                                        <span class="marketplace-price">
                                            ₱<?= number_format(
                                                    (float) $product->price,
                                                    2
                                                ) ?>
                                        </span>

                                    </div>


                                    <!-- FEATURED BADGE -->
                                    <span class="farm-featured-badge">
                                        <i class="bi bi-star-fill me-1"></i>
                                        Featured
                                    </span>


                                    <!-- PRODUCT NAME -->
                                    <h3 class="marketplace-product-name">
                                        <?= htmlspecialchars($product->name) ?>
                                    </h3>


                                    <!-- SELLER -->
                                    <div class="marketplace-seller">

                                        <i class="bi bi-shop"></i>

                                        <span>
                                            <?= htmlspecialchars(
                                                $product->shop_name
                                                    ?? $seller->shop_name
                                                    ?? 'Local Farm'
                                            ) ?>
                                        </span>

                                        <?php if (!empty($product->verification_badge)): ?>

                                            <i class="bi <?= BadgeCalculationService::getBadgeIcon($product->verification_badge) ?> marketplace-verified" title="<?= htmlspecialchars(
                                                                                                                BadgeCalculationService::getBadgeLabel(
                                                                                                                    $product->verification_badge
                                                                                                                )
                                                                                                            ) ?>" aria-label="<?= htmlspecialchars(BadgeCalculationService::getBadgeLabel($product->verification_badge)) ?>">
                                            </i>

                                        <?php endif; ?>

                                    </div>


                                    <!-- STOCK -->
                                    <div class="marketplace-stock">

                                        <?php if ((int) ($product->stock ?? 0) > 0): ?>

                                            <?php if ((int) $product->stock <= 3): ?>

                                                <span class="stock-low">
                                                    Only <?= (int) $product->stock ?> left
                                                </span>

                                            <?php else: ?>

                                                <span class="stock-available">
                                                    Available stock:
                                                    <?= (int) $product->stock ?>
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
                                                (float) ($product->avg_rating ?? 0),
                                                1
                                            ) ?>
                                        </strong>

                                        <span>
                                            (<?= (int) ($product->review_count ?? 0) ?>
                                            Reviews)
                                        </span>

                                    </div>


                                    <!-- ACTION -->
                                    <div class="marketplace-card-actions">

                                        <?php if ($currentRole === 'admin'): ?>

                                            <span class="badge bg-dark marketplace-status-badge">
                                                <i class="bi bi-shield-lock me-1"></i>
                                                Admin View
                                            </span>


                                        <?php elseif ($isSeller): ?>

                                            <span class="badge bg-secondary marketplace-status-badge">
                                                <i class="bi bi-person-check me-1"></i>
                                                Your Product
                                            </span>


                                        <?php elseif ((int) ($product->stock ?? 0) <= 0): ?>

                                            <button type="button" class="btn marketplace-out-of-stock-btn" disabled>
                                                Out of Stock
                                            </button>

                                        <?php elseif ($currentRole === 'guest'): ?>

                                            <button type="button" class="btn marketplace-add-cart-btn" data-bs-toggle="modal"
                                                data-bs-target="#loginRequiredModal">

                                                <i class="bi bi-cart-plus me-1"></i>
                                                Add to Cart

                                            </button>


                                        <?php elseif ($currentRole === 'customer'): ?>

                                            <button type="button" class="btn marketplace-add-cart-btn addToCartBtn"
                                                data-id="<?= (int) $product->id ?>" title="Add to cart"
                                                aria-label="Add <?= htmlspecialchars($product->name) ?> to cart"
                                                onclick="event.stopPropagation();">

                                                <i class="bi bi-cart-plus me-1"></i>
                                                Add to Cart

                                            </button>

                                        <?php endif; ?>

                                    </div>

                                </div>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

            </section>

        <?php endif; ?>


        <!-- =========================================================
     FRESH PRODUCTS
========================================================== -->

        <div class="d-flex justify-content-between align-items-center gap-3 mb-4">

            <div>

                <p class="farm-eyebrow shop-accent-text">FROM THE FARM</p>
                <h2 class="h3 fw-bold mb-1">
                    Fresh Products
                </h2>

                <p class="text-muted mb-0">
                    Available harvests from this farm.
                </p>

            </div>

        </div>


        <p class="farm-product-count"><?= count($products) ?> products to explore</p>

        <div class="row g-4 farm-products-grid">

            <?php foreach ($products as $product): ?>

                <div class="col-6 col-md-6 col-lg-4 col-xl-3">

                    <div class="marketplace-card product-card h-100">

                        <!-- PRODUCT IMAGE -->
                        <div class="marketplace-card-image">

                            <img src="<?= htmlspecialchars(
                                            $product->image ?: '/ani-mart/public/assets/img/product-placeholder.svg'
                                        ) ?>" class="marketplace-image" alt="<?= htmlspecialchars($product->name) ?>">

                        </div>


                        <!-- PRODUCT CONTENT -->
                        <div class="marketplace-card-content">

                            <!-- CATEGORY + PRICE -->
                            <div class="marketplace-card-top">

                                <span class="marketplace-category">

                                    <?= htmlspecialchars(
                                        $product->category_name ?? 'Other'
                                    ) ?>

                                </span>

                                <span class="marketplace-price">

                                    ₱<?= number_format(
                                            (float) $product->price,
                                            2
                                        ) ?>

                                </span>

                            </div>


                            <!-- PRODUCT NAME -->
                            <h3 class="marketplace-product-name">

                                <?= htmlspecialchars($product->name) ?>

                            </h3>


                            <!-- SELLER -->
                            <div class="marketplace-seller">

                                <i class="bi bi-shop"></i>

                                <span>

                                    <?= htmlspecialchars(
                                        $product->shop_name
                                            ?? $seller->shop_name
                                            ?? 'Local Farm'
                                    ) ?>

                                </span>

                                <?php if (!empty($product->verification_badge)): ?>

                                    <i class="bi <?= BadgeCalculationService::getBadgeIcon($product->verification_badge) ?> marketplace-verified" title="<?= htmlspecialchars(
                                                                                                        BadgeCalculationService::getBadgeLabel(
                                                                                                            $product->verification_badge
                                                                                                        )
                                                                                                    ) ?>" aria-label="<?= htmlspecialchars(BadgeCalculationService::getBadgeLabel($product->verification_badge)) ?>">
                                    </i>

                                <?php endif; ?>

                            </div>


                            <!-- STOCK -->
                            <div class="marketplace-stock">

                                <?php if ((int) ($product->stock ?? 0) > 0): ?>

                                    <?php if ((int) $product->stock <= 3): ?>

                                        <span class="stock-low">
                                            Only <?= (int) $product->stock ?> left
                                        </span>

                                    <?php else: ?>

                                        <span class="stock-available">
                                            Available stock:
                                            <?= (int) $product->stock ?>
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
                                        (float) ($product->avg_rating ?? 0),
                                        1
                                    ) ?>

                                </strong>

                                <span>

                                    (<?= (int) ($product->review_count ?? 0) ?>
                                    Reviews)

                                </span>

                            </div>


                            <!-- ACTION -->
                            <div class="marketplace-card-actions">

                                <?php if ($currentRole === 'admin'): ?>

                                    <span class="badge bg-dark marketplace-status-badge">

                                        <i class="bi bi-shield-lock me-1"></i>
                                        Admin View

                                    </span>


                                <?php elseif ($isSeller): ?>

                                    <span class="badge bg-secondary marketplace-status-badge">

                                        <i class="bi bi-person-check me-1"></i>
                                        Your Product

                                    </span>


                                <?php elseif ((int) ($product->stock ?? 0) <= 0): ?>

                                            <button type="button" class="btn marketplace-out-of-stock-btn" disabled>
                                                Out of Stock
                                            </button>

                                        <?php elseif ($currentRole === 'guest'): ?>

                                    <button type="button" class="btn marketplace-add-cart-btn" data-bs-toggle="modal"
                                        data-bs-target="#loginRequiredModal">

                                        <i class="bi bi-cart-plus me-1"></i>
                                        Add to Cart

                                    </button>


                                <?php elseif ($currentRole === 'customer'): ?>

                                    <button type="button" class="btn marketplace-add-cart-btn addToCartBtn"
                                        data-id="<?= (int) $product->id ?>" title="Add to cart"
                                        aria-label="Add <?= htmlspecialchars($product->name) ?> to cart">

                                        <i class="bi bi-cart-plus me-1"></i>
                                        Add to Cart

                                    </button>

                                <?php endif; ?>

                            </div>

                        </div>

                    </div>

                </div>

            <?php endforeach; ?>


            <?php if (count($products) === 0): ?>

                <div class="col-12">

                    <div class="text-center py-5">

                        <i class="bi bi-basket fs-1 text-muted mb-3 d-block"></i>

                        <h3 class="h5 text-muted">
                            No products available
                        </h3>

                        <p class="text-muted mb-0">
                            This farm has not listed active products yet.
                        </p>

                    </div>

                </div>

            <?php endif; ?>

        </div>

        </section>

        <div id="farm-about" class="farm-about-heading"><p class="farm-eyebrow">MEET YOUR GROWER</p><h2>Behind the harvest</h2></div>

        <?php if (!empty($seller->description)): ?>

            <?php
            $descriptionLength = mb_strlen($shopDescription);
            $descriptionLimit = 150;
            $hasMoreDescription = $descriptionLength > $descriptionLimit;

            $shortDescription = $hasMoreDescription
                ? mb_substr($shopDescription, 0, $descriptionLimit) . '...'
                : $shopDescription;
            ?>

            <section class="farm-description mb-5">

                <p class="text-uppercase small fw-bold mb-2 shop-accent-text">
                    About this shop
                </p>

                <div class="farm-description-content">

                    <!-- SHORT DESCRIPTION -->
                    <p id="shopDescriptionShort" class="farm-public-description farm-description-text mb-0">
                        <?= nl2br(htmlspecialchars($shortDescription)) ?>
                    </p>

                    <?php if ($hasMoreDescription): ?>

                        <!-- FULL DESCRIPTION -->
                        <p id="shopDescriptionFull" class="farm-public-description farm-description-text mb-0"
                            hidden>
                            <?= nl2br(htmlspecialchars($shopDescription)) ?>
                        </p>

                        <!-- READ MORE BUTTON -->
                        <button type="button" id="shopDescriptionToggle" class="farm-description-toggle" aria-expanded="false">
                            <span>Read More</span>
                            <i class="bi bi-chevron-down"></i>
                        </button>

                    <?php endif; ?>

                </div>

            </section>

        <?php endif; ?>

        <?php if (!empty($seller->farm_story)): ?>
            <section class="farm-story mb-5">
                <p class="text-uppercase small fw-bold mb-2 shop-accent-text">About the farm</p>
                <h2 class="h3 fw-bold mb-3">Grown with care</h2>
                <p class="mb-0 text-muted"><?= nl2br(htmlspecialchars($farmStory)) ?></p>
            </section>
        <?php endif; ?>

        <section class="farm-customer-activity mb-5">

            <!-- SECTION HEADER -->
            <div class="farm-section-heading d-flex align-items-center gap-3 mb-4">

                <span class="farm-heading-icon">
                    <i class="bi bi-people-fill"></i>
                </span>

                <div>
                    <h2 class="h4 fw-bold mb-1">
                        Customers who shop here
                    </h2>

                    <p class="text-muted mb-0">
                        Aggregate activity from completed purchases.
                    </p>
                </div>

            </div>


            <?php if (($customerStats['completed_orders'] ?? 0) > 0): ?>

                <!-- CUSTOMER STATISTICS -->
                <div class="customer-stats-grid">

                    <!-- CUSTOMERS -->
                    <div class="customer-stat-item">

                        <div class="farm-stat-icon">
                            <i class="bi bi-person-check"></i>
                        </div>

                        <div class="customer-stat-content">
                            <span>Customers</span>

                            <strong>
                                <?= (int) ($customerStats['unique_customers'] ?? 0) ?>
                            </strong>
                        </div>

                    </div>


                    <!-- PRODUCTS PURCHASED -->
                    <div class="customer-stat-item">

                        <div class="farm-stat-icon">
                            <i class="bi bi-basket2"></i>
                        </div>

                        <div class="customer-stat-content">
                            <span>Products purchased</span>

                            <strong>
                                <?= (int) ($customerStats['products_purchased'] ?? 0) ?>
                            </strong>
                        </div>

                    </div>


                    <!-- RETURNING CUSTOMERS -->
                    <div class="customer-stat-item">

                        <div class="farm-stat-icon">
                            <i class="bi bi-arrow-repeat"></i>
                        </div>

                        <div class="customer-stat-content">
                            <span>Returning customers</span>

                            <strong>
                                <?= (int) ($customerStats['returning_customers'] ?? 0) ?>
                            </strong>
                        </div>

                    </div>

                </div>

            <?php else: ?>

                <div class="alert alert-light border rounded-4 mb-0">
                    Customer statistics will appear after completed purchases.
                </div>

            <?php endif; ?>

        </section>

        <!-- =========================================================
     FOLLOWERS / FOLLOW FARM
========================================================= -->

        <div class="farm-follow-bar d-flex align-items-center justify-content-between gap-3 mb-4">

            <!-- FOLLOWERS COUNT -->
            <div class="d-flex align-items-center gap-3">

                <span class="farm-heading-icon">
                    <i class="bi bi-heart-fill"></i>
                </span>

                <div>
                    <span class="text-muted">Followers</span>

                    <h3 id="followersCount" class="fw-bold mb-0">
                        <?= (int) ($followersCount ?? 0) ?>
                    </h3>
                </div>

            </div>


            <!-- =====================================================
         FOLLOW BUTTON
         ONLY CUSTOMERS SEE THIS
         
         Seller role in Ani-Mart = "farmer"
    ====================================================== -->

            <div class="d-flex flex-wrap align-items-center gap-2">
            <?php if (!$isSeller): ?>

                <button type="button" id="followFarmBtn"
                    class="btn farm-follow-button<?= $isFollowing ? ' is-following' : '' ?> rounded-pill"
                    data-action="<?= $isFollowing ? 'unfollow' : 'follow' ?>">

                    <i class="bi <?= $isFollowing ? 'bi-person-x' : 'bi-person-plus' ?> me-2"></i>

                    <?= $isFollowing ? 'Unfollow' : 'Follow' ?> Farm

                </button>

            <?php endif; ?>

            </div>

        </div>

        <div class="farm-profile-summary mb-5">

            <div class="farm-info-tile">
                <div class="info-tile-icon"><i class="bi bi-person-check"></i></div>
                <div>
                    <span>Managed By</span>
                    <strong><?= htmlspecialchars(trim(($seller->first_name ?? '') . ' ' . ($seller->last_name ?? ''))) ?></strong>
                </div>
            </div>

            <div class="farm-info-tile">
                <div class="info-tile-icon"><i class="bi bi-shop-window"></i></div>
                <div>
                    <span>Storefront</span>
                    <strong><?= htmlspecialchars($seller->shop_name ?? 'Farm') ?></strong>
                </div>
            </div>

        </div>

        <?php
        $sections = [
            'Farm Gallery' => $farmGallery,
            'Harvest Gallery' => $harvestGallery
        ];
        ?>

        <?php foreach ($sections as $heading => $gallery): ?>

            <?php if (count($gallery) > 0): ?>

                <section class="mb-5">

                    <h2 class="h4 fw-bold mb-3"><?= htmlspecialchars($heading) ?></h2>

                    <?php $carouselId = 'farmGalleryCarousel-' . strtolower(str_replace(' ', '-', $heading)) . '-' . $seller->id; ?>
                    <div id="<?= htmlspecialchars($carouselId) ?>" class="carousel slide farm-gallery-carousel"
                        data-bs-ride="carousel" data-bs-interval="2000" data-bs-pause="hover">
                        <div class="carousel-inner">
                            <?php foreach ($gallery as $index => $image): ?>
                                <div class="carousel-item<?= $index === 0 ? ' active' : '' ?>">
                                    <img src="<?= htmlspecialchars($image->image_path) ?>" class="d-block w-100 rounded-4"
                                        alt="<?= htmlspecialchars($heading) ?>" data-image-modal="true">
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <?php if (count($gallery) > 1): ?>
                            <button class="carousel-control-prev" type="button"
                                data-bs-target="#<?= htmlspecialchars($carouselId) ?>" data-bs-slide="prev">
                                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Previous</span>
                            </button>
                            <button class="carousel-control-next" type="button"
                                data-bs-target="#<?= htmlspecialchars($carouselId) ?>" data-bs-slide="next">
                                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Next</span>
                            </button>
                        <?php endif; ?>
                    </div>

                </section>

            <?php endif; ?>

        <?php endforeach; ?>

        <section id="farm-community" class="farm-community-section py-5" aria-labelledby="farm-community-title">
            <div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mb-4">
                <div>
                    <p class="farm-eyebrow shop-accent-text mb-1">FROM THE COMMUNITY</p>
                    <h2 class="h3 fw-bold mb-1" id="farm-community-title">Latest farm updates</h2>
                    <p class="text-muted mb-0">Public posts from <?= htmlspecialchars($seller->shop_name ?? 'this farm') ?>.</p>
                </div>
                <?php if ($user && (int) ($user['id'] ?? 0) === (int) $seller->user_id): ?>
                    <a href="/ani-mart/public/community/create" class="btn btn-success rounded-pill">
                        <i class="bi bi-plus-lg me-1"></i>Create post
                    </a>
                <?php endif; ?>
            </div>

            <?php if (empty($communityPosts)): ?>
                <div class="farm-community-empty rounded-4 p-5 text-center">
                    <i class="bi bi-chat-square-text fs-1 shop-accent-text d-block mb-3"></i>
                    <h3 class="h5 mb-2">No public posts yet</h3>
                    <p class="text-muted mb-0">This farm’s public community updates will appear here.</p>
                </div>
            <?php else: ?>
                <div class="row g-4 justify-content-center" id="farmCommunityPosts">
                    <?php foreach ($communityPosts as $postIndex => $post): ?>
                        <div class="col-md-6 farm-community-post-item<?= $postIndex >= 2 ? ' d-none' : '' ?>">
                            <?php require __DIR__ . '/../community/partials/post-card.php'; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php if (count($communityPosts) > 2): ?>
                    <div class="text-center mt-4">
                        <button type="button" class="btn btn-outline-success rounded-pill px-4" id="farmCommunityMore" data-visible="2">
                            <i class="bi bi-chevron-down me-1"></i>Show more posts
                        </button>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </section>


    </div>

</div>

<script>
    document.querySelectorAll('.addToCartBtn').forEach(button => {
        button.addEventListener('click', function() {
            const formData = new FormData();
            formData.append('product_id', this.dataset.id);

            fetch('/ani-mart/public/cart/add', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        return;
                    }

                    const cartBadge = document.getElementById('cartBadge');

                    if (cartBadge) {
                        cartBadge.innerText = data.count;
                    }
                });
        });
    });

    const followFarmBtn = document.getElementById('followFarmBtn');
    if (followFarmBtn) {
        followFarmBtn.addEventListener('click', function() {
            const action = this.dataset.action;
            const formData = new FormData();
            const slug = '<?= htmlspecialchars(rawurlencode($seller->slug ?? '')) ?>';

            fetch(`/ani-mart/public/farm/${slug}/${action}`, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        if (data.redirect) {
                            window.location.href = data.redirect;
                            return;
                        }
                        alert(data.message || 'Unable to update follow status.');
                        return;
                    }

                    followFarmBtn.dataset.action = data.is_following ? 'unfollow' : 'follow';
                    followFarmBtn.className = `btn farm-follow-button${data.is_following ? ' is-following' : ''} rounded-pill`;
                    followFarmBtn.innerHTML = `${data.is_following ? '<i class="bi bi-person-x me-2"></i>Unfollow Farm' : '<i class="bi bi-person-plus me-2"></i>Follow Farm'}`;
                    const followersCount = document.getElementById('followersCount');
                    if (followersCount) {
                        followersCount.innerText = data.followers_count;
                    }
                })
                .catch(() => {
                    alert('Unable to update follow status.');
                });
        });
    }
    /* =========================================================
   SHOP DESCRIPTION - READ MORE / READ LESS
========================================================= */

    const shopDescriptionToggle =
        document.getElementById('shopDescriptionToggle');

    const shopDescriptionShort =
        document.getElementById('shopDescriptionShort');

    const shopDescriptionFull =
        document.getElementById('shopDescriptionFull');

    if (
        shopDescriptionToggle &&
        shopDescriptionShort &&
        shopDescriptionFull
    ) {

        shopDescriptionToggle.addEventListener('click', function() {

            const expanded =
                this.getAttribute('aria-expanded') === 'true';

            if (expanded) {

                // Collapse
                shopDescriptionFull.hidden = true;
                shopDescriptionShort.hidden = false;

                this.setAttribute('aria-expanded', 'false');
                this.classList.remove('expanded');

                this.querySelector('span').textContent = 'Read More';

            } else {

                // Expand
                shopDescriptionShort.hidden = true;
                shopDescriptionFull.hidden = false;

                this.setAttribute('aria-expanded', 'true');
                this.classList.add('expanded');

                this.querySelector('span').textContent = 'Read Less';
            }

        });

    }

    const farmCommunityMore = document.getElementById('farmCommunityMore');
    if (farmCommunityMore) {
        farmCommunityMore.addEventListener('click', function() {
            const posts = Array.from(document.querySelectorAll('.farm-community-post-item'));
            const visibleCount = Number(this.dataset.visible || 2);
            const nextVisibleCount = Math.min(visibleCount + 2, posts.length);

            posts.slice(0, nextVisibleCount).forEach(post => post.classList.remove('d-none'));
            this.dataset.visible = String(nextVisibleCount);

            if (nextVisibleCount >= posts.length) {
                this.remove();
            }
        });
    }
</script>

<?php if (!empty($communityPosts)): ?>
    <?php require __DIR__ . '/../community/partials/feed-actions-script.php'; ?>
<?php endif; ?>



<?php

$content = ob_get_clean();

$pageCss = '/ani-mart/public/assets/css/pages/farm.css?v='
    . filemtime(__DIR__ . '/../../../public/assets/css/pages/farm.css');
require __DIR__ . '/../layouts/main.php';

?>
