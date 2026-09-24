<?php

if (!defined('ANI_MART_ROOT')) {
    define('ANI_MART_ROOT', dirname(__DIR__));
}

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\App;
use App\Core\Session;

App::init();

/*
|-----------------------------------------------------------------------
| Start Session
|-----------------------------------------------------------------------
*/
Session::start();

require_once __DIR__ . '/../app/Helpers/functions.php';
