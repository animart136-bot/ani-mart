<?php

$title = 'Dispute #' . (int) $complaint->id;
$bodyClass = 'farmer-complaint-show-page';
$seller = $seller ?? null;
$evidence = $evidence ?? [];
$replies = $replies ?? [];
$themes = \App\Services\SellerProfileService::themes();
$themeKey = $seller->shop_theme ?? 'fresh-farm';
$theme = $themes[$themeKey] ?? $themes['fresh-farm'];
$statusLabel = \App\Services\StatusPresenter::label($complaint->status);

$pageStyles[] = '/ani-mart/public/assets/css/pages/farmer-complaints.css?v=' .
    filemtime(__DIR__ . '/../../../../public/assets/css/pages/farmer-complaints.css');

ob_start();
?>

<main
    class="container app-page-spacing farmer-complaints-shell farmer-complaint-detail-shell"
    style="
        --complaints-accent: <?= htmlspecialchars($theme['accent']) ?>;
        --complaints-secondary: <?= htmlspecialchars($theme['secondary']) ?>;
        --complaints-background: <?= htmlspecialchars($theme['background']) ?>;
        --complaints-ink: <?= htmlspecialchars($theme['text']) ?>;
    ">
    <section class="hero farmer-complaints-hero farmer-complaint-detail-hero mb-5" aria-labelledby="farmerComplaintTitle">
        <div class="row align-items-center g-4">
            <div class="col-lg-8">
                <span class="hero-badge">
                    Dispute #<?= (int) $complaint->id ?>
                </span>
                <h1 class="hero-title mt-3 mb-2" id="farmerComplaintTitle">Review the complaint</h1>
                <p class="hero-subtitle mb-0">
                    Order <?= htmlspecialchars($complaint->order_number ?? 'N/A') ?> ·
                    <?= htmlspecialchars($complaint->shop_name ?? 'Your shop') ?> ·
                    Filed <?= date('F j, Y', strtotime($complaint->created_at ?? 'now')) ?>
                </p>
                <a href="/ani-mart/public/farmer/complaints" class="farmer-complaints-back-link">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i>
                    Back to disputes
                </a>
            </div>
            <div class="col-lg-4 text-lg-end">
                <span class="farmer-complaint-hero-status <?= e(\App\Services\StatusPresenter::badgeClass($complaint->status)) ?>">
                    <i class="bi bi-record-circle" aria-hidden="true"></i>
                    <?= htmlspecialchars($statusLabel) ?>
                </span>
            </div>
        </div>
    </section>

    <div class="row g-4 align-items-start farmer-complaint-layout">
        <div class="col-lg-8">
            <article class="farmer-complaint-card farmer-complaint-case-card mb-4" aria-labelledby="complaintOverviewTitle">
                <header class="farmer-complaint-card-heading">
                    <div>
                        <span class="farmer-complaints-eyebrow">Case overview</span>
                        <h2 id="complaintOverviewTitle">Customer complaint</h2>
                    </div>
                    <span class="badge <?= e(\App\Services\StatusPresenter::badgeClass($complaint->status)) ?> rounded-pill px-3 py-2 text-capitalize">
                        <?= htmlspecialchars($statusLabel) ?>
                    </span>
                </header>

                <div class="farmer-complaint-facts">
                    <div><small>Category</small><strong><?= htmlspecialchars($complaint->category ?? 'General') ?></strong></div>
                    <div><small>Priority</small><strong><?= htmlspecialchars(ucfirst((string) ($complaint->priority ?? 'normal'))) ?></strong></div>
                    <div><small>Customer</small><strong><?= htmlspecialchars(trim(($complaint->customer_first_name ?? '') . ' ' . ($complaint->customer_last_name ?? ''))) ?></strong></div>
                </div>

                <section class="farmer-complaint-section">
                    <h3>Description</h3>
                    <p><?= nl2br(htmlspecialchars($complaint->description ?? 'No description supplied.')) ?></p>
                </section>

                <?php if (!empty($complaint->admin_notes)): ?>
                    <section class="farmer-complaint-section farmer-complaint-admin-note">
                        <h3><i class="bi bi-clipboard-check" aria-hidden="true"></i> Investigation notes</h3>
                        <p><?= nl2br(htmlspecialchars($complaint->admin_notes)) ?></p>
                    </section>
                <?php endif; ?>

                <?php if ($evidence): ?>
                    <section class="farmer-complaint-section">
                        <div class="farmer-complaint-section-heading">
                            <h3>Evidence</h3>
                            <span><?= count($evidence) ?> file(s)</span>
                        </div>
                        <div class="row g-3">
                            <?php foreach ($evidence as $item): ?>
                                <div class="col-sm-6 col-xl-4">
                                    <figure class="farmer-complaint-evidence">
                                        <img src="<?= htmlspecialchars($item->image_url) ?>" alt="Evidence submitted for dispute #<?= (int) $complaint->id ?>" data-image-modal="true">
                                        <figcaption>Uploaded <?= date('M d, Y', strtotime($item->created_at)) ?></figcaption>
                                    </figure>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>

                <section class="farmer-complaint-section farmer-complaint-conversation" aria-labelledby="conversationTitle">
                    <div class="farmer-complaint-section-heading">
                        <h3 id="conversationTitle">Conversation</h3>
                        <span><?= count($replies) ?> response(s)</span>
                    </div>
                    <?php if ($replies): ?>
                        <div class="farmer-complaint-replies">
                            <?php foreach ($replies as $reply): ?>
                                <article class="farmer-complaint-reply">
                                    <div class="farmer-complaint-reply-avatar" aria-hidden="true">
                                        <?= htmlspecialchars(strtoupper(substr((string) ($reply->first_name ?? 'U'), 0, 1))) ?>
                                    </div>
                                    <div>
                                        <header>
                                            <div>
                                                <strong><?= htmlspecialchars(trim(($reply->first_name ?? '') . ' ' . ($reply->last_name ?? ''))) ?></strong>
                                                <span><?= htmlspecialchars(ucfirst((string) ($reply->role ?? 'User'))) ?></span>
                                            </div>
                                            <time datetime="<?= htmlspecialchars(date('c', strtotime($reply->created_at))) ?>"><?= date('M d, Y · g:i A', strtotime($reply->created_at)) ?></time>
                                        </header>
                                        <p><?= nl2br(htmlspecialchars($reply->message)) ?></p>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="farmer-complaint-no-replies">
                            <i class="bi bi-chat-left-text" aria-hidden="true"></i>
                            <div><strong>No responses yet</strong><span>Add a clear response below to help move this case forward.</span></div>
                        </div>
                    <?php endif; ?>
                </section>
            </article>

            <section class="farmer-complaint-card farmer-complaint-response-card" aria-labelledby="responseTitle">
                <div class="farmer-complaint-card-heading">
                    <div>
                        <span class="farmer-complaints-eyebrow">Your response</span>
                        <h2 id="responseTitle">Add supporting details</h2>
                        <p>Keep your message factual and focused on resolving the customer’s concern.</p>
                    </div>
                </div>
                <form action="/ani-mart/public/complaints/replies/store" method="POST">
                    <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                    <input type="hidden" name="complaint_id" value="<?= (int) $complaint->id ?>">
                    <label for="farmerComplaintResponse" class="form-label fw-semibold">Response message</label>
                    <textarea id="farmerComplaintResponse" name="message" class="form-control" rows="5" maxlength="1000" placeholder="Explain what happened and provide any relevant details..." required></textarea>
                    <div class="farmer-complaint-response-footer">
                        <small>Maximum 1,000 characters</small>
                        <button type="submit" class="btn btn-success rounded-pill px-4">
                            <i class="bi bi-send me-2" aria-hidden="true"></i>Post response
                        </button>
                    </div>
                </form>
            </section>
        </div>

        <aside class="col-lg-4 farmer-complaint-sidebar">
            <section class="farmer-complaint-card mb-4" aria-labelledby="caseInformationTitle">
                <div class="farmer-complaint-card-heading farmer-complaint-sidebar-heading">
                    <div><span class="farmer-complaints-eyebrow">Summary</span><h2 id="caseInformationTitle">Case information</h2></div>
                </div>
                <dl class="farmer-complaint-summary-list">
                    <div><dt>Order number</dt><dd><?= htmlspecialchars($complaint->order_number ?? 'N/A') ?></dd></div>
                    <div><dt>Order total</dt><dd>₱<?= number_format((float) ($complaint->total_amount ?? 0), 2) ?></dd></div>
                    <div><dt>Current status</dt><dd><?= htmlspecialchars($statusLabel) ?></dd></div>
                    <div><dt>Priority</dt><dd><?= htmlspecialchars(ucfirst((string) ($complaint->priority ?? 'normal'))) ?></dd></div>
                    <div><dt>Last updated</dt><dd><?= date('M d, Y', strtotime($complaint->updated_at ?? $complaint->created_at ?? 'now')) ?></dd></div>
                </dl>
            </section>

            <section class="farmer-complaint-card farmer-complaint-next-steps" aria-labelledby="nextStepsTitle">
                <div class="farmer-complaint-card-heading farmer-complaint-sidebar-heading">
                    <div><span class="farmer-complaints-eyebrow">Guidance</span><h2 id="nextStepsTitle">Next steps</h2></div>
                </div>
                <ul>
                    <li><i class="bi bi-check-circle-fill" aria-hidden="true"></i><span>Keep every response clear, respectful, and factual.</span></li>
                    <li><i class="bi bi-check-circle-fill" aria-hidden="true"></i><span>Provide additional evidence when the resolution team requests it.</span></li>
                    <li><i class="bi bi-check-circle-fill" aria-hidden="true"></i><span>Monitor this case until its status is resolved or closed.</span></li>
                </ul>
            </section>
        </aside>
    </div>
</main>

<?php
$content = ob_get_clean();
require ANI_MART_ROOT . '/app/Views/layouts/main.php';
?>
