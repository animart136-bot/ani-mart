<?php

namespace App\Services;

use App\Core\Database;

class BadgeCalculationService
{
    /*
    |--------------------------------------------------------------------------
    | Badge Levels
    |--------------------------------------------------------------------------
    */

    const BADGE_NEW = 'new';
    const BADGE_VERIFIED = 'verified';
    const BADGE_TRUSTED = 'trusted';
    const BADGE_PREMIUM = 'premium';

    /*
    |--------------------------------------------------------------------------
    | Calculate Badge for a Seller
    |--------------------------------------------------------------------------
    */

    public function calculateBadge($sellerProfileId)
    {
        $db = Database::connection();

        // Get seller profile data
        $stmt = $db->prepare("
            SELECT 
                sp.*,
                u.id AS user_id
            FROM seller_profiles sp
            INNER JOIN users u ON u.id = sp.user_id
            WHERE sp.id = ?
            LIMIT 1
        ");

        $stmt->execute([$sellerProfileId]);
        $seller = $stmt->fetch();

        if (!$seller) {
            return self::BADGE_NEW;
        }

        // Check for Trusted Badge
        if ($this->isTrusted($sellerProfileId, $seller)) {
            return self::BADGE_TRUSTED;
        }

        // Check for Verified Badge
        if ($this->isVerified($seller)) {
            return self::BADGE_VERIFIED;
        }

        // Default to New
        return self::BADGE_NEW;
    }

    /*
    |--------------------------------------------------------------------------
    | Premium Badge Requirements
    |--------------------------------------------------------------------------
    */

    private function isPremium($sellerProfileId, $seller)
    {
        $db = Database::connection();

        // Trust Score ≥ 98
        if (($seller->trust_score ?? 0) < 98) {
            return false;
        }

        // At least 100 completed orders
        $stmt = $db->prepare("
            SELECT COUNT(DISTINCT o.id) AS total_orders
            FROM orders o
            INNER JOIN order_items oi ON oi.order_id = o.id
            WHERE oi.seller_profile_id = ?
            AND o.status IN ('completed', 'delivered')
        ");

        $stmt->execute([$sellerProfileId]);
        $orderCount = $stmt->fetch()->total_orders ?? 0;

        if ($orderCount < 100) {
            return false;
        }

        // Zero unresolved complaints
        $stmt = $db->prepare("
            SELECT COUNT(*) AS unresolved_complaints
            FROM complaints
            WHERE seller_profile_id = ?
            AND status IN ('pending', 'under_review', 'needs_more_evidence', 'waiting_farmer_response')
        ");

        $stmt->execute([$sellerProfileId]);
        $complaintCount = $stmt->fetch()->unresolved_complaints ?? 0;

        if ($complaintCount > 0) {
            return false;
        }

        // Verified harvest history (at least 10 products with harvest photos)
        $stmt = $db->prepare("
            SELECT COUNT(*) AS products_with_harvest
            FROM products
            WHERE seller_profile_id = ?
            AND harvest_photo IS NOT NULL
            AND harvest_photo != ''
            AND is_active = 1
        ");

        $stmt->execute([$sellerProfileId]);
        $harvestCount = $stmt->fetch()->products_with_harvest ?? 0;

        if ($harvestCount < 10) {
            return false;
        }

        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | Trusted Badge Requirements
    |--------------------------------------------------------------------------
    */

    private function isTrusted($sellerProfileId, $seller)
    {
        $db = Database::connection();

        // Trusted Seller requires a score above 80.
        if (($seller->trust_score ?? 0) <= 80) {
            return false;
        }

        // No unresolved complaints.
        $stmt = $db->prepare("
            SELECT COUNT(*) AS unresolved_complaints
            FROM complaints
            WHERE seller_profile_id = ?
            AND status IN ('pending', 'under_review', 'needs_more_evidence', 'waiting_farmer_response')
        ");

        $stmt->execute([$sellerProfileId]);
        $complaintCount = $stmt->fetch()->unresolved_complaints ?? 0;

        if ($complaintCount > 0) {
            return false;
        }

        // No unresolved trust-and-safety reports targeting the seller.
        $stmt = $db->prepare("
            SELECT COUNT(*) AS unresolved_reports
            FROM reports
            WHERE target_type = 'seller'
            AND target_id = ?
            AND status NOT IN ('resolved', 'rejected')
        ");

        $stmt->execute([$sellerProfileId]);
        $reportCount = $stmt->fetch()->unresolved_reports ?? 0;

        if ($reportCount > 0) {
            return false;
        }

        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | Verified Badge Requirements
    |--------------------------------------------------------------------------
    */

    private function isVerified($seller)
    {
        // The verified level represents a seller who submitted RSBSA information.
        // Official government verification remains separately recorded by rsbsa_verified.
        return ($seller->verification_badge ?? '') === self::BADGE_VERIFIED
            || !empty($seller->rsbsa_verified)
            || trim((string) ($seller->government_id ?? '')) !== ''
            || trim((string) ($seller->government_id_encrypted ?? '')) !== '';
    }

    /*
    |--------------------------------------------------------------------------
    | Recalculate All Badges
    |--------------------------------------------------------------------------
    */

    public function recalculateAll()
    {
        $db = Database::connection();

        $stmt = $db->query("SELECT id FROM seller_profiles");
        $sellers = $stmt->fetchAll();

        $updated = 0;

        foreach ($sellers as $seller) {
            $newBadge = $this->calculateBadge($seller->id);

            $updateStmt = $db->prepare("
                UPDATE seller_profiles
                SET verification_badge = ?
                WHERE id = ?
            ");

            $updateStmt->execute([$newBadge, $seller->id]);
            $updated++;
        }

        return $updated;
    }

    /*
    |--------------------------------------------------------------------------
    | Get Badge Distribution
    |--------------------------------------------------------------------------
    */

    public function getDistribution()
    {
        $db = Database::connection();

        $stmt = $db->query("
            SELECT 
                verification_badge,
                COUNT(*) AS count
            FROM seller_profiles
            GROUP BY verification_badge
        ");

        $results = $stmt->fetchAll();

        $distribution = [
            self::BADGE_NEW => 0,
            self::BADGE_VERIFIED => 0,
            self::BADGE_TRUSTED => 0
        ];

        foreach ($results as $row) {
            $badge = $row->verification_badge === self::BADGE_PREMIUM
                ? self::BADGE_TRUSTED
                : $row->verification_badge;

            if (array_key_exists($badge, $distribution)) {
                $distribution[$badge] += $row->count;
            }
        }

        return $distribution;
    }

    /*
    |--------------------------------------------------------------------------
    | Get Badge Label
    |--------------------------------------------------------------------------
    */

    public static function getBadgeLabel($badge)
    {
        $labels = [
            self::BADGE_NEW => 'New Seller · Building Trust',
            self::BADGE_VERIFIED => 'RSBSA ID Seller',
            self::BADGE_TRUSTED => 'Trusted Seller',
            self::BADGE_PREMIUM => 'Trusted Seller'
        ];

        return $labels[$badge] ?? 'Unknown';
    }

    /*
    |--------------------------------------------------------------------------
    | Get Badge Color Class
    |--------------------------------------------------------------------------
    */

    public static function getBadgeColor($badge)
    {
        $colors = [
            self::BADGE_NEW => 'bg-warning-subtle text-warning-emphasis',
            self::BADGE_VERIFIED => 'bg-success',
            self::BADGE_TRUSTED => 'bg-primary',
            self::BADGE_PREMIUM => 'bg-primary'
        ];

        return $colors[$badge] ?? 'bg-secondary';
    }

    /*
    |--------------------------------------------------------------------------
    | Get Badge Icon
    |--------------------------------------------------------------------------
    */

    public static function getBadgeIcon($badge)
    {
        $icons = [
            self::BADGE_NEW => 'bi-hourglass-split',
            self::BADGE_VERIFIED => 'bi-person-vcard-fill',
            self::BADGE_TRUSTED => 'bi-patch-check-fill',
            self::BADGE_PREMIUM => 'bi-patch-check-fill'
        ];

        return $icons[$badge] ?? 'bi-question-circle';
    }
}
