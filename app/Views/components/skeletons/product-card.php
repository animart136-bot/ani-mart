<?php $count = max(1, (int) ($count ?? 1)); ?>
<?php for ($i = 0; $i < $count; $i++): ?>
    <article class="skeleton-card" aria-hidden="true">
        <span class="skeleton skeleton-image"></span>
        <div class="skeleton-stack">
            <span class="skeleton skeleton-badge"></span>
            <span class="skeleton skeleton-title"></span>
            <span class="skeleton skeleton-subtitle"></span>
            <span class="skeleton skeleton-price"></span>
        </div>
        <div class="skeleton-product-actions"><span class="skeleton skeleton-text skeleton-text-short"></span><span class="skeleton skeleton-button"></span></div>
    </article>
<?php endfor; ?>
