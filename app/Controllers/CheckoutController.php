<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Controllers\NotificationController;
use App\Services\AuditLogService;
use App\Services\MarketplaceRulesService;
use App\Services\InventoryNotificationService;
use App\Services\OrderWorkflowSchemaService;
use App\Services\SensitiveDataCipher;
use App\Services\SchemaInspector;

class CheckoutController extends Controller
{
    private function ensureShopDeliveryFeeSchema($db): void
    {
        SchemaInspector::requireColumns('seller_profiles', ['delivery_fee']);
        SchemaInspector::requireColumns('product_variants', [
            'product_id', 'variant_name', 'weight', 'unit', 'retail_price', 'wholesale_price',
            'minimum_wholesale_quantity', 'stock', 'is_active',
        ]);
        SchemaInspector::requireColumns('inventory_stock_alerts', [
            'seller_user_id', 'product_id', 'entity_type', 'entity_id', 'stock_level', 'last_stock', 'last_notified_at',
        ]);
        SchemaInspector::requireColumns('cart', [
            'product_variant_id', 'variant_label', 'variant_weight', 'variant_unit', 'price_type', 'unit_price',
        ]);
        SchemaInspector::requireColumns('order_items', [
            'product_variant_id', 'variant_label', 'variant_weight', 'variant_unit', 'price_type', 'unit_price',
        ]);
        SchemaInspector::requireColumns('orders', [
            'total_amount', 'shipping_address', 'shipping_address_encrypted', 'payment_method',
            'delivery_latitude', 'delivery_longitude', 'delivery_address', 'delivery_address_encrypted',
            'delivery_barangay', 'delivery_municipality', 'delivery_province', 'delivery_distance',
            'delivery_fee', 'requested_delivery_date', 'delivery_slot', 'substitution_preference',
            'customer_note', 'customer_note_encrypted',
        ]);
        SchemaInspector::requireColumns('users', ['address_encrypted', 'phone_encrypted']);
    }

    private function configuredMunicipality(): string
    {
        return trim((string) ($_ENV['ANI_MART_MUNICIPALITY'] ?? $_ENV['MUNICIPALITY_NAME'] ?? $_ENV['MUNICIPALITY'] ?? 'Saint Bernard'));
    }

    private function defaultMapLocation(): array
    {
        return [
            'latitude' => (float) ($_ENV['ANI_MART_DEFAULT_LATITUDE'] ?? 10.2833),
            'longitude' => (float) ($_ENV['ANI_MART_DEFAULT_LONGITUDE'] ?? 125.1700),
            'zoom' => 13,
        ];
    }

    private function normalizeMunicipalityName($value): string
    {
        $value = strtolower(trim((string) $value));
        return preg_replace('/[^a-z0-9]+/', '', $value) ?? '';
    }

    private function allowedDeliveryBarangays(): array
    {
        return [
            'Atuyan', 'Ayahag', 'Bantawon', 'Bolodbolod', 'Cabagawan',
            'Carnaga', 'Catmon', 'Guinsaugon', 'Himatagon', 'Himbangan',
            'Himos-onan', 'Hindag-an', 'Hinabian', 'Kauswagan', 'Libas',
            'Lipanto', 'Magatas', 'Magbagacay', 'Mahayag', 'Mahayahay',
            'Malibago', 'Malinao', 'Nueva Esperanza', 'Panian', 'San Isidro',
            'Santa Cruz', 'Sug-angon', 'Tabontabon', 'Tambis I', 'Tambis II',
        ];
    }

    private function isAllowedDeliveryBarangay(string $barangay): bool
    {
        $normalized = $this->normalizeMunicipalityName($barangay);

        foreach ($this->allowedDeliveryBarangays() as $allowedBarangay) {
            if ($normalized === $this->normalizeMunicipalityName($allowedBarangay)) {
                return true;
            }
        }

        return false;
    }

    private function calculateDistanceKm($lat1, $lng1, $lat2, $lng2): float
    {
        if ($lat1 === null || $lng1 === null || $lat2 === null || $lng2 === null) {
            return 0.0;
        }

        $earthRadiusKm = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $lat1Rad = deg2rad($lat1);
        $lat2Rad = deg2rad($lat2);

        $a = sin($dLat / 2) * sin($dLat / 2) + sin($dLng / 2) * sin($dLng / 2) * cos($lat1Rad) * cos($lat2Rad);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadiusKm * $c, 1);
    }

    private function estimatedDeliveryTime(float $distance): string
    {
        if ($distance <= 2) {
            return '15-20 minutes';
        }
        if ($distance <= 5) {
            return '20-30 minutes';
        }
        if ($distance <= 10) {
            return '25-35 minutes';
        }
        return '30-45 minutes';
    }

    private function getUserLocation($db, $userId)
    {
        $stmt = $db->prepare("
            SELECT latitude, longitude, address, address_encrypted, barangay, municipality, province
            FROM users
            WHERE id = ?
            LIMIT 1
        ");

        $stmt->execute([$userId]);
        $location = $stmt->fetch();
        if ($location) {
            $location->address = sensitive_value($location, 'address', 'users.address', '');
            $location->address = $this->normalizeDeliveryStreet(
                (string) $location->address,
                (string) ($location->barangay ?? ''),
                (string) ($location->municipality ?? ''),
                (string) ($location->province ?? '')
            );
        }
        return $location;
    }

    private function normalizeDeliveryStreet(string $address, string $barangay = '', string $municipality = '', string $province = ''): string
    {
        $address = trim((string) preg_replace('/\s*\|\s*Phone:.*$/i', '', $address));
        if ($address === '') {
            return '';
        }

        $localityParts = array_filter([
            $barangay,
            $municipality,
            $province,
            'Saint Bernard',
            'Southern Leyte',
            'Eastern Visayas',
            'Region VIII',
            'Region 8',
            'Philippines',
        ]);
        $normalize = static fn (string $value): string => strtolower((string) preg_replace('/[^a-z0-9]+/i', '', trim($value)));
        $localities = array_fill_keys(array_map($normalize, $localityParts), true);
        $streetParts = [];
        $seen = [];

        foreach (preg_split('/\s*,\s*/', $address) ?: [] as $part) {
            $part = trim($part);
            $key = $normalize($part);
            if ($part === '' || isset($localities[$key]) || isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $streetParts[] = $part;
        }

        return trim(implode(', ', $streetParts));
    }

    private function getNearestFarmers($db, $userLocation, $items)
    {
        $sellerIds = [];
        foreach ($items as $item) {
            if (!empty($item->seller_profile_id)) {
                $sellerIds[] = (int) $item->seller_profile_id;
            }
        }

        $sellerIds = array_values(array_unique($sellerIds));
        if (empty($sellerIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($sellerIds), '?'));
        $stmt = $db->prepare("
            SELECT id, shop_name, verification_badge, trust_score, average_delivery_time, latitude, longitude, barangay, city
            FROM seller_profiles
            WHERE id IN ($placeholders)
              AND status = 'approved'
            ORDER BY trust_score DESC, id DESC
            LIMIT 4
        ");

        $stmt->execute($sellerIds);
        $farmers = $stmt->fetchAll();

        if (!empty($userLocation->latitude) && !empty($userLocation->longitude)) {
            foreach ($farmers as $farmer) {
                $farmer->distance_km = $this->calculateDistanceKm(
                    (float) $userLocation->latitude,
                    (float) $userLocation->longitude,
                    (float) ($farmer->latitude ?? 0),
                    (float) ($farmer->longitude ?? 0)
                );
            }

            usort($farmers, function ($a, $b) {
                return ($a->distance_km ?? 9999) <=> ($b->distance_km ?? 9999);
            });
        }

        return $farmers;
    }

    public function index()
    {
        $user = Session::get('user');
        if (!$user) {
            header('Location: /ani-mart/public/login');
            exit;
        }

        $cartIds = $_SESSION['selected_cart_ids'] ?? [];
        if (empty($cartIds)) {
            header('Location: /ani-mart/public/cart');
            exit;
        }

        $db = Database::connection();
        $this->ensureShopDeliveryFeeSchema($db);
        (new OrderWorkflowSchemaService())->ensure();

        if (empty($user['phone'])) {
            $phoneStmt = $db->prepare('SELECT phone FROM users WHERE id = ? LIMIT 1');
            $phoneStmt->execute([(int) $user['id']]);
            $storedPhone = trim((string) ($phoneStmt->fetchColumn() ?? ''));
            if ($storedPhone !== '') {
                $user['phone'] = $storedPhone;
                Session::set('user', $user);
            }
        }

        $cartIds = array_values(array_unique(array_filter(array_map('intval', $cartIds), fn($id) => $id > 0)));
        if (empty($cartIds)) {
            header('Location: /ani-mart/public/cart');
            exit;
        }

        $placeholders = implode(',', array_fill(0, count($cartIds), '?'));

        $stmt = $db->prepare("
            SELECT
                cart.id AS cart_id,
                cart.quantity,
                cart.product_variant_id,
                COALESCE(cart.variant_label, product_variants.variant_name) AS variant_label,
                COALESCE(cart.variant_weight, product_variants.weight) AS variant_weight,
                COALESCE(cart.variant_unit, product_variants.unit) AS variant_unit,
                products.id AS product_id,
                products.name,
                products.image,
                CASE WHEN cart.product_variant_id IS NULL THEN products.stock ELSE product_variants.stock END AS stock,
                COALESCE(cart.unit_price, product_variants.retail_price, product_variants.wholesale_price, products.price) AS price,
                seller_profiles.id AS seller_profile_id,
                seller_profiles.user_id AS seller_user_id,
                seller_profiles.delivery_fee AS shop_delivery_fee,
                seller_profiles.shop_name,
                seller_profiles.verification_badge,
                seller_profiles.trust_score,
                seller_profiles.average_delivery_time,
                seller_profiles.latitude,
                seller_profiles.longitude,
                seller_profiles.barangay,
                seller_profiles.city
            FROM cart
            INNER JOIN products ON products.id = cart.product_id
            LEFT JOIN product_variants ON product_variants.id = cart.product_variant_id
                AND product_variants.product_id = products.id
            LEFT JOIN seller_profiles ON seller_profiles.id = products.seller_profile_id
            WHERE cart.id IN ($placeholders)
              AND cart.user_id = ?
        ");

        $stmt->execute(array_merge($cartIds, [$user['id']]));
        $items = $stmt->fetchAll();

        foreach ($items as $item) {
            if ((int) $item->seller_user_id === (int) $user['id']) {
                Session::flash('error', 'You cannot purchase a product from your own shop.');
                return $this->redirect('/ani-mart/public/cart');
            }
        }

        $total = 0;
        $shopDeliveryFees = [];
        foreach ($items as $item) {
            $total += $item->price * $item->quantity;
            $sellerProfileId = (int) ($item->seller_profile_id ?? 0);
            if ($sellerProfileId > 0) {
                $shopDeliveryFees[$sellerProfileId] = max(0, (float) ($item->shop_delivery_fee ?? 0));
            }
        }
        $shopDeliveryFee = array_sum($shopDeliveryFees);
        $hasUnavailableItems = false;
        foreach ($items as $item) {
            if ((int) ($item->stock ?? 0) < (int) ($item->quantity ?? 0)) {
                $hasUnavailableItems = true;
                break;
            }
        }

        $userLocation = $this->getUserLocation($db, $user['id']);
        $nearestFarmers = $this->getNearestFarmers($db, $userLocation, $items);

        return $this->view('checkout/index', [
            'items' => $items,
            'total' => $total,
            'shopDeliveryFee' => $shopDeliveryFee,
            'hasUnavailableItems' => $hasUnavailableItems,
            'user' => $user,
            'userLocation' => $userLocation,
            'configuredMunicipality' => $this->configuredMunicipality(),
            'defaultMapLocation' => $this->defaultMapLocation(),
            'mapStyleUrl' => (require __DIR__ . '/../../config/map.php')['maplibre_style_url'],
            'nearestFarmers' => $nearestFarmers,
            'pageCss' => '/ani-mart/public/assets/css/pages/checkout.css',
        ]);
    }

    public function process()
    {
         csrf_verify();
         
        $user = Session::get('user');
        if (!$user) {
            header('Location: /ani-mart/public/login');
            exit;
        }

        $cartIds = $_SESSION['selected_cart_ids'] ?? [];
        if (empty($cartIds)) {
            header('Location: /ani-mart/public/cart');
            exit;
        }

        $db = Database::connection();
        $this->ensureShopDeliveryFeeSchema($db);
        $cartIds = array_values(array_unique(array_filter(array_map('intval', $cartIds), fn($id) => $id > 0)));
        if (empty($cartIds)) {
            header('Location: /ani-mart/public/cart');
            exit;
        }

        $placeholders = implode(',', array_fill(0, count($cartIds), '?'));

        $stmt = $db->prepare("
            SELECT
                cart.*,
                COALESCE(cart.unit_price, product_variants.retail_price, product_variants.wholesale_price, products.price) AS price,
                CASE WHEN cart.product_variant_id IS NULL THEN products.stock ELSE product_variants.stock END AS stock,
                COALESCE(cart.variant_label, product_variants.variant_name) AS selected_variant_label,
                COALESCE(cart.variant_weight, product_variants.weight) AS selected_variant_weight,
                COALESCE(cart.variant_unit, product_variants.unit) AS selected_variant_unit,
                products.seller_profile_id,
                seller_profiles.user_id AS seller_user_id,
                seller_profiles.delivery_fee AS shop_delivery_fee,
                seller_profiles.latitude AS farm_latitude,
                seller_profiles.longitude AS farm_longitude
            FROM cart
            INNER JOIN products ON products.id = cart.product_id
            LEFT JOIN product_variants ON product_variants.id = cart.product_variant_id
                AND product_variants.product_id = products.id
            LEFT JOIN seller_profiles ON seller_profiles.id = products.seller_profile_id
            WHERE cart.id IN ($placeholders)
              AND cart.user_id = ?
        ");

        $stmt->execute(array_merge($cartIds, [$user['id']]));
        $items = $stmt->fetchAll();

        $uniqueCartIds = array_unique($cartIds);
        if (count($items) !== count($uniqueCartIds)) {
            Session::flash('error', 'Some selected cart items are no longer available. Please review your cart.');
            return $this->redirect('/ani-mart/public/cart');
        }


        foreach ($items as $item) {
            if ((int) $item->seller_user_id === (int) $user['id']) {
                Session::flash('error', 'You cannot purchase a product from your own shop.');
                return $this->redirect('/ani-mart/public/cart');
            }
        }

        $db->beginTransaction();
        try {
            $productIds = array_values(array_unique(array_map(function ($item) {
                return $item->product_id;
            }, $items)));

            if (!empty($productIds)) {
                $productPlaceholders = implode(',', array_fill(0, count($productIds), '?'));
                $stockStmt = $db->prepare("SELECT id, stock FROM products WHERE id IN ($productPlaceholders) FOR UPDATE");
                $stockStmt->execute($productIds);
                $lockedProducts = [];
                foreach ($stockStmt->fetchAll() as $productRow) {
                    $lockedProducts[$productRow->id] = $productRow->stock;
                }

                $requiredQuantities = [];
                foreach ($items as $item) {
                    if ((int) ($item->product_variant_id ?? 0) === 0) {
                        $requiredQuantities[$item->product_id] = ($requiredQuantities[$item->product_id] ?? 0) + $item->quantity;
                    }
                }

                foreach ($requiredQuantities as $productId => $requiredQuantity) {
                    $availableStock = $lockedProducts[$productId] ?? 0;
                    if ($availableStock < $requiredQuantity) {
                        $db->rollBack();
                        Session::flash('error', 'Unable to place your order. One or more products no longer have enough stock.');
                        return $this->redirect('/ani-mart/public/checkout');
                    }
                }
            }

            $variantIds = array_values(array_unique(array_filter(array_map(
                static fn($item) => (int) ($item->product_variant_id ?? 0),
                $items
            ))));
            if ($variantIds !== []) {
                $variantPlaceholders = implode(',', array_fill(0, count($variantIds), '?'));
                $variantStockStmt = $db->prepare("SELECT id, stock FROM product_variants WHERE id IN ($variantPlaceholders) FOR UPDATE");
                $variantStockStmt->execute($variantIds);
                $lockedVariants = [];
                foreach ($variantStockStmt->fetchAll() as $variantRow) {
                    $lockedVariants[(int) $variantRow->id] = (int) $variantRow->stock;
                }

                foreach ($items as $item) {
                    $variantId = (int) ($item->product_variant_id ?? 0);
                    if ($variantId > 0 && ($lockedVariants[$variantId] ?? 0) < (int) $item->quantity) {
                        $db->rollBack();
                        Session::flash('error', 'A selected product size no longer has enough stock.');
                        return $this->redirect('/ani-mart/public/checkout');
                    }
                }
            }

            $total = 0;
            foreach ($items as $item) {
                $total += $item->price * $item->quantity;
            }

            $deliveryAddress = trim((string) ($_POST['delivery_address'] ?? ''));
            if ($deliveryAddress === '') {
                $deliveryAddress = trim((string) ($_POST['delivery_street'] ?? $_POST['address'] ?? ''));
            }
            $deliveryBarangay = trim((string) ($_POST['delivery_barangay'] ?? ''));
            $deliveryMunicipality = trim((string) ($_POST['delivery_municipality'] ?? ''));
            $deliveryProvince = trim((string) ($_POST['delivery_province'] ?? ''));
            $deliveryAddress = $this->normalizeDeliveryStreet(
                $deliveryAddress,
                $deliveryBarangay,
                $deliveryMunicipality,
                $deliveryProvince
            );
            $deliveryPhone = trim((string) ($_POST['delivery_phone'] ?? ''));
            $requestedDeliveryDate = trim((string) ($_POST['requested_delivery_date'] ?? ''));
            $deliverySlot = trim((string) ($_POST['delivery_slot'] ?? ''));
            $substitutionPreference = trim((string) ($_POST['substitution_preference'] ?? 'contact_me'));
            $customerNote = trim((string) ($_POST['customer_note'] ?? ''));
            $rawDeliveryLat = trim((string) ($_POST['delivery_latitude'] ?? ''));
            $rawDeliveryLng = trim((string) ($_POST['delivery_longitude'] ?? ''));
            $deliveryLat = is_numeric($rawDeliveryLat) ? (float) $rawDeliveryLat : null;
            $deliveryLng = is_numeric($rawDeliveryLng) ? (float) $rawDeliveryLng : null;
            $paymentMethod = MarketplaceRulesService::canonicalPaymentMethod(
                $_POST['payment_method'] ?? 'cash_on_delivery'
            );
            if ($paymentMethod === null) {
                $db->rollBack();
                Session::flash('error', 'The selected payment method is not currently available.');
                return $this->redirect('/ani-mart/public/checkout');
            }
            $configuredMunicipality = $this->configuredMunicipality();

            $hasInvalidCoordinates = ($deliveryLat !== null && $deliveryLng === null)
                || ($deliveryLat === null && $deliveryLng !== null)
                || ($deliveryLat !== null && ($deliveryLat < -90 || $deliveryLat > 90))
                || ($deliveryLng !== null && ($deliveryLng < -180 || $deliveryLng > 180));

            if ($hasInvalidCoordinates || $deliveryAddress === '') {
                $db->rollBack();
                Session::flash('error', 'Please enter your delivery address. You may optionally use the map to choose a precise location.');
                return $this->redirect('/ani-mart/public/checkout');
            }

            $today = new \DateTimeImmutable('today', new \DateTimeZone('Asia/Manila'));
            $requestedDate = \DateTimeImmutable::createFromFormat('!Y-m-d', $requestedDeliveryDate, new \DateTimeZone('Asia/Manila'));
            $validDate = $requestedDate
                && $requestedDate->format('Y-m-d') === $requestedDeliveryDate
                && $requestedDate >= $today
                && $requestedDate <= $today->modify('+7 days');
            if (!$validDate || !in_array($deliverySlot, ['morning', 'afternoon', 'evening'], true)) {
                $db->rollBack();
                Session::flash('error', 'Choose a delivery date within the next 7 days and an available time window.');
                return $this->redirect('/ani-mart/public/checkout');
            }
            if (!in_array($substitutionPreference, ['contact_me', 'no_substitutions', 'best_available'], true)) {
                $substitutionPreference = 'contact_me';
            }
            $customerNote = mb_substr($customerNote, 0, 500);

            $isConfiguredMunicipality = $this->normalizeMunicipalityName($deliveryMunicipality)
                === $this->normalizeMunicipalityName($configuredMunicipality);
            $isSouthernLeyte = $this->normalizeMunicipalityName($deliveryProvince) === 'southernleyte';

            if (!$isConfiguredMunicipality || !$isSouthernLeyte || !$this->isAllowedDeliveryBarangay($deliveryBarangay)) {
                $db->rollBack();
                Session::flash('error', 'Delivery is available only in the official barangays of Saint Bernard, Southern Leyte.');
                return $this->redirect('/ani-mart/public/checkout');
            }

            // Store canonical service-area names instead of trusting editable request text.
            $deliveryMunicipality = 'Saint Bernard';
            $deliveryProvince = 'Southern Leyte';

            $nearestDistance = null;
            if ($deliveryLat !== null && $deliveryLng !== null) {
                foreach ($items as $item) {
                    if (!empty($item->farm_latitude) && !empty($item->farm_longitude)) {
                        $distance = $this->calculateDistanceKm($deliveryLat, $deliveryLng, $item->farm_latitude, $item->farm_longitude);
                        if ($nearestDistance === null || $distance < $nearestDistance) {
                            $nearestDistance = $distance;
                        }
                    }
                }
            }
            $deliveryDistance = $nearestDistance ?? 0.0;

        // Delivery pricing belongs to each shop. Charge one configured fee per seller in the order.
        $shopDeliveryFees = [];
        foreach ($items as $item) {
            $sellerProfileId = (int) ($item->seller_profile_id ?? 0);
            if ($sellerProfileId > 0) {
                $shopDeliveryFees[$sellerProfileId] = max(0, (float) ($item->shop_delivery_fee ?? 0));
            }
        }
        $deliveryFee = array_sum($shopDeliveryFees);

        $fullAddress = trim(implode(', ', array_filter([
            $deliveryAddress,
            $deliveryBarangay,
            $deliveryMunicipality,
            $deliveryProvince,
        ])));
        if ($deliveryPhone !== '') {
            $fullAddress = trim($fullAddress . ' | Phone: ' . $deliveryPhone);
        }

        $orderNumber = MarketplaceRulesService::orderNumber();
        $cipher = new SensitiveDataCipher();
        $shippingAddressEncrypted = $cipher->encrypt($fullAddress, 'orders.shipping_address');
        $deliveryAddressEncrypted = $cipher->encrypt($fullAddress, 'orders.delivery_address');
        $customerNoteEncrypted = $cipher->encrypt($customerNote !== '' ? $customerNote : null, 'orders.customer_note');

        $stmt = $db->prepare("
            INSERT INTO orders (
                user_id,
                order_number,
                total_amount,
                shipping_address,
                shipping_address_encrypted,
                payment_method,
                status,
                created_at,
                delivery_latitude,
                delivery_latitude_encrypted,
                delivery_longitude,
                delivery_longitude_encrypted,
                delivery_address,
                delivery_address_encrypted,
                delivery_barangay,
                delivery_barangay_encrypted,
                delivery_municipality,
                delivery_municipality_encrypted,
                delivery_province,
                delivery_province_encrypted,
                delivery_distance,
                delivery_fee,
                requested_delivery_date,
                delivery_slot,
                substitution_preference,
                customer_note
                , customer_note_encrypted
            )
            VALUES (
                ?, ?, ?, ?, ?, ?,
                'pending',
                NOW(),
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            )
        ");

        $stmt->execute([
            $user['id'],
            $orderNumber,
            $total + $deliveryFee,
            $fullAddress,
            $shippingAddressEncrypted,
            $paymentMethod,
            $deliveryLat,
            $cipher->encrypt((string) $deliveryLat, 'orders.delivery_latitude'),
            $deliveryLng,
            $cipher->encrypt((string) $deliveryLng, 'orders.delivery_longitude'),
            $fullAddress,
            $deliveryAddressEncrypted,
            $deliveryBarangay,
            $cipher->encrypt($deliveryBarangay, 'orders.delivery_barangay'),
            $deliveryMunicipality,
            $cipher->encrypt($deliveryMunicipality, 'orders.delivery_municipality'),
            $deliveryProvince,
            $cipher->encrypt($deliveryProvince, 'orders.delivery_province'),
            $deliveryDistance,
            $deliveryFee,
            $requestedDeliveryDate,
            $deliverySlot,
            $substitutionPreference,
            $customerNote !== '' ? $customerNote : null,
            $customerNoteEncrypted,
        ]);

        $orderId = $db->lastInsertId();

        AuditLogService::log(
            'ORDER_CREATED',
            'order',
            (int) $orderId,
            [
                'order_number' => $orderNumber,
                'total_amount' => (float) ($total + $deliveryFee),
                'payment_method' => $paymentMethod,
                'status' => 'pending'
            ],
            (int) $user['id'],
            '/ani-mart/public/checkout/process',
            'success'
        );

        foreach ($items as $item) {
            $subtotal = $item->price * $item->quantity;
            $stmt = $db->prepare("
                INSERT INTO order_items (
                    order_id,
                    product_id,
                    product_variant_id,
                    variant_label,
                    variant_weight,
                    variant_unit,
                    price_type,
                    unit_price,
                    seller_profile_id,
                    quantity,
                    price,
                    subtotal,
                    created_at
                )
                VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                    NOW()
                )
            ");

            $stmt->execute([
                $orderId,
                $item->product_id,
                $item->product_variant_id ?: null,
                $item->selected_variant_label ?? null,
                $item->selected_variant_weight ?? null,
                $item->selected_variant_unit ?? null,
                $item->price_type ?? 'retail',
                $item->price,
                $item->seller_profile_id,
                $item->quantity,
                $item->price,
                $subtotal,
            ]);

            $stmt = $db->prepare(" 
                UPDATE products
                SET stock = GREATEST(stock - ?, 0)
                WHERE id = ?
            ");
            $stmt->execute([
                $item->quantity,
                $item->product_id,
            ]);

            if (!empty($item->product_variant_id)) {
                $db->prepare("
                    UPDATE product_variants
                    SET stock = GREATEST(stock - ?, 0)
                    WHERE id = ? AND product_id = ?
                ")->execute([
                    $item->quantity,
                    $item->product_variant_id,
                    $item->product_id,
                ]);
            }
        }

        $db->prepare("INSERT IGNORE INTO order_seller_statuses
            (order_id, seller_profile_id, status)
            SELECT DISTINCT order_id, seller_profile_id, 'pending'
            FROM order_items
            WHERE order_id = ?")
            ->execute([(int) $orderId]);

        $inventoryNotifier = new InventoryNotificationService();
        foreach ($productIds as $changedProductId) {
            $inventoryNotifier->evaluateProduct((int) $changedProductId);
        }

        $stmt = $db->prepare("
            UPDATE users
            SET latitude = ?,
                latitude_encrypted = ?,
                longitude = ?,
                longitude_encrypted = ?,
                address = ?,
                address_encrypted = ?,
                barangay = ?,
                barangay_encrypted = ?,
                municipality = ?,
                municipality_encrypted = ?,
                province = ?,
                province_encrypted = ?,
                phone = ?,
                phone_encrypted = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $deliveryLat,
            $cipher->encrypt((string) $deliveryLat, 'users.latitude'),
            $deliveryLng,
            $cipher->encrypt((string) $deliveryLng, 'users.longitude'),
            $deliveryAddress,
            $cipher->encrypt($deliveryAddress, 'users.address'),
            $deliveryBarangay,
            $cipher->encrypt($deliveryBarangay, 'users.barangay'),
            $deliveryMunicipality,
            $cipher->encrypt($deliveryMunicipality, 'users.municipality'),
            $deliveryProvince,
            $cipher->encrypt($deliveryProvince, 'users.province'),
            $deliveryPhone,
            $cipher->encrypt($deliveryPhone, 'users.phone'),
            $user['id'],
        ]);

        $user['latitude'] = $deliveryLat;
        $user['longitude'] = $deliveryLng;
        $user['address'] = $deliveryAddress;
        $user['barangay'] = $deliveryBarangay;
        $user['municipality'] = $deliveryMunicipality;
        $user['province'] = $deliveryProvince;
        $user['phone'] = $deliveryPhone;
        Session::set('user', $user);

        NotificationController::create(
            $user['id'],
            'order',
            '🛒 Order Placed',
            "Your order #$orderNumber has been placed and sent to the farmer.",
            'bi-bag-check',
            'success',
            '/ani-mart/public/customer/orders',
            'order',
            $orderId,
            '/ani-mart/public/customer/orders'
        );

        // Notify every seller represented in this order. Grouping by seller profile
        // ensures that a seller receives one useful alert even when a customer buys
        // several of that seller's products at once.
        $sellerNotificationStmt = $db->prepare("
            SELECT
                seller_profiles.user_id AS seller_user_id,
                seller_profiles.id AS seller_profile_id,
                seller_profiles.shop_name,
                SUM(order_items.quantity) AS item_count,
                SUM(order_items.subtotal) AS seller_total
            FROM order_items
            INNER JOIN seller_profiles
                ON seller_profiles.id = order_items.seller_profile_id
            WHERE order_items.order_id = ?
            GROUP BY seller_profiles.id, seller_profiles.user_id, seller_profiles.shop_name
        ");
        $sellerNotificationStmt->execute([$orderId]);

        $customerName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
        $customerName = $customerName !== '' ? $customerName : 'A customer';

        foreach ($sellerNotificationStmt->fetchAll() as $sellerOrder) {
            $sellerUserId = (int) ($sellerOrder->seller_user_id ?? 0);
            if ($sellerUserId <= 0 || $sellerUserId === (int) $user['id']) {
                continue;
            }

            $itemCount = (int) ($sellerOrder->item_count ?? 0);
            $itemLabel = $itemCount === 1 ? 'item' : 'items';
            $shopName = trim((string) ($sellerOrder->shop_name ?? 'your shop'));
            $sellerProfileId = (int) ($sellerOrder->seller_profile_id ?? 0);
            $sellerOrderUrl = '/ani-mart/public/farmer/orders?shop_id=' . $sellerProfileId;

            NotificationController::create(
                $sellerUserId,
                'order',
                '🛒 New Order Received',
                sprintf(
                    '%s placed order #%s for %d %s from %s (₱%s).',
                    $customerName,
                    $orderNumber,
                    $itemCount,
                    $itemLabel,
                    $shopName,
                    number_format((float) ($sellerOrder->seller_total ?? 0), 2)
                ),
                'bi-cart-check',
                'success',
                $sellerOrderUrl,
                'order',
                $orderId,
                $sellerOrderUrl
            );
        }

            $stmt = $db->prepare("DELETE FROM cart WHERE id IN ($placeholders) AND user_id = ?");
            $stmt->execute(array_merge($cartIds, [$user['id']]));
            unset($_SESSION['selected_cart_ids']);

            $db->commit();

            header('Location: /ani-mart/public/customer/orders');
            exit;
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log(sprintf(
                'Checkout failed for user %d: %s in %s on line %d; address_present=%d, barangay_present=%d, municipality=%s, latitude=%s, longitude=%s',
                (int) ($user['id'] ?? 0),
                $e->getMessage(),
                $e->getFile(),
                $e->getLine(),
                !empty($deliveryAddress) ? 1 : 0,
                !empty($deliveryBarangay) ? 1 : 0,
                $deliveryMunicipality ?? 'unknown',
                !empty($rawDeliveryLat) ? $rawDeliveryLat : 'none',
                !empty($rawDeliveryLng) ? $rawDeliveryLng : 'none'
            ));
            Session::flash('error', 'Unable to complete checkout. Please try again.');
            return $this->redirect('/ani-mart/public/checkout');
        }
    }

    public function success()
    {
        return $this->view('checkout/success');
    }

    public function failed()
    {
        return $this->view('checkout/failed');
    }
}
