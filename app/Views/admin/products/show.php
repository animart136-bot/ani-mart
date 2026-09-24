<?php
$product = $product ?? null;
$productImages = $productImages ?? [];
$title = $product ? 'Review: ' . $product->name : 'Product review';
$pageStyles[] = '/ani-mart/public/assets/css/pages/admin-product-review.css?v=' . filemtime(__DIR__ . '/../../../../public/assets/css/pages/admin-product-review.css');
$approvalStatus = (string) ($product->approval_status ?? 'pending');
$hasFinalDecision = in_array($approvalStatus, ['approved', 'rejected'], true);
$approvalStatusLabel = [
    'approved' => 'Approved',
    'pending' => 'Pending admin review',
    'pending_admin_review' => 'Pending admin review',
    'needs_correction' => 'Needs correction',
    'rejected' => 'Rejected',
    'under_review' => 'Under review',
    'removed' => 'Removed',
    'superseded' => 'Replaced by new submission',
][$approvalStatus] ?? ucfirst(str_replace('_', ' ', $approvalStatus));
$approvalStatusClass = $approvalStatus === 'approved'
    ? 'bg-success'
    : ($approvalStatus === 'rejected'
        ? 'bg-danger'
        : ($approvalStatus === 'superseded' ? 'bg-secondary' : 'bg-warning text-dark'));

$imageUrls = [];
if ($product && !empty($product->image)) {
    $imageUrls[] = product_image_url($product->image);
}
foreach ($productImages as $image) {
    if (!empty($image->image_url)) {
        $imageUrls[] = product_image_url($image->image_url);
    }
}
$imageUrls = array_values(array_unique($imageUrls));
if (empty($imageUrls)) {
    $imageUrls[] = product_image_url(null);
}

ob_start();
?>
<div class="container app-page-spacing">
    <div class="admin-hero mb-5">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <span class="hero-badge">Smart Price Check</span>
                <h1 class="hero-title mt-4">Review product</h1>
                <p class="hero-subtitle mt-3">Check the submitted listing price against the reference database and decide whether it should be approved, corrected, or rejected.</p>
            </div>
        </div>
    </div>

    <?php if (!$product): ?>
        <div class="alert alert-warning rounded-4">Product not found.</div>
    <?php else: ?>
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-5">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
                            <div>
                                <span class="section-badge">Product listing</span>
                                <h2 class="section-title mb-1"><?= htmlspecialchars($product->name ?? 'Unnamed product') ?></h2>
                                <small class="text-muted"><?= htmlspecialchars($product->category_name ?? 'Uncategorized') ?></small>
                            </div>
                            <span class="badge <?= $approvalStatusClass ?> rounded-pill px-3 py-2 text-uppercase">
                                <?= htmlspecialchars($approvalStatusLabel) ?>
                            </span>
                        </div>

                        <section class="admin-product-media mb-4" aria-labelledby="productImagesTitle">
                            <div class="admin-product-media-heading">
                                <div>
                                    <h3 id="productImagesTitle">Product images</h3>
                                    <p>Inspect the listing photos submitted by the seller.</p>
                                </div>
                                <span><?= count($imageUrls) ?> image<?= count($imageUrls) === 1 ? '' : 's' ?></span>
                            </div>

                            <div class="admin-product-main-image">
                                <img
                                    id="adminProductMainImage"
                                    src="<?= htmlspecialchars($imageUrls[0]) ?>"
                                    alt="<?= htmlspecialchars($product->name ?? 'Product') ?> listing image"
                                    data-image-modal="true">
                            </div>

                            <?php if (count($imageUrls) > 1): ?>
                                <div class="admin-product-thumbnails" aria-label="Choose a product image to inspect">
                                    <?php foreach ($imageUrls as $index => $imageUrl): ?>
                                        <button
                                            type="button"
                                            class="admin-product-thumbnail<?= $index === 0 ? ' is-active' : '' ?>"
                                            data-image-url="<?= htmlspecialchars($imageUrl) ?>"
                                            aria-label="View product image <?= $index + 1 ?>"
                                            aria-pressed="<?= $index === 0 ? 'true' : 'false' ?>">
                                            <img src="<?= htmlspecialchars($imageUrl) ?>" alt="" loading="lazy">
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($product->harvest_photo)): ?>
                                <div class="admin-harvest-photo">
                                    <div>
                                        <strong>Harvest verification photo</strong>
                                        <small>Submitted separately as farm-to-table evidence.</small>
                                    </div>
                                    <img src="<?= htmlspecialchars(product_image_url($product->harvest_photo)) ?>" alt="Harvest verification for <?= htmlspecialchars($product->name ?? 'product') ?>" loading="lazy" data-image-modal="true">
                                </div>
                            <?php endif; ?>
                        </section>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <div class="bg-light rounded-4 p-3 h-100">
                                    <div class="text-muted small">Seller</div>
                                    <div class="fw-bold mt-1"><?= htmlspecialchars(trim(($product->first_name ?? '') . ' ' . ($product->last_name ?? '')) ?: 'Unknown seller') ?></div>
                                    <div class="small text-muted"><?= htmlspecialchars($product->shop_name ?? 'No shop name') ?></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="bg-light rounded-4 p-3 h-100">
                                    <div class="text-muted small">Seller email</div>
                                    <div class="fw-bold mt-1"><?= htmlspecialchars($product->email ?? 'Not available') ?></div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <h5 class="fw-bold mb-3">Description</h5>
                            <p class="text-muted mb-0"><?= nl2br(htmlspecialchars($product->description ?? 'No description provided.')) ?></p>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <div class="bg-light rounded-4 p-3">
                                    <div class="small text-muted">Submitted price</div>
                                    <div class="fw-bold fs-5 mt-1">₱<?= number_format((float) ($product->price ?? 0), 2) ?></div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="bg-light rounded-4 p-3">
                                    <div class="small text-muted">Reference price</div>
                                    <div class="fw-bold fs-5 mt-1"><?= !empty($product->last_reference_price) ? '₱' . number_format((float) $product->last_reference_price, 2) : 'Unavailable' ?></div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="bg-light rounded-4 p-3">
                                    <div class="small text-muted">Deviation</div>
                                    <div class="fw-bold fs-5 mt-1"><?= $product->last_price_deviation !== null ? number_format((float) $product->last_price_deviation, 2) . '%' : 'N/A' ?></div>
                                </div>
                            </div>
                        </div>

                        <?php if (!empty($product->last_price_check_id)): ?>
                            <div class="small text-muted mb-4">
                                Reference source: <?= htmlspecialchars($product->reference_source ?? 'Available market reference data') ?>
                                <?php if (!empty($product->reference_price_date)): ?> | Date: <?= htmlspecialchars($product->reference_price_date) ?><?php endif; ?>
                                <?php if (!empty($product->reference_region)): ?> | Region: <?= htmlspecialchars($product->reference_region) ?><?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <div class="mb-4">
                            <h5 class="fw-bold mb-3">Smart Price Check result</h5>
                            <div class="alert <?= (($product->last_price_assessment ?? 'UNKNOWN') === 'NORMAL') ? 'alert-success' : 'alert-warning' ?> rounded-4 mb-0">
                                <div class="fw-bold mb-1"><?= htmlspecialchars((string) ($product->last_price_assessment ?? 'UNKNOWN')) ?></div>
                                <div class="small">Reference result from the latest submitted product price check.</div>
                            </div>
                        </div>

                        <?php if (!empty($product->admin_note)): ?>
                            <div class="alert alert-secondary rounded-4">
                                <div class="fw-bold mb-1">Latest admin note</div>
                                <div class="mb-0"><?= nl2br(htmlspecialchars($product->admin_note)) ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-5">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-3">Decision</h5>

                        <?php if (($product->approval_status ?? '') === 'superseded'): ?>
                            <div class="alert alert-secondary rounded-4 mb-3">
                                <div class="d-flex align-items-center gap-2 fw-bold mb-2">
                                    <i class="bi bi-check2-all" aria-hidden="true"></i>
                                    Previous submission closed
                                </div>
                                <p class="small mb-0">A newer version of this product was approved, so this older pending submission no longer needs review.</p>
                            </div>
                            <a href="/ani-mart/public/admin/products" class="btn btn-outline-dark w-100 rounded-pill">Back to products</a>
                        <?php elseif ($hasFinalDecision): ?>
                            <div class="alert <?= $approvalStatus === 'approved' ? 'alert-success' : 'alert-danger' ?> rounded-4 mb-3">
                                <div class="d-flex align-items-center gap-2 fw-bold mb-2">
                                    <i class="bi <?= $approvalStatus === 'approved' ? 'bi-check-circle-fill' : 'bi-x-circle-fill' ?>" aria-hidden="true"></i>
                                    Review completed
                                </div>
                                <p class="small mb-0">This product has already been <?= htmlspecialchars($approvalStatusLabel) ?>. Decision controls are now disabled.</p>
                            </div>

                            <div class="d-grid gap-3" aria-label="Completed product decision">
                                <button type="button" class="btn btn-success w-100 rounded-pill" disabled>Approve</button>
                                <button type="button" class="btn btn-warning w-100 rounded-pill text-dark" disabled>Request correction</button>
                                <button type="button" class="btn btn-danger w-100 rounded-pill" disabled>Reject</button>
                            </div>

                            <a href="/ani-mart/public/admin/products" class="btn btn-outline-dark w-100 rounded-pill mt-3">Back to products</a>
                        <?php else: ?>
                        <form method="POST" action="/ani-mart/public/admin/products/approve" class="mb-3">
                            <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                            <input type="hidden" name="id" value="<?= (int) $product->id ?>">
                            <button type="submit" class="btn btn-success w-100 rounded-pill">Approve</button>
                        </form>

                        <form method="POST" action="/ani-mart/public/admin/products/request-correction" class="mb-3">
                            <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                            <input type="hidden" name="id" value="<?= (int) $product->id ?>">
                            <label class="form-label small text-muted">Correction note</label>
                            <textarea name="note" rows="3" class="form-control rounded-4 mb-2" placeholder="Explain what needs to be fixed."></textarea>
                            <button type="submit" class="btn btn-warning w-100 rounded-pill text-dark">Request correction</button>
                        </form>

                        <form method="POST" action="/ani-mart/public/admin/products/reject">
                            <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                            <input type="hidden" name="id" value="<?= (int) $product->id ?>">
                            <label class="form-label small text-muted">Rejection note</label>
                            <textarea name="note" rows="3" class="form-control rounded-4 mb-2" placeholder="Why was this rejected?"></textarea>
                            <button type="submit" class="btn btn-danger w-100 rounded-pill">Reject</button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
<script>
document.querySelectorAll('.admin-product-thumbnail').forEach(button => {
    button.addEventListener('click', () => {
        const mainImage = document.getElementById('adminProductMainImage');
        if (mainImage) mainImage.src = button.dataset.imageUrl;

        document.querySelectorAll('.admin-product-thumbnail').forEach(thumbnail => {
            const isActive = thumbnail === button;
            thumbnail.classList.toggle('is-active', isActive);
            thumbnail.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });
    });
});
</script>
<?php $content = ob_get_clean(); require ANI_MART_ROOT . '/app/Views/layouts/main.php'; ?>
