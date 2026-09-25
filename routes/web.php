<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\AdminUserController;
use App\Controllers\UserPreferenceController;
use App\Controllers\CategoryController;
use App\Controllers\AccountController;
use App\Controllers\TransactionController;
use App\Controllers\RecurrenceController;
use App\Core\Csrf;
use App\Core\Router;
use App\Core\View;
use App\Services\AuthService;

return function (Router $router, AuthController $authController, UserPreferenceController $userPreferenceController, CategoryController $categoryController, AccountController $accountController, TransactionController $transactionController, RecurrenceController $recurrenceController, AdminUserController $adminUserController, AuthService $authService, Csrf $csrf, string $basePath): void {
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


    $router->get('/admin/usuarios', [$adminUserController, 'index']);
    $router->post('/admin/usuarios', [$adminUserController, 'save']);
    $router->get('/admin/usuarios/{id}/editar', [$adminUserController, 'index']);
    $router->post('/admin/usuarios/{id}/editar', [$adminUserController, 'save']);


    $router->post(
        '/logout',
        [$authController, 'logout']
    );

    $router->post(
        '/preferencias/tema',
        [$userPreferenceController, 'updateTheme']
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




    $router->get(
        '/contas/{id}/exportar/pdf',
        [$accountController, 'exportPdf']
    );


    $router->get(
        '/contas/{id}/exportar/excel',
        [$accountController, 'exportExcel']
    );


    $router->get(
        '/contas/{id}/editar',
        [$accountController, 'edit']
    );


    $router->post(
        '/contas/{id}/editar',
        [$accountController, 'update']
    );


    $router->post(
        '/contas/{id}/desativar',
        [$accountController, 'deactivate']
    );
    $router->post('/contas/{id}/reativar', [$accountController, 'reactivate']);

    /*
|--------------------------------------------------------------------------
| Movimentações
|--------------------------------------------------------------------------
*/









    $router->post(
        '/movimentacoes/{id}/efetivar',
        [$transactionController, 'effect']
    );

    $router->post(
        '/movimentacoes/{id}/cancelar',
        [$transactionController, 'cancel']
    );


    /*
    |--------------------------------------------------------------------------
    | Parcelamentos
    |--------------------------------------------------------------------------
    */

    $router->post(
        '/parcelamentos',
        [$transactionController, 'storeInstallment']
    );


    /*
    |--------------------------------------------------------------------------
    | Recorrências
    |--------------------------------------------------------------------------
    |
    | O cadastro continua no TransactionController por enquanto.
    | O gerenciamento fica no RecurrenceController.
    |
    */

    $router->post(
        '/recorrencias',
        [$transactionController, 'storeRecurrence']
    );

    $router->get(
        '/recorrencias',
        [$recurrenceController, 'index']
    );

    $router->post(
        '/recorrencias/processar',
        [$recurrenceController, 'process']
    );

    $router->post(
        '/recorrencias/{id}/pausar',
        [$recurrenceController, 'pause']
    );

    $router->post(
        '/recorrencias/{id}/retomar',
        [$recurrenceController, 'resume']
    );

    $router->post(
        '/recorrencias/{id}/encerrar',
        [$recurrenceController, 'finish']
    );

    $router->get(
        '/recorrencias/{id}/editar',
        [
            $recurrenceController,
            'edit'
        ]
    );


    $router->post(
        '/recorrencias/{id}/editar',
        [
            $recurrenceController,
            'update'
        ]
    );
};
