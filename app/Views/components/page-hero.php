<?php
$heroClass = trim('hero shared-page-hero ' . ($class ?? ''));
$heroId = $id ?? ('page-hero-' . substr(sha1((string) ($title ?? 'Ani-Mart')), 0, 8));
$actions = $actions ?? [];
$stats = $stats ?? [];
?>
<section class="<?= e($heroClass) ?>" aria-labelledby="<?= e($heroId) ?>">
    <div class="shared-page-hero__copy">
        <?php if (!empty($eyebrow)): ?>
            <span class="hero-badge"><?= e($eyebrow) ?></span>
        <?php endif; ?>
        <h1 class="hero-title" id="<?= e($heroId) ?>"><?= e($title ?? '') ?></h1>
        <?php if (!empty($subtitle)): ?>
            <p class="hero-subtitle"><?= e($subtitle) ?></p>
        <?php endif; ?>

        <?php if (!empty($backHref)): ?>
            <a class="shared-page-hero__back" href="<?= e($backHref) ?>">
                <i class="bi bi-arrow-left" aria-hidden="true"></i>
                <?= e($backLabel ?? 'Back') ?>
            </a>
        <?php endif; ?>
    </div>

    <?php if ($stats || $actions): ?>
        <div class="shared-page-hero__aside">
            <?php foreach ($stats as $stat): ?>
                <div class="shared-page-hero__stat">
                    <span><?= e($stat['label'] ?? '') ?></span>
                    <strong><?= e($stat['value'] ?? '') ?></strong>
                </div>
            <?php endforeach; ?>
            <?php foreach ($actions as $action): ?>
                <a class="btn <?= e($action['class'] ?? 'btn-light') ?>" href="<?= e($action['href'] ?? '#') ?>">
                    <?php if (!empty($action['icon'])): ?><i class="bi <?= e($action['icon']) ?>" aria-hidden="true"></i><?php endif; ?>
                    <?= e($action['label'] ?? '') ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
