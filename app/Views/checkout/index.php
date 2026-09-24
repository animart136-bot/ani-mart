<?php

$title = "Checkout";

use App\Services\BadgeCalculationService;

ob_start();

?>

<div class="container app-page-spacing">

    <!-- CHECKOUT HERO -->
    <div class="hero mb-5">
        <div class="row align-items-center">
            <div class="col-lg-7">
                <span class="hero-badge">
                    Secure Checkout
                </span>

                <h1 class="hero-title mt-3 mb-2">
                    Complete your order
                </h1>

                <p class="hero-subtitle mt-3 mb-0">
                    Review your items, confirm your delivery location, and place your order securely.
                </p>
            </div>
        </div>
    </div>

    <!-- DELIVERY AVAILABILITY WARNING -->
    <?php if (
        !empty($userLocation->municipality) &&
        $configuredMunicipality &&
        strtolower($userLocation->municipality) !== strtolower($configuredMunicipality)
    ): ?>

        <div class="alert alert-danger rounded-4 mb-4">
            <strong>Delivery unavailable.</strong>
            Ani-Mart delivers only within Saint Bernard, Southern Leyte,
            Eastern Visayas (Region VIII), Philippines.
        </div>

    <?php endif; ?>


    <div class="row g-5">

        <!-- LEFT COLUMN -->
        <div class="col-lg-7">

            <!-- ORDER ITEMS -->
            <div class="checkout-card mb-4">

                <h4 class="fw-bold mb-4">
                    Order Items
                </h4>

                <?php if (!empty($hasUnavailableItems)): ?>
                    <div class="alert alert-danger rounded-4" role="alert">
                        One or more selected items do not have enough stock for the requested quantity. Return to your cart and adjust the quantities before placing the order.
                    </div>
                <?php endif; ?>

                <?php if (!empty($items)): ?>

                    <?php foreach ($items as $item): ?>

                        <div class="checkout-item mb-3">

                            <img
                                src="<?= htmlspecialchars($item->image) ?>"
                                class="checkout-image"
                                alt="<?= htmlspecialchars($item->name) ?>"
                            >

                            <div class="flex-grow-1 ms-3">

                                <h5 class="fw-bold mb-1">
                                    <?= htmlspecialchars($item->name) ?>
                                </h5>

                                <?php if (!empty($item->variant_label)): ?>
                                    <small class="text-success fw-semibold d-block">
                                        <?= htmlspecialchars($item->variant_label) ?>
                                    </small>
                                <?php endif; ?>

                                <small class="text-muted d-block">

                                    Shop:
                                    <?= htmlspecialchars($item->shop_name) ?>

                                    <?php if (!empty($item->verification_badge)): ?>

                                        <span
                                            class="badge <?= BadgeCalculationService::getBadgeColor($item->verification_badge) ?> rounded-pill ms-2"
                                            title="<?= htmlspecialchars(BadgeCalculationService::getBadgeLabel($item->verification_badge)) ?>"
                                            aria-label="<?= htmlspecialchars(BadgeCalculationService::getBadgeLabel($item->verification_badge)) ?>"
                                        >
                                            <i class="bi <?= BadgeCalculationService::getBadgeIcon($item->verification_badge) ?>"></i>
                                        </span>

                                    <?php endif; ?>

                                </small>

                                <small class="text-muted">
                                    Quantity:
                                    <?= $item->quantity ?>
                                </small>

                            </div>

                            <div>
                                <strong class="text-success">
                                    ₱<?= number_format($item->price * $item->quantity, 2) ?>
                                </strong>
                            </div>

                        </div>

                    <?php endforeach; ?>

                <?php else: ?>

                    <p class="text-muted">
                        No selected items.
                    </p>

                <?php endif; ?>

            </div>


            <!-- NEAREST FARMERS -->
            <div class="checkout-card">

                <h4 class="fw-bold mb-4">
                    Support Local Farmers Nearby
                </h4>

                <?php if (!empty($nearestFarmers)): ?>

                    <?php foreach ($nearestFarmers as $farmer): ?>

                        <div class="d-flex align-items-start justify-content-between gap-3 border rounded-4 p-3 mb-3">

                            <div>

                                <div class="fw-semibold">
                                    <?= htmlspecialchars($farmer->shop_name) ?>
                                </div>

                                <div class="text-muted small d-flex align-items-center gap-2">
                                    <i class="bi bi-star-fill text-warning"></i>
                                    <span>
                                        <?= number_format((float) ($farmer->trust_score ?? 0), 0) ?>%
                                        Trust Score
                                    </span>
                                </div>

                            </div>

                            <div class="text-end">

                                <?php if (!empty($farmer->verification_badge)): ?>

                                    <span
                                        class="badge <?= BadgeCalculationService::getBadgeColor($farmer->verification_badge) ?> rounded-pill mb-2"
                                        title="<?= htmlspecialchars(BadgeCalculationService::getBadgeLabel($farmer->verification_badge)) ?>"
                                        aria-label="<?= htmlspecialchars(BadgeCalculationService::getBadgeLabel($farmer->verification_badge)) ?>"
                                    >
                                        <i class="bi <?= BadgeCalculationService::getBadgeIcon($farmer->verification_badge) ?>"></i>
                                    </span>

                                <?php endif; ?>

                                <div class="small text-muted">
                                    Avg. delivery
                                    <?= htmlspecialchars($farmer->average_delivery_time ?: 'Soon') ?>
                                </div>

                            </div>

                        </div>

                    <?php endforeach; ?>

                <?php else: ?>

                    <p class="text-muted mb-0">
                        Nearby farmers will appear here when your checkout location is selected.
                    </p>

                <?php endif; ?>

            </div>

        </div>


        <!-- RIGHT COLUMN -->
        <div class="col-lg-5">

            <div class="checkout-summary position-sticky top-0">

                <h4 class="fw-bold mb-4">
                    Order Summary
                </h4>


                <!-- PRODUCTS -->
                <div class="d-flex justify-content-between mb-3">
                    <span>Products</span>

                    <strong>
                        ₱<?= number_format($total, 2) ?>
                    </strong>
                </div>


                <?php if ((float) ($shopDeliveryFee ?? 0) > 0): ?>
                    <!-- OPTIONAL SHOP DELIVERY FEE -->
                    <div class="d-flex justify-content-between mb-3">
                        <span>Shop Delivery Fees</span>

                        <strong id="deliveryFeeText">
                            ₱<?= number_format((float) $shopDeliveryFee, 2) ?>
                        </strong>
                    </div>
                <?php endif; ?>


                <!-- TOTAL -->
                <div class="d-flex justify-content-between mb-4">
                    <span>Total</span>

                    <strong id="totalAmountText">
                        ₱<?= number_format($total + (float) ($shopDeliveryFee ?? 0), 2) ?>
                    </strong>
                </div>


                <!-- CHECKOUT FORM -->
                <form
                    method="POST"
                    action="/ani-mart/public/checkout/process"
                    id="checkoutLocationForm"
                >

                    <input
                        type="hidden"
                        name="_token"
                        value="<?= htmlspecialchars(csrf_token()) ?>"
                    >

                    <input
                        type="hidden"
                        name="payment_method"
                        value="Cash on Delivery"
                    >

                    <input
                        type="hidden"
                        name="delivery_latitude"
                        id="deliveryLatitude"
                        value="<?= htmlspecialchars((string) ($userLocation->latitude ?? '')) ?>"
                    >

                    <input
                        type="hidden"
                        name="delivery_longitude"
                        id="deliveryLongitude"
                        value="<?= htmlspecialchars((string) ($userLocation->longitude ?? '')) ?>"
                    >

                    <input
                        type="hidden"
                        name="delivery_address"
                        id="deliveryAddress"
                        value="<?= htmlspecialchars((string) ($userLocation->address ?? '')) ?>"
                    >

                    <input
                        type="hidden"
                        id="deliveryBarangay"
                        value="<?= htmlspecialchars((string) ($userLocation->barangay ?? '')) ?>"
                    >

                    <input
                        type="hidden"
                        id="deliveryMunicipality"
                        value="Saint Bernard"
                    >

                    <input
                        type="hidden"
                        name="delivery_province"
                        id="deliveryProvince"
                        value="<?= htmlspecialchars((string) ($userLocation->province ?? '')) ?>"
                    >

                    <input
                        type="hidden"
                        name="delivery_distance"
                        id="deliveryDistance"
                        value=""
                    >

                    <input
                        type="hidden"
                        name="delivery_fee"
                        id="deliveryFee"
                        value="<?= htmlspecialchars((string) ((float) ($shopDeliveryFee ?? 0))) ?>"
                    >


                    <!-- DELIVERY LOCATION CARD -->
                    <div class="card border-0 shadow-sm rounded-4 mb-4">

                        <div class="card-body p-4">

                            <div class="d-flex align-items-center justify-content-between mb-3">

                                <div>

                                    <div class="fw-semibold">
                                        📍 Delivery Location
                                    </div>

                                    <div class="text-muted small">
                                        Select a precise address for local delivery.
                                    </div>

                                </div>

                                <button
                                    type="button"
                                    class="btn btn-outline-success rounded-pill btn-sm"
                                    data-bs-toggle="modal"
                                    data-bs-target="#locationModal"
                                >
                                    Change Delivery Location
                                </button>

                            </div>


                            <!-- CURRENT ADDRESS -->
                            <div class="p-3 rounded-4 bg-light">

                                <div
                                    class="fw-semibold"
                                    id="displayCustomerName"
                                >
                                    <?= htmlspecialchars(
                                        trim(
                                            ($user['first_name'] ?? '') .
                                            ' ' .
                                            ($user['last_name'] ?? '')
                                        )
                                    ) ?>
                                </div>

                                <div
                                    class="small text-muted"
                                    id="displayContactNumber"
                                >
                                    Phone:
                                    <?= htmlspecialchars(
                                        (string) ($user['phone'] ?? 'No phone on file')
                                    ) ?>
                                </div>

                                <div
                                    class="small text-muted mt-2"
                                    id="displayAddress"
                                >
                                    <?= htmlspecialchars(
                                        (string) ($userLocation->address ?? 'No address')
                                    ) ?>
                                </div>

                                <div
                                    class="small text-muted"
                                    id="displayBarangay"
                                >
                                    Barangay:
                                    <?= htmlspecialchars(
                                        (string) ($userLocation->barangay ?? '')
                                    ) ?>
                                </div>

                                <div
                                    class="small text-muted"
                                    id="displayMunicipality"
                                >
                                    Municipality: Saint Bernard
                                </div>

                                <div
                                    class="small text-muted"
                                    id="displayProvince"
                                >
                                    Province:
                                    <?= htmlspecialchars(
                                        (string) ($userLocation->province ?? '')
                                    ) ?>
                                </div>


                                <!-- ADDRESS FIELDS -->
                                <div class="row g-2 mt-3">

                                    <!-- STREET -->
                                    <div class="col-12">

                                        <label class="form-label small mb-1">
                                            Street / House No.
                                        </label>

                                        <div class="input-group">

                                            <input
                                                type="text"
                                                class="form-control form-control-sm"
                                                id="streetInput"
                                                name="delivery_street"
                                                value="<?= htmlspecialchars(
                                                    (string) ($userLocation->address ?? '')
                                                ) ?>"
                                                placeholder="Type street or house number"
                                            >

                                            <button
                                                class="btn btn-outline-success btn-sm"
                                                type="button"
                                                id="searchAddressBtn"
                                                title="Show typed address on map"
                                                aria-label="Show typed address on map"
                                            >
                                                <i class="bi bi-search"></i>
                                            </button>

                                        </div>

                                    </div>


                                    <!-- BARANGAY -->
                                    <div class="col-12">

                                        <label class="form-label small mb-1">
                                            Barangay
                                        </label>

                                        <div class="input-group">

                                            <input
                                                type="text"
                                                class="form-control form-control-sm"
                                                id="barangayInput"
                                                name="delivery_barangay"
                                                value="<?= htmlspecialchars(
                                                    (string) ($userLocation->barangay ?? '')
                                                ) ?>"
                                                placeholder="Select a Saint Bernard barangay"
                                                readonly
                                                required
                                            >

                                            <button
                                                class="btn btn-outline-secondary btn-sm"
                                                type="button"
                                                id="openBarangayPicker"
                                            >
                                                Select
                                            </button>

                                        </div>

                                    </div>


                                    <!-- MUNICIPALITY -->
                                    <div class="col-12">

                                        <label class="form-label small mb-1">
                                            Municipality
                                        </label>

                                        <input
                                            type="text"
                                            class="form-control form-control-sm"
                                            id="municipalityInput"
                                            name="delivery_municipality"
                                            value="Saint Bernard"
                                            readonly
                                        >

                                    </div>


                                    <!-- PHONE -->
                                    <div class="col-12">

                                        <label class="form-label small mb-1">
                                            Phone Number
                                        </label>

                                        <input
                                            type="text"
                                            class="form-control form-control-sm"
                                            id="phoneInput"
                                            name="delivery_phone"
                                            value="<?= htmlspecialchars(
                                                (string) ($user['phone'] ?? '')
                                            ) ?>"
                                            placeholder="09XXXXXXXXX"
                                        >

                                    </div>

                                </div>


                                <div class="small text-muted mt-2">
                                    You can type your address directly, or use the map to locate and fill it automatically.
                                </div>

                            </div>


                            <!-- MAP PREVIEW -->
                            <div
                                class="mt-3"
                                id="mapPreview"
                            ></div>

                        </div>

                    </div>


                    <div class="card border-0 shadow-sm rounded-4 mb-4">
                        <div class="card-body p-4">
                            <h5 class="fw-bold mb-3">Delivery preferences</h5>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="requestedDeliveryDate" class="form-label">Preferred date</label>
                                    <input
                                        id="requestedDeliveryDate"
                                        name="requested_delivery_date"
                                        type="date"
                                        class="form-control"
                                        min="<?= date('Y-m-d') ?>"
                                        max="<?= date('Y-m-d', strtotime('+7 days')) ?>"
                                        value="<?= date('Y-m-d', strtotime('+1 day')) ?>"
                                        required>
                                </div>
                                <div class="col-md-6">
                                    <label for="deliverySlot" class="form-label">Time window</label>
                                    <select id="deliverySlot" name="delivery_slot" class="form-select" required>
                                        <option value="morning">Morning · 8 AM–12 PM</option>
                                        <option value="afternoon">Afternoon · 12–5 PM</option>
                                        <option value="evening">Evening · 5–8 PM</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label for="substitutionPreference" class="form-label">If an item becomes unavailable</label>
                                    <select id="substitutionPreference" name="substitution_preference" class="form-select">
                                        <option value="contact_me">Contact me first</option>
                                        <option value="no_substitutions">Do not substitute</option>
                                        <option value="best_available">Use the closest available alternative</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label for="customerNote" class="form-label">Note for the farmer <span class="text-muted">(optional)</span></label>
                                    <textarea id="customerNote" name="customer_note" class="form-control" maxlength="500" rows="2" placeholder="Delivery directions or produce preferences"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- PAYMENT METHOD -->
                    <div class="mb-4">

                        <label class="form-label">
                            Payment Method
                        </label>

                        <div class="payment-method-card">

                            <i class="bi bi-cash-stack text-success"></i>

                            <div>

                                <strong>
                                    Cash on Delivery
                                </strong>

                                <div class="text-muted small">
                                    Pay upon receiving your order
                                </div>

                            </div>

                        </div>

                    </div>


                    <!-- PLACE ORDER -->
                    <button
                        class="btn btn-success w-100 modern-btn"
                        type="submit"
                        <?= !empty($hasUnavailableItems) ? 'disabled' : '' ?>
                    >
                        <?= !empty($hasUnavailableItems) ? 'Adjust Cart Stock First' : 'Place Order' ?>
                    </button>

                </form>

            </div>

        </div>

    </div>

</div>


<!-- LOCATION MODAL -->
<div
    class="modal fade"
    id="locationModal"
    tabindex="-1"
    aria-labelledby="locationModalLabel"
    aria-hidden="true"
>

    <div class="modal-dialog modal-xl modal-dialog-centered">

        <div class="modal-content rounded-4">

            <div class="modal-header border-0">

                <div>

                    <h5
                        class="modal-title fw-bold"
                        id="locationModalLabel"
                    >
                        Choose Delivery Location
                    </h5>

                    <p class="text-muted small mb-0">
                        Pin your address inside Saint Bernard, Southern Leyte only.
                    </p>

                </div>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Close"
                ></button>

            </div>


            <div class="modal-body p-0">

                <div class="p-3 border-bottom bg-light">

                    <div class="row g-2 align-items-center">

                        <div class="col-md-8">

                            <input
                                type="text"
                                id="locationSearch"
                                class="form-control rounded-pill"
                                placeholder="Search location or address"
                            >

                        </div>

                        <div class="col-md-2">

                            <button
                                type="button"
                                id="detectLocationBtn"
                                class="btn btn-success rounded-pill w-100"
                            >
                                Detect
                            </button>

                        </div>

                        <div class="col-md-2">

                            <button
                                type="button"
                                id="confirmLocationBtn"
                                class="btn btn-outline-success rounded-pill w-100"
                            >
                                Use This
                            </button>

                        </div>

                    </div>

                </div>


                <div id="mapContainer"></div>

            </div>

        </div>

    </div>

</div>


<!-- BARANGAY PICKER MODAL -->
<div
    class="modal fade"
    id="barangayModal"
    tabindex="-1"
    aria-labelledby="barangayModalLabel"
    aria-hidden="true"
>

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content rounded-4">

            <div class="modal-header">

                <h5
                    class="modal-title"
                    id="barangayModalLabel"
                >
                    Select Barangay
                </h5>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Close"
                ></button>

            </div>


            <div class="modal-body p-0">

                <div class="p-3 border-bottom">

                    <input
                        type="text"
                        id="barangaySearch"
                        class="form-control rounded-pill"
                        placeholder="Search barangay"
                    >

                </div>


                <div
                    class="list-group list-group-flush"
                    id="barangayList"
                ></div>

            </div>

        </div>

    </div>

</div>


<!-- MAPLIBRE -->
<link
    rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/maplibre-gl@4.7.1/dist/maplibre-gl.css"
/>

<script src="https://cdn.jsdelivr.net/npm/maplibre-gl@4.7.1/dist/maplibre-gl.js"></script>


<script>

    const configuredMunicipality =
        <?= json_encode($configuredMunicipality) ?>;

    const baseTotal =
        <?= (float) $total ?>;

    const shopDeliveryFee =
        <?= (float) ($shopDeliveryFee ?? 0) ?>;

    const defaultMapLocation =
        <?= json_encode(
            $defaultMapLocation ?? [
                'latitude' => 10.2833,
                'longitude' => 125.1700,
                'zoom' => 13
            ]
        ) ?>;

    const farmLat =
        <?= (float) ($nearestFarmers[0]->latitude ?? 10.2833) ?>;

    const farmLng =
        <?= (float) ($nearestFarmers[0]->longitude ?? 125.1700) ?>;


    const mapPreviewElement =
        document.getElementById('mapPreview');

    const mapContainerElement =
        document.getElementById('mapContainer');


    const hasValidCoordinates = (latitude, longitude) => {

        return Number.isFinite(Number(latitude))
            && Number(latitude) >= -90
            && Number(latitude) <= 90
            && Number.isFinite(Number(longitude))
            && Number(longitude) >= -180
            && Number(longitude) <= 180;

    };


    const fallbackLatitude =
        hasValidCoordinates(
            defaultMapLocation.latitude,
            defaultMapLocation.longitude
        )
            ? Number(defaultMapLocation.latitude)
            : 10.2833;


    const fallbackLongitude =
        hasValidCoordinates(
            defaultMapLocation.latitude,
            defaultMapLocation.longitude
        )
            ? Number(defaultMapLocation.longitude)
            : 125.1700;


    const fallbackZoom =
        Number.isFinite(Number(defaultMapLocation.zoom))
            ? Number(defaultMapLocation.zoom)
            : 13;


    const mapLibre =
        window.maplibregl || null;


    const mapStyleUrl =
        <?= json_encode($mapStyleUrl ?? '') ?>;


    const fallbackMapStyle = {

        version: 8,

        sources: {

            openstreetmap: {

                type: 'raster',

                // CyclOSM is frequently unavailable.
                // Standard OSM tiles do not require an API key.
                tiles: [
                    'https://tile.openstreetmap.org/{z}/{x}/{y}.png'
                ],

                tileSize: 256,

                attribution:
                    '&copy; OpenStreetMap contributors'

            }

        },

        layers: [

            {
                id: 'openstreetmap-raster',
                type: 'raster',
                source: 'openstreetmap'
            }

        ]

    };


    let previewMap = null;
    let modalMap = null;
    let markerPreview = null;
    let markerModal = null;


    function showMapError(element) {

        if (element) {

            element.innerHTML =
                '<div class="d-flex h-100 align-items-center justify-content-center p-3 text-muted small text-center">' +
                'Map could not be loaded. You can still enter your delivery address manually.' +
                '</div>';

        }

    }


    function createMap(element, center, zoom) {

        if (!mapLibre || !element) {

            showMapError(element);

            return null;

        }


        let map;

        try {

            map = new mapLibre.Map({

                container: element,

                style:
                    mapStyleUrl || fallbackMapStyle,

                center: [
                    center[1],
                    center[0]
                ],

                zoom: zoom,

                attributionControl: true

            });

        } catch (error) {

            console.warn(
                'Checkout map could not be initialized.',
                error
            );

            showMapError(element);

            return null;

        }


        map.addControl(
            new mapLibre.NavigationControl(),
            'top-left'
        );


        map.on('error', function (event) {

            console.warn(
                'Checkout MapLibre error.',
                event.error || event
            );

        });


        return map;

    }


    previewMap =
        createMap(
            mapPreviewElement,
            [
                fallbackLatitude,
                fallbackLongitude
            ],
            fallbackZoom
        );


    const streetInput =
        document.getElementById('streetInput');

    const barangayInput =
        document.getElementById('barangayInput');

    const municipalityInput =
        document.getElementById('municipalityInput');

    const phoneInput =
        document.getElementById('phoneInput');


    let selectedLat =
        <?= json_encode(
            (string) ($userLocation->latitude ?? '')
        ) ?>;

    let selectedLng =
        <?= json_encode(
            (string) ($userLocation->longitude ?? '')
        ) ?>;

    let selectedAddress =
        <?= json_encode(
            trim(implode(', ', array_filter([
                (string) ($userLocation->barangay ?? ''),
                'Saint Bernard',
                'Southern Leyte',
                'Philippines'
            ])))
        ) ?>;


    let locationConfirmed =
        selectedLat !== ''
        && selectedLng !== ''
        && selectedAddress !== '';


    let selectedBarangay =
        <?= json_encode(
            (string) ($userLocation->barangay ?? '')
        ) ?>;

    let selectedMunicipality =
        <?= json_encode(
            (string) ($userLocation->municipality ?? '')
        ) ?>;

    let selectedProvince = 'Southern Leyte';


    const normalizePlaceName = (value) =>
        String(value || '')
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '');


    function isWithinDeliveryArea(address) {

        const addressData = address || {};
        const municipality =
            addressData.city ||
            addressData.municipality ||
            addressData.town ||
            addressData.county ||
            '';
        const returnedBarangay =
            addressData.barangay ||
            addressData.village ||
            addressData.suburb ||
            addressData.quarter ||
            addressData.neighbourhood ||
            addressData.hamlet ||
            '';
        const searchableAddress = normalizePlaceName(
            [
                addressData.display_name,
                addressData.province,
                addressData.state,
                addressData.region,
                addressData.county
            ].filter(Boolean).join(' ')
        );

        const municipalityMatches =
            normalizePlaceName(municipality) === 'saintbernard' ||
            searchableAddress.includes('saintbernard');
        const barangayMatches = barangays.some(
            (barangay) =>
                normalizePlaceName(barangay) ===
                    normalizePlaceName(returnedBarangay) ||
                searchableAddress.includes(normalizePlaceName(barangay))
        );
        const regionalAreaMatches =
            searchableAddress.includes('southernleyte') ||
            searchableAddress.includes('easternvisayas') ||
            searchableAddress.includes('regionviii') ||
            searchableAddress.includes('region8');
        const isPhilippines =
            normalizePlaceName(addressData.country_code) === 'ph' ||
            searchableAddress.includes('philippines');

        return isPhilippines && (
            municipalityMatches ||
            (barangayMatches && regionalAreaMatches)
        );
    }


    let selectedDistance = 0;
    let selectedFee = 0;


    const locationModal =
        document.getElementById('locationModal');


    function initializeModalMap() {

        if (
            modalMap ||
            !mapLibre ||
            !mapContainerElement
        ) {

            if (modalMap) {
                modalMap.resize();
            }

            return modalMap;

        }


        modalMap =
            createMap(
                mapContainerElement,
                [
                    fallbackLatitude,
                    fallbackLongitude
                ],
                fallbackZoom
            );


        if (!modalMap) {
            return null;
        }


        modalMap.on('click', function (event) {

            applySelection(
                event.lngLat.lat,
                event.lngLat.lng
            );

        });


        modalMap.resize();


        return modalMap;

    }


    // Barangay list for Saint Bernard
    const barangays = [

        'Atuyan',
        'Ayahag',
        'Bantawon',
        'Bolodbolod',
        'Nueva Esperanza',
        'Cabagawan',
        'Carnaga',
        'Catmon',
        'Guinsaugon',
        'Himatagon',
        'Himbangan',
        'Himos-onan',
        'Hindag-an',
        'Kauswagan',
        'Libas',
        'Lipanto',
        'Magatas',
        'Magbagacay',
        'Mahayag',
        'Mahayahay',
        'Malibago',
        'Malinao',
        'Panian',
        'San Isidro',
        'Santa Cruz',
        'Sug-angon',
        'Tabontabon',
        'Tambis I',
        'Tambis II',
        'Hinabian'

    ];


    function populateBarangayList(search = '') {

        const list =
            document.getElementById('barangayList');

        const searchTerm =
            search.trim().toLowerCase();


        list.innerHTML = '';


        let matchCount = 0;


        barangays.forEach(function (b) {

            if (
                searchTerm &&
                !b.toLowerCase().includes(searchTerm)
            ) {
                return;
            }


            matchCount += 1;


            const item =
                document.createElement('button');


            item.type = 'button';

            item.className =
                'list-group-item list-group-item-action';

            item.textContent = b;


            item.addEventListener(
                'click',
                function () {

                    barangayInput.value = b;

                    updateDisplay();


                    const modal =
                        bootstrap.Modal.getInstance(
                            document.getElementById('barangayModal')
                        );


                    if (modal) {
                        modal.hide();
                    }

                }
            );


            list.appendChild(item);

        });


        if (matchCount === 0) {

            const emptyItem =
                document.createElement('div');

            emptyItem.className =
                'list-group-item text-muted text-center';

            emptyItem.textContent =
                'No barangays found. Try a different search term.';

            list.appendChild(emptyItem);

        }

    }


    document
        .getElementById('openBarangayPicker')
        .addEventListener('click', function () {

            document.getElementById(
                'barangaySearch'
            ).value = '';


            populateBarangayList();


            const modalEl =
                document.getElementById(
                    'barangayModal'
                );


            const modal =
                new bootstrap.Modal(modalEl);


            modal.show();

        });


    document
        .getElementById('barangaySearch')
        .addEventListener('input', function () {

            populateBarangayList(this.value);

        });


    function updateSummary(distance, fee) {

        selectedDistance = distance;
        selectedFee = fee;


        document.getElementById(
            'deliveryDistance'
        ).value = distance;


        document.getElementById(
            'deliveryFee'
        ).value = fee;


        const feeLabel =
            fee === 0
                ? 'FREE'
                : '₱' + fee.toFixed(2);


        const deliveryFeeText = document.getElementById(
            'deliveryFeeText'
        );

        if (deliveryFeeText) {
            deliveryFeeText.textContent = feeLabel;
        }


        document.getElementById(
            'distanceText'
        ).textContent =
            distance.toFixed(1) + ' km';


        document.getElementById(
            'totalAmountText'
        ).textContent =
            '₱' + (baseTotal + fee).toFixed(2);

    }


    function deliveryAddressFromFields() {

        const street =
            streetInput.value.trim();


        // A dropped pin identifies the locality,
        // but not necessarily the house number.
        if (!selectedAddress) {
            return street;
        }


        if (
            !street ||
            selectedAddress
                .toLowerCase()
                .includes(street.toLowerCase())
        ) {
            return selectedAddress;
        }


        return street + ', ' + selectedAddress;

    }


    // A map pin must never replace the
    // editable Street / House No. field.
    function updateDisplay() {

        municipalityInput.value = 'Saint Bernard';
        selectedProvince = 'Southern Leyte';


        const currentAddress =
            deliveryAddressFromFields();


        const currentBarangay =
            barangayInput.value.trim()
            || selectedBarangay
            || '';


        document.getElementById(
            'displayAddress'
        ).textContent =
            currentAddress || 'No address';


        document.getElementById(
            'displayBarangay'
        ).textContent =
            'Barangay: ' + currentBarangay;


        document.getElementById(
            'displayMunicipality'
        ).textContent =
            'Municipality: ' +
            (
                municipalityInput.value.trim()
                || 'Saint Bernard'
            );


        document.getElementById(
            'displayProvince'
        ).textContent =
            'Province: ' +
            (selectedProvince || '');


        document.getElementById(
            'deliveryAddress'
        ).value =
            currentAddress;


        document.getElementById(
            'deliveryBarangay'
        ).value =
            currentBarangay;


        document.getElementById(
            'deliveryMunicipality'
        ).value =
            municipalityInput.value.trim()
            || 'Saint Bernard';


        document.getElementById(
            'deliveryProvince'
        ).value =
            selectedProvince || '';


        document.getElementById(
            'deliveryLatitude'
        ).value =
            selectedLat || '';


        document.getElementById(
            'deliveryLongitude'
        ).value =
            selectedLng || '';

    }


    function setMarker(lat, lng) {

        if (!hasValidCoordinates(lat, lng)) {
            return;
        }


        const position = [
            Number(lat),
            Number(lng)
        ];


        if (
            previewMap &&
            !markerPreview
        ) {

            markerPreview =
                new mapLibre.Marker()
                    .setLngLat([
                        position[1],
                        position[0]
                    ])
                    .addTo(previewMap);

        } else if (markerPreview) {

            markerPreview.setLngLat([
                position[1],
                position[0]
            ]);

        }


        if (
            modalMap &&
            !markerModal
        ) {

            markerModal =
                new mapLibre.Marker({
                    draggable: true
                })
                .setLngLat([
                    position[1],
                    position[0]
                ])
                .addTo(modalMap);


            markerModal.on(
                'dragend',
                function () {

                    const position =
                        markerModal.getLngLat();


                    applySelection(
                        position.lat,
                        position.lng
                    );

                }
            );

        } else if (markerModal) {

            markerModal.setLngLat([
                position[1],
                position[0]
            ]);

        }


        if (previewMap) {

            previewMap.flyTo({
                center: [
                    position[1],
                    position[0]
                ],
                zoom: 18
            });

        }


        if (modalMap) {

            modalMap.flyTo({
                center: [
                    position[1],
                    position[0]
                ],
                zoom: 18
            });

        }


        selectedLat = Number(lat);
        selectedLng = Number(lng);


        if (previewMap) {
            previewMap.resize();
        }

        if (modalMap) {
            modalMap.resize();
        }


        updateDisplay();

    }


    function calculateDistanceInKm(
        lat1,
        lng1,
        lat2,
        lng2
    ) {

        if (
            !lat1 ||
            !lng1 ||
            !lat2 ||
            !lng2
        ) {
            return 0;
        }


        const radius = 6371;


        const dLat =
            (lat2 - lat1) *
            Math.PI /
            180;


        const dLng =
            (lng2 - lng1) *
            Math.PI /
            180;


        const a =
            Math.sin(dLat / 2) *
            Math.sin(dLat / 2) +

            Math.cos(lat1 * Math.PI / 180) *
            Math.cos(lat2 * Math.PI / 180) *

            Math.sin(dLng / 2) *
            Math.sin(dLng / 2);


        const c =
            2 *
            Math.atan2(
                Math.sqrt(a),
                Math.sqrt(1 - a)
            );


        return radius * c;

    }


    function applySelection(lat, lng) {

        reverseGeocode(lat, lng);

    }


    function reverseGeocode(lat, lng) {

        fetch(
            `https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}`
        )
        .then((response) => response.json())
        .then((data) => {

            data.display_name = data.display_name || '';

            if (!isWithinDeliveryArea({
                ...data.address,
                display_name: data.display_name
            })) {

                alert(
                    'That location is outside Saint Bernard, Southern Leyte. Please choose a location within the municipality.'
                );

                return;
            }

            selectedAddress =
                data.display_name;


            selectedBarangay =
                data.address?.barangay ||
                data.address?.village ||
                data.address?.suburb ||
                data.address?.quarter ||
                data.address?.neighbourhood ||
                data.address?.hamlet ||
                '';


            selectedMunicipality = 'Saint Bernard';
            selectedProvince = 'Southern Leyte';


            setMarker(lat, lng);


            document.getElementById(
                'locationSearch'
            ).value =
                selectedAddress;


            updateDisplay();


            const distance =
                calculateDistanceInKm(
                    lat,
                    lng,
                    farmLat,
                    farmLng
                );


            updateSummary(
                distance,
                shopDeliveryFee
            );

        })
        .catch(() => {

            updateDisplay();

        });

    }


    if (previewMap) {

        previewMap.on(
            'click',
            function (event) {

                applySelection(
                    event.lngLat.lat,
                    event.lngLat.lng
                );

            }
        );

    }


    [
        streetInput,
        barangayInput,
        municipalityInput,
        phoneInput
    ].forEach(function (input) {

        input.addEventListener(
            'input',
            updateDisplay
        );

    });


    document
        .getElementById('detectLocationBtn')
        .addEventListener(
            'click',
            function () {

                if (!navigator.geolocation) {

                    alert(
                        'Geolocation is not supported by your browser.'
                    );

                    return;

                }


                navigator.geolocation.getCurrentPosition(

                    function (position) {

                        applySelection(
                            position.coords.latitude,
                            position.coords.longitude
                        );

                    },

                    function () {

                        alert(
                            'Location access was not allowed. You can search for your address or move the pin manually.'
                        );

                    },

                    {
                        enableHighAccuracy: true,
                        timeout: 15000,
                        maximumAge: 0
                    }

                );

            }
        );


    document
        .getElementById('confirmLocationBtn')
        .addEventListener(
            'click',
            function () {

                if (
                    selectedLat &&
                    selectedLng
                ) {

                    // The map address identifies
                    // the pinned area. Leave Street / House No.
                    // editable.
                    if (
                        streetInput.value.trim() ===
                        selectedAddress
                    ) {

                        streetInput.value = '';

                    }


                    barangayInput.value =
                        selectedBarangay ||
                        barangayInput.value;


                    municipalityInput.value = 'Saint Bernard';


                    locationConfirmed = true;


                    updateDisplay();


                    const modal =
                        bootstrap.Modal.getInstance(
                            locationModal
                        );


                    if (modal) {
                        modal.hide();
                    }

                } else {

                    alert(
                        'Please choose a location on the map first.'
                    );

                }

            }
        );


    function searchAddressOnMap(query) {

        if (
            !query.trim() ||
            !mapLibre ||
            !previewMap
        ) {
            return;
        }


        const fallbackQuery = [

            barangayInput.value,

            municipalityInput.value,

            document.getElementById(
                'deliveryProvince'
            ).value,

            'Philippines'

        ]
        .filter(Boolean)
        .join(', ');


        function fetchAddress(searchQuery) {

            return fetch(
                `https://nominatim.openstreetmap.org/search?format=jsonv2&addressdetails=1&limit=1&countrycodes=ph&q=${encodeURIComponent(searchQuery)}`
            )
            .then(
                (response) => response.json()
            );

        }


        fetchAddress(
            query + ', Philippines'
        )
        .then((data) => {

            if (
                data &&
                data[0]
            ) {

                applyGeocodedAddress(
                    data[0],
                    query
                );

            } else if (
                fallbackQuery !==
                query + ', Philippines'
            ) {

                return fetchAddress(
                    fallbackQuery
                )
                .then(
                    (fallbackData) => {

                        if (
                            fallbackData &&
                            fallbackData[0]
                        ) {

                            applyGeocodedAddress(
                                fallbackData[0],
                                fallbackQuery
                            );

                        }

                    }
                );

            }

        })
        .catch(() => {

            // Keep manually entered address
            // fields usable when geocoding is unavailable.

        });

    }


    function applyGeocodedAddress(
        item,
        query
    ) {

        const lat =
            parseFloat(item.lat);

        const lon =
            parseFloat(item.lon);


        if (
            !hasValidCoordinates(
                lat,
                lon
            )
        ) {
            return;
        }


        if (!isWithinDeliveryArea({
            ...(item.address || {}),
            display_name: item.display_name || query
        })) {

            alert(
                'That search result is outside Saint Bernard, Southern Leyte.'
            );

            return;
        }


        applySelection(
            lat,
            lon
        );


        const precisePlaceTypes = [

            'house',
            'building',
            'residential',
            'road',
            'street'

        ];


        const placeType =
            String(
                item.type || ''
            ).toLowerCase();


        const zoom =
            precisePlaceTypes.includes(
                placeType
            )
                ? 18
                : 15;


        if (previewMap) {

            previewMap.flyTo({

                center: [
                    lon,
                    lat
                ],

                zoom: zoom

            });

        }


        if (modalMap) {

            modalMap.flyTo({

                center: [
                    lon,
                    lat
                ],

                zoom: zoom

            });

        }


        document.getElementById(
            'locationSearch'
        ).value =
            selectedAddress;


        updateDisplay();

    }


    let addressSearchTimer;

    let lastAddressQuery = '';


    function typedAddressQuery() {

        return [

            streetInput.value,

            barangayInput.value,

            municipalityInput.value,

            document.getElementById(
                'deliveryProvince'
            ).value

        ]
        .filter(Boolean)
        .join(', ');

    }


    function scheduleAddressSearch() {

        const query =
            typedAddressQuery().trim();


        if (
            query.length < 5 ||
            query === lastAddressQuery
        ) {
            return;
        }


        clearTimeout(
            addressSearchTimer
        );


        addressSearchTimer =
            setTimeout(
                function () {

                    lastAddressQuery =
                        query;

                    searchAddressOnMap(
                        query
                    );

                },
                900
            );

    }


    [
        streetInput,
        barangayInput,
        municipalityInput
    ].forEach(function (input) {

        input.addEventListener(
            'input',
            scheduleAddressSearch
        );

        input.addEventListener(
            'blur',
            scheduleAddressSearch
        );

    });


    document
        .getElementById('locationSearch')
        .addEventListener(
            'keydown',
            function (event) {

                if (
                    event.key === 'Enter'
                ) {

                    event.preventDefault();

                    searchAddressOnMap(
                        this.value
                    );

                }

            }
        );


    streetInput.addEventListener(
        'keydown',
        function (event) {

            if (
                event.key === 'Enter'
            ) {

                event.preventDefault();

                searchAddressOnMap(
                    this.value ||
                    selectedAddress
                );

            }

        }
    );


    document
        .getElementById('searchAddressBtn')
        .addEventListener(
            'click',
            function () {

                const query = [

                    streetInput.value,

                    barangayInput.value,

                    municipalityInput.value,

                    document.getElementById(
                        'deliveryProvince'
                    ).value

                ]
                .filter(Boolean)
                .join(', ');


                searchAddressOnMap(query);

            }
        );


    locationModal.addEventListener(
        'shown.bs.modal',
        function () {

            initializeModalMap();


            if (previewMap) {
                previewMap.resize();
            }


            if (modalMap) {

                if (
                    selectedLat &&
                    selectedLng
                ) {

                    setMarker(
                        selectedLat,
                        selectedLng
                    );

                }


                modalMap.resize();

            }

        }
    );


    window.addEventListener(
        'load',
        function () {

            if (previewMap) {
                previewMap.resize();
            }

        }
    );


    if (
        selectedLat &&
        selectedLng
    ) {

        applySelection(
            parseFloat(selectedLat),
            parseFloat(selectedLng)
        );

    } else {

        updateSummary(
            0,
            shopDeliveryFee
        );

    }


    document
        .getElementById('checkoutLocationForm')
        .addEventListener(
            'submit',
            function (event) {

                updateDisplay();


                const latitude =
                    document.getElementById(
                        'deliveryLatitude'
                    ).value;


                const longitude =
                    document.getElementById(
                        'deliveryLongitude'
                    ).value;


                const address =
                    document.getElementById(
                        'deliveryAddress'
                    ).value.trim();


                const selectedBarangayName =
                    barangayInput.value.trim();


                const hasAllowedBarangay =
                    barangays.some(
                        (barangay) =>
                            normalizePlaceName(barangay) ===
                            normalizePlaceName(selectedBarangayName)
                    );


                if (!address) {

                    event.preventDefault();

                    alert(
                        'Please enter your delivery address. You may optionally use the map to choose a precise location.'
                    );

                    return;

                }


                if (!hasAllowedBarangay) {

                    event.preventDefault();

                    alert(
                        'Please select an official barangay of Saint Bernard, Southern Leyte.'
                    );

                    return;

                }


                locationConfirmed = true;

            }
        );

</script>


<?php

$content = ob_get_clean();

$pageCss =
    '/ani-mart/public/assets/css/pages/checkout.css';

require ANI_MART_ROOT . '/app/Views/layouts/main.php';
