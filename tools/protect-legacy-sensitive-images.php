<?php

require_once __DIR__ . '/../bootstrap/app.php';

use App\Core\Database;
use App\Services\CloudinaryService;
use App\Services\SensitiveDataCipher;

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This command can only run from the command line.\n");
    exit(1);
}

$execute = in_array('--execute', $argv, true);
$db = Database::connection();
$service = new CloudinaryService();
$cipher = new SensitiveDataCipher();
$candidates = [];

$queries = [
    ['farmer_application', "SELECT id, user_id, rsbsa_card_path image_url FROM farmer_applications WHERE rsbsa_card_path LIKE 'https://res.cloudinary.com/%'"],
    ['report', "SELECT re.id row_id, re.report_id id, r.reporter_id user_id, re.image_url FROM report_evidence re JOIN reports r ON r.id = re.report_id WHERE re.image_url LIKE 'https://res.cloudinary.com/%'"],
    ['complaint', "SELECT id row_id, complaint_id id, user_id, image_url FROM complaint_evidence WHERE image_url LIKE 'https://res.cloudinary.com/%'"],
    ['return_request', "SELECT id row_id, return_request_id id, user_id, image_url FROM return_evidence WHERE image_url LIKE 'https://res.cloudinary.com/%'"],
];

foreach ($queries as [$entityType, $sql]) {
    foreach ($db->query($sql)->fetchAll() as $row) {
        $candidates[] = [$entityType, $row];
    }
}

if (!$execute) {
    echo json_encode(['mode' => 'dry-run', 'legacy_sensitive_assets' => count($candidates)], JSON_PRETTY_PRINT) . PHP_EOL;
    exit(0);
}

$protected = 0;
$failed = 0;
foreach ($candidates as [$entityType, $row]) {
    $service->registerExistingUrl((string) $row->image_url, $entityType, (int) $row->id, (int) $row->user_id);
    $result = $service->protectExistingAsset((string) $row->image_url);
    if (!$result) {
        $failed++;
        continue;
    }

    $url = (string) $result['url'];
    switch ($entityType) {
        case 'farmer_application':
            $db->prepare('UPDATE farmer_applications SET rsbsa_card_path = ?, rsbsa_card_path_encrypted = ? WHERE id = ?')
                ->execute([$url, $cipher->encrypt($url, 'farmer_applications.rsbsa_card_path'), (int) $row->id]);
            break;
        case 'report':
            $db->prepare('UPDATE report_evidence SET image_url = ? WHERE id = ?')->execute([$url, (int) $row->row_id]);
            break;
        case 'complaint':
            $db->prepare('UPDATE complaint_evidence SET image_url = ? WHERE id = ?')->execute([$url, (int) $row->row_id]);
            break;
        case 'return_request':
            $db->prepare('UPDATE return_evidence SET image_url = ? WHERE id = ?')->execute([$url, (int) $row->row_id]);
            break;
    }
    $protected++;
}

echo json_encode(['mode' => 'execute', 'protected' => $protected, 'failed' => $failed], JSON_PRETTY_PRINT) . PHP_EOL;
exit($failed > 0 ? 1 : 0);
