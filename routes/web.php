<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\UserPreferenceController;
use App\Core\Csrf;
use App\Core\Router;
use App\Core\View;
use App\Services\AuthService;

return function (Router $router, AuthController $authController, UserPreferenceController $userPreferenceController, AuthService $authService, Csrf $csrf, string $basePath): void {

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
};