<?php

use App\Core\Session;

$activeShop = $activeShop ?? null;
$userShops = $userShops ?? [];
?>

<?php if (($role ?? '') === 'farmer'): ?>
    <li class="nav-item dropdown shop-switcher-item">
        <a
            class="nav-link nav-icon-link d-flex align-items-center"
            href="#"
            role="button"
            data-bs-toggle="dropdown"
            aria-expanded="false"
            title="Switch Shop"
        >
            <i class="bi bi-shop fs-4"></i>
            <span class="ms-2 d-none d-lg-inline"><?= htmlspecialchars($activeShop->shop_name ?? 'My Shop') ?></span>
            <i class="bi bi-caret-down-fill ms-2 shop-switcher-caret"></i>
        </a>

        <ul class="dropdown-menu dropdown-menu-end border-0 shadow-sm shop-switcher-dropdown">
            <li class="dropdown-header">
                <strong>Current Shop</strong>
            </li>

            <?php if (!empty($userShops)): ?>
                <?php foreach ($userShops as $shop): ?>
                    <li>
                        <form method="POST" action="/ani-mart/public/seller-center/switch" class="m-0">
                            <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                            <button
                                type="submit"
                                name="shop_id"
                                value="<?= (int) $shop->id ?>"
                                class="dropdown-item"
                            >
                                <?= htmlspecialchars($shop->shop_name) ?>
                                <?php if ($shop->id === ($activeShop->id ?? 0)): ?>
                                    <span class="badge bg-success ms-2">Active</span>
                                <?php endif; ?>
                            </button>
                        </form>
                    </li>
                <?php endforeach; ?>
            <?php else: ?>
                <li class="px-3 py-2 text-muted small">No shops found. Create one to start selling.</li>
            <?php endif; ?>

            <li><hr class="dropdown-divider"></li>

            <li>
                <a class="dropdown-item" href="/ani-mart/public/seller-center/shops/create">
                    <i class="bi bi-plus-lg me-2"></i>
                    Create New Shop
                </a>
            </li>
        </ul>
    </li>

<?php endif; ?>
