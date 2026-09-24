<?php

namespace App\Controllers;

use App\Services\SchemaInspector;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Controllers\NotificationController;
use App\Services\AuditLogService;
use App\Services\CloudinaryService;
use App\Services\NotificationService;
use App\Services\OrderHistoryReportService;
use App\Services\OrderWorkflowSchemaService;
use App\Services\TrustScoreService;
use App\Services\SensitiveDataCipher;

class CustomerController extends Controller
{
    private function canShop(?array $user): bool
    {
        return $user !== null
            && in_array(strtolower((string) ($user['role'] ?? '')), ['customer', 'farmer'], true);
    }

    /*
    |--------------------------------------------------------------------------
    | Orders
    |--------------------------------------------------------------------------
    */

    public function orders()
    {
        $user = Session::get('user');

        if (
            !$this->canShop($user)
        ) {

            header(
                'Location: /ani-mart/public/login'
            );

            exit;
        }

        $db = Database::connection();

        (new OrderWorkflowSchemaService())->ensure();
        $this->ensureReviewsTable();

        $sellerStatuses = $db->prepare("INSERT IGNORE INTO order_seller_statuses
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
            WHERE o.user_id = ?");
        $sellerStatuses->execute([(int) $user['id']]);

        $stmt = $db->prepare("

            SELECT
                orders.*,
                cancellation.id AS cancellation_request_id,
                cancellation.status AS cancellation_request_status,
                cancellation.request_reason AS cancellation_request_reason,
                cancellation.request_reason_encrypted AS cancellation_request_reason_encrypted,
                cancellation.response_reason AS cancellation_response_reason,
                cancellation.response_reason_encrypted AS cancellation_response_reason_encrypted,
                cancellation.requested_at AS cancellation_requested_at,
                cancellation.responded_at AS cancellation_responded_at,
                returns.id AS return_request_id,
                returns.status AS return_request_status,
                returns.refunded_amount AS return_refunded_amount

            FROM orders

            LEFT JOIN order_cancellation_requests cancellation
                ON cancellation.order_id = orders.id

            LEFT JOIN return_requests returns
                ON returns.order_id = orders.id

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

                    seller_profiles.shop_name,
                    seller_profiles.verification_badge,

                    seller_status.status AS seller_order_status,

                    reviews.id AS review_id,
                    reviews.rating AS review_rating,
                    reviews.freshness_rating AS freshness_rating,
                    reviews.seller_responsiveness_rating AS seller_responsiveness_rating,
                    reviews.product_condition_rating AS product_condition_rating,
                    reviews.driver_friendliness_rating AS driver_friendliness_rating,
                    reviews.delivery_speed_rating AS delivery_speed_rating,
                    reviews.comment AS review_comment,
                    reviews.review_photo AS review_photo,
                    reviews.created_at AS review_created_at

                FROM order_items

                LEFT JOIN products

                    ON products.id =
                    order_items.product_id

                LEFT JOIN seller_profiles

                    ON seller_profiles.id =
                    order_items.seller_profile_id

                LEFT JOIN order_seller_statuses seller_status

                    ON seller_status.order_id = order_items.order_id
                    AND seller_status.seller_profile_id = order_items.seller_profile_id

                LEFT JOIN reviews

                    ON reviews.order_item_id =
                    order_items.id

                WHERE order_items.order_id = ?

                ORDER BY order_items.seller_profile_id ASC, order_items.id ASC

            ");

            $itemStmt->execute([
                $order->id
            ]);

            $order->items =
                $itemStmt->fetchAll();
        }

        return $this->view(
            'orders/index',
            [

                'orders' =>
                $orders,

                'role' =>
                'customer',

                'pageCss' => '/ani-mart/public/assets/css/pages/orders.css',

            ]
        );
    }

    public function exportOrderHistoryCsv()
    {
        [$user, $orders, $filters] = $this->orderHistoryExportData();
        $name = trim((string) ($user['first_name'] ?? '') . ' ' . (string) ($user['last_name'] ?? '')) ?: 'Customer';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="ani-mart-order-history-' . date('Y-m-d') . '.csv"');
        header('X-Content-Type-Options: nosniff');
        echo OrderHistoryReportService::csv($orders, $filters, $name);
        exit;
    }

    public function exportOrderHistoryPdf()
    {
        [$user, $orders, $filters] = $this->orderHistoryExportData();
        $name = trim((string) ($user['first_name'] ?? '') . ' ' . (string) ($user['last_name'] ?? '')) ?: 'Customer';
        $pdf = OrderHistoryReportService::pdf($orders, $filters, $name);
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="ani-mart-order-history-' . date('Y-m-d') . '.pdf"');
        header('Content-Length: ' . strlen($pdf));
        header('X-Content-Type-Options: nosniff');
        echo $pdf;
        exit;
    }

    private function orderHistoryExportData(): array
    {
        $user = Session::get('user');
        if (!$this->canShop($user)) {
            header('Location: /ani-mart/public/login');
            exit;
        }
        $filters = $this->historyFilters($_GET);
        $orders = array_values(array_filter($this->customerOrderHistory((int) $user['id']), function ($order) use ($filters) {
            $date = substr((string) ($order->created_at ?? ''), 0, 10);
            if ($filters['date_from'] !== '' && $date < $filters['date_from']) return false;
            if ($filters['date_to'] !== '' && $date > $filters['date_to']) return false;
            if ($filters['status'] !== '' && (string) $order->status !== $filters['status']) return false;
            $sellers = array_map(static fn($item) => strtolower(trim((string) ($item->shop_name ?? ''))), $order->items ?? []);
            $products = array_map(static fn($item) => strtolower(trim((string) ($item->name ?? ''))), $order->items ?? []);
            if ($filters['seller'] !== '' && !in_array(strtolower($filters['seller']), $sellers, true)) return false;
            if ($filters['product'] !== '' && !in_array(strtolower($filters['product']), $products, true)) return false;
            return true;
        }));
        return [$user, $orders, $filters];
    }

    private function historyFilters(array $input): array
    {
        $validDate = static function ($value): string {
            $value = trim((string) $value);
            $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
            return $date && $date->format('Y-m-d') === $value ? $value : '';
        };
        $status = strtolower(trim((string) ($input['status'] ?? '')));
        if (!in_array($status, ['pending', 'accepted', 'preparing', 'out_for_delivery', 'delivered', 'completed', 'cancelled'], true)) $status = '';
        return [
            'date_from' => $validDate($input['date_from'] ?? ''),
            'date_to' => $validDate($input['date_to'] ?? ''),
            'seller' => mb_substr(trim((string) ($input['seller'] ?? '')), 0, 255),
            'product' => mb_substr(trim((string) ($input['product'] ?? '')), 0, 255),
            'status' => $status,
        ];
    }

    private function customerOrderHistory(int $userId): array
    {
        $db = Database::connection();
        $stmt = $db->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC');
        $stmt->execute([$userId]);
        $orders = $stmt->fetchAll();
        $items = $db->prepare('SELECT oi.*, p.name, sp.shop_name FROM order_items oi LEFT JOIN products p ON p.id = oi.product_id LEFT JOIN seller_profiles sp ON sp.id = oi.seller_profile_id WHERE oi.order_id = ? ORDER BY oi.id');
        foreach ($orders as $order) {
            $items->execute([(int) $order->id]);
            $order->items = $items->fetchAll();
        }
        return $orders;
    }

    /*
    |--------------------------------------------------------------------------
    | Store Review
    |--------------------------------------------------------------------------
    */

    public function storeReview()
    {
         csrf_verify();
         
        $user =
            Session::get('user');

        if (
            !$this->canShop($user)
        ) {

            header(
                'Location: /ani-mart/public/login'
            );

            exit;
        }

        $db =
            Database::connection();

        (new OrderWorkflowSchemaService())->ensure();
        $this->ensureReviewsTable();

        $orderItemId =
            (int) ($_POST['order_item_id'] ?? 0);

        $rating =
            (int) ($_POST['rating'] ?? 0);

        $comment =
            trim($_POST['comment'] ?? '');

        if (
            $orderItemId <= 0 ||
            $rating < 1 ||
            $rating > 5
        ) {

            Session::flash(
                'error',
                'Please choose a rating from 1 to 5 stars.'
            );

            return $this->redirect(
                '/ani-mart/public/customer/orders'
            );
        }

        if (mb_strlen($comment) > 1000) {
            $comment =
                mb_substr($comment, 0, 1000);
        }

        $stmt =
            $db->prepare("
                SELECT
                    order_items.*,
                    orders.user_id,
                    seller_status.status AS seller_order_status
                FROM order_items
                INNER JOIN orders
                    ON orders.id = order_items.order_id
                INNER JOIN order_seller_statuses seller_status
                    ON seller_status.order_id = order_items.order_id
                    AND seller_status.seller_profile_id = order_items.seller_profile_id
                WHERE order_items.id = ?
                    AND orders.user_id = ?
                    AND seller_status.status = 'completed'
                LIMIT 1
            ");

        $stmt->execute([
            $orderItemId,
            $user['id']
        ]);

        $item =
            $stmt->fetch();

        if (!$item) {
            Session::flash(
                'error',
                'You can review products only after confirming delivery.'
            );

            return $this->redirect(
                '/ani-mart/public/customer/orders'
            );
        }

        $existing =
            $db->prepare("
                SELECT id
                FROM reviews
                WHERE order_item_id = ?
                LIMIT 1
            ");

        $existing->execute([
            $orderItemId
        ]);

        if ($existing->fetch()) {
            Session::flash(
                'error',
                'You already reviewed this product.'
            );

            return $this->redirect(
                '/ani-mart/public/customer/orders'
            );
        }

        if (
            empty($_FILES['review_photo']) ||
            $_FILES['review_photo']['error'] !== UPLOAD_ERR_OK
        ) {
            Session::flash(
                'error',
                'Please upload a product photo as evidence.'
            );

            return $this->redirect(
                '/ani-mart/public/customer/orders'
            );
        }

        $upload = (new CloudinaryService())->uploadPublic($_FILES['review_photo'], 'ani-mart/review-photos', [
            'max_size' => 5 * 1024 * 1024,
            'allowed_mime_types' => ['image/jpeg', 'image/png', 'image/webp'],
            'allowed_extensions' => ['jpg', 'jpeg', 'png', 'webp'],
            'image_only' => true,
            'error_message' => 'Only JPG, PNG, and WEBP images are allowed for review evidence.'
        ]);

        if (!$upload || empty($upload['secure_url'])) {
            Session::flash(
                'error',
                'Unable to upload the photo. Please try again.'
            );

            return $this->redirect(
                '/ani-mart/public/customer/orders'
            );
        }

        $reviewPhotoPath = $upload['secure_url'];

        $freshnessRating =
            (int) ($_POST['freshness_rating'] ?? 0);

        $sellerResponsivenessRating =
            (int) ($_POST['seller_responsiveness_rating'] ?? 0);

        $productConditionRating = (int) ($_POST['product_condition_rating'] ?? 0);
        $driverFriendlinessRating = (int) ($_POST['driver_friendliness_rating'] ?? 0);
        $deliverySpeedRating = (int) ($_POST['delivery_speed_rating'] ?? 0);

        if (
            $freshnessRating < 1 ||
            $freshnessRating > 5 ||
            $sellerResponsivenessRating < 1 ||
            $sellerResponsivenessRating > 5 ||
            $productConditionRating < 1 ||
            $productConditionRating > 5 ||
            $driverFriendlinessRating < 1 ||
            $driverFriendlinessRating > 5 ||
            $deliverySpeedRating < 1 ||
            $deliverySpeedRating > 5
        ) {
            Session::flash(
                'error',
                'Please rate every product and delivery category from 1 to 5 stars.'
            );

            return $this->redirect(
                '/ani-mart/public/customer/orders'
            );
        }

        $insert =
            $db->prepare("
                INSERT INTO reviews (
                    product_id,
                    order_item_id,
                    user_id,
                    rating,
                    freshness_rating,
                    seller_responsiveness_rating,
                    product_condition_rating,
                    driver_friendliness_rating,
                    delivery_speed_rating,
                    comment,
                    review_photo,
                    created_at
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");

        $insert->execute([
            $item->product_id,
            $orderItemId,
            $user['id'],
            $rating,
            $freshnessRating,
            $sellerResponsivenessRating,
            $productConditionRating,
            $driverFriendlinessRating,
            $deliverySpeedRating,
            $comment,
            $reviewPhotoPath
        ]);

        $reviewId = (int) $db->lastInsertId();
        if (!empty($upload['public_id'])) {
            (new CloudinaryService())->associate((string) $upload['public_id'], 'review', $reviewId, (int) $user['id']);
        }
        (new TrustScoreService())->syncPositiveReviewReward($reviewId);

        Session::flash(
            'success',
            'Thank you for sharing your feedback and photo.'
        );

        return $this->redirect(
            '/ani-mart/public/customer/orders'
        );
    }

    public function updateReview()
    {
        csrf_verify();

        $user = Session::get('user');
        if (!$this->canShop($user)) {
            header('Location: /ani-mart/public/login');
            exit;
        }

        $db = Database::connection();
        $this->ensureReviewsTable();
        $reviewId = (int) ($_POST['review_id'] ?? 0);
        $rating = (int) ($_POST['rating'] ?? 0);
        $freshnessRating = (int) ($_POST['freshness_rating'] ?? 0);
        $sellerResponsivenessRating = (int) ($_POST['seller_responsiveness_rating'] ?? 0);
        $productConditionRating = (int) ($_POST['product_condition_rating'] ?? 0);
        $driverFriendlinessRating = (int) ($_POST['driver_friendliness_rating'] ?? 0);
        $deliverySpeedRating = (int) ($_POST['delivery_speed_rating'] ?? 0);
        $comment = trim($_POST['comment'] ?? '');

        if ($reviewId <= 0 || $rating < 1 || $rating > 5 || $freshnessRating < 1 || $freshnessRating > 5 || $sellerResponsivenessRating < 1 || $sellerResponsivenessRating > 5 || $productConditionRating < 1 || $productConditionRating > 5 || $driverFriendlinessRating < 1 || $driverFriendlinessRating > 5 || $deliverySpeedRating < 1 || $deliverySpeedRating > 5) {
            Session::flash('error', 'Please rate every product and delivery category from 1 to 5 stars.');
            return $this->redirect('/ani-mart/public/customer/orders');
        }

        $stmt = $db->prepare("            SELECT reviews.id, reviews.rating, reviews.freshness_rating, reviews.seller_responsiveness_rating, reviews.product_condition_rating, reviews.driver_friendliness_rating, reviews.delivery_speed_rating, reviews.review_photo
            FROM reviews
            INNER JOIN order_items ON order_items.id = reviews.order_item_id
            INNER JOIN orders ON orders.id = order_items.order_id
            WHERE reviews.id = ?
                AND reviews.user_id = ?
                AND orders.status = 'completed'
            LIMIT 1
        ");
        $stmt->execute([$reviewId, $user['id']]);
        $review = $stmt->fetch();

        if (!$review) {
            Session::flash('error', 'This review cannot be edited.');
            return $this->redirect('/ani-mart/public/customer/orders');
        }

        $reviewPhotoPath = $review->review_photo ?? null;
        if (!empty($_FILES['review_photo']['name'])) {
            $upload = (new CloudinaryService())->uploadPublic($_FILES['review_photo'], 'ani-mart/review-photos', [
                'max_size' => 5 * 1024 * 1024,
                'allowed_mime_types' => ['image/jpeg', 'image/png', 'image/webp'],
                'allowed_extensions' => ['jpg', 'jpeg', 'png', 'webp'],
                'image_only' => true,
                'error_message' => 'Only JPG, PNG, and WEBP images are allowed for review evidence.'
            ]);

            if (!$upload || empty($upload['secure_url'])) {
                Session::flash('error', 'Unable to upload the updated review photo.');
                return $this->redirect('/ani-mart/public/customer/orders');
            }

            $reviewPhotoPath = $upload['secure_url'];
        }

        if (mb_strlen($comment) > 1000) {
            $comment = mb_substr($comment, 0, 1000);
        }

        $update = $db->prepare("            UPDATE reviews
            SET rating = ?, freshness_rating = ?, seller_responsiveness_rating = ?, product_condition_rating = ?, driver_friendliness_rating = ?, delivery_speed_rating = ?, comment = ?, review_photo = ?, updated_at = NOW()
            WHERE id = ? AND user_id = ?
        ");
        $update->execute([$rating, $freshnessRating, $sellerResponsivenessRating, $productConditionRating, $driverFriendlinessRating, $deliverySpeedRating, $comment, $reviewPhotoPath, $reviewId, $user['id']]);

        if ($reviewPhotoPath !== ($review->review_photo ?? null)) {
            $imageService = new CloudinaryService();
            if (!empty($review->review_photo)) {
                $imageService->deleteByUrl((string) $review->review_photo, false);
            }
            if (!empty($upload['public_id'])) {
                $imageService->associate((string) $upload['public_id'], 'review', $reviewId, (int) $user['id']);
            }
        }

        (new TrustScoreService())->syncPositiveReviewReward($reviewId);

        AuditLogService::log(
            'REVIEW_UPDATED',
            'review',
            $reviewId,
            [
                'old_rating' => (int) ($review->rating ?? 0),
                'new_rating' => $rating,
                'old_freshness_rating' => (int) ($review->freshness_rating ?? 0),
                'new_freshness_rating' => $freshnessRating,
                'old_seller_responsiveness_rating' => (int) ($review->seller_responsiveness_rating ?? 0),
                'new_seller_responsiveness_rating' => $sellerResponsivenessRating,
                'old_product_condition_rating' => (int) ($review->product_condition_rating ?? 0),
                'new_product_condition_rating' => $productConditionRating,
                'old_driver_friendliness_rating' => (int) ($review->driver_friendliness_rating ?? 0),
                'new_driver_friendliness_rating' => $driverFriendlinessRating,
                'old_delivery_speed_rating' => (int) ($review->delivery_speed_rating ?? 0),
                'new_delivery_speed_rating' => $deliverySpeedRating,
            ],
            (int) $user['id'],
            '/ani-mart/public/reviews/edit',
            'success'
        );

        Session::flash('success', 'Your review was updated.');
        return $this->redirect('/ani-mart/public/customer/orders');
    }

    private function ensureReviewsTable()
    {
        SchemaInspector::requireColumns('reviews', [
            'product_id', 'order_item_id', 'user_id', 'rating', 'comment', 'review_photo',
            'freshness_rating', 'seller_responsiveness_rating', 'product_condition_rating',
            'driver_friendliness_rating', 'delivery_speed_rating', 'created_at', 'updated_at',
        ]);
        SchemaInspector::requireIndexes('reviews', ['idx_reviews_order_item_id', 'idx_reviews_order_item_unique']);
    }

    public function reviews()
    {
        $user = Session::get('user');

        if (!$this->canShop($user)) {
            header('Location: /ani-mart/public/login');
            exit;
        }

        $db = Database::connection();
        $this->ensureReviewsTable();

        $stmt = $db->prepare("SELECT reviews.*, products.name AS product_name, products.image AS product_image, seller_profiles.shop_name, orders.order_number FROM reviews INNER JOIN products ON products.id = reviews.product_id INNER JOIN order_items ON order_items.id = reviews.order_item_id INNER JOIN orders ON orders.id = order_items.order_id LEFT JOIN seller_profiles ON seller_profiles.id = order_items.seller_profile_id WHERE reviews.user_id = ? ORDER BY reviews.created_at DESC, reviews.id DESC");
        $stmt->execute([(int) $user['id']]);

        return $this->view('reviews/index', [
            'reviews' => $stmt->fetchAll(),
            'pageCss' => '/ani-mart/public/assets/css/pages/reviews.css',
        ]);
    }

    public function editReview()
    {
        $user = Session::get('user');
        if (!$this->canShop($user)) {
            header('Location: /ani-mart/public/login');
            exit;
        }

        $db = Database::connection();
        $this->ensureReviewsTable();
        $reviewId = (int) ($_GET['id'] ?? 0);
        $stmt = $db->prepare("SELECT reviews.*, products.name AS product_name, products.image AS product_image, seller_profiles.shop_name FROM reviews INNER JOIN products ON products.id = reviews.product_id INNER JOIN order_items ON order_items.id = reviews.order_item_id INNER JOIN orders ON orders.id = order_items.order_id LEFT JOIN seller_profiles ON seller_profiles.id = order_items.seller_profile_id WHERE reviews.id = ? AND reviews.user_id = ? LIMIT 1");
        $stmt->execute([$reviewId, (int) $user['id']]);
        $review = $stmt->fetch();

        if (!$review) {
            Session::flash('error', 'Review not found.');
            return $this->redirect('/ani-mart/public/reviews');
        }

        return $this->view('reviews/edit', [
            'review' => $review,
            'pageCss' => '/ani-mart/public/assets/css/pages/reviews.css',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Confirm Delivery
    |--------------------------------------------------------------------------
    */

    public function confirmOrder()
    {
        csrf_verify();

        $user = Session::get('user');

        if (!$user) {

            header(
                'Location: /ani-mart/public/login'
            );

            exit;
        }

        $orderId = (int) ($_POST['id'] ?? 0);
        $sellerProfileId = (int) ($_POST['seller_profile_id'] ?? 0);

        $db =
            Database::connection();

        (new OrderWorkflowSchemaService())->ensure();

        $confirmedShop = null;

        try {
            $db->beginTransaction();

            if ($sellerProfileId <= 0) {
                $eligibleSeller = $db->prepare("SELECT oss.seller_profile_id
                    FROM order_seller_statuses oss
                    INNER JOIN orders o ON o.id = oss.order_id
                    WHERE oss.order_id = ?
                        AND o.user_id = ?
                        AND oss.status IN ('out_for_delivery', 'delivered')");
                $eligibleSeller->execute([$orderId, (int) $user['id']]);
                $eligibleSellerIds = array_map('intval', $eligibleSeller->fetchAll(\PDO::FETCH_COLUMN));
                if (count($eligibleSellerIds) === 1) {
                    $sellerProfileId = $eligibleSellerIds[0];
                }
            }

            $shop = $db->prepare("SELECT
                    oss.status,
                    sp.shop_name,
                    sp.user_id AS seller_user_id,
                    o.order_number
                FROM order_seller_statuses oss
                INNER JOIN orders o ON o.id = oss.order_id
                INNER JOIN seller_profiles sp ON sp.id = oss.seller_profile_id
                WHERE oss.order_id = ?
                    AND oss.seller_profile_id = ?
                    AND o.user_id = ?
                    AND oss.status IN ('out_for_delivery', 'delivered')
                LIMIT 1
                FOR UPDATE");
            $shop->execute([$orderId, $sellerProfileId, (int) $user['id']]);
            $confirmedShop = $shop->fetch();

            if (!$confirmedShop) {
                $db->rollBack();
                Session::flash('error', 'This shop delivery is not ready to be confirmed.');
                return $this->redirect('/ani-mart/public/customer/orders?id=' . $orderId);
            }

            $db->prepare("UPDATE order_seller_statuses
                SET status = 'completed'
                WHERE order_id = ? AND seller_profile_id = ?")
                ->execute([$orderId, $sellerProfileId]);

            $aggregateStatus = $this->syncAggregateOrderStatus($db, $orderId);
            $allDelivered = $aggregateStatus === 'completed';
            $db->prepare("UPDATE orders
                SET customer_confirmed = ?,
                    delivered_at = CASE WHEN ? = 1 THEN COALESCE(delivered_at, NOW()) ELSE delivered_at END
                WHERE id = ?")
                ->execute([$allDelivered ? 1 : 0, $allDelivered ? 1 : 0, $orderId]);

            $db->commit();
        } catch (\Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Session::flash('error', 'Unable to confirm this shop delivery. Please try again.');
            return $this->redirect('/ani-mart/public/customer/orders?id=' . $orderId);
        }

        Session::flash(
            'success',
            'Delivery from ' . $confirmedShop->shop_name . ' confirmed. You can now review its products.'
        );

        NotificationController::create(
            $user['id'],
            'review',
            'Tell us how your shop delivery arrived',
            'Please rate the products delivered by ' . $confirmedShop->shop_name . '.',
            'bi-star-fill',
            'warning',
            '/ani-mart/public/customer/orders?id=' . $orderId,
            'order',
            $orderId,
            '/ani-mart/public/customer/orders?id=' . $orderId
        );

        NotificationService::send(
            $confirmedShop->seller_user_id,
            'Delivery Confirmed by Customer',
            "The customer confirmed your shop delivery for order #{$confirmedShop->order_number}.",
            'order',
            'bi-check-circle',
            'success',
            '/ani-mart/public/farmer/orders',
            'order',
            $orderId,
            '/ani-mart/public/farmer/orders'
        );

        header(
            'Location: /ani-mart/public/customer/orders'
        );

        exit;
    }

    private function syncAggregateOrderStatus($db, int $orderId): string
    {
        $stmt = $db->prepare("SELECT status
            FROM order_seller_statuses
            WHERE order_id = ?
            FOR UPDATE");
        $stmt->execute([$orderId]);
        $statuses = array_map(
            static fn ($row): string => strtolower((string) $row->status),
            $stmt->fetchAll()
        );

        $activeStatuses = array_values(array_filter(
            $statuses,
            static fn ($status): bool => $status !== 'cancelled'
        ));

        if ($activeStatuses === []) {
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
            usort($activeStatuses, static fn ($left, $right) => ($rank[$left] ?? 0) <=> ($rank[$right] ?? 0));
            $aggregateStatus = $activeStatuses[0] ?? 'pending';
        }

        $db->prepare('UPDATE orders SET status = ? WHERE id = ?')
            ->execute([$aggregateStatus, $orderId]);

        return $aggregateStatus;
    }

    /*
    |--------------------------------------------------------------------------
    | Receipt
    |--------------------------------------------------------------------------
    */

    public function receipt()
    {
        $user =
            Session::get('user');

        if (!$user) {

            header(
                'Location: /ani-mart/public/login'
            );

            exit;
        }

        $orderId =
            $_GET['id'] ?? 0;

        $db =
            Database::connection();

        $stmt =
            $db->prepare("

                SELECT *

                FROM orders

                WHERE id = ?

                AND user_id = ?

                LIMIT 1

            ");

        $stmt->execute([

            $orderId,

            $user['id']

        ]);

        $order =
            $stmt->fetch();

        if (!$order) {
            http_response_code(404);
            echo 'Order not found.';
            exit;
        }

        $itemStmt =
            $db->prepare("

                SELECT

                    order_items.*,

                    products.name

                FROM order_items

                LEFT JOIN products

                    ON products.id =
                    order_items.product_id

                WHERE order_items.order_id = ?

            ");

        $itemStmt->execute([
            $orderId
        ]);

        $items =
            $itemStmt->fetchAll();

        return $this->view(
            'orders/receipt',
            [

                'order' =>
                $order,

                'items' =>
                $items

            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Profile
    |--------------------------------------------------------------------------
    */

    public function profile()
    {
        $user =
            Session::get('user');

        if (!$user) {

            header(
                'Location: /ani-mart/public/login'
            );

            exit;
        }

        return $this->view(
            'customer/profile/index',
            [

                'user' =>
                $user

            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Update Profile
    |--------------------------------------------------------------------------
    */

    public function updateProfile()
    {
        $user =
            Session::get('user');

        if (!$user) {

            header(
                'Location: /ani-mart/public/login'
            );

            exit;
        }

        $db =
            Database::connection();

        $stmt =
            $db->prepare("

                UPDATE users

                SET

                    first_name = ?,
                    last_name = ?

                WHERE id = ?

            ");

        $stmt->execute([

            $_POST['first_name'],

            $_POST['last_name'],

            $user['id']

        ]);

        $cipher = new SensitiveDataCipher();
        $db->prepare('UPDATE users SET first_name_encrypted = ?, last_name_encrypted = ? WHERE id = ?')
            ->execute([
                $cipher->encrypt((string) $_POST['first_name'], 'users.first_name'),
                $cipher->encrypt((string) $_POST['last_name'], 'users.last_name'),
                $user['id'],
            ]);

        $_SESSION['user']['first_name'] =
            $_POST['first_name'];

        $_SESSION['user']['last_name'] =
            $_POST['last_name'];

        header(
            'Location: /ani-mart/public/customer/profile'
        );

        exit;
    }
}
