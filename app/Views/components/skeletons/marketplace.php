<?php $productCount = max(1, (int) ($productCount ?? 8)); ?>
<section class="skeleton-stack" aria-label="Loading marketplace" aria-busy="true">
    <div class="skeleton-card skeleton-hero"><div class="skeleton-stack"><span class="skeleton skeleton-badge"></span><span class="skeleton skeleton-title"></span><span class="skeleton skeleton-text"></span><span class="skeleton skeleton-text skeleton-text-short"></span></div><div class="skeleton-hero-actions"><span class="skeleton skeleton-button"></span><span class="skeleton skeleton-button"></span></div></div>
    <span class="skeleton skeleton-search"></span>
    <div class="skeleton-filter-list"><?php for ($i = 0; $i < 6; $i++): ?><span class="skeleton skeleton-filter"></span><?php endfor; ?></div>
    <div class="skeleton-grid"><?php $count = $productCount; require __DIR__ . '/product-card.php'; ?></div>
    <div class="skeleton-pagination"><?php for ($i = 0; $i < 5; $i++): ?><span class="skeleton"></span><?php endfor; ?></div>
</section>
