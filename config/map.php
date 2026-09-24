<?php

return [
    // Leave empty to use the keyless OpenStreetMap raster fallback.
    'maplibre_style_url' => $_ENV['MAPLIBRE_STYLE_URL'] ?? '',
];
