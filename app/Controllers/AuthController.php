<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Services\AuthService;
use DomainException;

final class AuthController
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly string $basePath
    ) {
    }

    public function showLogin(): void
    {
        if ($this->authService->isAuthenticated()) {
            $this->redirect('/');

            return;
        }

        View::render(
            'auth/login',
            [
                'basePath' => $this->basePath,
                'error' => null,
                'identifier' => ''
            ]
        );
    }

    public function login(): void
    {
        $identifier = $_POST['identifier']
            ?? '';

        $senha = $_POST['senha']
            ?? '';

        try {

            $this->authService->login(
                $identifier,
                $senha
            );

            $this->redirect('/');

        } catch (DomainException $e) {

            http_response_code(422);

            View::render(
                'auth/login',
                [
                    'basePath' => $this->basePath,
                    'error' => $e->getMessage(),
                    'identifier' => $identifier
                ]
            );
        }
    }

    public function showRegister(): void
    {
        if ($this->authService->isAuthenticated()) {
            $this->redirect('/');

            return;
        }

        View::render(
            'auth/register',
            [
                'basePath' => $this->basePath,
                'error' => null,
                'nome' => '',
                'login' => '',
                'email' => ''
            ]
        );
    }

    public function register(): void
    {
        $nome = $_POST['nome']
            ?? '';

        $login = $_POST['login']
            ?? '';

        $email = $_POST['email']
            ?? '';

        $senha = $_POST['senha']
            ?? '';

        $confirmacaoSenha = $_POST['confirmacao_senha']
            ?? '';

        try {

            $this->authService->register(
                $nome,
                $login,
                $email,
                $senha,
                $confirmacaoSenha
            );

            /*
             * Após criar a conta,
             * já autenticamos o usuário.
             */
            $this->authService->login(
                $login,
                $senha
            );

            $this->redirect('/');

        } catch (DomainException $e) {

            http_response_code(422);

            View::render(
                'auth/register',
                [
                    'basePath' => $this->basePath,
                    'error' => $e->getMessage(),
                    'nome' => $nome,
                    'login' => $login,
                    'email' => $email
                ]
            );
        }
    }

    public function logout(): void
    {
        $this->authService->logout();

        $this->redirect(
            '/login'
        );
    }

    private function redirect(
        string $path
    ): never {
        header(
            'Location: '
            . $this->url($path)
        );

        exit;
    }

    private function url(
        string $path
    ): string {
        if ($path === '/') {
            return $this->basePath . '/';
        }

        return $this->basePath
            . '/'
            . ltrim($path, '/');
    }
}