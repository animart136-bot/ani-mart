<?php

require_once __DIR__ . '/../bootstrap/app.php';

use App\Services\CloudinaryService;

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This command can only run from the command line.\n");
    exit(1);
}

$execute = in_array('--execute', $argv, true);
$graceDays = 30;
$limit = 100;
foreach ($argv as $argument) {
    if (str_starts_with($argument, '--grace-days=')) {
        $graceDays = max(1, (int) substr($argument, 13));
    }
    if (str_starts_with($argument, '--limit=')) {
        $limit = max(1, min(500, (int) substr($argument, 8)));
    }
}

$service = new CloudinaryService();
$pending = $execute
    ? $service->processPendingDeletions($limit)
    : ['processed' => 0, 'deleted' => 0, 'failed' => 0, 'skipped_in_dry_run' => true];
$known = $service->referencedPublicIds();
$cutoff = time() - ($graceDays * 86400);
$scanned = 0;
$orphans = [];

foreach (['upload', 'authenticated'] as $deliveryType) {
    $cursor = null;
    do {
        $page = $service->listRemoteAssets($cursor, 500, $deliveryType);
        foreach ($page['resources'] as $resource) {
            $scanned++;
            $publicId = (string) ($resource['public_id'] ?? '');
            $createdAt = strtotime((string) ($resource['created_at'] ?? '')) ?: time();
            if ($publicId !== '' && !isset($known[$publicId]) && $createdAt <= $cutoff) {
                $orphans[] = [
                    'public_id' => $publicId,
                    'type' => (string) ($resource['type'] ?? $deliveryType),
                    'created_at' => (string) ($resource['created_at'] ?? ''),
                ];
            }
        }
        $cursor = $page['next_cursor'];
    } while ($cursor && $scanned < 10000);
}

$removed = 0;
if ($execute) {
    foreach (array_slice($orphans, 0, $limit) as $orphan) {
        $result = $service->delete($orphan['public_id'], ['delivery_type' => $orphan['type']]);
        if (in_array((string) ($result['result'] ?? ''), ['ok', 'not found'], true)) {
            $removed++;
        }
    }
}

echo json_encode([
    'mode' => $execute ? 'execute' : 'dry-run',
    'pending_deletions' => $pending,
    'remote_assets_scanned' => $scanned,
    'unregistered_assets_older_than_grace_period' => count($orphans),
    'removed' => $removed,
    'grace_days' => $graceDays,
    'sample' => array_slice($orphans, 0, 25),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
