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
        private readonly string $basePath,
        private readonly ?\App\Core\RateLimiter $limiter = null
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
            $identity = mb_strtolower(trim($identifier));
            $this->limiter?->hit('login-ip', $_SERVER['REMOTE_ADDR'] ?? 'local', 60);
            $this->limiter?->hit('login-account', $identity, 10);
            $this->authService->login(
                $identifier,
                $senha
            );

            $this->limiter?->clear('login-account', $identity);
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

}
