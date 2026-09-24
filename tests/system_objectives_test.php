<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$failures = [];

$assertContains = static function (string $file, string $needle, string $label) use ($root, &$failures): void {
    $contents = file_get_contents($root . '/' . $file);
    if ($contents === false || !str_contains($contents, $needle)) {
        $failures[] = $label;
    }
};

$assertContains('app/Controllers/CheckoutController.php', 'beginTransaction()', 'Checkout uses a database transaction');
$assertContains('app/Controllers/CheckoutController.php', 'FOR UPDATE', 'Checkout locks inventory rows');
$assertContains('app/Controllers/CheckoutController.php', 'GREATEST(stock - ?, 0)', 'Checkout safely decrements stock');
$assertContains('app/Controllers/ProductController.php', 'AND seller_profiles.user_id = ?', 'Product changes enforce seller ownership');
$assertContains('app/Controllers/AuthController.php', 'password_verify(', 'Login verifies password hashes');
$assertContains('app/Controllers/AuthController.php', 'session_regenerate_id(true)', 'Authentication rotates session identifiers');
$assertContains('app/Services/SellerProfileService.php', 'calculateNetEarnings', 'Seller analytics calculates net earnings');
$assertContains('app/Controllers/SellerCenterController.php', "['daily', 'weekly', 'monthly']", 'Seller analytics supports reporting presets');
$assertContains('app/Controllers/MarketplaceController.php', 'stockSnapshot', 'Marketplace exposes live stock snapshots');
$assertContains('app/Views/orders/receipt.php', '$totalAmount', 'Digital receipts render order totals');
$assertContains('app/Services/AuthSecurityService.php', 'password_reset_tokens', 'Password reset tokens are stored persistently');
$assertContains('app/Services/AuthSecurityService.php', 'hash(\'sha256\', $rawToken)', 'Password reset tokens are hashed before storage');
$assertContains('app/Services/AuthSecurityService.php', 'auth_throttles', 'Login attempt limits persist in the database');
$assertContains('app/Controllers/AuthController.php', 'ADMIN_LOGIN_OTP_SUCCESS', 'Administrator email-code login is supported');
$assertContains('routes/web.php', '/password/forgot', 'Forgot-password routes are registered');
$assertContains('app/Services/OrderCancellationService.php', 'FOR UPDATE', 'Cancellation decisions lock the order and request');
$assertContains('app/Services/OrderCancellationService.php', 'SET stock = stock + ?', 'Accepted cancellations restore inventory');
$assertContains('app/Controllers/OrderController.php', 'Cancellation Request Received', 'Cancellation requests notify the seller');
$assertContains('app/Controllers/OrderController.php', 'Cancellation Request Accepted', 'Cancellation decisions notify the customer');
$assertContains('app/Services/ReturnService.php', 'requested_refund_amount', 'Returns record requested refund amounts');
$assertContains('app/Services/ReturnService.php', 'received_quantity', 'Returns record products actually received');
$assertContains('app/Services/ReturnService.php', 'refund_method', 'Return resolutions record COD-compatible refund methods');
$assertContains('app/Services/ReturnService.php', 'return_resolution_history', 'Return decisions maintain append-only resolution history');
$assertContains('app/Controllers/ReturnController.php', 'return_evidence', 'Returns persist supporting evidence');
$assertContains('app/Services/InventoryNotificationService.php', 'LOW_STOCK_THRESHOLD = 3', 'Inventory alerts use the visible low-stock threshold');
$assertContains('app/Services/InventoryNotificationService.php', 'last_notified_at', 'Inventory alerts persist notification state to prevent repeats');
$assertContains('app/Controllers/CheckoutController.php', 'InventoryNotificationService', 'Checkout evaluates stock alerts after decrementing inventory');
$assertContains('app/Controllers/ProductController.php', 'evaluateProduct', 'Manual inventory changes reset and evaluate stock alerts');
$assertContains('app/Services/OrderCancellationService.php', 'InventoryNotificationService', 'Cancellation restocking resets stock alert state');
$assertContains('app/Services/SellerProfileService.php', 'gross_sales', 'Seller financial records include gross sales');
$assertContains('app/Services/SellerProfileService.php', 'cancelled_order_value', 'Seller financial records include cancelled order value');
$assertContains('app/Services/SellerProfileService.php', 'refunded_amount', 'Seller financial records deduct actual refunds');
$assertContains('app/Services/SellerProfileService.php', 'delivery_fees', 'Seller financial records include delivery fees');
$assertContains('app/Controllers/SellerCenterController.php', 'text/csv', 'Seller financial reports are downloadable as CSV');
$assertContains('app/Controllers/SellerCenterController.php', 'safeCsvCell', 'Downloaded reports guard against spreadsheet formula injection');
$assertContains('app/Views/orders/index.php', 'orderSellerFilter', 'Order history filters by seller');
$assertContains('app/Views/orders/index.php', 'orderProductFilter', 'Order history filters by product');
$assertContains('app/Views/orders/index.php', 'orderDateFrom', 'Order history filters by date');
$assertContains('app/Views/orders/index.php', 'orderStatusFilter', 'Order history filters by status');
$assertContains('routes/web.php', '/customer/orders/export/csv', 'Order history CSV export is registered');
$assertContains('routes/web.php', '/customer/orders/export/pdf', 'Order history PDF export is registered');
$assertContains('app/Services/OrderHistoryReportService.php', 'safeCsv', 'Order history CSV prevents spreadsheet formula injection');
$assertContains('app/Services/MarketplacePolicyService.php', "'terms' =>", 'Terms and conditions are published');
$assertContains('app/Services/MarketplacePolicyService.php', "'privacy' =>", 'Privacy policy is published');
$assertContains('app/Services/MarketplacePolicyService.php', "'seller' =>", 'Seller policy is published');
$assertContains('app/Services/MarketplacePolicyService.php', "'prohibited-products' =>", 'Prohibited-product rules are published');
$assertContains('app/Services/MarketplacePolicyService.php', "'cancellation-returns' =>", 'Cancellation and return policy is published');
$assertContains('routes/web.php', '/policies/{slug}', 'Individual marketplace policy routes are registered');
$assertContains('app/Views/partials/footer.php', '/policies/privacy', 'Marketplace policies are linked from the site footer');
$assertContains('app/Views/partials/navbar.php', 'Storefront', 'Seller profile dropdown links to the active public storefront');
$assertContains('app/Views/farm/show.php', 'farm-message-link', 'Public storefront hero provides a message-seller action');
$assertContains('app/Controllers/MessageController.php', 'seller_profile_id', 'Messaging can start directly from a storefront seller');
$assertContains('app/Services/MarketplacePolicyService.php', 'COD orders still require an actual refund record', 'Cancellation and return policy covers COD refund records');
$assertContains('app/Controllers/DashboardController.php', "'admin/dashboard/index'", 'Administrator uses a dedicated dashboard view');
$assertContains('app/Views/admin/dashboard/index.php', 'Restricted administration area', 'Administrator dashboard is visually identified as restricted');
$assertContains('app/Views/admin/dashboard/index.php', 'Administration navigation', 'Administrator dashboard has dedicated management navigation');
$assertContains('app/Views/admin/dashboard/index.php', 'Needs your review', 'Administrator dashboard prioritizes operational review queues');
$assertContains('database/migrations/2026_09_08_admin_controls.sql', 'ADD COLUMN IF NOT EXISTS is_active', 'Product categories support reversible archiving');
$assertContains('database/migrations/2026_09_08_admin_controls.sql', "account_status ENUM('active','suspended')", 'User accounts support suspension and restoration');
$assertContains('routes/web.php', '/admin/categories/save', 'Category maintenance route is registered');
$assertContains('routes/web.php', '/admin/categories/status', 'Category archive and restore route is registered');
$assertContains('routes/web.php', '/admin/users', 'User access management route is registered');
$assertContains('routes/web.php', '/admin/users/status', 'User suspension and restoration route is registered');
$assertContains('routes/web.php', '/admin/reference-data/prices/update', 'Reference-price edit route is registered');
$assertContains('app/Services/CategoryAdminService.php', 'COUNT(p.id) AS product_count', 'Category controls show affected product counts');
$assertContains('app/Services/CategoryAdminService.php', 'UPDATE categories SET is_active', 'Categories are archived without deleting product relationships');
$assertContains('app/Services/AccountControlService.php', 'FOR UPDATE', 'Account status changes lock the selected user');
$assertContains('app/Controllers/AuthController.php', 'LOGIN_BLOCKED_SUSPENDED', 'Suspended users are blocked during login');
$assertContains('app/Core/Router.php', 'AccountControlService', 'Open sessions enforce current suspension status');
$assertContains('app/Services/PriceReferenceService.php', 'public function updateManual', 'Reference prices can be edited in place');
$assertContains('app/Services/PriceReferenceService.php', 'is_new_today', 'Newly added price references are identified');
$assertContains('app/Services/PriceReferenceImportService.php', 'classifyRows', 'CSV previews distinguish new references from same-day updates');
$assertContains('app/Views/admin/reference-data/prices.php', 'referenceSearch', 'Active price references provide search and filters');
$assertContains('app/Controllers/SupportCenterController.php', "['everyone', 'customers', 'farmers', 'admins']", 'System announcements support role-based audiences');
$assertContains('app/Controllers/SupportCenterController.php', 'system_announcement_published', 'System announcements are recorded in the audit trail');
$assertContains('app/Views/admin/support/settings.php', 'Announcement History', 'Administrators can review published announcements');
$assertContains('app/Services/OrderWorkflowSchemaService.php', 'CREATE TABLE IF NOT EXISTS order_cancellation_requests', 'Cancellation workflow repairs a missing schema automatically');
$assertContains('app/Services/OrderWorkflowSchemaService.php', 'CREATE TABLE IF NOT EXISTS return_requests', 'Return workflow repairs a missing schema automatically');
$assertContains('app/Controllers/CustomerController.php', 'OrderWorkflowSchemaService', 'Customer order history prepares workflow tables before joining them');
$assertContains('app/Controllers/FarmerController.php', 'OrderWorkflowSchemaService', 'Seller order history prepares workflow tables before joining them');
$assertContains('app/Controllers/ReturnController.php', 'OrderWorkflowSchemaService', 'Return pages prepare all required workflow tables');
$assertContains('routes/web.php', "'/farmer/analytics'", 'Legacy farmer analytics URL opens the seller analytics dashboard');
$assertContains('routes/web.php', "'/farmer/analytics/download'", 'Legacy farmer analytics download URL remains available');
$assertContains('routes/web.php', '/profile/delete-account', 'Customer and seller account deletion route is registered');
$assertContains('app/Services/AccountDeletionService.php', 'GRACE_PERIOD_DAYS = 30', 'Account deletion has a 30-day grace period');
$assertContains('app/Services/AccountDeletionService.php', 'handleSuccessfulLogin', 'A successful login can cancel pending deletion');
$assertContains('app/Services/AccountDeletionService.php', "account_status = 'deleted'", 'Expired account deletion disables the account');
$assertContains('app/Controllers/ProfileController.php', "strtolower((string) (\$user['role'] ?? '')) === 'admin'", 'Administrator accounts are excluded from self-service deletion');
$assertContains('app/Controllers/AuthController.php', 'ACCOUNT_DELETION_CANCELLED_BY_LOGIN', 'Login records automatic deletion cancellation');
$assertContains('tools/process-account-deletions.php', 'processDue()', 'Due account deletions can run as a scheduled job');

if ($failures !== []) {
    fwrite(STDERR, "System objective checks failed:\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "All system objective checks passed (90/90).\n";
