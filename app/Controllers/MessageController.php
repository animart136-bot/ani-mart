<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Services\NotificationService;
use App\Services\CloudinaryService;
use App\Models\Conversation;
use App\Models\ConversationSetting;
use App\Models\Message;

class MessageController extends Controller
{
    private $conversationModel;
    private $conversationSettingModel;
    private $messageModel;

    public function __construct()
    {
        $this->conversationModel = new Conversation();
        $this->conversationSettingModel = new ConversationSetting();
        $this->messageModel = new Message();
    }

    public function index()
    {
        $user = Session::get('user');

        if (!$user) {
            header('Location: /ani-mart/public/login');
            exit;
        }

        $role = $user['role'];
        $conversationId = isset($_GET['conversation']) ? (int) $_GET['conversation'] : 0;
        $search = trim($_GET['search'] ?? '');
        $peopleSearch = trim($_GET['people'] ?? '');
        $archived = isset($_GET['archived']) ? 1 : 0;

        if ($conversationId > 0) {
            $conversation = $this->conversationModel->findById($conversationId);

            if ($conversation && $this->isParticipant($conversation, $user['id'])) {
                header('Location: /ani-mart/public/messages/chat?id=' . $conversationId);
                exit;
            }
        }

        $conversations = $this->conversationModel->listForUser(
            $user['id'],
            $role,
            $search,
            $archived
        );

        $this->view('messages/index', [
            'title' => 'Messages',
            'conversations' => $conversations,
            'role' => $role,
            'search' => $search,
            'peopleSearch' => $peopleSearch,
            'people' => $this->findMessageableAccounts((int) $user['id'], $peopleSearch),
            'archived' => $archived,
            'bodyClass' => 'messages-page',
            'pageCss' => '/ani-mart/public/assets/css/pages/message-chat.css'
        ]);
    }

    public function chat()
    {
        $user = Session::get('user');

        if (!$user) {
            header('Location: /ani-mart/public/login');
            exit;
        }

        $conversationId = (int) ($_GET['id'] ?? 0);

        $conversation = $this->conversationModel->findById($conversationId);

        if (!$conversation) {
            $_SESSION['error'] = 'Conversation not found.';
            header('Location: /ani-mart/public/messages');
            exit;
        }

        if (!$this->isParticipant($conversation, $user['id'])) {
            http_response_code(403);
            echo 'Unauthorized access.';
            exit;
        }

        $messages = $this->messageModel->findByConversation($conversationId);
        $this->messageModel->markReadForConversation($conversationId, $user['id']);

        $conversationSettings = $this->conversationSettingModel->getForConversation($conversationId);
        if (!$conversationSettings) {
            $conversationSettings = $this->conversationSettingModel->createDefaultForConversation($conversationId);
        }

        $otherUser = $this->fetchConversationPartner($conversation, $user['role']);
        $product = $this->fetchProductPreview($conversation->product_id, $user['id'], $user['role'], $conversation);
        $order = $this->fetchOrderPreview($conversation->order_id, $user['id'], $user['role'], $conversation);
        $conversations = $this->conversationModel->listForUser($user['id'], $user['role']);

        $this->view('messages/chat', [
            'title' => 'Chat',
            'conversation' => $conversation,
            'conversationSettings' => $conversationSettings,
            'messages' => $messages,
            'currentUser' => $user,
            'otherUser' => $otherUser,
            'product' => $product,
            'order' => $order,
            'conversations' => $conversations,
            'bodyClass' => 'chat-page'
        ]);
    }

    public function saveConversationSettings()
    {
        csrf_verify();

        $user = Session::get('user');

        if (!$user) {
            return $this->jsonResponse(false, 'Login required.');
        }

        $conversationId = (int) ($_POST['conversation_id'] ?? 0);
        if (!$conversationId) {
            return $this->jsonResponse(false, 'Invalid conversation.');
        }

        $conversation = $this->conversationModel->findById($conversationId);
        if (!$conversation || !$this->isParticipant($conversation, $user['id'])) {
            return $this->jsonResponse(false, 'Unauthorized conversation access.');
        }

        $payload = [
            'notifications_muted' => isset($_POST['notifications_muted']) ? 1 : 0,
            'pinned' => isset($_POST['pinned']) ? 1 : 0,
            'hide_message_preview' => isset($_POST['hide_message_preview']) ? 1 : 0,
            'auto_download_images' => isset($_POST['auto_download_images']) ? 1 : 0,
        ];

        $this->conversationSettingModel->saveForConversation($conversationId, $payload);

        return $this->jsonResponse(true, 'Conversation settings saved successfully.');
    }

    public function start()
    {
        $user = Session::get('user');

        if (!$user) {
            header('Location: /ani-mart/public/login');
            exit;
        }

        $productId = (int) ($_GET['product_id'] ?? 0);
        $orderId = (int) ($_GET['order_id'] ?? 0);
        $itemId = (int) ($_GET['item_id'] ?? 0);
        $sellerProfileId = (int) ($_GET['seller_profile_id'] ?? 0);
        $targetUserId = (int) ($_GET['user_id'] ?? 0);

        $db = Database::connection();
        $customerId = null;
        $farmerId = null;
        $conversationProductId = null;
        $conversationOrderId = null;

        if ($targetUserId > 0) {
            $stmt = $db->prepare(
                "SELECT id, role
                FROM users
                WHERE id = ?
                    AND id != ?
                    AND COALESCE(account_status, 'active') = 'active'
                LIMIT 1"
            );
            $stmt->execute([$targetUserId, $user['id']]);
            $targetUser = $stmt->fetch();

            if (!$targetUser) {
                $_SESSION['error'] = $targetUserId === (int) $user['id']
                    ? 'You cannot message yourself.'
                    : 'This account is not available for messaging.';
                header('Location: /ani-mart/public/messages');
                exit;
            }

            // The legacy column names now represent the first and second account
            // in a direct conversation. Participant checks are role-independent.
            $customerId = $user['id'];
            $farmerId = $targetUser->id;
        } elseif ($sellerProfileId > 0) {
            if (!in_array(strtolower((string) ($user['role'] ?? '')), ['customer', 'farmer'], true)) {
                $_SESSION['error'] = 'A buyer or seller account is required to message this shop.';
                header('Location: /ani-mart/public/marketplace');
                exit;
            }

            $stmt = $db->prepare("SELECT id, user_id AS farmer_id
                FROM seller_profiles
                WHERE id = ?
                    AND status = 'approved'
                    AND COALESCE(is_suspended, 0) = 0
                LIMIT 1");
            $stmt->execute([$sellerProfileId]);
            $seller = $stmt->fetch();

            if (!$seller) {
                $_SESSION['error'] = 'Storefront not found.';
                header('Location: /ani-mart/public/marketplace');
                exit;
            }

            if ((int) $seller->farmer_id === (int) $user['id']) {
                $_SESSION['error'] = 'You cannot message your own storefront.';
                header('Location: /ani-mart/public/seller-center');
                exit;
            }

            $customerId = $user['id'];
            $farmerId = $seller->farmer_id;
        } elseif ($productId > 0) {
            if (!in_array(strtolower((string) ($user['role'] ?? '')), ['customer', 'farmer'], true)) {
                $_SESSION['error'] = 'A buyer account is required to message a seller from a product.';
                header('Location: /ani-mart/public/marketplace');
                exit;
            }

            $stmt = $db->prepare(
                "SELECT p.id, sp.user_id AS farmer_id FROM products p
                INNER JOIN seller_profiles sp ON sp.id = p.seller_profile_id
                WHERE p.id = ? LIMIT 1"
            );
            $stmt->execute([$productId]);
            $product = $stmt->fetch();

            if (!$product) {
                $_SESSION['error'] = 'Product not found.';
                header('Location: /ani-mart/public/marketplace');
                exit;
            }

            if ((int) $product->farmer_id === (int) $user['id']) {
                $_SESSION['error'] = 'You cannot start a buyer conversation about your own product.';
                header('Location: /ani-mart/public/marketplace');
                exit;
            }

            $customerId = $user['id'];
            $farmerId = $product->farmer_id;
            $conversationProductId = $product->id;
        } elseif ($orderId > 0) {
            $stmt = $db->prepare(
                "SELECT id, user_id FROM orders WHERE id = ? AND user_id = ? LIMIT 1"
            );
            $stmt->execute([$orderId, $user['id']]);
            $order = $stmt->fetch();

            if ($order) {

                $itemStmt = $db->prepare(
                    "SELECT oi.seller_profile_id, sp.user_id AS farmer_id
                    FROM order_items oi
                    INNER JOIN seller_profiles sp ON sp.id = oi.seller_profile_id
                    WHERE oi.order_id = ?
                    " . ($itemId ? ' AND oi.id = ?' : ' LIMIT 1')
                );
                $params = [$orderId];
                if ($itemId) {
                    $params[] = $itemId;
                }
                $itemStmt->execute($params);
                $item = $itemStmt->fetch();

                if (!$item) {
                    $_SESSION['error'] = 'Order item not found.';
                    header('Location: /ani-mart/public/customer/orders');
                    exit;
                }

                $customerId = $user['id'];
                $farmerId = $item->farmer_id;
                $conversationOrderId = $orderId;
            } elseif (strtolower((string) ($user['role'] ?? '')) === 'farmer') {
                $sellerStmt = $db->prepare(
                    "SELECT sp.user_id AS farmer_id, o.user_id AS customer_id
                    FROM orders o
                    INNER JOIN order_items oi ON oi.order_id = o.id
                    INNER JOIN seller_profiles sp ON sp.id = oi.seller_profile_id
                    WHERE o.id = ? AND sp.user_id = ?
                    LIMIT 1"
                );
                $sellerStmt->execute([$orderId, $user['id']]);
                $match = $sellerStmt->fetch();

                if (!$match) {
                    http_response_code(403);
                    echo 'Unauthorized access.';
                    exit;
                }

                $customerId = $match->customer_id;
                $farmerId = $user['id'];
                $conversationOrderId = $orderId;
            } else {
                $_SESSION['error'] = 'Order not found.';
                header('Location: /ani-mart/public/customer/orders');
                exit;
            }
        } else {
            $_SESSION['error'] = 'No conversation target specified.';
            header('Location: /ani-mart/public/messages');
            exit;
        }

        if (!$customerId || !$farmerId) {
            $_SESSION['error'] = 'Unable to start conversation.';
            header('Location: /ani-mart/public/messages');
            exit;
        }

        $conversation = $this->conversationModel->findExisting(
            $customerId,
            $farmerId,
            $conversationProductId,
            $conversationOrderId
        );

        if (!$conversation) {
            $conversationId = $this->conversationModel->create([
                'customer_id' => $customerId,
                'farmer_id' => $farmerId,
                'product_id' => $conversationProductId,
                'order_id' => $conversationOrderId,
                'last_message' => null,
                'last_message_at' => null
            ]);
        } else {
            $conversationId = $conversation->id;
            if ($conversationProductId || $conversationOrderId) {
                $this->conversationModel->updateContext(
                    $conversationId,
                    $conversationProductId,
                    $conversationOrderId
                );
            }
        }

        header("Location: /ani-mart/public/messages/chat?id={$conversationId}");
        exit;
    }

    public function send()
    {
        csrf_verify();

        $user = Session::get('user');

        if (!$user) {
            $this->jsonResponse(false, 'Login required.');
        }

        $conversationId = (int) ($_POST['conversation_id'] ?? 0);
        $messageText = trim($_POST['message'] ?? '');

        if (!$conversationId) {
            $this->jsonResponse(false, 'Invalid conversation.');
        }

        $conversation = $this->conversationModel->findById($conversationId);

        if (!$conversation || !$this->isParticipant($conversation, $user['id'])) {
            $this->jsonResponse(false, 'Unauthorized conversation access.');
        }

        if (empty($messageText) && empty($_FILES['image'])) {
            $this->jsonResponse(false, 'Enter a message or upload an image.');
        }

        $recipientId = $conversation->customer_id === $user['id'] ? $conversation->farmer_id : $conversation->customer_id;
        $imageUrl = $this->uploadMessageImages();

        $messageId = $this->messageModel->create([
            'conversation_id' => $conversationId,
            'sender_id' => $user['id'],
            'message' => htmlspecialchars($messageText, ENT_QUOTES, 'UTF-8'),
            'image' => $imageUrl,
            'is_read' => 0,
        ]);

        $message = $this->messageModel->findById($messageId);
        $this->conversationModel->updateLastMessage($conversationId, $messageText ?: '[Image]');

        NotificationController::create(
            $recipientId,
            'message',
            'New chat message',
            'You have a new message in your Ani-Mart conversation.',
            'bi-chat-dots',
            'info',
            "/ani-mart/public/messages/chat?id={$conversationId}",
            'conversation',
            $conversationId,
            "/ani-mart/public/messages/chat?id={$conversationId}"
        );

        $this->jsonResponse(true, 'Message sent.', [
            'message' => $message
        ]);
    }

    public function deleteConversation()
    {
        csrf_verify();

        $user = Session::get('user');

        if (!$user) {
            header('Location: /ani-mart/public/login');
            exit;
        }

        $conversationId = (int) ($_POST['conversation_id'] ?? 0);
        $conversation = $this->conversationModel->findById($conversationId);

        if (!$conversation || !$this->isParticipant($conversation, $user['id'])) {
            http_response_code(403);
            echo 'Unauthorized conversation access.';
            exit;
        }

        if (!$this->conversationModel->deleteForParticipant($conversationId, (int) $user['id'])) {
            Session::flash('error', 'The conversation could not be deleted. Please try again.');
            header("Location: /ani-mart/public/messages/chat?id={$conversationId}");
            exit;
        }

        Session::flash('success', 'Conversation and all of its messages permanently deleted.');
        header('Location: /ani-mart/public/messages');
        exit;
    }

    public function upload()
    {
        csrf_verify();

        $user = Session::get('user');

        if (!$user) {
            $this->jsonResponse(false, 'Login required.');
        }

        $conversationId = (int) ($_POST['conversation_id'] ?? 0);
        $conversation = $this->conversationModel->findById($conversationId);

        if (!$conversation || !$this->isParticipant($conversation, $user['id'])) {
            $this->jsonResponse(false, 'Unauthorized conversation access.');
        }

        $imageUrl = $this->uploadMessageImage();

        if (!$imageUrl) {
            $this->jsonResponse(false, 'Image upload failed.');
        }

        $messageId = $this->messageModel->create([
            'conversation_id' => $conversationId,
            'sender_id' => $user['id'],
            'message' => null,
            'image' => $imageUrl,
            'is_read' => 0,
        ]);

        $this->conversationModel->updateLastMessage($conversationId, '[Image]');

        $recipientId = $conversation->customer_id === $user['id'] ? $conversation->farmer_id : $conversation->customer_id;
        NotificationController::create(
            $recipientId,
            'message',
            'New image received',
            'A new image was sent in your chat.',
            'bi-image',
            'info',
            "/ani-mart/public/messages/chat?id={$conversationId}",
            'conversation',
            $conversationId,
            "/ani-mart/public/messages/chat?id={$conversationId}"
        );

        $message = $this->messageModel->findById($messageId);
        $this->jsonResponse(true, 'Image uploaded.', ['message' => $message]);
    }

    public function archive()
    {
        csrf_verify();

        $user = Session::get('user');

        if (!$user) {
            $this->jsonResponse(false, 'Login required.');
        }

        $conversationId = (int) ($_POST['conversation_id'] ?? 0);
        $conversation = $this->conversationModel->findById($conversationId);

        if (!$conversation || !$this->isParticipant($conversation, $user['id'])) {
            $this->jsonResponse(false, 'Unauthorized conversation access.');
        }

        $this->conversationModel->markArchived($conversationId, 1);
        $this->jsonResponse(true, 'Conversation archived.');
    }

    public function search()
    {
        $user = Session::get('user');

        if (!$user) {
            $this->jsonResponse(false, 'Login required.');
        }

        $search = trim($_GET['q'] ?? '');
        $archived = isset($_GET['archived']) ? 1 : 0;

        $conversations = $this->conversationModel->listForUser(
            $user['id'],
            $user['role'],
            $search,
            $archived
        );

        $this->jsonResponse(true, 'Search complete.', ['conversations' => $conversations]);
    }

    public function markRead()
    {
        $user = Session::get('user');

        if (!$user) {
            $this->jsonResponse(false, 'Login required.');
        }

        $conversationId = (int) ($_POST['conversation_id'] ?? 0);
        $conversation = $this->conversationModel->findById($conversationId);

        if (!$conversation || !$this->isParticipant($conversation, $user['id'])) {
            $this->jsonResponse(false, 'Unauthorized conversation access.');
        }

        $this->messageModel->markReadForConversation($conversationId, $user['id']);
        $this->jsonResponse(true, 'Conversation marked read.');
    }

    public function unread()
    {
        $user = Session::get('user');

        if (!$user) {
            $this->jsonResponse(false, 'Login required.');
        }

        $count = $this->messageModel->unreadCountForUser($user['id']);

        $this->jsonResponse(true, 'Unread messages fetched.', [
            'unread_message_count' => (int) $count
        ]);
    }

    private function isParticipant($conversation, $userId)
    {
        return (int) ($conversation->customer_id ?? 0) === (int) $userId
            || (int) ($conversation->farmer_id ?? 0) === (int) $userId;
    }

    private function fetchConversationPartner($conversation, $role)
    {
        $currentUserId = (int) (Session::get('user')['id'] ?? 0);
        $otherUserId = (int) ($conversation->customer_id ?? 0) === $currentUserId
            ? $conversation->farmer_id
            : $conversation->customer_id;
        $stmt = Database::connection()->prepare(
            "SELECT id, first_name, last_name, role FROM users WHERE id = ? LIMIT 1"
        );
        $stmt->execute([$otherUserId]);
        return $stmt->fetch();
    }

    private function findMessageableAccounts(int $viewerId, string $search = ''): array
    {
        $sql = "SELECT id, first_name, last_name, role, profile_image
            FROM users
            WHERE id != ?
                AND COALESCE(account_status, 'active') = 'active'";
        $params = [$viewerId];

        if ($search !== '') {
            $sql .= " AND TRIM(CONCAT(first_name, ' ', last_name)) LIKE ?";
            $params[] = '%' . $search . '%';
        }

        $sql .= " ORDER BY first_name ASC, last_name ASC LIMIT 12";
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    private function fetchProductPreview($productId, $userId, $userRole, $conversation)
    {
        if (!$productId) {
            return null;
        }

        $stmt = Database::connection()->prepare(
            "SELECT p.id, p.name, p.image
            FROM products p
            INNER JOIN seller_profiles sp ON sp.id = p.seller_profile_id
            WHERE p.id = ? AND sp.user_id = ?
            LIMIT 1"
        );
        $stmt->execute([$productId, $conversation->farmer_id ?? $userId]);

        return $stmt->fetch();
    }

    private function fetchOrderPreview($orderId, $userId, $userRole, $conversation)
    {
        if (!$orderId) {
            return null;
        }

        $db = Database::connection();
        $stmt = $db->prepare(
            "SELECT o.id, o.order_number, o.status, o.total_amount
            FROM orders o
            WHERE o.id = ?
            AND (
                o.user_id = ?
                OR EXISTS (
                    SELECT 1
                    FROM order_items oi
                    INNER JOIN seller_profiles sp ON sp.id = oi.seller_profile_id
                    WHERE oi.order_id = o.id AND sp.user_id = ?
                )
            )
            LIMIT 1"
        );
        $stmt->execute([$orderId, $userId, $userId]);
        return $stmt->fetch();
    }

    private function uploadMessageImage()
    {
        if (empty($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $allowedMimeTypes = [
            'image/jpeg',
            'image/png',
            'image/webp'
        ];

        $tmpFile = $_FILES['image']['tmp_name'];
        $mimeType = mime_content_type($tmpFile) ?: $_FILES['image']['type'];

        if (!in_array($mimeType, $allowedMimeTypes, true)) {
            return null;
        }

        $cloudinary = new CloudinaryService();
        $upload = $cloudinary->upload([
            'name' => $_FILES['image']['name'] ?? '',
            'tmp_name' => $tmpFile,
            'size' => $_FILES['image']['size'] ?? 0,
            'type' => $_FILES['image']['type'] ?? '',
            'error' => $_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE,
        ], 'ani-mart/messages');

        if (!is_array($upload)) {
            return null;
        }

        return $upload['url'] ?? null;
    }

    private function uploadMessageImages()
    {
        if (empty($_FILES['image'])) {
            return null;
        }

        $files = $_FILES['image'];
        if (!is_array($files['name'])) {
            return $this->uploadMessageImage();
        }

        $allowedMimeTypes = [
            'image/jpeg',
            'image/png',
            'image/webp'
        ];

        $uploaded = [];
        $cloudinary = new CloudinaryService();

        foreach ($files['name'] as $index => $name) {
            if ($files['error'][$index] !== UPLOAD_ERR_OK) {
                continue;
            }

            $tmpName = $files['tmp_name'][$index];
            $mimeType = mime_content_type($tmpName) ?: $files['type'][$index];

            if (!in_array($mimeType, $allowedMimeTypes, true)) {
                continue;
            }

            $upload = $cloudinary->upload([
                'name' => $files['name'][$index] ?? '',
                'tmp_name' => $tmpName,
                'size' => $files['size'][$index] ?? 0,
                'type' => $files['type'][$index] ?? '',
                'error' => $files['error'][$index] ?? UPLOAD_ERR_NO_FILE,
            ], 'ani-mart/messages');
            if (is_array($upload) && !empty($upload['url'])) {
                $uploaded[] = $upload['url'];
            }
        }

        if (empty($uploaded)) {
            return null;
        }

        return json_encode($uploaded);
    }

    private function jsonResponse($success, $message, $data = [])
    {
        header('Content-Type: application/json');
        echo json_encode(array_merge([
            'success' => $success,
            'message' => $message
        ], $data));
        exit;
    }
}
