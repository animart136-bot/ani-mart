<?php

namespace App\Services;

use App\Core\Database;
use App\Core\Session;
use App\Helpers\UploadValidator;
use Cloudinary\Cloudinary;
use PDO;
use RuntimeException;
use Throwable;

class CloudinaryService
{
    public const VISIBILITY_PUBLIC = 'public';
    public const VISIBILITY_SENSITIVE = 'sensitive';

    private Cloudinary $cloudinary;
    private ?PDO $db = null;

    public function __construct()
    {
        $cloudConfig = $this->resolveCloudinaryConfig();

        $this->cloudinary = new Cloudinary([
            'cloud' => [
                'cloud_name' => $cloudConfig['cloud_name'],
                'api_key'    => $cloudConfig['api_key'],
                'api_secret' => $cloudConfig['api_secret'],
            ],
            'url' => [
                'secure' => true,
            ],
        ]);
    }

    private function resolveCloudinaryConfig(): array
    {
        $cloudConfig = [
            'cloud_name' => $_ENV['CLOUDINARY_CLOUD_NAME'] ?? getenv('CLOUDINARY_CLOUD_NAME') ?: null,
            'api_key'    => $_ENV['CLOUDINARY_API_KEY'] ?? getenv('CLOUDINARY_API_KEY') ?: null,
            'api_secret' => $_ENV['CLOUDINARY_API_SECRET'] ?? getenv('CLOUDINARY_API_SECRET') ?: null,
        ];

        if (empty($cloudConfig['cloud_name']) || empty($cloudConfig['api_key']) || empty($cloudConfig['api_secret'])) {
            $fallback = $this->loadCloudinaryConfigFile();
            $cloudConfig = array_merge($fallback, $cloudConfig);
        }

        if (empty($cloudConfig['cloud_name']) || empty($cloudConfig['api_key']) || empty($cloudConfig['api_secret'])) {
            throw new RuntimeException(
                'Cloudinary configuration is missing. Set CLOUDINARY_CLOUD_NAME, CLOUDINARY_API_KEY, ' .
                'and CLOUDINARY_API_SECRET in environment variables or config/cloudinary.php.'
            );
        }

        return $cloudConfig;
    }

    private function loadCloudinaryConfigFile(): array
    {
        $configPath = __DIR__ . '/../../config/cloudinary.php';
        if (! file_exists($configPath)) {
            return [];
        }

        $config = require $configPath;
        if (! is_array($config)) {
            return [];
        }

        return [
            'cloud_name' => $config['cloud_name'] ?? null,
            'api_key'    => $config['api_key'] ?? null,
            'api_secret' => $config['api_secret'] ?? null,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Upload Image
    |--------------------------------------------------------------------------
    */
    public function upload($file, $folder = 'ani-mart', array $options = [])
    {
        $validation = $this->validateUpload($file, $options);
        if ($validation === null) {
            return null;
        }

        $visibility = ($options['visibility'] ?? self::VISIBILITY_PUBLIC) === self::VISIBILITY_SENSITIVE
            ? self::VISIBILITY_SENSITIVE
            : self::VISIBILITY_PUBLIC;
        $deliveryType = $visibility === self::VISIBILITY_SENSITIVE ? 'authenticated' : 'upload';

        $result =
            $this->cloudinary
                ->uploadApi()
                ->upload($validation['path'], [
                    'folder' => $folder,
                    'type' => $deliveryType,
                    'verify' => true,
                    'curl' => [
                        CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_2,
                    ],
                ]);

        $assetId = $this->registerAsset([
            'public_id' => (string) $result['public_id'],
            'secure_url' => (string) $result['secure_url'],
            'delivery_type' => $deliveryType,
            'resource_type' => (string) ($result['resource_type'] ?? 'image'),
            'format' => (string) ($result['format'] ?? pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION)),
            'bytes' => (int) ($result['bytes'] ?? $validation['size'] ?? 0),
            'visibility' => $visibility,
            'owner_user_id' => $options['owner_user_id'] ?? (Session::get('user')['id'] ?? null),
            'entity_type' => $options['entity_type'] ?? null,
            'entity_id' => $options['entity_id'] ?? null,
            'retention_days' => $options['retention_days'] ?? null,
        ]);

        $deliveryUrl = $visibility === self::VISIBILITY_SENSITIVE
            ? '/ani-mart/public/media/assets/' . $assetId
            : (string) $result['secure_url'];

        return [
            'public_id' => $result['public_id'],
            'secure_url' => $deliveryUrl,
            'url' => $deliveryUrl,
            'cloudinary_url' => $result['secure_url'],
            'asset_id' => $assetId,
            'delivery_type' => $deliveryType,
            'visibility' => $visibility,
        ];
    }

    public function uploadPublic($file, string $folder = 'ani-mart', array $options = []): ?array
    {
        $options['visibility'] = self::VISIBILITY_PUBLIC;
        return $this->upload($file, $folder, $options);
    }

    public function uploadSensitive($file, string $folder, array $options = []): ?array
    {
        $options['visibility'] = self::VISIBILITY_SENSITIVE;
        return $this->upload($file, $folder, $options);
    }

    private function validateUpload($file, array $options = []): ?array
    {
        $preparedFile = $this->normalizeUploadInput($file);
        if ($preparedFile === null) {
            return null;
        }

        $allowLocalPath = is_string($file) && $file !== '';
        $validator = new UploadValidator(array_merge($options, [
            'allow_local_path' => $allowLocalPath,
        ]));

        $validation = $validator->validate($preparedFile);
        if (!$validation['ok']) {
            Session::flash('error', $validation['message']);
            return null;
        }

        return $validation;
    }

    private function normalizeUploadInput($file): ?array
    {
        if (is_array($file) && isset($file['tmp_name'])) {
            return $file;
        }

        if (!is_string($file) || $file === '') {
            return null;
        }

        $path = $file;
        if (!is_file($path)) {
            return null;
        }

        $size = @filesize($path) ?: 0;
        $mimeType = null;
        if (function_exists('mime_content_type')) {
            $mimeType = mime_content_type($path) ?: null;
        }

        return [
            'name' => basename($path),
            'tmp_name' => $path,
            'size' => $size,
            'type' => $mimeType ?? '',
            'error' => UPLOAD_ERR_OK,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Image
    |--------------------------------------------------------------------------
    */
    public function delete($publicId, array $options = [])
    {
        $asset = $this->findByPublicId((string) $publicId);
        $deliveryType = (string) ($options['delivery_type'] ?? ($asset->delivery_type ?? 'upload'));

        try {
            $result = $this->cloudinary
                ->uploadApi()
                ->destroy($publicId, ['type' => $deliveryType, 'invalidate' => true]);
        } catch (Throwable $e) {
            $this->markDeleteFailed((string) $publicId, $e->getMessage());
            return ['result' => 'error', 'message' => 'Cloudinary deletion failed and was queued for retry.'];
        }

        $status = (string) ($result['result'] ?? '');
        if (in_array($status, ['ok', 'not found'], true)) {
            $this->markDeleted((string) $publicId);
        } else {
            $this->markDeleteFailed((string) $publicId, 'Cloudinary returned: ' . ($status ?: 'unknown'));
        }

        return $result;
    }

    public function associate(string $publicId, string $entityType, int $entityId, ?int $ownerUserId = null): void
    {
        $this->ensureSchema();
        $stmt = $this->db()->prepare('UPDATE image_assets SET entity_type = ?, entity_id = ?, owner_user_id = COALESCE(?, owner_user_id), updated_at = NOW() WHERE public_id = ?');
        $stmt->execute([$entityType, $entityId, $ownerUserId, $publicId]);
    }

    public function findById(int $assetId): ?object
    {
        $this->ensureSchema();
        $stmt = $this->db()->prepare('SELECT * FROM image_assets WHERE id = ? LIMIT 1');
        $stmt->execute([$assetId]);
        return $stmt->fetch() ?: null;
    }

    public function findByPublicId(string $publicId): ?object
    {
        try {
            $this->ensureSchema();
            $stmt = $this->db()->prepare('SELECT * FROM image_assets WHERE public_id = ? LIMIT 1');
            $stmt->execute([$publicId]);
            return $stmt->fetch() ?: null;
        } catch (Throwable $e) {
            error_log('Image registry lookup failed: ' . $e->getMessage());
            return null;
        }
    }

    public function findByUrl(string $url): ?object
    {
        $assetId = 0;
        if (preg_match('#/media/assets/(\d+)#', $url, $matches)) {
            $assetId = (int) $matches[1];
        }

        $this->ensureSchema();
        if ($assetId > 0) {
            return $this->findById($assetId);
        }

        $stmt = $this->db()->prepare('SELECT * FROM image_assets WHERE secure_url = ? LIMIT 1');
        $stmt->execute([$url]);
        return $stmt->fetch() ?: null;
    }

    public function signedDownloadUrl(object $asset, int $lifetimeSeconds = 300): string
    {
        if (($asset->visibility ?? '') !== self::VISIBILITY_SENSITIVE || ($asset->status ?? '') !== 'active') {
            throw new RuntimeException('This protected image is unavailable.');
        }

        return $this->cloudinary->uploadApi()->privateDownloadUrl(
            (string) $asset->public_id,
            (string) ($asset->format ?: 'jpg'),
            [
                'type' => (string) ($asset->delivery_type ?: 'authenticated'),
                'resource_type' => (string) ($asset->resource_type ?: 'image'),
                'expires_at' => time() + max(60, min($lifetimeSeconds, 600)),
            ]
        );
    }

    public function resolveForServer(string $url, int $lifetimeSeconds = 300): string
    {
        $asset = $this->findByUrl($url);
        if (!$asset || ($asset->visibility ?? '') !== self::VISIBILITY_SENSITIVE) {
            return $url;
        }
        return $this->signedDownloadUrl($asset, $lifetimeSeconds);
    }

    public function scheduleDeletion(string $publicId, int $days = 30): bool
    {
        $this->ensureSchema();
        $stmt = $this->db()->prepare("UPDATE image_assets SET status = 'deletion_pending', delete_after = DATE_ADD(NOW(), INTERVAL ? DAY), updated_at = NOW() WHERE public_id = ? AND status <> 'deleted'");
        $stmt->execute([max(0, $days), $publicId]);
        return $stmt->rowCount() > 0;
    }

    public function scheduleEntityDeletion(string $entityType, int $entityId, int $days = 365): int
    {
        $this->ensureSchema();
        $stmt = $this->db()->prepare("UPDATE image_assets SET status = 'deletion_pending', delete_after = DATE_ADD(NOW(), INTERVAL ? DAY), updated_at = NOW() WHERE entity_type = ? AND entity_id = ? AND status = 'active'");
        $stmt->execute([max(0, $days), $entityType, $entityId]);
        return $stmt->rowCount();
    }

    public function cancelEntityDeletion(string $entityType, int $entityId): int
    {
        $this->ensureSchema();
        $stmt = $this->db()->prepare("UPDATE image_assets SET status = 'active', delete_after = NULL, last_delete_error = NULL, updated_at = NOW() WHERE entity_type = ? AND entity_id = ? AND status IN ('deletion_pending', 'delete_failed') AND deleted_at IS NULL");
        $stmt->execute([$entityType, $entityId]);
        return $stmt->rowCount();
    }

    public function scheduleOwnerDeletion(int $ownerUserId, int $days = 30): int
    {
        $this->ensureSchema();
        $stmt = $this->db()->prepare("UPDATE image_assets SET status = 'deletion_pending', delete_after = DATE_ADD(NOW(), INTERVAL ? DAY), updated_at = NOW() WHERE owner_user_id = ? AND status = 'active'");
        $stmt->execute([max(0, $days), $ownerUserId]);
        return $stmt->rowCount();
    }

    public function activePublicIds(): array
    {
        $this->ensureSchema();
        $rows = $this->db()->query("SELECT public_id FROM image_assets WHERE status <> 'deleted'")->fetchAll(PDO::FETCH_COLUMN);
        return array_fill_keys(array_map('strval', $rows), true);
    }

    public function referencedPublicIds(): array
    {
        $known = $this->activePublicIds();
        $sources = [
            ['products', 'image'], ['product_images', 'image_url'],
            ['community_post_images', 'image_url'], ['farm_profile_images', 'image_path'],
            ['users', 'profile_image'], ['seller_profiles', 'farm_banner'], ['seller_profiles', 'farm_logo'],
            ['reviews', 'review_photo'], ['report_evidence', 'image_url'], ['complaint_evidence', 'image_url'],
            ['return_evidence', 'image_url'], ['orders', 'proof_image'],
            ['farmer_applications', 'rsbsa_card_path'], ['farmer_applications', 'seller_profile_image_path'],
            ['farmer_applications', 'shop_banner_path'],
        ];

        foreach ($sources as [$table, $column]) {
            try {
                $rows = $this->db()->query("SELECT `{$column}` FROM `{$table}` WHERE `{$column}` IS NOT NULL AND `{$column}` <> ''")->fetchAll(PDO::FETCH_COLUMN);
                foreach ($rows as $url) {
                    $publicId = $this->publicIdFromUrl((string) $url);
                    if ($publicId !== null) {
                        $known[$publicId] = true;
                    }
                }
            } catch (Throwable $e) {
                // Optional/legacy image columns do not exist in every installation.
            }
        }

        return $known;
    }

    public function publicIdFromUrl(string $url): ?string
    {
        if (!str_contains($url, 'res.cloudinary.com/')) {
            return null;
        }
        $path = (string) parse_url($url, PHP_URL_PATH);
        if (!preg_match('#/v\d+/(.+)\.([a-zA-Z0-9]+)$#', $path, $matches)) {
            return null;
        }
        return rawurldecode($matches[1]);
    }

    public function registerExistingUrl(string $url, ?string $entityType = null, ?int $entityId = null, ?int $ownerUserId = null): ?int
    {
        $publicId = $this->publicIdFromUrl($url);
        if ($publicId === null) {
            return null;
        }
        $path = (string) parse_url($url, PHP_URL_PATH);
        $format = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));
        return $this->registerAsset([
            'public_id' => $publicId,
            'secure_url' => $url,
            'delivery_type' => 'upload',
            'resource_type' => 'image',
            'format' => $format ?: null,
            'bytes' => 0,
            'visibility' => self::VISIBILITY_PUBLIC,
            'owner_user_id' => $ownerUserId,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'retention_days' => null,
        ]);
    }

    public function protectExistingAsset(string $url): ?array
    {
        $asset = $this->findByUrl($url);
        if (!$asset) {
            return null;
        }
        if (($asset->visibility ?? '') === self::VISIBILITY_SENSITIVE && ($asset->delivery_type ?? '') === 'authenticated') {
            return ['asset_id' => (int) $asset->id, 'url' => '/ani-mart/public/media/assets/' . (int) $asset->id];
        }

        try {
            $this->cloudinary->uploadApi()->rename(
                (string) $asset->public_id,
                (string) $asset->public_id,
                ['type' => (string) ($asset->delivery_type ?: 'upload'), 'to_type' => 'authenticated', 'invalidate' => true, 'overwrite' => true]
            );
        } catch (Throwable $e) {
            error_log('Unable to protect existing Cloudinary asset: ' . $e->getMessage());
            return null;
        }

        $this->db()->prepare("UPDATE image_assets SET delivery_type = 'authenticated', visibility = 'sensitive', updated_at = NOW() WHERE id = ?")
            ->execute([(int) $asset->id]);

        return ['asset_id' => (int) $asset->id, 'url' => '/ani-mart/public/media/assets/' . (int) $asset->id];
    }

    public function deleteByUrl(string $url, bool $immediate = true): bool
    {
        $asset = $this->findByUrl($url);
        if (!$asset) {
            return false;
        }
        if (!$immediate) {
            return $this->scheduleDeletion((string) $asset->public_id);
        }
        $result = $this->delete((string) $asset->public_id, ['delivery_type' => (string) $asset->delivery_type]);
        return in_array((string) ($result['result'] ?? ''), ['ok', 'not found'], true);
    }

    public function processPendingDeletions(int $limit = 100): array
    {
        $this->ensureSchema();
        $limit = max(1, min($limit, 500));
        $rows = $this->db()->query("SELECT * FROM image_assets WHERE status IN ('deletion_pending', 'delete_failed') AND delete_after IS NOT NULL AND delete_after <= NOW() AND delete_attempts < 5 ORDER BY delete_after ASC LIMIT {$limit}")->fetchAll();
        $summary = ['processed' => 0, 'deleted' => 0, 'failed' => 0];
        foreach ($rows as $asset) {
            $summary['processed']++;
            try {
                $result = $this->delete((string) $asset->public_id, ['delivery_type' => (string) $asset->delivery_type]);
                in_array((string) ($result['result'] ?? ''), ['ok', 'not found'], true) ? $summary['deleted']++ : $summary['failed']++;
            } catch (Throwable $e) {
                $this->markDeleteFailed((string) $asset->public_id, $e->getMessage());
                $summary['failed']++;
            }
        }
        return $summary;
    }

    public function listRemoteAssets(?string $nextCursor = null, int $maxResults = 500, string $deliveryType = 'upload'): array
    {
        $options = ['type' => $deliveryType, 'prefix' => 'ani-mart/', 'max_results' => max(1, min($maxResults, 500))];
        if ($nextCursor) {
            $options['next_cursor'] = $nextCursor;
        }
        $result = $this->cloudinary->adminApi()->assets($options);
        return [
            'resources' => (array) ($result['resources'] ?? []),
            'next_cursor' => $result['next_cursor'] ?? null,
        ];
    }

    private function registerAsset(array $asset): int
    {
        $this->ensureSchema();
        $retentionDays = $asset['retention_days'];
        $retainUntil = is_numeric($retentionDays) ? date('Y-m-d H:i:s', time() + ((int) $retentionDays * 86400)) : null;
        $stmt = $this->db()->prepare("INSERT INTO image_assets (public_id, secure_url, delivery_type, resource_type, format, bytes, visibility, owner_user_id, entity_type, entity_id, retain_until, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW(), NOW()) ON DUPLICATE KEY UPDATE secure_url = VALUES(secure_url), delivery_type = VALUES(delivery_type), resource_type = VALUES(resource_type), format = VALUES(format), bytes = VALUES(bytes), visibility = VALUES(visibility), owner_user_id = COALESCE(VALUES(owner_user_id), owner_user_id), entity_type = COALESCE(VALUES(entity_type), entity_type), entity_id = COALESCE(VALUES(entity_id), entity_id), retain_until = COALESCE(VALUES(retain_until), retain_until), status = 'active', updated_at = NOW(), id = LAST_INSERT_ID(id)");
        $stmt->execute([
            $asset['public_id'], $asset['secure_url'], $asset['delivery_type'], $asset['resource_type'],
            $asset['format'] ?: null, $asset['bytes'], $asset['visibility'], $asset['owner_user_id'] ?: null,
            $asset['entity_type'] ?: null, $asset['entity_id'] ?: null, $retainUntil,
        ]);
        return (int) $this->db()->lastInsertId();
    }

    private function markDeleted(string $publicId): void
    {
        try {
            $this->ensureSchema();
            $this->db()->prepare("UPDATE image_assets SET status = 'deleted', deleted_at = NOW(), last_delete_error = NULL, updated_at = NOW() WHERE public_id = ?")->execute([$publicId]);
        } catch (Throwable $e) {
            error_log('Unable to update deleted image registry record: ' . $e->getMessage());
        }
    }

    private function markDeleteFailed(string $publicId, string $error): void
    {
        try {
            $this->ensureSchema();
            $this->db()->prepare("UPDATE image_assets SET status = 'delete_failed', delete_attempts = delete_attempts + 1, last_delete_error = ?, delete_after = DATE_ADD(NOW(), INTERVAL 1 DAY), updated_at = NOW() WHERE public_id = ?")->execute([mb_substr($error, 0, 1000), $publicId]);
        } catch (Throwable $e) {
            error_log('Unable to record failed image deletion: ' . $e->getMessage());
        }
    }

    private function db(): PDO
    {
        return $this->db ??= Database::connection();
    }

    private function ensureSchema(): void
    {
        static $ready = false;
        if ($ready) {
            return;
        }
        SchemaInspector::requireColumns('image_assets', [
            'public_id', 'secure_url', 'delivery_type', 'resource_type', 'visibility',
            'owner_user_id', 'entity_type', 'entity_id', 'retain_until', 'delete_after',
            'status', 'delete_attempts', 'last_delete_error', 'deleted_at',
        ]);
        $ready = true;
    }
}
