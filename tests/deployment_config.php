<?php
declare(strict_types=1);
require dirname(__DIR__) . '/vendor/autoload.php';
use App\Core\DeploymentConfig;
$original = getenv('UAIMONEY_CONFIG_FILE');
$assert = static function (bool $value): void { if (!$value) throw new RuntimeException('Deployment config test failed'); };
try {
    putenv('UAIMONEY_CONFIG_FILE');
    $assert(DeploymentConfig::path('/home/account/domains/site/public_html/UaiMoney-MVP') === '/home/account/domains/site/private/uaimoney.php');
    $assert(DeploymentConfig::path('/home/account/domains/site/public_html') === '/home/account/domains/site/private/uaimoney.php');
    $assert(DeploymentConfig::path('/development/project') === null);
    putenv('UAIMONEY_CONFIG_FILE=' . dirname(__DIR__) . '/config/production.example.php');
    $config = DeploymentConfig::read('/development/project');
    $assert($config['app']['environment'] === 'production' && $config['app']['base_path'] === '/UaiMoney-MVP/public');
    putenv('UAIMONEY_CONFIG_FILE=' . __DIR__ . '/nonexistent-private-config.php');
    $failed = false;
    try { DeploymentConfig::read('/development/project'); } catch (RuntimeException $e) { $failed = true; }
    $assert($failed);
    echo "OK: descoberta do arquivo privado, configuração de produção e falha segura.\n";
} finally {
    $original === false ? putenv('UAIMONEY_CONFIG_FILE') : putenv('UAIMONEY_CONFIG_FILE=' . $original);
}
