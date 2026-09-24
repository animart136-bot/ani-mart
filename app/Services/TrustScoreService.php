<?php

namespace App\Services;

use App\Core\Database;

class TrustScoreService
{
    public const STANDARD_SELLER_START = 0;
    public const RSBSA_SELLER_START = 40;
    public const POSITIVE_REVIEW_MINIMUM = 4;
    public const POSITIVE_ORDER_REWARD = 5;
    public const NEGATIVE_REVIEW_MAXIMUM = 3;
    public const NEGATIVE_ORDER_PENALTY = 5;
    public const VALIDATED_REPORT_PENALTY = 5;

    /**
     * RSBSA information earns the seller's starting trust points as soon as
     * it is submitted. The public verification badge remains subject to the
     * stricter identity-match check in applicationHasRsbsa().
     */
    public static function applicationSubmittedRsbsa(object $application): bool
    {
        return trim((string) sensitive_value(
            $application,
            'rsbsa_number',
            'farmer_applications.rsbsa_number',
            $application->rsbsa_number ?? null
        )) !== ''
            || trim((string) sensitive_value(
                $application,
                'rsbsa_card_path',
                'farmer_applications.rsbsa_card_path',
                $application->rsbsa_card_path ?? null
            )) !== ''
            || in_array((string) ($application->rsbsa_verification_status ?? ''), ['submitted', 'verified'], true);
    }

    public static function applicationHasRsbsa(object $application): bool
    {
        // An administrator's official verification can resolve a documented
        // discrepancy. Otherwise, only a full name match can grant RSBSA
        // recognition; submitting a card alone is not enough.
        if (($application->rsbsa_verification_status ?? '') === 'verified') {
            return true;
        }

        return SellerApplicationRulesService::rsbsaNameMatchStatus(
            sensitive_value($application, 'first_name', 'farmer_applications.first_name', $application->first_name ?? null),
            sensitive_value($application, 'middle_name', 'farmer_applications.middle_name', $application->middle_name ?? null),
            sensitive_value($application, 'last_name', 'farmer_applications.last_name', $application->last_name ?? null),
            sensitive_value($application, 'extracted_name', 'farmer_applications.extracted_name', $application->extracted_name ?? null)
        ) === 'match';
    }

    public static function startingScore(object $application): int
    {
        return self::applicationSubmittedRsbsa($application) || self::applicationHasRsbsa($application)
            ? self::RSBSA_SELLER_START
            : self::STANDARD_SELLER_START;
    }

    public static function startingBadge(object $application): string
    {
        return self::applicationHasRsbsa($application) ? 'verified' : 'new';
    }

    /**
     * Award one trust increase per completed shop order, even when that order
     * contains several reviewed products. Re-running this method is safe.
     */
    public function syncPositiveReviewReward(int $reviewId): void
    {
        if ($reviewId <= 0) {
            return;
        }

        $db = Database::connection();
        $this->ensureRewardTable($db);

        $review = $db->prepare("SELECT oi.order_id, oi.seller_profile_id
            FROM reviews r
            INNER JOIN order_items oi ON oi.id = r.order_item_id
            WHERE r.id = ?
            LIMIT 1");
        $review->execute([$reviewId]);
        $context = $review->fetch();

        if (!$context) {
            return;
        }

        $orderId = (int) $context->order_id;
        $sellerProfileId = (int) $context->seller_profile_id;
        $changed = false;

        $db->beginTransaction();

        try {
            $sellerLock = $db->prepare('SELECT id FROM seller_profiles WHERE id = ? FOR UPDATE');
            $sellerLock->execute([$sellerProfileId]);

            if (!$sellerLock->fetch()) {
                $db->rollBack();
                return;
            }

            $eligible = $this->orderQualifies($db, $orderId, $sellerProfileId);
            $negativeReview = $this->orderHasNegativeReview($db, $orderId, $sellerProfileId);
            $reward = $db->prepare("SELECT id
                FROM seller_trust_rewards
                WHERE order_id = ? AND seller_profile_id = ? AND reward_type = 'positive_review'
                LIMIT 1
                FOR UPDATE");
            $reward->execute([$orderId, $sellerProfileId]);
            $existingReward = $reward->fetch();

            if ($eligible && !$existingReward) {
                $insert = $db->prepare("INSERT INTO seller_trust_rewards
                    (order_id, seller_profile_id, reward_type, points, created_at)
                    VALUES (?, ?, 'positive_review', ?, NOW())");
                $insert->execute([$orderId, $sellerProfileId, self::POSITIVE_ORDER_REWARD]);
                $db->prepare('UPDATE seller_profiles SET trust_score = LEAST(100, COALESCE(trust_score, 0) + ?) WHERE id = ?')
                    ->execute([self::POSITIVE_ORDER_REWARD, $sellerProfileId]);
                $changed = true;
            } elseif (!$eligible && $existingReward) {
                $db->prepare('DELETE FROM seller_trust_rewards WHERE id = ?')
                    ->execute([(int) $existingReward->id]);
                $db->prepare('UPDATE seller_profiles SET trust_score = GREATEST(0, COALESCE(trust_score, 0) - ?) WHERE id = ?')
                    ->execute([self::POSITIVE_ORDER_REWARD, $sellerProfileId]);
                $changed = true;
            }

            $penalty = $db->prepare("SELECT id
                FROM seller_trust_rewards
                WHERE order_id = ? AND seller_profile_id = ? AND reward_type = 'negative_review'
                LIMIT 1
                FOR UPDATE");
            $penalty->execute([$orderId, $sellerProfileId]);
            $existingPenalty = $penalty->fetch();

            if ($negativeReview && !$existingPenalty) {
                $insert = $db->prepare("INSERT INTO seller_trust_rewards
                    (order_id, seller_profile_id, reward_type, points, created_at)
                    VALUES (?, ?, 'negative_review', ?, NOW())");
                $insert->execute([$orderId, $sellerProfileId, self::NEGATIVE_ORDER_PENALTY]);
                $db->prepare('UPDATE seller_profiles SET trust_score = GREATEST(0, COALESCE(trust_score, 0) - ?) WHERE id = ?')
                    ->execute([self::NEGATIVE_ORDER_PENALTY, $sellerProfileId]);
                $changed = true;
            } elseif (!$negativeReview && $existingPenalty) {
                $db->prepare('DELETE FROM seller_trust_rewards WHERE id = ?')
                    ->execute([(int) $existingPenalty->id]);
                $db->prepare('UPDATE seller_profiles SET trust_score = LEAST(100, COALESCE(trust_score, 0) + ?) WHERE id = ?')
                    ->execute([self::NEGATIVE_ORDER_PENALTY, $sellerProfileId]);
                $changed = true;
            }

            $db->commit();
        } catch (\Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $exception;
        }

        if ($changed) {
            $badge = (new BadgeCalculationService())->calculateBadge($sellerProfileId);
            $db->prepare('UPDATE seller_profiles SET verification_badge = ? WHERE id = ?')
                ->execute([$badge, $sellerProfileId]);
        }
    }

    private function orderQualifies($db, int $orderId, int $sellerProfileId): bool
    {
        $positiveReview = $db->prepare("SELECT COUNT(*)
            FROM reviews r
            INNER JOIN order_items oi ON oi.id = r.order_item_id
            INNER JOIN order_seller_statuses oss
                ON oss.order_id = oi.order_id
                AND oss.seller_profile_id = oi.seller_profile_id
            WHERE oi.order_id = ?
                AND oi.seller_profile_id = ?
                AND oss.status = 'completed'
                AND r.rating >= ?");
        $positiveReview->execute([$orderId, $sellerProfileId, self::POSITIVE_REVIEW_MINIMUM]);

        if ((int) $positiveReview->fetchColumn() === 0) {
            return false;
        }

        $complaints = $db->prepare("SELECT COUNT(*)
            FROM complaints
            WHERE seller_profile_id = ?
                AND status <> 'rejected'");
        $complaints->execute([$sellerProfileId]);

        if ((int) $complaints->fetchColumn() > 0) {
            return false;
        }

        $reports = $db->prepare("SELECT COUNT(*)
            FROM reports
            WHERE target_type = 'seller'
                AND target_id = ?
                AND status <> 'rejected'");
        $reports->execute([$sellerProfileId]);

        return (int) $reports->fetchColumn() === 0;
    }

    private function orderHasNegativeReview($db, int $orderId, int $sellerProfileId): bool
    {
        $negativeReview = $db->prepare("SELECT COUNT(*)
            FROM reviews r
            INNER JOIN order_items oi ON oi.id = r.order_item_id
            INNER JOIN order_seller_statuses oss
                ON oss.order_id = oi.order_id
                AND oss.seller_profile_id = oi.seller_profile_id
            WHERE oi.order_id = ?
                AND oi.seller_profile_id = ?
                AND oss.status = 'completed'
                AND r.rating <= ?");
        $negativeReview->execute([$orderId, $sellerProfileId, self::NEGATIVE_REVIEW_MAXIMUM]);

        return (int) $negativeReview->fetchColumn() > 0;
    }

    /** Deduct once when an administrator validates a report against a seller. */
    public function deductForValidatedReport(int $sellerProfileId, int $reportId): void
    {
        if ($sellerProfileId <= 0 || $reportId <= 0) {
            return;
        }

        $db = Database::connection();
        $this->ensureAdjustmentTable($db);
        $db->beginTransaction();

        try {
            $insert = $db->prepare("INSERT IGNORE INTO seller_trust_adjustments
                (seller_profile_id, entity_type, entity_id, adjustment_type, points, created_at)
                VALUES (?, 'report', ?, 'validated_report', ?, NOW())");
            $insert->execute([$sellerProfileId, $reportId, self::VALIDATED_REPORT_PENALTY]);

            if ($insert->rowCount() > 0) {
                $db->prepare('UPDATE seller_profiles SET trust_score = GREATEST(0, COALESCE(trust_score, 0) - ?) WHERE id = ?')
                    ->execute([self::VALIDATED_REPORT_PENALTY, $sellerProfileId]);
            }

            $db->commit();
        } catch (\Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $exception;
        }
    }

    private function ensureRewardTable($db): void
    {
        SchemaInspector::requireColumns('seller_trust_rewards', [
            'order_id', 'seller_profile_id', 'reward_type', 'points', 'created_at',
        ]);
    }

    private function ensureAdjustmentTable($db): void
    {
        SchemaInspector::requireColumns('seller_trust_adjustments', [
            'seller_profile_id', 'entity_type', 'entity_id', 'adjustment_type', 'points', 'created_at',
        ]);
    }
}
