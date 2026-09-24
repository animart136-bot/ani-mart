<?php

require_once __DIR__ . '/../bootstrap/app.php';

use App\Services\MigrationRunner;

$command = $argv[1] ?? 'status';
$directory = ANI_MART_ROOT . '/database/migrations';
$runner = new MigrationRunner();

try {
    if ($command === 'migrate') {
        $items = $runner->migrate($directory);
        echo $items ? "Applied:\n- " . implode("\n- ", $items) . "\n" : "Database is up to date.\n";
        exit(0);
    }

    if ($command === 'baseline') {
        if (!in_array('--confirm-existing-schema', $argv, true)) {
            fwrite(STDERR, "Baseline records migrations without executing them. Re-run with --confirm-existing-schema after verifying this database is already provisioned.\n");
            exit(2);
        }
        $items = $runner->baseline($directory);
        echo $items ? "Baselined:\n- " . implode("\n- ", $items) . "\n" : "No migrations needed baselining.\n";
        exit(0);
    }

    if ($command !== 'status') {
        fwrite(STDERR, "Usage: php tools/migrate.php [status|migrate|baseline --confirm-existing-schema]\n");
        exit(2);
    }

    foreach ($runner->status($directory) as $migration) {
        $integrity = $migration['checksum_valid'] ? '' : ' (checksum mismatch)';
        echo str_pad($migration['status'], 9) . ' ' . $migration['migration'] . $integrity . "\n";
    }
} catch (Throwable $error) {
    fwrite(STDERR, 'Migration failed: ' . $error->getMessage() . "\n");
    exit(1);
}
