<?php

$title = "Farmers";

ob_start();

?>

<div class="container app-page-spacing">

<?php render_component('page-hero', [
    'class' => 'admin-hero',
    'eyebrow' => 'Ani-Mart Administration',
    'title' => 'Approved Farmers',
    'subtitle' => 'Manage verified marketplace sellers, monitor farm shops, and oversee the growing Ani-Mart farmer community.',
    'backHref' => app_url('dashboard'),
    'backLabel' => 'Admin dashboard',
    'stats' => [['label' => 'Approved farmers', 'value' => count($farmers)]],
]); ?>
    <!-- CARD -->

    <div class="card border-0 shadow-sm rounded-5">

        <div class="card-body p-4">

            <div class="table-responsive">

                <table class="table align-middle">

                    <thead>

                        <tr>

                            <th>ID</th>

                            <th>Farmer</th>

                            <th>Email</th>

                            <th>Shop Name</th>

                            <th>Trust Score</th>

                            <th>Risk Score</th>

                            <th>Created</th>

                            <th>Status</th>

                        </tr>

                    </thead>

                    <tbody>

                        <?php if(count($farmers)): ?>

                            <?php foreach($farmers as $farmer): ?>

                                <tr id="farmer-<?= (int) $farmer->id ?>">

                                    <!-- ID -->

                                    <td>

                                        #<?= $farmer->id ?>

                                    </td>

                                    <!-- NAME -->

                                    <td>

                                        <div
                                            class="d-flex align-items-center gap-3"
                                        >

                                            <div class="farmer-avatar">

                                                <?= strtoupper(substr($farmer->first_name ?? 'F', 0, 1)) ?>

                                            </div>

                                            <div>

                                                <h6 class="fw-bold mb-0">

                                                    <?= htmlspecialchars(
                                                        ($farmer->first_name ?? '')
                                                        . ' ' .
                                                        ($farmer->last_name ?? '')
                                                    ) ?>

                                                </h6>

                                                <small class="text-muted">

                                                    Farmer Seller

                                                </small>

                                            </div>

                                        </div>

                                    </td>

                                    <!-- EMAIL -->

                                    <td>

                                        <?= htmlspecialchars(
                                            $farmer->email ?? ''
                                        ) ?>

                                    </td>

                                    <!-- SHOP -->

                                    <td>

                                        <span class="fw-semibold">

                                            <?= htmlspecialchars(
                                                $farmer->shop_name ?? 'No Shop'
                                            ) ?>

                                        </span>

                                    </td>

                                    <!-- TRUST -->

                                    <td>

                                        <span
                                            class="badge <?= ($farmer->trust_score ?? 0) < 80 ? 'bg-warning text-dark' : 'bg-success' ?> px-3 py-2"
                                        >

                                            <?= (int) ($farmer->trust_score ?? 0) ?>

                                        </span>

                                    </td>

                                    <!-- RISK -->

                                    <td>

                                        <span
                                            class="badge <?= ($farmer->risk_level ?? 'low') === 'high' ? 'bg-danger' : (($farmer->risk_level ?? 'low') === 'medium' ? 'bg-warning text-dark' : 'bg-success') ?> px-3 py-2"
                                        >

                                            <?= (int) ($farmer->risk_score ?? 0) ?>

                                        </span>

                                    </td>

                                    <!-- DATE -->

                                    <td>

                                        <?= date(
                                            'M d, Y',
                                            strtotime(
                                                $farmer->created_at
                                            )
                                        ) ?>

                                    </td>

                                    <!-- STATUS -->

                                    <td>

                                        <span
                                            class="badge bg-success px-3 py-2"
                                        >

                                            Approved

                                        </span>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>

                                <td colspan="8">

                                    <div class="text-center py-5">

                                        <i
                                            class="bi bi-shop"
                                            style="font-size:3rem;"
                                        ></i>

                                        <h5 class="mt-3">

                                            No Farmers Found

                                        </h5>

                                        <p class="text-muted">

                                            No registered sellers yet.

                                        </p>

                                    </div>

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

?>
