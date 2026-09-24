<?php
$policy = $policy ?? [];
$policies = $policies ?? [];
$lastUpdated = $lastUpdated ?? 'N/A';
ob_start();
?>

<main class="policy-page container app-page-spacing">
    <div class="policy-breadcrumb"><a href="/ani-mart/public/policies">Marketplace Policies</a><i class="bi bi-chevron-right"></i><span><?= htmlspecialchars($policy['title']) ?></span></div>

    <div class="policy-layout">
        <aside class="policy-sidebar" aria-label="Policy navigation">
            <div class="policy-sidebar-title">Policy Center</div>
            <?php foreach ($policies as $slug => $navPolicy): ?>
                <a href="/ani-mart/public/policies/<?= urlencode($slug) ?>" class="<?= $slug === $policy['slug'] ? 'active' : '' ?>">
                    <i class="bi <?= htmlspecialchars($navPolicy['icon']) ?>"></i>
                    <?= htmlspecialchars($navPolicy['title']) ?>
                </a>
            <?php endforeach; ?>
        </aside>

        <article class="policy-document">
            <header>
                <span class="policy-document-icon"><i class="bi <?= htmlspecialchars($policy['icon']) ?>"></i></span>
                <div>
                    <h1><?= htmlspecialchars($policy['title']) ?></h1>
                    <p><?= htmlspecialchars($policy['summary']) ?></p>
                    <small>Effective and last updated <?= htmlspecialchars($lastUpdated) ?></small>
                </div>
            </header>

            <div class="policy-reading-note"><i class="bi bi-journal-text"></i><span>Please read this policy together with the other Ani-Mart marketplace policies and any mandatory rights under applicable law.</span></div>

            <?php foreach ($policy['sections'] as $section): ?>
                <section>
                    <h2><?= htmlspecialchars($section['title']) ?></h2>
                    <?php foreach (($section['paragraphs'] ?? []) as $paragraph): ?><p><?= htmlspecialchars($paragraph) ?></p><?php endforeach; ?>
                    <?php if (!empty($section['bullets'])): ?>
                        <ul><?php foreach ($section['bullets'] as $bullet): ?><li><?= htmlspecialchars($bullet) ?></li><?php endforeach; ?></ul>
                    <?php endif; ?>
                </section>
            <?php endforeach; ?>

            <?php if (!empty($policy['sources'])): ?>
                <section class="policy-sources">
                    <h2>Official references</h2>
                    <p>These references provide regulatory context. They do not replace the full law or professional advice.</p>
                    <?php foreach ($policy['sources'] as $source): ?><a href="<?= htmlspecialchars($source['url']) ?>" target="_blank" rel="noopener noreferrer"><i class="bi bi-box-arrow-up-right"></i><?= htmlspecialchars($source['label']) ?></a><?php endforeach; ?>
                </section>
            <?php endif; ?>

            <footer class="policy-document-footer"><span>Questions about a transaction should be raised through the relevant order, return, complaint, or reporting tool so the issue remains attached to its evidence and history.</span><a href="/ani-mart/public/policies">View all policies</a></footer>
        </article>
    </div>
</main>

<?php
$content = ob_get_clean();
require ANI_MART_ROOT . '/app/Views/layouts/main.php';
