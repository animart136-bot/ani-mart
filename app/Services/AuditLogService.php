<?php

namespace App\Services;

use App\Core\Database;
use App\Core\Session;

class AuditLogService
{
    private const SENSITIVE_KEYS = [
        'password',
        'passphrase',
        'otp',
        'otp_code',
        'otp_hash',
        'otpsecret',
        'session',
        'session_id',
        'csrf',
        'csrf_token',
        'token',
        'secret',
        'api_key',
        'api_secret',
        'cloudinary_secret',
        'mail_password',
        'smtp_password',
        'private_key',
        'authorization',
        'cookie',
        'auth_token'
    ];

    public static function log(
        string $event,
        ?string $targetType = null,
        ?int $targetId = null,
        array $details = [],
        ?int $actorUserId = null,
        ?string $requestPath = null,
        string $status = 'success'
    ): void {
        $eventName = trim($event);

        if ($eventName === '') {
            return;
        }

        $user = Session::get('user');
        $resolvedActorId = $actorUserId ?? (($user['id'] ?? null) !== null ? (int) $user['id'] : null);

        $oldValue = self::sanitizeDetails($details['old_value'] ?? $details['old'] ?? null, 'old_value');
        $newValue = self::sanitizeDetails($details['new_value'] ?? $details['new'] ?? null, 'new_value');
        $description = self::sanitizeDetails($details['description'] ?? $eventName, 'description');
        $oldJson = $oldValue === null ? null : json_encode($oldValue, JSON_UNESCAPED_SLASHES);
        $newJson = $newValue === null ? null : json_encode($newValue, JSON_UNESCAPED_SLASHES);
        $ipAddress = self::clientIpAddress();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $cipher = new SensitiveDataCipher();

        $db = Database::connection();
        $stmt = $db->prepare("
            INSERT INTO audit_logs (
                user_id,
                action,
                entity_type,
                entity_id,
                old_value,
                old_value_encrypted,
                new_value,
                new_value_encrypted,
                description,
                description_encrypted,
                ip_address,
                ip_address_encrypted,
                user_agent,
                user_agent_encrypted,
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        $stmt->execute([
            $resolvedActorId,
            $eventName,
            $targetType,
            $targetId,
            $oldJson,
            $cipher->encrypt($oldJson, 'audit_logs.old_value'),
            $newJson,
            $cipher->encrypt($newJson, 'audit_logs.new_value'),
            $description,
            $cipher->encrypt((string) $description, 'audit_logs.description'),
            $ipAddress,
            $cipher->encrypt($ipAddress, 'audit_logs.ip_address'),
            $userAgent,
            $cipher->encrypt($userAgent, 'audit_logs.user_agent'),
        ]);
    }

    private static function sanitizeDetails($value, string $key = '')
    {
        $normalizedKey = strtolower(str_replace(['-', '_', ' '], '', (string) $key));

        if ($key !== '' && self::isSensitiveKey($normalizedKey)) {
            return '[redacted]';
        }

        if (is_array($value)) {
            $sanitized = [];

            foreach ($value as $nestedKey => $nestedValue) {
                $sanitized[$nestedKey] = self::sanitizeDetails($nestedValue, (string) $nestedKey);
            }

            return $sanitized;
        }

        if (is_object($value)) {
            $sanitized = [];

            foreach ((array) $value as $nestedKey => $nestedValue) {
                $sanitized[$nestedKey] = self::sanitizeDetails($nestedValue, (string) $nestedKey);
            }

            return $sanitized;
        }

        if (is_bool($value)) {
            return $value;
        }

        if (is_null($value)) {
            return null;
        }

        if (is_int($value) || is_float($value)) {
            return $value;
        }

        $string = trim((string) $value);

        return mb_substr($string, 0, 500);
    }

    private static function isSensitiveKey(string $key): bool
    {
        foreach (self::SENSITIVE_KEYS as $candidate) {
            if ($key === $candidate || str_contains($key, $candidate)) {
                return true;
            }
        }

        return false;
    }

    private static function clientIpAddress(): string
    {
        $forwardedFor = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';

        if (is_string($forwardedFor) && trim($forwardedFor) !== '') {
            $parts = array_filter(array_map('trim', explode(',', $forwardedFor)));

            if (!empty($parts)) {
                return (string) $parts[0];
            }
        }

        return (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    }
}
