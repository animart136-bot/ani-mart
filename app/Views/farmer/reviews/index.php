<?php
$title = 'Shop Reviews';
$reviews = $reviews ?? [];
$activeShop = $activeShop ?? null;

$pageStyles[] = '/ani-mart/public/assets/css/pages/seller-reviews.css?v=' . filemtime(__DIR__ . '/../../../../public/assets/css/pages/seller-reviews.css');

ob_start();
?>

<div class="container app-page-spacing reviews-page">

    <!-- HEADER -->
    <div class="dashboard-hero mb-5">
        <span class="hero-badge">
            <?= htmlspecialchars($activeShop->shop_name ?? 'Shop') ?>
        </span>

        <h1 class="hero-title mt-3">Shop Reviews</h1>

        <p class="hero-subtitle">
            Feedback customers have shared about your products and service.
        </p>
    </div>

    <?php if (empty($reviews)): ?>

        <!-- EMPTY STATE -->
        <section class="reviews-empty">
            <div class="reviews-empty-icon">
                <i class="bi bi-chat-square-heart"></i>
            </div>

            <h2>No reviews yet</h2>

            <p>
                Customer reviews for this shop will appear here after delivered orders.
            </p>
        </section>

    <?php else: ?>

        <!-- REVIEW COUNT -->
        <div class="reviews-section-heading">
            <div>
                <span class="reviews-eyebrow">CUSTOMER FEEDBACK</span>
                <h2>What customers are saying</h2>
            </div>

            <div class="reviews-count">
                <i class="bi bi-chat-left-text"></i>
                <strong><?= count($reviews) ?></strong>
                <span><?= count($reviews) === 1 ? 'Review' : 'Reviews' ?></span>
            </div>
        </div>

        <!-- REVIEWS -->
        <div class="row g-4 reviews-grid">

            <?php foreach ($reviews as $review): ?>

                <?php
                $firstInitial = strtoupper(
                    substr(trim((string) ($review->first_name ?? '')), 0, 1)
                );

                $lastInitial = strtoupper(
                    substr(trim((string) ($review->last_name ?? '')), 0, 1)
                );

                $customerInitials =
                    trim($firstInitial . $lastInitial) ?: 'C';

                $rating = max(0, min(5, (int) ($review->rating ?? 0)));
                $freshness = max(0, min(5, (int) ($review->freshness_rating ?? 0)));
                $responsiveness = max(0, min(5, (int) ($review->seller_responsiveness_rating ?? 0)));
                $condition = max(0, min(5, (int) ($review->product_condition_rating ?? 0)));
                $driverFriendliness = max(0, min(5, (int) ($review->driver_friendliness_rating ?? 0)));
                $deliverySpeed = max(0, min(5, (int) ($review->delivery_speed_rating ?? 0)));

                $reviewDate = !empty($review->created_at)
                    ? date('M j, Y', strtotime($review->created_at))
                    : 'Recently';
                ?>

                <div class="col-6 col-md-3">

                    <article class="modern-review-card">

                        <!-- PRODUCT -->
                        <div class="review-product">
                            <?php if (!empty($review->product_image)): ?>

                                <img
                                    src="<?= htmlspecialchars($review->product_image) ?>"
                                    class="review-product-image"
                                    alt="<?= htmlspecialchars($review->product_name ?? 'Product') ?>">

                            <?php else: ?>

                                <div class="review-product-placeholder">
                                    <i class="bi bi-basket"></i>
                                </div>

                            <?php endif; ?>

                            <div class="review-product-info">
                                <span class="review-product-label">PRODUCT</span>

                                <h3>
                                    <?= htmlspecialchars($review->product_name ?? 'Product') ?>
                                </h3>

                                <span class="review-date">
                                    <i class="bi bi-calendar3"></i>
                                    <?= htmlspecialchars($reviewDate) ?>
                                </span>
                            </div>
                        </div>

                        <!-- CUSTOMER -->
                        <div class="review-customer">
                            <div class="customer-avatar">
                                <?= htmlspecialchars($customerInitials) ?>
                            </div>

                            <div class="customer-info">
                                <span>Reviewed by</span>
                                <strong><?= htmlspecialchars($customerInitials) ?></strong>
                            </div>

                            <div
                                class="review-score"
                                aria-label="<?= $rating ?> out of 5 stars">

                                <i class="bi bi-star-fill"></i>
                                <strong><?= $rating ?>.0</strong>
                            </div>
                        </div>

                        <!-- RATING -->
                        <div class="review-rating">
                            <div class="stars" aria-hidden="true">
                                <?php for ($star = 1; $star <= 5; $star++): ?>
                                    <i class="bi <?= $star <= $rating ? 'bi-star-fill' : 'bi-star' ?>"></i>
                                <?php endfor; ?>
                            </div>

                            <span><?= $rating ?>/5 overall rating</span>
                        </div>

                        <details class="review-more">
                            <summary class="review-more-toggle">
                                <span class="review-more-label">
                                    <span class="review-more-open-label">View more</span>
                                    <span class="review-more-close-label">Show less</span>
                                </span>
                                <i class="bi bi-chevron-down" aria-hidden="true"></i>
                            </summary>

                            <div class="review-more-content">
                                <!-- DETAILS -->
                                <div class="review-details">
                            <div class="review-detail">
                                <div>
                                    <i class="bi bi-flower1"></i>
                                    <span>Freshness</span>
                                </div>

                                <div class="detail-stars" aria-label="<?= $freshness ?> out of 5">
                                    <?php for ($star = 1; $star <= 5; $star++): ?>
                                        <i class="bi <?= $star <= $freshness ? 'bi-star-fill' : 'bi-star' ?>"></i>
                                    <?php endfor; ?>
                                </div>
                            </div>

                            <div class="review-detail">
                                <div>
                                    <i class="bi bi-person-check"></i>
                                    <span>Seller response</span>
                                </div>

                                <div class="detail-stars" aria-label="<?= $responsiveness ?> out of 5">
                                    <?php for ($star = 1; $star <= 5; $star++): ?>
                                        <i class="bi <?= $star <= $responsiveness ? 'bi-star-fill' : 'bi-star' ?>"></i>
                                    <?php endfor; ?>
                                </div>
                            </div>

                            <?php foreach ([
                                ['bi-box-seam', 'Product condition', $condition],
                                ['bi-emoji-smile', 'Driver friendliness', $driverFriendliness],
                                ['bi-stopwatch', 'Delivery speed', $deliverySpeed],
                            ] as [$icon, $label, $score]): ?>
                                <div class="review-detail">
                                    <div><i class="bi <?= $icon ?>"></i><span><?= $label ?></span></div>
                                    <div class="detail-stars" aria-label="<?= $score ?> out of 5">
                                        <?php for ($star = 1; $star <= 5; $star++): ?>
                                            <i class="bi <?= $star <= $score ? 'bi-star-fill' : 'bi-star' ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                                </div>

                                <!-- COMMENT -->
                                <?php if (!empty($review->comment)): ?>

                                    <div class="review-comment">
                                        <i class="bi bi-quote"></i>

                                        <p>
                                            <?= nl2br(htmlspecialchars($review->comment)) ?>
                                        </p>
                                    </div>

                                <?php endif; ?>

                                <!-- PHOTO -->
                                <?php if (!empty($review->review_photo)): ?>

                                    <div class="review-photo-wrapper">
                                        <img
                                            src="<?= htmlspecialchars($review->review_photo) ?>"
                                            class="review-evidence"
                                            alt="Customer review photo"
                                            data-image-modal="true">
                                    </div>

                                <?php endif; ?>
                            </div>
                        </details>

                    </article>

                </div>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</div>



<?php
$content = ob_get_clean();
require ANI_MART_ROOT . '/app/Views/layouts/main.php';
?>
