<?php

$title = "Farmer Verification Details";
$bodyClass = "admin-page admin-verification-detail-page";
$pageStyles[] = '/ani-mart/public/assets/css/pages/admin-verification-detail.css?v=' .
    filemtime(__DIR__ . '/../../../../public/assets/css/pages/admin-verification-detail.css');

ob_start();

use App\Services\BadgeCalculationService;

?>

<div class="container app-page-spacing verification-detail-page">

<!-- HERO -->

<div class="admin-hero verification-detail-hero mb-4">

    <div class="row align-items-center">

        <div class="col-lg-8">

            <span class="hero-badge">

                Ani-Mart Administration

            </span>

            <h1 class="hero-title mt-4">

                <?= htmlspecialchars($seller->shop_name ?? '') ?>

            </h1>

            <p class="hero-subtitle mt-3">

                <?= htmlspecialchars(
                    ($seller->first_name ?? '')
                    . ' ' .
                    ($seller->last_name ?? '')
                ) ?>

            </p>

            <a href="/ani-mart/public/dashboard" class="admin-dashboard-back-link">
                <i class="bi bi-arrow-left" aria-hidden="true"></i>
                Admin dashboard
            </a>

        </div>

    </div>

</div>

<!-- BADGE STATUS -->

<div class="card verification-badge-card mb-4">

    <div class="card-body p-4">

        <div class="row align-items-center g-3">

            <div class="col-lg-8">

                <h2 class="verification-card-title">Current Verification Badge</h2>

                <div class="verification-current-badge">
                    <span class="badge <?= BadgeCalculationService::getBadgeColor($seller->verification_badge) ?> verification-current-badge-icon" aria-hidden="true">

                        <i class="bi <?= BadgeCalculationService::getBadgeIcon($seller->verification_badge) ?>"></i>

                    </span>

                    <span>
                        <strong><?= htmlspecialchars(BadgeCalculationService::getBadgeLabel($seller->verification_badge)) ?></strong>
                        <small>Current seller verification level</small>
                    </span>
                </div>

            </div>

            <div class="col-lg-4 text-end">

                <form method="POST" action="/ani-mart/public/admin/verifications/recalculate-single">

                    <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                    <input type="hidden" name="seller_id" value="<?= $seller->id ?>">

                    <button type="submit" class="btn btn-outline-primary rounded-pill px-4">

                        <i class="bi bi-arrow-clockwise me-2"></i>Recalculate Badge

                    </button>

                </form>

            </div>

        </div>

    </div>

</div>

<!-- METRICS -->

<div class="row g-3 mb-4 verification-metrics-grid">

    <div class="col-lg-3 col-md-6">

        <div class="card verification-metric-card h-100">

            <div class="card-body p-4">

                <small class="text-muted d-block">Trust Score</small>

                <h3 class="fw-bold mb-0"><?= $seller->trust_score ?? 0 ?>%</h3>

            </div>

        </div>

    </div>

    <div class="col-lg-3 col-md-6">

        <div class="card verification-metric-card h-100">

            <div class="card-body p-4">

                <small class="text-muted d-block">Transparency Score</small>

                <h3 class="fw-bold mb-0"><?= $seller->transparency_score ?? 0 ?>%</h3>

            </div>

        </div>

    </div>

    <div class="col-lg-3 col-md-6">

        <div class="card verification-metric-card h-100">

            <div class="card-body p-4">

                <small class="text-muted d-block">Completed Orders</small>

                <h3 class="fw-bold mb-0"><?= count($orders) ?></h3>

            </div>

        </div>

    </div>

    <div class="col-lg-3 col-md-6">

        <div class="card verification-metric-card h-100">

            <div class="card-body p-4">

                <small class="text-muted d-block">Total Complaints</small>

                <h3 class="fw-bold mb-0 <?= count($complaints) > 0 ? 'text-danger' : 'text-success' ?>"><?= count($complaints) ?></h3>

            </div>

        </div>

    </div>

</div>

<!-- TABS -->

<ul class="nav nav-tabs verification-detail-tabs mb-3" id="verificationTabs" role="tablist">

    <li class="nav-item">

        <button class="nav-link active rounded-pill" data-bs-toggle="tab" data-bs-target="#orders-tab">

            <i class="bi bi-box-seam me-2"></i>Orders (<?= count($orders) ?>)

        </button>

    </li>

    <li class="nav-item">

        <button class="nav-link rounded-pill" data-bs-toggle="tab" data-bs-target="#complaints-tab">

            <i class="bi bi-exclamation-triangle me-2"></i>Complaints (<?= count($complaints) ?>)

        </button>

    </li>

    <li class="nav-item">

        <button class="nav-link rounded-pill" data-bs-toggle="tab" data-bs-target="#products-tab">

            <i class="bi bi-basket me-2"></i>Products (<?= count($products) ?>)

        </button>

    </li>

    <li class="nav-item">

        <button class="nav-link rounded-pill" data-bs-toggle="tab" data-bs-target="#info-tab">

            <i class="bi bi-info-circle me-2"></i>Profile Info

        </button>

    </li>

</ul>

<div class="tab-content verification-detail-tab-content">

    <!-- ORDERS TAB -->

    <div class="tab-pane fade show active" id="orders-tab">

        <div class="card verification-detail-panel">

            <div class="card-body p-4">

                <?php if(count($orders)): ?>

                    <div class="table-responsive">

                        <table class="table align-middle">

                            <thead>

                                <tr>

                                    <th>Order ID</th>

                                    <th>Customer</th>

                                    <th>Items</th>

                                    <th>Total</th>

                                    <th>Status</th>

                                    <th>Date</th>

                                </tr>

                            </thead>

                            <tbody>

                                <?php foreach($orders as $order): ?>

                                    <tr>

                                        <td>#<?= $order->id ?></td>

                                        <td>

                                            <span class="fw-semibold">

                                                <?= htmlspecialchars($order->first_name ?? '') ?>

                                                <?= htmlspecialchars($order->last_name ?? '') ?>

                                            </span>

                                        </td>

                                        <td><?= $order->item_count ?? 0 ?></td>

                                        <td>₱<?= number_format($order->total_amount ?? 0, 2) ?></td>

                                        <td>

                                            <span class="badge bg-<?= $order->status === 'completed' ? 'success' : ($order->status === 'pending' ? 'warning' : 'secondary') ?> rounded-pill">

                                                <?= ucfirst($order->status ?? '') ?>

                                            </span>

                                        </td>

                                        <td><?= date('M d, Y', strtotime($order->created_at ?? 'now')) ?></td>

                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                <?php else: ?>

                    <div class="text-center py-5">

                        <i class="bi bi-inbox fs-1 text-muted mb-3 d-block"></i>

                        <p class="text-muted">No orders found</p>

                    </div>

                <?php endif; ?>

            </div>

        </div>

    </div>

    <!-- COMPLAINTS TAB -->

    <div class="tab-pane fade" id="complaints-tab">

        <div class="card verification-detail-panel">

            <div class="card-body p-4">

                <?php if(count($complaints)): ?>

                    <div class="table-responsive">

                        <table class="table align-middle">

                            <thead>

                                <tr>

                                    <th>ID</th>

                                    <th>Type</th>

                                    <th>Description</th>

                                    <th>Status</th>

                                    <th>Date</th>

                                </tr>

                            </thead>

                            <tbody>

                                <?php foreach($complaints as $complaint): ?>

                                    <tr>

                                        <td>#<?= $complaint->id ?></td>

                                        <td><?= htmlspecialchars($complaint->type ?? '') ?></td>

                                        <td><?= htmlspecialchars(mb_strimwidth($complaint->description ?? '', 0, 50, '...')) ?></td>

                                        <td>

                                            <span class="badge bg-<?= $complaint->status === 'resolved' ? 'success' : ($complaint->status === 'pending' ? 'warning' : 'danger') ?> rounded-pill">

                                                <?= ucfirst($complaint->status ?? '') ?>

                                            </span>

                                        </td>

                                        <td><?= date('M d, Y', strtotime($complaint->created_at ?? 'now')) ?></td>

                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                <?php else: ?>

                    <div class="text-center py-5">

                        <i class="bi bi-shield-check fs-1 text-success mb-3 d-block"></i>

                        <p class="text-muted">No complaints - Clean record!</p>

                    </div>

                <?php endif; ?>

            </div>

        </div>

    </div>

    <!-- PRODUCTS TAB -->

    <div class="tab-pane fade" id="products-tab">

        <div class="card verification-detail-panel">

            <div class="card-body p-4">

                <?php if(count($products)): ?>

                    <div class="table-responsive">

                        <table class="table align-middle">

                            <thead>

                                <tr>

                                    <th>Product</th>

                                    <th>Category</th>

                                    <th>Price</th>

                                    <th>Stock</th>

                                    <th>Harvest Photo</th>

                                </tr>

                            </thead>

                            <tbody>

                                <?php foreach($products as $product): ?>

                                    <tr>

                                        <td>

                                            <div class="d-flex align-items-center gap-3">

                                                <?php if($product->image): ?>

                                                    <img src="<?= htmlspecialchars($product->image) ?>" class="rounded" width="40" height="40" style="object-fit: cover;" alt="<?= htmlspecialchars(($product->name ?? 'Product') . ' image') ?>">

                                                <?php endif; ?>

                                                <span class="fw-semibold"><?= htmlspecialchars($product->name ?? '') ?></span>

                                            </div>

                                        </td>

                                        <td><?= htmlspecialchars($product->category_name ?? '') ?></td>

                                        <td>₱<?= number_format($product->price ?? 0, 2) ?></td>

                                        <td><?= $product->stock ?? 0 ?></td>

                                        <td>

                                            <?php if($product->harvest_photo): ?>

                                                <span class="badge bg-success rounded-pill">

                                                    <i class="bi bi-check-circle me-1"></i>Yes

                                                </span>

                                            <?php else: ?>

                                                <span class="badge bg-secondary rounded-pill">No</span>

                                            <?php endif; ?>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                <?php else: ?>

                    <div class="text-center py-5">

                        <i class="bi bi-basket fs-1 text-muted mb-3 d-block"></i>

                        <p class="text-muted">No products found</p>

                    </div>

                <?php endif; ?>

            </div>

        </div>

    </div>

    <!-- PROFILE INFO TAB -->

    <div class="tab-pane fade" id="info-tab">

        <div class="card verification-detail-panel">

            <div class="card-body p-4">

                <div class="row g-4">

                    <div class="col-lg-6">

                        <h6 class="fw-bold mb-3">Contact Information</h6>

                        <p class="mb-2"><strong>Email:</strong> <?= htmlspecialchars($seller->email ?? '') ?></p>

                        <p class="mb-2"><strong>Phone:</strong> <?= htmlspecialchars((string) sensitive_value($seller, 'phone', 'users.phone', 'Not provided')) ?></p>

                        <p class="mb-2"><strong>Barangay:</strong> <?= htmlspecialchars($seller->barangay ?? '') ?></p>

                        <p class="mb-2"><strong>City:</strong> <?= htmlspecialchars($seller->city ?? '') ?></p>

                    </div>

                    <div class="col-lg-6">

                        <h6 class="fw-bold mb-3">Account Information</h6>

                        <p class="mb-2"><strong>Member Since:</strong> <?= date('F d, Y', strtotime($seller->user_created_at ?? 'now')) ?></p>

                        <p class="mb-2"><strong>Shop Created:</strong> <?= date('F d, Y', strtotime($seller->created_at ?? 'now')) ?></p>

                        <p class="mb-2"><strong>ID Verified:</strong> 

                            <?php if($seller->is_verified ?? 0): ?>

                                <span class="badge bg-success rounded-pill">Yes</span>

                            <?php else: ?>

                                <span class="badge bg-secondary rounded-pill">No</span>

                            <?php endif; ?>

                        </p>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

</div>

<?php

$content = ob_get_clean();

require ANI_MART_ROOT . '/app/Views/layouts/main.php';
