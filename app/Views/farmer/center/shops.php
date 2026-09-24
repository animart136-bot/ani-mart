<?php

$title = 'My Shops';

ob_start();

$shops = $shops ?? [];
$activeShop = $activeShop ?? null;
?>

<div class="container app-page-spacing">
    <section class="hero mb-5" aria-labelledby="myShopsTitle">
        <div class="row align-items-center">
            <div class="col-lg-7">
                <span class="hero-badge">
                    Seller Centre
                </span>
                <h1 id="myShopsTitle" class="hero-title mt-3 mb-2">My Shops</h1>
                <p class="hero-subtitle mb-0">Manage each seller profile and switch instantly without logging out.</p>
            </div>

            <div class="col-lg-5 text-lg-end mt-4 mt-lg-0">
                <a href="/ani-mart/public/seller-center/shops/create" class="btn btn-light rounded-pill px-4">
                    <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>
                    Create New Shop
                </a>
            </div>
        </div>
    </section>

    <div class="row g-4">
        <?php if (!empty($shops)): ?>
            <?php foreach ($shops as $shop): ?>
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100">
                        <div class="row g-0">
                            <div class="col-md-5">
                                <div class="shop-card-banner" style="background: url('<?= htmlspecialchars($shop->banner ?? '/ani-mart/public/assets/img/farm-placeholder.svg') ?>') center/cover no-repeat; min-height: 180px;"></div>
                            </div>
                            <div class="col-md-7">
                                <div class="card-body h-100 d-flex flex-column justify-content-between">
                                    <div>
                                        <h4 class="fw-bold mb-2"><?= htmlspecialchars($shop->shop_name) ?></h4>
                                        <p class="text-muted mb-3"><?= htmlspecialchars(substr($shop->description ?? '', 0, 90)) ?></p>
                                        <div class="d-flex flex-wrap gap-2 mb-3">
                                            <span class="badge bg-<?= $shop->status === 'approved' ? 'success' : ($shop->status === 'pending' ? 'warning text-dark' : 'secondary') ?> text-capitalize"><?= htmlspecialchars($shop->status) ?></span>
                                            <span class="badge bg-info text-dark text-capitalize"><?= htmlspecialchars($shop->verification_status ?? $shop->status) ?></span>
                                            <span class="badge bg-success">Trust <?= (int) ($shop->trust_score ?? 0) ?></span>
                                        </div>
                                        <p class="small text-muted mb-0"><?= htmlspecialchars($shop->address ?? '') ?> <?= htmlspecialchars($shop->city ?? '') ?> <?= htmlspecialchars($shop->province ?? '') ?></p>
                                    </div>
                                    <div class="d-flex gap-2 pt-3">
                                        <?php if ($shop->id === ($activeShop->id ?? 0)): ?>
                                            <span class="btn btn-outline-success disabled rounded-pill">Active Shop</span>
                                        <?php else: ?>
                                            <form method="POST" action="/ani-mart/public/seller-center/switch" class="d-inline">
                                                <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                                                <input type="hidden" name="shop_id" value="<?= (int) $shop->id ?>">
                                                <button type="submit" class="btn btn-outline-primary rounded-pill">Switch to this shop</button>
                                            </form>
                                        <?php endif; ?>
                                        <a href="/ani-mart/public/seller-center/shop/customize?shop_id=<?= (int) $shop->id ?>" class="btn btn-light rounded-pill">Edit Shop</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-4 p-5 text-center">
                    <h3 class="fw-bold">No shops yet</h3>
                    <p class="text-muted">Create your first shop and start selling directly to local customers.</p>
                    <a href="/ani-mart/public/seller-center/shops/create" class="btn btn-success rounded-pill px-4">Create First Shop</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
require ANI_MART_ROOT . '/app/Views/layouts/main.php';
