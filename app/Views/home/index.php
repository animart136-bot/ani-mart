<?php

$title = "Ani-Mart";

$pageStyles[] = '/ani-mart/public/assets/css/pages/home-landing.css?v=' . filemtime(__DIR__ . '/../../../public/assets/css/pages/home-landing.css');

$featuredProducts = $featuredProducts ?? [];
$popularCategories = $popularCategories ?? [];
$categoryIcons = [
    'vegetables' => 'bi-flower1',
    'fruits' => 'bi-apple',
    'grains' => 'bi-basket2',
    'dairy-and-eggs' => 'bi-egg',
    'livestock-and-poultry' => 'bi-box2-heart',
];

ob_start();

?>



<!-- HERO -->

<section class="landing-hero">

    <div class="container">

        <div class="row align-items-center min-vh-100">

            <!-- LEFT -->

            <div class="col-lg-6">

                <span class="hero-badge">

                    Fresh Local Marketplace

                </span>

                <h1 class="hero-title mt-4">

                    Buy Fresh Products
                    Directly From Farmers

                </h1>

                <p class="hero-subtitle mt-4">

                    Ani-Mart connects local farmers and customers
                    through a modern marketplace platform for
                    fresh and affordable agricultural products.

                </p>

                <div class="d-flex gap-3 mt-5 flex-wrap">

                    <a href="/ani-mart/public/marketplace" class="btn btn-success hero-btn">

                        Explore Marketplace

                    </a>

                    <a href="/ani-mart/public/register" class="btn btn-outline-success hero-btn-outline">

                        Become a Seller

                    </a>

                </div>

                <!-- STATS -->

                <div class="row mt-5 g-4">

                    <div class="col-4">

                        <div class="hero-stat">

                            <h3>

                                <?= htmlspecialchars(number_format($farmersCount)) ?>

                            </h3>

                            <p>

                                Farmers

                            </p>

                        </div>

                    </div>

                    <div class="col-4">

                        <div class="hero-stat">

                            <h3>

                                <?= htmlspecialchars(number_format($productsCount)) ?>

                            </h3>

                            <p>

                                Products

                            </p>

                        </div>

                    </div>

                    <div class="col-4">

                        <div class="hero-stat">

                            <h3>

                                <?= htmlspecialchars(number_format($ordersCount)) ?>

                            </h3>

                            <p>

                                Completed Orders

                            </p>

                        </div>

                    </div>

                </div>

            </div>

            <!-- RIGHT -->

            <div class="col-lg-6">

                <div class="hero-image-wrapper">

                    <img src="https://images.unsplash.com/photo-1542838132-92c53300491e?q=80&w=1200"
                        data-image-modal="false"
                        class="img-fluid hero-image">

                    <div class="floating-card card-1">

                        Organic Vegetables

                    </div>

                    <div class="floating-card card-2">

                        Fast Delivery

                    </div>

                </div>

            </div>

        </div>

    </div>

</section>

<!-- FEATURES -->

<section class="landing-section landing-features">

    <div class="container">

        <div class="text-center mb-5">

            <h2 class="fw-bold">

                Why Choose Ani-Mart?

            </h2>

            <p class="section-lead">

                A simpler way to discover local produce, know who grew it, and support farming communities.

            </p>

        </div>

        <div class="row g-4">

            <div class="col-lg-4">

                <div class="feature-card">

                    <div class="feature-icon">

                        <span class="bi bi-basket2" aria-hidden="true"></span>

                    </div>

                    <h4 class="fw-bold mt-4">

                        Fresh Products

                    </h4>

                    <p class="text-muted">

                        Farm-fresh vegetables, fruits,
                        and agricultural products.

                    </p>

                </div>

            </div>

            <div class="col-lg-4">

                <div class="feature-card">

                    <div class="feature-icon">

                        <span class="bi bi-cart2" aria-hidden="true"></span>

                    </div>

                    <h4 class="fw-bold mt-4">

                        Easy Shopping

                    </h4>

                    <p class="text-muted">

                        Smooth and modern shopping
                        experience across all devices.

                    </p>

                </div>

            </div>

            <div class="col-lg-4">

                <div class="feature-card">

                    <div class="feature-icon">

                        <span class="bi bi-truck" aria-hidden="true"></span>

                    </div>

                    <h4 class="fw-bold mt-4">

                        Support Farmers

                    </h4>

                    <p class="text-muted">

                        Help local farmers grow
                        their businesses online.

                    </p>

                </div>

            </div>

        </div>

    </div>

</section>

<!-- LIVE MARKETPLACE -->

<section class="landing-section landing-marketplace">
    <div class="container">
        <div class="section-heading-row">
            <div>
                <span class="section-kicker">Live marketplace</span>
                <h2 class="section-title">Fresh from Ani-Mart sellers</h2>
                <p class="section-lead mb-0">Recently listed products pulled directly from the approved marketplace catalog.</p>
            </div>
            <a href="/ani-mart/public/marketplace" class="section-link">
                View all products <i class="bi bi-arrow-right" aria-hidden="true"></i>
            </a>
        </div>

        <?php if (!empty($featuredProducts)): ?>
            <div class="row g-4">
                <?php foreach ($featuredProducts as $product): ?>
                    <?php
                    $productImage = !empty($product->product_image)
                        ? $product->product_image
                        : '/ani-mart/public/assets/img/product-placeholder.svg';
                    $stock = (int) ($product->available_stock ?? 0);
                    ?>
                    <div class="col-sm-6 col-xl-3">
                        <article class="landing-product-card h-100">
                            <a href="/ani-mart/public/marketplace" class="landing-product-image" aria-label="View <?= htmlspecialchars($product->name) ?> in the marketplace">
                                <img src="<?= htmlspecialchars($productImage) ?>" alt="<?= htmlspecialchars($product->name) ?>" loading="lazy">
                                <span class="stock-pill <?= $stock > 0 ? '' : 'out-of-stock' ?>">
                                    <?= $stock > 0 ? htmlspecialchars(number_format($stock)) . ' available' : 'Out of stock' ?>
                                </span>
                            </a>
                            <div class="landing-product-body">
                                <span class="product-category"><?= htmlspecialchars($product->category_name ?? 'Farm product') ?></span>
                                <h3><?= htmlspecialchars($product->name) ?></h3>
                                <div class="product-price">
                                    ₱<?= htmlspecialchars(number_format((float) ($product->display_price ?? $product->price ?? 0), 2)) ?>
                                    <?php if (!empty($product->unit)): ?>
                                        <small>/ <?= htmlspecialchars($product->unit) ?></small>
                                    <?php endif; ?>
                                </div>
                                <div class="product-meta">
                                    <a href="/ani-mart/public/farm/<?= rawurlencode((string) $product->farm_slug) ?>">
                                        <?= htmlspecialchars($product->shop_name) ?>
                                    </a>
                                    <?php if ((int) ($product->review_count ?? 0) > 0): ?>
                                        <span><i class="bi bi-star-fill" aria-hidden="true"></i> <?= htmlspecialchars(number_format((float) $product->average_rating, 1)) ?></span>
                                    <?php else: ?>
                                        <span>New listing</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </article>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="landing-empty-state">
                <i class="bi bi-basket2" aria-hidden="true"></i>
                <h3>New harvests are on the way</h3>
                <p>Approved seller products will appear here as soon as they are available.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- CATEGORIES -->

<?php if (!empty($popularCategories)): ?>
    <section class="landing-section landing-categories">
        <div class="container">
            <div class="text-center landing-narrow-heading">
                <span class="section-kicker">Browse what is growing</span>
                <h2 class="section-title">Shop by category</h2>
                <p class="section-lead">Every total below reflects products that are active and approved right now.</p>
            </div>
            <div class="category-grid">
                <?php foreach ($popularCategories as $category): ?>
                    <?php $icon = $categoryIcons[$category->slug] ?? 'bi-basket'; ?>
                    <a href="/ani-mart/public/marketplace" class="landing-category-card">
                        <span class="category-icon"><i class="bi <?= htmlspecialchars($icon) ?>" aria-hidden="true"></i></span>
                        <span>
                            <strong><?= htmlspecialchars($category->name) ?></strong>
                            <small><?= htmlspecialchars(number_format((int) $category->product_count)) ?> <?= (int) $category->product_count === 1 ? 'product' : 'products' ?></small>
                        </span>
                        <i class="bi bi-arrow-up-right" aria-hidden="true"></i>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<!-- HOW IT WORKS -->

<section class="landing-section landing-how">
    <div class="container">
        <div class="landing-narrow-heading text-center">
            <span class="section-kicker">From farm to your table</span>
            <h2 class="section-title">How Ani-Mart works</h2>
            <p class="section-lead">Find food nearby, purchase with confidence, and keep in touch with the people who produce it.</p>
        </div>
        <div class="how-grid">
            <article class="how-card">
                <span class="step-number">01</span>
                <i class="bi bi-search" aria-hidden="true"></i>
                <h3>Discover local products</h3>
                <p>Explore approved listings, compare options, and visit each seller's farm profile.</p>
            </article>
            <article class="how-card">
                <span class="step-number">02</span>
                <i class="bi bi-bag-check" aria-hidden="true"></i>
                <h3>Order from farmers</h3>
                <p>Add available products to your cart and follow the order from checkout to completion.</p>
            </article>
            <article class="how-card">
                <span class="step-number">03</span>
                <i class="bi bi-people" aria-hidden="true"></i>
                <h3>Build local connections</h3>
                <p>Review your experience and join community conversations about local agriculture.</p>
            </article>
        </div>
    </div>
</section>

<!-- TRUST -->

<section class="landing-section landing-trust">
    <div class="container">
        <div class="trust-panel">
            <div class="trust-copy">
                <span class="section-kicker section-kicker-light">Marketplace confidence</span>
                <h2>Know more about every purchase.</h2>
                <p>Ani-Mart combines seller review, product approval, visible farm profiles, community feedback, and accountable marketplace policies.</p>
                <div class="trust-actions">
                    <a href="/ani-mart/public/marketplace" class="btn btn-light landing-cta-btn">Explore verified listings</a>
                    <a href="/ani-mart/public/policies" class="btn btn-outline-light landing-cta-btn">Read marketplace policies</a>
                </div>
            </div>
            <div class="trust-points" aria-label="Ani-Mart trust features">
                <div><i class="bi bi-patch-check" aria-hidden="true"></i><span><strong>Seller review</strong><small>Applications are reviewed before shops enter the marketplace.</small></span></div>
                <div><i class="bi bi-shield-check" aria-hidden="true"></i><span><strong>Product oversight</strong><small>Listings pass through marketplace approval and safety controls.</small></span></div>
                <div><i class="bi bi-chat-heart" aria-hidden="true"></i><span><strong>Real feedback</strong><small>Customer ratings and community activity help buyers make informed choices.</small></span></div>
            </div>
        </div>
    </div>
</section>

<!-- FINAL CTA -->

<section class="landing-section landing-final-cta">
    <div class="container">
        <div class="final-cta-card">
            <div>
                <span class="section-kicker">Grow with Ani-Mart</span>
                <h2>Fresh choices for buyers. A wider market for farmers.</h2>
                <p>Join the local marketplace and start connecting with your agricultural community.</p>
            </div>
            <div class="final-cta-actions">
                <a href="/ani-mart/public/register" class="btn btn-success landing-cta-btn">Create an account</a>
                <a href="/ani-mart/public/community" class="btn btn-outline-success landing-cta-btn">Visit the community</a>
            </div>
        </div>
    </div>
</section>

<?php

$content = ob_get_clean();

require ANI_MART_ROOT . '/app/Views/layouts/main.php';

?>
