<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\UserPreferenceController;
use App\Controllers\CategoryController;
use App\Controllers\AccountController;
use App\Controllers\TransactionController;
use App\Core\Csrf;
use App\Core\Router;
use App\Core\View;
use App\Services\AuthService;

return function (
    Router $router,
    AuthController $authController,
    UserPreferenceController $userPreferenceController,
    CategoryController $categoryController,
    AccountController $accountController,
    TransactionController $transactionController,
    AuthService $authService,
    Csrf $csrf,
    string $basePath
): void {
    /*
     * Autenticação
     */

    $router->get(
        '/login',
        [$authController, 'showLogin']
    );

    $router->post(
        '/login',
        [$authController, 'login']
    );


    $router->get(
        '/register',
        [$authController, 'showRegister']
    );

    $router->post(
        '/register',
        [$authController, 'register']
    );


    $router->post(
        '/logout',
        [$authController, 'logout']
    );

    $router->post(
        '/preferencias/tema',
        [$userPreferenceController, 'updateTheme']
    );


    /*
     * Home
     */

    $router->get(
        '/',
        function () use ($authService, $csrf, $basePath): void {

            $usuario = $authService
                ->currentUser();

            if ($usuario === null) {

                header(
                    'Location: '
                        . $basePath
                        . '/login'
                );

                exit;
            }

            View::render(
                'home',
                [
                    'usuario' => $usuario,
                    'basePath' => $basePath,
                    'csrfToken' => $csrf->token(),
                    'pageTitle' => 'Início - UaiMoney'
                ],
                'layouts/app'
            );
        }
    );

    /*
    |--------------------------------------------------------------------------
    | Categorias
    |--------------------------------------------------------------------------
    */

    $router->get(
        '/categorias',
        [$categoryController, 'index']
    );


    $router->post(
        '/categorias/grupos',
        [$categoryController, 'storeGroup']
    );


    $router->post(
        '/categorias/subgrupos',
        [$categoryController, 'storeSubgroup']
    );


    $router->post(
        '/categorias/grupos/{id}/desativar',
        [$categoryController, 'deactivateGroup']
    );


    $router->post(
        '/categorias/subgrupos/{id}/desativar',
        [$categoryController, 'deactivateSubgroup']
    );

    /*
    |--------------------------------------------------------------------------
    | Contas
    |--------------------------------------------------------------------------
    */

    $router->get(
        '/contas',
        [$accountController, 'index']
    );


    $router->post(
        '/contas',
        [$accountController, 'store']
    );


    $router->post(
        '/contas/{id}/desativar',
        [$accountController, 'deactivate']
    );

    /*
    |--------------------------------------------------------------------------
    | Movimentações
    |--------------------------------------------------------------------------
    */

    $router->get(
        '/movimentacoes',
        [$transactionController, 'index']
    );
};
