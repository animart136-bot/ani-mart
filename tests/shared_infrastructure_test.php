<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap/app.php';

use App\Services\MessageFormatter;
use App\Services\StatusPresenter;
use App\Services\TrustCasePolicy;

$failures = [];
$check = static function (bool $condition, string $message) use (&$failures): void {
    if (!$condition) $failures[] = $message;
};

$check(StatusPresenter::label('needs_more_evidence') === 'Needs More Evidence', 'Status labels are normalized.');
$check(StatusPresenter::badgeClass('resolved') === 'bg-success', 'Resolved status uses the success badge.');
$check(TrustCasePolicy::isFinal('complaint', 'closed'), 'Closed complaints are final.');
$check(!TrustCasePolicy::isFinal('report', 'closed'), 'Report lifecycle stays distinct from complaint lifecycle.');
$check(TrustCasePolicy::retentionDays('return_request') === 365, 'Evidence retention is centralized.');
$check(MessageFormatter::orderReference('Order #42', 'ORD-2026-42') === 'Order ID: ORD-2026-42', 'Order references are normalized.');

$conversation = (object) ['product_name' => null, 'order_id' => 42, 'order_number' => 'ORD-2026-42'];
$check(MessageFormatter::conversationSubject($conversation) === 'Order ID: ORD-2026-42', 'Conversation subjects are centralized.');

$controllerFiles = glob(dirname(__DIR__) . '/app/Controllers/*.php') ?: [];
foreach ($controllerFiles as $file) {
    $contents = (string) file_get_contents($file);
    $check(!preg_match('/\b(?:CREATE\s+TABLE|ALTER\s+TABLE)\b/i', $contents), basename($file) . ' must not mutate the schema at request time.');
}

if ($failures) {
    fwrite(STDERR, "Shared infrastructure checks failed:\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "Shared infrastructure checks passed.\n";
