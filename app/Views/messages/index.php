<?php

$title = 'Messages';
$search = $search ?? '';
$peopleSearch = $peopleSearch ?? '';
$people = $people ?? [];
$archived = $archived ?? 0;
$role = $role ?? '';

ob_start();
?>

<div class="messages-list-page container app-page-spacing">

        <?php render_component('page-hero', [
            'class' => 'messages-hero',
            'eyebrow' => 'Messages',
            'title' => 'Your conversations',
            'subtitle' => 'Message any active Ani-Mart account, or continue a product and order conversation.',
            'actions' => [
                ['href' => app_url('messages?archived=' . ($archived ? '0' : '1')), 'label' => $archived ? 'View Inbox' : 'View Archived', 'class' => 'btn-light'],
                ['href' => app_url('messages'), 'label' => 'Refresh', 'class' => 'btn-outline-light'],
            ],
        ]); ?>

        <form class="row g-2 mb-4" action="/ani-mart/public/messages" method="GET">
            <div class="col-md-9">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input
                        type="search"
                        name="search"
                        value="<?= htmlspecialchars($search) ?>"
                        class="form-control modern-input"
                        aria-label="Search conversations"
                        placeholder="Search conversations, products, orders, or messages">
                </div>
            </div>
            <div class="col-md-3 d-grid">
                <button type="submit" class="btn btn-success">Search</button>
            </div>
        </form>

        <section class="card border-0 shadow-sm rounded-4 mb-4 message-people-card" aria-labelledby="startConversationTitle">
            <div class="message-people-header">
                <h2 class="h6 fw-bold mb-0" id="startConversationTitle">Start a conversation</h2>
                <form class="message-people-search" action="/ani-mart/public/messages" method="GET">
                    <label class="visually-hidden" for="peopleSearch">Search people</label>
                    <input id="peopleSearch" type="search" name="people" value="<?= htmlspecialchars($peopleSearch) ?>" class="form-control form-control-sm" placeholder="Search people">
                    <button type="submit" class="btn btn-sm btn-outline-success" aria-label="Search people">
                        <i class="bi bi-search" aria-hidden="true"></i>
                    </button>
                </form>
            </div>

            <?php if (empty($people)): ?>
                <p class="text-muted small mb-0 mt-3">No matching accounts found.</p>
            <?php else: ?>
                <div class="message-people-strip" aria-label="People you can message">
                    <?php foreach ($people as $person): ?>
                        <?php
                            $personName = trim((string) ($person->first_name ?? '') . ' ' . (string) ($person->last_name ?? '')) ?: 'Ani-Mart user';
                            $personRole = ucfirst(str_replace('_', ' ', (string) ($person->role ?? 'account')));
                        ?>
                        <a class="message-person-tile" href="/ani-mart/public/messages/start?user_id=<?= (int) $person->id ?>" aria-label="Message <?= htmlspecialchars($personName) ?>" title="Message <?= htmlspecialchars($personName) ?>">
                            <div class="message-person-avatar">
                                <?php if (!empty($person->profile_image)): ?>
                                    <img src="<?= htmlspecialchars($person->profile_image) ?>" alt="" data-image-modal="false">
                                <?php else: ?>
                                    <?= htmlspecialchars(strtoupper(substr($personName, 0, 1))) ?>
                                <?php endif; ?>
                                <span class="message-person-chat-badge" aria-hidden="true"><i class="bi bi-chat-fill"></i></span>
                            </div>
                            <span class="message-person-name"><?= htmlspecialchars($personName) ?></span>
                            <span class="message-person-role"><?= htmlspecialchars($personRole) ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <?php if (empty($conversations)): ?>

            <div class="empty-state py-5 text-center">
                <i class="bi bi-chat-dots fs-1 mb-3 text-muted"></i>
                <h3 class="mb-2">No conversations yet</h3>
                <p class="text-muted">Use the people finder above or start a conversation from a product or order.</p>
            </div>

        <?php else: ?>

            <div class="row g-4">

                <?php foreach ($conversations as $conversation): ?>

                    <?php
                        $isUnread = (int) ($conversation->unread_count ?? 0) > 0;
                        $label = trim((string) ($conversation->partner_name ?? ''));
                        $partnerRole = strtolower((string) ($conversation->partner_role ?? 'account'));
                        $partnerType = ucfirst(str_replace('_', ' ', $partnerRole));
                        $partnerTypeClass = $partnerRole === 'farmer' ? 'is-seller' : ($partnerRole === 'admin' ? 'is-admin' : 'is-customer');
                        $subject = \App\Services\MessageFormatter::conversationSubject($conversation);
                    ?>

                    <div class="col-12 conversation-list-shell">
                        <a href="/ani-mart/public/messages/chat?id=<?= $conversation->id ?>" class="conversation-card d-flex align-items-center gap-3 p-4 rounded-4 text-decoration-none">

                            <div class="conversation-avatar rounded-circle bg-success text-white d-flex align-items-center justify-content-center">
                                <i class="bi bi-chat-left-text"></i>
                            </div>

                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start gap-3">
                                    <div>
                                        <div class="conversation-name-row">
                                            <h5 class="mb-0 text-dark"><?= htmlspecialchars($label ?: 'Unknown user') ?></h5>
                                            <span class="conversation-party-badge <?= $partnerTypeClass ?>">
                                                <i class="bi <?= $partnerRole === 'farmer' ? 'bi-shop' : 'bi-person' ?>" aria-hidden="true"></i>
                                                <?= htmlspecialchars($partnerType) ?>
                                            </span>
                                        </div>
                                        <div class="text-muted small"><?= htmlspecialchars($subject) ?></div>
                                    </div>
                                    <?php if ($isUnread): ?>
                                        <span class="badge bg-success"><?= (int) $conversation->unread_count ?> new</span>
                                    <?php endif; ?>
                                </div>

                                <p class="conversation-preview text-muted mb-0 mt-3">
                                    <?= htmlspecialchars(mb_strimwidth(\App\Services\MessageFormatter::orderReference($conversation->last_message ?: 'No message yet', $conversation->order_number ?? null), 0, 80, '...')) ?>
                                </p>
                            </div>

                        </a>
                        <form
                            class="conversation-delete-form"
                            action="/ani-mart/public/messages/conversation/delete"
                            method="POST"
                            data-confirm="Are you sure you want to delete this entire conversation? All messages will be permanently deleted for both participants and cannot be recovered."
                            data-confirm-title="Delete conversation"
                            data-confirm-text="Delete conversation"
                            data-confirm-class="btn-danger">
                            <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                            <input type="hidden" name="conversation_id" value="<?= (int) $conversation->id ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger conversation-delete-button" aria-label="Delete conversation with <?= htmlspecialchars($label ?: 'this user') ?>" title="Delete conversation">
                                <i class="bi bi-trash3" aria-hidden="true"></i>
                                <span class="d-none d-sm-inline">Delete</span>
                            </button>
                        </form>
                    </div>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

</div>

<?php

$content = ob_get_clean();

require ANI_MART_ROOT . '/app/Views/layouts/main.php';
