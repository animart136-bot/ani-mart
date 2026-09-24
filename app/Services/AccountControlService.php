<?php

namespace App\Services;

use App\Core\Database;
use InvalidArgumentException;

class AccountControlService
{
    private static bool $schemaReady = false;

    public function ensureSchema(): void
    {
        if (self::$schemaReady) return;
        SchemaInspector::requireColumns('users', [
            'account_status', 'suspension_reason', 'suspension_reason_encrypted', 'suspended_at', 'suspended_by',
        ]);
        self::$schemaReady = true;
    }

    public function users(string $search = '', string $role = '', string $status = ''): array
    {
        $this->ensureSchema();
        $where = ["LOWER(u.role) <> 'admin'"];
        $params = [];
        if ($search !== '') { $where[] = '(u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)'; $term = '%' . $search . '%'; array_push($params, $term, $term, $term); }
        if (in_array($role, ['customer', 'farmer'], true)) { $where[] = 'LOWER(u.role) = ?'; $params[] = $role; }
        if (in_array($status, ['active', 'suspended', 'deleted'], true)) { $where[] = 'u.account_status = ?'; $params[] = $status; }
        $stmt = Database::connection()->prepare(
            'SELECT u.*, (SELECT sp.shop_name FROM seller_profiles sp WHERE sp.user_id = u.id ORDER BY sp.id DESC LIMIT 1) AS shop_name
             FROM users u WHERE ' . implode(' AND ', $where) . ' ORDER BY u.created_at DESC LIMIT 250'
        );
        $stmt->execute($params);
        return array_map(
            fn($record) => hydrate_sensitive_fields($record, 'users', [
                'first_name', 'last_name', 'email', 'suspension_reason',
            ]),
            $stmt->fetchAll()
        );
    }

    public function setStatus(int $userId, string $status, string $reason, int $adminId): array
    {
        $this->ensureSchema();
        if (!in_array($status, ['active', 'suspended'], true)) throw new InvalidArgumentException('Invalid account status.');
        if ($status === 'suspended' && mb_strlen(trim($reason)) < 5) throw new InvalidArgumentException('Provide a clear suspension reason.');
        $db = Database::connection();
        $db->beginTransaction();
        try {
            $stmt = $db->prepare('SELECT id, first_name, last_name, email, role, account_status FROM users WHERE id = ? FOR UPDATE');
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            $user = hydrate_sensitive_fields($user, 'users', ['first_name', 'last_name', 'email']);
            if (!$user) throw new InvalidArgumentException('User account not found.');
            if (strtolower((string) $user->role) === 'admin') throw new InvalidArgumentException('Administrator accounts cannot be changed here.');
            if ((string) ($user->account_status ?? '') === 'deleted') throw new InvalidArgumentException('Deleted accounts cannot be restored or suspended.');
            $oldStatus = (string) ($user->account_status ?? 'active');
            if ($status === 'suspended') {
                $reason = trim($reason);
                $update = $db->prepare("UPDATE users SET account_status = 'suspended', suspension_reason = ?, suspension_reason_encrypted = ?, suspended_at = NOW(), suspended_by = ? WHERE id = ?");
                $update->execute([$reason, (new SensitiveDataCipher())->encrypt($reason, 'users.suspension_reason'), $adminId, $userId]);
                if (strtolower((string) $user->role) === 'farmer') $db->prepare('UPDATE seller_profiles SET is_suspended = 1, suspended_at = NOW() WHERE user_id = ?')->execute([$userId]);
            } else {
                $update = $db->prepare("UPDATE users SET account_status = 'active', suspension_reason = NULL, suspension_reason_encrypted = NULL, suspended_at = NULL, suspended_by = NULL WHERE id = ?");
                $update->execute([$userId]);
                if (strtolower((string) $user->role) === 'farmer') $db->prepare("UPDATE seller_profiles SET is_suspended = 0, suspended_at = NULL, status = CASE WHEN status = 'suspended' THEN 'approved' ELSE status END WHERE user_id = ?")->execute([$userId]);
            }
            $db->commit();
            return ['user' => $user, 'old_status' => $oldStatus, 'new_status' => $status];
        } catch (\Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            throw $e;
        }
    }

    public function isSuspended(int $userId): bool
    {
        $this->ensureSchema();
        $stmt = Database::connection()->prepare("SELECT 1 FROM users WHERE id = ? AND account_status = 'suspended' LIMIT 1");
        $stmt->execute([$userId]);
        return (bool) $stmt->fetchColumn();
    }
}
