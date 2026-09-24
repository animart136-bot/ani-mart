<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;
use SodiumException;

final class SensitiveDataCipher
{
    private const PREFIX = 'enc:v1:';
    private const KEY_ENV = 'DATA_ENCRYPTION_KEY_BASE64';

    private string $key;

    public function __construct(?string $encodedKey = null)
    {
        if (!function_exists('sodium_crypto_aead_xchacha20poly1305_ietf_encrypt')) {
            throw new RuntimeException('PHP Sodium is required for sensitive-data encryption.');
        }

        $encodedKey ??= $this->configuredKey();
        $key = sodium_base642bin(
            trim($encodedKey),
            SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING,
            ''
        );

        if (strlen($key) !== SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES) {
            throw new RuntimeException('The sensitive-data encryption key is invalid.');
        }

        $this->key = $key;
    }

    public static function generateEncodedKey(): string
    {
        return sodium_bin2base64(
            random_bytes(SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES),
            SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING
        );
    }

    public function encrypt(?string $plaintext, string $context): ?string
    {
        if ($plaintext === null || $plaintext === '') {
            return $plaintext;
        }

        if ($this->isEncrypted($plaintext)) {
            return $plaintext;
        }

        $nonce = random_bytes(SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES);
        $ciphertext = sodium_crypto_aead_xchacha20poly1305_ietf_encrypt(
            $plaintext,
            $context,
            $nonce,
            $this->key
        );

        return self::PREFIX . sodium_bin2base64(
            $nonce . $ciphertext,
            SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING
        );
    }

    public function decrypt(?string $storedValue, string $context): ?string
    {
        if ($storedValue === null || $storedValue === '' || !$this->isEncrypted($storedValue)) {
            return $storedValue;
        }

        try {
            $payload = sodium_base642bin(
                substr($storedValue, strlen(self::PREFIX)),
                SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING,
                ''
            );
        } catch (SodiumException $exception) {
            throw new RuntimeException('Encrypted sensitive data is malformed.', 0, $exception);
        }

        $nonceLength = SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES;
        if (strlen($payload) <= $nonceLength) {
            throw new RuntimeException('Encrypted sensitive data is incomplete.');
        }

        $plaintext = sodium_crypto_aead_xchacha20poly1305_ietf_decrypt(
            substr($payload, $nonceLength),
            $context,
            substr($payload, 0, $nonceLength),
            $this->key
        );

        if ($plaintext === false) {
            throw new RuntimeException('Sensitive data could not be authenticated or decrypted.');
        }

        return $plaintext;
    }

    public function isEncrypted(?string $value): bool
    {
        return is_string($value) && str_starts_with($value, self::PREFIX);
    }

    private function configuredKey(): string
    {
        $configured = $_ENV[self::KEY_ENV] ?? $_SERVER[self::KEY_ENV] ?? getenv(self::KEY_ENV);
        $configured = trim(is_string($configured) ? $configured : '');

        if ($configured !== '') {
            return $configured;
        }

        $appEnvironment = $_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? getenv('APP_ENV');
        if (strtolower((string) $appEnvironment) === 'production') {
            throw new RuntimeException(
                'DATA_ENCRYPTION_KEY_BASE64 must be configured outside the application in production.'
            );
        }

        $root = defined('ANI_MART_ROOT') ? ANI_MART_ROOT : dirname(__DIR__, 2);
        $keyDirectory = $root . '/storage/private/keys';
        $keyPath = $keyDirectory . '/data-encryption-v1.key';

        if (is_file($keyPath)) {
            $stored = file_get_contents($keyPath);
            if ($stored === false || trim($stored) === '') {
                throw new RuntimeException('The sensitive-data key file cannot be read.');
            }

            return trim($stored);
        }

        if (!is_dir($keyDirectory) && !mkdir($keyDirectory, 0700, true) && !is_dir($keyDirectory)) {
            throw new RuntimeException('The sensitive-data key directory cannot be created.');
        }

        $generated = self::generateEncodedKey();
        if (file_put_contents($keyPath, $generated . PHP_EOL, LOCK_EX) === false) {
            throw new RuntimeException('The sensitive-data key file cannot be created.');
        }
        @chmod($keyPath, 0600);

        return $generated;
    }
}
