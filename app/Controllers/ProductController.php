<?php

namespace App\Controllers;

use App\Services\ImageUploadService;
use App\Services\AuthGuard;
use App\Services\SchemaInspector;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Services\AuditLogService;
use App\Services\CloudinaryService;
use App\Services\NotificationService;
use App\Services\ProductModerationService;
use App\Services\SellerProfileService;
use App\Services\SmartPriceCheckService;
use App\Services\InventoryNotificationService;
use App\Services\SensitiveDataCipher;

class ProductController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | PRODUCT LIST
    |--------------------------------------------------------------------------
    */

    public function index()
{
    $user = $this->requireAuthenticatedUser();

    $role = strtolower($user['role'] ?? '');

    $db = Database::connection();

    /*
    |--------------------------------------------------------------------------
    | ADMIN
    |--------------------------------------------------------------------------
    */

    if ($role === 'admin') {

        $this->requireAdmin();
        (new SmartPriceCheckService())->ensureSchema();

        $stmt = $db->prepare("
            SELECT
                p.*,
                c.name AS category_name,
                sp.shop_name,
                u.first_name,
                u.last_name,
                u.email,
                h.source AS reference_source,
                h.recorded_on AS reference_price_date,
                h.region AS reference_region
            FROM products p
            LEFT JOIN categories c
                ON c.id = p.category_id
            LEFT JOIN seller_profiles sp
                ON sp.id = p.seller_profile_id
            LEFT JOIN users u
                ON u.id = sp.user_id
            LEFT JOIN price_check_history h
                ON h.id = p.last_price_check_id
            WHERE COALESCE(p.approval_status, 'approved') <> 'draft'
            ORDER BY p.created_at DESC, p.id DESC
        ");

        $stmt->execute();

        $products = $stmt->fetchAll();

        return $this->view('admin/products/index', [
            'products' => $products,
            'role' => 'admin',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | FARMER
    |--------------------------------------------------------------------------
    */

    if ($role === 'farmer') {

        $this->ensureProductMediaTables($db);

        $activeShop = $this->requireActiveShop();

        $sellerProfileId = $activeShop->id;

        $stmt = $db->prepare("
            SELECT
                products.*,
                categories.name AS category_name,
                CASE
                    WHEN COALESCE(variant_inventory.variant_count, 0) > 0
                        THEN variant_inventory.available_stock
                    ELSE products.stock
                END AS available_stock
            FROM products
            LEFT JOIN categories
                ON categories.id = products.category_id
            LEFT JOIN (
                SELECT
                    product_id,
                    COUNT(*) AS variant_count,
                    COALESCE(SUM(CASE WHEN is_active = 1 THEN stock ELSE 0 END), 0) AS available_stock
                FROM product_variants
                GROUP BY product_id
            ) AS variant_inventory
                ON variant_inventory.product_id = products.id
            WHERE products.seller_profile_id = ?
                AND products.is_active = 1
            ORDER BY products.created_at DESC
        ");

        $stmt->execute([
            $sellerProfileId
        ]);

        $products = $stmt->fetchAll();
        foreach ($products as $product) {
            $product->stock = max(0, (int) ($product->available_stock ?? $product->stock ?? 0));
        }
        $moderationSignals = (new ProductModerationService())->sellerSignals((int) $sellerProfileId);

        return $this->view('farmer/products/index', [
            'products' => $products,
            'role' => 'farmer',
            'activeShop' => $activeShop,
            'moderationSignals' => $moderationSignals,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | CUSTOMER
    |--------------------------------------------------------------------------
    */

    if ($role === 'customer') {
        return $this->redirect('/ani-mart/public/marketplace');
    }

    /*
    |--------------------------------------------------------------------------
    | UNKNOWN ROLE
    |--------------------------------------------------------------------------
    */

    return $this->redirect('/ani-mart/public/dashboard');
}

    /*
    |--------------------------------------------------------------------------
    | CREATE PAGE
    |--------------------------------------------------------------------------
    */

    public function create()
    {
        $activeShop = $this->requireActiveShop();

        $db =
            Database::getInstance()
            ->connect();

        $categories = $this->productCategories($db);
        $moderationSignals = (new ProductModerationService())->sellerSignals((int) $activeShop->id);
        $smartPriceWarning = $_SESSION['smart_price_warning'] ?? null;
        unset($_SESSION['smart_price_warning']);

        return $this->view(
            'farmer/products/create',
            compact('categories', 'activeShop', 'moderationSignals', 'smartPriceWarning')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | STORE PRODUCT
    |--------------------------------------------------------------------------
    */

    public function store()
    {
        csrf_verify();

        $db =
            Database::getInstance()
            ->connect();

        $this->ensureProductMediaTables($db);

        $activeShop = $this->requireActiveShop();
        $user = Session::get('user');
        $sellerProfileId = $activeShop->id;

        $variants = $this->normalizeVariants($_POST);

        if (empty($variants)) {
            $_SESSION['error'] =
                'Please add at least one weight variant.';

            header('Location: /ani-mart/public/farmer/products/create');
            exit;
        }

        $priceType = $_POST['price_type'] ?? 'retail';
        $status = ($_POST['is_active'] ?? '1') === '0' ? 0 : 1;

        $productPrice = $this->variantDisplayPrice($variants);
        $productStock = array_sum(array_column($variants, 'stock'));

        $categoryId = (int) ($_POST['category_id'] ?? 0);
        $categoryName = $this->categoryNameById($db, $categoryId);
        $sellerLocation = $this->sellerLocationForProfile($db, $sellerProfileId);
        $warningAcknowledged = !empty($_POST['continue_anyway']) || !empty($_POST['price_warning_acknowledged']);
        $smartCheck = $this->runSmartPriceCheck(
            trim((string) ($_POST['name'] ?? '')),
            $categoryName,
            $productPrice,
            $sellerLocation['municipality'] ?? '',
            $sellerLocation['region'] ?? '',
            $sellerLocation['unit'] ?? 'kg',
            $productStock,
            $sellerProfileId
        );

        if (!empty($smartCheck['requires_warning']) && !$warningAcknowledged) {
            $_SESSION['error'] = 'Price Review Recommended. Please review the submitted price and choose Continue Anyway only after checking the warning.';
            $_SESSION['smart_price_warning'] = $smartCheck;
            header('Location: /ani-mart/public/farmer/products/create');
            exit;
        }

        // Upload only after the seller has reviewed any price recommendation.
        // This avoids creating unused cloud uploads when publication is paused.
        $harvestPhoto = $this->uploadHarvestPhoto();
        $galleryFiles = $this->uploadProductImages('product_images');
        $galleryOrder = $_POST['gallery_order'] ?? [];
        $coverImage = $_POST['gallery_cover'] ?? null;

        $moderationSignals = (new ProductModerationService())->sellerSignals((int) $sellerProfileId);
        $moderationDecision = ProductModerationService::publicationDecision($moderationSignals, $smartCheck);
        $approvalStatus = $moderationDecision['status'];

        $columns = [
            'seller_profile_id',
            'category_id',
            'name',
            'slug',
            'description',
            'image',
            'image_public_id',
            'price',
            'stock',
            'harvest_date',
            'harvest_photo',
            'is_active',
            'approval_status',
            'last_price_assessment',
            'last_reference_price',
            'last_price_deviation',
            'price_warning_shown',
            'price_warning_acknowledged',
            'price_warning_acknowledged_at',
            'price_justification',
            'moderation_reason'
        ];

        $values = [
            $sellerProfileId,
            $_POST['category_id'],
            $_POST['name'],
            strtolower(str_replace(' ', '-', $_POST['name'])),
            $_POST['description'],
            null,
            null,
            $productPrice,
            $productStock,
            $this->nullableDate($_POST['harvest_date'] ?? null),
            $harvestPhoto,
            $status,
            $approvalStatus,
            $smartCheck['assessment'] ?? null,
            $smartCheck['reference_price'] ?? null,
            $smartCheck['price_deviation_percentage'] ?? null,
            !empty($smartCheck['requires_warning']) ? 1 : 0,
            $warningAcknowledged ? 1 : 0,
            $warningAcknowledged ? date('Y-m-d H:i:s') : null,
            trim((string) ($_POST['price_justification'] ?? '')) ?: null,
            $moderationDecision['reason']
        ];

        if ($this->tableHasColumn($db, 'products', 'price_type')) {
            $columns[] = 'price_type';
            $values[] = $priceType;
        }

        $stmt = $db->prepare(
            'INSERT INTO products (' . implode(', ', $columns) . ') VALUES ('
            . implode(', ', array_fill(0, count($columns), '?')) . ')'
        );
        $stmt->execute($values);

        $productId = $db->lastInsertId();

        $galleryResult = $this->saveProductGallery(
            $db,
            $productId,
            $galleryOrder,
            $galleryFiles,
            $coverImage
        );

        if ($galleryResult['cover_url']) {
            $updateImage = $db->prepare("UPDATE products SET image = ?, image_public_id = ? WHERE id = ?");
            $updateImage->execute([
                $galleryResult['cover_url'],
                $galleryResult['cover_public_id'],
                $productId
            ]);
        }

        $followers = $approvalStatus === 'approved'
            ? \App\Models\FavoriteFarmer::followerCustomerIds($sellerProfileId)
            : [];

        if (!empty($followers)) {
            $stmt = $db->prepare("SELECT shop_name FROM seller_profiles WHERE id = ? LIMIT 1");
            $stmt->execute([$sellerProfileId]);
            $shopName = $stmt->fetch()->shop_name ?? 'your farm';

            $sellerStmt = $db->prepare("SELECT slug FROM seller_profiles WHERE id = ? LIMIT 1");
            $sellerStmt->execute([$sellerProfileId]);
            $sellerSlug = $sellerStmt->fetch()->slug ?? null;
            $productUrl = $sellerSlug ? '/ani-mart/public/farm/' . urlencode($sellerSlug) : '/ani-mart/public/marketplace';

            foreach ($followers as $customerId) {
                \App\Services\NotificationService::harvest(
                    $customerId,
                    'New product from ' . $shopName,
                    'A followed farm has listed a new product: ' . ($_POST['name'] ?? 'New harvest') . '.',
                    $productUrl
                );
            }
        }

        $admins = [];
        if ($approvalStatus !== 'approved') {
            $adminStmt = $db->query("SELECT id FROM users WHERE role = 'admin'");
            $admins = $adminStmt->fetchAll();
        }

        foreach ($admins as $admin) {
            \App\Services\NotificationService::send(
                $admin->id,
                'New Product Pending Approval',
                'A seller submitted ' . ($_POST['name'] ?? 'a new product') . '. Smart Price Check and seller-history details are ready for review.',
                'product',
                'bi-basket',
                'primary',
                '/ani-mart/public/admin/products/view?id=' . (int) $productId,
                'product',
                $productId,
                '/ani-mart/public/admin/products/view?id=' . (int) $productId
            );
        }

        $productUrl = '/ani-mart/public/farmer/products/edit?id=' . (int) $productId;
        if ($approvalStatus === 'approved') {
            NotificationService::send(
                (int) $user['id'],
                'Product Approved',
                'Your product "' . ($_POST['name'] ?? 'Product') . '" passed Smart Price Check and is now visible in the marketplace.',
                'product',
                'bi-check-circle',
                'success',
                $productUrl,
                'product',
                $productId,
                $productUrl
            );
        } else {
            $reason = $moderationDecision['reason'];
            NotificationService::send(
                (int) $user['id'],
                'Product Pending Approval',
                $reason . ' It will remain hidden from the marketplace until approved.',
                'product',
                'bi-hourglass-split',
                'warning',
                $productUrl,
                'product',
                $productId,
                $productUrl
            );
        }

        if (!empty($smartCheck['notify_seller'])) {
            NotificationService::send(
                (int) $user['id'],
                'Price Review Recommended',
                'The price of ' . ($_POST['name'] ?? 'your product') . ' differs significantly from the available reference price. Please review your listing before publishing.',
                'product',
                'bi-exclamation-triangle',
                'warning',
                '/ani-mart/public/farmer/products/edit?id=' . (int) $productId
            );
        }

        $this->saveProductVariants($db, $productId, $variants);
        (new InventoryNotificationService())->evaluateProduct((int) $productId);

        if (!empty($smartCheck['history_id'])) {
            (new SmartPriceCheckService())->attachProduct((int) $smartCheck['history_id'], (int) $productId, 'submitted');
        }

        AuditLogService::log(
            'PRODUCT_CREATED',
            'product',
            (int) $productId,
            [
                'name' => trim((string) ($_POST['name'] ?? '')),
                'price' => $productPrice,
                'stock' => $productStock,
                'category_id' => $_POST['category_id'] ?? null,
                'description' => mb_substr(trim((string) ($_POST['description'] ?? '')), 0, 255),
                'moderation_status' => $approvalStatus,
                'seller_established' => !empty($moderationSignals['established'])
            ],
            (int) $user['id'],
            '/ani-mart/public/farmer/products/store',
            'success'
        );

        $_SESSION['success'] = $approvalStatus === 'approved'
            ? 'Product published successfully.'
            : 'Product submitted for administrator review.';

        header(
            'Location: /ani-mart/public/farmer/products'
        );

        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | EDIT PAGE
    |--------------------------------------------------------------------------
    */

    public function edit()
    {
        $user =
            $this->requireFarmerUser();

        $db =
            Database::getInstance()
            ->connect();

        $id =
            $_GET['id'] ?? 0;

        /*
        |--------------------------------------------------------------------------
        | Product
        |--------------------------------------------------------------------------
        */

        $stmt = $db->prepare("

            SELECT products.*, seller_profiles.shop_name, seller_profiles.shop_theme

            FROM products

            INNER JOIN seller_profiles
                ON seller_profiles.id =
                products.seller_profile_id

            WHERE products.id = ?
                AND seller_profiles.user_id = ?

        ");

        $stmt->execute([
            $id,
            $user['id']
        ]);

        $product =
            $stmt->fetch();

        if (!$product) {

            $_SESSION['error'] =
                'Product not found.';

            header('Location: /ani-mart/public/farmer/products');
            exit;
        }

        $this->ensureProductMediaTables($db);

        $imagesStmt = $db->prepare("

            SELECT *

            FROM product_images

            WHERE product_id = ?

            ORDER BY display_order ASC, id ASC

        ");

        $imagesStmt->execute([
            $id
        ]);

        $productImages =
            $imagesStmt->fetchAll();

        $variantsStmt =
            $db->prepare(
                'SELECT * FROM product_variants WHERE product_id = ? ORDER BY id ASC'
            );
        $variantsStmt->execute([$id]);
        $productVariants =
            $variantsStmt->fetchAll();

        /*
        |--------------------------------------------------------------------------
        | Categories
        |--------------------------------------------------------------------------
        */

        $categories = $this->productCategories($db);

        $smartPriceWarning = $_SESSION['smart_price_warning'] ?? null;
        unset($_SESSION['smart_price_warning']);

        return $this->view(
            'farmer/products/edit',
            compact(
                'product',
                'categories',
                'productImages',
                'productVariants',
                'smartPriceWarning'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE PRODUCT
    |--------------------------------------------------------------------------
    */

    public function update()
    {
        csrf_verify();

        $user =
            $this->requireFarmerUser();

        $db =
            Database::getInstance()
            ->connect();

        $id =
            $_POST['id'] ?? 0;

        /*
    |--------------------------------------------------------------------------
    | Current Product
    |--------------------------------------------------------------------------
    */

        $stmt = $db->prepare("

        SELECT products.*

        FROM products

        INNER JOIN seller_profiles
            ON seller_profiles.id =
            products.seller_profile_id

        WHERE products.id = ?
            AND seller_profiles.user_id = ?

    ");

        $stmt->execute([
            $id,
            $user['id']
        ]);

        $product =
            $stmt->fetch();

        if (!$product) {

            $_SESSION['error'] =
                'Product not found.';

            header(
                'Location: /ani-mart/public/farmer/products'
            );

            exit;
        }

        $this->ensureProductMediaTables($db);

        $variants = $this->normalizeVariants($_POST);

        if (empty($variants)) {
            $_SESSION['error'] =
                'Please add at least one weight variant.';

            header(
                'Location: /ani-mart/public/farmer/products/edit?id=' . $id
            );

            exit;
        }

        $priceType = $_POST['price_type'] ?? 'retail';
        $galleryFiles = $this->uploadProductImages('product_images');
        $galleryOrder = $_POST['gallery_order'] ?? [];
        $coverImage = $_POST['gallery_cover'] ?? null;
        $productPrice = $this->variantDisplayPrice($variants);
        $productStock = array_sum(array_column($variants, 'stock'));
        $categoryId = (int) ($_POST['category_id'] ?? 0);
        $approvalInputsChanged =
            trim((string) ($_POST['name'] ?? '')) !== trim((string) ($product->name ?? ''))
            || $categoryId !== (int) ($product->category_id ?? 0)
            || abs($productPrice - (float) ($product->price ?? 0)) > 0.009
            || trim((string) ($_POST['description'] ?? '')) !== trim((string) ($product->description ?? ''))
            || trim((string) ($_POST['price_justification'] ?? '')) !== trim((string) ($product->price_justification ?? ''))
            || !empty($galleryFiles);
        $categoryName = $this->categoryNameById($db, $categoryId);
        $sellerLocation = $this->sellerLocationForProfile($db, $product->seller_profile_id);
        $warningAcknowledged = !empty($_POST['continue_anyway']) || !empty($_POST['price_warning_acknowledged']);
        $smartCheck = $this->runSmartPriceCheck(
            trim((string) ($_POST['name'] ?? '')),
            $categoryName,
            $productPrice,
            $sellerLocation['municipality'] ?? '',
            $sellerLocation['region'] ?? '',
            $sellerLocation['unit'] ?? 'kg',
            $productStock,
            $product->seller_profile_id
        );

        if ($approvalInputsChanged && !empty($smartCheck['requires_warning']) && !$warningAcknowledged) {
            $_SESSION['error'] = 'Price Review Recommended. Please review the submitted price and choose Continue Anyway only after checking the warning.';
            $_SESSION['smart_price_warning'] = $smartCheck;
            header('Location: /ani-mart/public/farmer/products/edit?id=' . $id);
            exit;
        }

        /*
    |--------------------------------------------------------------------------
    | Store Old Harvest Date
    |--------------------------------------------------------------------------
    */

        $oldHarvestDate =
            $product->harvest_date ?? null;

        /*
    |--------------------------------------------------------------------------
    | Product Image
    |--------------------------------------------------------------------------
    */

        $image =
            $product->image;

        $imagePublicId =
            $product->image_public_id ?? null;

        $newImage =
            $this->uploadProductImage();

        if ($newImage) {

            if (
                !empty($imagePublicId)
                && ! $this->deleteCloudinaryImage(
                    $imagePublicId
                )
            ) {
                $this->deleteCloudinaryImage(
                    $newImage['public_id']
                );

                $_SESSION['error'] =
                    'Unable to replace the current product image. Please try again.';

                header(
                    'Location: /ani-mart/public/farmer/products/edit?id=' . $id
                );

                exit;
            }

            $image =
                $newImage['url'];

            $imagePublicId =
                $newImage['public_id'];
        }

        /*
    |--------------------------------------------------------------------------
    | Harvest Photo
    |--------------------------------------------------------------------------
    */

        $harvestPhoto =
            $product->harvest_photo ?? null;

        $newHarvestPhoto =
            $this->uploadHarvestPhoto();

        if ($newHarvestPhoto) {

            $harvestPhoto =
                $newHarvestPhoto;
        }

        /*
    |--------------------------------------------------------------------------
    | Update Product
    |--------------------------------------------------------------------------
    */

        $previousApprovalStatus = (string) ($product->approval_status ?? 'pending');
        $materialUpdate = $approvalInputsChanged || !empty($newImage) || !empty($newHarvestPhoto);
        $moderationSignals = (new ProductModerationService())->sellerSignals((int) $product->seller_profile_id);
        $moderationDecision = $materialUpdate
            ? ProductModerationService::publicationDecision(
                $moderationSignals,
                $smartCheck,
                true,
                $previousApprovalStatus
            )
            : ['status' => $previousApprovalStatus, 'reason' => (string) ($product->moderation_reason ?? '')];
        $approvalStatus = $moderationDecision['status'];

        $updateSql = "

        UPDATE products

        SET

            category_id = ?,
            name = ?,
            slug = ?,
            description = ?,
            image = ?,
            image_public_id = ?,
            price = ?,
            stock = ?,
            harvest_date = ?,
            harvest_photo = ?,
            approval_status = ?,
            last_price_assessment = ?,
            last_reference_price = ?,
            last_price_deviation = ?,
            price_warning_shown = ?,
            price_warning_acknowledged = ?,
            price_warning_acknowledged_at = ?,
            price_justification = ?,
            moderation_reason = ?";

        if ($this->tableHasColumn($db, 'products', 'price_type')) {
            $updateSql .= ",\n            price_type = ?";
        }

        $updateSql .= "

        WHERE id = ?
            AND seller_profile_id = ?

    ";

        $update = $db->prepare($updateSql);

        $params = [
            $_POST['category_id'],
            $_POST['name'],
            strtolower(
                str_replace(
                    ' ',
                    '-',
                    $_POST['name']
                )
            ),
            $_POST['description'],
            $image,
            $imagePublicId,
            $productPrice,
            $productStock,
            $this->nullableDate(
                $_POST['harvest_date'] ?? null
            ),
            $harvestPhoto,
            $approvalStatus,
            $smartCheck['assessment'] ?? null,
            $smartCheck['reference_price'] ?? null,
            $smartCheck['price_deviation_percentage'] ?? null,
            !empty($smartCheck['requires_warning']) ? 1 : 0,
            $warningAcknowledged ? 1 : 0,
            $warningAcknowledged ? date('Y-m-d H:i:s') : null,
            trim((string) ($_POST['price_justification'] ?? '')) ?: null,
            $moderationDecision['reason'] ?: null
        ];

        if ($this->tableHasColumn($db, 'products', 'price_type')) {
            $params[] = $priceType;
        }

        $params[] = $id;
        $params[] = $product->seller_profile_id;

        $update->execute($params);

        $productUrl = '/ani-mart/public/farmer/products/edit?id=' . (int) $id;
        if ($approvalStatus !== $previousApprovalStatus && $approvalStatus === 'approved') {
            NotificationService::send(
                (int) $user['id'],
                'Product Approved',
                'Your updated product passed Smart Price Check and is now visible in the marketplace.',
                'product',
                'bi-check-circle',
                'success',
                $productUrl,
                'product',
                (int) $id,
                $productUrl
            );
        } elseif ($approvalStatus !== $previousApprovalStatus) {
            $reason = $moderationDecision['reason'] ?: 'The updated product is pending administrator review.';
            NotificationService::send(
                (int) $user['id'],
                'Product Pending Approval',
                $reason . ' It will remain hidden from the marketplace until approved.',
                'product',
                'bi-hourglass-split',
                'warning',
                $productUrl,
                'product',
                (int) $id,
                $productUrl
            );
        }

        AuditLogService::log(
            'PRODUCT_UPDATED',
            'product',
            (int) $id,
            [
                'old_price' => (float) $product->price,
                'new_price' => (float) $productPrice,
                'old_stock' => (int) $product->stock,
                'new_stock' => (int) $productStock,
                'name' => trim((string) ($_POST['name'] ?? ''))
            ],
            (int) $user['id'],
            '/ani-mart/public/farmer/products/update',
            'success'
        );

        $db->prepare("DELETE FROM product_variants WHERE product_id = ?")
            ->execute([$id]);

        $this->saveProductVariants($db, $id, $variants);
        (new InventoryNotificationService())->evaluateProduct((int) $id);

        if (!empty($smartCheck['history_id'])) {
            (new SmartPriceCheckService())->attachProduct((int) $smartCheck['history_id'], (int) $id, 'updated');
        }

        if (!empty($galleryFiles)) {
            $galleryResult = $this->saveProductGallery(
                $db,
                $id,
                $galleryOrder,
                $galleryFiles,
                $coverImage
            );

            if ($galleryResult['cover_url']) {
                $updateImage = $db->prepare("UPDATE products SET image = ?, image_public_id = ? WHERE id = ?");
                $updateImage->execute([
                    $galleryResult['cover_url'],
                    $galleryResult['cover_public_id'],
                    $id
                ]);
            }
        }

        /*
    |--------------------------------------------------------------------------
    | Transparency Log
    |--------------------------------------------------------------------------
    */

        $newHarvestDate =
            $this->nullableDate(
                $_POST['harvest_date'] ?? null
            );

        if (
            $oldHarvestDate !=
            $newHarvestDate
        ) {

            $log =
                $db->prepare("

                INSERT INTO
                product_activity_logs (

                    product_id,
                    user_id,
                    action,
                    old_value,
                    new_value

                )

                VALUES (

                    ?, ?, ?,
                    ?, ?

                )

            ");

            $log->execute([

                $id,

                $user['id'],

                'Harvest Date Changed',

                $oldHarvestDate,

                $newHarvestDate

            ]);

            /*
        |--------------------------------------------------------------------------
        | Reduce Trust Score
        |--------------------------------------------------------------------------
        */

            $db->prepare("

            UPDATE seller_profiles

            SET trust_score =
                GREATEST(
                    trust_score - 3,
                    0
                )

            WHERE id = ?

        ")

                ->execute([
                    $product->seller_profile_id
                ]);
        }

        $_SESSION['success'] =
            'Product updated successfully.';

        header(
            'Location: /ani-mart/public/farmer/products'
        );

        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE PRODUCT
    |--------------------------------------------------------------------------
    */

    public function delete()
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            Session::flash('error', 'Please use the delete button from the products page.');
            return $this->redirect('/ani-mart/public/farmer/products');
        }

        csrf_verify();

        $user =
            $this->requireFarmerUser();

        $activeShop = SellerProfileService::activeShop($user['id']);

        $db =
            Database::getInstance()
            ->connect();

        $id =
            $_POST['id'] ?? 0;

        $stmt = $db->prepare("

            UPDATE products

            INNER JOIN seller_profiles
                ON seller_profiles.id =
                products.seller_profile_id

            SET products.is_active = 0

            WHERE products.id = ?
                AND seller_profiles.user_id = ?
                AND seller_profiles.id = ?
                AND products.is_active = 1

        ");

        $stmt->execute([
            $id,
            $user['id'],
            $activeShop->id ?? 0
        ]);

        if ($stmt->rowCount() > 0) {

            AuditLogService::log(
                'PRODUCT_DELETED',
                'product',
                (int) $id,
                [
                    'name' => trim((string) ($_POST['name'] ?? '')),
                    'status' => 'inactive'
                ],
                (int) $user['id'],
                '/ani-mart/public/farmer/products/delete',
                'success'
            );

            $_SESSION['success'] =
                'Product deleted successfully.';
        } else {

            $_SESSION['error'] =
                'Product not found.';
        }

        header(
            'Location: /ani-mart/public/farmer/products'
        );

        exit;
    }

    /*
|--------------------------------------------------------------------------
| UPDATE ORDER STATUS
|--------------------------------------------------------------------------
*/

    public function updateOrderStatus()
    {
        $user = $this->requireAuthenticatedUser();

        $db =
            Database::getInstance()
            ->connect();

        $id =
            $_GET['id'];

        $status =
            $_GET['status'];

        if (strtolower($user['role'] ?? '') !== 'farmer') {
            http_response_code(403);
            echo '403 Unauthorized';
            exit;
        }

        if (!$this->orderBelongsToFarmer($db, $id, $user['id'])) {
            http_response_code(403);
            echo '403 Unauthorized';
            exit;
        }

        $stmt = $db->prepare("

        UPDATE orders

        SET status = ?

        WHERE id = ?

    ");

        $stmt->execute([
            $status,
            $id
        ]);

        $_SESSION['success'] =
            'Order status updated successfully.';

        header(
            'Location: /ani-mart/public/farmer/orders'
        );

        exit;
    }

    private function requireAuthenticatedUser()
    {
        return AuthGuard::user();
    }

    private function requireFarmerUser()
    {
        return AuthGuard::farmer();
    }

    private function orderBelongsToFarmer($db, $orderId, $userId)
    {
        $stmt = $db->prepare(
            "SELECT o.id
            FROM orders o
            INNER JOIN order_items oi ON oi.order_id = o.id
            INNER JOIN seller_profiles sp ON sp.id = oi.seller_profile_id
            WHERE o.id = ? AND sp.user_id = ?
            LIMIT 1"
        );
        $stmt->execute([$orderId, $userId]);

        return (bool) $stmt->fetch();
    }

    private function sellerProfileIdForUser($db, $userId)
    {
        $activeShopId = Session::get('active_shop_id');

        if ($activeShopId) {
            $stmt = $db->prepare("
                SELECT id
                FROM seller_profiles
                WHERE user_id = ?
                    AND id = ?
                LIMIT 1
            ");
            $stmt->execute([
                $userId,
                $activeShopId
            ]);

            if ($stmt->fetch()) {
                return (int) $activeShopId;
            }

            Session::remove('active_shop_id');
        }

        $profileStmt = $db->prepare("
            SELECT id
            FROM seller_profiles
            WHERE user_id = ?
            ORDER BY created_at DESC
            LIMIT 1
        ");

        $profileStmt->execute([
            $userId
        ]);

        $profile =
            $profileStmt->fetch();

        if ($profile) {
            Session::set('active_shop_id', (int) $profile->id);
            return $profile->id;
        }

        return 0;
    }

    private function uploadProductImage()
    {
        return (new ImageUploadService())->publicField('image', 'products');
    }

    private function uploadHarvestPhoto()
    {
        $upload = (new ImageUploadService())->publicField('harvest_photo', 'harvest-photos');

        if (!is_array($upload)) {
            return null;
        }

        return $upload['secure_url'] ?? $upload['url'] ?? null;
    }

    private function uploadProductImages($fieldName)
    {
        $images = [];
        foreach ((new ImageUploadService())->publicFiles($fieldName, 'products') as $upload) {
            if (is_array($upload)) {
                $images[] = [
                    'url' => $upload['secure_url'] ?? $upload['url'],
                    'public_id' => $upload['public_id']
                ];
            }
        }

        return $images;
    }

    private function saveProductGallery($db, $productId, $galleryOrder, $uploadedImages, $coverImage)
    {
        $coverUrl = null;
        $coverPublicId = null;

        foreach ($uploadedImages as $index => $image) {
            $displayOrder = isset($galleryOrder[$index]) ? (int) $galleryOrder[$index] : $index;
            $isCover = ($coverImage === (string) $index) ? 1 : 0;

            if ($isCover) {
                $coverUrl = $image['url'];
                $coverPublicId = $image['public_id'];
            }

            $insert = $db->prepare("INSERT INTO product_images (
                product_id,
                image_url,
                image_public_id,
                display_order,
                is_cover
            ) VALUES (?, ?, ?, ?, ?)");

            $insert->execute([
                $productId,
                $image['url'],
                $image['public_id'],
                $displayOrder,
                $isCover
            ]);
        }

        if (!$coverUrl && !empty($uploadedImages)) {
            $coverUrl = $uploadedImages[0]['url'];
            $coverPublicId = $uploadedImages[0]['public_id'];
        }

        return [
            'cover_url' => $coverUrl,
            'cover_public_id' => $coverPublicId
        ];
    }

    private function tableHasColumn($db, $table, $column)
    {
        $stmt = $db->prepare(
            'SELECT 1 FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
             AND TABLE_NAME = ?
             AND COLUMN_NAME = ?
             LIMIT 1'
        );
        $stmt->execute([$table, $column]);

        return (bool) $stmt->fetch();
    }

    private function ensureProductMediaTables($db)
    {
        SchemaInspector::requireColumns('product_variants', [
            'product_id', 'variant_name', 'weight', 'unit', 'retail_price', 'wholesale_price',
            'minimum_wholesale_quantity', 'stock', 'is_active',
        ]);
        SchemaInspector::requireColumns('product_images', [
            'product_id', 'image_url', 'image_public_id', 'display_order', 'is_cover',
        ]);
    }

    private function tableExists($db, $table)
    {
        $stmt = $db->prepare(
            'SELECT 1 FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE()
             AND TABLE_NAME = ?
             LIMIT 1'
        );
        $stmt->execute([$table]);

        return (bool) $stmt->fetch();
    }

    private function productCategories($db)
    {
        (new \App\Services\CategoryAdminService())->ensureSchema();
        $names = [
            'Vegetables',
            'Fruits',
            'Grains',
            'Livestock and Poultry',
            'Dairy and Eggs',
        ];

        $categories = $db->query(
            'SELECT * FROM categories WHERE is_active = 1 ORDER BY name ASC'
        )->fetchAll();

        if (count($categories) >= count($names)) {
            return $categories;
        }

        // Keep product forms usable when a local database retains the table
        // but its standard category rows have not been imported. Older local
        // schemas require a unique slug while newer ones do not include it.
        $hasSlug = SchemaInspector::hasColumn('categories', 'slug');

        $seed = $hasSlug
            ? $db->prepare(
                'INSERT IGNORE INTO categories (name, slug) VALUES (?, ?)'
            )
            : $db->prepare(
                'INSERT IGNORE INTO categories (name) VALUES (?)'
            );

        $repairSlug = $hasSlug
            ? $db->prepare(
                "UPDATE categories SET slug = ? WHERE name = ? AND slug = ''"
            )
            : null;

        foreach ($names as $name) {
            $slug = strtolower(
                preg_replace('/[^a-z0-9]+/i', '-', $name)
            );
            $slug = trim($slug, '-');

            if ($repairSlug) {
                $repairSlug->execute([$slug, $name]);
            }

            $hasSlug
                ? $seed->execute([$name, $slug])
                : $seed->execute([$name]);
        }

        return $db->query(
            'SELECT * FROM categories WHERE is_active = 1 ORDER BY name ASC'
        )->fetchAll();
    }

    private function normalizeVariants($post)
    {
        $variants = [];

        $names = $post['variant_name'] ?? [];
        $weights = $post['variant_weight'] ?? [];
        $units = $post['variant_unit'] ?? [];
        $retails = $post['retail_price'] ?? [];
        $wholesales = $post['wholesale_price'] ?? [];
        $minimums = $post['minimum_wholesale_quantity'] ?? [];
        $stocks = $post['stock'] ?? [];
        $statuses = $post['variant_active'] ?? [];

        foreach ($names as $index => $name) {
            $name = trim((string) $name);
            $weight = trim((string) ($weights[$index] ?? ''));
            $unit = trim((string) ($units[$index] ?? ''));
            $retail = floatval($retails[$index] ?? 0);
            $wholesale = floatval($wholesales[$index] ?? 0);
            $minimum = intval($minimums[$index] ?? 0);
            $stock = intval($stocks[$index] ?? 0);
            $isActive = isset($statuses[$index]) && $statuses[$index] === '1' ? 1 : 0;

            if ($name === '') {
                continue;
            }

            $variants[] = [
                'name' => $name,
                'weight' => $weight,
                'unit' => $unit,
                'retail_price' => $retail > 0 ? $retail : null,
                'wholesale_price' => $wholesale > 0 ? $wholesale : null,
                'minimum_wholesale_quantity' => $minimum > 0 ? $minimum : null,
                'stock' => max(0, $stock),
                'is_active' => $isActive,
                'price_type' => $post['price_type'] ?? 'retail'
            ];
        }

        return $variants;
    }

    private function variantDisplayPrice(array $variants)
    {
        foreach ($variants as $variant) {
            if (!empty($variant['retail_price'])) {
                return $variant['retail_price'];
            }

            if (!empty($variant['wholesale_price'])) {
                return $variant['wholesale_price'];
            }
        }

        return 0;
    }

    private function saveProductVariants($db, $productId, array $variants)
    {
        $insert = $db->prepare("INSERT INTO product_variants (
            product_id,
            variant_name,
            weight,
            unit,
            retail_price,
            wholesale_price,
            minimum_wholesale_quantity,
            stock,
            is_active
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

        foreach ($variants as $variant) {
            $insert->execute([
                $productId,
                $variant['name'],
                $variant['weight'],
                $variant['unit'],
                $variant['retail_price'],
                $variant['wholesale_price'],
                $variant['minimum_wholesale_quantity'],
                $variant['stock'],
                $variant['is_active']
            ]);
        }
    }

    public function smartPriceCheck()
    {
        header('Content-Type: application/json');

        if (empty($_SESSION['user'])) {
            echo json_encode(['success' => false, 'message' => 'Authentication required.']);
            exit;
        }

        $db = Database::connection();
        $user = $_SESSION['user'];
        $sellerProfileId = $this->sellerProfileIdForUser($db, (int) ($user['id'] ?? 0));
        $productName = trim((string) ($_POST['name'] ?? $_GET['name'] ?? ''));
        $categoryName = trim((string) ($_POST['category'] ?? $_GET['category'] ?? ''));
        if ($categoryName === '' && !empty($_POST['category_id'])) {
            $categoryName = $this->categoryNameById($db, (int) $_POST['category_id']);
        }

        $price = (float) ($_POST['seller_price'] ?? $_POST['price'] ?? $_GET['price'] ?? 0);
        $municipality = trim((string) ($_POST['municipality'] ?? $_POST['city'] ?? $_GET['municipality'] ?? ''));
        $region = trim((string) ($_POST['region'] ?? $_GET['region'] ?? ''));
        $unit = trim((string) ($_POST['unit'] ?? $_GET['unit'] ?? 'kg'));

        if ($sellerProfileId && ($municipality === '' || $region === '')) {
            $location = $this->sellerLocationForProfile($db, $sellerProfileId);
            $municipality = $municipality ?: ($location['municipality'] ?? '');
            $region = $region ?: ($location['region'] ?? '');
        }

        $check = $this->runSmartPriceCheck($productName, $categoryName, $price, $municipality, $region, $unit, 1, $sellerProfileId);

        echo json_encode([
            'success' => true,
            'check' => $check,
        ]);
        exit;
    }

    public function adminIndex()
    {
        $this->requireAdmin();
        $db = Database::connection();
        (new SmartPriceCheckService())->ensureSchema();

        $stmt = $db->prepare("
                 SELECT p.*, c.name AS category_name, sp.shop_name, u.first_name, u.last_name, u.email,
                     h.source AS reference_source, h.recorded_on AS reference_price_date, h.region AS reference_region
            FROM products p
            LEFT JOIN categories c ON c.id = p.category_id
            LEFT JOIN seller_profiles sp ON sp.id = p.seller_profile_id
            LEFT JOIN users u ON u.id = sp.user_id
                 LEFT JOIN price_check_history h ON h.id = p.last_price_check_id
            WHERE COALESCE(p.approval_status, 'approved') <> 'draft'
            ORDER BY p.created_at DESC, p.id DESC
        ");
        $stmt->execute();

        return $this->view('admin/products/index', [
            'products' => $stmt->fetchAll(),
        ]);
    }

    public function adminView()
    {
        $this->requireAdmin();
        $db = Database::connection();
        (new SmartPriceCheckService())->ensureSchema();
        $productId = (int) ($_GET['id'] ?? 0);

        $stmt = $db->prepare("
                 SELECT p.*, c.name AS category_name, sp.shop_name, sp.city AS municipality, sp.barangay, u.first_name, u.last_name, u.email,
                     h.source AS reference_source, h.recorded_on AS reference_price_date, h.region AS reference_region
            FROM products p
            LEFT JOIN categories c ON c.id = p.category_id
            LEFT JOIN seller_profiles sp ON sp.id = p.seller_profile_id
            LEFT JOIN users u ON u.id = sp.user_id
                 LEFT JOIN price_check_history h ON h.id = p.last_price_check_id
            WHERE p.id = ?
            LIMIT 1
        ");
        $stmt->execute([$productId]);
        $product = $stmt->fetch();

        if (!$product) {
            Session::flash('error', 'Product not found.');
            return $this->redirect('/ani-mart/public/admin/products');
        }

        $productImages = [];
        if ($this->tableExists($db, 'product_images')) {
            $images = $db->prepare('SELECT * FROM product_images WHERE product_id = ? ORDER BY is_cover DESC, display_order ASC, id ASC');
            $images->execute([$productId]);
            $productImages = $images->fetchAll();
        }

        $moderationSignals = (new ProductModerationService())->sellerSignals((int) $product->seller_profile_id);

        return $this->view('admin/products/show', [
            'product' => $product,
            'productImages' => $productImages,
            'moderationSignals' => $moderationSignals,
        ]);
    }

    public function adminApprove()
    {
        $this->requireAdmin();
        csrf_verify();
        $this->handleAdminProductDecision('approved');
    }

    public function adminRequestCorrection()
    {
        $this->requireAdmin();
        csrf_verify();
        $this->handleAdminProductDecision('needs_correction');
    }

    public function adminReject()
    {
        $this->requireAdmin();
        csrf_verify();
        $this->handleAdminProductDecision('rejected');
    }

    private function requireAdmin()
    {
        return AuthGuard::admin();
    }

    private function requireAdminUser()
    {
        return AuthGuard::admin();
    }

    private function categoryNameById($db, int $categoryId): string
    {
        if ($categoryId <= 0) {
            return '';
        }

        $stmt = $db->prepare('SELECT name FROM categories WHERE id = ? LIMIT 1');
        $stmt->execute([$categoryId]);
        $row = $stmt->fetch();

        return trim((string) ($row->name ?? ''));
    }

    private function sellerLocationForProfile($db, int $sellerProfileId): array
    {
        $stmt = $db->prepare('SELECT city, barangay FROM seller_profiles WHERE id = ? LIMIT 1');
        $stmt->execute([$sellerProfileId]);
        $row = $stmt->fetch();

        if (!$row) {
            return ['municipality' => '', 'region' => ''];
        }

        return [
            'municipality' => trim((string) ($row->city ?? '')),
            'region' => '',
        ];
    }

    private function runSmartPriceCheck(
        string $productName,
        string $categoryName,
        float $price,
        string $municipality,
        string $region,
        string $unit,
        int $stock = 1,
        ?int $sellerProfileId = null
    ): array {
        $service = new SmartPriceCheckService();

        return $service->check([
            'commodity' => $productName,
            'category' => $categoryName,
            'seller_price' => $price,
            'unit' => $unit ?: 'kg',
            'municipality' => $municipality,
            'region' => $region,
            'date' => date('Y-m-d'),
            'stock' => $stock,
            'seller_profile_id' => $sellerProfileId,
            'product_id' => null,
        ]);
    }

    public function deleteImage()
    {
        csrf_verify();

        $user =
            $this->requireFarmerUser();

        $db =
            Database::getInstance()
            ->connect();

        $imageId =
            $_POST['image_id'] ?? 0;

        $stmt = $db->prepare("SELECT product_images.*, products.seller_profile_id
            FROM product_images
            INNER JOIN products
                ON products.id = product_images.product_id
            INNER JOIN seller_profiles
                ON seller_profiles.id = products.seller_profile_id
            WHERE product_images.id = ?
                AND seller_profiles.user_id = ?");

        $stmt->execute([
            $imageId,
            $user['id']
        ]);

        $image = $stmt->fetch();

        if (!$image) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Image not found.'
            ], 404);
        }

        $countStmt = $db->prepare("SELECT COUNT(*) AS total FROM product_images WHERE product_id = ?");
        $countStmt->execute([$image->product_id]);
        $totalImages = (int) $countStmt->fetch()->total;

        if ($totalImages <= 1) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'The last remaining product image cannot be deleted.'
            ], 422);
        }

        $db->beginTransaction();

        $deleted = $this->deleteCloudinaryImage($image->image_public_id);

        if (!$deleted) {
            $db->rollBack();
            error_log('Cloudinary deletion failed for image id ' . $imageId . ' public_id ' . $image->image_public_id);

            return $this->jsonResponse([
                'success' => false,
                'message' => 'Unable to delete image from Cloudinary. Please try again.'
            ], 500);
        }

        $deleteStmt = $db->prepare("DELETE FROM product_images WHERE id = ?");
        $deleteStmt->execute([$imageId]);

        $newCoverImage = null;

        if ($image->is_cover) {
            $coverStmt = $db->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY display_order ASC, id ASC LIMIT 1");
            $coverStmt->execute([$image->product_id]);
            $newCoverImage = $coverStmt->fetch();

            if ($newCoverImage) {
                $db->prepare("UPDATE products SET image = ?, image_public_id = ? WHERE id = ?")
                    ->execute([
                        $newCoverImage->image_url,
                        $newCoverImage->image_public_id,
                        $image->product_id
                    ]);

                $db->prepare("UPDATE product_images SET is_cover = 0 WHERE product_id = ?")
                    ->execute([$image->product_id]);

                $db->prepare("UPDATE product_images SET is_cover = 1 WHERE id = ?")
                    ->execute([$newCoverImage->id]);
            }
        }

        $this->logProductActivity(
            $db,
            $user['id'],
            $image->product_id,
            'Product Image Deleted',
            $image->image_public_id
        );

        $db->commit();

        return $this->jsonResponse([
            'success' => true,
            'new_cover_url' => $newCoverImage->image_url ?? null,
            'new_cover_public_id' => $newCoverImage->image_public_id ?? null
        ]);
    }

    public function setImageCover()
    {
        csrf_verify();
        
        $user =
            $this->requireFarmerUser();

        $db =
            Database::getInstance()
            ->connect();

        $imageId =
            $_POST['image_id'] ?? 0;

        $stmt = $db->prepare("SELECT product_images.*, products.seller_profile_id
            FROM product_images
            INNER JOIN products
                ON products.id = product_images.product_id
            INNER JOIN seller_profiles
                ON seller_profiles.id = products.seller_profile_id
            WHERE product_images.id = ?
                AND seller_profiles.user_id = ?");

        $stmt->execute([
            $imageId,
            $user['id']
        ]);

        $image = $stmt->fetch();

        if (!$image) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Image not found.'
            ], 404);
        }

        $db->beginTransaction();

        $db->prepare("UPDATE product_images SET is_cover = 0 WHERE product_id = ?")
            ->execute([$image->product_id]);

        $db->prepare("UPDATE product_images SET is_cover = 1 WHERE id = ?")
            ->execute([$imageId]);

        $db->prepare("UPDATE products SET image = ?, image_public_id = ? WHERE id = ?")
            ->execute([
                $image->image_url,
                $image->image_public_id,
                $image->product_id
            ]);

        $db->commit();

        return $this->jsonResponse([
            'success' => true,
            'cover_url' => $image->image_url
        ]);
    }

    public function replaceImage()
    {
        csrf_verify();

        $user =
            $this->requireFarmerUser();

        $db =
            Database::getInstance()
            ->connect();

        $imageId =
            $_POST['image_id'] ?? 0;

        if (empty($_FILES['replacement_image']) || $_FILES['replacement_image']['error'] !== UPLOAD_ERR_OK) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Please select a replacement image.'
            ], 400);
        }

        $stmt = $db->prepare("SELECT product_images.*, products.seller_profile_id
            FROM product_images
            INNER JOIN products
                ON products.id = product_images.product_id
            INNER JOIN seller_profiles
                ON seller_profiles.id = products.seller_profile_id
            WHERE product_images.id = ?
                AND seller_profiles.user_id = ?");

        $stmt->execute([
            $imageId,
            $user['id']
        ]);

        $image = $stmt->fetch();

        if (!$image) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Image not found.'
            ], 404);
        }

        $file = $_FILES['replacement_image'];
        $allowedTypes = [
            'image/jpeg',
            'image/png',
            'image/webp'
        ];

        if (!in_array($file['type'], $allowedTypes, true) || $file['size'] > 10 * 1024 * 1024) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Replacement image must be JPEG, PNG, or WEBP and under 10MB.'
            ], 422);
        }

        $cloudinary = new CloudinaryService();

        $newUpload = $cloudinary->upload(
            $file,
            'ani-mart/products',
            ['max_size' => 10 * 1024 * 1024]
        );

        if (!is_array($newUpload) || empty($newUpload['url']) || empty($newUpload['public_id'])) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Unable to upload replacement image.'
            ], 500);
        }

        $deleted = $this->deleteCloudinaryImage($image->image_public_id);

        if (!$deleted) {
            $this->deleteCloudinaryImage($newUpload['public_id']);
            error_log('Cloudinary replacement cleanup failed for image id ' . $imageId . ' public_id ' . $image->image_public_id);

            return $this->jsonResponse([
                'success' => false,
                'message' => 'Unable to remove the previous image from Cloudinary. Please try again.'
            ], 500);
        }

        $db->beginTransaction();

        $update = $db->prepare("UPDATE product_images
            SET image_url = ?, image_public_id = ?
            WHERE id = ?");

        $update->execute([
            $newUpload['url'],
            $newUpload['public_id'],
            $imageId
        ]);

        if ($image->is_cover) {
            $db->prepare("UPDATE products SET image = ?, image_public_id = ? WHERE id = ?")
                ->execute([
                    $newUpload['url'],
                    $newUpload['public_id'],
                    $image->product_id
                ]);
        }

        $this->logProductActivity(
            $db,
            $user['id'],
            $image->product_id,
            'Product Image Replaced',
            $image->image_public_id
        );

        $db->commit();

        return $this->jsonResponse([
            'success' => true,
            'image_url' => $newUpload['url'],
            'image_public_id' => $newUpload['public_id']
        ]);
    }

    public function destroy()
    {
        csrf_verify();

        $user =
            Session::get('user');

        if (
            empty($user) ||
            strtolower($user['role'] ?? '') !== 'admin'
        ) {
            header('Location: /ani-mart/public/login');
            exit;
        }

        $db =
            Database::getInstance()
            ->connect();

        $productId =
            $_POST['id'] ?? 0;

        $stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        $product = $stmt->fetch();

        if (!$product) {
            $_SESSION['error'] =
                'Product not found.';

            header('Location: /ani-mart/public/admin');
            exit;
        }

        $imagesStmt = $db->prepare("SELECT * FROM product_images WHERE product_id = ?");
        $imagesStmt->execute([$productId]);
        $images = $imagesStmt->fetchAll();

        foreach ($images as $image) {
            if (!$this->deleteCloudinaryImage($image->image_public_id)) {
                error_log('Cloudinary deletion failed during permanent product delete for public_id: ' . $image->image_public_id);

                $_SESSION['error'] =
                    'Unable to delete all product images from Cloudinary. Product was not removed.';

                header('Location: /ani-mart/public/admin');
                exit;
            }
        }

        $deleteProduct = $db->prepare("DELETE FROM products WHERE id = ?");
        $deleteProduct->execute([$productId]);

        AuditLogService::log(
            'PRODUCT_DELETED',
            'product',
            (int) $productId,
            [
                'name' => trim((string) ($product->name ?? '')),
                'status' => 'permanently_deleted'
            ],
            (int) $user['id'],
            '/ani-mart/public/admin/products/delete',
            'success'
        );

        $_SESSION['success'] =
            'Product permanently deleted and Cloudinary assets removed.';

        header('Location: /ani-mart/public/admin');
        exit;
    }

    private function handleAdminProductDecision(string $decision)
    {
        $db = Database::connection();
        $adminUser = $this->requireAdminUser();
        $productId = (int) ($_POST['id'] ?? 0);
        $note = trim((string) ($_POST['note'] ?? ''));

        $stmt = $db->prepare("SELECT * FROM products WHERE id = ? LIMIT 1");
        $stmt->execute([$productId]);
        $product = $stmt->fetch();

        if (!$product) {
            Session::flash('error', 'Product not found.');
            return $this->redirect('/ani-mart/public/admin/products');
        }

        $previousStatus = strtolower((string) ($product->approval_status ?? 'pending'));

        if (in_array($previousStatus, ['approved', 'rejected'], true)) {
            Session::flash('error', 'This product review is already final and cannot be changed.');
            return $this->redirect('/ani-mart/public/admin/products/view?id=' . $productId);
        }

        if ($previousStatus === 'superseded') {
            Session::flash('error', 'This product was already replaced by a newer approved submission.');
            return $this->redirect('/ani-mart/public/admin/products/view?id=' . $productId);
        }

        $newStatus = 'pending';

        if ($decision === 'approved') {
            $newStatus = 'approved';
        } elseif ($decision === 'rejected') {
            $newStatus = 'rejected';
        } elseif ($decision === 'needs_correction') {
            $newStatus = 'needs_correction';
        }

        $moderationReason = $decision === 'approved'
            ? 'Approved by an administrator after reviewing the listing, seller history, and Smart Price Check information.'
            : ($note !== '' ? $note : null);
        $cipher = new SensitiveDataCipher();
        $adminNote = $note !== '' ? $note : null;
        $correctionReason = $decision === 'needs_correction' && $note !== '' ? $note : null;
        $db->prepare("UPDATE products SET approval_status = ?, admin_note = ?, admin_note_encrypted = ?, correction_reason = ?, correction_reason_encrypted = ?, moderation_reason = ?, moderation_reason_encrypted = ? WHERE id = ?")
            ->execute([
                $newStatus,
                $adminNote,
                $cipher->encrypt($adminNote, 'products.admin_note'),
                $correctionReason,
                $cipher->encrypt($correctionReason, 'products.correction_reason'),
                $moderationReason,
                $cipher->encrypt($moderationReason, 'products.moderation_reason'),
                $productId,
            ]);

        $seller = $db->prepare("SELECT user_id FROM seller_profiles WHERE id = ? LIMIT 1");
        $seller->execute([$product->seller_profile_id]);
        $sellerProfile = $seller->fetch();

        if ($sellerProfile) {
            NotificationService::resolveProductDecision(
                $productId,
                (int) $sellerProfile->user_id,
                (string) ($product->name ?? 'Product'),
                $decision,
                $note
            );

            if ($decision === 'approved') {
                $this->supersedePendingDuplicateProducts(
                    $db,
                    $product,
                    $productId,
                    (int) $adminUser['id'],
                    (int) $sellerProfile->user_id
                );
            }
        }

        $db->prepare("INSERT INTO product_approval_logs (product_id, admin_id, decision, note, note_encrypted, seller_price, reference_price, deviation_percentage, assessment, previous_status, new_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")
            ->execute([
                $productId,
                $adminUser['id'],
                $decision,
                $note !== '' ? $note : null,
                $cipher->encrypt($note !== '' ? $note : null, 'product_approval_logs.note'),
                $product->price,
                $product->last_reference_price,
                $product->last_price_deviation,
                $product->last_price_assessment,
                $previousStatus,
                $newStatus,
            ]);

        (new SmartPriceCheckService())->recordAdminDecision((int) $productId, $decision);

        AuditLogService::log(
            'PRODUCT_APPROVAL_' . strtoupper(str_replace('-', '_', $decision)),
            'product',
            (int) $productId,
            ['previous_status' => $previousStatus, 'new_status' => $newStatus, 'note' => $note],
            (int) $adminUser['id'],
            '/ani-mart/public/admin/products/decision',
            'success'
        );

        $resultLabels = [
            'approved' => 'Product approved successfully.',
            'rejected' => 'Product rejected successfully.',
            'needs_correction' => 'Product correction request sent successfully.',
        ];
        Session::flash('success', $resultLabels[$decision] ?? 'Product review updated successfully.');
        return $this->redirect('/ani-mart/public/admin/products');
    }

    private function supersedePendingDuplicateProducts(
        $db,
        object $approvedProduct,
        int $approvedProductId,
        int $adminId,
        int $sellerUserId
    ): void {
        $duplicates = $db->prepare("SELECT * FROM products
            WHERE seller_profile_id = ?
              AND id <> ?
              AND LOWER(TRIM(name)) = LOWER(TRIM(?))
              AND approval_status IN ('pending', 'pending_admin_review')");
        $duplicates->execute([
            $approvedProduct->seller_profile_id,
            $approvedProductId,
            $approvedProduct->name ?? '',
        ]);

        foreach ($duplicates->fetchAll() as $duplicate) {
            $reason = 'Replaced by approved product submission #' . $approvedProductId . ' to prevent duplicate marketplace listings.';
            $cipher = new SensitiveDataCipher();
            $update = $db->prepare("UPDATE products
                SET approval_status = 'superseded', is_active = 0, moderation_reason = ?, moderation_reason_encrypted = ?, admin_note = ?, admin_note_encrypted = ?
                WHERE id = ? AND approval_status IN ('pending', 'pending_admin_review')");
            $encryptedReason = $cipher->encrypt($reason, 'products.moderation_reason');
            $encryptedAdminNote = $cipher->encrypt($reason, 'products.admin_note');
            $update->execute([$reason, $encryptedReason, $reason, $encryptedAdminNote, $duplicate->id]);

            if ($update->rowCount() === 0) {
                continue;
            }

            NotificationService::resolveProductDecision(
                (int) $duplicate->id,
                $sellerUserId,
                (string) ($duplicate->name ?? 'Product'),
                'superseded'
            );

            $log = $db->prepare("INSERT INTO product_approval_logs
                (product_id, admin_id, decision, note, note_encrypted, seller_price, reference_price, deviation_percentage, assessment, previous_status, new_status)
                VALUES (?, ?, 'superseded', ?, ?, ?, ?, ?, ?, ?, 'superseded')");
            $log->execute([
                $duplicate->id,
                $adminId,
                $reason,
                $cipher->encrypt($reason, 'product_approval_logs.note'),
                $duplicate->price,
                $duplicate->last_reference_price,
                $duplicate->last_price_deviation,
                $duplicate->last_price_assessment,
                $duplicate->approval_status ?? 'pending_admin_review',
            ]);
        }
    }

    private function deleteCloudinaryImage($publicId)
    {
        if (empty($publicId)) {
            return false;
        }

        $cloudinary = new CloudinaryService();

        $result = $cloudinary->delete($publicId);

        return isset($result['result']) && in_array((string) $result['result'], ['ok', 'not found'], true);
    }

    private function logProductActivity($db, $userId, $productId, $action, $publicId = null)
    {
        $log = $db->prepare("INSERT INTO product_activity_logs (
            product_id,
            user_id,
            action,
            new_value
        ) VALUES (?, ?, ?, ?)");

        $log->execute([
            $productId,
            $userId,
            $action,
            $publicId
        ]);
    }

    private function jsonResponse($data, $status = 200)
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
