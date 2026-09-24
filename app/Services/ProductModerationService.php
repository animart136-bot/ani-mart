<?php

namespace App\Services;

use App\Core\Database;

class ProductModerationService
{
    public const MIN_APPROVED_PRODUCTS = 3;
    public const MIN_COMPLETED_ORDERS = 5;
    public const MIN_ACCEPTABLE_RATING = 3.5;

    public function sellerSignals(int $sellerProfileId): array
    {
        $db = Database::connection();

        $seller = $db->prepare('SELECT status, is_suspended FROM seller_profiles WHERE id = ? LIMIT 1');
        $seller->execute([$sellerProfileId]);
        $profile = $seller->fetch();

        $approved = $db->prepare("SELECT COUNT(*) FROM products WHERE seller_profile_id = ? AND approval_status = 'approved'");
        $approved->execute([$sellerProfileId]);

        $orders = $db->prepare("SELECT COUNT(DISTINCT o.id) FROM orders o INNER JOIN order_items oi ON oi.order_id = o.id WHERE oi.seller_profile_id = ? AND o.status IN ('delivered', 'completed')");
        $orders->execute([$sellerProfileId]);

        $complaints = $db->prepare("SELECT COUNT(*) FROM complaints WHERE seller_profile_id = ? AND priority = 'high' AND status NOT IN ('resolved', 'rejected', 'closed')");
        $complaints->execute([$sellerProfileId]);

        $ratings = $db->prepare('SELECT COUNT(r.id) AS review_count, COALESCE(AVG(r.rating), 0) AS average_rating FROM reviews r INNER JOIN products p ON p.id = r.product_id WHERE p.seller_profile_id = ?');
        $ratings->execute([$sellerProfileId]);
        $rating = $ratings->fetch() ?: (object) ['review_count' => 0, 'average_rating' => 0];

        return self::evaluateSignals([
            'approved_products' => (int) $approved->fetchColumn(),
            'completed_orders' => (int) $orders->fetchColumn(),
            'is_suspended' => !$profile || !empty($profile->is_suspended) || ($profile->status ?? '') !== 'approved',
            'serious_unresolved_complaints' => (int) $complaints->fetchColumn(),
            'review_count' => (int) $rating->review_count,
            'average_rating' => (float) $rating->average_rating,
        ]);
    }

    public static function evaluateSignals(array $signals): array
    {
        $approvedProducts = (int) ($signals['approved_products'] ?? 0);
        $completedOrders = (int) ($signals['completed_orders'] ?? 0);
        $reviewCount = (int) ($signals['review_count'] ?? 0);
        $averageRating = (float) ($signals['average_rating'] ?? 0);
        $ratingAcceptable = $reviewCount === 0 || $averageRating >= self::MIN_ACCEPTABLE_RATING;

        $checks = [
            'approved_products' => $approvedProducts >= self::MIN_APPROVED_PRODUCTS,
            'completed_orders' => $completedOrders >= self::MIN_COMPLETED_ORDERS,
            'not_suspended' => empty($signals['is_suspended']),
            'no_serious_unresolved_complaints' => (int) ($signals['serious_unresolved_complaints'] ?? 0) === 0,
            'acceptable_rating' => $ratingAcceptable,
        ];

        return array_merge($signals, [
            'approved_products' => $approvedProducts,
            'completed_orders' => $completedOrders,
            'review_count' => $reviewCount,
            'average_rating' => $averageRating,
            'checks' => $checks,
            'established' => !in_array(false, $checks, true),
        ]);
    }

    public static function publicationDecision(
        array $sellerSignals,
        array $priceCheck,
        bool $isMaterialUpdate = false,
        string $previousStatus = 'pending'
    ): array {
        if ($previousStatus === 'removed') {
            return ['status' => 'removed', 'reason' => 'A removed product requires an administrator decision before it can return.'];
        }

        if (in_array($previousStatus, ['needs_correction', 'rejected'], true)) {
            return ['status' => 'pending_admin_review', 'reason' => 'The corrected listing was resubmitted for administrator review.'];
        }

        if (!empty($sellerSignals['is_suspended']) || empty($sellerSignals['checks']['not_suspended'])) {
            return ['status' => 'pending_admin_review', 'reason' => 'This seller account is not eligible for automatic publishing and requires administrator review.'];
        }

        $referenceAvailable = !empty($priceCheck['reference_available']);
        $assessment = strtoupper((string) ($priceCheck['assessment'] ?? 'REFERENCE_UNAVAILABLE'));
        $priceRisk = !empty($priceCheck['requires_warning']) || !$referenceAvailable || $assessment !== 'NORMAL';

        if (!$priceRisk) {
            return ['status' => 'approved', 'reason' => 'Smart Price Check confirmed that the listing price meets the available market reference, so the product was automatically approved.'];
        }

        return [
            'status' => $isMaterialUpdate && $previousStatus === 'approved' ? 'under_review' : 'pending_admin_review',
            'reason' => !$referenceAvailable
                ? 'Smart Price Check could not find a suitable reference, so an administrator must review the listing.'
                : 'Smart Price Check detected an unusual price that requires administrator review.',
        ];

    }

    public static function displayStatus(string $approvalStatus, int $stock): string
    {
        if ($approvalStatus === 'approved' && $stock <= 0) {
            return 'Out of Stock';
        }

        return [
            'draft' => 'Draft',
            'pending' => 'Pending Review',
            'pending_admin_review' => 'Pending Review',
            'approved' => 'Active',
            'needs_correction' => 'Needs Correction',
            'rejected' => 'Rejected',
            'under_review' => 'Under Review',
            'removed' => 'Removed',
            'superseded' => 'Replaced by New Submission',
        ][$approvalStatus] ?? 'Pending Review';
    }
}
