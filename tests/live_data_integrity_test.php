<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap/app.php';

use App\Core\Database;
use App\Services\AdminAnalyticsService;
use App\Services\DashboardAnalyticsService;
use App\Services\RecommendationService;
use App\Services\TransparencyAlertService;

$db = Database::connection();
$failures = [];

$assert = static function (bool $condition, string $message) use (&$failures): void {
    if (!$condition) {
        $failures[] = $message;
    }
};

$admin = (new AdminAnalyticsService())->dashboard();
$databaseOrderCount = (int) $db->query('SELECT COUNT(*) FROM orders')->fetchColumn();
$databaseRevenue = (float) $db->query("
    SELECT COALESCE(SUM(total_amount), 0)
    FROM orders
    WHERE LOWER(COALESCE(status, '')) IN ('completed', 'delivered')
")->fetchColumn();
$databaseFarmerCount = (int) $db->query("SELECT COUNT(*) FROM seller_profiles WHERE status = 'approved'")->fetchColumn();
$databaseProductCount = (int) $db->query('SELECT COUNT(*) FROM products WHERE is_active = 1')->fetchColumn();

$assert((int) ($admin['overview']['total_orders'] ?? -1) === $databaseOrderCount, 'Admin order total matches the orders table');
$assert(abs((float) ($admin['overview']['total_revenue'] ?? -1) - $databaseRevenue) < 0.001, 'Admin revenue matches completed order totals');
$assert((int) ($admin['overview']['active_farmers'] ?? -1) === $databaseFarmerCount, 'Admin farmer total matches approved seller profiles');
$assert((int) ($admin['overview']['active_products'] ?? -1) === $databaseProductCount, 'Admin product total matches active products');

$transparency = new TransparencyAlertService();
$assert($transparency->count() === count($transparency->all()), 'Transparency dashboard count matches its records page');

$customerId = (int) ($db->query("SELECT id FROM users WHERE role = 'customer' ORDER BY id LIMIT 1")->fetchColumn() ?: 0);
if ($customerId > 0) {
    $customerAnalytics = (new DashboardAnalyticsService())->forRole('customer', $customerId);
    $customerOrderStmt = $db->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ? AND created_at >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 5 MONTH), '%Y-%m-01') AND LOWER(COALESCE(status, 'pending')) <> 'cancelled'");
    $customerOrderStmt->execute([$customerId]);
    $databaseCustomerOrders = (int) $customerOrderStmt->fetchColumn();
    $assert(array_sum(array_column($customerAnalytics['trend'], 'orders')) === $databaseCustomerOrders, 'Customer dashboard trend matches live orders');

    $recommendations = RecommendationService::recommendProductsWithContext($customerId, 8)['products'] ?? [];
    foreach ($recommendations as $product) {
        $exists = (int) $db->query('SELECT COUNT(*) FROM products WHERE id = ' . (int) $product->id)->fetchColumn();
        $assert($exists === 1, 'Every recommendation points to a stored product');
    }
}

if ($failures !== []) {
    fwrite(STDERR, "Live-data integrity checks failed:\n- " . implode("\n- ", array_unique($failures)) . "\n");
    exit(1);
}

echo "Live-data integrity checks passed.\n";
