<?php

$title = 'Community Reports';
$bodyClass = 'community-reports-page admin-community-reports';
$reports = $reports ?? [];
$pageStyles[] = '/ani-mart/public/assets/css/pages/community-reports.css?v=' .
    filemtime(__DIR__ . '/../../../public/assets/css/pages/community-reports.css');

ob_start();
?>

<main class="container app-page-spacing community-reports-shell">
    <?php render_component('page-hero', [
        'class' => 'admin-hero community-reports-hero',
        'id' => 'community-reports-title',
        'eyebrow' => 'Community Moderation',
        'title' => 'Reported Community Content',
        'subtitle' => 'Review flagged posts and comments, understand their context, and record a clear moderation decision.',
        'backHref' => app_url('dashboard'),
        'backLabel' => 'Admin dashboard',
        'stats' => [['label' => 'Pending review', 'value' => count($reports)]],
    ]); ?>

    <section class="card border-0 shadow-sm rounded-5 community-reports-panel">
        <div class="card-body p-4">
            <header class="community-reports-panel-heading">
                <div>
                    <span class="section-badge">Moderation queue</span>
                    <h2 class="section-title mb-0">Pending reports</h2>
                </div>
                <span class="badge bg-success rounded-pill px-3 py-2"><?= count($reports) ?> item(s)</span>
            </header>

        <?php if (empty($reports)): ?>
        <div class="community-reports-empty text-center" aria-live="polite">
            <span class="community-empty-icon"><i class="bi bi-check2-circle" aria-hidden="true"></i></span>
            <h2>No pending reports</h2>
            <p>All reported community content has been reviewed.</p>
            <a href="/ani-mart/public/community" class="btn btn-outline-success">View Community</a>
        </div>
        <?php else: ?>
        <section class="community-report-list" aria-label="Pending community reports">
            <?php foreach ($reports as $report): ?>
                <?php
                $isComment = !empty($report->comment_id);
                $contentLabel = $isComment ? 'Reported comment' : 'Reported post';
                $contentText = $isComment ? ($report->comment_content ?? '') : ($report->post_content ?? '');
                $contentAuthor = $isComment
                    ? trim(($report->comment_author_first_name ?? '') . ' ' . ($report->comment_author_last_name ?? ''))
                    : trim(($report->post_author_first_name ?? '') . ' ' . ($report->post_author_last_name ?? ''));
                $reporterName = trim(($report->reporter_first_name ?? '') . ' ' . ($report->reporter_last_name ?? '')) ?: 'User #' . (int) $report->user_id;
                ?>
                <article class="card community-report-card">
                    <header class="community-report-heading">
                        <div>
                            <span class="community-report-eyebrow">Report #<?= (int) $report->id ?></span>
                            <h2><?= htmlspecialchars($report->reason) ?></h2>
                        </div>
                        <time datetime="<?= htmlspecialchars($report->created_at) ?>">
                            <?= date('M j, Y · g:i A', strtotime($report->created_at)) ?>
                        </time>
                    </header>

                    <div class="community-report-meta">
                        <div><span>Reported by</span><strong><?= htmlspecialchars($reporterName) ?></strong></div>
                        <div><span>Content type</span><strong><?= $isComment ? 'Comment' : 'Post' ?></strong></div>
                        <div><span>Content author</span><strong><?= htmlspecialchars($contentAuthor ?: 'Unknown user') ?></strong></div>
                    </div>

                    <div class="community-reported-content">
                        <div>
                            <span><?= $contentLabel ?></span>
                            <?php if (!$isComment && !empty($report->post_title)): ?>
                                <strong><?= htmlspecialchars($report->post_title) ?></strong>
                            <?php endif; ?>
                        </div>
                        <p><?= nl2br(htmlspecialchars($contentText !== '' ? $contentText : 'The reported content is no longer available.')) ?></p>
                    </div>

                    <footer class="community-report-actions">
                        <?php if (!empty($report->post_id)): ?>
                            <a href="/ani-mart/public/community/show?id=<?= (int) $report->post_id ?><?= $isComment ? '#comment' . (int) $report->comment_id : '' ?>" class="btn btn-outline-success">
                                <i class="bi bi-eye me-1" aria-hidden="true"></i> View in Community
                            </a>
                        <?php endif; ?>
                        <div class="community-report-decisions">
                            <form method="POST" action="/ani-mart/public/community/reports/moderate">
                                <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                                <input type="hidden" name="report_id" value="<?= (int) $report->id ?>">
                                <input type="hidden" name="action" value="dismissed">
                                <button class="btn btn-outline-secondary">Dismiss</button>
                            </form>
                            <form method="POST" action="/ani-mart/public/community/reports/moderate">
                                <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                                <input type="hidden" name="report_id" value="<?= (int) $report->id ?>">
                                <input type="hidden" name="action" value="reviewed">
                                <button class="btn btn-success"><i class="bi bi-check2 me-1" aria-hidden="true"></i> Mark Reviewed</button>
                            </form>
                        </div>
                    </footer>
                </article>
            <?php endforeach; ?>
        </section>
        <?php endif; ?>
        </div>
    </section>
</main>

<?php
$content = ob_get_clean();
require ANI_MART_ROOT . '/app/Views/layouts/main.php';
