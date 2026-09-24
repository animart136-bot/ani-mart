<?php

require_once __DIR__ . '/../bootstrap/app.php';

try {
    $sent = (new App\Services\PriceReferenceService())->sendStaleReminders();
    echo $sent > 0
        ? "Price-reference reminders sent: {$sent}" . PHP_EOL
        : "Price references are current, or today's reminder was already sent." . PHP_EOL;
} catch (Throwable $e) {
    fwrite(STDERR, 'Price-reference freshness check failed: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
