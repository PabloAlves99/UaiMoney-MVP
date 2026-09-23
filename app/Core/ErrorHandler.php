<?php
declare(strict_types=1);
namespace App\Core;

final class ErrorHandler
{
    public static function register(): void
    {
        ini_set('zend.exception_ignore_args', '1');
        set_exception_handler(static function (\Throwable $error): void {
            $reference = bin2hex(random_bytes(6));
            $directory = dirname(__DIR__, 2) . '/storage/logs';
            if (!is_dir($directory))
                mkdir($directory, 0770, true);
            error_log('[' . date('c') . "] [$reference] " . $error . PHP_EOL, 3, $directory . '/application.log');
            while (ob_get_level() > 0)
                ob_end_clean();
            http_response_code(500);
            if (PHP_SAPI === 'cli') {
                fwrite(STDERR, "Falha inesperada. Referência: $reference. Consulte storage/logs/application.log.\n");
                exit(1);
            }
            header('Content-Type: text/html; charset=UTF-8');
            echo '<!doctype html><html lang="pt-BR"><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>UaiMoney · Erro</title><body><main><h1>Não foi possível concluir esta operação</h1><p>Tente novamente. Se o problema persistir, informe a referência ' . Html::escape($reference) . '.</p><a href="javascript:history.back()">Voltar</a></main></body></html>';
        });
    }
}
