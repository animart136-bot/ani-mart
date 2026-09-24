<?php

$title = 'Disputes Against My Farm';
$bodyClass = 'farmer-complaints-page';
$complaints = $complaints ?? [];
$seller = $seller ?? null;
$themes = \App\Services\SellerProfileService::themes();
$themeKey = $seller->shop_theme ?? 'fresh-farm';
$theme = $themes[$themeKey] ?? $themes['fresh-farm'];
$activeStatuses = ['open', 'pending', 'under_review', 'needs_more_evidence', 'waiting_farmer_response'];
$activeCount = count(array_filter($complaints, static fn ($complaint) => in_array($complaint->status ?? '', $activeStatuses, true)));
$responseCount = count(array_filter($complaints, static fn ($complaint) => ($complaint->status ?? '') === 'waiting_farmer_response'));

$pageStyles[] = '/ani-mart/public/assets/css/pages/farmer-complaints.css?v=' .
    filemtime(__DIR__ . '/../../../../public/assets/css/pages/farmer-complaints.css');

ob_start();
?>

<main
    class="container app-page-spacing farmer-complaints-shell"
    style="
        --complaints-accent: <?= htmlspecialchars($theme['accent']) ?>;
        --complaints-secondary: <?= htmlspecialchars($theme['secondary']) ?>;
        --complaints-background: <?= htmlspecialchars($theme['background']) ?>;
        --complaints-ink: <?= htmlspecialchars($theme['text']) ?>;
    ">
    <section class="hero farmer-complaints-hero mb-5" aria-labelledby="farmerComplaintsTitle">
        <div class="row align-items-center g-4">
            <div class="col-lg-8">
                <span class="hero-badge">
                    Trust &amp; Resolution Center
                </span>
                <h1 class="hero-title mt-3 mb-2" id="farmerComplaintsTitle">Disputes Against Your Farm</h1>
                <p class="hero-subtitle mb-0">
                    Review customer concerns, provide clear supporting details, and follow every case through resolution.
                </p>
                <a href="/ani-mart/public/seller-center" class="farmer-complaints-back-link">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i>
                    Seller Dashboard
                </a>
            </div>

            <div class="col-lg-4">
                <div class="farmer-complaints-hero-stats" aria-label="Dispute summary">
                    <div><span>Total cases</span><strong><?= count($complaints) ?></strong></div>
                    <div><span>Active</span><strong><?= $activeCount ?></strong></div>
                    <div><span>Need response</span><strong><?= $responseCount ?></strong></div>
                </div>
            </div>
        </div>
    </section>

    <section class="farmer-complaints-card" aria-labelledby="farmerComplaintRecordsTitle">
        <header class="farmer-complaints-heading">
            <div>
                <span class="farmer-complaints-eyebrow">Case management</span>
                <h2 id="farmerComplaintRecordsTitle">Complaint records</h2>
                <p>Open a case to review its evidence, conversation, and current resolution status.</p>
            </div>
            <span class="farmer-complaints-count"><?= count($complaints) ?> item(s)</span>
        </header>

        <?php if ($complaints): ?>
            <div class="table-responsive farmer-complaints-table-wrap">
                <table class="table align-middle mb-0 farmer-complaints-table">
                    <thead>
                        <tr>
                            <th>Dispute</th><th>Order</th><th>Shop</th><th>Customer</th>
                            <th>Category</th><th>Status</th><th>Updated</th>
                            <th><span class="visually-hidden">Action</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($complaints as $complaint): ?>
                            <tr>
                                <td data-label="Dispute"><strong>#<?= (int) $complaint->id ?></strong></td>
                                <td data-label="Order"><?= htmlspecialchars($complaint->order_number ?? 'N/A') ?></td>
                                <td data-label="Shop"><?= htmlspecialchars($complaint->shop_name ?? 'Your shop') ?></td>
                                <td data-label="Customer"><?= htmlspecialchars(trim(($complaint->customer_first_name ?? '') . ' ' . ($complaint->customer_last_name ?? ''))) ?></td>
                                <td data-label="Category"><?= htmlspecialchars($complaint->category ?? 'General') ?></td>
                                <td data-label="Status">
                                    <span class="badge <?= e(\App\Services\StatusPresenter::badgeClass($complaint->status)) ?> rounded-pill px-3 py-2 text-capitalize">
                                        <?= e(\App\Services\StatusPresenter::label($complaint->status)) ?>
                                    </span>
                                </td>
                                <td data-label="Updated"><?= date('M d, Y', strtotime($complaint->updated_at ?? $complaint->created_at ?? 'now')) ?></td>
                                <td data-label="Action">
                                    <a href="/ani-mart/public/farmer/complaints/show?id=<?= (int) $complaint->id ?>" class="btn btn-sm btn-outline-success rounded-pill">View details</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="farmer-complaints-empty">
                <span><i class="bi bi-shield-check" aria-hidden="true"></i></span>
                <h3>No disputes found</h3>
                <p>Customer disputes connected to your shops will appear here when they are submitted.</p>
                <a href="/ani-mart/public/seller-center" class="btn btn-success rounded-pill px-4">Return to Seller Dashboard</a>
            </div>
        <?php endif; ?>
    </section>
</main>

<?php
$content = ob_get_clean();
require ANI_MART_ROOT . '/app/Views/layouts/main.php';
?>
