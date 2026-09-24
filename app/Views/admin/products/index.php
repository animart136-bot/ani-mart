<?php
$title = 'Product Management';
$products = $products ?? [];
$pageStyles[] = '/ani-mart/public/assets/css/pages/admin-product-review.css?v=' . filemtime(__DIR__ . '/../../../../public/assets/css/pages/admin-product-review.css');
ob_start();
?>
<div class="container app-page-spacing admin-products-page">
    <?php render_component('page-hero', [
        'class' => 'admin-hero',
        'eyebrow' => 'Product Management',
        'title' => 'Product Management',
        'subtitle' => 'Review and manage all seller products, including approved marketplace listings.',
        'backHref' => app_url('dashboard'),
        'backLabel' => 'Admin dashboard',
        'stats' => [['label' => 'Products', 'value' => count($products)]],
    ]); ?>

    <div class="card border-0 shadow-sm rounded-5">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-3">
                <div>
                    <span class="section-badge">Marketplace review</span>
                    <h2 class="section-title mb-0">All products</h2>
                </div>
                <span class="badge bg-success rounded-pill px-3 py-2"><?= count($products) ?> item(s)</span>
            </div>

            <?php if (empty($products)): ?>
                <div class="alert alert-light border rounded-4">
                    <strong>No products found.</strong>
                    <div class="small text-muted mt-1">Seller products will appear here after they are submitted.</div>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Seller</th>
                                <th>Price</th>
                                <th>Unit</th>
                                <th>Reference</th>
                                <th>Assessment</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                                <?php
                                    $approvalStatus = (string) ($product->approval_status ?? 'pending');
                                    $statusLabel = \App\Services\StatusPresenter::label($approvalStatus);
                                    $statusClass = \App\Services\StatusPresenter::badgeClass($approvalStatus);
                                ?>
                                <tr>
                                    <td data-label="Product">
                                        <div class="fw-bold"><?= htmlspecialchars($product->name ?? 'Unnamed product') ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($product->category_name ?? 'Uncategorized') ?></small>
                                    </td>
                                    <td data-label="Seller">
                                        <div class="fw-semibold"><?= htmlspecialchars(trim(($product->first_name ?? '') . ' ' . ($product->last_name ?? '')) ?: 'Unknown seller') ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($product->shop_name ?? 'No shop name') ?></small>
                                    </td>
                                    <td data-label="Price">
                                        <div class="fw-bold">₱<?= number_format((float) ($product->price ?? 0), 2) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($product->stock ?? 0) ?> stock</small>
                                    </td>
                                    <td data-label="Unit">
                                        <div class="fw-semibold"><?= htmlspecialchars((string) ($product->unit ?? 'kg')) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars((string) ($product->price_type ?? 'retail')) ?></small>
                                    </td>
                                    <td data-label="Reference">
                                        <?php if (!empty($product->last_reference_price)): ?>
                                            <div class="fw-bold">₱<?= number_format((float) $product->last_reference_price, 2) ?></div>
                                            <small class="text-muted"><?= htmlspecialchars($product->last_price_deviation ?? '0') ?>% deviation</small>
                                        <?php else: ?>
                                            <span class="text-muted">Unavailable</span>
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="Assessment">
                                        <span class="badge <?= (($product->last_price_assessment ?? '') === 'NORMAL') ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning-emphasis' ?> rounded-pill px-3 py-2">
                                            <?= htmlspecialchars((string) ($product->last_price_assessment ?? 'UNKNOWN')) ?>
                                        </span>
                                    </td>
                                    <td data-label="Status">
                                        <span class="badge <?= $statusClass ?> rounded-pill px-3 py-2">
                                            <?= htmlspecialchars($statusLabel) ?>
                                        </span>
                                    </td>
                                    <td data-label="Action">
                                        <div class="d-flex flex-wrap gap-2">
                                            <a href="/ani-mart/public/admin/products/view?id=<?= (int) $product->id ?>" class="btn btn-outline-dark btn-sm rounded-pill">View</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php $content = ob_get_clean(); require ANI_MART_ROOT . '/app/Views/layouts/main.php'; ?>
