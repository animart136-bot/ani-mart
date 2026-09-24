<?php

$title = "Farmer Verifications";

$pageStyles[] = '/ani-mart/public/assets/css/pages/admin-verifications.css?v=' . filemtime(__DIR__ . '/../../../../public/assets/css/pages/admin-verifications.css');

ob_start();

use App\Services\BadgeCalculationService;

?>

<div class="container app-page-spacing">

<!-- HERO -->

<div class="admin-hero mb-5">

    <div class="row align-items-center">

        <div class="col-lg-8">

            <span class="hero-badge">

                Ani-Mart Administration

            </span>

            <h1 class="hero-title mt-4">

                Farmer Verification Badges

            </h1>

            <p class="hero-subtitle mt-3">

                Manage farmer verification badges, promote trustworthy sellers,
                and maintain marketplace quality standards.

            </p>

        </div>

    </div>

</div>

<!-- BADge DISTRIBUTION -->

<div class="row g-4 mb-5">

    <div class="col-lg-4 col-md-6">

        <div class="verification-stat-card trusted-card">

            <div class="stat-icon">

                <i class="bi bi-shield-check"></i>

            </div>

            <div>

                <small class="text-white d-block">Trusted Farmers</small>

                <h2 class="fw-bold mb-0"><?= $distribution['trusted'] ?? 0 ?></h2>

            </div>

        </div>

    </div>

    <div class="col-lg-4 col-md-6">

        <div class="verification-stat-card verified-card">

            <div class="stat-icon">

                <i class="bi bi-patch-check-fill"></i>

            </div>

            <div>

                <small class="text-white d-block">Verified Farmers</small>

                <h2 class="fw-bold mb-0"><?= $distribution['verified'] ?? 0 ?></h2>

            </div>

        </div>

    </div>

    <div class="col-lg-4 col-md-6">

        <div class="verification-stat-card new-card">

            <div class="stat-icon">

                <i class="bi bi-person-plus"></i>

            </div>

            <div>

                <small class="text-white d-block">New Farmers</small>

                <h2 class="fw-bold mb-0"><?= $distribution['new'] ?? 0 ?></h2>

            </div>

        </div>

    </div>

</div>

<!-- ACTIONS -->

<div class="d-flex justify-content-between align-items-center mb-4">

    <h5 class="fw-bold mb-0">All Farmers</h5>

    <div class="d-flex gap-2">

        <form method="POST" action="/ani-mart/public/admin/verifications/recalculate">

            <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token()) ?>">

            <button type="submit" class="btn btn-outline-primary rounded-pill px-4">

                <i class="bi bi-arrow-clockwise me-2"></i>

                Recalculate All Badges

            </button>

        </form>

    </div>

</div>

<!-- TABLE -->

<div class="card border-0 shadow-sm rounded-5">

    <div class="card-body p-4">

        <div class="table-responsive dropdown-visible">

            <table class="table align-middle">

                <thead>

                    <tr>

                        <th>Farmer</th>

                        <th>Shop</th>

                        <th>Status</th>

                        <th>Current Badge</th>

                        <th>Trust Score</th>

                        <th>Completed Orders</th>

                        <th>Complaints</th>

                        <th>Harvest Photos</th>

                        <th>Actions</th>

                    </tr>

                </thead>

                <tbody>

                    <?php if(count($sellers)): ?>

                        <?php foreach($sellers as $seller): ?>

                            <tr>

                                <!-- NAME -->

                                <td>

                                    <div class="d-flex align-items-center gap-3">

                                        <div class="farmer-avatar">

                                            <?= strtoupper(substr($seller->first_name ?? 'F', 0, 1)) ?>

                                        </div>

                                        <div>

                                            <h6 class="fw-bold mb-0">

                                                <?= htmlspecialchars(
                                                    ($seller->first_name ?? '')
                                                    . ' ' .
                                                    ($seller->last_name ?? '')
                                                ) ?>

                                            </h6>

                                            <small class="text-muted">

                                                <?= htmlspecialchars($seller->email ?? '') ?>

                                            </small>

                                        </div>

                                    </div>

                                </td>

                                <!-- SHOP -->

                                <td>

                                    <span class="fw-semibold">

                                        <?= htmlspecialchars($seller->shop_name ?? '') ?>

                                    </span>

                                </td>

                                <!-- BADGE -->

                                <td>
                                    <span class="badge <?= ($seller->status ?? 'pending') === 'approved' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning-emphasis' ?> rounded-pill text-capitalize">
                                        <?= htmlspecialchars($seller->status ?? 'pending') ?>
                                    </span>
                                </td>

                                <td>

                                    <span class="badge <?= BadgeCalculationService::getBadgeColor($seller->verification_badge) ?> rounded-pill px-3 py-2" title="<?= htmlspecialchars(BadgeCalculationService::getBadgeLabel($seller->verification_badge)) ?>" aria-label="<?= htmlspecialchars(BadgeCalculationService::getBadgeLabel($seller->verification_badge)) ?>">

                                        <i class="bi <?= BadgeCalculationService::getBadgeIcon($seller->verification_badge) ?>"></i>

                                    </span>

                                </td>

                                <!-- TRUST SCORE -->

                                <td>

                                    <div class="d-flex align-items-center gap-2">

                                        <div class="progress" style="width: 60px; height: 6px;">

                                            <div class="progress-bar bg-success" style="width: <?= $seller->trust_score ?? 0 ?>%"></div>

                                        </div>

                                        <span class="fw-bold"><?= $seller->trust_score ?? 0 ?>%</span>

                                    </div>

                                </td>

                                <!-- COMPLETED ORDERS -->

                                <td>

                                    <span class="fw-semibold"><?= $seller->completed_orders ?? 0 ?></span>

                                </td>

                                <!-- COMPLAINTS -->

                                <td>

                                    <span class="fw-semibold <?= ($seller->unresolved_complaints ?? 0) > 0 ? 'text-danger' : 'text-success' ?>">

                                        <?= $seller->unresolved_complaints ?? 0 ?> / <?= $seller->total_complaints ?? 0 ?>

                                    </span>

                                </td>

                                <!-- HARVEST PHOTOS -->

                                <td>

                                    <span class="fw-semibold"><?= $seller->products_with_harvest ?? 0 ?></span>

                                </td>

                                <!-- ACTIONS -->

                                <td>

                                    <div class="dropdown">

                                        <button class="btn btn-sm btn-outline-secondary rounded-pill dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" title="Manage this farmer's verification">

                                            <i class="bi bi-sliders me-1"></i>Actions

                                        </button>

                                        <ul class="dropdown-menu dropdown-menu-end border-0 shadow dropdown-menu-admin">

                                            <?php if (($seller->status ?? 'pending') === 'pending'): ?>

                                                <li>
                                                    <form method="POST" action="/ani-mart/public/admin/verifications/approve-shop">
                                                        <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                                                        <input type="hidden" name="seller_id" value="<?= (int) $seller->id ?>">
                                                        <button type="submit" class="dropdown-item text-success">
                                                            <i class="bi bi-check-circle me-2"></i>Approve Shop
                                                        </button>
                                                    </form>
                                                </li>

                                                <li><hr class="dropdown-divider"></li>

                                            <?php endif; ?>

                                            <li>

                                                <a class="dropdown-item" href="/ani-mart/public/admin/verifications/view?id=<?= $seller->id ?>">

                                                    <i class="bi bi-eye me-2"></i>View Details

                                                </a>

                                            </li>

                                            <li><hr class="dropdown-divider"></li>

                                            <li>

                                                <h6 class="dropdown-header">Promote To</h6>

                                            </li>

                                            <?php if($seller->verification_badge !== 'trusted'): ?>

                                                <li>

                                                    <form method="POST" action="/ani-mart/public/admin/verifications/promote">

                                                        <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                                                        <input type="hidden" name="seller_id" value="<?= $seller->id ?>">

                                                        <input type="hidden" name="badge" value="trusted">

                                                        <button type="submit" class="dropdown-item">

                                                            <i class="bi bi-shield-check-fill me-2 text-primary"></i>Trusted

                                                        </button>

                                                    </form>

                                                </li>

                                            <?php endif; ?>

                                            <?php if($seller->verification_badge !== 'verified'): ?>

                                                <li>

                                                    <form method="POST" action="/ani-mart/public/admin/verifications/promote">

                                                        <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                                                        <input type="hidden" name="seller_id" value="<?= $seller->id ?>">

                                                        <input type="hidden" name="badge" value="verified">

                                                        <button type="submit" class="dropdown-item">

                                                            <i class="bi bi-patch-check-fill me-2 text-success"></i>Verified

                                                        </button>

                                                    </form>

                                                </li>

                                            <?php endif; ?>

                                            <li><hr class="dropdown-divider"></li>

                                            <li>

                                                <h6 class="dropdown-header">Demote To</h6>

                                            </li>

                                            <?php if($seller->verification_badge !== 'new'): ?>

                                                <li>

                                                    <form method="POST" action="/ani-mart/public/admin/verifications/demote">

                                                        <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                                                        <input type="hidden" name="seller_id" value="<?= $seller->id ?>">

                                                        <input type="hidden" name="badge" value="new">

                                                        <button type="submit" class="dropdown-item text-danger">

                                                            <i class="bi bi-person-dash me-2"></i>New

                                                        </button>

                                                    </form>

                                                </li>

                                            <?php endif; ?>

                                            <?php if($seller->verification_badge !== 'verified'): ?>

                                                <li>

                                                    <form method="POST" action="/ani-mart/public/admin/verifications/demote">

                                                        <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                                                        <input type="hidden" name="seller_id" value="<?= $seller->id ?>">

                                                        <input type="hidden" name="badge" value="verified">

                                                        <button type="submit" class="dropdown-item">

                                                            <i class="bi bi-patch-check-fill me-2 text-success"></i>Verified

                                                        </button>

                                                    </form>

                                                </li>

                                            <?php endif; ?>

                                            <?php if($seller->verification_badge !== 'trusted'): ?>

                                                <li>

                                                    <form method="POST" action="/ani-mart/public/admin/verifications/demote">

                                                        <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                                                        <input type="hidden" name="seller_id" value="<?= $seller->id ?>">

                                                        <input type="hidden" name="badge" value="trusted">

                                                        <button type="submit" class="dropdown-item">

                                                            <i class="bi bi-shield-check-fill me-2 text-primary"></i>Trusted

                                                        </button>

                                                    </form>

                                                </li>

                                            <?php endif; ?>

                                            <li><hr class="dropdown-divider"></li>

                                            <li>

                                                <form method="POST" action="/ani-mart/public/admin/verifications/recalculate-single">

                                                    <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                                                    <input type="hidden" name="seller_id" value="<?= $seller->id ?>">

                                                    <button type="submit" class="dropdown-item">

                                                        <i class="bi bi-arrow-clockwise me-2"></i>Recalculate Badge

                                                    </button>

                                                </form>

                                            </li>

                                        </ul>

                                    </div>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>

                            <td colspan="9" class="text-center py-5">

                                <i class="bi bi-inbox fs-1 text-muted mb-3 d-block"></i>

                                <p class="text-muted">No farmers found</p>

                            </td>

                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>

</div>



<?php

$content = ob_get_clean();

require ANI_MART_ROOT . '/app/Views/layouts/main.php';
