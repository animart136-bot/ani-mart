<?php $statCount = max(1, (int) ($statCount ?? 4)); ?>
<section class="skeleton-stack" aria-label="Loading dashboard" aria-busy="true">
    <div class="skeleton-card skeleton-hero"><div class="skeleton-stack"><span class="skeleton skeleton-badge"></span><span class="skeleton skeleton-title"></span><span class="skeleton skeleton-text"></span></div><div class="skeleton-hero-actions"><span class="skeleton skeleton-button"></span></div></div>
    <div class="skeleton-grid"><?php for ($i = 0; $i < $statCount; $i++): ?><div class="skeleton-stat-card"><span class="skeleton skeleton-stat-icon"></span><div class="skeleton-stat-copy"><span class="skeleton skeleton-title"></span><span class="skeleton skeleton-subtitle"></span></div></div><?php endfor; ?></div>
    <div class="row g-4"><div class="col-lg-7"><?php require __DIR__ . '/analytics.php'; ?></div><div class="col-lg-5"><?php $rows = 4; require __DIR__ . '/orders.php'; ?></div></div>
</section>
