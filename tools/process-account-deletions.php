<?php

require_once __DIR__ . '/../bootstrap/app.php';

use App\Services\AccountDeletionService;

try {
    $count = (new AccountDeletionService())->processDue();
    echo 'Completed account deletions: ' . $count . PHP_EOL;
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, 'Account deletion processing failed: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
