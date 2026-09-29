<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$root = dirname(__DIR__);
$stage = 'dependências: envie vendor/ completo ou execute composer install';
$failures = 0;
$check = static function (bool $ok, string $label) use (&$failures): void {
    echo ($ok ? '[OK] ' : '[FALHA] ') . $label . PHP_EOL;
    if (!$ok) $failures++;
};
try {
    if (!is_file($root . '/vendor/autoload.php')) throw new RuntimeException();
    require $root . '/vendor/autoload.php';
    $check(class_exists(\PHPMailer\PHPMailer\PHPMailer::class), 'PHPMailer instalado');
    $check(extension_loaded('openssl'), 'Extensão OpenSSL');
    $check(extension_loaded('pdo_sqlite'), 'Extensão PDO SQLite');
    $stage = 'localização da configuração privada: confira app/Core/DeploymentConfig.php';
    $path = \App\Core\DeploymentConfig::path($root);
    if ($path !== null) {
        echo 'Arquivo privado esperado: ' . $path . PHP_EOL;
        $check(is_file($path), 'Arquivo privado existe');
        $check(is_readable($path), 'Arquivo privado permite leitura pelo PHP');
        if (!is_file($path) || !is_readable($path)) exit(1);
    } else {
        echo "[AVISO] Nenhum caminho de produção detectado. Defina UAIMONEY_CONFIG_FILE se o projeto não estiver sob public_html.\n";
    }
    $stage = 'leitura do arquivo privado: confira a sintaxe PHP e as seções app e mail';
    $app = require dirname(__DIR__) . '/config/app.php';
    $mail = require dirname(__DIR__) . '/config/mail.php';
    $check(($app['environment'] ?? '') === 'production', 'Ambiente de produção');
    $check(is_string($mail['password'] ?? null) && trim($mail['password']) !== '', 'Senha de app preenchida (valor oculto)');
    $check(filter_var($mail['username'] ?? '', FILTER_VALIDATE_EMAIL) !== false, 'Usuário SMTP é um e-mail válido');
    $check(filter_var($mail['from'] ?? '', FILTER_VALIDATE_EMAIL) !== false, 'Remetente é um e-mail válido');
    $check(($mail['host'] ?? '') === 'smtp.gmail.com' && (int)($mail['port'] ?? 0) === 587, 'Servidor Gmail e porta STARTTLS 587');
    $url = (string)($mail['site_url'] ?? '');
    $check(str_starts_with($url, 'https://'), 'URL pública usa HTTPS');
    $check(rtrim(parse_url($url, PHP_URL_PATH) ?? '', '/') === rtrim((string)($app['base_path'] ?? ''), '/'), 'Caminho da URL corresponde a app.base_path');
    $check(is_readable($root . '/public/images/brand/logo-horizontal.png'), 'Logo do e-mail disponível');
    if ($failures > 0) exit(1);
    $stage = 'validação do SMTP: confira os campos de e-mail no arquivo privado';
    $mailer = new \App\Services\PasswordResetMailer($mail);
    $mailer->configuredMailer();
    $stage = 'montagem do e-mail: confira site_url e app/Views/emails/password-reset.php';
    $mailer->content('123456');
    echo "OK: configuração de e-mail, URL, rotas e extensões. Nenhuma mensagem enviada.\n";
} catch (Throwable $e) {
    fwrite(STDERR, "[FALHA] Etapa: {$stage}. Nenhuma credencial foi exibida.\n");
    exit(1);
}
