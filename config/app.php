<?php

declare(strict_types=1);

return [
    'name' => 'UaiMoney',
    'environment' => getenv('UAIMONEY_ENV') ?: 'development',
    'debug' => (getenv('UAIMONEY_ENV') ?: 'development') !== 'production',
    'timezone' => 'America/Sao_Paulo',

    'base_path' => getenv('UAIMONEY_BASE_PATH') !== false ? getenv('UAIMONEY_BASE_PATH') : '/uaimoney-mvp/public',
];