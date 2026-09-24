<?php

namespace App\Services;

use InvalidArgumentException;

/** Shared normalization and Cloudinary upload entry point for image fields. */
final class ImageUploadService
{
    public function __construct(private ?CloudinaryService $cloudinary = null)
    {
        $this->cloudinary ??= new CloudinaryService();
    }

    public function publicField(string $field, string $folder, array $options = []): ?array
    {
        $file = $_FILES[$field] ?? null;
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        $this->assertSuccessful($file);
        return $this->cloudinary->uploadPublic($file, $this->folder($folder), $options);
    }

    public function publicFile(array $file, string $folder, array $options = []): ?array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        $this->assertSuccessful($file);
        return $this->cloudinary->uploadPublic($file, $this->folder($folder), $options);
    }

    public function sensitiveField(string $field, string $folder, array $options = []): ?array
    {
        $file = $_FILES[$field] ?? null;
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        $this->assertSuccessful($file);
        return $this->cloudinary->uploadSensitive($file, $this->folder($folder), $options);
    }

    public function publicFiles(string $field, string $folder, array $options = []): array
    {
        $group = $_FILES[$field] ?? null;
        if (!$group || !is_array($group['name'] ?? null)) {
            return [];
        }

        $uploads = [];
        foreach ($group['name'] as $index => $name) {
            $file = [
                'name' => $name,
                'tmp_name' => $group['tmp_name'][$index] ?? '',
                'size' => $group['size'][$index] ?? 0,
                'type' => $group['type'][$index] ?? '',
                'error' => $group['error'][$index] ?? UPLOAD_ERR_NO_FILE,
            ];
            if ($file['error'] === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $this->assertSuccessful($file);
            $upload = $this->cloudinary->uploadPublic($file, $this->folder($folder), $options);
            if ($upload) {
                $uploads[] = $upload;
            }
        }
        return $uploads;
    }

    public static function errorMessage(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE => 'The image is larger than the server upload limit.',
            UPLOAD_ERR_FORM_SIZE => 'The image is larger than the form upload limit.',
            UPLOAD_ERR_PARTIAL => 'The image upload was interrupted. Please try again.',
            UPLOAD_ERR_NO_TMP_DIR => 'The server upload temp folder is missing.',
            UPLOAD_ERR_CANT_WRITE => 'The server could not write the uploaded image.',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the image upload.',
            default => 'The image could not be uploaded.',
        };
    }

    private function assertSuccessful(array $file): void
    {
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException(self::errorMessage($error));
        }
    }

    private function folder(string $folder): string
    {
        return 'ani-mart/' . trim(preg_replace('#^ani-mart/#', '', $folder), '/');
    }
}
