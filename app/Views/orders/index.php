<?php

$role = $role ?? 'customer';
$title = ucfirst($role) . ' Orders';

use App\Services\BadgeCalculationService;

$orderSellerOptions = [];
$orderProductOptions = [];
foreach (($orders ?? []) as $filterOrder) {
    foreach (($filterOrder->items ?? []) as $filterItem) {
        $shop = trim((string) ($filterItem->shop_name ?? ''));
        $product = trim((string) ($filterItem->name ?? ''));
        if ($shop !== '') $orderSellerOptions[$shop] = true;
        if ($product !== '') $orderProductOptions[$product] = true;
    }
}
ksort($orderSellerOptions, SORT_NATURAL | SORT_FLAG_CASE);
ksort($orderProductOptions, SORT_NATURAL | SORT_FLAG_CASE);

$renderOrderTracking = static function (string $status, string $title = 'Order Tracking'): void {
    $status = strtolower($status);
    $steps = [
        'pending' => ['Order Placed', 'bi-cart-check'],
        'accepted' => ['Accepted', 'bi-check-circle'],
        'preparing' => ['Preparing', 'bi-basket'],
        'out_for_delivery' => ['Out for Delivery', 'bi-truck'],
        'delivered' => ['Delivered', 'bi-box-seam'],
        'completed' => ['Completed', 'bi-check2-all'],
    ];
    $statusRank = array_search($status, array_keys($steps), true);
    ?>
    <div class="farmer-order-tracking <?= $status === 'cancelled' ? 'tracking-cancelled' : '' ?>">
        <div class="tracking-header d-flex align-items-center justify-content-between gap-3">
            <h6 class="fw-bold mb-0"><?= htmlspecialchars($title) ?></h6>
            <?php if ($status === 'cancelled'): ?>
                <span class="badge text-bg-danger rounded-pill">Cancelled by shop</span>
            <?php endif; ?>
        </div>

        <?php if ($status !== 'cancelled'): ?>
            <div class="tracking-timeline">
                <?php foreach ($steps as $stepStatus => [$label, $icon]): ?>
                    <?php
                    $stepRank = array_search($stepStatus, array_keys($steps), true);
                    $classes = [];
                    if ($statusRank !== false && $stepRank <= $statusRank) $classes[] = 'completed';
                    if ($stepStatus === $status) $classes[] = 'active';
                    ?>
                    <div class="tracking-step <?= implode(' ', $classes) ?>">
                        <div class="tracking-icon"><i class="bi <?= $icon ?>"></i></div>
                        <div class="tracking-label"><?= htmlspecialchars($label) ?></div>
                    </div>
                    <?php if ($stepStatus !== 'completed'): ?>
                        <div class="tracking-line"></div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="small text-muted mb-0">These items will not be delivered and cannot be reviewed.</p>
        <?php endif; ?>
    </div>
    <?php
};

ob_start();

?>

<div class="orders-page container app-page-spacing">

    <!-- HERO -->
    <div class="dashboard-hero mb-4 mb-lg-5">
        <div class="col-lg-7">

            <span class="hero-badge">
                <?php if ($role === 'admin'): ?>
                    Ani-Mart Order Management
                <?php elseif ($role === 'farmer'): ?>
                    Order Management
                <?php else: ?>
                    My Purchases
                <?php endif; ?>
            </span>

            <h1 class="hero-title mt-3">
                <?php if ($role === 'admin'): ?>
                    Marketplace Orders
                <?php elseif ($role === 'farmer'): ?>
                    Customer Orders
                <?php else: ?>
                    My Orders
                <?php endif; ?>
            </h1>

            <p class="hero-subtitle">
                <?php if ($role === 'admin'): ?>
                    Monitor and manage all marketplace transactions.
                <?php elseif ($role === 'farmer'): ?>
                    Accept, prepare, and deliver customer orders. Track your farm's sales in real-time.
                <?php else: ?>
                    View your purchases and order history.
                <?php endif; ?>
            </p>

        </div>
    </div>

    <!-- ORDER STATS -->

<?php if (!empty($orders)): ?>

    <div class="order-stats-row row g-3 g-lg-4 mb-4 mb-lg-5">

        <!-- PENDING -->
        <div class="col-6 col-lg-3">

            <div class="order-stat-card pending-card">

                <div class="stat-icon">
                    <i class="bi bi-clock-history"></i>
                </div>

                <div>
                    <small class="text-white d-block">
                        Pending Orders
                    </small>

                    <h3 class="fw-bold mb-0">
                        <?= count(
                            array_filter(
                                $orders,
                                fn($o) => ($o->status ?? '') === 'pending'
                            )
                        ) ?>
                    </h3>
                </div>

            </div>

        </div>


        <!-- PREPARING -->
        <div class="col-6 col-lg-3">

            <div class="order-stat-card preparing-card">

                <div class="stat-icon">
                    <i class="bi bi-basket"></i>
                </div>

                <div>
                    <small class="text-white d-block">
                        Preparing
                    </small>

                    <h3 class="fw-bold mb-0">
                        <?= count(
                            array_filter(
                                $orders,
                                fn($o) => ($o->status ?? '') === 'preparing'
                            )
                        ) ?>
                    </h3>
                </div>

            </div>

        </div>


        <!-- OUT FOR DELIVERY -->
        <div class="col-6 col-lg-3">

            <div class="order-stat-card delivery-card">

                <div class="stat-icon">
                    <i class="bi bi-truck"></i>
                </div>

                <div>
                    <small class="text-white d-block">
                        Out for Delivery
                    </small>

                    <h3 class="fw-bold mb-0">
                        <?= count(
                            array_filter(
                                $orders,
                                fn($o) => ($o->status ?? '') === 'out_for_delivery'
                            )
                        ) ?>
                    </h3>
                </div>

            </div>

        </div>


        <!-- COMPLETED -->
        <div class="col-6 col-lg-3">

            <div class="order-stat-card completed-card">

                <div class="stat-icon">
                    <i class="bi bi-check-circle"></i>
                </div>

                <div>
                    <small class="text-white d-block">
                        Completed
                    </small>

                    <h3 class="fw-bold mb-0">
                        <?= count(
                            array_filter(
                                $orders,
                                fn($o) => in_array(
                                    ($o->status ?? ''),
                                    ['delivered', 'completed'],
                                    true
                                )
                            )
                        ) ?>
                    </h3>
                </div>

            </div>

        </div>

    </div>

<?php endif; ?>

    <?php if (!empty($orders)): ?>

        <!-- FILTERS -->
        <div class="orders-filter-panel mb-4">

            <div class="row g-3 align-items-end">

                <!-- SEARCH -->
                <div class="col-lg-4">
                    <label class="form-label fw-semibold">
                        Search orders
                    </label>

                    <div class="order-search-box">
                        <i class="bi bi-search"></i>

                        <input
                            type="text"
                            id="orderSearchInput"
                            class="form-control"
                            placeholder="Search order number, product, shop, or address">
                    </div>
                </div>

                <div class="col-lg-2 col-md-4">
                    <label class="form-label fw-semibold">Seller</label>
                    <select id="orderSellerFilter" class="form-select modern-input">
                        <option value="">All Sellers</option>
                        <?php foreach (array_keys($orderSellerOptions) as $sellerOption): ?>
                            <option value="<?= htmlspecialchars(strtolower($sellerOption)) ?>"><?= htmlspecialchars($sellerOption) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- STATUS -->
                <div class="col-lg-2 col-md-4 status-filter-col">
                    <label class="form-label fw-semibold">
                        Status
                    </label>

                    <select
                        id="orderStatusFilter"
                        class="form-select modern-input">

                        <option value="">
                            All Status
                        </option>

                        <option value="pending">
                            Pending
                        </option>

                        <option value="accepted">
                            Accepted
                        </option>

                        <option value="preparing">
                            Preparing
                        </option>

                        <option value="out_for_delivery">
                            Out for Delivery
                        </option>

                        <option value="delivered">
                            Delivered
                        </option>

                        <option value="completed">
                            Completed
                        </option>

                        <option value="cancelled">
                            Cancelled
                        </option>

                    </select>
                </div>

                <!-- FROM -->
                <div class="col-lg-2 col-md-4 date-filter-col">
                    <label class="form-label fw-semibold">
                        From
                    </label>

                    <input
                        type="date"
                        id="orderDateFrom"
                        class="form-control modern-input">
                </div>

                <!-- TO -->
                <div class="col-lg-2 col-md-4 date-filter-col">
                    <label class="form-label fw-semibold">
                        To
                    </label>

                    <input
                        type="date"
                        id="orderDateTo"
                        class="form-control modern-input">
                </div>

                <!-- CLEAR -->
                <div class="col-lg-1">
                    <button
                        type="button"
                        id="clearOrderFilters"
                        class="btn btn-light border w-100 order-filter-clear"
                        title="Clear filters">

                        <i class="bi bi-x-lg"></i>

                    </button>
                </div>

                <?php if ($role === 'customer'): ?>
                    <div class="col-lg-3 d-flex gap-2">
                        <a id="orderExportCsv" href="/ani-mart/public/customer/orders/export/csv" class="btn btn-outline-success flex-fill">
                            <i class="bi bi-filetype-csv me-1"></i>CSV
                        </a>
                        <a id="orderExportPdf" href="/ani-mart/public/customer/orders/export/pdf" class="btn btn-outline-danger flex-fill">
                            <i class="bi bi-file-earmark-pdf me-1"></i>PDF
                        </a>
                    </div>
                <?php endif; ?>

            </div>
        </div>

        <!-- FILTER EMPTY STATE -->
        <div
            id="ordersFilteredEmpty"
            class="empty-state orders-filter-empty d-none">

            <i class="bi bi-search"></i>

            <h4>
                No matching orders
            </h4>

            <p class="text-muted">
                Try another search term, status, or date range.
            </p>

        </div>

        <!-- ORDERS -->
        <?php foreach ($orders as $order): ?>

            <?php

            $displayShippingAddress = (string) sensitive_value(
                $order,
                'shipping_address',
                'orders.shipping_address',
                'No address provided'
            );
            $displayCustomerNote = (string) sensitive_value(
                $order,
                'customer_note',
                'orders.customer_note',
                ''
            );

            $orderSearchParts = [
                $order->order_number ?? ('#' . $order->id),
                $displayShippingAddress,
                $order->status ?? ''
            ];

            $productSubtotal = 0.0;
            $primaryShopName = '';
            $orderProductFilterNames = [];
            $customerShopGroups = [];

            foreach ($order->items ?? [] as $item) {
                $orderSearchParts[] = $item->name ?? '';
                $orderSearchParts[] = $item->shop_name ?? '';
                if (strtolower((string) ($item->seller_order_status ?? 'pending')) !== 'cancelled') {
                    $productSubtotal += (float) ($item->price ?? 0) * (int) ($item->quantity ?? 0);
                }
                if ($primaryShopName === '' && !empty($item->shop_name)) {
                    $primaryShopName = (string) $item->shop_name;
                }
                if (!empty($item->name)) {
                    $orderProductFilterNames[] = strtolower((string) $item->name);
                }

                $shopKey = (int) ($item->seller_profile_id ?? 0);
                if (!isset($customerShopGroups[$shopKey])) {
                    $customerShopGroups[$shopKey] = [
                        'name' => (string) ($item->shop_name ?? 'Shop'),
                        'status' => strtolower((string) ($item->seller_order_status ?? 'pending')),
                        'subtotal' => 0.0,
                        'items' => 0,
                    ];
                }
                $customerShopGroups[$shopKey]['items']++;
                if ($customerShopGroups[$shopKey]['status'] !== 'cancelled') {
                    $customerShopGroups[$shopKey]['subtotal'] += (float) ($item->price ?? 0) * (int) ($item->quantity ?? 0);
                }
            }

            $shopCount = count($customerShopGroups);

            $orderSearchText = strtolower(
                implode(' ', $orderSearchParts)
            );

            ?>

            <div
                id="order-card-<?= (int) $order->id ?>"
                class="order-card order-card-filterable"
                data-order-id="<?= (int) $order->id ?>"
                data-order-search="<?= htmlspecialchars($orderSearchText) ?>"
                data-order-status="<?= htmlspecialchars(strtolower($order->status ?? '')) ?>"
                data-order-seller="<?= htmlspecialchars(strtolower($primaryShopName)) ?>"
                data-order-products="<?= htmlspecialchars('|' . implode('|', $orderProductFilterNames) . '|') ?>"
                data-order-date="<?= htmlspecialchars(
                    date(
                        'Y-m-d',
                        strtotime($order->created_at ?? 'now')
                    )
                ) ?>">

                <!-- ORDER HEADER -->
                <div class="order-header">

                    <div class="row align-items-center">

                        <div class="col-lg-6">

                            <div class="d-flex flex-column align-items-start gap-2">

                                <div class="order-number-badge">

                                    <?= htmlspecialchars(
                                        $order->order_number ?? ('#' . $order->id)
                                    ) ?>

                                </div>

                                <div>

                                    <?php if ($role === 'admin' || $role === 'farmer'): ?>

                                        <h5 class="fw-bold mb-1">

                                            <?= htmlspecialchars(
                                                trim(
                                                    ($order->first_name ?? '')
                                                    . ' ' .
                                                    ($order->last_name ?? '')
                                                )
                                            ) ?>

                                        </h5>

                                    <?php else: ?>

                                        <h5 class="fw-bold mb-1">
                                            <?= $shopCount > 1
                                                ? 'Order from ' . $shopCount . ' shops'
                                                : ($primaryShopName !== ''
                                                    ? 'Order from ' . htmlspecialchars($primaryShopName)
                                                    : 'Order Details') ?>
                                        </h5>

                                    <?php endif; ?>

                                    <small class="text-muted">

                                        <?= date('F d, Y - g:i A', strtotime($order->created_at ?? 'now')) ?>

                                    </small>

                                </div>

                            </div>

                        </div>

                        <div class="col-lg-6 text-lg-end">

                            <span class="order-status-badge order-status-<?= htmlspecialchars($order->status ?? '') ?>">

                                <?= $role === 'customer' && $shopCount > 1 ? 'Overall: ' : '' ?><?= ucwords(str_replace('_', ' ', $order->status ?? '')) ?>

                            </span>

                            <div class="order-total-amount">

                                ₱<?= number_format($order->total_amount, 2) ?>

                            </div>

                        </div>

                    </div>

                </div>

                <!-- DELIVERY ADDRESS -->
                <div class="farmer-order-address">

                    <div class="farmer-order-address-row">
                        <i class="bi bi-geo-alt"></i>
                        <span class="farmer-order-address-text">
                            <?= htmlspecialchars($displayShippingAddress) ?>
                        </span>
                    </div>

                    <?php if (!empty($order->requested_delivery_date)): ?>
                        <div class="small mt-2">
                            <i class="bi bi-calendar3 me-1"></i>
                            Preferred: <?= htmlspecialchars(date('M j, Y', strtotime($order->requested_delivery_date))) ?>
                            · <?= htmlspecialchars(ucfirst((string) ($order->delivery_slot ?? ''))) ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($displayCustomerNote !== ''): ?>
                        <div class="small text-muted mt-1">
                            Note: <?= htmlspecialchars($displayCustomerNote) ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($role === 'admin'): ?>

                        <?php if (
                            !empty($order->delivery_barangay)
                            ||
                            !empty($order->delivery_distance)
                            ||
                            !empty($order->delivery_fee)
                        ): ?>

                            <div class="small text-muted mt-2">

                                <strong>
                                    Barangay:
                                </strong>

                                <?= htmlspecialchars(
                                    $order->delivery_barangay ?? ''
                                ) ?>

                                <?php if (!empty($order->delivery_distance)): ?>

                                    <span class="mx-2">•</span>

                                    <strong>
                                        Distance:
                                    </strong>

                                    <?= number_format(
                                        (float) $order->delivery_distance,
                                        1
                                    ) ?>
                                    km

                                <?php endif; ?>

                                <?php if (!empty($order->delivery_fee)): ?>

                                    <span class="mx-2">•</span>

                                    <strong>
                                        Fee:
                                    </strong>

                                    <?= (
                                        (float) $order->delivery_fee === 0.0
                                    )
                                        ? 'FREE'
                                        : '₱' . number_format(
                                            (float) $order->delivery_fee,
                                            2
                                        )
                                    ?>

                                <?php endif; ?>

                            </div>

                        <?php endif; ?>

                    <?php elseif ((float) ($order->delivery_distance ?? 0) > 0): ?>

                        <div class="text-muted small mt-2">
                            <i class="bi bi-map me-1"></i>
                            Distance: <?= number_format((float) $order->delivery_distance, 1) ?> km
                            <?php if (!empty($order->delivery_barangay)): ?>
                                • Barangay: <?= htmlspecialchars($order->delivery_barangay) ?>
                            <?php endif; ?>
                        </div>

                    <?php endif; ?>

                    <?php if (
                        $role === 'admin'
                        && !empty($order->delivery_latitude)
                        && !empty($order->delivery_longitude)
                    ): ?>
                        <div class="mt-2">
                            <a href="https://www.google.com/maps/dir/?api=1&destination=<?= (float) $order->delivery_latitude ?>,<?= (float) $order->delivery_longitude ?>"
                                target="_blank" rel="noopener noreferrer" class="btn btn-outline-success btn-sm rounded-pill">
                                <i class="bi bi-box-arrow-up-right me-1"></i>
                                Delivery Map
                            </a>
                        </div>
                    <?php endif; ?>

                </div>

                <!-- Seller and admin see the status for their current order scope. -->
                <?php if ($role !== 'customer'): ?>
                    <?php $renderOrderTracking((string) ($order->status ?? 'pending')); ?>
                <?php endif; ?>

                <?php if (!empty($order->cancellation_request_id)): ?>
                    <?php
                    $cancellationStatus = (string) ($order->cancellation_request_status ?? 'pending');
                    $cancellationAlertClass = $cancellationStatus === 'accepted'
                        ? 'success'
                        : ($cancellationStatus === 'declined' ? 'secondary' : 'warning');
                    ?>
                    <div class="alert alert-<?= $cancellationAlertClass ?> mx-3 mt-3 mb-0" role="status">
                        <div class="fw-semibold">
                            <i class="bi bi-info-circle me-1"></i>
                            Cancellation request <?= htmlspecialchars($cancellationStatus) ?>
                        </div>
                        <div class="small mt-1">
                            Customer reason: <?= htmlspecialchars((string) sensitive_value($order, 'cancellation_request_reason', 'order_cancellation_requests.request_reason', '')) ?>
                        </div>
                        <?php if (!empty($order->cancellation_response_reason)): ?>
                            <div class="small mt-1">
                                Seller response: <?= htmlspecialchars((string) sensitive_value($order, 'cancellation_response_reason', 'order_cancellation_requests.response_reason', '')) ?>
                            </div>
                        <?php elseif ($cancellationStatus === 'pending'): ?>
                            <div class="small mt-1">Waiting for the seller's decision.</div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- ORDER ITEMS -->
                <div class="farmer-order-items">

                    <?php if (!empty($order->items)): ?>

                        <?php
                        $openCustomerShop = false;
                        $lastCustomerShopKey = null;
                        $customerShopPosition = 0;
                        ?>

                        <?php foreach ($order->items as $item): ?>

                            <?php if ($role === 'customer'): ?>
                                <?php
                                $currentShopKey = (int) ($item->seller_profile_id ?? 0);
                                if ($currentShopKey !== $lastCustomerShopKey):
                                    if ($openCustomerShop):
                                ?>
                                        </div>
                                    </section>
                                <?php
                                    endif;
                                    $openCustomerShop = true;
                                    $lastCustomerShopKey = $currentShopKey;
                                    $customerShopPosition++;
                                    $shopGroup = $customerShopGroups[$currentShopKey];
                                    $shopStatus = $shopGroup['status'];
                                    $shopStatusClass = match ($shopStatus) {
                                        'cancelled' => 'text-bg-danger',
                                        'completed', 'delivered' => 'text-bg-success',
                                        'out_for_delivery' => 'text-bg-info',
                                        'preparing' => 'text-bg-warning',
                                        'accepted' => 'text-bg-primary',
                                        default => 'text-bg-secondary',
                                    };
                                ?>
                                    <section class="customer-shop-section" aria-labelledby="shop-heading-<?= (int) $order->id ?>-<?= $currentShopKey ?>">
                                        <div class="customer-shop-header">
                                            <div>
                                                <span class="customer-shop-count">Shop <?= $customerShopPosition ?> of <?= $shopCount ?></span>
                                                <h5 id="shop-heading-<?= (int) $order->id ?>-<?= $currentShopKey ?>" class="mb-1">
                                                    <i class="bi bi-shop me-2"></i><?= htmlspecialchars($shopGroup['name']) ?>
                                                </h5>
                                                <span class="small text-muted"><?= (int) $shopGroup['items'] ?> item<?= (int) $shopGroup['items'] === 1 ? '' : 's' ?> · ₱<?= number_format((float) $shopGroup['subtotal'], 2) ?></span>
                                            </div>
                                            <span class="badge <?= $shopStatusClass ?> rounded-pill customer-shop-status">
                                                <?= htmlspecialchars(ucwords(str_replace('_', ' ', $shopStatus))) ?>
                                            </span>
                                        </div>

                                        <?php $renderOrderTracking($shopStatus, 'Tracking for ' . $shopGroup['name']); ?>

                                        <?php if (in_array($shopStatus, ['out_for_delivery', 'delivered'], true)): ?>
                                            <div class="customer-shop-confirm">
                                                <div>
                                                    <strong>Did this shop's delivery arrive?</strong>
                                                    <span>Confirm only the items from <?= htmlspecialchars($shopGroup['name']) ?>.</span>
                                                </div>
                                                <form action="/ani-mart/public/customer/orders/confirm" method="POST">
                                                    <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                                                    <input type="hidden" name="id" value="<?= (int) $order->id ?>">
                                                    <input type="hidden" name="seller_profile_id" value="<?= $currentShopKey ?>">
                                                    <button type="submit" class="btn btn-primary farmer-action-btn">
                                                        <i class="bi bi-check-circle me-1"></i>Confirm this delivery
                                                    </button>
                                                </form>
                                            </div>
                                        <?php elseif ($shopStatus === 'completed'): ?>
                                            <div class="customer-shop-confirm customer-shop-confirmed">
                                                <i class="bi bi-check-circle-fill">                                                 <span>Delivery confirmed. Reviews are open for this shop's products.</span>
 </i>
                                            </div>
                                        <?php endif; ?>

                                        <div class="customer-shop-products">
                                <?php endif; ?>
                            <?php endif; ?>

                            <div class="farmer-order-item">

                                <img src="<?= htmlspecialchars($item->image ?? '/ani-mart/public/assets/img/product-placeholder.svg') ?>"
                                    class="farmer-item-image"
                                    alt="<?= htmlspecialchars($item->name ?? 'Product') ?>">

                                <div class="flex-grow-1">

                                    <h6 class="mb-1">
                                        <?= htmlspecialchars($item->name) ?>
                                    </h6>

                                    <small class="text-muted">
                                        Qty: <?= $item->quantity ?>
                                    </small>

                                    <?php if (!empty($item->shop_name)): ?>

                                        <small class="text-muted d-block">

                                            Shop:
                                            <?= htmlspecialchars($item->shop_name) ?>

                                            <?php if ($role === 'customer' && !empty($item->seller_order_status)): ?>
                                                <?php
                                                $shopItemStatus = strtolower((string) $item->seller_order_status);
                                                $shopItemStatusClass = match ($shopItemStatus) {
                                                    'cancelled' => 'text-bg-danger',
                                                    'completed', 'delivered' => 'text-bg-success',
                                                    'out_for_delivery' => 'text-bg-info',
                                                    'preparing' => 'text-bg-warning',
                                                    'accepted' => 'text-bg-primary',
                                                    default => 'text-bg-secondary',
                                                };
                                                ?>
                                                <span class="badge <?= $shopItemStatusClass ?> rounded-pill ms-2">
                                                    <?= htmlspecialchars(ucwords(str_replace('_', ' ', $shopItemStatus))) ?>
                                                </span>
                                            <?php endif; ?>

                                            <?php if (!empty($item->verification_badge)): ?>

                                                <span
                                                    class="badge <?= BadgeCalculationService::getBadgeColor($item->verification_badge) ?> rounded-pill ms-2"
                                                    title="<?= htmlspecialchars(
                                                        BadgeCalculationService::getBadgeLabel(
                                                            $item->verification_badge
                                                        )
                                                    ) ?>"
                                                    aria-label="<?= htmlspecialchars(
                                                        BadgeCalculationService::getBadgeLabel(
                                                            $item->verification_badge
                                                        )
                                                    ) ?>">

                                                    <i class="bi <?= BadgeCalculationService::getBadgeIcon($item->verification_badge) ?>"></i>

                                                </span>

                                            <?php endif; ?>

                                        </small>

                                    <?php endif; ?>

                                </div>

                                <div class="fw-bold text-success">

                                    ₱<?= number_format(
                                        $item->price * $item->quantity,
                                        2
                                    ) ?>

                                </div>

                            </div>

                            <!-- REVIEW (customer only, completed orders) -->
                            <?php if (
                                $role === 'customer'
                                &&
                                strtolower((string) ($item->seller_order_status ?? 'pending')) === 'completed'
                            ): ?>

                                <?php if (!empty($item->review_id)): ?>

                                    <?php
                                        $reviewCategoryScores = array_values(array_filter([
                                            (int) ($item->freshness_rating ?? 0),
                                            (int) ($item->seller_responsiveness_rating ?? 0),
                                            (int) ($item->product_condition_rating ?? 0),
                                            (int) ($item->driver_friendliness_rating ?? 0),
                                            (int) ($item->delivery_speed_rating ?? 0),
                                        ], static fn ($score) => $score > 0));
                                        $overallAverageRating = !empty($reviewCategoryScores)
                                            ? array_sum($reviewCategoryScores) / count($reviewCategoryScores)
                                            : (float) ($item->review_rating ?? 0);
                                        $overallAverageRounded = round($overallAverageRating, 1);
                                    ?>

                                    <div class="review-summary p-3 rounded-4 border bg-light">

                                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">

                                            <div>

                                                <span class="review-kicker text-uppercase text-success small">
                                                    Feedback sent
                                                </span>

                                                <h6 class="mb-0 mt-1">
                                                    Your Review
                                                </h6>

                                            </div>

                                            <div class="review-summary-actions">
                                                <div
                                                    class="overall-review-rating"
                                                    aria-label="Overall average rating: <?= number_format($overallAverageRounded, 1) ?> out of 5">

                                                    <span class="overall-review-label">Overall average</span>

                                                    <div class="d-flex align-items-center gap-1 review-stars">

                                                    <?php for ($star = 1; $star <= 5; $star++): ?>

                                                        <?php
                                                            $starClass = 'bi-star text-muted';
                                                            if ($overallAverageRating >= $star) {
                                                                $starClass = 'bi-star-fill text-warning';
                                                            } elseif ($overallAverageRating >= ($star - 0.5)) {
                                                                $starClass = 'bi-star-half text-warning';
                                                            }
                                                        ?>

                                                        <i class="bi <?= $starClass ?>"></i>

                                                    <?php endfor; ?>

                                                        <strong class="overall-review-value ms-1">
                                                            <?= number_format($overallAverageRounded, 1) ?><span>/5</span>
                                                        </strong>

                                                    </div>

                                                </div>

                                                <button
                                                    class="btn btn-outline-success btn-sm rounded-pill review-more-btn"
                                                    type="button"
                                                    data-bs-toggle="collapse"
                                                    data-bs-target="#review-details-<?= (int) $item->review_id ?>"
                                                    aria-expanded="false"
                                                    aria-controls="review-details-<?= (int) $item->review_id ?>">
                                                    <span class="review-more-label">More</span>
                                                    <span class="review-less-label">Less</span>
                                                    <i class="bi bi-chevron-down" aria-hidden="true"></i>
                                                </button>
                                            </div>

                                        </div>

                                        <div class="collapse review-summary-more" id="review-details-<?= (int) $item->review_id ?>">

                                        <div class="review-metrics-grid review-metrics-grid-primary">

                                            <div class="review-metric">

                                                <div class="d-flex align-items-center gap-2">

                                                    <strong>
                                                        Freshness
                                                    </strong>

                                                    <div class="review-stars">

                                                        <?php for ($star = 1; $star <= 5; $star++): ?>

                                                            <i class="bi <?= $star <= (int) $item->freshness_rating
                                                                ? 'bi-star-fill text-warning'
                                                                : 'bi-star text-muted' ?>"></i>

                                                        <?php endfor; ?>

                                                    </div>

                                                </div>

                                            </div>

                                            <div class="review-metric">

                                                <div class="d-flex align-items-center gap-2">

                                                    <strong>
                                                        Seller Responsiveness
                                                    </strong>

                                                    <div class="review-stars">

                                                        <?php for ($star = 1; $star <= 5; $star++): ?>

                                                            <i class="bi <?= $star <= (int) $item->seller_responsiveness_rating
                                                                ? 'bi-star-fill text-warning'
                                                                : 'bi-star text-muted' ?>"></i>

                                                        <?php endfor; ?>

                                                    </div>

                                                </div>

                                            </div>

                                        </div>

                                        <div class="review-metrics-grid review-metrics-grid-delivery">
                                            <?php foreach ([
                                                'product_condition_rating' => ['Product Condition', 'bi-box-seam'],
                                                'driver_friendliness_rating' => ['Driver Friendliness', 'bi-emoji-smile'],
                                                'delivery_speed_rating' => ['Delivery Speed', 'bi-stopwatch'],
                                            ] as $field => [$label, $icon]): ?>
                                                <div class="review-metric">
                                                    <div class="d-flex align-items-center gap-2">
                                                        <strong><i class="bi <?= $icon ?> me-1"></i><?= $label ?></strong>
                                                        <?php $deliveryScore = (int) ($item->{$field} ?? 0); ?>
                                                        <div
                                                            class="review-stars d-flex align-items-center"
                                                            aria-label="<?= htmlspecialchars($label) ?>: <?= $deliveryScore ?> out of 5 stars"
                                                            title="<?= $deliveryScore ?> out of 5 stars">

                                                            <?php for ($star = 1; $star <= 5; $star++): ?>

                                                                <i class="bi <?= $star <= $deliveryScore
                                                                    ? 'bi-star-fill text-warning'
                                                                    : 'bi-star text-muted' ?>"></i>

                                                            <?php endfor; ?>

                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>

                                        <div class="review-notes-media <?= !empty($item->review_photo) ? 'has-photo' : '' ?>">

                                        <div class="review-note review-note-expandable">

                                            <?php if (!empty($item->review_comment)): ?>

                                                <div class="review-note-content">
                                                    <p class="mb-2 text-dark">

                                                        <?= nl2br(
                                                            htmlspecialchars(
                                                                $item->review_comment
                                                            )
                                                        ) ?>

                                                    </p>
                                                </div>

                                                <button
                                                    type="button"
                                                    class="review-note-toggle btn btn-link p-0 text-success fw-semibold small"
                                                    data-review-note-toggle
                                                    aria-expanded="false">
                                                    <span class="review-note-toggle-text">Show more</span>
                                                </button>

                                            <?php else: ?>

                                                <p class="text-muted mb-0">
                                                    No written comment added.
                                                </p>

                                            <?php endif; ?>

                                        </div>

                                        <?php if (!empty($item->review_photo)): ?>

                                            <div class="review-photo-preview mt-3">

                                                <strong class="d-block mb-2">
                                                    Review Photo
                                                </strong>

                                                <img
                                                    src="<?= htmlspecialchars($item->review_photo) ?>"
                                                    alt="Product review photo"
                                                    class="img-fluid rounded-3 review-photo-image">

                                            </div>

                                        <?php endif; ?>

                                        </div>

                                        <details class="mt-3">

                                            <summary class="btn btn-outline-success btn-sm rounded-pill">
                                                Edit Review
                                            </summary>

                                            <form
                                                action="/ani-mart/public/customer/orders/review/update"
                                                method="POST"
                                                enctype="multipart/form-data"
                                                class="review-form p-3 rounded-4 border bg-white mt-3">

                                                <input
                                                    type="hidden"
                                                    name="_token"
                                                    value="<?= htmlspecialchars(csrf_token()) ?>">

                                                <input
                                                    type="hidden"
                                                    name="review_id"
                                                    value="<?= (int) $item->review_id ?>">

                                                <div class="review-star-picker mb-3">

                                                    <?php for ($rating = 1; $rating <= 5; $rating++): ?>

                                                        <input
                                                            type="radio"
                                                            id="edit-rating-<?= (int) $item->review_id ?>-<?= $rating ?>"
                                                            name="rating"
                                                            value="<?= $rating ?>"
                                                            <?= (int) $item->review_rating === $rating ? 'checked' : '' ?>
                                                            required>

                                                        <label
                                                            class="<?= (int) $item->review_rating >= $rating ? 'selected' : '' ?>"
                                                            for="edit-rating-<?= (int) $item->review_id ?>-<?= $rating ?>"
                                                            data-rating="<?= $rating ?>">

                                                            <i class="bi bi-star-fill"></i>

                                                        </label>

                                                    <?php endfor; ?>

                                                </div>

                                                <div class="row gx-3 gy-3 mb-3">
                                                    <?php foreach ([
                                                        'product_condition_rating' => 'Product Condition on Arrival',
                                                        'driver_friendliness_rating' => 'Driver Friendliness',
                                                        'delivery_speed_rating' => 'Delivery Speed',
                                                    ] as $field => $label): ?>
                                                        <div class="col-md-4">
                                                            <label class="form-label fw-semibold"><?= $label ?></label>
                                                            <div class="review-star-picker">
                                                                <?php for ($rating = 1; $rating <= 5; $rating++): ?>
                                                                    <input type="radio" id="edit-<?= $field ?>-<?= (int) $item->review_id ?>-<?= $rating ?>" name="<?= $field ?>" value="<?= $rating ?>" <?= (int) ($item->{$field} ?? 0) === $rating ? 'checked' : '' ?> required>
                                                                    <label class="<?= (int) ($item->{$field} ?? 0) >= $rating ? 'selected' : '' ?>" for="edit-<?= $field ?>-<?= (int) $item->review_id ?>-<?= $rating ?>" data-rating="<?= $rating ?>"><i class="bi bi-star-fill"></i></label>
                                                                <?php endfor; ?>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>

                                                <div class="row gx-3 gy-3 mb-3">

                                                    <div class="col-md-6">

                                                        <label class="form-label fw-semibold">
                                                            Freshness
                                                        </label>

                                                        <div class="review-star-picker">
                                                            <?php for ($rating = 1; $rating <= 5; $rating++): ?>
                                                                <input type="radio" id="edit-freshness-<?= (int) $item->review_id ?>-<?= $rating ?>" name="freshness_rating" value="<?= $rating ?>" <?= (int) $item->freshness_rating === $rating ? 'checked' : '' ?> required>
                                                                <label class="<?= (int) $item->freshness_rating >= $rating ? 'selected' : '' ?>" for="edit-freshness-<?= (int) $item->review_id ?>-<?= $rating ?>" data-rating="<?= $rating ?>"><i class="bi bi-star-fill"></i></label>
                                                            <?php endfor; ?>
                                                        </div>

                                                    </div>

                                                    <div class="col-md-6">

                                                        <label class="form-label fw-semibold">
                                                            Seller Responsiveness
                                                        </label>

                                                        <div class="review-star-picker">
                                                            <?php for ($rating = 1; $rating <= 5; $rating++): ?>
                                                                <input type="radio" id="edit-responsiveness-<?= (int) $item->review_id ?>-<?= $rating ?>" name="seller_responsiveness_rating" value="<?= $rating ?>" <?= (int) $item->seller_responsiveness_rating === $rating ? 'checked' : '' ?> required>
                                                                <label class="<?= (int) $item->seller_responsiveness_rating >= $rating ? 'selected' : '' ?>" for="edit-responsiveness-<?= (int) $item->review_id ?>-<?= $rating ?>" data-rating="<?= $rating ?>"><i class="bi bi-star-fill"></i></label>
                                                            <?php endfor; ?>
                                                        </div>

                                                    </div>

                                                </div>

                                                <div class="mb-3">

                                                    <label
                                                        class="form-label fw-semibold"
                                                        for="edit-photo-<?= (int) $item->review_id ?>">

                                                        Replace photo (optional)

                                                    </label>

                                                    <input
                                                        type="file"
                                                        id="edit-photo-<?= (int) $item->review_id ?>"
                                                        name="review_photo"
                                                        accept="image/jpeg,image/png,image/webp"
                                                        class="form-control">

                                                </div>

                                                <div class="mb-3">

                                                    <label
                                                        class="form-label fw-semibold"
                                                        for="edit-comment-<?= (int) $item->review_id ?>">

                                                        Additional notes

                                                    </label>

                                                    <textarea
                                                        id="edit-comment-<?= (int) $item->review_id ?>"
                                                        name="comment"
                                                        class="form-control"
                                                        rows="3"
                                                        maxlength="1000"><?= htmlspecialchars($item->review_comment ?? '') ?></textarea>

                                                </div>

                                                <button
                                                    type="submit"
                                                    class="btn btn-success rounded-pill">

                                                    Save Changes

                                                </button>

                                            </form>

                                        </details>

                                        </div>

                                    </div>

                                <?php else: ?>

                                    <form
                                        action="/ani-mart/public/customer/orders/review"
                                        method="POST"
                                        enctype="multipart/form-data"
                                        class="review-form p-3 rounded-4 border bg-white product-feedback-shell">

                                        <input
                                            type="hidden"
                                            name="_token"
                                            value="<?= htmlspecialchars(csrf_token()) ?>">

                                        <input
                                            type="hidden"
                                            name="order_item_id"
                                            value="<?= (int) $item->id ?>">

                                        <div class="d-flex flex-column flex-md-row align-items-start justify-content-between gap-3 mb-4">

                                            <div>

                                                <span class="review-kicker text-uppercase text-success small">
                                                    Product feedback
                                                </span>

                                                <h5 class="mb-1">
                                                    How was this item?
                                                </h5>

                                                <p class="text-muted small mb-0">
                                                    Rate the product, seller, driver, and delivery experience.
                                                </p>

                                            </div>

                                            <button
                                                type="button"
                                                class="btn btn-outline-success btn-sm rounded-pill review-more-btn product-feedback-toggle"
                                                data-product-feedback-toggle
                                                aria-expanded="false">
                                                <span class="review-more-label">More</span>
                                                <span class="review-less-label">Less</span>
                                                <i class="bi bi-chevron-down" aria-hidden="true"></i>
                                            </button>

                                        </div>

                                        <div class="product-feedback-body">

                                        <div class="mb-4">

                                            <label class="form-label fw-semibold mb-1">
                                                Overall Rating
                                            </label>

                                            <p class="text-muted small mb-2">
                                                How satisfied were you with this product overall?
                                            </p>

                                            <div class="review-star-picker">

                                                <?php for ($rating = 1; $rating <= 5; $rating++): ?>

                                                    <input
                                                        type="radio"
                                                        id="rating-<?= (int) $item->id ?>-<?= $rating ?>"
                                                        name="rating"
                                                        value="<?= $rating ?>"
                                                        required>

                                                    <label
                                                        for="rating-<?= (int) $item->id ?>-<?= $rating ?>"
                                                        data-rating="<?= $rating ?>">

                                                        <i class="bi bi-star-fill"></i>

                                                    </label>

                                                <?php endfor; ?>

                                            </div>

                                        </div>

                                        <div class="row gx-3 gy-3 mb-4">
                                            <?php foreach ([
                                                'product_condition_rating' => ['Product Condition on Arrival', 'How well the item held up during delivery.'],
                                                'driver_friendliness_rating' => ['Driver Friendliness', 'How courteous and helpful the delivery driver was.'],
                                                'delivery_speed_rating' => ['Delivery Speed', 'How satisfied you were with the delivery time.'],
                                            ] as $field => [$label, $help]): ?>
                                                <div class="col-md-4">
                                                    <label class="form-label fw-semibold mb-1"><?= $label ?></label>
                                                    <p class="text-muted small mb-2"><?= $help ?></p>
                                                    <div class="review-star-picker">
                                                        <?php for ($rating = 1; $rating <= 5; $rating++): ?>
                                                            <input type="radio" id="<?= $field ?>-<?= (int) $item->id ?>-<?= $rating ?>" name="<?= $field ?>" value="<?= $rating ?>" required>
                                                            <label for="<?= $field ?>-<?= (int) $item->id ?>-<?= $rating ?>" data-rating="<?= $rating ?>"><i class="bi bi-star-fill"></i></label>
                                                        <?php endfor; ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>

                                        <div class="row gx-3 gy-3 mb-4">

                                            <div class="col-md-6">

                                                <div class="form-group">

                                                    <label class="form-label fw-semibold mb-2">
                                                        Freshness
                                                    </label>

                                                    <div class="review-star-picker">

                                                        <?php for ($rating = 1; $rating <= 5; $rating++): ?>

                                                            <input
                                                                type="radio"
                                                                id="freshness-<?= (int) $item->id ?>-<?= $rating ?>"
                                                                name="freshness_rating"
                                                                value="<?= $rating ?>"
                                                                required>

                                                            <label
                                                                for="freshness-<?= (int) $item->id ?>-<?= $rating ?>"
                                                                data-rating="<?= $rating ?>">

                                                                <i class="bi bi-star-fill"></i>

                                                            </label>

                                                        <?php endfor; ?>

                                                    </div>

                                                </div>

                                            </div>

                                            <div class="col-md-6">

                                                <div class="form-group">

                                                    <label class="form-label fw-semibold mb-2">
                                                        Seller Responsiveness
                                                    </label>

                                                    <div class="review-star-picker">

                                                        <?php for ($rating = 1; $rating <= 5; $rating++): ?>

                                                            <input
                                                                type="radio"
                                                                id="responsiveness-<?= (int) $item->id ?>-<?= $rating ?>"
                                                                name="seller_responsiveness_rating"
                                                                value="<?= $rating ?>"
                                                                required>

                                                            <label
                                                                for="responsiveness-<?= (int) $item->id ?>-<?= $rating ?>"
                                                                data-rating="<?= $rating ?>">

                                                                <i class="bi bi-star-fill"></i>

                                                            </label>

                                                        <?php endfor; ?>

                                                    </div>

                                                </div>

                                            </div>

                                        </div>

                                        <div class="mb-4">

                                            <label
                                                class="form-label fw-semibold"
                                                for="review-photo-<?= (int) $item->id ?>">

                                                Upload a product photo

                                            </label>

                                            <input
                                                type="file"
                                                id="review-photo-<?= (int) $item->id ?>"
                                                name="review_photo"
                                                accept="image/jpeg,image/png,image/webp"
                                                class="form-control"
                                                required>

                                        </div>

                                        <div class="mb-4">

                                            <label
                                                class="form-label fw-semibold"
                                                for="comment-<?= (int) $item->id ?>">

                                                Additional notes

                                            </label>

                                            <textarea
                                                id="comment-<?= (int) $item->id ?>"
                                                name="comment"
                                                class="form-control modern-input"
                                                rows="4"
                                                maxlength="1000"
                                                placeholder="Share how the product arrived, how the driver treated you, and whether the delivery was on time."></textarea>

                                        </div>

                                        <button
                                            type="submit"
                                            class="btn btn-success w-100 review-submit-btn py-3">

                                            <i class="bi bi-send me-2"></i>

                                            Submit

                                        </button>

                                        </div>

                                    </form>

                                <?php endif; ?>

                            <?php endif; ?>

                        <?php endforeach; ?>

                        <?php if ($openCustomerShop): ?>
                                </div>
                            </section>
                        <?php endif; ?>

                    <?php endif; ?>

                    <?php if (in_array($role, ['customer', 'farmer'], true)): ?>
                        <div class="customer-order-summary" aria-label="Payment summary">
                            <div>
                                <span>Products subtotal</span>
                                <strong>₱<?= number_format($productSubtotal, 2) ?></strong>
                            </div>

                            <div>
                                <span>Total delivery fees</span>
                                <strong><?= (float) ($order->delivery_fee ?? 0) > 0 ? '₱' . number_format((float) $order->delivery_fee, 2) : 'Free' ?></strong>
                            </div>

                            <div class="customer-order-summary-total">
                                <span>Order total</span>
                                <strong>₱<?= number_format((float) $order->total_amount, 2) ?></strong>
                            </div>
                        </div>
                    <?php endif; ?>

                </div>

                <!-- ORDER ACTIONS -->
                <div class="farmer-order-actions">

                    <?php if ($role === 'farmer'): ?>

                        <?php if ((string) ($order->status ?? '') === 'completed' && !empty($order->customer_confirmed)): ?>
                            <a
                                href="/ani-mart/public/orders/receipt?id=<?= (int) $order->id ?>"
                                class="btn btn-success farmer-action-btn">

                                <i class="bi bi-receipt me-2"></i>
                                View Receipt

                            </a>
                        <?php endif; ?>

                        <?php if (!empty($order->return_request_id)): ?>
                            <a href="/ani-mart/public/farmer/returns/show?id=<?= (int) $order->return_request_id ?>" class="btn btn-outline-primary farmer-action-btn">
                                <i class="bi bi-arrow-return-left me-2"></i>
                                Return: <?= htmlspecialchars(ucfirst((string) $order->return_request_status)) ?>
                            </a>
                        <?php endif; ?>

                        <?php if (($order->cancellation_request_status ?? '') === 'pending'): ?>

                            <button type="button" class="btn btn-success farmer-action-btn"
                                onclick="respondToCancellation(<?= (int) $order->id ?>, 'accepted')">
                                <i class="bi bi-check-circle me-2"></i>Accept Cancellation
                            </button>

                            <button type="button" class="btn btn-outline-danger farmer-action-btn"
                                onclick="respondToCancellation(<?= (int) $order->id ?>, 'declined')">
                                <i class="bi bi-x-circle me-2"></i>Decline Cancellation
                            </button>

                        <?php elseif ($order->status === 'pending'): ?>

                            <button type="button" class="btn btn-success farmer-action-btn"
                                onclick="updateOrderStatus(<?= $order->id ?>, 'accepted')">

                                <i class="bi bi-check-circle me-2"></i>Accept Order

                            </button>

                            <button type="button" class="btn btn-outline-danger farmer-action-btn"
                                onclick="updateOrderStatus(<?= $order->id ?>, 'cancelled')">

                                <i class="bi bi-x-circle me-2"></i>Cancel Shop Items

                            </button>

                            <a href="/ani-mart/public/messages/start?order_id=<?= $order->id ?>"
                                class="btn btn-outline-success farmer-action-btn">

                                <i class="bi bi-chat-left-text me-2"></i>
                                Message Customer

                            </a>

                        <?php elseif ($order->status === 'accepted'): ?>

                            <button type="button" class="btn btn-outline-danger farmer-action-btn"
                                onclick="updateOrderStatus(<?= $order->id ?>, 'cancelled')">

                                <i class="bi bi-x-circle me-2"></i>Cancel Shop Items

                            </button>

                            <a href="/ani-mart/public/messages/start?order_id=<?= $order->id ?>"
                                class="btn btn-outline-success farmer-action-btn">

                                <i class="bi bi-chat-left-text me-2"></i>
                                Message Customer

                            </a>

                            <button type="button" class="btn btn-primary farmer-action-btn"
                                onclick="updateOrderStatus(<?= $order->id ?>, 'preparing')">

                                <i class="bi bi-basket me-2"></i>Start Preparing

                            </button>

                        <?php elseif ($order->status === 'preparing'): ?>

                            <button type="button" class="btn btn-warning farmer-action-btn"
                                onclick="updateOrderStatus(<?= $order->id ?>, 'out_for_delivery')">

                                <i class="bi bi-truck me-2"></i>Mark Out for Delivery

                            </button>

                        <?php elseif ($order->status === 'out_for_delivery'): ?>

                            <a href="/ani-mart/public/messages/start?order_id=<?= $order->id ?>"
                                class="btn btn-outline-success farmer-action-btn">

                                <i class="bi bi-chat-left-text me-2"></i>
                                Message Customer

                            </a>

                            <span class="badge bg-info farmer-awaiting-badge">

                                <i class="bi bi-clock-history me-1"></i>Awaiting Customer Confirmation

                            </span>

                        <?php elseif ($order->status === 'delivered'): ?>

                            <span class="badge bg-success farmer-completed-badge">

                                <i class="bi bi-check-circle me-1"></i>Order Delivered

                            </span>

                        <?php elseif ($order->status === 'completed'): ?>

                            <a href="/ani-mart/public/messages/start?order_id=<?= $order->id ?>"
                                class="btn btn-outline-success farmer-action-btn">

                                <i class="bi bi-chat-left-text me-2"></i>
                                Message Customer

                            </a>

                            <span class="badge bg-success farmer-completed-badge">

                                <i class="bi bi-check-circle me-1"></i>Order Completed

                            </span>

                        <?php elseif ($order->status === 'cancelled'): ?>

                            <span class="badge bg-danger farmer-cancelled-badge">

                                <i class="bi bi-x-circle me-1"></i>Order Cancelled

                            </span>

                        <?php endif; ?>

                    <?php elseif ($role === 'customer'): ?>

                        <?php if ((string) ($order->status ?? '') === 'completed' && !empty($order->customer_confirmed)): ?>
                            <a
                                href="/ani-mart/public/orders/receipt?id=<?= (int) $order->id ?>"
                                class="btn btn-success farmer-action-btn">

                                <i class="bi bi-receipt me-2"></i>
                                View Receipt

                            </a>
                        <?php endif; ?>

                        <a href="/ani-mart/public/messages/start?order_id=<?= $order->id ?>"
                            class="btn btn-outline-success farmer-action-btn">

                            <i class="bi bi-chat-left-text me-2"></i>
                            Message Seller

                        </a>

                        <?php if (!empty($order->return_request_id)): ?>
                            <a href="/ani-mart/public/customer/returns/show?id=<?= (int) $order->return_request_id ?>" class="btn btn-outline-primary farmer-action-btn">
                                <i class="bi bi-arrow-return-left me-2"></i>
                                Return: <?= htmlspecialchars(ucfirst((string) $order->return_request_status)) ?>
                            </a>
                        <?php else: ?>
                            <?php $returnAllowed = \App\Services\MarketplaceRulesService::canRequestReturn((string) $order->status, (string) ($order->delivered_at ?? $order->completed_at ?? '')); ?>
                            <?php if (in_array((string) $order->status, ['delivered', 'completed'], true)): ?>
                                <button type="button" class="btn btn-outline-warning farmer-action-btn" <?= $returnAllowed ? '' : 'disabled' ?> <?php if ($returnAllowed): ?>onclick="window.location.href='/ani-mart/public/customer/returns/create?order_id=<?= (int) $order->id ?>'"<?php endif; ?>>
                                    <i class="bi bi-arrow-return-left me-2"></i>Request Return
                                </button>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php if (
                            in_array((string) $order->status, ['pending', 'accepted'], true)
                            && empty($order->cancellation_request_id)
                        ): ?>
                            <button type="button" class="btn btn-outline-danger farmer-action-btn"
                                onclick="requestOrderCancellation(<?= (int) $order->id ?>)">
                                <i class="bi bi-x-circle me-2"></i>Request Cancellation
                            </button>
                        <?php endif; ?>

                    <?php endif; ?>

                </div>

            </div>

        <?php endforeach; ?>

    <?php else: ?>

        <!-- EMPTY STATE -->
        <div class="empty-state orders-empty-state">

            <?php if ($role === 'farmer'): ?>

                <i class="bi bi-inbox orders-empty-icon"></i>

                <h4>No Orders Yet</h4>

                <p class="text-muted">Customer orders will appear here when they purchase your products.</p>

                <a href="/ani-mart/public/marketplace" class="btn btn-success rounded-pill mt-3 orders-empty-action">

                    <i class="bi bi-shop" aria-hidden="true"></i>
                    <span>Visit Marketplace</span>

                </a>

            <?php else: ?>

                <i class="bi bi-bag-x orders-empty-icon"></i>

                <h4>
                    No Orders Found
                </h4>

                <p class="text-muted">
                    Orders will appear here.
                </p>

            <?php endif; ?>

        </div>

    <?php endif; ?>

</div>

<?php if (!empty($orders)): ?>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const orderStatusCsrfToken =
        <?= json_encode(csrf_token()) ?>;

    document.querySelectorAll('[data-review-note-toggle]').forEach(function (button) {
        button.addEventListener('click', function () {
            const noteCard = button.closest('.review-note-expandable');
            if (!noteCard) return;

            const isExpanded = noteCard.classList.toggle('is-expanded');
            button.setAttribute('aria-expanded', String(isExpanded));

            const label = button.querySelector('.review-note-toggle-text');
            if (label) {
                label.textContent = isExpanded ? 'Show less' : 'Show more';
            }
        });
    });

    document.querySelectorAll('[data-product-feedback-toggle]').forEach(function (button) {
        button.addEventListener('click', function () {
            const shell = button.closest('.product-feedback-shell');
            if (!shell) return;

            const isExpanded = shell.classList.toggle('is-expanded');
            button.setAttribute('aria-expanded', String(isExpanded));
        });
    });

    window.updateOrderStatus = async function (orderId, status) {

        const labels = {
            accepted: 'accept this order',
            cancelled: 'cancel only your shop items in this order',
            preparing: 'start preparing this order',
            out_for_delivery: 'mark this order as out for delivery'
        };

        const promptText = 'Are you sure you want to ' + (labels[status] || 'update this order') + '?';

        if (window.aniMartConfirm) {
            window.aniMartConfirm(promptText, 'Update order status', async function () {
                const actionButtons =
                    document.querySelectorAll(
                        `button[onclick*="updateOrderStatus(${Number(orderId)},"]`
                    );

                actionButtons.forEach((button) => {
                    button.disabled = true;
                });

                try {
                    const body = new URLSearchParams({
                        _token: orderStatusCsrfToken,
                        order_id: String(orderId),
                        status: String(status)
                    });

                    const response = await fetch('/ani-mart/public/farmer/orders/status', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
                        },
                        body: body.toString(),
                        credentials: 'same-origin'
                    });

                    const data = await response.json().catch(() => null);
                    if (!response.ok || !data?.success) {
                        throw new Error(data?.message || 'Unable to update order status. Please try again.');
                    }

                    location.reload();
                } catch (error) {
                    alert(error.message || 'Unable to update order status.');
                    actionButtons.forEach((button) => {
                        button.disabled = false;
                    });
                }
            }, { confirmText: 'Continue', confirmClass: 'btn-primary' });
            return;
        }

        const actionButtons =
            document.querySelectorAll(
                `button[onclick*="updateOrderStatus(${Number(orderId)},"]`
            );

        actionButtons.forEach((button) => {
            button.disabled = true;
        });

        try {
            const body = new URLSearchParams({
                _token: orderStatusCsrfToken,
                order_id: String(orderId),
                status: String(status)
            });

            const response = await fetch(
                '/ani-mart/public/farmer/orders/status',
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                        'Accept': 'application/json'
                    },
                    body: body.toString(),
                    credentials: 'same-origin'
                }
            );

            const result = await response.json().catch(() => null);

            if (!response.ok || !result?.success) {
                throw new Error(
                    result?.message ||
                    'The order status could not be updated.'
                );
            }

            window.location.reload();
        } catch (error) {
            alert(error.message || 'The order status could not be updated. Please try again.');

            actionButtons.forEach((button) => {
                button.disabled = false;
            });
        }
    };

    async function submitCancellationAction(url, values, buttons, fallbackMessage) {
        buttons.forEach((button) => { button.disabled = true; });

        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    'Accept': 'application/json'
                },
                body: new URLSearchParams({ _token: orderStatusCsrfToken, ...values }).toString(),
                credentials: 'same-origin'
            });
            const result = await response.json().catch(() => null);
            if (!response.ok || !result?.success) {
                throw new Error(result?.message || fallbackMessage);
            }
            window.location.reload();
        } catch (error) {
            alert(error.message || fallbackMessage);
            buttons.forEach((button) => { button.disabled = false; });
        }
    }

    window.requestOrderCancellation = function (orderId) {
        const reason = window.prompt('Why do you want to cancel this order? (5–500 characters)');
        if (reason === null) return;

        const buttons = document.querySelectorAll(
            `button[onclick="requestOrderCancellation(${Number(orderId)})"]`
        );
        submitCancellationAction(
            '/ani-mart/public/customer/orders/cancellation/request',
            { order_id: String(orderId), reason: reason.trim() },
            buttons,
            'The cancellation request could not be submitted.'
        );
    };

    window.respondToCancellation = function (orderId, decision) {
        let reason = '';
        if (decision === 'declined') {
            reason = window.prompt('Please provide a reason to decline this cancellation request (5–500 characters).');
            if (reason === null) return;
        }

        const buttons = document.querySelectorAll(
            `button[onclick*="respondToCancellation(${Number(orderId)},"]`
        );
        submitCancellationAction(
            '/ani-mart/public/farmer/orders/cancellation/respond',
            { order_id: String(orderId), decision: decision, reason: reason.trim() },
            buttons,
            'The cancellation decision could not be saved.'
        );
    };

    const orderSearchInput =
        document.getElementById('orderSearchInput');

    const orderStatusFilter =
        document.getElementById('orderStatusFilter');

    const orderSellerFilter =
        document.getElementById('orderSellerFilter');

    const orderProductFilter =
        document.getElementById('orderProductFilter');

    const orderDateFrom =
        document.getElementById('orderDateFrom');

    const orderDateTo =
        document.getElementById('orderDateTo');

    const clearOrderFilters =
        document.getElementById('clearOrderFilters');

    const orderCards =
        document.querySelectorAll('.order-card-filterable');

    const ordersFilteredEmpty =
        document.getElementById('ordersFilteredEmpty');

    const orderExportCsv = document.getElementById('orderExportCsv');
    const orderExportPdf = document.getElementById('orderExportPdf');


    function filterOrders() {

        const search =
            (orderSearchInput?.value || '')
                .trim()
                .toLowerCase();

        const status =
            orderStatusFilter?.value || '';

        const seller =
            orderSellerFilter?.value || '';

        const product =
            orderProductFilter?.value || '';

        const fromDate =
            orderDateFrom?.value || '';

        const toDate =
            orderDateTo?.value || '';

        let visibleCount = 0;


        orderCards.forEach(card => {

            const searchable =
                card.dataset.orderSearch || '';

            const cardStatus =
                card.dataset.orderStatus || '';

            const cardDate =
                card.dataset.orderDate || '';

            const cardSeller =
                card.dataset.orderSeller || '';

            const cardProducts =
                card.dataset.orderProducts || '';


            const matchesSearch =
                search === '' ||
                searchable.includes(search);

            const matchesStatus =
                status === '' ||
                cardStatus === status;

            const matchesSeller =
                seller === '' ||
                cardSeller === seller;

            const matchesProduct =
                product === '' ||
                cardProducts.includes('|' + product + '|');

            const matchesFrom =
                fromDate === '' ||
                cardDate >= fromDate;

            const matchesTo =
                toDate === '' ||
                cardDate <= toDate;


            const isVisible =
                matchesSearch &&
                matchesStatus &&
                matchesSeller &&
                matchesProduct &&
                matchesFrom &&
                matchesTo;


            card.classList.toggle(
                'd-none',
                !isVisible
            );


            if (isVisible) {
                visibleCount++;
            }

        });


        if (ordersFilteredEmpty) {

            ordersFilteredEmpty.classList.toggle(
                'd-none',
                visibleCount > 0
            );

        }

        const exportParams = new URLSearchParams();
        if (fromDate) exportParams.set('date_from', fromDate);
        if (toDate) exportParams.set('date_to', toDate);
        if (seller) exportParams.set('seller', orderSellerFilter.options[orderSellerFilter.selectedIndex].text);
        if (product) exportParams.set('product', orderProductFilter.options[orderProductFilter.selectedIndex].text);
        if (status) exportParams.set('status', status);
        const suffix = exportParams.toString() ? '?' + exportParams.toString() : '';
        if (orderExportCsv) orderExportCsv.href = '/ani-mart/public/customer/orders/export/csv' + suffix;
        if (orderExportPdf) orderExportPdf.href = '/ani-mart/public/customer/orders/export/pdf' + suffix;

    }


    [
        orderSearchInput,
        orderStatusFilter,
        orderSellerFilter,
        orderProductFilter,
        orderDateFrom,
        orderDateTo
    ].forEach(input => {

        if (!input) {
            return;
        }

        input.addEventListener(
            input.tagName === 'INPUT'
                ? 'input'
                : 'change',
            filterOrders
        );

    });


    clearOrderFilters?.addEventListener(
        'click',
        function () {

            if (orderSearchInput) {
                orderSearchInput.value = '';
            }

            if (orderStatusFilter) {
                orderStatusFilter.value = '';
            }

            if (orderSellerFilter) {
                orderSellerFilter.value = '';
            }

            if (orderProductFilter) {
                orderProductFilter.value = '';
            }

            if (orderDateFrom) {
                orderDateFrom.value = '';
            }

            if (orderDateTo) {
                orderDateTo.value = '';
            }

            filterOrders();

        }
    );


    function scrollToTargetOrder() {

        const params =
            new URLSearchParams(window.location.search);

        const orderId =
            params.get('id');

        if (!orderId) {
            return;
        }

        const target =
            document.getElementById(
                'order-card-' + orderId
            );

        if (!target) {
            return;
        }

        target.classList.add(
            'highlighted-target'
        );

        target.scrollIntoView({
            behavior: 'smooth',
            block: 'center'
        });

        window.setTimeout(
            function () {

                target.classList.remove(
                    'highlighted-target'
                );

            },
            4000
        );

    }


    scrollToTargetOrder();


    document
        .querySelectorAll('.review-star-picker')
        .forEach(picker => {

            const inputs =
                picker.querySelectorAll(
                    'input[type="radio"]'
                );

            const labels =
                picker.querySelectorAll('label');


            function paintStars(value) {

                labels.forEach(label => {

                    label.classList.toggle(
                        'selected',
                        Number(
                            label.dataset.rating
                        ) <= value
                    );

                });

            }


            labels.forEach(label => {

                label.addEventListener(
                    'mouseenter',
                    function () {

                        paintStars(
                            Number(
                                label.dataset.rating
                            )
                        );

                    }
                );

            });


            picker.addEventListener(
                'mouseleave',
                function () {

                    const checked =
                        picker.querySelector(
                            'input[type="radio"]:checked'
                        );

                    paintStars(
                        checked
                            ? Number(checked.value)
                            : 0
                    );

                }
            );


            inputs.forEach(input => {

                input.addEventListener(
                    'change',
                    function () {

                        paintStars(
                            Number(input.value)
                        );

                    }
                );

            });

        });

});
</script>

<?php endif; ?>

<?php

$content = ob_get_clean();

$pageCss = '/ani-mart/public/assets/css/pages/orders.css?v=' .
    filemtime(__DIR__ . '/../../../public/assets/css/pages/orders.css');

require ANI_MART_ROOT . '/app/Views/layouts/main.php';
