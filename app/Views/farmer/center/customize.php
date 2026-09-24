<?php
$title = 'Seller Centre';
$bodyClass = 'seller-centre-page';
$appearanceActive = ($_GET['tab'] ?? '') === 'appearance';
$featuredActive = ($_GET['tab'] ?? '') === 'featured';

$activeShop = $activeShop ?? null;
$themes = $themes ?? [];
$featuredIds = $featuredIds ?? [];
$availableProducts = $availableProducts ?? [];
$seller = $seller ?? $activeShop ?? null;
$metrics = $metrics ?? [];
$farmGallery = $farmGallery ?? [];
$harvestGallery = $harvestGallery ?? [];
$deleteGalleryForms = [];
$responseRateLabel = isset($metrics['response_rate'])
    ? (int) $metrics['response_rate'] . '%'
    : 'No data';
$averageDeliveryLabel = !empty($metrics['average_delivery_time'])
    ? (string) $metrics['average_delivery_time']
    : 'No data';

$savedThemeKey = $activeShop?->shop_theme ?? 'fresh-farm';
$savedThemeKey = isset($themes[$savedThemeKey]) ? $savedThemeKey : 'fresh-farm';
$savedTheme = $themes[$savedThemeKey] ?? $themes['fresh-farm'] ?? [
    'accent' => '#2F7D32',
    'secondary' => '#A8D5A2',
    'background' => '#F2F8F4',
    'text' => '#183C2A',
];

$themeButtonText = static function (string $color): string {
    $hex = ltrim($color, '#');
    if (strlen($hex) === 3) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }

    if (strlen($hex) !== 6 || !ctype_xdigit($hex)) {
        return '#ffffff';
    }

    $red = hexdec(substr($hex, 0, 2));
    $green = hexdec(substr($hex, 2, 2));
    $blue = hexdec(substr($hex, 4, 2));
    $brightness = (($red * 299) + ($green * 587) + ($blue * 114)) / 1000;

    return $brightness >= 150 ? '#17211b' : '#ffffff';
};

$publicUrl = '/ani-mart/public/farm/' . htmlspecialchars($seller?->slug ?? $activeShop?->slug ?? '');

$pageStyles[] = '/ani-mart/public/assets/css/pages/shop-customize.css?v=' . filemtime(__DIR__ . '/../../../../public/assets/css/pages/shop-customize.css');

ob_start();
?>

<div
    class="container app-page-spacing seller-centre"
    data-shop-theme="<?= htmlspecialchars($savedThemeKey) ?>"
    style="
        --seller-green: <?= htmlspecialchars($savedTheme['accent']) ?>;
        --seller-green-soft: <?= htmlspecialchars($savedTheme['background']) ?>;
        --seller-theme-secondary: <?= htmlspecialchars($savedTheme['secondary']) ?>;
        --seller-theme-text: <?= htmlspecialchars($savedTheme['text']) ?>;
        --seller-button-text: <?= $themeButtonText($savedTheme['accent']) ?>;
    "
>

    <section class="shop-customize-hero mb-4" aria-labelledby="shopCustomizeTitle">
        <div class="shop-customize-hero-content">
            <span class="shop-customize-eyebrow">
                <i class="bi bi-shop-window" aria-hidden="true"></i>
                <?= htmlspecialchars($activeShop?->shop_name ?? 'Your shop') ?>
            </span>
            <h1 id="shopCustomizeTitle">Manage Your Farm Store</h1>
            <p>
                Manage your farm profile, storefront appearance, information, gallery, and featured products in one
                place.
            </p>
        </div>

        <div class="shop-customize-hero-actions">
            <a href="<?= $publicUrl ?>" target="_blank" rel="noopener" class="btn btn-light rounded-pill px-4">
                <i class="bi bi-box-arrow-up-right me-1" aria-hidden="true"></i>
                View Storefront
            </a>
        </div>
    </section>

    <!-- QUICK OVERVIEW -->
    <div class="seller-overview mb-4">
        <div class="seller-overview-main">
            <div class="seller-avatar">
                <?php if (!empty($seller?->farm_logo)): ?>
                    <img src="<?= htmlspecialchars($seller?->farm_logo) ?>" alt="Farm logo">
                <?php elseif (!empty($activeShop?->farm_logo)): ?>
                    <img src="<?= htmlspecialchars($activeShop?->farm_logo) ?>" alt="Farm logo">
                <?php else: ?>
                    <?= htmlspecialchars(strtoupper(substr($seller?->shop_name ?? $activeShop?->shop_name ?? 'F', 0, 1))) ?>
                <?php endif; ?>
            </div>

            <div>
                <h2 class="h4 fw-bold mb-1">
                    <?= htmlspecialchars($seller?->shop_name ?? $activeShop?->shop_name ?? 'Your Farm') ?>
                </h2>
                <div class="text-muted small">
                    <i class="bi bi-geo-alt me-1"></i>
                    <?= htmlspecialchars($seller?->farm_location ?? $activeShop?->farm_location ?? 'Add your farm location') ?>
                </div>
            </div>
        </div>

        <div class="seller-overview-metrics">
            <div>
                <strong><?= number_format((float) ($metrics['customer_rating'] ?? 0), 1) ?></strong>
                <span>Rating</span>
            </div>
            <div>
                <strong><?= (int) ($metrics['total_products'] ?? count($availableProducts)) ?></strong>
                <span>Products</span>
            </div>
            <div>
                <strong><?= htmlspecialchars($responseRateLabel) ?></strong>
                <span>Response</span>
            </div>
        </div>
    </div>

    <!-- TABS -->
    <div class="seller-tabs mb-4" role="tablist">
        <button class="seller-tab<?= !$appearanceActive && !$featuredActive ? ' active' : '' ?>" type="button" data-target="sellerProfilePanel">
            <i class="bi bi-person-badge me-2"></i>Farm Profile
        </button>
        <button class="seller-tab<?= $appearanceActive ? ' active' : '' ?>" type="button" data-target="shopCustomizePanel">
            <i class="bi bi-palette me-2"></i>Shop Appearance
        </button>
        <button class="seller-tab<?= $featuredActive ? ' active' : '' ?>" type="button" data-target="shopProductsPanel">
            <i class="bi bi-star me-2"></i>Featured Products
        </button>
    </div>

    <!-- FARM PROFILE + SHOP INFORMATION -->
    <section id="sellerProfilePanel" class="seller-panel<?= !$appearanceActive && !$featuredActive ? ' active' : '' ?>">

        <form method="POST" enctype="multipart/form-data" action="/ani-mart/public/seller-center/shop/profile">
            <input type="hidden" name="return_to" value="shop-customize">

            <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token()) ?>">

            <div class="seller-grid">

                <div class="seller-main-column">

                    <!-- FARM BRANDING -->
                    <div class="seller-card mb-4">
                        <div class="seller-card-heading">
                            <div>
                                <span class="seller-card-icon">
                                    <i class="bi bi-image"></i>
                                </span>
                                <div>
                                    <h2>Farm Branding</h2>
                                    <p>Update the logo and banner customers see on your storefront.</p>
                                </div>
                            </div>
                        </div>

                        <div class="farm-editor-hero mb-3">
                            <img src="<?= htmlspecialchars($seller?->farm_banner ?: '/ani-mart/public/assets/img/farm-placeholder.svg') ?>"
                                alt="Farm banner" class="farm-editor-banner"
                                onerror="this.onerror=null;this.src='/ani-mart/public/assets/img/farm-placeholder.svg';this.style.display='block'">

                            <div class="farm-editor-overlay">
                                <div class="farm-editor-content">
                                    <div class="farm-editor-logo">
                                        <?php if (!empty($seller?->farm_logo)): ?>
                                            <img src="<?= htmlspecialchars($seller?->farm_logo) ?>" alt="Farm logo">
                                        <?php else: ?>
                                            <?= htmlspecialchars(strtoupper(substr($seller?->shop_name ?? 'F', 0, 1))) ?>
                                        <?php endif; ?>

                                        <button type="button" class="image-edit-overlay" data-target="farm_logo_input"
                                            aria-label="Change farm logo">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                    </div>

                                    <div class="farm-editor-text">
                                        <h3><?= htmlspecialchars($seller?->shop_name ?? 'Your Farm') ?></h3>
                                        <p><?= htmlspecialchars($seller?->farm_location ?? 'Add your farm location') ?>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <button type="button" class="farm-banner-edit" data-target="farm_banner_input">
                                <i class="bi bi-pencil me-1"></i> Edit Banner
                            </button>

                            <input type="file" id="farm_banner_input" name="farm_banner"
                                accept="image/jpeg,image/png,image/webp" class="d-none">
                            <input type="file" id="farm_logo_input" name="farm_logo"
                                accept="image/jpeg,image/png,image/webp" class="d-none">
                        </div>

                        <div class="mt-3">
                            <p id="brandingUploadStatus" class="small text-muted mb-0" aria-live="polite">
                                Choose a JPG, PNG, or WEBP image. It will preview above; use Save Farm Profile below to keep it.
                            </p>
                        </div>
                    </div>

                    <!-- FARM INFORMATION -->
                    <div class="seller-card mb-4">
                        <div class="seller-card-heading">
                            <div>
                                <span class="seller-card-icon"><i class="bi bi-pencil-square"></i></span>
                                <div>
                                    <h2>Farm Information</h2>
                                    <p>Keep your store identity and customer-facing information up to date.</p>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label fw-semibold">Farm Name</label>
                                <input type="text" name="shop_name" class="form-control modern-input"
                                    value="<?= htmlspecialchars($seller?->shop_name ?? $activeShop?->shop_name ?? '') ?>"
                                    required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Farm Location</label>
                                <input type="text" name="farm_location" class="form-control modern-input"
                                    value="<?= htmlspecialchars($seller?->farm_location ?? $activeShop?->farm_location ?? '') ?>"
                                    placeholder="Barangay, City, Province">
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold">Shop Description</label>
                                <textarea name="description" rows="4" class="form-control modern-input" maxlength="1000"
                                    required><?= htmlspecialchars($seller?->description ?? $activeShop?->description ?? '') ?></textarea>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Estimated Delivery Day</label>
                                <input type="text" name="average_delivery_time" class="form-control modern-input"
                                    value="<?= htmlspecialchars($seller?->average_delivery_time ?? '') ?>"
                                    placeholder="1-2 days">
                                <div class="form-text">Your customer-facing delivery estimate, not a performance metric.</div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Delivery Fee (₱)</label>
                                <input type="number" name="delivery_fee" class="form-control modern-input"
                                    min="0" max="10000" step="0.01"
                                    value="<?= htmlspecialchars(isset($seller?->delivery_fee) ? number_format((float) $seller->delivery_fee, 2, '.', '') : '') ?>"
                                    placeholder="Leave blank for free delivery">
                                <div class="form-text">Optional flat fee per order. Leave blank or enter 0 for free delivery.</div>
                            </div>
                        </div>
                    </div>

                    <!-- STORE INFORMATION -->
                    <div class="seller-card mb-4">
                        <div class="seller-card-heading">
                            <div>
                                <span class="seller-card-icon"><i class="bi bi-info-circle"></i></span>
                                <div>
                                    <h2>Store Information</h2>
                                    <p>Help customers understand when and how you operate.</p>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Operating Hours</label>
                                <textarea name="operating_hours" rows="4" class="form-control modern-input"
                                    maxlength="1000"
                                    placeholder="Mon-Fri, 8:00 AM-5:00 PM"><?= htmlspecialchars($seller?->operating_hours ?? $activeShop?->operating_hours ?? '') ?></textarea>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Delivery Information</label>
                                <textarea name="delivery_information" rows="4" class="form-control modern-input"
                                    maxlength="1000"
                                    placeholder="Delivery days, areas, and lead time"><?= htmlspecialchars($seller?->delivery_information ?? $activeShop?->delivery_information ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- GALLERIES -->
                    <div class="seller-card mb-4">
                        <div class="seller-card-heading">
                            <div>
                                <span class="seller-card-icon"><i class="bi bi-images"></i></span>
                                <div>
                                    <h2>Farm Gallery</h2>
                                    <p>Show customers your farm and fresh harvest.</p>
                                </div>
                            </div>
                        </div>

                        <div class="gallery-upload-grid">
                            <div class="image-upload-card">
                                <div class="upload-card-header">
                                    <span class="upload-card-icon"><i class="bi bi-images"></i></span>
                                    <div>
                                        <label class="form-label fw-semibold mb-1">Farm Gallery</label>
                                        <p class="text-muted small mb-0">Upload multiple photos of your farm.</p>
                                    </div>
                                </div>
                                <input type="file" name="farm_gallery[]" class="form-control modern-input"
                                    accept="image/jpeg,image/png,image/webp" multiple>
                            </div>

                            <div class="image-upload-card">
                                <div class="upload-card-header">
                                    <span class="upload-card-icon"><i class="bi bi-box-seam"></i></span>
                                    <div>
                                        <label class="form-label fw-semibold mb-1">Harvest Gallery</label>
                                        <p class="text-muted small mb-0">Add fresh harvest previews.</p>
                                    </div>
                                </div>
                                <input type="file" name="harvest_gallery[]" class="form-control modern-input"
                                    accept="image/jpeg,image/png,image/webp" multiple>
                            </div>
                        </div>

                        <?php
                        $galleryGroups = [
                            'Farm Gallery' => $farmGallery,
                            'Harvest Gallery' => $harvestGallery
                        ];
                        ?>

                        <?php foreach ($galleryGroups as $label => $gallery): ?>
                            <h3 class="h6 fw-bold mt-4 mb-3"><?= htmlspecialchars($label) ?></h3>

                            <?php if (count($gallery) > 0): ?>
                                <?php $profileGalleryId = 'sellerGallery-' . strtolower(str_replace(' ', '-', $label)); ?>

                                <div id="<?= htmlspecialchars($profileGalleryId) ?>"
                                    class="carousel slide profile-gallery-carousel mb-3" data-bs-ride="false">

                                    <div class="carousel-inner">
                                        <?php foreach ($gallery as $index => $image): ?>
                                            <div class="carousel-item<?= $index === 0 ? ' active' : '' ?>">
                                                <img src="<?= htmlspecialchars($image->image_path) ?>"
                                                    class="d-block w-100 rounded-4" alt="<?= htmlspecialchars($label) ?>">
                                            </div>
                                        <?php endforeach; ?>
                                    </div>

                                    <?php if (count($gallery) > 1): ?>
                                        <button class="carousel-control-prev" type="button"
                                            data-bs-target="#<?= htmlspecialchars($profileGalleryId) ?>" data-bs-slide="prev">
                                            <span class="carousel-control-prev-icon"></span>
                                            <span class="visually-hidden">Previous</span>
                                        </button>

                                        <button class="carousel-control-next" type="button"
                                            data-bs-target="#<?= htmlspecialchars($profileGalleryId) ?>" data-bs-slide="next">
                                            <span class="carousel-control-next-icon"></span>
                                            <span class="visually-hidden">Next</span>
                                        </button>
                                    <?php endif; ?>
                                </div>

                                <div class="d-flex flex-wrap gap-2">
                                    <?php foreach ($gallery as $image): ?>
                                        <?php $deleteGalleryForms[] = (int) $image->id; ?>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="farm-gallery-empty">No images yet</div>
                            <?php endif; ?>
                        <?php endforeach; ?>

                    </div>

                    <button type="submit" class="btn btn-success rounded-pill px-4">
                        <i class="bi bi-check-circle me-2"></i>
                        Save Farm Profile
                    </button>

                </div>

                <!-- SIDE METRICS -->
                <aside class="seller-side-column">

                    <div class="seller-card seller-metrics-card">
                        <div class="seller-card-heading">
                            <div>
                                <span class="seller-card-icon"><i class="bi bi-graph-up-arrow"></i></span>
                                <div>
                                    <h2>Storefront Metrics</h2>
                                    <p>Your current store performance.</p>
                                </div>
                            </div>
                        </div>

                        <div class="farm-metric-list">
                            <div><span>Trust Score</span><strong><?= (int) ($seller?->trust_score ?? 0) ?>%</strong>
                            </div>
                            <div>
                                <span>Seller Badge</span><strong><?= htmlspecialchars(\App\Services\BadgeCalculationService::getBadgeLabel($seller?->verification_badge ?? 'new')) ?></strong>
                            </div>
                            <div><span>Customer
                                    Rating</span><strong><?= number_format((float) ($metrics['customer_rating'] ?? 0), 1) ?></strong>
                            </div>
                            <div><span>Total Orders</span><strong><?= (int) ($metrics['total_orders'] ?? 0) ?></strong>
                            </div>
                            <div><span>Total
                                    Products</span><strong><?= (int) ($metrics['total_products'] ?? 0) ?></strong></div>
                            <div><span>Response
                                    Rate</span><strong><?= htmlspecialchars($responseRateLabel) ?></strong></div>
                            <div><span>Avg.
                                    Delivery</span><strong><?= htmlspecialchars($averageDeliveryLabel) ?></strong>
                            </div>
                        </div>
                    </div>

                </aside>

            </div>
        </form>

        <?php foreach ($deleteGalleryForms as $imageId): ?>
            <form id="delete-gallery-image-<?= $imageId ?>" method="POST"
                action="/ani-mart/public/seller-center/shop/gallery/delete" class="d-none">
                <input type="hidden" name="return_to" value="shop-customize">
                <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                <input type="hidden" name="image_id" value="<?= $imageId ?>">
            
                <input type="hidden"
       name="shop_name"
       value="<?= htmlspecialchars(
           $seller?->shop_name
           ?? $activeShop?->shop_name
           ?? ''
       ) ?>">
            
            </form>
        <?php endforeach; ?>

    </section>

    <!-- SHOP APPEARANCE -->
    <section id="shopCustomizePanel" class="seller-panel<?= $appearanceActive ? ' active' : '' ?>">

        <form method="POST" action="/ani-mart/public/seller-center/shop/customize" enctype="multipart/form-data">
            <input type="hidden" name="return_tab" value="appearance">

            <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token()) ?>">

            <div class="seller-grid">

                <div class="seller-main-column">

                    <!-- =========================================================
     SHOP APPEARANCE
========================================================= -->

                    <div class="seller-card mb-4">

                        <!-- Header -->
                        <div class="seller-card-heading">
                            <div>
                                <span class="seller-card-icon">
                                    <i class="bi bi-palette"></i>
                                </span>

                                <div>
                                    <h2>Shop Appearance</h2>
                                    <p>Choose the visual style of your storefront.</p>
                                </div>
                            </div>
                        </div>


                        <!-- Theme Selection -->
                        <!-- =========================================================
     SHOP THEME SELECTOR
========================================================= -->

                        <?php

                        $themes = array_intersect_key(\App\Services\SellerProfileService::themes(), array_flip([
                            'fresh-farm', 'harvest-gold', 'tomato-harvest', 'nature-blue', 'mint-garden', 'carrot-orange', 'berry-garden', 'olive-grove', 'peach-blossom', 'earthen-farm'
                        ]));

                        ?>

                        <fieldset class="shop-theme-fieldset">

                            <legend class="form-label fw-semibold mb-3">
                                Choose a Theme
                            </legend>

                            <p class="theme-help-text">
                                Your saved theme is marked “Active theme.” Select another theme to preview it, then save your changes to make it active.
                            </p>

                            <div class="theme-grid" id="themeGrid">

                                <?php foreach ($themes as $key => $theme): ?>

                                    <?php
                                    $accent = $theme['accent'];
                                    $secondary = $theme['secondary'];
                                    $background = $theme['background'];
                                    $text = $theme['text'];

                                    $isSelected =
                                        ($activeShop?->shop_theme ?? 'fresh-farm') === $key;

                                    ?>

                                    <label
                                        class="theme-choice <?= $isSelected ? 'selected current-theme' : '' ?>"
                                        data-theme-key="<?= htmlspecialchars($key) ?>"
                                        data-active-theme="<?= $isSelected ? 'true' : 'false' ?>"
                                        style="
                --theme-accent: <?= htmlspecialchars($accent) ?>;
                --theme-secondary: <?= htmlspecialchars($secondary) ?>;
                --theme-bg: <?= htmlspecialchars($background) ?>;
                --theme-text: <?= htmlspecialchars($text) ?>;
            ">

                                        <input type="radio" name="shop_theme" value="<?= htmlspecialchars($key) ?>"
                                            <?= $isSelected ? 'checked' : '' ?>>

                                        <?php if ($isSelected): ?>
                                            <span class="active-theme-badge">
                                                <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
                                                Active theme
                                            </span>
                                        <?php endif; ?>

                                        <!-- PREVIEW -->
                                        <div class="theme-preview">

                                            <div class="theme-preview-header">

                                                <div class="theme-preview-brand">

                                                    <div class="theme-preview-logo">
                                                        <i class="bi bi-shop"></i>
                                                    </div>

                                                    <span class="theme-preview-title">
                                                        Ani-Mart
                                                    </span>

                                                </div>

                                                <div class="theme-preview-nav">
                                                    <span></span>
                                                    <span></span>
                                                    <span></span>
                                                </div>

                                            </div>

                                            <div class="theme-preview-body">

                                                <div class="theme-preview-content">

                                                    <strong>
                                                        <?= htmlspecialchars($theme['label']) ?>
                                                    </strong>

                                                    <div class="theme-preview-products">
                                                        <span></span>
                                                        <span></span>
                                                        <span></span>
                                                    </div>

                                                </div>

                                            </div>

                                        </div>

                                        <!-- THEME INFO -->
                                        <div class="theme-choice-info">

                                            <div class="theme-name-wrapper">

                                                <strong>
                                                    <?= htmlspecialchars($theme['label']) ?>
                                                </strong>

                                                <small>
                                                    <?= htmlspecialchars($accent) ?>
                                                </small>

                                            </div>

                                            <span class="theme-check">
                                                <i class="bi bi-check-lg"></i>
                                            </span>

                                        </div>

                                    </label>


                                <?php endforeach; ?>

                            </div>

                            <div class="theme-more-wrapper">
                                <button type="button" id="themeMoreBtn" class="theme-more-btn">
                                    <span>Show More Themes</span>
                                    <i class="bi bi-chevron-down"></i>
                                </button>
                            </div>

                        </fieldset>

                    </div>


                    <!-- =========================================================
     THEME COLORS
========================================================= -->




                    <!-- =========================================================
     THEME CSS
========================================================= -->


                    <button type="submit" class="btn btn-success rounded-pill px-4">
                        <i class="bi bi-check2-circle me-2"></i>
                        Save Shop Appearance
                    </button>

                </div>

                <aside class="seller-side-column">

                    <div class="seller-card shop-live-preview">
                        <div class="seller-card-heading">
                            <div>
                                <span class="seller-card-icon"><i class="bi bi-eye"></i></span>
                                <div>
                                    <h2>Live Preview</h2>
                                    <p>See how your storefront identity looks.</p>
                                </div>
                            </div>
                        </div>

                        <div class="preview-store-card">
                            <div id="previewBanner" class="shop-preview-banner"
                                style="background-image:url('<?= htmlspecialchars($activeShop?->farm_banner ?? '') ?>')">
                            </div>

                            <div class="preview-store-body">
                                <div class="d-flex align-items-center gap-3">
                                    <div id="previewLogo" class="shop-preview-logo">
                                        <?php if (!empty($activeShop?->farm_logo)): ?>
                                            <img src="<?= htmlspecialchars($activeShop?->farm_logo) ?>" alt="Shop logo">
                                        <?php else: ?>
                                            <?= htmlspecialchars(strtoupper(substr($activeShop?->shop_name ?? 'S', 0, 1))) ?>
                                        <?php endif; ?>
                                    </div>

                                    <div>
                                        <h3 id="previewName">
                                            <?= htmlspecialchars($activeShop?->shop_name ?? 'Your Shop') ?>
                                        </h3>
                                        <span class="badge text-bg-success">
                                            <i class="bi bi-patch-check-fill me-1"></i> Verified Farmer
                                        </span>
                                    </div>
                                </div>

                                <p id="previewDescription" class="text-muted mt-3 mb-0">
                                    <?= htmlspecialchars($activeShop?->description ?? 'Your shop description will appear here.') ?>
                                </p>

                                <div id="previewTheme" class="small mt-3 p-3 rounded-3"
                                    style="background-color: <?= htmlspecialchars($savedTheme['secondary']) ?>; color: <?= htmlspecialchars($savedTheme['accent']) ?>;">
                                    <?= htmlspecialchars($savedTheme['label'] ?? 'Fresh Farm') ?> theme
                                </div>
                            </div>
                        </div>

                    </div>

                </aside>

            </div>

        </form>

    </section>

    <!-- FEATURED PRODUCTS -->
    <section id="shopProductsPanel" class="seller-panel<?= $featuredActive ? ' active' : '' ?>">

        <form method="POST" action="/ani-mart/public/seller-center/shop/customize">
            <input type="hidden" name="save_featured_products" value="1">
            <input type="hidden" name="return_tab" value="featured">
            <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token()) ?>">

            <div class="seller-card">

                <div class="seller-card-heading">
                    <div>
                        <span class="seller-card-icon">
                            <i class="bi bi-star"></i>
                        </span>

                        <div>
                            <h2>Featured Products</h2>
                            <p>Choose up to 6 approved, active products to highlight on your storefront.</p>
                        </div>
                    </div>

                    <span class="badge bg-light text-dark">Optional</span>
                </div>

                <?php if (!empty($availableProducts)): ?>
                    <div class="row g-3">

                        <?php foreach ($availableProducts as $product): ?>
                            <div class="col-md-6 col-xl-4">
                                <label class="product-check featured-product-select">

                                    <input type="checkbox" name="featured_products[]" value="<?= (int) $product->id ?>"
                                        <?= in_array((int) $product->id, $featuredIds, true) ? 'checked' : '' ?>>

                                    <span class="featured-product-icon">
                                        <i class="bi bi-box-seam"></i>
                                    </span>

                                    <span class="featured-product-details">
                                        <strong><?= htmlspecialchars($product->name) ?></strong>
                                        <small>Approved active product</small>
                                    </span>

                                    <span class="featured-product-check">
                                        <i class="bi bi-check-lg"></i>
                                    </span>

                                </label>
                            </div>
                        <?php endforeach; ?>

                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="bi bi-box-seam"></i>
                        <strong>No products available</strong>
                        <span>Approved active products will appear here.</span>
                    </div>
                <?php endif; ?>

                <div class="featured-products-footer">
                    <div class="featured-limit-text">
                        <i class="bi bi-info-circle me-1"></i>
                        You can select a maximum of 6 products.
                    </div>
                    <button type="submit" class="btn btn-success rounded-pill px-4">
                        <i class="bi bi-check2-circle me-2"></i>
                        Save Featured Products
                    </button>
                </div>

            </div>
        </form>

    </section>

</div>



<script>
    (() => {
        const tabs = document.querySelectorAll('.seller-tab');
        const panels = document.querySelectorAll('.seller-panel');

        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                tabs.forEach(item => item.classList.remove('active'));
                panels.forEach(panel => panel.classList.remove('active'));

                tab.classList.add('active');

                const target = document.getElementById(tab.dataset.target);
                if (target) target.classList.add('active');

                window.scrollTo({
                    top: document.querySelector('.seller-tabs')?.offsetTop - 90 || 0,
                    behavior: 'smooth'
                });
            });
        });

        const logoInput = document.getElementById('farm_logo_input');
        const bannerInput = document.getElementById('farm_banner_input');
        const brandingUploadStatus = document.getElementById('brandingUploadStatus');

        function readImagePreview(file, onReady) {
            const reader = new FileReader();
            reader.addEventListener('load', () => onReady(reader.result));
            reader.addEventListener('error', () => {
                if (brandingUploadStatus) {
                    brandingUploadStatus.textContent = 'This image could not be previewed. Please choose another JPG, PNG, or WEBP file.';
                }
            });
            reader.readAsDataURL(file);
        }

        document.querySelectorAll('.image-edit-overlay, .farm-banner-edit').forEach(button => {
            button.addEventListener('click', () => {
                const input = document.getElementById(button.dataset.target);
                if (input) input.click();
            });
        });

        if (logoInput) {
            logoInput.addEventListener('change', () => {
                const file = logoInput.files[0];
                if (!file) return;

                readImagePreview(file, url => {
                    const logo = document.querySelector('.farm-editor-logo');
                    if (logo) logo.innerHTML = `<img src="${url}" alt="Farm logo preview">`;

                    const previewLogo = document.getElementById('previewLogo');
                    if (previewLogo) previewLogo.innerHTML = `<img src="${url}" alt="Shop logo">`;
                });
                if (brandingUploadStatus) brandingUploadStatus.textContent = `${file.name} is previewing above. Click “Save Farm Profile” below to upload it.`;
            });
        }

        if (bannerInput) {
            bannerInput.addEventListener('change', () => {
                const file = bannerInput.files[0];
                if (!file) return;

                readImagePreview(file, url => {
                    const banner = document.querySelector('.farm-editor-banner');
                    if (banner) {
                        banner.onerror = null;
                        banner.style.display = 'block';
                        banner.src = url;
                    }

                    const previewBanner = document.getElementById('previewBanner');
                    if (previewBanner) previewBanner.style.backgroundImage = `url("${url}")`;
                });
                if (brandingUploadStatus) brandingUploadStatus.textContent = `${file.name} is previewing above. Click “Save Farm Profile” below to upload it.`;
            });
        }

        const description = document.querySelector('textarea[name="description"]');
        const previewDescription = document.getElementById('previewDescription');

        if (description && previewDescription) {
            description.addEventListener('input', () => {
                previewDescription.textContent =
                    description.value || 'Your shop description will appear here.';
            });
        }

        const themes = <?= json_encode($themes, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        const root = document.querySelector('.seller-centre');
        const previewTheme = document.getElementById('previewTheme');

        document.querySelectorAll('input[name="shop_theme"]').forEach(input => {
            input.addEventListener('change', () => {
                const theme = themes[input.value];
                if (!theme) return;

                document.querySelectorAll('.theme-choice').forEach(choice => {
                    choice.classList.toggle(
                        'selected',
                        choice.querySelector('input[name="shop_theme"]')?.checked === true
                    );
                });

                if (root) {
                    const normalizedAccent = String(theme.accent || '').replace('#', '');
                    const fullAccent = normalizedAccent.length === 3
                        ? normalizedAccent.split('').map(character => character + character).join('')
                        : normalizedAccent;
                    const red = parseInt(fullAccent.slice(0, 2), 16);
                    const green = parseInt(fullAccent.slice(2, 4), 16);
                    const blue = parseInt(fullAccent.slice(4, 6), 16);
                    const brightness = ((red * 299) + (green * 587) + (blue * 114)) / 1000;

                    root.style.setProperty('--shop-accent', theme.accent);
                    root.style.setProperty('--seller-green', theme.accent);
                    root.style.setProperty('--seller-green-soft', theme.background);
                    root.style.setProperty('--seller-theme-secondary', theme.secondary);
                    root.style.setProperty('--seller-theme-text', theme.text);
                    root.style.setProperty('--seller-button-text', brightness >= 150 ? '#17211b' : '#ffffff');
                    root.dataset.shopTheme = input.value;
                }

                if (previewTheme) {
                    previewTheme.textContent = theme.label + ' theme';
                    previewTheme.style.backgroundColor = theme.secondary;
                    previewTheme.style.color = theme.accent;
                }
            });
        });

        document.querySelectorAll('input[name="featured_products[]"]').forEach(input => {
            input.addEventListener('change', () => {
                const checked = document.querySelectorAll(
                    'input[name="featured_products[]"]:checked'
                );

                if (checked.length > 6) {
                    input.checked = false;
                    alert('You can feature up to 6 products.');
                }
            });
        });
    })();
    document.addEventListener('DOMContentLoaded', function () {

        const themeGrid = document.getElementById('themeGrid');
        const themeMoreBtn = document.getElementById('themeMoreBtn');

        if (!themeGrid || !themeMoreBtn) {
            return;
        }

        themeMoreBtn.addEventListener('click', function () {

            const expanded = themeGrid.classList.toggle('show-all');

            themeMoreBtn.classList.toggle('active', expanded);

            const text = themeMoreBtn.querySelector('span');

            if (expanded) {
                text.textContent = 'Show Less Themes';
            } else {
                text.textContent = 'Show More Themes';

                // Keep the theme section in view
                themeGrid.scrollIntoView({
                    behavior: 'smooth',
                    block: 'nearest'
                });
            }

        });

    });
</script>

<?php
$content = ob_get_clean();
require ANI_MART_ROOT . '/app/Views/layouts/main.php';
?>
