<?php

namespace App\Controllers;

use App\Services\AuthGuard;
use App\Services\ImageUploadService;
use App\Services\SlugService;
use App\Services\SchemaInspector;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Services\NotificationService;
use App\Services\SellerProfileService;
use App\Services\SellerApprovalService;
use App\Services\CloudinaryService;
use App\Services\SensitiveDataCipher;
use App\Models\SellerProfile;

class SellerCenterController extends Controller
{
    public function index()
    {
        $user = $this->requireFarmer();

        $activeShop = SellerProfileService::activeShop($user['id']);

        if (!$activeShop) {
            return $this->redirect('/ani-mart/public/seller-center/shops');
        }

        $db = Database::connection();

        $stats = [
            'total_products' => 0,
            'total_orders' => 0,
            'total_revenue' => 0,
            'total_complaints' => 0,
        ];

        try {
            $productStmt = $db->prepare("SELECT COUNT(*) AS total FROM products WHERE seller_profile_id = ? AND is_active = 1");
            $productStmt->execute([$activeShop->id]);
            $stats['total_products'] = (int) ($productStmt->fetch()->total ?? 0);

            $orderStmt = $db->prepare("SELECT COUNT(DISTINCT orders.id) AS total_orders, COALESCE(SUM(order_items.price * order_items.quantity), 0) AS total_revenue FROM order_items INNER JOIN orders ON orders.id = order_items.order_id WHERE order_items.seller_profile_id = ? AND orders.status IN ('completed','delivered')");
            $orderStmt->execute([$activeShop->id]);
            $orderTotals = $orderStmt->fetch();
            $stats['total_orders'] = (int) ($orderTotals->total_orders ?? 0);
            $stats['total_revenue'] = (float) ($orderTotals->total_revenue ?? 0);

            $complaintStmt = $db->prepare("SELECT COUNT(*) AS total_complaints FROM complaints WHERE seller_profile_id = ?");
            $complaintStmt->execute([$activeShop->id]);
            $stats['total_complaints'] = (int) ($complaintStmt->fetch()->total_complaints ?? 0);
        } catch (\Throwable $e) {
            $stats = [
                'total_products' => 0,
                'total_orders' => 0,
                'total_revenue' => 0,
                'total_complaints' => 0,
            ];
        }

        return $this->view('farmer/center/dashboard', [
            'activeShop' => $activeShop,
            'stats' => $stats,
            'pageCss' => '/ani-mart/public/assets/css/pages/farmer.css',
        ]);
    }

    public function shops()
    {
        $user = $this->requireFarmer();

        $shops = SellerProfileService::userShops($user['id']);
        $activeShop = SellerProfileService::activeShop($user['id']);

        return $this->view('farmer/center/shops', [
            'shops' => $shops,
            'activeShop' => $activeShop,
            'pageCss' => '/ani-mart/public/assets/css/pages/farmer.css',
        ]);
    }

    public function create()
    {
        $this->requireFarmer();

        return $this->view('farmer/center/create', [
            'pageCss' => '/ani-mart/public/assets/css/pages/farmer.css',
        ]);
    }

    public function store()
    {
        csrf_verify();

        $user = $this->requireFarmer();

        $shopName = trim($_POST['shop_name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $contactNumber = trim($_POST['contact_number'] ?? '');

        if ($shopName === '' || $description === '' || $address === '' || $contactNumber === '') {
            Session::flash('error', 'Please complete all required shop fields.');
            return $this->redirect('/ani-mart/public/seller-center/shops/create');
        }

        $db = Database::connection();
        $this->ensureSellerProfileContactColumn($db);

        try {
            $logo = $this->uploadImage('logo', 'shop-logos');
            $banner = $this->uploadImage('banner', 'shop-banners');
            $slug = SlugService::uniqueSeller($shopName, 0, 'shop', $db);
        } catch (\Throwable $exception) {
            Session::flash('error', 'The shop could not be created because an image upload failed. Please try again.');
            return $this->redirect('/ani-mart/public/seller-center/shops/create');
        }

        $columns = [
            'user_id',
            'shop_name',
            'slug',
            'description',
            'farm_logo',
            'farm_banner',
            'farm_location',
            'contact_number',
        ];

        if (SchemaInspector::hasColumn('seller_profiles', 'shop_theme')) {
            $columns[] = 'shop_theme';
        }

        if (SchemaInspector::hasColumn('seller_profiles', 'government_id')) {
            $columns[] = 'government_id';
        }

        $columns = array_merge($columns, ['status', 'verification_badge', 'trust_score', 'transparency_score', 'created_at']);
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));

        $values = [
            $user['id'],
            $shopName,
            $slug,
            $description,
            $logo,
            $banner,
            $address,
            $contactNumber,
        ];

        if (in_array('shop_theme', $columns, true)) {
            $values[] = 'fresh-farm';
        }

        if (in_array('government_id', $columns, true)) {
            $values[] = null;
        }

        $values = array_merge($values, ['pending', 'new', 0, 100, date('Y-m-d H:i:s')]);

        try {
            $stmt = $db->prepare("INSERT INTO seller_profiles (" . implode(', ', $columns) . ") VALUES ($placeholders)");
            $stmt->execute($values);
        } catch (\Throwable $exception) {
            Session::flash('error', 'The shop could not be created. Please check the shop details and try again.');
            return $this->redirect('/ani-mart/public/seller-center/shops/create');
        }

        $shopId = $db->lastInsertId();
        $cipher = new SensitiveDataCipher();
        $db->prepare('UPDATE seller_profiles SET farm_location_encrypted = ?, contact_number_encrypted = ? WHERE id = ?')
            ->execute([
                $cipher->encrypt($address, 'seller_profiles.farm_location'),
                $cipher->encrypt($contactNumber, 'seller_profiles.contact_number'),
                $shopId,
            ]);
        SellerProfileService::setActiveShopId($shopId);

        $adminStmt = $db->query("SELECT id FROM users WHERE role = 'admin'");
        $admins = $adminStmt->fetchAll();

        foreach ($admins as $admin) {
            NotificationService::send(
                $admin->id,
                'New Shop Verification Request',
                "$shopName was submitted for verification.",
                'verification',
                'bi-patch-check',
                'info',
                '/ani-mart/public/admin/verifications'
            );
        }

        Session::flash('success', 'Shop created successfully. An administrator will review it shortly.');

        return $this->redirect('/ani-mart/public/seller-center/shops');
    }

    private function ensureSellerProfileContactColumn($db): void
    {
        SchemaInspector::requireColumns('seller_profiles', [
            'contact_number', 'farm_location_encrypted', 'contact_number_encrypted', 'government_id',
        ]);
    }

    public function switch()
    {
        csrf_verify();

        $user = $this->requireFarmer();

        $shopId = (int) ($_POST['shop_id'] ?? 0);

        $isJson = (isset($_SERVER['HTTP_ACCEPT']) && stripos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
            || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

        if (!$shopId || !SellerProfileService::isOwnedByUser($shopId, $user['id'])) {
            if ($isJson) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Unable to switch shops.']);
                exit;
            }

            Session::flash('error', 'Unable to switch shops.');
            return $this->redirect($_SERVER['HTTP_REFERER'] ?? '/ani-mart/public/seller-center');
        }

        SellerProfileService::setActiveShopId($shopId);

        if ($isJson) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Active shop updated.']);
            exit;
        }

        Session::flash('success', 'Active shop updated.');

        $redirectTo = '/ani-mart/public/seller-center';
        $referer = $_SERVER['HTTP_REFERER'] ?? null;
        $host = $_SERVER['HTTP_HOST'] ?? null;

        if ($referer !== null) {
            $refererHost = parse_url($referer, PHP_URL_HOST);
            if ($refererHost !== null && ($refererHost === $host || $refererHost === 'localhost' || $refererHost === '127.0.0.1')) {
                $redirectTo = $referer;
            }
        }

        $separator = str_contains($redirectTo, '?') ? '&' : '?';
        $redirectTo .= $separator . 'shop_switched=1';

        return $this->redirect($redirectTo);
    }

    public function analytics()
    {
        $user = $this->requireFarmer();

        $activeShop = SellerProfileService::activeShop($user['id']);

        if (!$activeShop) {
            return $this->redirect('/ani-mart/public/seller-center/shops');
        }

        $period = in_array(($_GET['period'] ?? ''), ['daily', 'weekly', 'monthly'], true)
            ? $_GET['period']
            : 'custom';
        $periodStarts = [
            'daily' => date('Y-m-d'),
            'weekly' => date('Y-m-d', strtotime('-6 days')),
            'monthly' => date('Y-m-d', strtotime('-29 days')),
        ];
        $defaultStart = $periodStarts[$period] ?? date('Y-m-d', strtotime('-29 days'));
        $startDate = $this->analyticsDate($_GET['start_date'] ?? $defaultStart, $defaultStart);
        $endDate = $this->analyticsDate($_GET['end_date'] ?? date('Y-m-d'), date('Y-m-d'));
        if ($startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        $analytics = SellerProfileService::analytics((int) $activeShop->id, $startDate, $endDate);
        $ordersSummary = $analytics['summary'];
        $ratingSummary = $analytics['rating'];

        $db = Database::connection();

        $productStmt = $db->prepare("SELECT COUNT(*) AS total_products FROM products WHERE seller_profile_id = ? AND is_active = 1");
        $productStmt->execute([$activeShop->id]);
        $productSummary = $productStmt->fetch();

        $complaintsByStatus = $analytics['complaints'];

        return $this->view('farmer/center/analytics', [
            'activeShop' => $activeShop,
            'ordersSummary' => $ordersSummary,
            'productSummary' => $productSummary,
            'ratingSummary' => $ratingSummary,
            'complaintsByStatus' => $complaintsByStatus,
            'analyticsTrend' => $analytics['trend'],
            'topProducts' => $analytics['top_products'],
            'customerAnalytics' => $analytics['customers'],
            'financialRecords' => $analytics['financial_records'],
            'startDate' => $startDate,
            'endDate' => $endDate,
            'period' => $period,
            'pageCss' => '/ani-mart/public/assets/css/pages/farmer.css',
        ]);
    }

    public function downloadFinancialReport()
    {
        $user = $this->requireFarmer();
        $activeShop = SellerProfileService::activeShop($user['id']);
        if (!$activeShop) {
            return $this->redirect('/ani-mart/public/seller-center/shops');
        }

        $period = in_array(($_GET['period'] ?? ''), ['daily', 'weekly', 'monthly'], true) ? $_GET['period'] : 'custom';
        $periodStarts = [
            'daily' => date('Y-m-d'),
            'weekly' => date('Y-m-d', strtotime('-6 days')),
            'monthly' => date('Y-m-d', strtotime('-29 days')),
        ];
        $defaultStart = $periodStarts[$period] ?? date('Y-m-d', strtotime('-29 days'));
        $startDate = $this->analyticsDate($_GET['start_date'] ?? $defaultStart, $defaultStart);
        $endDate = $this->analyticsDate($_GET['end_date'] ?? date('Y-m-d'), date('Y-m-d'));
        if ($startDate > $endDate) [$startDate, $endDate] = [$endDate, $startDate];

        $analytics = SellerProfileService::analytics((int) $activeShop->id, $startDate, $endDate);
        $summary = $analytics['summary'];
        $safeShop = preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) ($activeShop->shop_name ?? 'shop'));
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="ani-mart-financial-report-' . trim($safeShop, '-') . '-' . $startDate . '-to-' . $endDate . '.csv"');
        header('X-Content-Type-Options: nosniff');

        $output = fopen('php://output', 'wb');
        fwrite($output, "\xEF\xBB\xBF");
        fputcsv($output, ['Ani-Mart Seller Financial Report']);
        fputcsv($output, ['Shop', $this->safeCsvCell((string) ($activeShop->shop_name ?? ''))]);
        fputcsv($output, ['Period', $startDate . ' to ' . $endDate]);
        fputcsv($output, []);
        fputcsv($output, ['Gross sales', number_format((float) $summary->gross_sales, 2, '.', '')]);
        fputcsv($output, ['Cancelled orders', (int) $summary->cancelled_orders]);
        fputcsv($output, ['Cancelled order value', number_format((float) $summary->cancelled_order_value, 2, '.', '')]);
        fputcsv($output, ['Orders with returns', (int) $summary->returned_orders]);
        fputcsv($output, ['Refunded orders', (int) $summary->refunded_orders]);
        fputcsv($output, ['Refunded amount', number_format((float) $summary->refunded_amount, 2, '.', '')]);
        fputcsv($output, ['Delivery fees', number_format((float) $summary->delivery_fees, 2, '.', '')]);
        fputcsv($output, ['Net earnings', number_format((float) $summary->net_earnings, 2, '.', '')]);
        fputcsv($output, []);
        fputcsv($output, ['Order', 'Date', 'Customer', 'Status', 'Payment method', 'Units', 'Product value', 'Gross sales', 'Cancelled value', 'Delivery fees', 'Refunded amount', 'Net earnings']);
        foreach ($analytics['financial_records'] as $record) {
            fputcsv($output, [
                $this->safeCsvCell((string) $record->order_number),
                (string) $record->created_at,
                $this->safeCsvCell((string) $record->customer_name),
                (string) $record->status,
                (string) $record->payment_method,
                (int) $record->units,
                number_format((float) $record->product_value, 2, '.', ''),
                number_format((float) $record->gross_sales, 2, '.', ''),
                number_format((float) $record->cancelled_value, 2, '.', ''),
                number_format((float) $record->delivery_fees, 2, '.', ''),
                number_format((float) $record->refunded_amount, 2, '.', ''),
                number_format((float) $record->net_earnings, 2, '.', ''),
            ]);
        }
        fclose($output);
        exit;
    }

    private function analyticsDate($value, string $fallback): string
    {
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', (string) $value);
        return $date && $date->format('Y-m-d') === $value ? $date->format('Y-m-d') : $fallback;
    }

    private function safeCsvCell(string $value): string
    {
        $value = trim($value);
        return preg_match('/^[=+\-@]/', $value) ? "'" . $value : $value;
    }

    public function reviews()
    {
        $user = $this->requireFarmer();
        $activeShop = SellerProfileService::activeShop($user['id']);

        if (!$activeShop) {
            return $this->view('farmer/reviews/index', [
                'reviews' => [],
                'activeShop' => null,
                'pageCss' => '/ani-mart/public/assets/css/pages/farmer.css',
            ]);
        }

        $db = Database::connection();
        $stmt = $db->prepare("SELECT reviews.*, products.name AS product_name, products.image AS product_image, orders.order_number, users.first_name, users.last_name FROM reviews INNER JOIN products ON products.id = reviews.product_id INNER JOIN order_items ON order_items.id = reviews.order_item_id INNER JOIN orders ON orders.id = order_items.order_id INNER JOIN users ON users.id = reviews.user_id WHERE products.seller_profile_id = ? AND order_items.seller_profile_id = ? ORDER BY reviews.created_at DESC, reviews.id DESC");
        $stmt->execute([(int) $activeShop->id, (int) $activeShop->id]);

        return $this->view('farmer/reviews/index', [
            'reviews' => $stmt->fetchAll(),
            'activeShop' => $activeShop,
            'pageCss' => '/ani-mart/public/assets/css/pages/farmer.css',
        ]);
    }

    public function customize()
    {
        $user = $this->requireFarmer();

        $requestedShopId = (int) ($_GET['shop_id'] ?? 0);
        if ($requestedShopId && SellerProfileService::isOwnedByUser($requestedShopId, $user['id'])) {
            SellerProfileService::setActiveShopId($requestedShopId);
        }

        $activeShop = SellerProfileService::activeShop($user['id']);

        if (!$activeShop || ($activeShop->status ?? '') !== 'approved' || !(bool) ($activeShop->is_active ?? true)) {
            Session::flash('error', 'Only approved active shops can be customized.');
            return $this->redirect('/ani-mart/public/seller-center');
        }

        $availableProducts = SellerProfileService::availableProducts($activeShop->id);
        $featuredProducts = SellerProfileService::featuredProducts($activeShop->id);
        $featuredIds = array_map(static fn ($product) => (int) $product->id, $featuredProducts);
        $themes = SellerProfileService::themes();

        return $this->view('farmer/center/customize', [
            'activeShop' => $activeShop,
            'availableProducts' => $availableProducts,
            'featuredIds' => $featuredIds,
            'themes' => $themes,
            'metrics' => SellerProfile::metrics((int) $activeShop->id),
            'farmGallery' => SellerProfile::gallery((int) $activeShop->id, 'farm'),
            'harvestGallery' => SellerProfile::gallery((int) $activeShop->id, 'harvest'),
            'pageCss' => '/ani-mart/public/assets/css/pages/farmer.css',
        ]);
    }

    public function updateCustomization()
    {
        csrf_verify();
        $user = $this->requireFarmer();
        $activeShop = SellerProfileService::activeShop($user['id']);

        if (!$activeShop || ($activeShop->status ?? '') !== 'approved' || !(bool) ($activeShop->is_active ?? true)) {
            Session::flash('error', 'Only approved active shops can be customized.');
            return $this->redirect('/ani-mart/public/seller-center');
        }

        $theme = (string) ($_POST['shop_theme'] ?? $activeShop->shop_theme ?? 'fresh-farm');
        $themes = SellerProfileService::themes();
        if (!isset($themes[$theme])) {
            $theme = 'fresh-farm';
        }

        $banner = $this->uploadCustomizationImage('shop_banner', 'shop-banners', $activeShop->farm_banner ?? null);
        $logo = $this->uploadCustomizationImage('shop_logo', 'shop-logos', $activeShop->farm_logo ?? null);

        $db = Database::connection();
        $shopName = trim($_POST['shop_name'] ?? $activeShop->shop_name ?? '');
        if ($shopName === '') {
            Session::flash('error', 'Shop name is required.');
            return $this->redirect($this->customizationReturnUrl());
        }

        $farmLocation = trim($_POST['farm_location'] ?? $activeShop->farm_location ?? '');
        $stmt = $db->prepare('UPDATE seller_profiles SET shop_name = ?, description = ?, farm_story = ?, farm_location = ?, operating_hours = ?, delivery_information = ?, shop_theme = ?, farm_banner = ?, farm_logo = ?, updated_at = NOW() WHERE id = ? AND user_id = ?');
        $stmt->execute([
            $shopName,
            trim($_POST['description'] ?? $activeShop->description ?? ''),
            trim($_POST['farm_story'] ?? $activeShop->farm_story ?? ''),
            $farmLocation,
            trim($_POST['operating_hours'] ?? $activeShop->operating_hours ?? ''),
            trim($_POST['delivery_information'] ?? $activeShop->delivery_information ?? ''),
            $theme,
            $banner,
            $logo,
            $activeShop->id,
            $user['id'],
        ]);

        $imageService = new CloudinaryService();
        if ($banner !== ($activeShop->farm_banner ?? null) && !empty($activeShop->farm_banner)) {
            $imageService->deleteByUrl((string) $activeShop->farm_banner, false);
        }
        if ($logo !== ($activeShop->farm_logo ?? null) && !empty($activeShop->farm_logo)) {
            $imageService->deleteByUrl((string) $activeShop->farm_logo, false);
        }
        foreach ([$banner, $logo] as $imageUrl) {
            $asset = $imageUrl ? $imageService->findByUrl((string) $imageUrl) : null;
            if ($asset) {
                $imageService->associate((string) $asset->public_id, 'seller_profile', (int) $activeShop->id, (int) $user['id']);
            }
        }

        $cipher = new SensitiveDataCipher();
        $db->prepare('UPDATE seller_profiles SET farm_location_encrypted = ? WHERE id = ? AND user_id = ?')
            ->execute([
                $cipher->encrypt($farmLocation, 'seller_profiles.farm_location'),
                $activeShop->id,
                $user['id'],
            ]);

        if (isset($_POST['save_featured_products']) || array_key_exists('featured_products', $_POST)) {
            SellerProfileService::saveFeaturedProducts($activeShop->id, (array) ($_POST['featured_products'] ?? []));
        }
        Session::flash('success', 'Shop customization saved successfully.');
        return $this->redirect($this->customizationReturnUrl());
    }

    private function uploadCustomizationImage(string $field, string $folder, ?string $currentValue): ?string
    {
        if (empty($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
            return $currentValue;
        }

        $upload = (new ImageUploadService())->publicField($field, $folder);
        if (!$upload || empty($upload['secure_url'])) {
            Session::flash('error', 'The image could not be uploaded.');
            return $this->redirect($this->customizationReturnUrl());
        }

        return $upload['secure_url'];
    }

    private function customizationReturnUrl(): string
    {
        $url = '/ani-mart/public/seller-center/shop/customize';
        $tab = $_POST['return_tab'] ?? '';
        return in_array($tab, ['appearance', 'featured'], true)
            ? $url . '?tab=' . $tab
            : $url;
    }

    private function requireFarmer()
    {
        return AuthGuard::farmer();
    }

    private function uploadImage($fieldName, $folder)
    {
        $upload = (new ImageUploadService())->publicField($fieldName, $folder);
        return $upload['secure_url'] ?? null;
    }

}
