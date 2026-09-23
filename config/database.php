<?php

declare(strict_types=1);

return [
    'driver' => 'sqlite',
    'database' => getenv('UAIMONEY_DATABASE') ?: dirname(__DIR__) . '/storage/database/uaimoney.sqlite',
    'foreign_keys' => true,
];