<?php $rows = max(1, (int) ($rows ?? 5)); ?>
<div class="skeleton-table" role="status" aria-label="Loading table" aria-busy="true">
    <?php for ($i = 0; $i < $rows; $i++): ?>
        <div class="skeleton-table-row" aria-hidden="true">
            <div class="skeleton-table-person"><span class="skeleton skeleton-avatar"></span><div class="skeleton-stack"><span class="skeleton skeleton-subtitle"></span><span class="skeleton skeleton-text skeleton-text-short"></span></div></div>
            <span class="skeleton skeleton-badge"></span><span class="skeleton skeleton-text"></span><span class="skeleton skeleton-text"></span><span class="skeleton skeleton-button skeleton-table-action"></span>
        </div>
    <?php endfor; ?>
</div>
