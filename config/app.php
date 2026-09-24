<?php

return [
    'app_name' => $_ENV['APP_NAME'] ?? 'Ani-Mart',
    'app_url'  => rtrim($_ENV['APP_URL'] ?? 'http://localhost/ani-mart/public', '/'),
];
