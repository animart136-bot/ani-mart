<?php
$title = 'My Products';
$products = $products ?? [];
$statusLabels = [
    'approved' => 'Approved',
    'pending' => 'Pending review',
    'pending_admin_review' => 'Pending review',
    'rejected' => 'Rejected',
    'needs_correction' => 'Needs correction',
    'superseded' => 'Replaced by new submission',
];
$shopThemes = \App\Services\SellerProfileService::themes();
$shopTheme = $shopThemes[$activeShop->shop_theme ?? 'fresh-farm'] ?? $shopThemes['fresh-farm'];
$inStock = count(array_filter($products, static fn($product) => (int) ($product->stock ?? 0) > 0));
$lowStockThreshold = \App\Services\InventoryNotificationService::LOW_STOCK_THRESHOLD;
$lowStock = count(array_filter($products, static fn($product) => (int) ($product->stock ?? 0) <= $lowStockThreshold));
$moderationSignals = $moderationSignals ?? [];
$approvedProductCount = (int) ($moderationSignals['approved_products'] ?? 0);
$completedOrderCount = (int) ($moderationSignals['completed_orders'] ?? 0);
$isEstablishedSeller = !empty($moderationSignals['established']);
ob_start();
?>
<div class="container app-page-spacing inventory-page" style="
    --inventory-accent: <?= htmlspecialchars($shopTheme['accent']) ?>;
    --inventory-secondary: <?= htmlspecialchars($shopTheme['secondary']) ?>;
    --inventory-text: <?= htmlspecialchars($shopTheme['text']) ?>;
    --inventory-background: <?= htmlspecialchars($shopTheme['background']) ?>;
">
    <section class="inventory-hero" aria-labelledby="inventoryTitle">
        <div class="inventory-hero-copy">
            <span class="inventory-eyebrow"><i class="bi bi-shop" aria-hidden="true"></i><?= htmlspecialchars($activeShop->shop_name ?? 'Your shop') ?></span>
            <h1 id="inventoryTitle">My Products</h1>
            <p>Fresh listings. A growing business.</p>
            <span class="inventory-hero-description">Manage your products, update prices, and keep your stock ready for customers.</span>
        </div>
        <a href="/ani-mart/public/farmer/products/create" class="inventory-add-btn">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>Add Product
        </a>
    </section>

    <aside class="inventory-review-note" aria-label="Product approval reminder">
            <span class="inventory-review-note-icon"><i class="bi bi-hourglass-split" aria-hidden="true"></i></span>
            <div>
                <strong>Price-based automatic approval</strong>
                <p>Products that pass Smart Price Check are approved and shown to customers automatically.</p>
                <small>An unusual price, a missing reference, or a suspended seller account still requires administrator review.</small>
            </div>
    </aside>

    <section class="inventory-summary" aria-label="Inventory summary">
        <div class="inventory-summary-item">
            <span class="inventory-summary-icon"><i class="bi bi-box-seam" aria-hidden="true"></i></span>
            <div><span>Total products</span><strong><?= count($products) ?></strong></div>
        </div>
        <div class="inventory-summary-item">
            <span class="inventory-summary-icon"><i class="bi bi-check2-circle" aria-hidden="true"></i></span>
            <div><span>In stock</span><strong><?= $inStock ?></strong></div>
        </div>
        <div class="inventory-summary-item">
            <span class="inventory-summary-icon inventory-low-icon"><i class="bi bi-exclamation-circle" aria-hidden="true"></i></span>
            <div><span>Low stock</span><strong><?= $lowStock ?></strong></div>
        </div>
    </section>

    <section class="inventory-list" aria-labelledby="inventoryListTitle">
        <div class="inventory-list-heading">
            <div><h2 id="inventoryListTitle">Product inventory</h2><p>Your shop’s current listings, all in one place.</p></div>
            <span class="inventory-count"><?= count($products) ?> products</span>
        </div>
        <div class="inventory-list-body">
            <?php if (empty($products)): ?>
                <div class="inventory-empty">
                    <i class="bi bi-basket fs-1 text-success" aria-hidden="true"></i>
                    <h2 class="h4 mt-3">No products yet</h2>
                    <p class="text-muted mb-0">Add your first product to start building your shop.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive inventory-table-wrap" tabindex="0" aria-label="Product inventory table">
                    <table class="table align-middle mb-0">
                        <caption class="visually-hidden"><?= count($products) ?> active product listings</caption>
                        <thead>
                            <tr>
                                <th scope="col">Product</th>
                                <th scope="col">Category</th>
                                <th scope="col">Price</th>
                                <th scope="col">Stock</th>
                                <th scope="col">Status</th>
                                <th scope="col" class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td>
                                    <div class="inventory-product">
                                        <span class="inventory-product-image">
                                            <?php if (!empty($product->image)): ?>
                                                <img src="<?= htmlspecialchars($product->image) ?>" alt="" loading="lazy">
                                            <?php else: ?>
                                                <i class="bi bi-image" aria-hidden="true"></i>
                                            <?php endif; ?>
                                        </span>
                                        <strong><?= htmlspecialchars($product->name ?? 'Product') ?></strong>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($product->category_name ?? 'Uncategorized') ?></td>
                                <td class="text-nowrap">₱<?= number_format((float) ($product->price ?? 0), 2) ?></td>
                                <td><span class="inventory-stock<?= (int) ($product->stock ?? 0) <= 0 ? ' is-empty' : ((int) $product->stock <= 3 ? ' is-low' : '') ?>"><?= (int) ($product->stock ?? 0) ?></span></td>
                                <td>
                                    <div class="inventory-status-wrap">
                                        <span class="inventory-status <?= ($product->approval_status ?? 'approved') === 'approved' ? 'is-approved' : 'is-review' ?>"><?= htmlspecialchars($statusLabels[$product->approval_status ?? 'approved'] ?? 'Pending review') ?></span>
                                        <?php if (($product->approval_status ?? 'approved') !== 'approved' && !empty($product->moderation_reason)): ?>
                                            <small class="inventory-status-reason"><?= htmlspecialchars($product->moderation_reason) ?></small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="inventory-actions">
                                        <a href="/ani-mart/public/farmer/products/edit?id=<?= (int) $product->id ?>"
                                            class="btn btn-sm btn-outline-success"
                                            aria-label="Edit <?= htmlspecialchars($product->name ?? 'product') ?>">Edit</a>
                                        <form method="POST" action="/ani-mart/public/farmer/products/delete"
                                            data-confirm="Delete this product from your shop?"
                                            data-confirm-title="Delete product"
                                            data-confirm-text="Delete"
                                            data-confirm-class="btn-danger">
                                            <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                                            <input type="hidden" name="id" value="<?= (int) $product->id ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger"
                                                aria-label="Delete <?= htmlspecialchars($product->name ?? 'product') ?>">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </section>
</div>
<?php
$content = ob_get_clean();
$pageCss = '/ani-mart/public/assets/css/pages/product-inventory.css?v='
    . filemtime(__DIR__ . '/../../../../public/assets/css/pages/product-inventory.css');
require __DIR__ . '/../../layouts/main.php';
