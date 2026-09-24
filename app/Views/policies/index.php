<?php
$lastUpdated = $lastUpdated ?? 'N/A';
$policies = $policies ?? [];
ob_start();
?>

<main class="policy-page container app-page-spacing">
    <section class="policy-hero">
        <span class="policy-kicker"><i class="bi bi-shield-check"></i> Trust, safety, and transparency</span>
        <h1>Marketplace Policies</h1>
        <p>Clear expectations for customers and sellers using Ani-Mart.</p>
        <div class="policy-updated">Last updated <?= htmlspecialchars($lastUpdated) ?></div>
    </section>

    <section class="policy-card-grid" aria-label="Available marketplace policies">
        <?php foreach ($policies as $slug => $policy): ?>
            <a href="/ani-mart/public/policies/<?= urlencode($slug) ?>" class="policy-card">
                <span class="policy-card-icon"><i class="bi <?= htmlspecialchars($policy['icon']) ?>"></i></span>
                <span>
                    <strong><?= htmlspecialchars($policy['title']) ?></strong>
                    <small><?= htmlspecialchars($policy['summary']) ?></small>
                </span>
                <i class="bi bi-arrow-right policy-card-arrow"></i>
            </a>
        <?php endforeach; ?>
    </section>

    <section class="policy-note">
        <i class="bi bi-info-circle"></i>
        <div><strong>How these policies work together</strong><p>The Terms and Conditions apply to all use of Ani-Mart. The other policies add specific requirements for personal information, sellers, products, cancellations, returns, and refunds. Applicable law controls if it provides rights that cannot be limited by these policies.</p></div>
    </section>
</main>

<?php
$content = ob_get_clean();
require ANI_MART_ROOT . '/app/Views/layouts/main.php';
