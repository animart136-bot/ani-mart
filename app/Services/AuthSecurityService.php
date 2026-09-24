<?php

namespace App\Services;

use App\Core\Database;
use PDO;

class AuthSecurityService
{
    public const RESET_EXPIRY_SECONDS = 3600;
    public const LOGIN_LIMIT = 5;
    public const LOGIN_WINDOW_SECONDS = 900;
    public const LOGIN_LOCK_SECONDS = 900;

    public function ensureSchema(): void
    {
        SchemaInspector::requireTable('auth_throttles');
        SchemaInspector::requireTable('password_reset_tokens');
        SchemaInspector::requireColumns('users', ['admin_login_otp_enabled']);
    }

    public function retryAfter(string $scope, string $identifier): int
    {
        $this->ensureSchema();
        $stmt = Database::connection()->prepare('SELECT locked_until FROM auth_throttles WHERE scope = ? AND identifier_hash = ? LIMIT 1');
        $stmt->execute([$scope, $this->identifierHash($identifier)]);
        $lockedUntil = $stmt->fetchColumn();
        if (!$lockedUntil) return 0;
        return max(0, strtotime((string) $lockedUntil) - time());
    }

    public function recordAttempt(string $scope, string $identifier, int $limit, int $windowSeconds, int $lockSeconds): int
    {
        $this->ensureSchema();
        $db = Database::connection();
        $hash = $this->identifierHash($identifier);
        $db->beginTransaction();
        try {
            $stmt = $db->prepare('SELECT attempts, window_started_at, locked_until FROM auth_throttles WHERE scope = ? AND identifier_hash = ? FOR UPDATE');
            $stmt->execute([$scope, $hash]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $now = time();

            if ($row && !empty($row['locked_until']) && strtotime($row['locked_until']) > $now) {
                $db->commit();
                return strtotime($row['locked_until']) - $now;
            }

            $attempts = 1;
            $windowStarted = date('Y-m-d H:i:s', $now);
            if ($row && strtotime((string) $row['window_started_at']) > $now - $windowSeconds) {
                $attempts = (int) $row['attempts'] + 1;
                $windowStarted = $row['window_started_at'];
            }
            $lockedUntil = $attempts >= $limit ? date('Y-m-d H:i:s', $now + $lockSeconds) : null;

            $save = $db->prepare('INSERT INTO auth_throttles (scope, identifier_hash, attempts, window_started_at, locked_until) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE attempts = VALUES(attempts), window_started_at = VALUES(window_started_at), locked_until = VALUES(locked_until), updated_at = NOW()');
            $save->execute([$scope, $hash, $attempts, $windowStarted, $lockedUntil]);
            $db->commit();
            return $lockedUntil ? $lockSeconds : 0;
        } catch (\Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            throw $e;
        }
    }

    public function clearAttempts(string $scope, string $identifier): void
    {
        $this->ensureSchema();
        $stmt = Database::connection()->prepare('DELETE FROM auth_throttles WHERE scope = ? AND identifier_hash = ?');
        $stmt->execute([$scope, $this->identifierHash($identifier)]);
    }

    public function createResetToken(int $userId, string $requesterIp): string
    {
        $this->ensureSchema();
        $db = Database::connection();
        $rawToken = bin2hex(random_bytes(32));
        $db->prepare('UPDATE password_reset_tokens SET used_at = NOW() WHERE user_id = ? AND used_at IS NULL')->execute([$userId]);
        $stmt = $db->prepare('INSERT INTO password_reset_tokens (user_id, token_hash, requester_ip_hash, expires_at) VALUES (?, ?, ?, ?)');
        $stmt->execute([$userId, hash('sha256', $rawToken), $this->identifierHash($requesterIp), date('Y-m-d H:i:s', time() + self::RESET_EXPIRY_SECONDS)]);
        return $rawToken;
    }

    public function validReset(string $rawToken): ?array
    {
        $this->ensureSchema();
        if (!preg_match('/^[a-f0-9]{64}$/', $rawToken)) return null;
        $stmt = Database::connection()->prepare(
            'SELECT prt.id, prt.user_id, u.email, u.first_name
             FROM password_reset_tokens prt INNER JOIN users u ON u.id = prt.user_id
             WHERE prt.token_hash = ? AND prt.used_at IS NULL AND prt.expires_at > NOW() LIMIT 1'
        );
        $stmt->execute([hash('sha256', $rawToken)]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function resetPassword(string $rawToken, string $passwordHash): ?int
    {
        $this->ensureSchema();
        $db = Database::connection();
        $db->beginTransaction();
        try {
            $stmt = $db->prepare('SELECT id, user_id FROM password_reset_tokens WHERE token_hash = ? AND used_at IS NULL AND expires_at > NOW() FOR UPDATE');
            $stmt->execute([hash('sha256', $rawToken)]);
            $token = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$token) {
                $db->rollBack();
                return null;
            }
            $db->prepare('UPDATE users SET password = ? WHERE id = ?')->execute([$passwordHash, (int) $token['user_id']]);
            $db->prepare('UPDATE password_reset_tokens SET used_at = NOW() WHERE user_id = ? AND used_at IS NULL')->execute([(int) $token['user_id']]);
            $db->commit();
            return (int) $token['user_id'];
        } catch (\Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            throw $e;
        }
    }

    private function identifierHash(string $identifier): string
    {
        return hash('sha256', strtolower(trim($identifier)));
    }
}
