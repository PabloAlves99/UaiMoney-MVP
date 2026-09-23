<?php

declare(strict_types=1);

use App\Core\Database;
use App\Core\Session;
use App\Core\Csrf;

require_once dirname(__DIR__) . '/vendor/autoload.php';
\App\Core\ErrorHandler::register();
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', dirname(__DIR__).'/storage/logs/php.log');
if (PHP_SAPI !== 'cli') {
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
    header('X-Frame-Options: DENY');
    header('Cache-Control: no-store');
}



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