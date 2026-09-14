<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Services\AuthService;
use App\Core\Csrf;
use DomainException;

final class AuthController
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly Csrf $csrf,
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
                'pageTitle' => 'Entrar - UaiMoney',
                'error' => null,
                'identifier' => '',
                'csrfToken' => $this->csrf->token()
            ],
            'layouts/auth'
        );
    }

    public function login(): void
    {
        $this->validateCsrf();

        $identifier = $_POST['identifier']
            ?? '';

        $senha = $_POST['senha']
            ?? '';

        try {

            $this->authService->login(
                $identifier,
                $senha
            );

            $this->csrf->regenerate();

            $this->redirect('/');

        } catch (DomainException $e) {

            http_response_code(422);

            View::render(
                'auth/login',
                [
                    'basePath' => $this->basePath,
                    'pageTitle' => 'Entrar - UaiMoney',
                    'error' => $e->getMessage(),
                    'identifier' => $identifier,
                    'csrfToken' => $this->csrf->token()
                ],
                'layouts/auth'
            );
        }
    }

    public function showRegister(): void
    {
        $usuario = $this->requireAdmin();

        $created = (
            $_GET['created'] ?? null
        ) === '1';

        View::render(
            'auth/register',
            [
                'usuario' => $usuario,
                'basePath' => $this->basePath,
                'pageTitle' => 'Cadastrar usuário - UaiMoney',
                'error' => null,
                'success' => $created
                    ? 'Usuário cadastrado com sucesso.'
                    : null,
                'nome' => '',
                'login' => '',
                'email' => '',
                'csrfToken' => $this->csrf->token()
            ],
            'layouts/app'
        );
    }

    public function register(): void
    {
        $usuario = $this->requireAdmin();

        $this->validateCsrf();

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
             * NÃO fazemos login com o novo usuário.
             *
             * O administrador permanece autenticado.
             */

            $this->redirect(
                '/register?created=1'
            );

        } catch (DomainException $e) {

            http_response_code(422);

            View::render(
                'auth/register',
                [
                    'usuario' => $usuario,
                    'basePath' => $this->basePath,
                    'pageTitle' => 'Cadastrar usuário - UaiMoney',
                    'error' => $e->getMessage(),
                    'success' => null,
                    'nome' => $nome,
                    'login' => $login,
                    'email' => $email,
                    'csrfToken' => $this->csrf->token()
                ],
                'layouts/app'
            );
        }
    }

    public function logout(): void
    {
        $this->validateCsrf();

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
    private function validateCsrf(): void
    {
        $token = $_POST['_token']
            ?? null;

        if ($this->csrf->validate($token)) {
            return;
        }

        http_response_code(419);

        View::render(
            'errors/419',
            [
                'basePath' => $this->basePath,
                'pageTitle' => 'Sessão expirada - UaiMoney'
            ],
            'layouts/auth'
        );

        exit;
    }

    private function requireAdmin(): array
    {
        $usuario = $this->authService
            ->currentUser();

        /*
         * Nem está logado.
         */
        if ($usuario === null) {
            $this->redirect('/login');
        }

        /*
         * Está logado, mas não é admin.
         */
        if (($usuario['tipo'] ?? null) !== 'admin') {

            http_response_code(403);

            View::render(
                'errors/403',
                [
                    'usuario' => $usuario,
                    'basePath' => $this->basePath,
                    'csrfToken' => $this->csrf->token(),
                    'pageTitle' => 'Acesso negado - UaiMoney'
                ],
                'layouts/app'
            );

            exit;
        }

        return $usuario;
    }
}