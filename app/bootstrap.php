<?php

declare(strict_types=1);

use App\Core\Database;
use App\Core\Session;
use App\Core\Csrf;

require_once dirname(__DIR__) . '/vendor/autoload.php';


/*
|--------------------------------------------------------------------------
| Configurações
|--------------------------------------------------------------------------
*/

$appConfig = require dirname(__DIR__) . '/config/app.php';

$databaseConfig = require dirname(__DIR__) . '/config/database.php';

$sessionConfig = require dirname(__DIR__) . '/config/session.php';


/*
|--------------------------------------------------------------------------
| Timezone
|--------------------------------------------------------------------------
*/

date_default_timezone_set(
    $appConfig['timezone']
);


/*
|--------------------------------------------------------------------------
| Banco de dados
|--------------------------------------------------------------------------
*/

$database = new Database(
    $databaseConfig
);

$pdo = $database->connect();


/*
|--------------------------------------------------------------------------
| Sessão
|--------------------------------------------------------------------------
*/

$session = new Session(
    $sessionConfig
);

$session->start();


$csrf = new Csrf(
    $session
);


/*
|--------------------------------------------------------------------------
| Retorno do bootstrap
|--------------------------------------------------------------------------
*/

return [
    'config' => $appConfig,
    'database' => $database,
    'pdo' => $pdo,
    'session' => $session,
    'csrf' => $csrf
];