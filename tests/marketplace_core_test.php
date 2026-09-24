<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/app/Helpers/functions.php';

use App\Services\MarketplaceRulesService;
use App\Services\InventoryNotificationService;
use App\Services\ProductModerationService;
use App\Services\SellerApplicationRulesService;
use App\Services\SellerProfileService;
use App\Services\OrderHistoryReportService;
use App\Services\MarketplacePolicyService;
use App\Services\TrustScoreService;
use App\Services\BadgeCalculationService;

$failures = [];
$assert = static function (bool $condition, string $message) use (&$failures): void {
    if (!$condition) {
        $failures[] = $message;
    }
};

$assert(MarketplaceRulesService::hasSingleSeller([7, 7]), 'One repeated seller is accepted');
$assert(!MarketplaceRulesService::hasSingleSeller([7, 8]), 'Multiple sellers are rejected');
$assert(!MarketplaceRulesService::hasSingleSeller([]), 'An empty checkout is rejected');

$assert(MarketplaceRulesService::canonicalPaymentMethod('Cash on Delivery') === 'cash_on_delivery', 'COD is canonicalized');
$assert(MarketplaceRulesService::canonicalPaymentMethod('gcash') === null, 'Unavailable payment methods are rejected');

$assert(MarketplaceRulesService::canTransitionOrder('pending', 'accepted'), 'Pending orders can be accepted');
$assert(!MarketplaceRulesService::canTransitionOrder('pending', 'delivered'), 'Order states cannot be skipped');
$assert(!MarketplaceRulesService::canTransitionOrder('completed', 'preparing'), 'Completed orders cannot move backwards');
$assert(!MarketplaceRulesService::canTransitionOrder('pending', 'cancelled'), 'Cancellation uses the request and seller-decision workflow');
$assert(MarketplaceRulesService::canTransitionSellerOrder('pending', 'accepted'), 'Each seller can accept their own order portion');
$assert(MarketplaceRulesService::canTransitionSellerOrder('pending', 'cancelled'), 'Each seller can cancel their own pending order portion');
$assert(MarketplaceRulesService::canTransitionSellerOrder('accepted', 'cancelled'), 'Each seller can cancel their own accepted order portion');
$assert(!MarketplaceRulesService::canTransitionSellerOrder('preparing', 'cancelled'), 'A seller portion cannot be cancelled after preparation starts');
$assert(MarketplaceRulesService::canRequestCancellation('pending'), 'A pending order can be submitted for cancellation');
$assert(MarketplaceRulesService::canRequestCancellation('accepted'), 'An accepted order can be submitted for cancellation before preparation');
$assert(!MarketplaceRulesService::canRequestCancellation('preparing'), 'Cancellation requests close once preparation starts');
$assert(MarketplaceRulesService::canRequestReturn('delivered'), 'Delivered orders can enter the return workflow');
$assert(MarketplaceRulesService::canRequestReturn('completed'), 'Completed orders retain a return record path');
$assert(!MarketplaceRulesService::canRequestReturn('preparing'), 'Undelivered products cannot be returned');

$assert(
    SellerApplicationRulesService::rsbsaNameMatchStatus('Maria', 'Santos', 'Dela Cruz', 'Maria Santos Dela Cruz') === 'match',
    'An exact applicant and RSBSA-card name match qualifies for RSBSA recognition'
);
$assert(
    SellerApplicationRulesService::rsbsaNameMatchStatus('Maria', 'Santos', 'Dela Cruz', 'Maria Dela Cruz') === 'review',
    'A middle-name difference requires review rather than rejecting the applicant'
);
$assert(
    SellerApplicationRulesService::rsbsaNameMatchStatus('Maria', 'Santos', 'Dela Cruz', 'Ana Santos Reyes') === 'mismatch',
    'A different first or last name is flagged as an RSBSA mismatch'
);
$assert(InventoryNotificationService::levelFor(8) === 'healthy', 'Stock above the alert threshold is healthy');
$assert(InventoryNotificationService::levelFor(3) === 'low', 'Three remaining units trigger low-stock status');
$assert(InventoryNotificationService::levelFor(1) === 'low', 'Positive inventory below the threshold remains low stock');
$assert(InventoryNotificationService::levelFor(0) === 'out', 'Zero inventory triggers out-of-stock status');
$assert(SellerProfileService::calculateNetEarnings(1000, 80, 125) === 955.0, 'Net earnings include delivery fees and deduct actual refunds');
$assert(SellerProfileService::calculateNetEarnings(500, 0, 0) === 500.0, 'Gross sales remain net earnings when there are no adjustments');

$regularTrustApplication = (object) ['rsbsa_number' => null, 'rsbsa_card_path' => null];
$rsbsaTrustApplication = (object) [
    'first_name' => 'Maria',
    'middle_name' => 'Santos',
    'last_name' => 'Dela Cruz',
    'extracted_name' => 'Maria Santos Dela Cruz',
];
$submittedRsbsaTrustApplication = (object) ['rsbsa_number' => 'RSBSA-123'];
$assert(TrustScoreService::startingScore($regularTrustApplication) === 0, 'A regular seller starts with zero trust points');
$assert(TrustScoreService::startingScore($submittedRsbsaTrustApplication) === 40, 'A seller who submits an RSBSA ID starts with forty trust points');
$assert(TrustScoreService::startingBadge($regularTrustApplication) === 'new', 'A regular seller receives the building-trust badge');
$assert(TrustScoreService::startingScore($rsbsaTrustApplication) === 40, 'An RSBSA seller starts with forty trust points');
$assert(TrustScoreService::startingBadge($rsbsaTrustApplication) === 'verified', 'An RSBSA seller receives the RSBSA badge');
$assert(TrustScoreService::POSITIVE_ORDER_REWARD === 5, 'A qualifying positive order has a five-point trust reward');
$assert(BadgeCalculationService::getBadgeLabel('new') === 'New Seller · Building Trust', 'The zero-score badge explains that trust is still being built');
$assert(BadgeCalculationService::getBadgeLabel('verified') === 'RSBSA ID Seller', 'The RSBSA badge clearly identifies the seller type');

$reportOrder = (object) [
    'id' => 1,
    'order_number' => 'ORD-TEST-1',
    'created_at' => '2026-09-08 10:00:00',
    'status' => 'completed',
    'payment_method' => 'cash_on_delivery',
    'delivery_fee' => 25,
    'total_amount' => 125,
    'items' => [(object) ['name' => 'Tomatoes', 'shop_name' => 'Green Farm', 'quantity' => 2, 'price' => 50]],
];
$reportFilters = ['seller' => 'Green Farm', 'product' => 'Tomatoes', 'status' => 'completed', 'date_from' => '', 'date_to' => ''];
$csvReport = OrderHistoryReportService::csv([$reportOrder], $reportFilters, 'Test Customer');
$pdfReport = OrderHistoryReportService::pdf([$reportOrder], $reportFilters, 'Test Customer');
$assert(str_contains($csvReport, 'ORD-TEST-1') && str_contains($csvReport, 'Green Farm'), 'CSV order history contains the filtered order details');
$assert(str_starts_with($pdfReport, '%PDF-1.4') && str_contains($pdfReport, 'Order History Report'), 'PDF order history produces a valid report document');

$policies = MarketplacePolicyService::all();
$assert(count($policies) === 5, 'The policy center publishes all five marketplace policies');
foreach (['terms', 'privacy', 'seller', 'prohibited-products', 'cancellation-returns'] as $policySlug) {
    $assert(MarketplacePolicyService::find($policySlug) !== null, "The {$policySlug} policy can be opened");
}
$assert(MarketplacePolicyService::find('unknown-policy') === null, 'Unknown policy pages return no document');
$assert(count($policies['privacy']['sources'] ?? []) >= 2, 'The privacy policy includes official privacy references');
$termsSections = json_encode($policies['terms']['sections'] ?? []);
$assert(is_string($termsSections) && str_contains($termsSections, 'Cash on Delivery (COD)'), 'The terms publish a dedicated COD section');
$assert(is_string($termsSections) && str_contains($termsSections, 'does not waive applicable cancellation, return, refund'), 'The COD terms preserve post-delivery consumer rights');
$cancellationSections = json_encode($policies['cancellation-returns']['sections'] ?? []);
$assert(is_string($cancellationSections) && str_contains($cancellationSections, 'COD orders still require an actual refund record'), 'The return policy documents COD refund records');

$regularSeller = SellerApplicationRulesService::verificationTrack(null, null);
$assert($regularSeller['application_status'] === 'pending', 'A seller can apply without RSBSA information');
$assert($regularSeller['rsbsa_verification_status'] === 'not_submitted', 'A regular seller is not presented as RSBSA verified');
$documentedSeller = SellerApplicationRulesService::verificationTrack('https://example.test/card.jpg', 'RSBSA-123');
$assert($documentedSeller['application_status'] === 'pending', 'Optional RSBSA information does not block seller review');
$assert($documentedSeller['rsbsa_verification_status'] === 'submitted', 'Optional RSBSA information is recorded as submitted evidence');

$newSellerSignals = ProductModerationService::evaluateSignals([
    'approved_products' => 0,
    'completed_orders' => 0,
    'is_suspended' => false,
    'serious_unresolved_complaints' => 0,
    'review_count' => 0,
    'average_rating' => 0,
]);
$normalPriceCheck = ['reference_available' => true, 'assessment' => 'NORMAL', 'requires_warning' => false];
$assert(!$newSellerSignals['established'], 'A new seller starts in the guided moderation period');
$assert(ProductModerationService::publicationDecision($newSellerSignals, $normalPriceCheck)['status'] === 'approved', 'A normal reference-matched price is automatically approved for a new seller');

$establishedSignals = ProductModerationService::evaluateSignals([
    'approved_products' => 3,
    'completed_orders' => 5,
    'is_suspended' => false,
    'serious_unresolved_complaints' => 0,
    'review_count' => 2,
    'average_rating' => 4.5,
]);
$assert($establishedSignals['established'], 'A trustworthy seller can become established');
$assert(ProductModerationService::publicationDecision($establishedSignals, $normalPriceCheck)['status'] === 'approved', 'A low-risk established seller product can publish immediately');
$highPriceCheck = ['reference_available' => true, 'assessment' => 'HIGH_DEVIATION', 'requires_warning' => true];
$assert(ProductModerationService::publicationDecision($establishedSignals, $highPriceCheck, true, 'approved')['status'] === 'under_review', 'A suspicious price change is reviewed without rejecting the seller-selected price');
$assert(ProductModerationService::displayStatus('approved', 0) === 'Out of Stock', 'An approved zero-stock listing is retained as out of stock');

$numbers = [];
for ($i = 0; $i < 250; $i++) {
    $number = MarketplaceRulesService::orderNumber(new DateTimeImmutable('2026-09-05 12:00:00'));
    $assert((bool) preg_match('/^ORD-\d{8}-\d{6}-[A-F0-9]{10}$/', $number), 'Order number format is valid');
    $numbers[$number] = true;
}
$assert(count($numbers) === 250, 'Order numbers generated in the same second are unique');

$db = new PDO('sqlite::memory:');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->exec('CREATE TABLE variants (id INTEGER PRIMARY KEY, price NUMERIC NOT NULL, stock INTEGER NOT NULL)');
$db->exec('CREATE TABLE order_items (variant_id INTEGER, quantity INTEGER, unit_price NUMERIC, subtotal NUMERIC)');
$db->exec('INSERT INTO variants (id, price, stock) VALUES (1, 42.50, 3)');

$db->beginTransaction();
$variant = $db->query('SELECT id, price, stock FROM variants WHERE id = 1')->fetch(PDO::FETCH_OBJ);
$quantity = 2;
$assert($variant !== false && (int) $variant->stock >= $quantity, 'Variant has sufficient inventory');
$insert = $db->prepare('INSERT INTO order_items VALUES (?, ?, ?, ?)');
$insert->execute([$variant->id, $quantity, $variant->price, $variant->price * $quantity]);
$update = $db->prepare('UPDATE variants SET stock = stock - ? WHERE id = ? AND stock >= ?');
$update->execute([$quantity, $variant->id, $quantity]);
$db->commit();

$remaining = (int) $db->query('SELECT stock FROM variants WHERE id = 1')->fetchColumn();
$snapshot = $db->query('SELECT unit_price, subtotal FROM order_items')->fetch(PDO::FETCH_OBJ);
$assert($remaining === 1, 'Variant stock is decremented exactly');
$assert(abs((float) $snapshot->unit_price - 42.5) < 0.001, 'Order item keeps its price snapshot');
$assert(abs((float) $snapshot->subtotal - 85.0) < 0.001, 'Order item subtotal is accurate');

if ($failures !== []) {
    fwrite(STDERR, "Marketplace core tests failed:\n- " . implode("\n- ", array_unique($failures)) . "\n");
    exit(1);
}

echo "Marketplace core behavior checks passed.\n";
