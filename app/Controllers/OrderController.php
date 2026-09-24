<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Core\Database;

use App\Middleware\AuthMiddleware;
use App\Controllers\NotificationController;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\AuditLogService;
use App\Services\CloudinaryService;
use App\Services\SellerProfileService;
use App\Services\MarketplaceRulesService;
use App\Services\OrderCancellationService;
use App\Services\OrderWorkflowSchemaService;
use App\Services\InventoryNotificationService;
use DomainException;
use Throwable;

class OrderController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Customer Orders
    |--------------------------------------------------------------------------
    */
    public function customer()
    {
        /*
        |--------------------------------------------------------------------------
        | Require Login
        |--------------------------------------------------------------------------
        */
        AuthMiddleware::handle();

        /*
        |--------------------------------------------------------------------------
        | Current User
        |--------------------------------------------------------------------------
        */
        $user =
            Session::get('user');

        /*
        |--------------------------------------------------------------------------
        | Database
        |--------------------------------------------------------------------------
        */
        $db =
            Database::connection();

        /*
        |--------------------------------------------------------------------------
        | Fetch Customer Orders
        |--------------------------------------------------------------------------
        */
        $stmt = $db->prepare("

            SELECT *

            FROM orders

            WHERE user_id = ?

            ORDER BY id DESC

        ");

        $stmt->execute([
            $user['id']
        ]);

        $orders =
            $stmt->fetchAll();

            foreach ($orders as $order) {

    $itemStmt = $db->prepare("

        SELECT

            order_items.*,

            products.name,
            products.image,

            seller_profiles.shop_name

        FROM order_items

        LEFT JOIN products

            ON products.id =
            order_items.product_id

        LEFT JOIN seller_profiles

            ON seller_profiles.id =
            order_items.seller_profile_id

        WHERE order_items.order_id = ?

    ");

    $itemStmt->execute([
        $order->id
    ]);

    $order->items =
        $itemStmt->fetchAll();
}

        /*
        |--------------------------------------------------------------------------
        | Load View
        |--------------------------------------------------------------------------
        */
        $this->view(
            'orders/customer',
            [

                'orders' =>
                $orders,
                'pageCss' => '/ani-mart/public/assets/css/pages/orders.css'

            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Farmer Orders
    |--------------------------------------------------------------------------
    */
    public function farmer()
    {
        AuthMiddleware::handle();

        $user =
            Session::get('user');

        $db =
            Database::connection();

        /*
    |--------------------------------------------------------------------------
    | Seller Profile
    |--------------------------------------------------------------------------
    */
        $sellerProfile = SellerProfileService::activeShop($user['id']);

        if (!$sellerProfile) {

            return $this->view(
                'orders/index',
                [
                    'orders' => []
                ]
            );
        }

        /*
    |--------------------------------------------------------------------------
    | Farmer Orders
    |--------------------------------------------------------------------------
    */
        $stmt = $db->prepare("

        SELECT

            orders.*,

            users.first_name,
            users.last_name,

            CONCAT(
                users.first_name,
                ' ',
                users.last_name
            ) AS customer_name,

            SUM(
                order_items.quantity
            ) AS quantity

        FROM orders

        INNER JOIN order_items

            ON order_items.order_id =
            orders.id

        LEFT JOIN users

            ON users.id =
            orders.user_id

        WHERE
            order_items.seller_profile_id = ?

        GROUP BY orders.id

        ORDER BY orders.id DESC

    ");

        $stmt->execute([
            $sellerProfile->id
        ]);

        $orders =
            $stmt->fetchAll();

        foreach ($orders as $order) {

            $itemStmt = $db->prepare("

        SELECT

            order_items.*,

            products.name,
            products.image

        FROM order_items

        LEFT JOIN products

            ON products.id =
            order_items.product_id

        WHERE order_items.order_id = ?
            AND order_items.seller_profile_id = ?

    ");

            $itemStmt->execute([
                $order->id,
                $sellerProfile->id
            ]);

            $order->items =
                $itemStmt->fetchAll();
        }

        return $this->view(
    'orders/index',
    [
        'orders' => $orders,
        'role'   => 'farmer',
        'pageCss' => '/ani-mart/public/assets/css/pages/orders.css',
    ]
);
    }

    public function updateOrderStatus()
    {
        csrf_verify();

        $user = Session::get('user');

        if (!$user) {
            header('Location: /ani-mart/public/login');
            exit;
        }

        if (strtolower($user['role'] ?? '') !== 'farmer') {
            http_response_code(403);
            echo '403 Unauthorized';
            exit;
        }

        $id =
            $_POST['order_id'] ?? 0;

        $status =
            $_POST['status'] ?? '';

        $allowed = [

            'accepted',
            'preparing',
            'out_for_delivery',
            'cancelled'
        ];

        if (
            $id &&
            in_array(
                $status,
                $allowed
            )
        ) {

            $db =
                Database::connection();

            (new OrderWorkflowSchemaService())->ensure();

            $activeShop = SellerProfileService::activeShop($user['id']);
            if (!$activeShop || !$this->orderBelongsToFarmer($db, $id, $user['id'], $activeShop->id)) {
                http_response_code(403);
                echo '403 Unauthorized';
                exit;
            }

            $restockedProductIds = [];
            $db->beginTransaction();

            try {
                $currentOrder = $db->prepare("SELECT status FROM orders WHERE id = ? LIMIT 1 FOR UPDATE");
                $currentOrder->execute([$id]);
                if (!$currentOrder->fetch()) {
                    throw new DomainException('Order not found.');
                }

                $this->initializeSellerOrderStatuses($db, (int) $id);

                $sellerStatusStmt = $db->prepare("SELECT status
                    FROM order_seller_statuses
                    WHERE order_id = ? AND seller_profile_id = ?
                    LIMIT 1 FOR UPDATE");
                $sellerStatusStmt->execute([(int) $id, (int) $activeShop->id]);
                $sellerStatus = $sellerStatusStmt->fetch();
                $oldStatus = $sellerStatus ? (string) $sellerStatus->status : null;

                if ($oldStatus === null || !MarketplaceRulesService::canTransitionSellerOrder($oldStatus, $status)) {
                    throw new DomainException('Your shop portion of this order can no longer move to the selected status.');
                }

                $pendingCancellation = $db->prepare("SELECT id
                    FROM order_cancellation_requests
                    WHERE order_id = ? AND seller_user_id = ? AND status = 'pending'
                    LIMIT 1");
                $pendingCancellation->execute([(int) $id, (int) $user['id']]);
                if ($pendingCancellation->fetch()) {
                    throw new DomainException('Answer the customer cancellation request before changing your shop order status.');
                }

                if ($status === 'cancelled') {
                    $restockedProductIds = $this->restoreSellerInventory(
                        $db,
                        (int) $id,
                        (int) $activeShop->id
                    );
                }

                $stmt = $db->prepare("UPDATE order_seller_statuses
                    SET status = ?
                    WHERE order_id = ? AND seller_profile_id = ?");
                $stmt->execute([$status, (int) $id, (int) $activeShop->id]);

                $this->syncAggregateOrderStatus($db, (int) $id);
                $db->commit();
            } catch (DomainException $exception) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                $this->jsonResponse(false, $exception->getMessage(), 422);
            } catch (Throwable $exception) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                $this->jsonResponse(false, 'The shop order status could not be updated. Please try again.', 500);
            }

            if ($status === 'cancelled') {
                $inventoryNotifier = new InventoryNotificationService();
                foreach ($restockedProductIds as $productId) {
                    $inventoryNotifier->evaluateProduct($productId);
                }
            }

            if ($oldStatus !== null && $oldStatus !== $status) {
                AuditLogService::log(
                    'ORDER_STATUS_CHANGED',
                    'order',
                    (int) $id,
                    [
                        'old_status' => $oldStatus,
                        'new_status' => $status,
                        'seller_profile_id' => (int) $activeShop->id,
                        'source' => 'seller_portion_status_update'
                    ],
                    (int) $user['id'],
                    '/ani-mart/public/farmer/orders/status',
                    'success'
                );
            }

            // Get order details for notification
            $stmt = $db->prepare("
                SELECT o.*, u.id as user_id
                FROM orders o
                INNER JOIN users u ON u.id = o.user_id
                WHERE o.id = ?
                LIMIT 1
            ");

            $stmt->execute([$id]);
            $order = $stmt->fetch();

            if ($order) {
                $statusMessages = [
                    'accepted' => [
                        'title' => 'Order Accepted',
                        'message' => ($activeShop->shop_name ?? 'A shop') . ' accepted its part of your order.',
                        'icon' => 'bi-check-circle',
                        'color' => 'success'
                    ],
                    'preparing' => [
                        'title' => 'Order Being Prepared',
                        'message' => ($activeShop->shop_name ?? 'A shop') . ' is preparing its part of your order.',
                        'icon' => 'bi-basket',
                        'color' => 'info'
                    ],
                    'out_for_delivery' => [
                        'title' => 'Order Out for Delivery',
                        'message' => ($activeShop->shop_name ?? 'A shop') . ' marked its part of your order out for delivery.',
                        'icon' => 'bi-truck',
                        'color' => 'warning'
                    ],
                    'cancelled' => [
                        'title' => 'Shop Items Cancelled',
                        'message' => ($activeShop->shop_name ?? 'A shop') . ' cancelled its part of your order. Those quantities were returned to inventory.',
                        'icon' => 'bi-x-circle',
                        'color' => 'danger'
                    ]
                ];

                if (isset($statusMessages[$status])) {
                    NotificationController::create(
                        $order->user_id,
                        'order',
                        $statusMessages[$status]['title'],
                        $statusMessages[$status]['message'],
                        $statusMessages[$status]['icon'],
                        $statusMessages[$status]['color'],
                        '/ani-mart/public/orders?id=' . $id,
                        'order',
                        $id,
                        '/ani-mart/public/orders?id=' . $id
                    );
                }

                // Add a one-time seller message when the order is first accepted.
                // This makes the acceptance visible in the customer's Messages area,
                // in addition to the standard order notification.
                if ($status === 'accepted' && $oldStatus !== 'accepted') {
                    $conversationModel = new Conversation();
                    $conversation = $conversationModel->findExisting(
                        (int) $order->user_id,
                        (int) $user['id'],
                        null,
                        (int) $id
                    );

                    if (!$conversation) {
                        $shopName = trim((string) ($activeShop->shop_name ?? 'Your seller'));
                        $acceptanceMessage = $shopName . ' accepted its part of your order and will begin preparing it shortly.';
                        $conversationId = $conversationModel->create([
                            'customer_id' => (int) $order->user_id,
                            'farmer_id' => (int) $user['id'],
                            'order_id' => (int) $id,
                            'last_message' => $acceptanceMessage,
                            'last_message_at' => date('Y-m-d H:i:s'),
                        ]);
                    } else {
                        $conversationId = (int) $conversation->id;
                        $conversationModel->updateContext($conversationId, null, (int) $id);
                    }

                    $publicOrderId = trim((string) ($order->order_number ?? '')) ?: (string) ((int) $id);
                    $shopName = trim((string) ($activeShop->shop_name ?? 'Your seller'));
                    $thankYouMessage = 'Order ID: ' . $publicOrderId . ' — ' . $shopName . ' accepted its part of your order and will begin preparing it shortly.';
                    (new Message())->create([
                        'conversation_id' => $conversationId,
                        'sender_id' => (int) $user['id'],
                        'message' => $thankYouMessage,
                        'is_read' => 0,
                    ]);
                    $conversationModel->updateLastMessage($conversationId, $thankYouMessage);

                    NotificationController::create(
                        (int) $order->user_id,
                        'message',
                        'New message from your seller',
                        $thankYouMessage,
                        'bi-chat-left-text',
                        'info',
                        "/ani-mart/public/messages/chat?id={$conversationId}",
                        'conversation',
                        $conversationId,
                        "/ani-mart/public/messages/chat?id={$conversationId}"
                    );
                }
            }

            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
            exit;
        }

        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
        exit;
    }

    public function requestCancellation()
    {
        csrf_verify();

        $user = Session::get('user');
        if (!$user || !in_array(strtolower((string) ($user['role'] ?? '')), ['customer', 'farmer'], true)) {
            $this->jsonResponse(false, 'Please sign in to request a cancellation.', 403);
        }

        $orderId = (int) ($_POST['order_id'] ?? 0);

        try {
            $order = (new OrderCancellationService())->request(
                $orderId,
                (int) $user['id'],
                (string) ($_POST['reason'] ?? '')
            );

            $orderNumber = trim((string) ($order->order_number ?? '')) ?: (string) $orderId;
            NotificationController::create(
                (int) $order->seller_user_id,
                'order',
                'Cancellation Request Received',
                "The customer requested cancellation of order #{$orderNumber}. Reason: {$order->request_reason}",
                'bi-x-circle',
                'warning',
                '/ani-mart/public/farmer/orders?id=' . $orderId,
                'order',
                $orderId,
                '/ani-mart/public/farmer/orders?id=' . $orderId
            );
            NotificationController::create(
                (int) $user['id'],
                'order',
                'Cancellation Request Sent',
                "Your cancellation request for order #{$orderNumber} was sent to the seller.",
                'bi-hourglass-split',
                'info',
                '/ani-mart/public/orders?id=' . $orderId,
                'order',
                $orderId,
                '/ani-mart/public/orders?id=' . $orderId
            );

            AuditLogService::log('ORDER_CANCELLATION_REQUESTED', 'order', $orderId, [
                'request_id' => (int) $order->request_id,
                'reason' => $order->request_reason,
            ], (int) $user['id'], '/ani-mart/public/customer/orders/cancellation/request', 'success');

            $this->jsonResponse(true, 'Your cancellation request was sent to the seller.');
        } catch (DomainException $exception) {
            $this->jsonResponse(false, $exception->getMessage(), 422);
        } catch (Throwable $exception) {
            $this->jsonResponse(false, 'The cancellation request could not be submitted. Please try again.', 500);
        }
    }

    public function respondToCancellation()
    {
        csrf_verify();

        $user = Session::get('user');
        if (!$user || strtolower((string) ($user['role'] ?? '')) !== 'farmer') {
            $this->jsonResponse(false, 'Only the seller can answer this request.', 403);
        }

        $orderId = (int) ($_POST['order_id'] ?? 0);
        $decision = strtolower(trim((string) ($_POST['decision'] ?? '')));

        try {
            $request = (new OrderCancellationService())->respond(
                $orderId,
                (int) $user['id'],
                $decision,
                (string) ($_POST['reason'] ?? '')
            );

            $accepted = $decision === 'accepted';
            $orderNumber = trim((string) ($request->order_number ?? '')) ?: (string) $orderId;
            $customerTitle = $accepted ? 'Cancellation Request Accepted' : 'Cancellation Request Declined';
            $customerMessage = $accepted
                ? "Order #{$orderNumber} has been cancelled and its items were returned to inventory. Seller reason: {$request->response_reason}"
                : "Your cancellation request for order #{$orderNumber} was declined. Seller reason: {$request->response_reason}";

            NotificationController::create(
                (int) $request->customer_id,
                'order',
                $customerTitle,
                $customerMessage,
                $accepted ? 'bi-check-circle' : 'bi-x-circle',
                $accepted ? 'success' : 'danger',
                '/ani-mart/public/orders?id=' . $orderId,
                'order',
                $orderId,
                '/ani-mart/public/orders?id=' . $orderId
            );
            NotificationController::create(
                (int) $user['id'],
                'order',
                'Cancellation Decision Recorded',
                "You {$decision} the cancellation request for order #{$orderNumber}. Reason: {$request->response_reason}",
                'bi-check2-circle',
                'info',
                '/ani-mart/public/farmer/orders?id=' . $orderId,
                'order',
                $orderId,
                '/ani-mart/public/farmer/orders?id=' . $orderId
            );

            AuditLogService::log('ORDER_CANCELLATION_' . strtoupper($decision), 'order', $orderId, [
                'request_id' => (int) $request->id,
                'reason' => $request->response_reason,
                'inventory_restored' => $accepted,
            ], (int) $user['id'], '/ani-mart/public/farmer/orders/cancellation/respond', 'success');

            $this->jsonResponse(true, $accepted
                ? 'The order was cancelled and all quantities were returned to inventory.'
                : 'The cancellation request was declined.');
        } catch (DomainException $exception) {
            $this->jsonResponse(false, $exception->getMessage(), 422);
        } catch (Throwable $exception) {
            $this->jsonResponse(false, 'The cancellation decision could not be saved. Please try again.', 500);
        }
    }

    public function uploadProof()
    {
        csrf_verify();
        
        $user = Session::get('user');

        if (!$user) {
            header('Location: /ani-mart/public/login');
            exit;
        }

        if (strtolower($user['role'] ?? '') !== 'farmer') {
            http_response_code(403);
            echo '403 Unauthorized';
            exit;
        }

        $orderId =
            $_POST['order_id'] ?? 0;

        $db = Database::connection();

        if (!$this->orderBelongsToFarmer($db, $orderId, $user['id'])) {
            http_response_code(403);
            echo '403 Unauthorized';
            exit;
        }

        if (
            empty($_FILES['proof_image'])
            ||
            $_FILES['proof_image']['error']
            !== UPLOAD_ERR_OK
        ) {

            return $this->redirect(
                '/ani-mart/public/seller/orders'
            );
        }

        /*
    |--------------------------------------------------------------------------
    | CLOUDINARY UPLOAD
    |--------------------------------------------------------------------------
    */

        $cloudinary = new CloudinaryService();
        $upload = $cloudinary->uploadSensitive(
            $_FILES['proof_image'],
            'ani-mart/delivery-proofs',
            [
                'entity_type' => 'order',
                'entity_id' => (int) $orderId,
                'owner_user_id' => (int) $user['id'],
                'retention_days' => 365,
            ]
        );

        if (!is_array($upload) || empty($upload['secure_url'])) {
            return $this->redirect(
                '/ani-mart/public/seller/orders'
            );
        }

        $proofImage =
            $upload['secure_url'];

        /*
    |--------------------------------------------------------------------------
    | UPDATE ORDER
    |--------------------------------------------------------------------------
    */

        $db =
            Database::connection();

        $currentOrder = $db->prepare("SELECT status FROM orders WHERE id = ? LIMIT 1");
        $currentOrder->execute([$orderId]);
        $existingOrder = $currentOrder->fetch();
        $oldStatus = $existingOrder ? (string) $existingOrder->status : null;

        if ($oldStatus !== 'out_for_delivery') {
            if (!empty($upload['public_id'])) {
                $cloudinary->delete((string) $upload['public_id'], ['delivery_type' => (string) ($upload['delivery_type'] ?? 'authenticated')]);
            }
            Session::flash('error', 'Delivery proof can only be uploaded after the order is out for delivery.');
            return $this->redirect('/ani-mart/public/farmer/orders');
        }

        $stmt =
            $db->prepare("

            UPDATE orders

            SET

                proof_image = ?,
                status = 'delivered'

            WHERE id = ?

        ");

        $stmt->execute([

            $proofImage,
            $orderId

        ]);

        if ($oldStatus !== null && $oldStatus !== 'delivered') {
            AuditLogService::log(
                'ORDER_STATUS_CHANGED',
                'order',
                (int) $orderId,
                [
                    'old_status' => $oldStatus,
                    'new_status' => 'delivered',
                    'source' => 'delivery_proof_uploaded'
                ],
                (int) $user['id'],
                '/ani-mart/public/farmer/orders/upload-proof',
                'success'
            );
        }

        $stmt = $db->prepare("
            SELECT user_id, order_number
            FROM orders
            WHERE id = ?
            LIMIT 1
        ");

        $stmt->execute([
            $orderId
        ]);

        $order = $stmt->fetch();

        if ($order) {
            NotificationController::create(
                $order->user_id,
                'order',
                'Delivery Proof Uploaded',
                "Farmer uploaded delivery proof for order #{$order->order_number}. Please confirm delivery after checking your order.",
                'bi-camera',
                'info',
                '/ani-mart/public/customer/orders?id=' . $orderId,
                'order',
                $orderId,
                '/ani-mart/public/customer/orders?id=' . $orderId
            );
        }

        return $this->redirect(
            '/ani-mart/public/farmer/orders'
        );
    }

    public function confirm()
    {
        $user = Session::get('user');

        if (!$user) {
            header('Location: /ani-mart/public/login');
            exit;
        }

        if (strtolower($user['role'] ?? '') !== 'customer') {
            http_response_code(403);
            echo '403 Unauthorized';
            exit;
        }

        $orderId =
            $_GET['id'] ?? 0;

        $db =
            Database::connection();

        if (!$this->orderBelongsToCustomer($db, $orderId, $user['id'])) {
            http_response_code(403);
            echo '403 Unauthorized';
            exit;
        }

        $currentOrder = $db->prepare("SELECT status FROM orders WHERE id = ? LIMIT 1");
        $currentOrder->execute([$orderId]);
        $existingOrder = $currentOrder->fetch();
        $oldStatus = $existingOrder ? (string) $existingOrder->status : null;

        $stmt =
            $db->prepare("

            UPDATE orders

            SET

                status = 'completed',
                customer_confirmed = 1,
                delivered_at = NOW()

            WHERE id = ?

        ");

        $stmt->execute([
            $orderId
        ]);

        if ($oldStatus !== null && $oldStatus !== 'completed') {
            AuditLogService::log(
                'ORDER_STATUS_CHANGED',
                'order',
                (int) $orderId,
                [
                    'old_status' => $oldStatus,
                    'new_status' => 'completed',
                    'source' => 'customer_confirmation'
                ],
                (int) $user['id'],
                '/ani-mart/public/orders/confirm',
                'success'
            );
        }

        return $this->redirect(
            '/ani-mart/public/orders'
        );
    }

    public function receipt()
    {
        [$order, $items, $receiptRole] = $this->receiptData();

        return $this->view('orders/receipt', [
            'order' => $order,
            'items' => $items,
            'receiptRole' => $receiptRole,
            'download' => false,
        ]);
    }

    public function downloadReceipt()
    {
        [$order, $items, $receiptRole] = $this->receiptData();

        $orderNumber = preg_replace(
            '/[^A-Za-z0-9_-]+/',
            '-',
            (string) ($order->order_number ?? $order->id)
        );

        header('Content-Type: text/html; charset=UTF-8');
        header('Content-Disposition: attachment; filename="ani-mart-receipt-' . $orderNumber . '.html"');
        header('X-Content-Type-Options: nosniff');

        return $this->view('orders/receipt', [
            'order' => $order,
            'items' => $items,
            'receiptRole' => $receiptRole,
            'download' => true,
        ]);
    }

    private function receiptData(): array
    {
        $user = Session::get('user');

        if (!$user) {
            header('Location: /ani-mart/public/login');
            exit;
        }

        $accountRole = strtolower($user['role'] ?? '');

        if (!in_array($accountRole, ['customer', 'farmer'], true)) {
            http_response_code(403);
            echo '403 Unauthorized';
            exit;
        }

        $orderId = (int) ($_GET['id'] ?? 0);
        $db = Database::connection();
        $sellerProfileId = null;
        $receiptRole = 'customer';

        $canViewReceipt = $this->orderBelongsToCustomer($db, $orderId, $user['id']);

        if (!$canViewReceipt && $accountRole === 'farmer') {
            $receiptRole = 'farmer';
            $activeShop = SellerProfileService::activeShop($user['id']);
            $sellerProfileId = $activeShop ? (int) $activeShop->id : 0;
            $canViewReceipt = $sellerProfileId > 0
                && $this->orderBelongsToFarmer($db, $orderId, $user['id'], $sellerProfileId);
        }

        if ($orderId <= 0 || !$canViewReceipt) {
            http_response_code(404);
            echo 'Order not found.';
            exit;
        }

        $stmt = $db->prepare("
            SELECT
                orders.*,
                users.first_name,
                users.last_name,
                users.phone AS customer_phone,
                (
                    SELECT sp.delivery_fee
                    FROM order_items oi
                    INNER JOIN seller_profiles sp ON sp.id = oi.seller_profile_id
                    WHERE oi.order_id = orders.id
                    LIMIT 1
                ) AS seller_delivery_fee
            FROM orders
            LEFT JOIN users ON users.id = orders.user_id
            WHERE orders.id = ?
            LIMIT 1
        ");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();

        if (!$order) {
            http_response_code(404);
            echo 'Order not found.';
            exit;
        }

        if ((string) ($order->status ?? '') !== 'completed' || (int) ($order->customer_confirmed ?? 0) !== 1) {
            http_response_code(404);
            echo 'Receipt is only available after the customer confirms the order is complete.';
            exit;
        }

        $itemSql = "
            SELECT
                order_items.*,
                products.name,
                seller_profiles.shop_name
            FROM order_items
            LEFT JOIN products ON products.id = order_items.product_id
            LEFT JOIN seller_profiles ON seller_profiles.id = order_items.seller_profile_id
            WHERE order_items.order_id = ?
        ";
        $itemParams = [$orderId];

        if ($receiptRole === 'farmer') {
            $itemSql .= " AND order_items.seller_profile_id = ?";
            $itemParams[] = $sellerProfileId;
        }

        $itemSql .= " ORDER BY order_items.id ASC";
        $itemStmt = $db->prepare($itemSql);
        $itemStmt->execute($itemParams);

        return [$order, $itemStmt->fetchAll(), $receiptRole];
    }

    private function orderBelongsToCustomer($db, $orderId, $userId)
    {
        $stmt = $db->prepare(
            "SELECT id FROM orders WHERE id = ? AND user_id = ? LIMIT 1"
        );
        $stmt->execute([$orderId, $userId]);

        return (bool) $stmt->fetch();
    }

    private function initializeSellerOrderStatuses($db, int $orderId): void
    {
        $stmt = $db->prepare("INSERT IGNORE INTO order_seller_statuses
            (order_id, seller_profile_id, status)
            SELECT DISTINCT
                oi.order_id,
                oi.seller_profile_id,
                CASE
                    WHEN (
                        SELECT COUNT(DISTINCT sibling.seller_profile_id)
                        FROM order_items sibling
                        WHERE sibling.order_id = oi.order_id
                    ) > 1 THEN 'pending'
                    ELSE o.status
                END
            FROM order_items oi
            INNER JOIN orders o ON o.id = oi.order_id
            WHERE oi.order_id = ?");
        $stmt->execute([$orderId]);
    }

    private function restoreSellerInventory($db, int $orderId, int $sellerProfileId): array
    {
        $items = $db->prepare("SELECT product_id, product_variant_id, quantity, subtotal
            FROM order_items
            WHERE order_id = ? AND seller_profile_id = ?
            FOR UPDATE");
        $items->execute([$orderId, $sellerProfileId]);

        $productIds = [];
        $cancelledSubtotal = 0.0;
        foreach ($items->fetchAll() as $item) {
            $productId = (int) $item->product_id;
            $quantity = max(0, (int) $item->quantity);
            $cancelledSubtotal += (float) ($item->subtotal ?? 0);
            if ($productId <= 0 || $quantity === 0) {
                continue;
            }

            $productIds[$productId] = true;
            $db->prepare('UPDATE products SET stock = stock + ? WHERE id = ?')
                ->execute([$quantity, $productId]);

            if (!empty($item->product_variant_id)) {
                $db->prepare('UPDATE product_variants
                    SET stock = stock + ?
                    WHERE id = ? AND product_id = ?')
                    ->execute([$quantity, (int) $item->product_variant_id, $productId]);
            }
        }

        $deliveryFeeStmt = $db->prepare('SELECT COALESCE(delivery_fee, 0)
            FROM seller_profiles
            WHERE id = ?
            LIMIT 1');
        $deliveryFeeStmt->execute([$sellerProfileId]);
        $cancelledDeliveryFee = max(0.0, (float) $deliveryFeeStmt->fetchColumn());

        $db->prepare('UPDATE orders
            SET total_amount = GREATEST(total_amount - ?, 0),
                delivery_fee = GREATEST(COALESCE(delivery_fee, 0) - ?, 0)
            WHERE id = ?')
            ->execute([
                $cancelledSubtotal + $cancelledDeliveryFee,
                $cancelledDeliveryFee,
                $orderId,
            ]);

        return array_map('intval', array_keys($productIds));
    }

    private function syncAggregateOrderStatus($db, int $orderId): string
    {
        $stmt = $db->prepare("SELECT status
            FROM order_seller_statuses
            WHERE order_id = ?
            FOR UPDATE");
        $stmt->execute([$orderId]);
        $statuses = array_map(
            static fn($row): string => strtolower((string) $row->status),
            $stmt->fetchAll()
        );

        if ($statuses === [] || count(array_filter($statuses, static fn($status) => $status !== 'cancelled')) === 0) {
            $aggregateStatus = 'cancelled';
        } else {
            $rank = [
                'pending' => 0,
                'accepted' => 1,
                'preparing' => 2,
                'out_for_delivery' => 3,
                'delivered' => 4,
                'completed' => 5,
            ];
            $activeStatuses = array_values(array_filter(
                $statuses,
                static fn($status) => $status !== 'cancelled'
            ));
            usort($activeStatuses, static fn($left, $right) => ($rank[$left] ?? 0) <=> ($rank[$right] ?? 0));
            $aggregateStatus = $activeStatuses[0] ?? 'pending';
        }

        $db->prepare('UPDATE orders SET status = ? WHERE id = ?')
            ->execute([$aggregateStatus, $orderId]);

        return $aggregateStatus;
    }

    private function orderBelongsToFarmer($db, $orderId, $userId, $sellerProfileId)
    {
        $stmt = $db->prepare(
            "SELECT o.id
            FROM orders o
            INNER JOIN order_items oi ON oi.order_id = o.id
            INNER JOIN seller_profiles sp ON sp.id = oi.seller_profile_id
            WHERE o.id = ? AND sp.user_id = ? AND sp.id = ?
            LIMIT 1"
        );
        $stmt->execute([$orderId, $userId, $sellerProfileId]);

        return (bool) $stmt->fetch();
    }

    private function jsonResponse(bool $success, string $message, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => $success, 'message' => $message]);
        exit;
    }
}
