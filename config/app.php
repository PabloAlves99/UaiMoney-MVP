<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Core/DeploymentConfig.php';
$private = \App\Core\DeploymentConfig::read(dirname(__DIR__));
$config = [
    'name' => 'UaiMoney',
    'environment' => getenv('UAIMONEY_ENV') ?: 'development',
    'debug' => (getenv('UAIMONEY_ENV') ?: 'development') !== 'production',
    'timezone' => 'America/Sao_Paulo',

    'base_path' => getenv('UAIMONEY_BASE_PATH') !== false ? getenv('UAIMONEY_BASE_PATH') : '/uaimoney-mvp/public',
];
if ($private !== []) $config = array_replace($config, $private['app']);
foreach (['UAIMONEY_ENV' => 'environment', 'UAIMONEY_BASE_PATH' => 'base_path'] as $variable => $key) {
    $value = getenv($variable);
    if ($value !== false) $config[$key] = $value;
}
$config['debug'] = $config['environment'] !== 'production';
return $config;
