<?php

$title = $title ?? 'Support Conversation';
$bodyClass = $bodyClass ?? 'admin-page';

$conversation = $conversation ?? null;
$messages = $messages ?? [];
$notes = $notes ?? [];
$templates = $templates ?? [];

ob_start();
?>

<div class="container app-page-spacing">

    <div class="admin-hero mb-5">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <span class="hero-badge">Conversation Details</span>
                <h1 class="hero-title mt-4">Support Ticket #<?= (int) ($conversation->id ?? 0) ?></h1>
                <p class="hero-subtitle mt-3">
                    Review and respond to messages, update status, or reassign this conversation.
                </p>
            </div>
            <div class="col-lg-4 text-center">
                <div class="hero-icon">
                    <i class="bi bi-chat-left-text"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-5 p-4 h-100">
                <h5 class="fw-bold mb-3">Conversation Summary</h5>
                <p><strong>Subject:</strong> <?= htmlspecialchars($conversation->subject ?? 'N/A') ?></p>
                <p><strong>Type:</strong> <?= htmlspecialchars(str_replace('_', ' ', $conversation->conversation_type ?? 'N/A')) ?></p>
                <p><strong>Status:</strong> <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $conversation->status ?? 'N/A'))) ?></p>
                <p><strong>Priority:</strong> <?= htmlspecialchars(ucfirst($conversation->priority ?? 'N/A')) ?></p>
                <p><strong>Assigned To:</strong> <?= htmlspecialchars(trim(($conversation->assigned_first_name ?? '') . ' ' . ($conversation->assigned_last_name ?? ''))) ?: 'Unassigned' ?></p>
                <p><strong>Last Updated:</strong> <?= htmlspecialchars(date('M d, Y H:i', strtotime($conversation->last_message_at ?? $conversation->created_at))) ?></p>

                <div class="mt-4">
                    <form method="POST" action="/ani-mart/public/admin/support/conversation/status">
                        <input type="hidden" name="conversation_id" value="<?= (int) $conversation->id ?>">
                        <div class="mb-3">
                            <label class="form-label">Update Status</label>
                            <select class="form-select" name="status">
                                <?php foreach (['open','pending','investigating','waiting_seller','waiting_customer','resolved','rejected'] as $status): ?>
                                    <option value="<?= $status ?>" <?= ($conversation->status === $status) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $status))) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 rounded-pill">Save Status</button>
                    </form>
                </div>

            </div>
        </div>
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-5 p-4 mb-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold mb-0">Message Thread</h5>
                    <span class="badge bg-secondary"><?= count($messages) ?> messages</span>
                </div>

                <div class="support-thread">
                    <?php if (count($messages)): ?>
                        <?php foreach ($messages as $message): ?>
                            <div class="support-message <?= $message->sender_role === 'admin' ? 'admin-message' : 'user-message' ?> mb-3 p-3 rounded-4">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <div>
                                        <strong><?= htmlspecialchars(trim(($message->first_name ?? '') . ' ' . ($message->last_name ?? '')) ?: ($message->sender_role === 'admin' ? 'Admin' : ucfirst($message->sender_role))) ?></strong>
                                        <small class="text-muted ms-2"><?= htmlspecialchars(date('M d, Y H:i', strtotime($message->created_at))) ?></small>
                                    </div>
                                    <span class="badge bg-<?= $message->sender_role === 'admin' ? 'primary' : 'secondary' ?>">
                                        <?= htmlspecialchars(ucfirst($message->sender_role)) ?>
                                    </span>
                                </div>
                                <div class="support-message-body">
                                    <?= nl2br(htmlspecialchars($message->message ?? '')) ?>
                                </div>
                                <?php if (!empty($message->attachment_url)): ?>
                                    <div class="mt-3">
                                        <a href="<?= htmlspecialchars($message->attachment_url) ?>" target="_blank" rel="noopener noreferrer" class="text-decoration-none">
                                            <i class="bi bi-paperclip"></i> View attachment
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center text-muted py-5">
                            No messages have been posted for this conversation yet.
                        </div>
                    <?php endif; ?>
                </div>

                <form method="POST" action="/ani-mart/public/admin/support/message/send" class="mt-4">
                    <input type="hidden" name="conversation_id" value="<?= (int) $conversation->id ?>">
                    <div class="mb-3">
                        <label class="form-label">Reply</label>
                        <textarea name="message" rows="4" class="form-control rounded-4" placeholder="Type your response..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Attachment URL</label>
                        <input type="url" name="attachment_url" class="form-control rounded-4" placeholder="https://...">
                    </div>
                    <button class="btn btn-primary rounded-pill">Send Reply</button>
                </form>
            </div>

            <div class="card border-0 shadow-sm rounded-5 p-4">
                <h5 class="fw-bold mb-3">Internal Notes</h5>
                <?php if (count($notes)): ?>
                    <?php foreach ($notes as $note): ?>
                        <div class="mb-3 p-3 rounded-4 bg-light">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <strong><?= htmlspecialchars(trim(($note->first_name ?? '') . ' ' . ($note->last_name ?? ''))) ?></strong>
                                <small class="text-muted"><?= htmlspecialchars(date('M d, Y H:i', strtotime($note->created_at))) ?></small>
                            </div>
                            <p class="mb-0"><?= nl2br(htmlspecialchars($note->note)) ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-muted">No internal notes yet.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<?php
$content = ob_get_clean();
require ANI_MART_ROOT . '/app/Views/layouts/main.php';
?>
