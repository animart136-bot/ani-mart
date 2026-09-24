<?php
require_once __DIR__ . '/../bootstrap/app.php';
$path = $argv[1] ?? __DIR__ . '/../storage/reference-data/prices/price_reference_dataset_prototype.csv';
$result = (new App\Services\PriceReferenceImportService())->import($path);
unset($result['_rows']);
echo json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
