<?php

$title = "Marketplace";

use App\Services\BadgeCalculationService;
use App\Services\SellerProfileService;

ob_start();

$user =
    $_SESSION['user'] ?? [];

$currentUserId =
    $user['id'] ?? 0;

$currentRole =
    strtolower(
        $user['role'] ?? 'guest'
    );

$products = $products ?? [];
$categories = $categories ?? [];
$recommendedProducts = $recommendedProducts ?? [];
$productImagesByProduct = $productImagesByProduct ?? [];
$productReviewsByProduct = $productReviewsByProduct ?? [];
$productVariantsByProduct = $productVariantsByProduct ?? [];
$topFarmerTabs = $topFarmerTabs ?? [
    'weekly' => 'Weekly',
    'monthly' => 'Monthly',
    'yearly' => 'Yearly'
];
$hasPersonalizedRecommendations = $hasPersonalizedRecommendations ?? false;
$topCustomersBySeller = $topCustomersBySeller ?? [];

/* =========================================================
   HARVEST LABEL
   ========================================================= */

function harvest_label($harvestDate)
{
    if (empty($harvestDate)) {
        return 'Harvest date not provided';
    }

    $today =
        new DateTime('today');

    $harvestedAt =
        new DateTime($harvestDate);

    $days =
        (int) $today->diff($harvestedAt)->format('%r%a');

    if ($days === 0) {
        return 'Harvested Today';
    }

    if ($days === -1) {
        return 'Harvested Yesterday';
    }

    if ($days < -1) {
        return 'Harvested ' . abs($days) . ' Days Ago';
    }

    return 'Harvest date pending';
}

function marketplace_contrast_color(string $hexColor): string
{
    if (!preg_match('/^#([0-9a-f]{6})$/i', $hexColor, $matches)) {
        return '#ffffff';
    }

    $channels = array_map(
        static function (string $channel): float {
            $value = hexdec($channel) / 255;
            return $value <= 0.04045
                ? $value / 12.92
                : (($value + 0.055) / 1.055) ** 2.4;
        },
        str_split($matches[1], 2)
    );
    $luminance = (0.2126 * $channels[0]) + (0.7152 * $channels[1]) + (0.0722 * $channels[2]);
    $whiteContrast = 1.05 / ($luminance + 0.05);
    $darkContrast = ($luminance + 0.05) / 0.064;

    return $whiteContrast >= $darkContrast ? '#ffffff' : '#17211b';
}

function marketplace_product_location($product): string
{
    $parts = [];

    foreach ([$product->barangay ?? '', $product->city ?? ''] as $part) {
        $part = trim((string) $part);
        if ($part !== '' && !in_array(mb_strtolower($part), array_map('mb_strtolower', $parts), true)) {
            $parts[] = $part;
        }
    }

    if ($parts !== []) {
        return implode(', ', $parts);
    }

    $farmLocation = trim((string) ($product->farm_location ?? ''));

    return $farmLocation !== '' ? $farmLocation : 'Location not provided';
}


// Share one detail dialog across main and recommended product cards.
$modalProducts = [];
foreach (array_merge($recommendedProducts ?? [], $products) as $modalProduct) {
    $modalProducts[(int) $modalProduct->id] = $modalProduct;
}

$farmerModals = '';

$topFarmerTabs = [
    'weekly' => 'Weekly',
    'monthly' => 'Monthly',
    'yearly' => 'Yearly'
];


/* =========================================================
   SHARED MARKETPLACE PRODUCT CARD RENDERER
   ========================================================= */

function renderMarketplaceProductCard(
    $product,
    $productImagesByProduct,
    $productVariantsByProduct,
    $currentRole,
    $currentUserId,
    $prefix = 'product'
) {
    $productId =
        (int) $product->id;

    $activeVariants = $productVariantsByProduct[$productId] ?? [];
    $defaultVariantId = count($activeVariants) === 1 ? (int) $activeVariants[0]->id : 0;
    $displayStock = count($activeVariants) === 1
        ? (int) $activeVariants[0]->stock
        : (int) ($product->stock ?? 0);
    $shopThemes = SellerProfileService::themes();
    $shopTheme = $shopThemes[$product->shop_theme ?? 'fresh-farm'] ?? $shopThemes['fresh-farm'];
    $ownerBadgeColor = $shopTheme['accent'];
    $ownerBadgeTextColor = marketplace_contrast_color($ownerBadgeColor);
    $productLocation = marketplace_product_location($product);

    $carouselImages =
        $productImagesByProduct[$productId] ?? [];

    if (empty($carouselImages)) {
        $carouselImages = [
            (object) [
                'image_url' =>
                    $product->image
                    ?: '/ani-mart/public/assets/img/product-placeholder.svg'
            ]
        ];
    }

    $carouselId =
        $prefix .
        '-product-carousel-' .
        $productId;
?>

    <div
        id="<?= htmlspecialchars($prefix . '-product-' . $productId) ?>"
        class="col-6 col-md-6 col-lg-4 col-xl-3 product-item"
        tabindex="-1"
        data-name="<?= htmlspecialchars(
            strtolower($product->name ?? '')
        ) ?>"
        data-category="<?= htmlspecialchars(
            strtolower($product->category_name ?? '')
        ) ?>">

        <div
            class="marketplace-card product-card h-100"
            data-modal-target="#productModal<?= $productId ?>"
            data-id="<?= $productId ?>"
            data-name="<?= htmlspecialchars(
                $product->name ?? ''
            ) ?>"
            data-price="<?= number_format(
                (float) ($product->price ?? 0),
                2
            ) ?>"
            data-description="<?= htmlspecialchars(
                $product->description ?? ''
            ) ?>"
            data-image="<?= htmlspecialchars(
                $product->image ?? ''
            ) ?>"
            data-category="<?= htmlspecialchars(
                $product->category_name ?? ''
            ) ?>"
            data-stock="<?= $displayStock ?>"
            data-shop="<?= htmlspecialchars(
                $product->shop_name ?? ''
            ) ?>"
            data-barangay="<?= htmlspecialchars(
                $product->barangay ?? ''
            ) ?>"
            data-city="<?= htmlspecialchars(
                $product->city ?? ''
            ) ?>"
            data-location="<?= htmlspecialchars($productLocation) ?>"
            data-trust="<?= htmlspecialchars(
                $product->trust_score ?? 0
            ) ?>"
            data-transparency="<?= htmlspecialchars(
                $product->transparency_score ?? 0
            ) ?>"
            data-badge="<?= htmlspecialchars(
                $product->verification_badge ?? ''
            ) ?>"
            data-harvest-date="<?= htmlspecialchars(
                $product->harvest_date ?? ''
            ) ?>"
            data-harvest-photo="<?= htmlspecialchars(
                $product->harvest_photo ?? ''
            ) ?>"
            data-rating="<?= htmlspecialchars(
                $product->avg_rating ?? 0
            ) ?>"
            data-reviews="<?= htmlspecialchars(
                $product->review_count ?? 0
            ) ?>"
            data-store="<?= htmlspecialchars(
                $product->seller_profile_id ?? 0
            ) ?>">

            <!-- =================================================
                 PRODUCT IMAGE CAROUSEL
                 ================================================= -->

            <div
                id="<?= htmlspecialchars($carouselId) ?>"
                class="carousel slide marketplace-card-carousel skeleton-image-wrap is-loading"
                data-bs-ride="carousel"
                data-bs-interval="2000"
                data-bs-pause="hover">

                <div class="carousel-inner">

                    <?php foreach (
                        $carouselImages
                        as $index => $image
                    ): ?>

                        <div
                            class="carousel-item<?= $index === 0 ? ' active' : '' ?>">

                            <img
                                src="<?= htmlspecialchars(
                                    $image->image_url
                                ) ?>"
                                class="d-block w-100 marketplace-image"
                                alt="<?= htmlspecialchars(
                                    $product->name ?? 'Product'
                                ) ?>">

                        </div>

                    <?php endforeach; ?>

                </div>


                <?php if (count($carouselImages) > 1): ?>

                    <button
                        class="carousel-control-prev"
                        type="button"
                        data-bs-target="#<?= htmlspecialchars(
                            $carouselId
                        ) ?>"
                        data-bs-slide="prev">

                        <span
                            class="carousel-control-prev-icon"
                            aria-hidden="true">
                        </span>

                        <span class="visually-hidden">
                            Previous
                        </span>

                    </button>


                    <button
                        class="carousel-control-next"
                        type="button"
                        data-bs-target="#<?= htmlspecialchars(
                            $carouselId
                        ) ?>"
                        data-bs-slide="next">

                        <span
                            class="carousel-control-next-icon"
                            aria-hidden="true">
                        </span>

                        <span class="visually-hidden">
                            Next
                        </span>

                    </button>

                <?php endif; ?>

            </div>


            <!-- =================================================
                 PRODUCT CONTENT
                 ================================================= -->

            <div class="marketplace-card-content">

                <div class="marketplace-card-top">

                    <span class="marketplace-category">

                        <?= htmlspecialchars(
                            $product->category_name ?? 'Other'
                        ) ?>

                    </span>


                    <span class="marketplace-price">

                        ₱<?= number_format(
                            (float) ($product->price ?? 0),
                            2
                        ) ?>

                    </span>

                </div>


                <h5 class="marketplace-product-name">

                    <button type="button" class="marketplace-product-link"
                        data-bs-toggle="modal" data-bs-target="#productModal<?= $productId ?>">
                        <?= htmlspecialchars($product->name ?? 'Product') ?>
                    </button>

                </h5>


                <div class="marketplace-seller">

                    <i class="bi bi-shop"></i>

                    <span>

                        <?= htmlspecialchars(
                            $product->shop_name ?? 'Local Farm'
                        ) ?>

                    </span>


                    <?php if (!empty($product->verification_badge)): ?>

                        <i
                            class="bi <?= BadgeCalculationService::getBadgeIcon($product->verification_badge) ?> marketplace-verified"
                            title="<?= htmlspecialchars(
                                BadgeCalculationService::getBadgeLabel(
                                    $product->verification_badge
                                )
                            ) ?>"
                            aria-label="<?= htmlspecialchars(BadgeCalculationService::getBadgeLabel($product->verification_badge)) ?>">
                        </i>

                    <?php endif; ?>

                </div>

                <div class="marketplace-location" title="<?= htmlspecialchars($productLocation) ?>">
                    <i class="bi bi-geo-alt-fill" aria-hidden="true"></i>
                    <span><?= htmlspecialchars($productLocation) ?></span>
                </div>


                <!-- STOCK -->

                <div class="marketplace-stock">

                    <?php if (
                        $displayStock > 0
                    ): ?>

                        <?php if (
                            $displayStock <= 3
                        ): ?>

                            <span class="stock-low">

                                Only
                                <?= $displayStock ?>
                                left

                            </span>

                        <?php else: ?>

                            <span class="stock-available">

                                Available stock:
                                <?= $displayStock ?>

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

                        (<?= (int) (
                            $product->review_count ?? 0
                        ) ?> Reviews)

                    </span>

                </div>


                <!-- ACTIONS -->

                <div class="marketplace-card-actions">

                    <?php if ($displayStock <= 0): ?>

                        <button
                            type="button"
                            class="btn marketplace-out-of-stock-btn"
                            disabled>

                            Out of Stock

                        </button>


                    <?php elseif (
                        $currentRole === 'admin'
                    ): ?>

                        <span
                            class="badge bg-dark marketplace-status-badge">

                            <i class="bi bi-shield-lock me-1"></i>

                            Admin View

                        </span>


                    <?php elseif (
                        $currentUserId ==
                        ($product->seller_user_id ?? 0)
                    ): ?>

                        <span
                            class="badge marketplace-owner-badge marketplace-status-badge"
                            style="--owner-theme-color: <?= htmlspecialchars($ownerBadgeColor) ?>; --owner-theme-contrast: <?= htmlspecialchars($ownerBadgeTextColor) ?>;">

                            <i class="bi bi-person-check me-1"></i>

                            Your Product

                        </span>


                    <?php elseif (
                        $currentRole === 'guest'
                    ): ?>

                        <button
                            type="button"
                            class="btn btn-success marketplace-add-cart-btn"
                            data-bs-toggle="modal"
                            data-bs-target="#loginRequiredModal">

                            <i class="bi bi-cart-plus me-1"></i>

                            Add to Cart

                        </button>


                    <?php else: ?>

                        <?php if (count($activeVariants) > 1): ?>
                            <button
                                type="button"
                                class="btn btn-success marketplace-add-cart-btn"
                                data-bs-toggle="modal"
                                data-bs-target="#productModal<?= $productId ?>"
                                title="Choose a size"
                                onclick="event.stopPropagation();">
                                <i class="bi bi-sliders me-1"></i>
                                Choose Size
                            </button>
                        <?php else: ?>

                        <button
                            type="button"
                            class="btn btn-success marketplace-add-cart-btn addToCartBtn"
                            data-id="<?= $productId ?>"
                            data-variant-id="<?= $defaultVariantId ?>"
                            title="Add to cart"
                            aria-label="Add <?= htmlspecialchars(
                                $product->name ?? 'product'
                            ) ?> to cart"
                            onclick="event.stopPropagation();">

                            <i class="bi bi-cart-plus me-1"></i>

                            Add to Cart

                        </button>

                        <?php endif; ?>

                    <?php endif; ?>

                </div>

            </div>

        </div>

    </div>

<?php
}
?>


<div class="container app-page-spacing marketplace-page">

    <!-- =========================================================
         HERO
         ========================================================= -->

    <div class="marketplace-hero mb-5">

        <div class="row align-items-center">

            <div class="col-lg-7">

                <span class="marketplace-badge">
                    Fresh Local Marketplace
                </span>

                <h1 class="marketplace-title mt-3">

                    Discover Fresh Farm Products

                </h1>

                <p class="marketplace-subtitle">

                    Support local farmers and buy fresh
                    vegetables, fruits and organic products.

                </p>

            </div>


            <div class="col-lg-5">

                <img
                    src="https://images.unsplash.com/photo-1542838132-92c53300491e?q=80&w=1200"
                    class="img-fluid marketplace-hero-image"
                    alt="Fresh farm products">

            </div>

        </div>

    </div>


    <!-- =========================================================
         PRODUCT DISCOVERY
         ========================================================= -->

    <section class="marketplace-search marketplace-discovery mb-5" aria-labelledby="marketplace-discovery-title">
        <div class="marketplace-catalog-heading">
            <div>
                <h2 id="marketplace-discovery-title">Find fresh products</h2>
                <p>Search the marketplace or browse by category.</p>
            </div>
        </div>

        <div class="row g-3">

            <div class="col-lg-8">

                <label for="searchInput" class="marketplace-filter-label">Find a product</label>
                <div class="input-group">
                    <input
                        type="search"
                        id="searchInput"
                        class="form-control marketplace-input"
                        placeholder="Search products..."
                        list="marketplaceProductSuggestions"
                        autocomplete="off"
                        aria-describedby="marketplaceSearchHelp"
                        aria-label="Search products">

                    <button
                        type="button"
                        id="marketplaceSearchButton"
                        class="btn btn-success px-4 rounded-end-pill"
                        aria-label="Search products">
                        Search
                    </button>
                </div>

                <datalist id="marketplaceProductSuggestions">
                    <?php foreach ($products as $searchProduct): ?>
                        <option value="<?= htmlspecialchars($searchProduct->name ?? '') ?>"></option>
                    <?php endforeach; ?>
                </datalist>

                <div class="form-text marketplace-search-help" id="marketplaceSearchHelp">
                    Choose a suggestion, press Enter, or click Search to jump to the closest matching product.
                </div>

            </div>


            <div class="col-lg-4">

                <label for="categoryFilter" class="marketplace-filter-label">Category</label>
                <select
                    id="categoryFilter"
                    class="form-select marketplace-input"
                    aria-label="Filter by category">

                    <option value="">
                        All Categories
                    </option>

                    <?php foreach (
                        $categories
                        as $category
                    ): ?>

                        <option
                            value="<?= htmlspecialchars(
                                strtolower(
                                    $category->name
                                )
                            ) ?>">

                            <?= htmlspecialchars(
                                $category->name
                            ) ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>

        </div>

    </section>


    <!-- =========================================================
         TOP FARMERS
         ========================================================= -->

    <?php if (!empty($topFarmerTabs)): ?>

        <section class="top-farmers-section mb-5">

            <div
                class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">

                <div>

                    <span class="section-badge">

                        <i class="bi bi-trophy-fill me-1"></i>

                        Top Sellers

                    </span>

                    <h2 class="top-farmers-title mt-3 mb-1">

                        Marketplace leaders

                    </h2>

                    <p class="text-muted mb-0">

                        Ranked by completed sales from trusted local farms.

                    </p>

                </div>


                <ul
                    class="nav nav-pills top-farmers-tabs"
                    id="topFarmersTabs"
                    role="tablist">

                    <?php foreach (
                        $topFarmerTabs
                        as $periodKey => $periodLabel
                    ): ?>

                        <li
                            class="nav-item"
                            role="presentation">

                            <button
                                class="nav-link <?= $periodKey === 'weekly' ? 'active' : '' ?>"
                                id="top-<?= $periodKey ?>-tab"
                                data-bs-toggle="pill"
                                data-bs-target="#top-<?= $periodKey ?>"
                                type="button"
                                role="tab"
                                aria-controls="top-<?= $periodKey ?>"
                                aria-selected="<?= $periodKey === 'weekly' ? 'true' : 'false' ?>">

                                <?= $periodLabel ?>

                            </button>

                        </li>

                    <?php endforeach; ?>

                </ul>

            </div>


            <div
                class="tab-content"
                id="topFarmersTabsContent">

                <?php foreach (
                    $topFarmerTabs
                    as $periodKey => $periodLabel
                ): ?>

                    <?php
                    $farmers =
                        array_slice($topFarmers[$periodKey] ?? [], 0, 3);
                    ?>

                    <div
                        class="tab-pane fade <?= $periodKey === 'weekly' ? 'show active' : '' ?>"
                        id="top-<?= $periodKey ?>"
                        role="tabpanel"
                        aria-labelledby="top-<?= $periodKey ?>-tab">

                        <?php if (!empty($farmers)): ?>

                            <div class="top-farmers-compact-list">

                                <?php foreach (
                                    $farmers
                                    as $index => $farmer
                                ): ?>

                                    <?php

                                    $farmerId =
                                        (int) $farmer->id;

                                    $farmerModalId =
                                        'farmerDetailsModal-' .
                                        $periodKey .
                                        '-' .
                                        $farmerId;

                                    $farmerSlug =
                                        !empty($farmer->slug)
                                            ? $farmer->slug
                                            : $farmer->id;

                                    $farmerUrl =
                                        '/ani-mart/public/farm/' .
                                        rawurlencode(
                                            (string) $farmerSlug
                                        );

                                    $sellerCustomerStats =
                                        $topCustomersBySeller[$farmerId]
                                        ?? [
                                            'top_customers' => [],
                                            'total_customers' => 0
                                        ];

                                    ?>


                                    <!-- TOP SELLER ITEM -->

                                    <button
                                        type="button"
                                        class="top-farmer-compact"
                                        data-bs-toggle="modal"
                                        data-bs-target="#<?= htmlspecialchars(
                                            $farmerModalId
                                        ) ?>">

                                        <div
                                            class="top-farmer-crown"
                                            aria-hidden="true">

                                            <span class="crown-icon">
                                                👑
                                            </span>

                                        </div>


                                        <div class="top-farmer-rank-badge">

                                            <?= $index + 1 ?>

                                        </div>


                                        <div class="top-farmer-avatar-wrap">

                                            <div class="top-farmer-compact-avatar">

                                                <?php if (
                                                    !empty($farmer->farm_logo)
                                                ): ?>

                                                    <img
                                                        src="<?= htmlspecialchars(
                                                            $farmer->farm_logo
                                                        ) ?>"
                                                        alt="<?= htmlspecialchars(
                                                            $farmer->shop_name ?? 'Farm'
                                                        ) ?>">

                                                <?php else: ?>

                                                    <?= strtoupper(
                                                        substr(
                                                            $farmer->shop_name
                                                            ?? 'F',
                                                            0,
                                                            1
                                                        )
                                                    ) ?>

                                                <?php endif; ?>

                                            </div>

                                        </div>


                                        <div class="top-farmer-info">

                                            <div class="top-farmer-compact-name">

                                                <span>

                                                    <?= htmlspecialchars(
                                                        $farmer->shop_name
                                                        ?? 'Farm'
                                                    ) ?>

                                                </span>


                                                <?php if (!empty($farmer->verification_badge)): ?>

                                                    <i
                                                        class="bi <?= BadgeCalculationService::getBadgeIcon($farmer->verification_badge) ?>"
                                                        title="<?= htmlspecialchars(
                                                            BadgeCalculationService::getBadgeLabel(
                                                                $farmer->verification_badge
                                                            )
                                                        ) ?>">
                                                    </i>

                                                <?php endif; ?>

                                            </div>


                                            <div class="top-farmer-tagline">

                                                <i class="bi bi-leaf-fill"></i>

                                                Trusted local farm

                                            </div>


                                            <div class="top-farmer-stats">

                                                <span>

                                                    <i class="bi bi-cart-check-fill"></i>

                                                    <?php $completedSales = (int) ($farmer->total_orders ?? 0); ?>
                                                    <?= number_format($completedSales) ?>

                                                    <?= $completedSales === 1 ? 'Sale' : 'Sales' ?>

                                                </span>


                                                <span>

                                                    <i class="bi bi-star-fill"></i>

                                                    <?= number_format(
                                                        (float) (
                                                            $farmer->avg_rating
                                                            ?? 0
                                                        ),
                                                        1
                                                    ) ?>

                                                </span>

                                            </div>

                                        </div>

                                    </button>


                                    <?php ob_start(); ?>
                                    <!-- =================================================
                                         FARMER DETAILS MODAL
                                         ================================================= -->

                                    <div
                                        class="modal fade farmer-details-modal"
                                        id="<?= htmlspecialchars(
                                            $farmerModalId
                                        ) ?>"
                                        tabindex="-1"
                                        aria-labelledby="<?= htmlspecialchars($farmerModalId) ?>-title"
                                        aria-hidden="true">

                                        <div
                                            class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">

                                            <div class="modal-content">

                                                <div
                                                    class="modal-header farmer-modal-header">

                                                    <div
                                                        class="d-flex align-items-center gap-3">

                                                        <div class="farmer-modal-avatar">

                                                            <?php if (
                                                                !empty(
                                                                    $farmer->farm_logo
                                                                )
                                                            ): ?>

                                                                <img
                                                                    src="<?= htmlspecialchars(
                                                                        $farmer->farm_logo
                                                                    ) ?>"
                                                                    alt="<?= htmlspecialchars(
                                                                        $farmer->shop_name
                                                                            ?? 'Farm'
                                                                    ) ?>">

                                                            <?php else: ?>

                                                                <?= strtoupper(
                                                                    substr(
                                                                        $farmer->shop_name
                                                                            ?? 'F',
                                                                        0,
                                                                        1
                                                                    )
                                                                ) ?>

                                                            <?php endif; ?>

                                                        </div>


                                                        <div>

                                                            <h4 class="mb-1 fw-bold" id="<?= htmlspecialchars($farmerModalId) ?>-title">

                                                                <?= htmlspecialchars(
                                                                    $farmer->shop_name
                                                                        ?? 'Farm'
                                                                ) ?>


                                                                <?php if (!empty($farmer->verification_badge)): ?>

                                                                    <i
                                                                        class="bi <?= BadgeCalculationService::getBadgeIcon($farmer->verification_badge) ?> text-success ms-1"
                                                                        title="<?= htmlspecialchars(BadgeCalculationService::getBadgeLabel($farmer->verification_badge)) ?>">
                                                                    </i>

                                                                <?php endif; ?>

                                                            </h4>


                                                            <div class="text-muted small">

                                                                <i class="bi bi-geo-alt me-1"></i>

                                                                <?= htmlspecialchars(
                                                                    trim(
                                                                        ($farmer->barangay ?? '') .
                                                                            ', ' .
                                                                            ($farmer->city ?? ''),
                                                                        ', '
                                                                    )
                                                                ) ?: 'Local farm' ?>

                                                            </div>

                                                        </div>

                                                    </div>


                                                    <button
                                                        type="button"
                                                        class="btn-close"
                                                        data-bs-dismiss="modal"
                                                        aria-label="Close">
                                                    </button>

                                                </div>


                                                <div class="modal-body p-4">

                                                    <!-- RANK -->

                                                    <div class="farmer-modal-rank">

                                                        <?php if ($index === 0): ?>

                                                            <i class="bi bi-trophy-fill"></i>

                                                            #1 Top Farmer

                                                        <?php elseif ($index === 1): ?>

                                                            🥈 #2 Top Farmer

                                                        <?php elseif ($index === 2): ?>

                                                            🥉 #3 Top Farmer

                                                        <?php else: ?>

                                                            #<?= $index + 1 ?>
                                                            Top Farmer

                                                        <?php endif; ?>


                                                        <span>

                                                            <?= htmlspecialchars(
                                                                $periodLabel
                                                            ) ?>

                                                        </span>

                                                    </div>


                                                    <!-- MAIN STATS -->

                                                    <div class="farmer-modal-stats">

                                                        <div class="farmer-modal-stat">

                                                            <strong>

                                                                ₱<?= number_format(
                                                                    (float) (
                                                                        $farmer->total_sales
                                                                        ?? 0
                                                                    ),
                                                                    2
                                                                ) ?>

                                                            </strong>

                                                            <span>
                                                                Sales
                                                            </span>

                                                        </div>


                                                        <div class="farmer-modal-stat">

                                                            <strong>

                                                                <?= (int) (
                                                                    $farmer->total_orders
                                                                    ?? 0
                                                                ) ?>

                                                            </strong>

                                                            <span>
                                                                Orders
                                                            </span>

                                                        </div>


                                                        <div class="farmer-modal-stat">

                                                            <strong>

                                                                <?= (int) (
                                                                    $farmer->total_items
                                                                    ?? 0
                                                                ) ?>

                                                            </strong>

                                                            <span>
                                                                Products Sold
                                                            </span>

                                                        </div>

                                                    </div>


                                                    <!-- TRUST / RATING -->

                                                    <div class="farmer-modal-meta">

                                                        <span>

                                                            <i class="bi bi-star-fill text-warning me-1"></i>

                                                            <strong>

                                                                <?= number_format(
                                                                    (float) (
                                                                        $farmer->avg_rating
                                                                        ?? 0
                                                                    ),
                                                                    1
                                                                ) ?>

                                                            </strong>

                                                            Rating

                                                        </span>


                                                        <span>

                                                            <i class="bi bi-shield-check text-success me-1"></i>

                                                            <strong>

                                                                <?= (int) (
                                                                    $farmer->trust_score
                                                                    ?? 100
                                                                ) ?>%

                                                            </strong>

                                                            Trust

                                                        </span>


                                                        <span>

                                                            <i class="bi bi-basket text-success me-1"></i>

                                                            <strong>

                                                                <?= (int) (
                                                                    $farmer->active_products
                                                                    ?? 0
                                                                ) ?>

                                                            </strong>

                                                            Active Products

                                                        </span>

                                                    </div>


                                                    <!-- TOP CUSTOMERS -->

                                                    <div class="farmer-modal-customers mt-4">

                                                        <h5 class="fw-bold mb-3">

                                                            <i class="bi bi-people-fill me-1"></i>

                                                            Top Customers

                                                        </h5>


                                                        <?php if (
                                                            !empty(
                                                                $sellerCustomerStats[
                                                                    'top_customers'
                                                                ]
                                                            )
                                                        ): ?>

                                                            <?php foreach (
                                                                $sellerCustomerStats[
                                                                    'top_customers'
                                                                ]
                                                                as $customer
                                                            ): ?>

                                                                <div
                                                                    class="farmer-customer-row">

                                                                    <span>

                                                                        <?php
                                                                        $customerNameParts = preg_split(
                                                                            '/\s+/u',
                                                                            trim(($customer->first_name ?? '') . ' ' . ($customer->last_initial ?? '')),
                                                                            -1,
                                                                            PREG_SPLIT_NO_EMPTY
                                                                        );
                                                                        $customerInitials = array_map(
                                                                            static fn($part) => mb_strtoupper(mb_substr($part, 0, 1, 'UTF-8'), 'UTF-8') . '.',
                                                                            $customerNameParts ?: []
                                                                        );
                                                                        ?>
                                                                        <?= htmlspecialchars(implode(' ', $customerInitials) ?: 'Customer') ?>

                                                                    </span>


                                                                    <span class="text-muted">

                                                                        <?= (int) (
                                                                            $customer->completed_orders
                                                                            ?? 0
                                                                        ) ?>

                                                                        orders

                                                                        ·

                                                                        <?= (int) (
                                                                            $customer->products_purchased
                                                                            ?? 0
                                                                        ) ?>

                                                                        products

                                                                    </span>

                                                                </div>

                                                            <?php endforeach; ?>


                                                            <?php

                                                            $otherCustomers =
                                                                (int) (
                                                                    $sellerCustomerStats[
                                                                        'total_customers'
                                                                    ]
                                                                    ?? 0
                                                                )
                                                                -
                                                                count(
                                                                    $sellerCustomerStats[
                                                                        'top_customers'
                                                                    ]
                                                                );

                                                            ?>


                                                            <?php if (
                                                                $otherCustomers > 0
                                                            ): ?>

                                                                <div
                                                                    class="small text-muted mt-2">

                                                                    +<?= $otherCustomers ?>

                                                                    other customers

                                                                </div>

                                                            <?php endif; ?>


                                                        <?php else: ?>

                                                            <div class="text-muted small">

                                                                No customer activity yet.

                                                            </div>

                                                        <?php endif; ?>

                                                    </div>


                                                    <!-- VISIT FARM -->

                                                    <a
                                                        href="<?= htmlspecialchars(
                                                            $farmerUrl
                                                        ) ?>"
                                                        class="btn btn-success rounded-pill w-100 mt-4 farmer-visit-button">

                                                        <i class="bi bi-shop me-2"></i>

                                                        Visit Farm Store

                                                        <i class="bi bi-arrow-right ms-2"></i>

                                                    </a>

                                                </div>

                                            </div>

                                        </div>

                                    </div>
                                    <?php $farmerModals .= ob_get_clean(); ?>

                                <?php endforeach; ?>

                            </div>

                        <?php else: ?>

                            <div class="top-farmers-empty">

                                <i class="bi bi-bar-chart-line"></i>

                                <span>

                                    No <?= strtolower(
                                        $periodLabel
                                    ) ?>

                                    farmer ranking yet.

                                </span>

                            </div>

                        <?php endif; ?>

                    </div>

                <?php endforeach; ?>

            </div>

        </section>

    <?php endif; ?>


    <!-- Render seller dialogs outside the ranking tabs and their clipping container. -->
    <?= $farmerModals ?>

    <!-- =========================================================
         RECOMMENDED PRODUCTS
         ========================================================= -->

    <?php if (!empty($hasPersonalizedRecommendations) && !empty($recommendedProducts)): ?>

        <section class="recommended-products mb-5">

            <div
                class="d-flex justify-content-between align-items-center mb-4">

                <div>

                    <h3 class="fw-bold mb-1">
                        Recommended for You
                    </h3>

                    <p class="text-muted mb-0">
                        Based on shops you follow and categories you have purchased from.
                    </p>

                </div>

            </div>


            <div class="row marketplace-product-grid">

                <?php foreach (
                    $recommendedProducts
                    as $product
                ): ?>

                    <?php
                    renderMarketplaceProductCard(
                        $product,
                        $productImagesByProduct,
                        $productVariantsByProduct,
                        $currentRole,
                        $currentUserId,
                        'recommended'
                    );
                    ?>

                <?php endforeach; ?>

            </div>

        </section>

    <?php endif; ?>


    <!-- =========================================================
         PRODUCT CATALOG
         ========================================================= -->

    <div class="marketplace-catalog-heading mb-4">
        <div>
            <h2><?= !empty($hasPersonalizedRecommendations) ? 'Shop local produce' : 'Explore Fresh Products' ?></h2>
            <p><?= !empty($hasPersonalizedRecommendations)
                ? 'Browse every product available from local sellers.'
                : 'Discover all available products from local sellers.' ?></p>
        </div>
        <p id="productResultsCount" role="status" aria-live="polite"></p>
    </div>


    <!-- =========================================================
         MAIN PRODUCTS
         ========================================================= -->

    <div
        class="row marketplace-product-grid"
        id="productsContainer">

        <?php if (count($products)): ?>

            <?php foreach (
                $products
                as $product
            ): ?>

                <?php
                renderMarketplaceProductCard(
                    $product,
                    $productImagesByProduct,
                    $productVariantsByProduct,
                    $currentRole,
                    $currentUserId,
                    'main'
                );
                ?>

            <?php endforeach; ?>

        <?php else: ?>

            <div class="col-12">

                <div class="text-center py-5">

                    <i
                        class="bi bi-basket fs-1 text-muted mb-3 d-block">
                    </i>

                    <h5 class="fw-bold">

                        No products uploaded yet

                    </h5>

                    <p class="text-muted mb-0">

                        There are currently no products available
                        in this marketplace.

                    </p>

                </div>

            </div>

        <?php endif; ?>

    </div>


    <div id="noMatchingProducts" class="marketplace-empty-state" role="status" hidden>
        <i class="bi bi-search marketplace-empty-icon" aria-hidden="true"></i>
        <h3>No matching products</h3>
        <p class="text-muted">Try another search or category.</p>
        <button type="button" id="clearProductFilters" class="btn btn-outline-success rounded-pill">
            Clear filters
        </button>
    </div>

    <!-- =========================================================
         PRODUCT DETAIL MODALS
         
         IMPORTANT:
         THESE ARE OUTSIDE #productsContainer.
         DO NOT MOVE THEM BACK INSIDE THE ROW.
         ========================================================= -->

    <?php foreach (
        $modalProducts
        as $product
    ): ?>

        <?php
        $productReviews = $productReviewsByProduct[(int) $product->id] ?? [];
        $productVariants = $productVariantsByProduct[(int) $product->id] ?? [];
        $defaultVariantId = count($productVariants) === 1 ? (int) $productVariants[0]->id : 0;
        $modalDisplayStock = count($productVariants) === 1
            ? (int) $productVariants[0]->stock
            : (int) ($product->stock ?? 0);
        $modalShopThemes = SellerProfileService::themes();
        $modalShopTheme = $modalShopThemes[$product->shop_theme ?? 'fresh-farm'] ?? $modalShopThemes['fresh-farm'];
        $ownerBadgeColor = $modalShopTheme['accent'];
        $ownerBadgeTextColor = marketplace_contrast_color($ownerBadgeColor);
        $productLocation = marketplace_product_location($product);
        ?>

        <div
            class="modal fade"
            id="productModal<?= (int) $product->id ?>"
            tabindex="-1"
            aria-hidden="true">

            <div class="modal-dialog modal-xl product-detail-dialog">

                <div class="modal-content product-detail-modal">

                    <!-- CLOSE -->

                    <button
                        type="button"
                        class="btn-close product-detail-close"
                        data-bs-dismiss="modal"
                        aria-label="Close">
                    </button>


                    <!-- SCROLLABLE BODY -->

                    <div class="modal-body product-detail-body">

                        <div class="row g-0">

                            <!-- =================================================
                                 LEFT SIDE — PRODUCT IMAGE
                                 ================================================= -->

                            <div
                                class="col-lg-5 product-detail-media">

                                <?php

                                $galleryImages =
                                    $productImagesByProduct[
                                        $product->id
                                    ]
                                    ?? [];

                                if (empty($galleryImages)) {

                                    $galleryImages = [
                                        (object) [
                                            'image_url' =>
                                                $product->image
                                                ?: '/ani-mart/public/assets/img/product-placeholder.svg'
                                        ]
                                    ];

                                }

                                ?>


                                <div
                                    id="productDetailCarousel<?= (int) $product->id ?>"
                                    class="carousel slide product-detail-carousel h-100"
                                    data-bs-ride="carousel"
                                    data-bs-interval="2000"
                                    data-bs-pause="hover">

                                    <div class="carousel-inner h-100">

                                        <?php foreach (
                                            $galleryImages
                                            as $index => $image
                                        ): ?>

                                            <div
                                                class="carousel-item<?= $index === 0 ? ' active' : '' ?> h-100">

                                                <img
                                                    src="<?= htmlspecialchars(
                                                        $image->image_url
                                                    ) ?>"
                                                    class="d-block w-100 h-100"
                                                    alt="<?= htmlspecialchars(
                                                        $product->name
                                                    ) ?>">

                                            </div>

                                        <?php endforeach; ?>

                                    </div>


                                    <?php if (
                                        count($galleryImages) > 1
                                    ): ?>

                                        <button
                                            class="carousel-control-prev"
                                            type="button"
                                            data-bs-target="#productDetailCarousel<?= (int) $product->id ?>"
                                            data-bs-slide="prev">

                                            <span
                                                class="carousel-control-prev-icon"
                                                aria-hidden="true">
                                            </span>

                                            <span class="visually-hidden">
                                                Previous
                                            </span>

                                        </button>


                                        <button
                                            class="carousel-control-next"
                                            type="button"
                                            data-bs-target="#productDetailCarousel<?= (int) $product->id ?>"
                                            data-bs-slide="next">

                                            <span
                                                class="carousel-control-next-icon"
                                                aria-hidden="true">
                                            </span>

                                            <span class="visually-hidden">
                                                Next
                                            </span>

                                        </button>

                                    <?php endif; ?>

                                </div>

                            </div>


                            <!-- =================================================
                                 RIGHT SIDE — PRODUCT INFORMATION
                                 ================================================= -->

                            <div
                                class="col-lg-7 product-detail-content">


                                <!-- HEADING -->

                                <div class="product-detail-heading">

                                    <!-- CATEGORY -->

                                    <span class="product-detail-category">

                                        <?= htmlspecialchars(
                                            $product->category_name
                                                ?? 'Other'
                                        ) ?>

                                    </span>


                                    <!-- PRODUCT NAME -->

                                    <h2 class="product-detail-title">

                                        <?= htmlspecialchars(
                                            $product->name
                                        ) ?>

                                    </h2>


                                    <!-- RATING -->

                                    <div
                                        class="product-detail-rating">

                                        <i
                                            class="bi bi-star-fill text-warning">
                                        </i>

                                        <strong>

                                            <?= number_format(
                                                (float) (
                                                    $product->avg_rating
                                                    ?? 0
                                                ),
                                                1
                                            ) ?>

                                        </strong>

                                        <span>

                                            <?= (int) (
                                                $product->review_count
                                                ?? 0
                                            ) ?>

                                            Reviews

                                        </span>

                                    </div>

                                    <div class="product-detail-location">
                                        <i class="bi bi-geo-alt-fill" aria-hidden="true"></i>
                                        <span><?= htmlspecialchars($productLocation) ?></span>
                                    </div>

                                </div>


                                <!-- PRICE -->

                                <div class="product-price">

                                    ₱<?= number_format(
                                        (float) (
                                            $product->price
                                            ?? 0
                                        ),
                                        2
                                    ) ?>

                                </div>


                                <!-- STOCK -->

                                <?php if (
                                    (int) (
                                        $modalDisplayStock
                                    ) > 0
                                ): ?>

                                    <div class="mb-2 product-detail-live-stock">

                                        <?php if (
                                            $modalDisplayStock <= 3
                                        ): ?>

                                            <span
                                                class="badge bg-warning text-dark">

                                                Only
                                                <?= $modalDisplayStock ?>
                                                left

                                            </span>

                                        <?php else: ?>

                                            <span
                                                class="badge bg-success bg-opacity-10 text-success">

                                                Available stock:
                                                <?= $modalDisplayStock ?>

                                            </span>

                                        <?php endif; ?>

                                    </div>

                                <?php else: ?>

                                    <div class="mb-3 product-detail-live-stock">

                                        <span class="badge bg-danger">

                                            Out of Stock

                                        </span>

                                    </div>

                                <?php endif; ?>


                                <?php if (count($productVariants) > 1): ?>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold" for="variantSelect<?= (int) $product->id ?>">
                                            Choose size
                                        </label>
                                        <select
                                            id="variantSelect<?= (int) $product->id ?>"
                                            class="form-select product-variant-select"
                                            data-product-id="<?= (int) $product->id ?>">
                                            <option value="">Select a size</option>
                                            <?php foreach ($productVariants as $variant): ?>
                                                <?php
                                                $variantPrice = (float) ($variant->retail_price ?? $variant->wholesale_price ?? $product->price);
                                                $variantStock = max(0, (int) $variant->stock);
                                                ?>
                                                <option
                                                    value="<?= (int) $variant->id ?>"
                                                    data-price="<?= number_format($variantPrice, 2, '.', '') ?>"
                                                    data-stock="<?= $variantStock ?>"
                                                    <?= $variantStock <= 0 ? 'disabled' : '' ?>>
                                                    <?= htmlspecialchars($variant->variant_name) ?> — ₱<?= number_format($variantPrice, 2) ?>
                                                    <?= $variantStock <= 0 ? ' (Out of stock)' : '' ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                <?php endif; ?>

                                <!-- ACTIONS -->

                                <div
                                    class="product-detail-actions">


                                    <?php if (
                                        $currentRole === 'admin'
                                    ): ?>

                                        <span
                                            class="badge bg-dark px-3 py-2">

                                            Admin View

                                        </span>


                                    <?php elseif (
                                        $currentUserId ==
                                        (
                                            $product->seller_user_id
                                            ?? 0
                                        )
                                    ): ?>

                                        <span
                                            class="badge marketplace-owner-badge px-3 py-2"
                                            style="--owner-theme-color: <?= htmlspecialchars($ownerBadgeColor) ?>; --owner-theme-contrast: <?= htmlspecialchars($ownerBadgeTextColor) ?>;">

                                            Your Product

                                        </span>


                                    <?php elseif (
                                        $currentRole === 'guest'
                                    ): ?>

                                        <div class="d-grid gap-2">

                                            <button
                                                type="button"
                                                class="btn btn-success rounded-pill product-detail-cart-btn"
                                                data-bs-toggle="modal"
                                                data-bs-target="#loginRequiredModal">

                                                <i
                                                    class="bi bi-cart-plus me-2">
                                                </i>

                                                Add to Cart

                                            </button>

                                        </div>


                                    <?php elseif (
                                        in_array($currentRole, ['customer', 'farmer'], true)
                                    ): ?>

                                        <div class="d-grid gap-2">

                                            <button
                                                type="button"
                                                class="btn btn-success rounded-pill addToCartBtn product-detail-cart-btn"
                                                data-id="<?= (int) $product->id ?>"
                                                data-variant-id="<?= $defaultVariantId ?>"
                                                <?= count($productVariants) > 1 || $modalDisplayStock <= 0
                                                    ? 'disabled'
                                                    : '' ?>>

                                                <i
                                                    class="bi bi-cart-plus me-2">
                                                </i>

                                                Add to Cart

                                            </button>

                                        </div>


                                    <?php else: ?>

                                        <span
                                            class="badge bg-secondary px-3 py-2">

                                            Messaging not available

                                        </span>

                                    <?php endif; ?>

                                </div>


                                <div class="product-detail-tabs" role="tablist" aria-label="Product information">
                                    <button type="button" class="product-detail-tab active" role="tab" aria-selected="true" data-product-tab="details">
                                        <i class="bi bi-info-circle me-2" aria-hidden="true"></i>Details
                                    </button>
                                    <button type="button" class="product-detail-tab" role="tab" aria-selected="false" data-product-tab="reviews">
                                        <i class="bi bi-star me-2" aria-hidden="true"></i>Reviews
                                        <span class="product-tab-count"><?= count($productReviews) ?></span>
                                    </button>
                                </div>

                                <section class="product-detail-tab-panel active" data-product-panel="details" role="tabpanel">

                                <!-- DESCRIPTION -->

                                <p
                                    class="product-detail-description">

                                    <?= nl2br(
                                        htmlspecialchars(
                                            $product->description
                                            ?? ''
                                        )
                                    ) ?>

                                </p>


                                <!-- =================================================
                                     FARM INFORMATION
                                     ================================================= -->

                                <div
                                    class="passport-section product-detail-farm">

                                    <h5 class="passport-title">

                                        <i
                                            class="bi bi-shop me-2">
                                        </i>

                                        Farm Information

                                    </h5>


                                    <div
                                        class="d-flex align-items-center mb-3">

                                        <div class="farm-avatar">

                                            <span class="farm-avatar-initial" <?= !empty($product->farm_logo) ? 'hidden' : '' ?>>
                                                <?= htmlspecialchars(mb_strtoupper(mb_substr(($product->shop_name ?? '') ?: 'F', 0, 1, 'UTF-8'), 'UTF-8')) ?>
                                            </span>
                                            <?php if (!empty($product->farm_logo)): ?>
                                                <img
                                                    src="<?= htmlspecialchars($product->farm_logo) ?>"
                                                    alt="<?= htmlspecialchars(($product->shop_name ?? 'Local Farm') . ' profile') ?>"
                                                    onerror="this.hidden = true; this.previousElementSibling.hidden = false;">
                                            <?php endif; ?>

                                        </div>


                                        <div
                                            class="ms-3 min-w-0">

                                            <h5
                                                class="farm-name mb-1">

                                                <?= htmlspecialchars(
                                                    $product->shop_name
                                                        ?? 'Local Farm'
                                                ) ?>

                                            </h5>


                                            <span
                                                class="farm-verified-badge">

                                                <i
                                                    class="bi bi-patch-check-fill me-1">
                                                </i>

                                                Verified Farmer

                                            </span>

                                        </div>

                                    </div>


                                    <!-- FARM PASSPORT -->

                                    <div class="passport-info">

                                        <div class="passport-item">

                                            <span>
                                                Trust Score
                                            </span>

                                            <strong>

                                                <?= (int) (
                                                    $product->trust_score
                                                    ?? 100
                                                ) ?>%

                                            </strong>

                                        </div>


                                        <div class="passport-item">

                                            <span>
                                                Complaint Rate
                                            </span>

                                            <strong class="text-success">

                                                <?php
                                                $complaintRate = (float) ($product->complaint_rate ?? 0);
                                                echo rtrim(rtrim(number_format($complaintRate, 1, '.', ''), '0'), '.') . '%';
                                                ?>

                                            </strong>

                                        </div>


                                        <div class="passport-item">

                                            <span>
                                                Member Since
                                            </span>

                                            <strong>

                                                <?= !empty(
                                                    $product->seller_created_at
                                                )
                                                    ? date(
                                                        'M Y',
                                                        strtotime(
                                                            $product->seller_created_at
                                                        )
                                                    )
                                                    : '2026'; ?>

                                            </strong>

                                        </div>

                                    </div>


                                    <!-- VISIT FARM -->

                                    <a
                                        href="/ani-mart/public/farm/<?= htmlspecialchars(
                                            !empty(
                                                $product->farm_slug
                                            )
                                                ? $product->farm_slug
                                                : (
                                                    $product->seller_profile_id
                                                    ?? 0
                                                )
                                        ) ?>"
                                        class="btn btn-outline-success rounded-pill w-100 mt-4 product-detail-store-link">

                                        <i
                                            class="bi bi-shop me-2">
                                        </i>

                                        Visit Farm Store

                                    </a>

                                </div>

                                </section>

                                <section class="product-detail-tab-panel" data-product-panel="reviews" role="tabpanel" hidden>
                                    <div class="product-review-summary-box mb-3">
                                        <div>
                                            <span class="product-review-summary-label">Customer rating</span>
                                            <div class="product-review-summary-score">
                                                <?= number_format((float) ($product->avg_rating ?? 0), 1) ?>
                                                <small>/ 5</small>
                                            </div>
                                        </div>
                                        <div>
                                            <div class="product-review-rating" aria-label="<?= number_format((float) ($product->avg_rating ?? 0), 1) ?> out of 5 stars">
                                                <?php $roundedRating = (int) round((float) ($product->avg_rating ?? 0)); ?>
                                                <?php for ($star = 1; $star <= 5; $star++): ?>
                                                    <i class="bi <?= $star <= $roundedRating ? 'bi-star-fill' : 'bi-star' ?>"></i>
                                                <?php endfor; ?>
                                            </div>
                                            <p class="mb-0"><?= count($productReviews) ?> verified <?= count($productReviews) === 1 ? 'review' : 'reviews' ?></p>
                                        </div>
                                    </div>

                                    <?php if (empty($productReviews)): ?>
                                        <div class="product-review-empty">
                                            <i class="bi bi-chat-square-heart" aria-hidden="true"></i>
                                            <h3>No reviews yet</h3>
                                            <p>Be the first customer to review this product after delivery.</p>
                                        </div>
                                    <?php else: ?>
                                        <div class="product-review-list">
                                            <?php foreach ($productReviews as $review): ?>
                                                <?php
                                                $initials = mb_strtoupper(
                                                    mb_substr((string) ($review->first_name ?? 'C'), 0, 1) .
                                                    mb_substr((string) ($review->last_name ?? ''), 0, 1)
                                                );
                                                $reviewRatings = [
                                                    'Freshness' => $review->freshness_rating ?? null,
                                                    'Condition' => $review->product_condition_rating ?? null,
                                                    'Driver' => $review->driver_friendliness_rating ?? null,
                                                    'Delivery speed' => $review->delivery_speed_rating ?? null,
                                                ];
                                                ?>
                                                <article class="product-review-item">
                                                    <header class="product-review-header">
                                                        <span class="reviewer-badge"><?= htmlspecialchars($initials ?: 'C') ?></span>
                                                        <div class="flex-grow-1">
                                                            <strong>Verified customer</strong>
                                                            <small><?= !empty($review->created_at) ? htmlspecialchars(date('M j, Y', strtotime($review->created_at))) : 'Recently' ?></small>
                                                        </div>
                                                        <span class="review-score-pill"><?= (int) ($review->rating ?? 0) ?>/5</span>
                                                    </header>

                                                    <div class="review-stars" aria-label="<?= (int) ($review->rating ?? 0) ?> out of 5 stars">
                                                        <?php for ($star = 1; $star <= 5; $star++): ?>
                                                            <i class="bi <?= $star <= (int) ($review->rating ?? 0) ? 'bi-star-fill' : 'bi-star' ?>"></i>
                                                        <?php endfor; ?>
                                                    </div>

                                                    <div class="product-review-metrics">
                                                        <?php foreach ($reviewRatings as $label => $score): ?>
                                                            <?php if ((int) $score > 0): ?>
                                                                <span><strong><?= htmlspecialchars($label) ?></strong> <?= (int) $score ?>/5</span>
                                                            <?php endif; ?>
                                                        <?php endforeach; ?>
                                                    </div>

                                                    <?php if (!empty($review->comment)): ?>
                                                        <p>“<?= nl2br(htmlspecialchars($review->comment)) ?>”</p>
                                                    <?php endif; ?>

                                                    <?php if (!empty($review->review_photo)): ?>
                                                        <img class="product-review-photo" src="<?= htmlspecialchars($review->review_photo) ?>" alt="Photo shared with this product review" data-image-modal="true">
                                                    <?php endif; ?>
                                                </article>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </section>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    <?php endforeach; ?>


    <!-- =========================================================
         LOGIN REQUIRED MODAL
         ========================================================= -->

    <div
        class="modal fade"
        id="loginRequiredModal"
        tabindex="-1"
        aria-hidden="true">

        <div
            class="modal-dialog modal-dialog-centered">

            <div
                class="modal-content border-0 rounded-5 shadow">

                <div
                    class="modal-body text-center p-5">

                    <div class="mb-3">

                        <i
                            class="bi bi-person-lock text-success login-required-icon">
                        </i>

                    </div>


                    <h3 class="fw-bold">

                        Login Required

                    </h3>


                    <p
                        class="text-muted mt-3">

                        Please login or create an
                        account before adding
                        products to your cart.

                    </p>


                    <div
                        class="d-flex justify-content-center gap-2 mt-4">

                        <a
                            href="/ani-mart/public/login"
                            class="btn btn-success rounded-pill px-4">

                            Login

                        </a>


                        <a
                            href="/ani-mart/public/register"
                            class="btn btn-outline-success rounded-pill px-4">

                            Register

                        </a>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>


<?php

$content =
    ob_get_clean();


ob_start();

?>

<!-- =========================================================
     PRODUCT SEARCH / FILTER
     ========================================================= -->

<script>

    const searchInput =
        document.getElementById("searchInput");

    const searchButton =
        document.getElementById("marketplaceSearchButton");

    const categoryFilter =
        document.getElementById("categoryFilter");

    const products =
        document.querySelectorAll("#productsContainer .product-item");


    function filterProducts() {

        if (!searchInput || !categoryFilter) {
            return;
        }


        const search =
            searchInput.value
                .toLowerCase()
                .trim();


        const category =
            categoryFilter.value
                .toLowerCase()
                .trim();


        let visibleCount = 0;

        products.forEach(product => {

            const name =
                (
                    product.dataset.name
                    || ''
                ).toLowerCase();


            const cat =
                (
                    product.dataset.category
                    || ''
                ).toLowerCase();


            const matchSearch =
                name.includes(search);


            const matchCategory =
                category === '' ||
                cat === category;


            const matches = matchSearch && matchCategory;
            product.hidden = !matches;
            if (matches) visibleCount++;

        });

        document.getElementById('productResultsCount').textContent =
            `${visibleCount} of ${products.length} products`;

        document.getElementById('noMatchingProducts').hidden =
            products.length === 0 || visibleCount > 0;

    }

    let searchHighlightTimer = null;

    function focusProductCard(product) {
        if (!product) {
            return false;
        }

        document.querySelectorAll('.product-search-target').forEach(item => {
            item.classList.remove('product-search-target');
        });

        product.hidden = false;
        product.classList.add('product-search-target');
        product.scrollIntoView({
            behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches
                ? 'auto'
                : 'smooth',
            block: 'center'
        });
        product.focus({ preventScroll: true });

        window.clearTimeout(searchHighlightTimer);
        searchHighlightTimer = window.setTimeout(() => {
            product.classList.remove('product-search-target');
        }, 2600);

        return true;
    }

    function scrollToBestMatchingProduct() {
        const search = searchInput.value.toLowerCase().trim();

        if (search === '') {
            return false;
        }

        const matchingProducts = Array.from(products).filter(product => {
            const name = (product.dataset.name || '').toLowerCase();
            const category = (product.dataset.category || '').toLowerCase();
            const selectedCategory = categoryFilter.value.toLowerCase().trim();

            return name.includes(search)
                && (selectedCategory === '' || category === selectedCategory);
        });

        if (matchingProducts.length === 0) {
            return false;
        }

        const selectedProduct = matchingProducts.find(product =>
            (product.dataset.name || '').toLowerCase() === search
        ) || matchingProducts.find(product =>
            (product.dataset.name || '').toLowerCase().startsWith(search)
        ) || matchingProducts[0];

        return focusProductCard(selectedProduct);
    }

    function handleProductSearch(event) {
        if (event) {
            event.preventDefault();
        }

        filterProducts();

        if (searchInput.value.trim() === '') {
            const firstVisibleProduct = Array.from(products).find(product => !product.hidden);
            if (firstVisibleProduct) {
                focusProductCard(firstVisibleProduct);
            }
            return;
        }

        if (!scrollToBestMatchingProduct()) {
            const firstVisibleProduct = Array.from(products).find(product => !product.hidden);
            if (firstVisibleProduct) {
                focusProductCard(firstVisibleProduct);
            }
        }

    }

    document.getElementById('clearProductFilters').addEventListener('click', () => {
        searchInput.value = '';
        categoryFilter.value = '';
        filterProducts();
        searchInput.focus();
    });

    filterProducts();

    if (searchInput) {

        searchInput.addEventListener(
            'input',
            filterProducts
        );

        searchInput.addEventListener('keydown', event => {
            if (event.key !== 'Enter') {
                return;
            }

            handleProductSearch(event);
        });

    }

    if (searchButton) {
        searchButton.addEventListener('click', handleProductSearch);
    }


    if (categoryFilter) {

        categoryFilter.addEventListener('change', () => {
            filterProducts();

            const firstVisibleProduct = Array.from(products).find(product => !product.hidden);
            if (firstVisibleProduct) {
                focusProductCard(firstVisibleProduct);
            }
        });

    }

</script>

<script>
    function renderLiveStock(stock) {
        if (stock <= 0) return '<span class="stock-out">Out of stock</span>';
        if (stock <= 3) return `<span class="stock-low">Only ${stock} left</span>`;
        return `<span class="stock-available">Available stock: ${stock}</span>`;
    }

    async function refreshMarketplaceStock() {
        if (document.hidden) return;

        try {
            const response = await fetch('/ani-mart/public/marketplace/stock', {
                headers: { 'Accept': 'application/json' },
                cache: 'no-store'
            });
            if (!response.ok) return;
            const payload = await response.json();

            (payload.products || []).forEach(product => {
                document.querySelectorAll(`.product-card[data-id="${product.id}"]`).forEach(card => {
                    card.dataset.stock = product.stock;
                    const stockDisplay = card.querySelector('.marketplace-stock');
                    if (stockDisplay) stockDisplay.innerHTML = renderLiveStock(product.stock);
                    card.querySelectorAll('.addToCartBtn').forEach(button => {
                        button.disabled = product.stock <= 0;
                    });
                });

                const modal = document.getElementById(`productModal${product.id}`);
                if (modal) {
                    const modalStock = modal.querySelector('.product-detail-live-stock');
                    if (modalStock) modalStock.innerHTML = renderLiveStock(product.stock);
                    modal.querySelectorAll('.addToCartBtn').forEach(button => {
                        button.disabled = product.stock <= 0;
                    });
                }
            });
        } catch (error) {
            // Keep the last rendered stock when a background refresh is unavailable.
        }
    }

    window.setInterval(refreshMarketplaceStock, 15000);
    document.addEventListener('visibilitychange', refreshMarketplaceStock);
</script>


<!-- =========================================================
     PRODUCT CARD -> PRODUCT MODAL
     ========================================================= -->

<script>

    document
        .querySelectorAll('.product-card')
        .forEach(card => {

            card.addEventListener(
                'click',
                function (e) {

                    /*
                     * Do not open product modal when the user
                     * clicks buttons, links, inputs, etc.
                     */

                    if (
                        e.target.closest(
                            'button, a, input, select, textarea, label'
                        )
                    ) {
                        return;
                    }


                    const modalTarget =
                        this.dataset.modalTarget;


                    const modalElement =
                        modalTarget
                            ? document.querySelector(
                                modalTarget
                            )
                            : null;


                    if (!modalElement) {
                        return;
                    }


                    bootstrap.Modal
                        .getOrCreateInstance(
                            modalElement
                        )
                        .show();

                }
            );

        });

</script>


<!-- =========================================================
     ADD TO CART
     ========================================================= -->

<script>

    document
        .querySelectorAll('.addToCartBtn')
        .forEach(button => {

            button.addEventListener(
                'click',
                function (e) {

                    e.stopPropagation();


                    const productId =
                        this.dataset.id;


                    if (!productId || this.disabled) {
                        return;
                    }

                    this.disabled = true;
                    this.setAttribute('aria-busy', 'true');

                    const formData =
                        new FormData();

                    formData.append(
                        'product_id',
                        productId
                    );

                    if (this.dataset.variantId) {
                        formData.append('variant_id', this.dataset.variantId);
                    }


                    const buttonId =
                        this.id ||
                        `cart-button-${productId}-${Date.now()}`;


                    this.id =
                        buttonId;


                    const skeletonId =
                        `cart-skeleton-${buttonId}`;


                    const loadingBlock =
                        document.createElement(
                            'span'
                        );


                    loadingBlock.id =
                        skeletonId;


                    loadingBlock.className =
                        'skeleton skeleton-button w-100';


                    loadingBlock.hidden =
                        true;


                    this.insertAdjacentElement(
                        'afterend',
                        loadingBlock
                    );


                    if (
                        typeof showSkeleton ===
                        'function'
                    ) {

                        showSkeleton(
                            skeletonId
                        );

                    }


                    if (
                        typeof hideContent ===
                        'function'
                    ) {

                        hideContent(
                            buttonId
                        );

                    }


                    fetch(
                        '/ani-mart/public/cart/add',
                        {
                            method: 'POST',
                            body: formData
                        }
                    )
                        .then(response => {

                            if (!response.ok) {

                                throw new Error(
                                    'Unable to add this item to the cart.'
                                );

                            }

                            return response.json();

                        })
                        .then(data => {

                            if (data.success) {

                                const cartBadge =
                                    document.getElementById(
                                        'cartBadge'
                                    );


                                if (cartBadge) {

                                    cartBadge.innerText =
                                        data.count;

                                }


                                showToast(
                                    'Added to cart'
                                );

                                return;

                            }


                            throw new Error(
                                data.message ||
                                'Unable to add this item to the cart.'
                            );

                        })
                        .catch(error => {

                            showToast(
                                error.message ||
                                'Unable to add this item to the cart.'
                            );

                        })
                        .finally(() => {
                            this.disabled = false;
                            this.removeAttribute('aria-busy');

                            if (
                                typeof hideSkeleton ===
                                'function'
                            ) {

                                hideSkeleton(
                                    skeletonId
                                );

                            }


                            if (
                                typeof showContent ===
                                'function'
                            ) {

                                showContent(
                                    buttonId
                                );

                            }


                            loadingBlock.remove();

                        });

                }
            );

        });


    /* =========================================================
       TOAST
       ========================================================= */

    function showToast(message) {

        const toast =
            document.createElement(
                'div'
            );


        toast.className =
            'cart-toast';
        toast.setAttribute('role', 'status');


        toast.innerText =
            message;


        document.body.appendChild(
            toast
        );


        setTimeout(() => {

            toast.remove();

        }, 2500);

    }

</script>

<script>
    document.querySelectorAll('.product-variant-select').forEach(select => {
        select.addEventListener('change', function () {
            const modal = this.closest('.modal');
            const button = modal ? modal.querySelector('.addToCartBtn') : null;
            if (!button) return;

            const option = this.options[this.selectedIndex];
            button.dataset.variantId = this.value;
            button.disabled = !this.value || Number(option?.dataset.stock || 0) <= 0;

            const price = modal.querySelector('.product-price');
            if (price && option?.dataset.price) {
                price.textContent = `₱${Number(option.dataset.price).toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
            }
        });
    });
</script>


<!-- =========================================================
     PRODUCT MODAL SCROLL RESET
     
     THIS FIXES THE MOBILE ISSUE WHERE THE MODAL OPENS
     ALREADY SCROLLED DOWN TO DESCRIPTION / FARM INFORMATION.
     ========================================================= -->

<script>

    document
        .querySelectorAll('[id^="productModal"]')
        .forEach(function (modal) {

            const tabs = modal.querySelectorAll('[data-product-tab]');
            const panels = modal.querySelectorAll('[data-product-panel]');

            function activateProductTab(tabName) {
                tabs.forEach(function (tab) {
                    const isActive = tab.dataset.productTab === tabName;
                    tab.classList.toggle('active', isActive);
                    tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
                });

                panels.forEach(function (panel) {
                    const isActive = panel.dataset.productPanel === tabName;
                    panel.classList.toggle('active', isActive);
                    panel.hidden = !isActive;
                });
            }

            tabs.forEach(function (tab) {
                tab.addEventListener('click', function () {
                    activateProductTab(tab.dataset.productTab);
                });
            });


            /*
             * Reset before Bootstrap starts showing the modal.
             */

            modal.addEventListener(
                'show.bs.modal',
                function () {

                    activateProductTab('details');

                    const body =
                        modal.querySelector(
                            '.product-detail-body'
                        );


                    if (body) {

                        body.scrollTop = 0;

                    }


                    modal.scrollTop = 0;

                }
            );


            /*
             * Reset again after Bootstrap has finished
             * opening the modal.
             */

            modal.addEventListener(
                'shown.bs.modal',
                function () {

                    const body =
                        modal.querySelector(
                            '.product-detail-body'
                        );


                    if (body) {

                        body.scrollTop = 0;


                        requestAnimationFrame(
                            function () {

                                body.scrollTop = 0;

                            }
                        );

                    }


                    modal.scrollTop = 0;

                }
            );


            /*
             * Reset when closing so the next opening
             * starts from the very top.
             */

            modal.addEventListener(
                'hidden.bs.modal',
                function () {

                    const body =
                        modal.querySelector(
                            '.product-detail-body'
                        );


                    if (body) {

                        body.scrollTop = 0;

                    }


                    modal.scrollTop = 0;

                }
            );

        });

</script>
<?php

$pageCss = '/ani-mart/public/assets/css/pages/marketplace.css?v='
    . filemtime(__DIR__ . '/../../../public/assets/css/pages/marketplace.css');
$pageScript = ($pageScript ?? '') . ob_get_clean();
require __DIR__ . '/../layouts/main.php';
