<?php

use App\Controllers\HomeController;
use App\Controllers\AuthController;

use App\Controllers\DashboardController;
use App\Controllers\ProfileController;

use App\Controllers\AdminController;
use App\Controllers\FarmerController;
use App\Controllers\CustomerController;

use App\Controllers\MarketplaceController;
use App\Controllers\ProductController;

use App\Controllers\CartController;
use App\Controllers\CheckoutController;
use App\Controllers\OrderController;
use App\Controllers\ReturnController;

use App\Controllers\NotificationController;
use App\Controllers\MessageController;
use App\Controllers\VerificationController;
use App\Controllers\FarmProfileController;
use App\Controllers\SellerCenterController;
use App\Controllers\CommunityController;
use App\Controllers\CommunityPostController;
use App\Controllers\CommunityCommentController;
use App\Controllers\CommunityCommentLikeController;
use App\Controllers\CommunityLikeController;
use App\Controllers\CommunitySaveController;
use App\Controllers\CommunityShareController;
use App\Controllers\CommunityReportController;
use App\Controllers\CommunityAdminController;
use App\Controllers\ComplaintController;
use App\Controllers\ComplaintReplyController;
use App\Controllers\ComplaintAdminController;
use App\Controllers\TrustSafetyController;
use App\Controllers\ReportController;
use App\Controllers\ReportComplaintController;
use App\Controllers\ModerationController;
use App\Controllers\AppealController;
use App\Controllers\AdminAuditController;
use App\Controllers\PolicyController;
use App\Controllers\AdminControlController;
use App\Controllers\MediaController;
use App\Controllers\MediaAdminController;

/*
|--------------------------------------------------------------------------
| HOME
|--------------------------------------------------------------------------
*/

$router->get('/', [HomeController::class, 'index']);
$router->get('/policies', [PolicyController::class, 'index']);
$router->get('/policies/{slug}', [PolicyController::class, 'show']);

/*
|--------------------------------------------------------------------------
| AUTH
|--------------------------------------------------------------------------
*/

$router->get('/register', [AuthController::class, 'register']);
$router->post('/register', [AuthController::class, 'store']);
$router->get('/register/verify', [AuthController::class, 'verifyRegistration']);
$router->post('/register/verify', [AuthController::class, 'confirmRegistrationOtp']);
$router->post('/register/resend-otp', [AuthController::class, 'resendRegistrationOtp']);

$router->get('/login', [AuthController::class, 'login']);
$router->post('/login', [AuthController::class, 'authenticate']);
$router->get('/password/forgot', [AuthController::class, 'forgotPassword']);
$router->post('/password/forgot', [AuthController::class, 'sendPasswordReset']);
$router->get('/password/reset', [AuthController::class, 'resetPassword']);
$router->post('/password/reset', [AuthController::class, 'updatePasswordFromReset']);
$router->get('/login/admin-verify', [AuthController::class, 'verifyAdminLogin']);
$router->post('/login/admin-verify', [AuthController::class, 'confirmAdminLogin']);
$router->post('/login/admin-resend', [AuthController::class, 'resendAdminLoginCode']);

$router->get('/logout', [AuthController::class, 'logout']);

/*
|--------------------------------------------------------------------------
| DASHBOARDS
|--------------------------------------------------------------------------
*/

$router->get('/dashboard', [DashboardController::class, 'index']);

// Authenticated, short-lived delivery for protected evidence and identity images.
$router->get('/media/assets/{id}', [MediaController::class, 'show']);
$router->post('/admin/media/delete', [MediaAdminController::class, 'destroy']);

/*
|--------------------------------------------------------------------------
| PROFILE
|--------------------------------------------------------------------------
*/

$router->get('/profile', [ProfileController::class, 'edit']);
$router->post('/profile', [ProfileController::class, 'update']);
$router->post('/profile/delete-account', [ProfileController::class, 'requestAccountDeletion']);

$router->get('/admin/profile', [ProfileController::class, 'edit']);
$router->post('/admin/profile', [ProfileController::class, 'update']);
$router->post('/profile/admin-login-protection', [ProfileController::class, 'updateAdminLoginProtection']);

$router->post('/seller-center/shop/profile', [FarmProfileController::class, 'update']);
$router->post('/seller-center/shop/gallery/delete', [FarmProfileController::class, 'deleteImage']);

$router->get('/customer/profile', [ProfileController::class, 'edit']);
$router->post('/customer/profile', [ProfileController::class, 'update']);

/*
|--------------------------------------------------------------------------
| FARMER APPLICATION
|--------------------------------------------------------------------------
*/

$router->get('/farmer/apply', [FarmerController::class, 'create']);
$router->post('/farmer/apply', [FarmerController::class, 'store']);

/*
|--------------------------------------------------------------------------
| SELLER CENTRE
|--------------------------------------------------------------------------
*/

$router->get('/seller-center', [SellerCenterController::class, 'index']);
$router->get('/seller-center/shops', [SellerCenterController::class, 'shops']);
$router->get('/seller-center/shops/create', [SellerCenterController::class, 'create']);
$router->post('/seller-center/shops/store', [SellerCenterController::class, 'store']);
$router->post('/seller-center/switch', [SellerCenterController::class, 'switch']);
$router->get('/seller-center/analytics', [SellerCenterController::class, 'analytics']);
$router->get('/seller-center/analytics/download', [SellerCenterController::class, 'downloadFinancialReport']);
$router->redirect('/farmer/analytics', '/seller-center/analytics');
$router->redirect('/farmer/analytics/download', '/seller-center/analytics/download');
$router->get('/seller-center/reviews', [SellerCenterController::class, 'reviews']);
$router->get('/seller-center/shop/customize', [SellerCenterController::class, 'customize']);
$router->post('/seller-center/shop/customize', [SellerCenterController::class, 'updateCustomization']);

/*
|--------------------------------------------------------------------------
| ADMIN
|--------------------------------------------------------------------------
*/

$router->get('/admin/applications', [AdminController::class, 'applications']);
$router->get('/admin/reference-data/prices', [AdminController::class, 'priceReferenceData']);
$router->post('/admin/reference-data/prices/store', [AdminController::class, 'storePriceReferenceData']);
$router->post('/admin/reference-data/prices/update', [AdminController::class, 'updatePriceReferenceData']);
$router->post('/admin/reference-data/prices/delete', [AdminController::class, 'deletePriceReferenceData']);

$router->get('/admin/categories', [AdminControlController::class, 'categories']);
$router->post('/admin/categories/save', [AdminControlController::class, 'saveCategory']);
$router->post('/admin/categories/status', [AdminControlController::class, 'setCategoryStatus']);

$router->get('/admin/users', [AdminControlController::class, 'users']);
$router->post('/admin/users/status', [AdminControlController::class, 'setUserStatus']);

$router->post('/admin/applications/approve', [AdminController::class, 'approve']);

$router->post('/admin/applications/reject', [AdminController::class, 'reject']);

$router->post('/admin/applications/reverify', [AdminController::class, 'reverify']);


$router->get('/admin/farmers', [AdminController::class, 'farmers']);

$router->get('/admin/products', [ProductController::class, 'adminIndex']);
$router->get('/admin/products/view', [ProductController::class, 'adminView']);
$router->post('/admin/products/approve', [ProductController::class, 'adminApprove']);
$router->post('/admin/products/request-correction', [ProductController::class, 'adminRequestCorrection']);
$router->post('/admin/products/reject', [ProductController::class, 'adminReject']);
$router->post('/farmer/products/price-check', [ProductController::class, 'smartPriceCheck']);

$router->get('/admin/analytics', [AdminController::class, 'analytics']);

$router->get('/admin/audit-logs', [AdminAuditController::class, 'index']);

$router->get('/admin/risk-monitoring', [AdminController::class, 'riskMonitoring']);

/*
|--------------------------------------------------------------------------
| VERIFICATION BADGES
|--------------------------------------------------------------------------
*/

$router->get('/admin/verifications', [VerificationController::class, 'index']);

$router->get('/admin/verifications/view', [VerificationController::class, 'viewSeller']);

$router->post('/admin/verifications/approve-shop', [VerificationController::class, 'approveShop']);

$router->post('/admin/verifications/promote', [VerificationController::class, 'promote']);

$router->post('/admin/verifications/demote', [VerificationController::class, 'demote']);

$router->post('/admin/verifications/recalculate', [VerificationController::class, 'recalculate']);

$router->post('/admin/verifications/recalculate-single', [VerificationController::class, 'recalculateSingle']);

/*
|--------------------------------------------------------------------------
| PRODUCTS
|--------------------------------------------------------------------------
*/

$router->get('/products', [ProductController::class, 'index']);

/*
|--------------------------------------------------------------------------
| FARMER PRODUCTS
|--------------------------------------------------------------------------
*/

$router->get('/farmer/products', [ProductController::class, 'index']);

$router->get('/farmer/products/create', [ProductController::class, 'create']);

$router->post('/farmer/products/store', [ProductController::class, 'store']);

$router->get('/farmer/products/edit', [ProductController::class, 'edit']);

$router->post('/farmer/products/update', [ProductController::class, 'update']);

$router->post('/farmer/products/image/delete', [ProductController::class, 'deleteImage']);
$router->post('/farmer/products/image/replace', [ProductController::class, 'replaceImage']);
$router->post('/farmer/products/image/cover', [ProductController::class, 'setImageCover']);

$router->post('/farmer/products/delete', [ProductController::class, 'delete']);
$router->post('/admin/products/delete', [ProductController::class, 'destroy']);

/*
|--------------------------------------------------------------------------
| FARMER ORDERS
|--------------------------------------------------------------------------
*/

$router->get('/farmer/orders', [FarmerController::class, 'orders']);

$router->post('/farmer/orders/status', [OrderController::class, 'updateOrderStatus']);

$router->post('/farmer/orders/cancellation/respond', [OrderController::class, 'respondToCancellation']);

$router->post('/farmer/orders/upload-proof', [OrderController::class, 'uploadProof']);

$router->get(
    '/orders',
    [CustomerController::class, 'orders']
);

/*
|--------------------------------------------------------------------------
| MARKETPLACE
|--------------------------------------------------------------------------
*/

$router->get('/marketplace', [MarketplaceController::class, 'index']);
$router->get('/marketplace/stock', [MarketplaceController::class, 'stockSnapshot']);

/*
|--------------------------------------------------------------------------
| COMMUNITY HUB
|--------------------------------------------------------------------------
*/

$router->get('/community', [CommunityController::class, 'index']);
$router->get('/community/show', [CommunityController::class, 'show']);
$router->get('/community/create', [CommunityController::class, 'create']);
$router->get('/community/posts/edit', [CommunityPostController::class, 'edit']);
$router->redirect('/community/edit', '/community/posts/edit');
$router->get('/community/saved', [CommunityController::class, 'saved']);

$router->post('/community/posts/store', [CommunityPostController::class, 'store']);
$router->post('/community/posts/update', [CommunityPostController::class, 'update']);
$router->post('/community/posts/delete', [CommunityPostController::class, 'delete']);

$router->post('/community/comments/store', [CommunityCommentController::class, 'store']);
$router->post('/community/comments/update', [CommunityCommentController::class, 'update']);
$router->post('/community/comments/delete', [CommunityCommentController::class, 'delete']);
$router->post('/community/comments/likes/toggle', [CommunityCommentLikeController::class, 'toggle']);

$router->post('/community/likes/toggle', [CommunityLikeController::class, 'toggle']);
$router->post('/community/saves/toggle', [CommunitySaveController::class, 'toggle']);
$router->post('/community/shares/create', [CommunityShareController::class, 'create']);
$router->post('/community/reports/store', [CommunityReportController::class, 'store']);

$router->get('/community/reports', [CommunityAdminController::class, 'reports']);
$router->post('/community/reports/moderate', [CommunityAdminController::class, 'moderate']);

/*
|--------------------------------------------------------------------------
| PUBLIC FARM PROFILE
|--------------------------------------------------------------------------
*/

$router->get('/farm/{slug}', [FarmProfileController::class, 'show']);
$router->post('/farm/{slug}/follow', [FarmProfileController::class, 'follow']);
$router->post('/farm/{slug}/unfollow', [FarmProfileController::class, 'unfollow']);

/*
|--------------------------------------------------------------------------
| CART
|--------------------------------------------------------------------------
*/

$router->get('/cart', [CartController::class, 'index']);

$router->post('/cart/add', [CartController::class, 'add']);

$router->post('/cart/remove', [CartController::class, 'remove']);

$router->post('/cart/update', [CartController::class, 'updateQuantity']);

$router->post('/cart/checkout', [CartController::class, 'checkout']);

/*
|--------------------------------------------------------------------------
| CHECKOUT
|--------------------------------------------------------------------------
*/

$router->get('/checkout', [CheckoutController::class, 'index']);

$router->post('/checkout/process', [CheckoutController::class, 'process']);

/*
|--------------------------------------------------------------------------
| CUSTOMER ORDERS
|--------------------------------------------------------------------------
*/

$router->get('/customer/orders', [CustomerController::class, 'orders']);
$router->get('/customer/orders/export/csv', [CustomerController::class, 'exportOrderHistoryCsv']);
$router->get('/customer/orders/export/pdf', [CustomerController::class, 'exportOrderHistoryPdf']);

$router->post('/customer/orders/confirm', [CustomerController::class, 'confirmOrder']);

$router->post('/customer/orders/cancellation/request', [OrderController::class, 'requestCancellation']);

$router->post('/customer/orders/review', [CustomerController::class, 'storeReview']);
$router->post('/customer/orders/review/update', [CustomerController::class, 'updateReview']);

$router->get('/customer/returns/create', [ReturnController::class, 'create']);
$router->post('/customer/returns/store', [ReturnController::class, 'store']);
$router->get('/customer/returns/show', [ReturnController::class, 'customerShow']);
$router->get('/farmer/returns/show', [ReturnController::class, 'farmerShow']);
$router->post('/farmer/returns/decision', [ReturnController::class, 'decide']);
$router->post('/farmer/returns/resolve', [ReturnController::class, 'resolve']);

$router->get('/reviews', [CustomerController::class, 'reviews']);
$router->get('/reviews/edit', [CustomerController::class, 'editReview']);

$router->get('/orders/receipt', [OrderController::class, 'receipt']);
$router->get('/orders/receipt/download', [OrderController::class, 'downloadReceipt']);

/*
|--------------------------------------------------------------------------
| CUSTOMER COMPLAINTS
|--------------------------------------------------------------------------
*/

$router->get('/customer/complaints', [ComplaintController::class, 'customerIndex']);
$router->get('/customer/complaints/create', [ComplaintController::class, 'customerCreate']);
$router->post('/customer/complaints/store', [ComplaintController::class, 'customerStore']);
$router->get('/customer/complaints/show', [ComplaintController::class, 'customerShow']);

/*
|--------------------------------------------------------------------------
| FARMER COMPLAINTS
|--------------------------------------------------------------------------
*/

$router->get('/farmer/complaints', [ComplaintController::class, 'farmerIndex']);
$router->get('/farmer/complaints/show', [ComplaintController::class, 'farmerShow']);

/*
|--------------------------------------------------------------------------
| ADMIN COMPLAINTS
|--------------------------------------------------------------------------
*/

$router->get('/admin/complaints', [ComplaintAdminController::class, 'index']);
$router->get('/admin/complaints/show', [ComplaintAdminController::class, 'show']);
$router->post('/admin/complaints/action', [ComplaintAdminController::class, 'action']);
$router->post('/admin/complaints/reopen', [ComplaintAdminController::class, 'reopen']);

/*
|--------------------------------------------------------------------------
| TRUST & SAFETY CENTER
|--------------------------------------------------------------------------
*/

$router->get('/admin/trust-center', [TrustSafetyController::class, 'dashboard']);
$router->get('/admin/trust-center/reports', [ModerationController::class, 'index']);
$router->get('/admin/trust-center/reports/show', [ModerationController::class, 'show']);
$router->post('/admin/trust-center/reports/action', [ModerationController::class, 'action']);

$router->get('/reports/create', [ReportController::class, 'create']);
$router->redirect('/trust-center/reports/create', '/reports/create');
$router->get('/reports-complaints', [ReportComplaintController::class, 'index']);
$router->post('/reports/store', [ReportController::class, 'store']);

$router->get('/trust-center/appeals', [AppealController::class, 'index']);
$router->post('/trust-center/appeals/store', [AppealController::class, 'store']);

$router->get('/admin/trust-center/appeals', [AppealController::class, 'adminIndex']);
$router->post('/admin/trust-center/appeals/update', [AppealController::class, 'adminUpdate']);

/*
|--------------------------------------------------------------------------
| COMPLAINT REPLIES
|--------------------------------------------------------------------------
*/

$router->post('/complaints/replies/store', [ComplaintReplyController::class, 'store']);

/*
|--------------------------------------------------------------------------
| NOTIFICATIONS
|--------------------------------------------------------------------------
*/

$router->get('/notifications', [NotificationController::class, 'index']);

$router->get('/notifications/fetch', [NotificationController::class, 'fetch']);
$router->get('/notifications/redirect', [NotificationController::class, 'redirect']);

$router->post('/notifications/read', [NotificationController::class, 'read']);

$router->post('/notifications/mark-all-read', [NotificationController::class, 'markAllRead']);

$router->get('/messages', [MessageController::class, 'index']);
$router->get('/messages/chat', [MessageController::class, 'chat']);
$router->post('/messages/chat/settings', [MessageController::class, 'saveConversationSettings']);
$router->get('/messages/start', [MessageController::class, 'start']);
$router->get('/messages/unread', [MessageController::class, 'unread']);
$router->post('/messages/send', [MessageController::class, 'send']);
$router->post('/messages/conversation/delete', [MessageController::class, 'deleteConversation']);
$router->post('/messages/upload', [MessageController::class, 'upload']);
$router->post('/messages/archive', [MessageController::class, 'archive']);
$router->get('/messages/search', [MessageController::class, 'search']);
$router->post('/messages/read', [MessageController::class, 'markRead']);


$router->get(
    '/admin/transparency',
    [AdminController::class, 'transparency']
);
