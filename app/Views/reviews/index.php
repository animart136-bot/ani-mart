<?php
$title = 'My Reviews';
$reviews = $reviews ?? [];
ob_start();
?>
<div class="container app-page-spacing">
    <div class="dashboard-hero mb-5">
        <span class="hero-badge"></i>Customer Feedback</span>
        <h1 class="hero-title mt-3">My Reviews</h1>
        <p class="hero-subtitle">Reviews and ratings you have shared with Ani-Mart sellers.</p>
    </div>

    <?php if (empty($reviews)): ?>
        <div class="empty-state-card text-center py-5">
            <i class="bi bi-chat-square-heart fs-1 text-success mb-3 d-block"></i>
            <h2 class="h4 fw-bold">No reviews yet</h2>
            <p class="text-muted mb-4">After a delivered order, you can review the products you purchased.</p>
            <a href="/ani-mart/public/orders" class="btn btn-success rounded-pill px-4">View My Orders</a>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($reviews as $review): ?>
                <div class="col-12 col-sm-6 col-lg-3">
                    <article class="review-card h-100">
                        <div class="d-flex gap-3 align-items-start">
                            <?php if (!empty($review->product_image)): ?>
                                <img src="<?= htmlspecialchars($review->product_image) ?>" class="review-product-image" alt="<?= htmlspecialchars($review->product_name) ?>">
                            <?php else: ?>
                                <div class="review-product-placeholder"><i class="bi bi-basket"></i></div>
                            <?php endif; ?>
                            <div class="flex-grow-1">
                                <h2 class="h5 fw-bold mb-1"><?= htmlspecialchars($review->product_name ?? 'Product') ?></h2>
                                <p class="text-muted small mb-1"><?= htmlspecialchars($review->shop_name ?? 'Seller') ?></p>
                                <small class="text-muted"><?= htmlspecialchars(date('M j, Y', strtotime($review->created_at ?? 'now'))) ?></small>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center gap-2 mt-4">
                            <div class="review-rating" aria-label="<?= (int) $review->rating ?> out of 5 stars">
                                <?php for ($star = 1; $star <= 5; $star++): ?><i class="bi <?= $star <= (int) $review->rating ? 'bi-star-fill' : 'bi-star' ?>"></i><?php endfor; ?>
                                <strong class="ms-2"><?= (int) $review->rating ?>/5</strong>
                            </div>
                            <a href="/ani-mart/public/reviews/edit?id=<?= (int) $review->id ?>" class="btn btn-outline-success btn-sm rounded-pill"><i class="bi bi-pencil me-1"></i>Edit</a>
                        </div>

                        <div class="review-card-content">
                            <div class="review-details mt-3">
                                <span>Freshness: <?= (int) $review->freshness_rating ?>/5</span>
                                <span>Seller response: <?= (int) $review->seller_responsiveness_rating ?>/5</span>
                                <span>Product condition: <?= (int) ($review->product_condition_rating ?? 0) ?>/5</span>
                                <span>Driver friendliness: <?= (int) ($review->driver_friendliness_rating ?? 0) ?>/5</span>
                                <span>Delivery speed: <?= (int) ($review->delivery_speed_rating ?? 0) ?>/5</span>
                            </div>
                            <?php if (!empty($review->comment)): ?><p class="mb-0 mt-3 review-comment">“<?= nl2br(htmlspecialchars($review->comment)) ?>”</p><?php endif; ?>
                            <?php if (!empty($review->review_photo)): ?><img src="<?= htmlspecialchars($review->review_photo) ?>" class="review-evidence mt-3" alt="Review evidence photo" data-image-modal="true"><?php endif; ?>
                        </div>

                        <button type="button" class="review-toggle-btn" data-review-toggle aria-expanded="false">
                            <span class="review-toggle-text">Show more</span>
                            <i class="bi bi-chevron-down"></i>
                        </button>
                    </article>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-review-toggle]').forEach(function (button) {
            button.addEventListener('click', function () {
                const card = button.closest('.review-card');
                const isExpanded = card.classList.toggle('is-expanded');
                button.setAttribute('aria-expanded', String(isExpanded));
                const label = button.querySelector('.review-toggle-text');
                if (label) {
                    label.textContent = isExpanded ? 'Show less' : 'Show more';
                }
            });
        });
    });
</script>
<?php
$content = ob_get_clean();
$pageCss = '/ani-mart/public/assets/css/pages/reviews.css';
require ANI_MART_ROOT . '/app/Views/layouts/main.php';
?>
