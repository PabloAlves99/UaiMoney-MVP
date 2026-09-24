<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\CategoryController;
use App\Controllers\UserPreferenceController;
use App\Controllers\AccountController;
use App\Controllers\TransactionController;
use App\Controllers\RecurrenceController;
use App\Controllers\AccountShareController;

use App\Core\Router;

use App\Repositories\CategoryRepository;
use App\Repositories\UserRepository;
use App\Repositories\AccountRepository;
use App\Repositories\TransactionRepository;
use App\Repositories\InstallmentRepository;
use App\Repositories\RecurrenceRepository;
use App\Repositories\AccountShareRepository;

use App\Services\AuthService;
use App\Services\CategoryService;
use App\Services\UserPreferenceService;
use App\Services\AccountService;
use App\Services\TransactionService;
use App\Services\InstallmentService;
use App\Services\RecurrenceService;
use App\Services\AccountExportService;

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

$installmentRepository = new InstallmentRepository(
    $pdo
);

$recurrenceRepository = new RecurrenceRepository(
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

$installmentService = new InstallmentService(
    $pdo,
    $installmentRepository,
    $transactionRepository,
    $categoryRepository,
    $accountRepository
);

$recurrenceService = new RecurrenceService(
    $pdo,
    $recurrenceRepository,
    $transactionRepository,
    $categoryRepository,
    $accountRepository
);

$accountService = new AccountService(
    $accountRepository
);

$accountExportService =
    new AccountExportService();

/*
|--------------------------------------------------------------------------
| Controllers
|--------------------------------------------------------------------------
*/

$authController = new AuthController(
    $authService,
    $csrf,
    $basePath,
    new \App\Core\RateLimiter($pdo)
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
    $accountExportService,
    $csrf,
    $basePath
);

$transactionController = new TransactionController(
    $authService,
    $transactionService,
    $categoryService,
    $accountService,
    $installmentService,
    $recurrenceService,
    $csrf,
    $basePath
);

$recurrenceController = new RecurrenceController(
    $authService,
    $recurrenceService,
    $categoryService,
    $accountService,
    $csrf,
    $basePath
);

$accountShareController = new AccountShareController(
    $authService,
    $csrf,
    $basePath,
    $accountRepository,
    new AccountShareRepository($pdo),
    new \App\Core\RateLimiter($pdo),
    new \App\Services\PublicAccountExportService()
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
    $recurrenceController,
    $authService,
    $csrf,
    $basePath
);

$router->get('/contas/{id}/compartilhar', [$accountShareController, 'manage']);
$router->post('/contas/{id}/compartilhar', [$accountShareController, 'create']);
$router->post('/contas/{id}/compartilhar/desativar', [$accountShareController, 'disable']);
$router->get('/compartilhado/{token}', [$accountShareController, 'publicPage']);
$router->post('/compartilhado/{token}', [$accountShareController, 'publicPage']);
$router->get('/compartilhado/{token}/exportar/{format}', [$accountShareController, 'export']);


require dirname(__DIR__) . '/routes/finance.php';

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
