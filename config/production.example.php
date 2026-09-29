<?php
declare(strict_types=1);

// TEMPLATE ONLY. Copy outside public_html to private/uaimoney.php.
// Fill the password only in that private copy on the server.
return [
    'app' => [
        'environment' => 'production',
        'base_path' => '/UaiMoney-MVP/public',
    ],
    'mail' => [
        'host' => 'smtp.gmail.com',
        'port' => 587,
        'username' => 'PabloHAlves99@gmail.com',
        'from' => 'PabloHAlves99@gmail.com',
        'from_name' => 'UaiMoney',
        'password' => '', // New Google app password, not the account password.
        'site_url' => 'https://uaimoney.cloud/UaiMoney-MVP/public',
    ],
];
