<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Core\Router;
use App\Repositories\UserRepository;
use App\Services\AuthService;


/*
|--------------------------------------------------------------------------
| Bootstrap
|--------------------------------------------------------------------------
*/

$app = require dirname(__DIR__)
    . '/app/bootstrap.php';

$pdo = $app['pdo'];
$session = $app['session'];


/*
|--------------------------------------------------------------------------
| Base path
|--------------------------------------------------------------------------
*/

$basePath = $app['config']['base_path']
    ?? '';

/*
|--------------------------------------------------------------------------
| Dependências
|--------------------------------------------------------------------------
*/

$userRepository = new UserRepository(
    $pdo
);

$authService = new AuthService(
    $userRepository,
    $session
);

$authController = new AuthController(
    $authService,
    $basePath
);


/*
|--------------------------------------------------------------------------
| Router
|--------------------------------------------------------------------------
*/

$router = new Router(
    $basePath
);


/*
|--------------------------------------------------------------------------
| Rotas
|--------------------------------------------------------------------------
*/

$registerRoutes = require dirname(__DIR__)
    . '/routes/web.php';

$registerRoutes(
    $router,
    $authController,
    $authService,
    $basePath
);


/*
|--------------------------------------------------------------------------
| Dispatch
|--------------------------------------------------------------------------
*/

$router->dispatch(
    $_SERVER['REQUEST_METHOD']
    ?? 'GET',

    $_SERVER['REQUEST_URI']
    ?? '/'
);