<?php

namespace App\Helpers;

use App\Core\Session;

class UploadValidator
{
    private const DEFAULT_MAX_SIZE = 5 * 1024 * 1024;
    private const ALLOWED_IMAGE_MIME_TYPES = [
        'image/jpeg' => ['jpg', 'jpeg'],
        'image/png' => ['png'],
        'image/webp' => ['webp'],
    ];

    private const DANGEROUS_EXTENSIONS = [
        'php', 'phtml', 'php5', 'phar', 'cgi', 'pl', 'sh', 'exe', 'bat', 'cmd', 'scr', 'js', 'jar'
    ];

    private array $config;

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'max_size' => self::DEFAULT_MAX_SIZE,
            'allowed_mime_types' => array_keys(self::ALLOWED_IMAGE_MIME_TYPES),
            'allowed_extensions' => ['jpg', 'jpeg', 'png', 'webp'],
            'image_only' => true,
            'field_name' => 'file',
            'error_message' => 'Invalid file. Please upload a JPG, PNG, or WebP file under 5 MB.',
        ], $config);
    }

    public function validate(array $file): array
    {
        $result = [
            'ok' => false,
            'message' => $this->config['error_message'],
            'path' => null,
        ];

        if (!is_array($file)) {
            return $result;
        }

        $errorCode = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($errorCode !== UPLOAD_ERR_OK) {
            $result['message'] = 'Upload failed. Please try again.';
            return $result;
        }

        $tmpName = $file['tmp_name'] ?? '';
        $allowLocalPath = (bool) ($this->config['allow_local_path'] ?? false);
        $isValidUploadSource = is_string($tmpName)
            && $tmpName !== ''
            && is_file($tmpName)
            && ($allowLocalPath || is_uploaded_file($tmpName));

        if (!$isValidUploadSource) {
            $result['message'] = 'Upload failed. Please try again.';
            return $result;
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0 || $size > $this->config['max_size']) {
            $result['message'] = 'The selected file is too large.';
            return $result;
        }

        $name = (string) ($file['name'] ?? '');
        $safeName = $this->normalizeFilename($name);
        if ($safeName === '' || $this->hasDangerousName($safeName)) {
            $result['message'] = 'Invalid file name.';
            return $result;
        }

        $extension = strtolower(pathinfo($safeName, PATHINFO_EXTENSION));
        if ($extension === '' || !in_array($extension, $this->config['allowed_extensions'], true)) {
            $result['message'] = $this->config['error_message'];
            return $result;
        }

        $mimeType = $this->detectMimeType($tmpName, $file['type'] ?? '');
        if ($mimeType === null || !in_array($mimeType, $this->config['allowed_mime_types'], true)) {
            $result['message'] = $this->config['error_message'];
            return $result;
        }

        if ($this->config['image_only'] && !$this->isValidImage($tmpName, $mimeType)) {
            $result['message'] = $this->config['error_message'];
            return $result;
        }

        $result['ok'] = true;
        $result['path'] = $tmpName;
        $result['mime_type'] = $mimeType;
        $result['extension'] = $extension;
        $result['size'] = $size;
        return $result;
    }

    public function flashError(?string $message = null): void
    {
        Session::flash('error', $message ?? $this->config['error_message']);
    }

    public function storeLocalFile(array $file, string $destinationDir): array
    {
        $validation = $this->validate($file);
        if (!$validation['ok']) {
            return [
                'ok' => false,
                'message' => $validation['message'],
                'path' => null,
            ];
        }

        $resolvedDir = $this->prepareDestinationDirectory($destinationDir);
        if ($resolvedDir === null) {
            return [
                'ok' => false,
                'message' => 'The upload folder could not be prepared.',
                'path' => null,
            ];
        }

        $storedName = $this->generateSafeFilename((string) $validation['extension']);
        $targetPath = $resolvedDir . DIRECTORY_SEPARATOR . $storedName;

        if (!move_uploaded_file($validation['path'], $targetPath)) {
            return [
                'ok' => false,
                'message' => 'We could not save the uploaded file.',
                'path' => null,
            ];
        }

        @chmod($targetPath, 0644);

        return [
            'ok' => true,
            'message' => '',
            'path' => $targetPath,
            'stored_name' => $storedName,
            'extension' => $validation['extension'],
            'destination_dir' => $resolvedDir,
        ];
    }

    private function normalizeFilename(string $name): string
    {
        $name = trim(basename($name));
        return str_replace(["\n", "\r", "\t"], '', $name);
    }

    private function hasDangerousName(string $name): bool
    {
        $lowerName = strtolower($name);
        if (str_contains($lowerName, '..')) {
            return true;
        }

        foreach (self::DANGEROUS_EXTENSIONS as $extension) {
            if (str_ends_with($lowerName, '.' . $extension)) {
                return true;
            }
        }

        $segments = explode('.', $lowerName);
        if (count($segments) >= 2) {
            $last = end($segments);
            $beforeLast = prev($segments);
            if (in_array($last, self::DANGEROUS_EXTENSIONS, true) || in_array($beforeLast, self::DANGEROUS_EXTENSIONS, true)) {
                return true;
            }
        }

        return false;
    }

    private function detectMimeType(string $tmpName, string $clientMime): ?string
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo !== false) {
            $detected = finfo_file($finfo, $tmpName);
            finfo_close($finfo);
            if (is_string($detected) && $detected !== '') {
                return strtolower($detected);
            }
        }

        return $clientMime !== '' ? strtolower($clientMime) : null;
    }

    private function prepareDestinationDirectory(string $destinationDir): ?string
    {
        $cleanDir = rtrim($destinationDir, DIRECTORY_SEPARATOR);
        if ($cleanDir === '') {
            return null;
        }

        if (!is_dir($cleanDir)) {
            if (!mkdir($cleanDir, 0775, true) && !is_dir($cleanDir)) {
                return null;
            }
        }

        if (!is_dir($cleanDir) || !is_writable($cleanDir)) {
            return null;
        }

        $resolvedDir = realpath($cleanDir);
        return $resolvedDir !== false ? $resolvedDir : null;
    }

    private function generateSafeFilename(string $extension): string
    {
        try {
            $randomPart = bin2hex(random_bytes(16));
        } catch (\Throwable $exception) {
            $randomPart = uniqid('', true);
        }

        $safeExtension = strtolower(preg_replace('/[^a-z0-9]+/', '', $extension) ?: 'bin');
        return $randomPart . '.' . $safeExtension;
    }

    private function isValidImage(string $tmpName, string $mimeType): bool
    {
        if (!in_array($mimeType, array_keys(self::ALLOWED_IMAGE_MIME_TYPES), true)) {
            return false;
        }

        $imageInfo = @getimagesize($tmpName);
        if ($imageInfo === false) {
            return false;
        }

        return true;
    }
}
