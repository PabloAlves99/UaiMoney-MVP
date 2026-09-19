<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\CategoryController;
use App\Controllers\UserPreferenceController;
use App\Controllers\AccountController;
use App\Controllers\TransactionController;

use App\Core\Router;

use App\Repositories\CategoryRepository;
use App\Repositories\UserRepository;
use App\Repositories\AccountRepository;
use App\Repositories\TransactionRepository;

use App\Services\AuthService;
use App\Services\CategoryService;
use App\Services\UserPreferenceService;
use App\Services\AccountService;
use App\Services\TransactionService;

/*
|--------------------------------------------------------------------------
| Bootstrap
|--------------------------------------------------------------------------
*/

$app = require dirname(__DIR__)
    . '/app/bootstrap.php';

$pdo = $app['pdo'];
$session = $app['session'];
$csrf = $app['csrf'];


/*
|--------------------------------------------------------------------------
| Base path
|--------------------------------------------------------------------------
*/

$basePath = $app['config']['base_path']
    ?? '';


/*
|--------------------------------------------------------------------------
| Repositories
|--------------------------------------------------------------------------
*/

$userRepository = new UserRepository(
    $pdo
);

$categoryRepository = new CategoryRepository(
    $pdo
);

$accountRepository = new AccountRepository(
    $pdo
);

$transactionRepository = new TransactionRepository(
    $pdo
);

/*
|--------------------------------------------------------------------------
| Services
|--------------------------------------------------------------------------
*/

$authService = new AuthService(
    $userRepository,
    $session
);

$preferenceService = new UserPreferenceService(
    $userRepository
);

$categoryService = new CategoryService(
    $categoryRepository
);

$accountService = new AccountService(
    $accountRepository
);

$transactionService = new TransactionService(
    $transactionRepository,
    $categoryRepository,
    $accountRepository
);

/*
|--------------------------------------------------------------------------
| Controllers
|--------------------------------------------------------------------------
*/

$authController = new AuthController(
    $authService,
    $csrf,
    $basePath
);

$userPreferenceController = new UserPreferenceController(
    $authService,
    $preferenceService,
    $csrf,
    $basePath
);

$categoryController = new CategoryController(
    $authService,
    $categoryService,
    $csrf,
    $basePath
);

$accountController = new AccountController(
    $authService,
    $accountService,
    $transactionService,
    $categoryService,
    $csrf,
    $basePath
);

$transactionController = new TransactionController(
    $authService,
    $transactionService,
    $categoryService,
    $accountService,
    $csrf,
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
    $userPreferenceController,
    $categoryController,
    $accountController,
    $transactionController,
    $authService,
    $csrf,
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
