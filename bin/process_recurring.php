<?php

declare(strict_types=1);

use App\Repositories\AccountRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\RecurrenceRepository;
use App\Repositories\TransactionRepository;
use App\Services\RecurrenceService;


/*
|--------------------------------------------------------------------------
| Bootstrap
|--------------------------------------------------------------------------
*/

$app = require dirname(__DIR__)
    . '/app/bootstrap.php';


$pdo = $app['pdo'];


/*
|--------------------------------------------------------------------------
| Dependências necessárias ao Job
|--------------------------------------------------------------------------
*/

$categoryRepository =
    new CategoryRepository(
        $pdo
    );


$accountRepository =
    new AccountRepository(
        $pdo
    );


$transactionRepository =
    new TransactionRepository(
        $pdo
    );


$recurrenceRepository =
    new RecurrenceRepository(
        $pdo
    );


$recurrenceService =
    new RecurrenceService(
        $pdo,
        $recurrenceRepository,
        $transactionRepository,
        $categoryRepository,
        $accountRepository
    );


/*
|--------------------------------------------------------------------------
| Processamento
|--------------------------------------------------------------------------
*/

$result =
    $recurrenceService
        ->processDue();


echo PHP_EOL;

echo 'UaiMoney - Processamento de recorrências'
    . PHP_EOL;

echo 'Data: '
    . date('d/m/Y H:i:s')
    . PHP_EOL;

echo 'Recorrências verificadas: '
    . $result['recorrencias']
    . PHP_EOL;

echo 'Ocorrências criadas: '
    . $result['ocorrencias_criadas']
    . PHP_EOL;

echo 'Recorrências encerradas: '
    . $result['recorrencias_encerradas']
    . PHP_EOL;


if ($result['erros'] !== []) {

    echo PHP_EOL;
    echo 'Erros:'
        . PHP_EOL;


    foreach (
        $result['erros']
        as $erro
    ) {

        echo '- Recorrência #'
            . $erro['recorrencia_id']
            . ': '
            . $erro['mensagem']
            . PHP_EOL;
    }


    exit(1);
}


echo PHP_EOL;
echo 'Processamento concluído.'
    . PHP_EOL;

exit(0);