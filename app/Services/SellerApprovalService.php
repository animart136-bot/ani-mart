<?php

namespace App\Services;

use App\Core\Database;
use App\Controllers\NotificationController;

class SellerApprovalService
{
    public function approve(object $application, bool $automatic = false): void
    {
        $db = Database::connection();
        $this->ensureSlugColumn($db);
        $this->ensureSensitiveColumns($db);
        $hasSubmittedRsbsa = TrustScoreService::applicationSubmittedRsbsa($application);
        $hasVerifiedRsbsa = TrustScoreService::applicationHasRsbsa($application);
        $startingScore = TrustScoreService::startingScore($application);
        $startingBadge = TrustScoreService::startingBadge($application);
        $applicationAddress = sensitive_value($application, 'address', 'farmer_applications.address', null);
        $applicationContact = sensitive_value($application, 'contact_number', 'farmer_applications.contact_number', null);
        $db->beginTransaction();

        try {
            $update = $db->prepare("UPDATE farmer_applications SET status = ?, application_status = ? WHERE id = ? AND status IN ('pending', 'pending_official_verification')");
            $update->execute(['approved', 'approved', $application->id]);
            $existing = $db->prepare('SELECT id FROM seller_profiles WHERE user_id = ? LIMIT 1');
            $existing->execute([$application->user_id]);

            if (!$existing->fetch()) {
                $seller = $db->prepare("INSERT INTO seller_profiles (user_id, shop_name, slug, description, shop_theme, farm_logo, farm_banner, farm_location, contact_number, government_id, status, trust_score, verification_badge, ai_verified_farmer, rsbsa_verified, created_at) VALUES (?, ?, ?, ?, 'fresh-farm', ?, ?, ?, ?, ?, 'approved', ?, ?, 0, 0, NOW())");
                $seller->execute([$application->user_id, $application->farm_name, $this->uniqueSlug($db, $application->farm_name), $application->farm_description, $application->seller_profile_image_path ?? null, $application->shop_banner_path ?? null, $applicationAddress, $applicationContact, $application->rsbsa_card_path ?? null, $startingScore, $startingBadge]);
            } else {
                $seller = $db->prepare("UPDATE seller_profiles SET verification_badge = CASE WHEN ? = 1 THEN 'verified' ELSE verification_badge END, trust_score = CASE WHEN ? = 1 THEN GREATEST(COALESCE(trust_score, 0), ?) ELSE COALESCE(trust_score, 0) END, rsbsa_verified = CASE WHEN ? = 1 THEN rsbsa_verified ELSE 0 END, farm_location = COALESCE(NULLIF(farm_location, ''), ?) WHERE user_id = ?");
                $seller->execute([$hasVerifiedRsbsa ? 1 : 0, $hasSubmittedRsbsa ? 1 : 0, TrustScoreService::RSBSA_SELLER_START, $hasVerifiedRsbsa ? 1 : 0, $applicationAddress, $application->user_id]);
            }

            $cipher = new SensitiveDataCipher();
            $db->prepare('UPDATE seller_profiles SET farm_location_encrypted = ?, contact_number_encrypted = ?, government_id_encrypted = ? WHERE user_id = ?')
                ->execute([
                    $cipher->encrypt($applicationAddress, 'seller_profiles.farm_location'),
                    $cipher->encrypt($applicationContact, 'seller_profiles.contact_number'),
                    $cipher->encrypt($application->rsbsa_card_path ?? null, 'seller_profiles.government_id'),
                    $application->user_id,
                ]);

            $user = $db->prepare("UPDATE users SET role = 'farmer' WHERE id = ?");
            $user->execute([$application->user_id]);
            $db->commit();
        } catch (\Throwable $exception) {
            if ($db->inTransaction()) $db->rollBack();
            throw $exception;
        }

        $message = $automatic
            ? 'Your shop is active. You can start preparing products now; your first products will be reviewed before appearing in the marketplace.'
            : 'Congratulations! Your seller application has been approved. You can now start selling on Ani-Mart.';
        NotificationController::create($application->user_id, 'verification', '🎉 Seller Access Activated', $message, 'bi-check-circle', 'success', '/ani-mart/public/farmer/products');
    }

    public function createReferenceSeller(object $application): void
    {
        $db = Database::connection();
        $this->ensureSlugColumn($db);
        $this->ensureSensitiveColumns($db);
        $applicationAddress = sensitive_value($application, 'address', 'farmer_applications.address', null);
        $startingScore = TrustScoreService::startingScore($application);
        $startingBadge = TrustScoreService::startingBadge($application);
        $existing = $db->prepare('SELECT id FROM seller_profiles WHERE user_id = ? LIMIT 1');
        $existing->execute([$application->user_id]);
        if (!$existing->fetch()) {
            $seller = $db->prepare("INSERT INTO seller_profiles (user_id, shop_name, slug, description, shop_theme, farm_logo, farm_location, government_id, status, trust_score, verification_badge, ai_verified_farmer, rsbsa_verified, created_at) VALUES (?, ?, ?, ?, 'fresh-farm', ?, ?, ?, 'approved', ?, ?, 0, 0, NOW())");
            $seller->execute([$application->user_id, $application->farm_name, $this->uniqueSlug($db, $application->farm_name), $application->farm_description, $application->seller_profile_image_path ?? null, $applicationAddress, $application->rsbsa_card_path ?? null, $startingScore, $startingBadge]);
        } else {
            $db->prepare("UPDATE seller_profiles SET farm_location = COALESCE(NULLIF(farm_location, ''), ?) WHERE user_id = ?")->execute([$applicationAddress, $application->user_id]);
        }
        $cipher = new SensitiveDataCipher();
        $db->prepare('UPDATE seller_profiles SET farm_location_encrypted = ?, government_id_encrypted = ? WHERE user_id = ?')
            ->execute([
                $cipher->encrypt($applicationAddress, 'seller_profiles.farm_location'),
                $cipher->encrypt($application->rsbsa_card_path ?? null, 'seller_profiles.government_id'),
                $application->user_id,
            ]);
        $db->prepare("UPDATE users SET role = 'farmer' WHERE id = ?")->execute([$application->user_id]);
    }

    private function uniqueSlug($db, string $value): string
    {
        return SlugService::uniqueSeller($value, 0, 'farm', $db);
    }

    private function ensureSlugColumn($db): void
    {
        SchemaInspector::requireColumns('seller_profiles', ['slug']);
    }

    private function ensureSensitiveColumns($db): void
    {
        SchemaInspector::requireColumns('seller_profiles', [
            'farm_location_encrypted', 'contact_number_encrypted', 'government_id_encrypted',
        ]);
    }
}
