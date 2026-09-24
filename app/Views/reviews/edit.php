<?php
$title = 'Edit Review';
$review = (object) ($review ?? []);
$pageStyles[] = '/ani-mart/public/assets/css/pages/review-edit.css?v=' . filemtime(__DIR__ . '/../../../public/assets/css/pages/review-edit.css');

ob_start();
?>
<div class="container app-page-spacing">
    <section class="dashboard-hero mb-5">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <span class="hero-badge">Customer Feedback</span>
                <h1 class="hero-title mt-4">Refine your review</h1>
                <p class="hero-subtitle mt-3">Update your experience so sellers can keep improving the local marketplace.</p>
                <a href="/ani-mart/public/reviews" class="hero-back-link">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i> My reviews
                </a>
            </div>
        </div>
    </section>

    <div class="row g-4 align-items-start">
        <div class="col-lg-7">
        <div class="review-edit-card">
        <div class="d-flex gap-3 align-items-center mb-4">
            <?php if (!empty($review->product_image)): ?>
                <img src="<?= htmlspecialchars($review->product_image) ?>" class="review-product-image" alt="<?= htmlspecialchars($review->product_name ?? 'Product') ?>">
            <?php endif; ?>
            <div>
                <h1 class="h3 fw-bold mb-1">Edit Review</h1>
                <p class="text-muted mb-0"><?= htmlspecialchars($review->product_name ?? 'Product') ?> · <?= htmlspecialchars($review->shop_name ?? 'Seller') ?></p>
            </div>
        </div>
        <form method="POST" action="/ani-mart/public/customer/orders/review/update" enctype="multipart/form-data">
            <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token()) ?>">
            <input type="hidden" name="review_id" value="<?= (int) $review->id ?>">

<?php
$productAverageRating = (float) ($review->product_avg_rating ?? 0);
$productReviewCount = (int) ($review->product_review_count ?? 0);
?>

<div class="product-average-rating mb-4">
    <div class="product-average-header">
        <div>
            <span class="product-average-label">
                <i class="bi bi-bar-chart-fill me-1"></i>
                Product Rating
            </span>

            <div class="product-average-score">
                <strong><?= number_format($productAverageRating, 1) ?></strong>
                <span class="text-muted">/ 5</span>
            </div>
        </div>

        <div class="product-average-stars" aria-label="<?= number_format($productAverageRating, 1) ?> out of 5">
            <?php
            $fullStars = (int) floor($productAverageRating);
            $hasHalfStar = ($productAverageRating - $fullStars) >= 0.5;

            for ($star = 1; $star <= 5; $star++):
                if ($star <= $fullStars):
            ?>
                    <i class="bi bi-star-fill"></i>
                <?php elseif ($star === $fullStars + 1 && $hasHalfStar): ?>
                    <i class="bi bi-star-half"></i>
                <?php else: ?>
                    <i class="bi bi-star"></i>
                <?php endif; ?>
            <?php endfor; ?>
        </div>
    </div>

    <div class="product-average-footer">
        Based on <strong><?= $productReviewCount ?></strong>
        <?= $productReviewCount === 1 ? 'review' : 'reviews' ?>
    </div>
</div>

            <fieldset class="review-rating-fields mb-4">
                <legend>Rate your experience</legend>
                <p class="review-rating-help">Select 1 to 5 stars for every category.</p>

                <?php
                $ratingFields = [
                    'rating' => [
                        'label' => 'Product rating',
                        'description' => 'Overall quality and satisfaction with the product.',
                        'icon' => 'bi-bag-heart',
                        'value' => (int) ($review->rating ?? 0),
                    ],
                    'freshness_rating' => [
                        'label' => 'Freshness',
                        'description' => 'Condition and freshness when your order arrived.',
                        'icon' => 'bi-flower1',
                        'value' => (int) ($review->freshness_rating ?? 0),
                    ],
                    'seller_responsiveness_rating' => [
                        'label' => 'Seller responsiveness',
                        'description' => 'How promptly and clearly the seller communicated.',
                        'icon' => 'bi-chat-heart',
                        'value' => (int) ($review->seller_responsiveness_rating ?? 0),
                    ],
                    'product_condition_rating' => [
                        'label' => 'Product condition on arrival',
                        'description' => 'How well the item held up during delivery.',
                        'icon' => 'bi-box-seam',
                        'value' => (int) ($review->product_condition_rating ?? 0),
                    ],
                    'driver_friendliness_rating' => [
                        'label' => 'Driver friendliness',
                        'description' => 'How courteous and helpful the delivery driver was.',
                        'icon' => 'bi-emoji-smile',
                        'value' => (int) ($review->driver_friendliness_rating ?? 0),
                    ],
                    'delivery_speed_rating' => [
                        'label' => 'Delivery speed',
                        'description' => 'How satisfied you were with the delivery time.',
                        'icon' => 'bi-stopwatch',
                        'value' => (int) ($review->delivery_speed_rating ?? 0),
                    ],
                ];
                ?>

                <?php foreach ($ratingFields as $fieldName => $field): ?>
                    <div class="review-rating-field">
                        <div class="review-rating-copy">
                            <span class="review-rating-icon"><i class="bi <?= $field['icon'] ?>" aria-hidden="true"></i></span>
                            <span>
                                <strong><?= htmlspecialchars($field['label']) ?></strong>
                                <small><?= htmlspecialchars($field['description']) ?></small>
                            </span>
                        </div>

                        <div class="review-rating-control">
                            <div class="review-star-picker" data-rating="<?= htmlspecialchars($fieldName) ?>" aria-label="<?= htmlspecialchars($field['label']) ?>">
                                <?php for ($star = 1; $star <= 5; $star++): ?>
                                    <input type="radio" id="<?= htmlspecialchars($fieldName) ?>-<?= $star ?>" name="<?= htmlspecialchars($fieldName) ?>" value="<?= $star ?>" <?= $field['value'] === $star ? 'checked' : '' ?> required>
                                    <label class="<?= $star <= $field['value'] ? 'selected' : '' ?>" for="<?= htmlspecialchars($fieldName) ?>-<?= $star ?>" data-rating="<?= $star ?>" aria-label="<?= $star ?> out of 5 stars"><i class="bi bi-star-fill" aria-hidden="true"></i></label>
                                <?php endfor; ?>
                            </div>
                            <output class="review-rating-score" for="<?= htmlspecialchars($fieldName) ?>"><?= $field['value'] > 0 ? $field['value'] . '/5' : 'Not rated' ?></output>
                        </div>
                    </div>
                <?php endforeach; ?>
            </fieldset>
            <div class="mb-3"><label class="form-label fw-semibold" for="comment">Comment</label><textarea id="comment" name="comment" rows="5" maxlength="1000" class="form-control" placeholder="Share your experience"><?= htmlspecialchars($review->comment ?? '') ?></textarea></div>
            <?php if (!empty($review->review_photo)): ?><img src="<?= htmlspecialchars($review->review_photo) ?>" class="review-evidence mb-3" alt="Current review photo" data-image-modal="true"><?php endif; ?>
            <div class="mb-4"><label class="form-label fw-semibold" for="review_photo">Replace photo <span class="text-muted fw-normal">(optional)</span></label><input id="review_photo" type="file" name="review_photo" class="form-control" accept="image/jpeg,image/png,image/webp"></div>
            <div class="d-flex gap-2"><a href="/ani-mart/public/reviews" class="btn btn-light border rounded-pill">Cancel</a><button type="submit" class="btn btn-success rounded-pill">Save Changes</button></div>
        </form>
        </div>
        </div>

        <div class="col-lg-5">
            <aside class="review-preview-card">
                <div class="review-preview-heading">
                    <span class="preview-kicker"><i class="bi bi-eye me-2"></i>Live Preview</span>
                    <h2 class="h4 fw-bold mb-1">Your review</h2>
                    <p class="text-muted small mb-0">This is how your feedback will appear to the seller.</p>
                </div>
                <div class="review-preview-product">
                    <?php if (!empty($review->product_image)): ?>
                        <img src="<?= htmlspecialchars($review->product_image) ?>" class="review-preview-product-image" alt="<?= htmlspecialchars($review->product_name ?? 'Product') ?>">
                    <?php endif; ?>
                    <div>
                        <h3 class="h5 fw-bold mb-1"><?= htmlspecialchars($review->product_name ?? 'Product') ?></h3>
                        <p class="text-muted small mb-0"><?= htmlspecialchars($review->shop_name ?? 'Seller') ?></p>
                    </div>
                </div>
                <div class="preview-rating-main">
                    <div class="preview-rating-label">Overall rating</div>
                    <div class="preview-stars" id="preview-stars" aria-label="Current product rating"></div>
                </div>
                <p class="review-preview-comment" id="preview-comment"><?= htmlspecialchars($review->comment ?? '') ?: 'Your comment will appear here.' ?></p>
                <div class="preview-meta">
                    <div class="preview-rating-row"><span><i class="bi bi-sun me-2"></i>Freshness</span><span class="preview-rating-pill"><span class="preview-small-stars" id="preview-freshness" aria-label="Freshness rating"></span><strong id="preview-freshness-score"></strong></span></div>
                    <div class="preview-rating-row"><span><i class="bi bi-chat-heart me-2"></i>Seller response</span><span class="preview-rating-pill"><span class="preview-small-stars" id="preview-responsiveness" aria-label="Seller responsiveness rating"></span><strong id="preview-responsiveness-score"></strong></span></div>
                    <div class="preview-rating-row"><span><i class="bi bi-box-seam me-2"></i>Product condition</span><span class="preview-rating-pill"><span class="preview-small-stars" id="preview-condition"></span><strong id="preview-condition-score"></strong></span></div>
                    <div class="preview-rating-row"><span><i class="bi bi-emoji-smile me-2"></i>Driver friendliness</span><span class="preview-rating-pill"><span class="preview-small-stars" id="preview-driver"></span><strong id="preview-driver-score"></strong></span></div>
                    <div class="preview-rating-row"><span><i class="bi bi-stopwatch me-2"></i>Delivery speed</span><span class="preview-rating-pill"><span class="preview-small-stars" id="preview-speed"></span><strong id="preview-speed-score"></strong></span></div>
                </div>
                <img id="preview-review-photo" src="<?= htmlspecialchars($review->review_photo ?? '') ?>" class="review-preview-photo <?= empty($review->review_photo) ? 'd-none' : '' ?>" alt="Review photo preview">
            </aside>
        </div>
    </div>
</div>

<script>
    function updateReviewPreview() {
        const selectedValue = name => document.querySelector(`input[name="${name}"]:checked`)?.value || 0;
        const rating = Number(selectedValue('rating'));
        const freshness = selectedValue('freshness_rating');
        const responsiveness = selectedValue('seller_responsiveness_rating');
        const condition = selectedValue('product_condition_rating');
        const driver = selectedValue('driver_friendliness_rating');
        const speed = selectedValue('delivery_speed_rating');
        const comment = document.getElementById('comment').value.trim();
        const renderStars = value => Array.from({ length: 5 }, (_, index) => `<i class="bi ${index < Number(value) ? 'bi-star-fill' : 'bi-star'}"></i>`).join('');
        document.getElementById('preview-stars').innerHTML = renderStars(rating);
        document.getElementById('preview-freshness').innerHTML = renderStars(freshness);
        document.getElementById('preview-responsiveness').innerHTML = renderStars(responsiveness);
        document.getElementById('preview-condition').innerHTML = renderStars(condition);
        document.getElementById('preview-driver').innerHTML = renderStars(driver);
        document.getElementById('preview-speed').innerHTML = renderStars(speed);
        document.getElementById('preview-freshness-score').textContent = `${freshness}/5`;
        document.getElementById('preview-responsiveness-score').textContent = `${responsiveness}/5`;
        document.getElementById('preview-condition-score').textContent = `${condition}/5`;
        document.getElementById('preview-driver-score').textContent = `${driver}/5`;
        document.getElementById('preview-speed-score').textContent = `${speed}/5`;
        document.getElementById('preview-comment').textContent = comment || 'Your comment will appear here.';
    }

    document.querySelectorAll('.review-star-picker').forEach(function(ratingGroup) {
        const inputs = ratingGroup.querySelectorAll('input[type="radio"]');
        const labels = ratingGroup.querySelectorAll('label[data-rating]');
        const paintStars = value => labels.forEach(label => label.classList.toggle('selected', Number(label.dataset.rating) <= value));

        labels.forEach(label => label.addEventListener('mouseenter', () => paintStars(Number(label.dataset.rating))));
        ratingGroup.addEventListener('mouseleave', () => paintStars(Number(ratingGroup.querySelector('input:checked')?.value || 0)));
        inputs.forEach(input => input.addEventListener('change', function() {
            paintStars(Number(this.value));
            const score = ratingGroup.parentElement.querySelector('.review-rating-score');
            if (score) score.textContent = `${this.value}/5`;
            updateReviewPreview();
        }));
    });

    document.getElementById('comment').addEventListener('input', updateReviewPreview);
    document.getElementById('review_photo').addEventListener('change', function() {
        const [image] = this.files;
        const previewPhoto = document.getElementById('preview-review-photo');
        if (!image) {
            previewPhoto.classList.add('d-none');
            return;
        }
        const reader = new FileReader();
        reader.onload = function(event) {
            previewPhoto.src = event.target.result;
            previewPhoto.classList.remove('d-none');
        };
        reader.readAsDataURL(image);
    });
    updateReviewPreview();
</script>
<?php $content = ob_get_clean();
require ANI_MART_ROOT . '/app/Views/layouts/main.php'; ?>
