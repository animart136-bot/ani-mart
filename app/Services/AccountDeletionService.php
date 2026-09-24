<?php

namespace App\Services;

use App\Core\Database;
use InvalidArgumentException;
use PDO;

class AccountDeletionService
{
    public const GRACE_PERIOD_DAYS = 30;

    private static bool $schemaReady = false;

    public function ensureSchema(): void
    {
        if (self::$schemaReady) return;

        SchemaInspector::requireColumns('users', [
            'account_status', 'deletion_requested_at', 'deletion_scheduled_for', 'deleted_at',
        ]);

        self::$schemaReady = true;
    }

    public function schedule(int $userId, string $currentPassword): string
    {
        $this->ensureSchema();
        if ($currentPassword === '') throw new InvalidArgumentException('Enter your current password to confirm deletion.');

        $db = Database::connection();
        $db->beginTransaction();
        try {
            $stmt = $db->prepare('SELECT id, role, password, account_status FROM users WHERE id = ? FOR UPDATE');
            $stmt->execute([$userId]);
            $user = $stmt->fetch();

            if (!$user) throw new InvalidArgumentException('Account not found.');
            if (strtolower((string) $user->role) === 'admin') throw new InvalidArgumentException('Administrator accounts cannot be deleted here.');
            if ((string) $user->account_status === 'deleted') throw new InvalidArgumentException('This account has already been deleted.');
            if (!password_verify($currentPassword, (string) $user->password)) throw new InvalidArgumentException('The current password is incorrect.');

            $db->prepare(
                'UPDATE users SET deletion_requested_at = NOW(), deletion_scheduled_for = DATE_ADD(NOW(), INTERVAL ' . self::GRACE_PERIOD_DAYS . ' DAY) WHERE id = ?'
            )->execute([$userId]);

            $scheduledFor = (string) $db->query('SELECT DATE_ADD(NOW(), INTERVAL ' . self::GRACE_PERIOD_DAYS . ' DAY)')->fetchColumn();
            $db->commit();
            return $scheduledFor;
        } catch (\Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            throw $e;
        }
    }

    /** Returns "active", "cancelled", or "deleted". */
    public function handleSuccessfulLogin(int $userId): string
    {
        $this->ensureSchema();
        $db = Database::connection();
        $stmt = $db->prepare('SELECT id, role, account_status, deletion_scheduled_for FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user || strtolower((string) $user->role) === 'admin') return 'active';
        if ((string) $user->account_status === 'deleted') return 'deleted';
        if (empty($user->deletion_scheduled_for)) return 'active';

        if (strtotime((string) $user->deletion_scheduled_for) <= time()) {
            $this->finalize($userId);
            return 'deleted';
        }

        $db->prepare('UPDATE users SET deletion_requested_at = NULL, deletion_scheduled_for = NULL WHERE id = ? AND account_status <> ?')
            ->execute([$userId, 'deleted']);
        return 'cancelled';
    }

    public function processDue(): int
    {
        $this->ensureSchema();
        $stmt = Database::connection()->query(
            "SELECT id FROM users WHERE LOWER(role) <> 'admin' AND account_status <> 'deleted' AND deletion_scheduled_for IS NOT NULL AND deletion_scheduled_for <= NOW()"
        );
        $count = 0;
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $userId) {
            if ($this->finalize((int) $userId)) $count++;
        }
        return $count;
    }

    public function isDeleted(int $userId): bool
    {
        $this->ensureSchema();
        $stmt = Database::connection()->prepare("SELECT 1 FROM users WHERE id = ? AND account_status = 'deleted' LIMIT 1");
        $stmt->execute([$userId]);
        return (bool) $stmt->fetchColumn();
    }

    private function finalize(int $userId): bool
    {
        $db = Database::connection();
        $db->beginTransaction();
        try {
            $stmt = $db->prepare('SELECT id, role, account_status, deletion_scheduled_for FROM users WHERE id = ? FOR UPDATE');
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            if (!$user || strtolower((string) $user->role) === 'admin' || (string) $user->account_status === 'deleted' || empty($user->deletion_scheduled_for) || strtotime((string) $user->deletion_scheduled_for) > time()) {
                $db->rollBack();
                return false;
            }

            $deletedEmail = 'deleted-user-' . $userId . '@ani-mart.invalid';
            $db->prepare("UPDATE users SET
                    first_name = 'Deleted', first_name_encrypted = NULL,
                    last_name = 'User', last_name_encrypted = NULL,
                    email = ?, email_encrypted = NULL, password = ?, profile_image = NULL,
                    latitude = NULL, latitude_encrypted = NULL, longitude = NULL, longitude_encrypted = NULL,
                    address = NULL, address_encrypted = NULL, barangay = NULL, barangay_encrypted = NULL,
                    municipality = NULL, municipality_encrypted = NULL, province = NULL, province_encrypted = NULL,
                    phone = NULL, phone_encrypted = NULL, account_status = 'deleted',
                    suspension_reason = NULL, suspension_reason_encrypted = NULL, suspended_at = NULL, suspended_by = NULL,
                    deletion_requested_at = NULL, deleted_at = NOW()
                WHERE id = ?")
                ->execute([$deletedEmail, password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT), $userId]);

            if (strtolower((string) $user->role) === 'farmer') {
                $db->prepare('UPDATE products SET is_active = 0 WHERE seller_profile_id IN (SELECT id FROM seller_profiles WHERE user_id = ?)')->execute([$userId]);
                $db->prepare("UPDATE seller_profiles SET
                        shop_name = ?, slug = ?, description = '', farm_story = NULL, operating_hours = NULL,
                        delivery_information = NULL, is_active = 0, farm_banner = NULL, farm_logo = NULL,
                        farm_location = NULL, farm_location_encrypted = NULL, contact_number = NULL,
                        contact_number_encrypted = NULL, latitude = NULL, latitude_encrypted = NULL,
                        longitude = NULL, longitude_encrypted = NULL, barangay = NULL, barangay_encrypted = NULL,
                        city = NULL, city_encrypted = NULL, government_id = NULL, government_id_encrypted = NULL,
                        is_suspended = 1, suspended_at = NOW(), updated_at = NOW()
                    WHERE user_id = ?")
                    ->execute(['Deleted seller', 'deleted-seller-' . $userId, $userId]);
            }

            $db->commit();
            (new CloudinaryService())->scheduleOwnerDeletion($userId, 30);
            return true;
        } catch (\Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            throw $e;
        }
    }
}
