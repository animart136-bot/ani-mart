<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Models\SupportConversation;
use App\Models\SupportMessage;
use App\Models\SupportNote;
use App\Models\QuickReplyTemplate;
use App\Models\BroadcastMessage;
use App\Models\CommunityPost;
use App\Models\CommunityCategory;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\AuditLogService;

class SupportCenterController extends Controller
{
    private SupportConversation $conversationModel;
    private SupportMessage $messageModel;
    private SupportNote $noteModel;
    private QuickReplyTemplate $templateModel;
    private BroadcastMessage $broadcastModel;

    public function __construct()
    {
        $this->conversationModel = new SupportConversation();
        $this->messageModel = new SupportMessage();
        $this->noteModel = new SupportNote();
        $this->templateModel = new QuickReplyTemplate();
        $this->broadcastModel = new BroadcastMessage();
    }

    public function index()
    {
        $user = Session::get('user');

        if (!$user || strtolower($user['role'] ?? '') !== 'admin') {
            header('Location: /ani-mart/public/login');
            exit;
        }

        $tab = $_GET['tab'] ?? 'inbox';
        $search = trim($_GET['search'] ?? '');

        $filterType = null;
        $archived = false;

        switch ($tab) {
            case 'complaints':
                $filterType = 'complaint';
                break;
            case 'bug_reports':
                $filterType = 'bug_report';
                break;
            case 'verification':
                $filterType = 'verification';
                break;
            case 'community_reports':
                $filterType = 'community_report';
                break;
            case 'archived':
                $archived = true;
                break;
            default:
                $filterType = null;
        }

        $conversations = $this->conversationModel->listForAdmin($filterType, $search, $archived);
        $templates = $this->templateModel->all();
        $broadcasts = $this->broadcastModel->listAll();
        $stats = $this->conversationModel->summaryCounts();

        $this->view('admin/support/index', [
            'title' => 'Support Center',
            'activeTab' => $tab,
            'search' => $search,
            'conversations' => $conversations,
            'templates' => $templates,
            'broadcasts' => $broadcasts,
            'stats' => $stats,
            'bodyClass' => 'admin-support-page'
        ]);
    }

    public function conversation()
    {
        $user = Session::get('user');

        if (!$user || strtolower($user['role'] ?? '') !== 'admin') {
            header('Location: /ani-mart/public/login');
            exit;
        }

        $conversationId = (int) ($_GET['id'] ?? 0);
        $conversation = $this->conversationModel->findById($conversationId);

        if (!$conversation) {
            $_SESSION['error'] = 'Support conversation not found.';
            header('Location: /ani-mart/public/admin/support');
            exit;
        }

        $messages = $this->messageModel->findByConversation($conversationId);
        $notes = $this->noteModel->findByConversation($conversationId);
        $templates = $this->templateModel->all();

        $this->view('admin/support/conversation', [
            'title' => 'Support Conversation',
            'conversation' => $conversation,
            'messages' => $messages,
            'notes' => $notes,
            'templates' => $templates,
            'bodyClass' => 'admin-support-conversation-page'
        ]);
    }

    public function sendMessage()
    {
        $user = Session::get('user');

        if (!$user || strtolower($user['role'] ?? '') !== 'admin') {
            header('Location: /ani-mart/public/login');
            exit;
        }

        $conversationId = (int) ($_POST['conversation_id'] ?? 0);
        $message = trim($_POST['message'] ?? '');
        $attachmentUrl = trim($_POST['attachment_url'] ?? '');
        $messageType = $attachmentUrl ? 'attachment' : 'text';

        if (!$conversationId || !$message && !$attachmentUrl) {
            $_SESSION['error'] = 'Message content is required.';
            header('Location: /ani-mart/public/admin/support/conversation?id=' . $conversationId);
            exit;
        }

        $this->messageModel->create([
            'conversation_id' => $conversationId,
            'sender_id' => $user['id'],
            'sender_role' => 'admin',
            'message_type' => $messageType,
            'message' => $message ?: null,
            'attachment_url' => $attachmentUrl ?: null,
            'is_read' => 0,
        ]);

        $this->conversationModel->updateLastMessage($conversationId, $message ?: 'Attachment');

        $_SESSION['success'] = 'Message sent successfully.';
        header('Location: /ani-mart/public/admin/support/conversation?id=' . $conversationId);
        exit;
    }

    public function assign()
    {
        $user = Session::get('user');

        if (!$user || strtolower($user['role'] ?? '') !== 'admin') {
            header('Location: /ani-mart/public/login');
            exit;
        }

        csrf_verify();

        $conversationId = (int) ($_POST['conversation_id'] ?? 0);
        $adminId = (int) ($_POST['assigned_admin_id'] ?? 0);

        if (!$conversationId || !$adminId) {
            $_SESSION['error'] = 'Assignment requires both conversation and admin.';
            header('Location: /ani-mart/public/admin/support/conversation?id=' . $conversationId);
            exit;
        }

        $this->conversationModel->assignAdmin($conversationId, $adminId);
        $_SESSION['success'] = 'Conversation reassigned successfully.';
        header('Location: /ani-mart/public/admin/support/conversation?id=' . $conversationId);
        exit;
    }

    public function updateStatus()
    {
        $user = Session::get('user');

        if (!$user || strtolower($user['role'] ?? '') !== 'admin') {
            header('Location: /ani-mart/public/login');
            exit;
        }

        csrf_verify();

        $conversationId = (int) ($_POST['conversation_id'] ?? 0);
        $status = $_POST['status'] ?? '';

        if (!$conversationId || !in_array($status, ['open','pending','investigating','waiting_seller','waiting_customer','resolved','rejected'])) {
            $_SESSION['error'] = 'Invalid status selection.';
            header('Location: /ani-mart/public/admin/support/conversation?id=' . $conversationId);
            exit;
        }

        $this->conversationModel->updateStatus($conversationId, $status);
        $_SESSION['success'] = 'Conversation status updated.';
        header('Location: /ani-mart/public/admin/support/conversation?id=' . $conversationId);
        exit;
    }

    public function settings()
    {
        $user = Session::get('user');

        if (!$user || strtolower($user['role'] ?? '') !== 'admin') {
            header('Location: /ani-mart/public/login');
            exit;
        }

        $categoryModel = new CommunityCategory();

        $this->view('admin/support/settings', [
            'title' => 'Support Center Settings',
            'bodyClass' => 'admin-support-settings-page',
            'categories' => $categoryModel->all(),
            'broadcasts' => $this->broadcastModel->listAll()
        ]);
    }

    public function publishAnnouncement()
    {
        $user = Session::get('user');

        if (!$user || strtolower($user['role'] ?? '') !== 'admin') {
            header('Location: /ani-mart/public/login');
            exit;
        }

        csrf_verify();

        $title = trim($_POST['announcement_title'] ?? '');
        $body = trim($_POST['announcement_body'] ?? '');
        $categoryId = (int) ($_POST['announcement_category_id'] ?? 0);
        $audience = strtolower(trim((string) ($_POST['announcement_audience'] ?? 'everyone')));
        $messageType = strtolower(trim((string) ($_POST['announcement_type'] ?? 'announcement')));

        if ($title === '' || $body === '' || mb_strlen($title) > 255 || mb_strlen($body) > 5000) {
            $_SESSION['error'] = 'Enter a title up to 255 characters and announcement content up to 5,000 characters.';
            header('Location: /ani-mart/public/admin/support/settings');
            exit;
        }
        if (!in_array($audience, ['everyone', 'customers', 'farmers', 'admins'], true)) $audience = 'everyone';
        if (!in_array($messageType, ['announcement', 'maintenance', 'emergency', 'weather_advisory', 'marketplace_news', 'agricultural_tip', 'feature_update'], true)) $messageType = 'announcement';

        if ($categoryId > 0 && $audience === 'everyone') {
            $post = new CommunityPost();
            $post->create(['user_id' => $user['id'], 'category_id' => $categoryId, 'title' => $title, 'content' => $body, 'visibility' => 'public', 'featured' => 1, 'pinned' => 1, 'allow_comments' => 1]);
        }

        $broadcastId = (int) $this->broadcastModel->create([
            'author_id' => $user['id'],
            'audience' => $audience,
            'recipient_ids' => null,
            'message_type' => $messageType,
            'title' => $title,
            'body' => $body,
            'is_published' => 1,
            'sent_at' => date('Y-m-d H:i:s'),
        ]);

        $roleMap = ['customers' => 'customer', 'farmers' => 'farmer', 'admins' => 'admin'];
        $params = [];
        $sql = "SELECT id FROM users WHERE COALESCE(account_status, 'active') = 'active'";
        if (isset($roleMap[$audience])) { $sql .= ' AND LOWER(role) = ?'; $params[] = $roleMap[$audience]; }
        $recipients = Database::connection()->prepare($sql);
        $recipients->execute($params);
        $sent = 0;
        foreach ($recipients->fetchAll() as $recipient) {
            NotificationService::send((int) $recipient->id, $title, $body, 'system', 'bi-megaphone-fill', $messageType === 'emergency' ? 'danger' : 'primary', '/ani-mart/public/notifications', 'broadcast', $broadcastId, '/ani-mart/public/notifications');
            $sent++;
        }
        AuditLogService::log('system_announcement_published', 'broadcast', $broadcastId, ['new_value' => ['title' => $title, 'audience' => $audience, 'message_type' => $messageType, 'recipient_count' => $sent]], (int) $user['id'], '/ani-mart/public/admin/support/settings/announcement');
        $_SESSION['success'] = 'System announcement published to ' . number_format($sent) . ' user' . ($sent === 1 ? '' : 's') . '.';
        header('Location: /ani-mart/public/admin/support/settings');
        exit;
    }

    public function sendWelcomeMessages()
    {
        $user = Session::get('user');

        if (!$user || strtolower($user['role'] ?? '') !== 'admin') {
            header('Location: /ani-mart/public/login');
            exit;
        }

        csrf_verify();

        $days = max(1, (int) ($_POST['welcome_days'] ?? 7));
        $message = trim($_POST['welcome_message'] ?? '');
        $role = $_POST['welcome_role'] ?? 'everyone';

        if (!$message) {
            $_SESSION['error'] = 'Please enter a welcome message.';
            header('Location: /ani-mart/public/admin/support/settings');
            exit;
        }

        $userModel = new User();
        $recipients = $userModel->newUsers($days, $role === 'everyone' ? null : $role);

        if (!count($recipients)) {
            $_SESSION['info'] = 'No new users found for the selected audience.';
            header('Location: /ani-mart/public/admin/support/settings');
            exit;
        }

        foreach ($recipients as $recipient) {
            NotificationService::send(
                $recipient->id,
                'Welcome to Ani-Mart!',
                $message,
                'promotion',
                'bi-chat-left-text',
                'primary',
                '/ani-mart/public/dashboard',
                'welcome_message',
                null,
                '/ani-mart/public/dashboard'
            );
        }

        $recipientIds = implode(',', array_map(fn ($user) => $user->id, $recipients));

        $this->broadcastModel->create([
            'author_id' => $user['id'],
            'audience' => $role,
            'recipient_ids' => $recipientIds,
            'message_type' => 'broadcast',
            'title' => 'Welcome to Ani-Mart',
            'body' => $message,
            'is_published' => 1,
            'sent_at' => date('Y-m-d H:i:s'),
        ]);

        $_SESSION['success'] = 'Welcome message sent to ' . count($recipients) . ' new user(s).';
        header('Location: /ani-mart/public/admin/support/settings');
        exit;
    }

    public function saveSettings()
    {
        $user = Session::get('user');

        if (!$user || strtolower($user['role'] ?? '') !== 'admin') {
            header('Location: /ani-mart/public/login');
            exit;
        }

        csrf_verify();

        $_SESSION['success'] = 'Support settings saved successfully.';
        header('Location: /ani-mart/public/admin/support?tab=settings');
        exit;
    }
}
