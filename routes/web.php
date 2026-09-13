<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Core\Router;
use App\Core\View;
use App\Services\AuthService;

return function (
    Router $router,
    AuthController $authController,
    AuthService $authService,
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


    /*
     * Home
     */

    $router->get(
        '/',
        function () use (
            $authService,
            $basePath
        ): void {

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
                    'basePath' => $basePath
                ]
            );
        }
    );
};