<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$stage = 'dependências PHP: execute composer install ou envie vendor/ completo';
try {
    require dirname(__DIR__) . '/vendor/autoload.php';
    $stage = 'leitura da configuração: execute php bin/check_config.php';
    $config = require dirname(__DIR__) . '/config/mail.php';
    $stage = 'configuração SMTP: confira senha de app, remetente, servidor e porta';
    $mail = (new \App\Services\PasswordResetMailer($config))->configuredMailer();
    $mail->addAddress($config['from']);
    $mail->Subject = 'UaiMoney - Teste de envio';
    $mail->Body = 'O envio de e-mails do UaiMoney foi configurado. Esta mensagem é somente um teste; não contém código de recuperação.';
    $stage = 'conexão SMTP';
    $mail->send();
    echo "Mensagem de teste aceita pelo Gmail. Confira sua caixa de entrada.\n";
} catch (Throwable $e) {
    // Do not print SMTP debug output, credentials or raw provider exceptions.
    if ($stage === 'conexão SMTP') {
        $detail = strtolower($e->getMessage());
        $stage = match (true) {
            str_contains($detail, 'authenticate') => 'autenticação recusada pelo Gmail: confira o usuário e gere uma senha de app para essa mesma conta',
            str_contains($detail, 'connect'), str_contains($detail, 'smtp error') && str_contains($detail, 'connection') => 'conexão com Gmail: confira saída para smtp.gmail.com:587, DNS e certificados TLS do servidor',
            default => 'envio SMTP recusado: confira a conta remetente e os limites de envio do provedor',
        };
    }
    fwrite(STDERR, "[FALHA] {$stage}. Nenhuma credencial foi exibida.\n");
    exit(1);
}
