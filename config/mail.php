<?php
declare(strict_types=1);

$config = [
    'host' => 'smtp.gmail.com',
    'port' => 587,
    'username' => 'PabloHAlves99@gmail.com',
    'password' => getenv('UAIMONEY_MAIL_PASSWORD') ?: '',
    'from' => 'PabloHAlves99@gmail.com',
    'from_name' => 'UaiMoney',
    // Full public application address, including a subdirectory when applicable.
    'site_url' => getenv('UAIMONEY_SITE_URL') ?: 'https://uaimoney.cloud/UaiMoney-MVP/public',
];
$local = __DIR__ . '/mail.local.php';
require_once dirname(__DIR__) . '/app/Core/DeploymentConfig.php';
$private = \App\Core\DeploymentConfig::read(dirname(__DIR__));
if ($private !== []) {
    $config = array_replace($config, $private['mail']);
} elseif (is_file($local)) {
    $config = array_replace($config, require $local);
}
// Explicit process variables take precedence over file values.
foreach (['UAIMONEY_MAIL_PASSWORD' => 'password', 'UAIMONEY_SITE_URL' => 'site_url'] as $variable => $key) {
    $value = getenv($variable);
    if ($value !== false && $value !== '') $config[$key] = $value;
}
return $config;
