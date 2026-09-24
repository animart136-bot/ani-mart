<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Services\SensitiveDataCipher;

$failures = [];
$assert = static function (bool $condition, string $message) use (&$failures): void {
    if (!$condition) {
        $failures[] = $message;
    }
};

$key = SensitiveDataCipher::generateEncodedKey();
$cipher = new SensitiveDataCipher($key);
$context = 'orders.shipping_address';
$plaintext = 'Poblacion, Saint Bernard, Southern Leyte';

$first = $cipher->encrypt($plaintext, $context);
$second = $cipher->encrypt($plaintext, $context);

$assert(is_string($first) && str_starts_with($first, 'enc:v1:'), 'Ciphertext uses a versioned format');
$assert($first !== $plaintext, 'Ciphertext does not contain the plaintext value');
$assert($first !== $second, 'A unique nonce produces different ciphertext for the same value');
$assert($cipher->decrypt($first, $context) === $plaintext, 'Authorized decryption restores the original value');
$assert($cipher->decrypt($plaintext, $context) === $plaintext, 'Legacy plaintext remains readable during migration');

try {
    $cipher->decrypt($first, 'users.address');
    $failures[] = 'Ciphertext cannot be moved to a different field context';
} catch (RuntimeException $expected) {
}

$tampered = substr($first, 0, -1) . ($first[-1] === 'A' ? 'B' : 'A');
try {
    $cipher->decrypt($tampered, $context);
    $failures[] = 'Modified ciphertext must be rejected';
} catch (RuntimeException $expected) {
}

try {
    (new SensitiveDataCipher(SensitiveDataCipher::generateEncodedKey()))->decrypt($first, $context);
    $failures[] = 'A different key must not decrypt the value';
} catch (RuntimeException $expected) {
}

if ($failures !== []) {
    fwrite(STDERR, "Sensitive-data encryption checks failed:\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "All sensitive-data encryption checks passed (8/8).\n";
