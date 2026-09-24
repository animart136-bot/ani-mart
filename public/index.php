<?php

// IMPORTANT: load bootstrap FIRST
require_once __DIR__ . '/../bootstrap/app.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

use App\Core\Router;

$router = new Router();

require_once __DIR__ . '/../routes/web.php';

$router->resolve();
