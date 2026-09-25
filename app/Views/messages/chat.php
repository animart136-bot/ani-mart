<?php

$title = 'Chat';
$conversations = $conversations ?? [];
$messages = $messages ?? [];
$currentUser = $currentUser ?? null;
$role = $role ?? (is_array($currentUser) ? ($currentUser['role'] ?? '') : ((is_object($currentUser) ? ($currentUser->role ?? '') : '')));
$otherUser = $otherUser ?? null;
$product = $product ?? null;
$order = $order ?? null;
$conversation = $conversation ?? (object) ['id' => 0];
$currentUserId = is_array($currentUser) ? (int) ($currentUser['id'] ?? 0) : ((is_object($currentUser) ? (int) ($currentUser->id ?? 0) : 0));
$conversationSettings = $conversationSettings ?? (object) [
    'notifications_muted' => 0,
    'pinned' => 0,
    'hide_message_preview' => 0,
    'auto_download_images' => 0,
];

$pageStyles[] = '/ani-mart/public/assets/css/pages/inbox-ui.css?v=' . filemtime(__DIR__ . '/../../../public/assets/css/pages/inbox-ui.css');

ob_start();
?>

<div class="chat-page app-page-spacing">

    <div class="container">

        <div class="row gx-4">

            <aside class="col-lg-4 chat-sidebar-column" id="conversationDrawer" aria-label="Conversations">

                <div class="rounded-4 shadow-sm chat-sidebar p-4">
                    <div class="d-flex align-items-start justify-content-between gap-3 mb-4">
                        <div>
                            <h5 class="mb-1 chat-drawer-title">Chats</h5>
                            <p class="text-muted mb-0 chat-drawer-subtitle">Your Ani-Mart conversations</p>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="/ani-mart/public/messages" class="btn btn-sm btn-outline-secondary">Back</a>
                            <button type="button" class="btn btn-sm btn-light chat-drawer-close" id="closeConversationDrawer" aria-label="Close conversations">
                                <i class="bi bi-x-lg" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="input-group search-input shadow-sm">
                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                            <input type="search" id="chatConversationSearch" class="form-control border-start-0" placeholder="Search chats" autocomplete="off">
                        </div>
                    </div>

                    <?php if (empty($conversations)): ?>
                        <div class="text-center py-5 text-muted">
                            No conversations available.
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($conversations as $conversationItem): ?>
                                <?php
                                    $isUnread = (int) ($conversationItem->unread_count ?? 0) > 0;
                                    $label = trim((string) ($conversationItem->partner_name ?? ''));
                                    $partnerRole = strtolower((string) ($conversationItem->partner_role ?? 'account'));
                                    $partnerType = ucfirst(str_replace('_', ' ', $partnerRole));
                                    $partnerTypeClass = $partnerRole === 'farmer' ? 'is-seller' : ($partnerRole === 'admin' ? 'is-admin' : 'is-customer');
                                    $subject = \App\Services\MessageFormatter::conversationSubject($conversationItem);
                                    $lastMessageTime = !empty($conversationItem->last_message_at)
                                        ? date('g:i A', strtotime($conversationItem->last_message_at))
                                        : '';
                                ?>
                                <a href="/ani-mart/public/messages/chat?id=<?= $conversationItem->id ?>" data-chat-search="<?= htmlspecialchars(mb_strtolower($label . ' ' . $subject . ' ' . ($conversationItem->last_message ?? ''))) ?>" class="list-group-item list-group-item-action chat-conversation-row <?= (isset($conversation) && $conversation->id === $conversationItem->id ? 'active' : '') ?>">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="avatar-circle bg-success text-white d-flex align-items-center justify-content-center">
                                            <?= strtoupper(substr($label ?: 'U', 0, 1)) ?>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-start gap-2">
                                                <span class="chat-partner-name">
                                                    <strong><?= htmlspecialchars($label ?: 'Unknown User') ?></strong>
                                                    <span class="conversation-party-badge <?= $partnerTypeClass ?>"><?= htmlspecialchars($partnerType) ?></span>
                                                </span>
                                                <?php if ($isUnread): ?>
                                                    <span class="badge bg-success rounded-pill">New</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="chat-conversation-preview text-muted small">
                                                <span><?= htmlspecialchars(mb_strimwidth(\App\Services\MessageFormatter::orderReference($conversationItem->last_message ?: $subject, $conversationItem->order_number ?? null), 0, 48, '...')) ?></span>
                                                <?php if ($lastMessageTime): ?><time datetime="<?= htmlspecialchars($conversationItem->last_message_at) ?>">· <?= htmlspecialchars($lastMessageTime) ?></time><?php endif; ?>
                                            </div>
                                            <span class="chat-conversation-subject"><?= htmlspecialchars($subject) ?></span>
                                        </div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

            </aside>

            <button type="button" class="chat-drawer-backdrop" id="conversationDrawerBackdrop" aria-label="Close conversations" tabindex="-1"></button>

            <main class="col-lg-8">

                <div class="card rounded-4 shadow-sm chat-shell p-4">

                    <button type="button" class="btn btn-outline-success chat-drawer-toggle mb-3" id="openConversationDrawer" aria-controls="conversationDrawer" aria-expanded="false">
                        <i class="bi bi-chat-left-text me-2" aria-hidden="true"></i>
                        Conversations
                    </button>

                    <?php
                        $otherFirstName = is_array($otherUser)
                            ? ($otherUser['first_name'] ?? 'Partner')
                            : ($otherUser->first_name ?? 'Partner');
                        $otherLastName = is_array($otherUser)
                            ? ($otherUser['last_name'] ?? '')
                            : ($otherUser->last_name ?? '');
                        $chatPartnerRole = strtolower((string) (
                            is_array($otherUser)
                                ? ($otherUser['role'] ?? 'account')
                                : ($otherUser->role ?? 'account')
                        ));
                        $chatPartnerType = ucfirst(str_replace('_', ' ', $chatPartnerRole));
                        $chatPartnerTypeClass = $chatPartnerRole === 'farmer' ? 'is-seller' : ($chatPartnerRole === 'admin' ? 'is-admin' : 'is-customer');
                        $chatSubject = 'General chat';

                        if ($product) {
                            $chatSubject = is_array($product)
                                ? ($product['name'] ?? 'Product chat')
                                : ($product->name ?? 'Product chat');
                        } elseif ($order) {
                            $chatSubject = is_array($order)
                                ? ($order['id'] ? 'Order chat' : 'General chat')
                                : ($order->id ? 'Order chat' : 'General chat');
                        }
                    ?>

                    <div class="chat-conversation-header d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3 mb-4">
                        <div class="d-flex align-items-center gap-3">
                            <div class="chat-avatar bg-success text-white d-flex align-items-center justify-content-center">
                                <?= strtoupper(substr($otherFirstName, 0, 1)) ?>
                            </div>
                            <div>
                                <div class="chat-heading-name-row">
                                    <h3 class="mb-0"><?= htmlspecialchars(trim($otherFirstName . ' ' . $otherLastName)) ?></h3>
                                    <span class="conversation-party-badge <?= $chatPartnerTypeClass ?>">
                                        <i class="bi <?= $chatPartnerRole === 'farmer' ? 'bi-shop' : 'bi-person' ?>" aria-hidden="true"></i>
                                        <?= htmlspecialchars($chatPartnerType) ?>
                                    </span>
                                </div>
                                <p class="text-muted mb-0"><?= htmlspecialchars($chatSubject) ?></p>
                            </div>
                        </div>
                        <div class="chat-header-actions d-flex flex-column gap-2 text-end">
                            <span class="badge bg-light text-dark"><?= htmlspecialchars($product ? 'Product' : ($order ? 'Order' : 'Conversation')) ?></span>
                            <details class="chat-settings-details">
                                <summary class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-sliders me-1" aria-hidden="true"></i>
                                    Conversation Settings
                                </summary>
                                <div class="chat-settings-panel">
                                    <div class="chat-settings-panel-heading">
                                        <div>
                                            <strong>Conversation settings</strong>
                                            <small>Customize this chat</small>
                                        </div>
                                        <span class="chat-settings-close-hint">Esc to close</span>
                                    </div>
                                    <form id="conversationSettingsForm" action="/ani-mart/public/messages/chat/settings" method="POST">
                                        <input type="hidden" name="conversation_id" value="<?= $conversation->id ?>">
                                        <div class="form-check form-switch chat-setting-row">
                                            <input class="form-check-input" type="checkbox" id="notificationsMuted" name="notifications_muted" <?= (int) $conversationSettings->notifications_muted ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="notificationsMuted">Mute notifications for this chat</label>
                                        </div>
                                        <div class="form-check form-switch chat-setting-row">
                                            <input class="form-check-input" type="checkbox" id="pinnedConversation" name="pinned" <?= (int) $conversationSettings->pinned ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="pinnedConversation">Pin this conversation</label>
                                        </div>
                                        <div class="form-check form-switch chat-setting-row">
                                            <input class="form-check-input" type="checkbox" id="hidePreview" name="hide_message_preview" <?= (int) $conversationSettings->hide_message_preview ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="hidePreview">Hide message preview in lists</label>
                                        </div>
                                        <div class="form-check form-switch chat-setting-row">
                                            <input class="form-check-input" type="checkbox" id="autoDownloadImages" name="auto_download_images" <?= (int) $conversationSettings->auto_download_images ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="autoDownloadImages">Auto-download chat images</label>
                                        </div>
                                        <button type="submit" class="btn btn-success btn-sm w-100 mt-2">Save Chat Settings</button>
                                        <div id="conversationSettingsFeedback" class="mt-2 small text-success d-none" role="status"></div>
                                    </form>
                                    <div class="chat-settings-danger-zone">
                                        <form
                                            action="/ani-mart/public/messages/conversation/delete"
                                            method="POST"
                                            data-confirm="Are you sure you want to delete this entire conversation? All messages will be permanently deleted for both participants and cannot be recovered."
                                            data-confirm-title="Delete conversation"
                                            data-confirm-text="Delete conversation"
                                            data-confirm-class="btn-danger">
                                            <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                                            <input type="hidden" name="conversation_id" value="<?= (int) $conversation->id ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger w-100">
                                                <i class="bi bi-trash3 me-1" aria-hidden="true"></i>
                                                Delete conversation
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </details>
                        </div>
                    </div>

                    <div class="chat-messages mb-4">
                        <?php if (empty($messages)): ?>
                            <div class="empty-chat-state text-center py-6">
                                <i class="bi bi-chat-dots fs-1 text-success mb-3"></i>
                                <h5 class="mb-2">No messages yet</h5>
                                <p class="text-muted mb-0">Send the first message below and start the conversation.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($messages as $message): ?>
                                <?php
                                    $isMine = (int) $message->sender_id === $currentUserId;
                                    $messageImages = [];
                                    if (!empty($message->image)) {
                                        $decoded = json_decode($message->image, true);
                                        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                            $messageImages = $decoded;
                                        } else {
                                            $messageImages = [$message->image];
                                        }
                                    }
                                ?>
                                <div class="chat-message-row <?= $isMine ? 'chat-message-right' : 'chat-message-left' ?>">
                                    <div class="chat-bubble <?= $isMine ? 'chat-bubble-right' : 'chat-bubble-left' ?>">
                                        <?php foreach ($messageImages as $messageImage): ?>
                                            <img src="<?= htmlspecialchars($messageImage) ?>" alt="Message image" class="img-fluid rounded-4 message-image" data-image-modal="true">
                                        <?php endforeach; ?>
                                        <?php if (!empty($message->message)): ?>
                                            <p class="mb-1 message-text"><?= nl2br(htmlspecialchars(\App\Services\MessageFormatter::orderReference($message->message, $order->order_number ?? null))) ?></p>
                                        <?php endif; ?>
                                        <small class="text-muted message-time"><?= date('M j, Y h:i A', strtotime($message->created_at)) ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <form id="messageForm" class="message-form" action="/ani-mart/public/messages/send" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="conversation_id" value="<?= $conversation->id ?>">

                        <div id="messageImagePreview" class="image-preview d-none">
                            <div class="image-preview-grid"></div>
                            <div class="d-flex justify-content-between align-items-center mt-3 gap-3">
                                <div class="text-muted small preview-summary"></div>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="messageImageClear">Remove all</button>
                            </div>
                        </div>

                        <div class="input-group chat-input-group">
                            <input
                                type="text"
                                name="message"
                                class="form-control modern-input"
                                placeholder="Type your message..."
                                autocomplete="off">
                            <button class="btn btn-light text-secondary border-start-0" type="button" id="messageImageBtn" aria-label="Upload images" title="Upload images">
                                <i class="bi bi-image fs-5"></i>
                            </button>
                            <button class="btn btn-success" type="submit">
                                <i class="bi bi-send-fill"></i>
                                <span class="d-none d-sm-inline ms-2">Send</span>
                            </button>
                        </div>

                        <input type="file" name="image[]" id="messageImageInput" accept="image/jpeg,image/png,image/webp" class="d-none" multiple>
                    </form>

                </div>

            </main>

        </div>

    </div>

</div>

<script>
const messageForm = document.getElementById('messageForm');
const messageImageBtn = document.getElementById('messageImageBtn');
const messageImageInput = document.getElementById('messageImageInput');
const messageImagePreview = document.getElementById('messageImagePreview');
const messageImageClear = document.getElementById('messageImageClear');
const imagePreviewGrid = document.querySelector('.image-preview-grid');
const previewSummary = document.querySelector('.preview-summary');
const conversationDrawer = document.getElementById('conversationDrawer');
const openConversationDrawer = document.getElementById('openConversationDrawer');
const closeConversationDrawer = document.getElementById('closeConversationDrawer');
const conversationDrawerBackdrop = document.getElementById('conversationDrawerBackdrop');
const chatConversationSearch = document.getElementById('chatConversationSearch');

function setConversationDrawer(open) {
    conversationDrawer?.classList.toggle('is-open', open);
    conversationDrawerBackdrop?.classList.toggle('is-open', open);
    openConversationDrawer?.setAttribute('aria-expanded', open ? 'true' : 'false');
    document.body.classList.toggle('chat-drawer-open', open);
}

openConversationDrawer?.addEventListener('click', () => setConversationDrawer(true));
closeConversationDrawer?.addEventListener('click', () => setConversationDrawer(false));
conversationDrawerBackdrop?.addEventListener('click', () => setConversationDrawer(false));
document.addEventListener('keydown', event => {
    if (event.key === 'Escape') setConversationDrawer(false);
});

window.addEventListener('resize', () => {
    if (window.innerWidth >= 992) setConversationDrawer(false);
});

chatConversationSearch?.addEventListener('input', function () {
    const query = this.value.trim().toLocaleLowerCase();
    document.querySelectorAll('.chat-conversation-row').forEach(row => {
        row.hidden = query !== '' && !row.dataset.chatSearch.includes(query);
    });
});

function escapeHtml(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function updateImagePreview(files) {
    if (!imagePreviewGrid || !previewSummary || !messageImagePreview) {
        return;
    }

    imagePreviewGrid.innerHTML = '';

    if (!files.length) {
        previewSummary.textContent = '';
        messageImagePreview.classList.add('d-none');
        return;
    }

    files.forEach((file, index) => {
        const reader = new FileReader();
        reader.onload = () => {
            const previewItem = document.createElement('div');
            previewItem.className = 'image-preview-item';
            previewItem.innerHTML = `
                <img src="${escapeHtml(reader.result)}" alt="${escapeHtml(file.name)}" class="image-preview-thumb">
                <div class="image-preview-meta">
                    <span title="${escapeHtml(file.name)}">${escapeHtml(file.name)}</span>
                    <button type="button" class="btn btn-link btn-sm text-danger preview-remove" data-index="${index}">Remove</button>
                </div>
            `;
            imagePreviewGrid.appendChild(previewItem);

            const removeButton = previewItem.querySelector('.preview-remove');
            removeButton?.addEventListener('click', () => {
                const nextFiles = files.filter((_, idx) => idx !== index);
                setInputFiles(nextFiles);
                updateImagePreview(nextFiles);
            });
        };
        reader.readAsDataURL(file);
    });

    previewSummary.textContent = `${files.length} image${files.length > 1 ? 's' : ''} selected`;
    messageImagePreview.classList.remove('d-none');
}

function setInputFiles(files) {
    if (!messageImageInput) {
        return;
    }

    const dataTransfer = new DataTransfer();
    files.forEach((file) => dataTransfer.items.add(file));
    messageImageInput.files = dataTransfer.files;
}

messageImageBtn?.addEventListener('click', () => {
    messageImageInput?.click();
});

messageImageInput?.addEventListener('change', () => {
    const files = messageImageInput.files ? Array.from(messageImageInput.files) : [];
    updateImagePreview(files);
});

messageImageClear?.addEventListener('click', () => {
    setInputFiles([]);
    updateImagePreview([]);
});

messageForm?.addEventListener('submit', (event) => {
    event.preventDefault();

    const formData = new FormData(messageForm);

    fetch(messageForm.action, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            alert(data.message || 'Unable to send message');
            return;
        }

        window.location.reload();
    });
});

const conversationSettingsForm = document.getElementById('conversationSettingsForm');
const conversationSettingsFeedback = document.getElementById('conversationSettingsFeedback');
const conversationSettingsDetails = document.querySelector('.chat-settings-details');

document.addEventListener('click', (event) => {
    if (
        conversationSettingsDetails?.open
        && !conversationSettingsDetails.contains(event.target)
    ) {
        conversationSettingsDetails.open = false;
    }
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && conversationSettingsDetails?.open) {
        conversationSettingsDetails.open = false;
        conversationSettingsDetails.querySelector('summary')?.focus();
    }
});

conversationSettingsForm?.addEventListener('submit', (event) => {
    event.preventDefault();

    const formData = new FormData(conversationSettingsForm);

    fetch(conversationSettingsForm.action, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            conversationSettingsFeedback.classList.remove('text-success');
            conversationSettingsFeedback.classList.add('text-danger');
            conversationSettingsFeedback.textContent = data.message || 'Unable to save settings.';
            conversationSettingsFeedback.classList.remove('d-none');
            return;
        }

        conversationSettingsFeedback.classList.remove('text-danger');
        conversationSettingsFeedback.classList.add('text-success');
        conversationSettingsFeedback.textContent = data.message || 'Settings saved.';
        conversationSettingsFeedback.classList.remove('d-none');
    });
});
</script>



<?php

$content = ob_get_clean();

require ANI_MART_ROOT . '/app/Views/layouts/main.php';
