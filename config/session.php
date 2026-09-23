<?php

declare(strict_types=1);

return [
    'save_path' => dirname(__DIR__) . '/storage/sessions',

    'name' => 'uaimoney_session',

    'lifetime' => 0,

    'path' => '/',

    'secure' => (getenv('UAIMONEY_ENV') === 'production') || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),

    'http_only' => true,

    'same_site' => 'Lax',

];
