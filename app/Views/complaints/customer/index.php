<?php

$title = 'My Disputes';
$bodyClass = 'customer-complaints-page';
$complaints = $complaints ?? [];
$pageStyles[] = '/ani-mart/public/assets/css/pages/customer-complaints.css?v=' .
    filemtime(__DIR__ . '/../../../../public/assets/css/pages/customer-complaints.css');

ob_start();

?>

<main class="container app-page-spacing customer-complaints-shell">
    <section class="dashboard-hero mb-5" aria-labelledby="customer-disputes-title">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <span class="hero-badge">Trust & Resolution Center</span>
                <h1 class="hero-title mt-4" id="customer-disputes-title">My Disputes</h1>
                <p class="hero-subtitle mt-3">Track your filed disputes and stay informed while the resolution team reviews each case.</p>
                <div class="customer-complaint-hero-actions">
                    <a href="/ani-mart/public/orders" class="hero-back-link">
                        <i class="bi bi-arrow-left" aria-hidden="true"></i> Customer orders
                    </a>
                    <a href="/ani-mart/public/customer/complaints/create" class="btn btn-light">
                        <i class="bi bi-plus-circle" aria-hidden="true"></i> File a new dispute
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section class="card border-0 shadow-sm rounded-5 customer-complaints-card" aria-labelledby="dispute-list-title">
        <div class="card-body p-4">
            <header class="customer-complaints-heading">
                <div>
                    <span class="section-badge">Resolution history</span>
                    <h2 class="section-title mb-0" id="dispute-list-title">Your dispute records</h2>
                    <p>Review the latest status and open any case for full details.</p>
                </div>
                <span class="badge bg-success rounded-pill px-3 py-2"><?= count($complaints) ?> item(s)</span>
            </header>

            <?php if ($complaints): ?>
                <div class="table-responsive customer-complaints-table-wrap">
                    <table class="table align-middle mb-0 customer-complaints-table">
                        <thead>
                            <tr>
                                <th>Dispute</th>
                                <th>Order</th>
                                <th>Category</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Updated</th>
                                <th><span class="visually-hidden">Action</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($complaints as $complaint): ?>
                                <tr>
                                    <td data-label="Dispute"><strong>#<?= (int) $complaint->id ?></strong></td>
                                    <td data-label="Order"><?= htmlspecialchars($complaint->order_number ?? 'N/A') ?></td>
                                    <td data-label="Category"><?= htmlspecialchars($complaint->category) ?></td>
                                    <td data-label="Priority"><span class="priority-label priority-<?= htmlspecialchars(strtolower((string) $complaint->priority)) ?>"><?= htmlspecialchars(ucfirst($complaint->priority)) ?></span></td>
                                    <td data-label="Status">
                                        <span class="badge <?= e(\App\Services\StatusPresenter::badgeClass($complaint->status)) ?> rounded-pill px-3 py-2 text-capitalize">
                                            <?= e(\App\Services\StatusPresenter::label($complaint->status)) ?>
                                        </span>
                                    </td>
                                    <td data-label="Updated"><?= date('M d, Y', strtotime($complaint->updated_at ?? $complaint->created_at ?? 'now')) ?></td>
                                    <td data-label="Action">
                                        <a href="/ani-mart/public/customer/complaints/show?id=<?= (int) $complaint->id ?>" class="btn btn-sm btn-outline-success rounded-pill">View details</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="customer-complaints-empty">
                    <span><i class="bi bi-shield-check" aria-hidden="true"></i></span>
                    <h3>No disputes found</h3>
                    <p>You can file a dispute once an eligible order has been delivered or completed.</p>
                    <a href="/ani-mart/public/customer/complaints/create" class="btn btn-success rounded-pill px-4">File your first dispute</a>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php
$content = ob_get_clean();
require ANI_MART_ROOT . '/app/Views/layouts/main.php';
?>
