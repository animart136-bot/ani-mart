<?php

require_once __DIR__ . '/../bootstrap/app.php';

use App\Core\Database;
use App\Services\CloudinaryService;

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This command can only run from the command line.\n");
    exit(1);
}

$db = Database::connection();
$service = new CloudinaryService();
$queries = [
    ['product', "SELECT p.id entity_id, sp.user_id owner_user_id, p.image image_url FROM products p LEFT JOIN seller_profiles sp ON sp.id = p.seller_profile_id"],
    ['product', "SELECT pi.product_id entity_id, sp.user_id owner_user_id, pi.image_url FROM product_images pi JOIN products p ON p.id = pi.product_id LEFT JOIN seller_profiles sp ON sp.id = p.seller_profile_id"],
    ['community_post', "SELECT cpi.post_id entity_id, cp.user_id owner_user_id, cpi.image_url FROM community_post_images cpi JOIN community_posts cp ON cp.id = cpi.post_id"],
    ['seller_profile', "SELECT fpi.seller_profile_id entity_id, sp.user_id owner_user_id, fpi.image_path image_url FROM farm_profile_images fpi JOIN seller_profiles sp ON sp.id = fpi.seller_profile_id"],
    ['seller_profile', "SELECT id entity_id, user_id owner_user_id, farm_banner image_url FROM seller_profiles"],
    ['seller_profile', "SELECT id entity_id, user_id owner_user_id, farm_logo image_url FROM seller_profiles"],
    ['user_profile', "SELECT id entity_id, id owner_user_id, profile_image image_url FROM users"],
    ['review', "SELECT id entity_id, user_id owner_user_id, review_photo image_url FROM reviews"],
    ['report', "SELECT re.report_id entity_id, r.reporter_id owner_user_id, re.image_url FROM report_evidence re JOIN reports r ON r.id = re.report_id"],
    ['complaint', "SELECT ce.complaint_id entity_id, ce.user_id owner_user_id, ce.image_url FROM complaint_evidence ce"],
    ['return_request', "SELECT re.return_request_id entity_id, re.user_id owner_user_id, re.image_url FROM return_evidence re"],
    ['order', "SELECT id entity_id, user_id owner_user_id, proof_image image_url FROM orders"],
    ['farmer_application', "SELECT id entity_id, user_id owner_user_id, rsbsa_card_path image_url FROM farmer_applications"],
    ['farmer_application', "SELECT id entity_id, user_id owner_user_id, seller_profile_image_path image_url FROM farmer_applications"],
    ['farmer_application', "SELECT id entity_id, user_id owner_user_id, shop_banner_path image_url FROM farmer_applications"],
];

$registered = 0;
$skipped = 0;
foreach ($queries as [$entityType, $sql]) {
    try {
        foreach ($db->query($sql)->fetchAll() as $row) {
            $url = trim((string) ($row->image_url ?? ''));
            if ($url === '') {
                continue;
            }
            $id = $service->registerExistingUrl($url, $entityType, (int) $row->entity_id, (int) $row->owner_user_id);
            $id ? $registered++ : $skipped++;
        }
    } catch (Throwable $e) {
        fwrite(STDERR, "Skipped {$entityType}: {$e->getMessage()}\n");
    }
}

echo json_encode(['registered_or_updated' => $registered, 'non_cloudinary_or_protected_urls_skipped' => $skipped], JSON_PRETTY_PRINT) . PHP_EOL;
