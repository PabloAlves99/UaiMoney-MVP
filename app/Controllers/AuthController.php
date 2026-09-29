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
        private readonly ?\App\Core\RateLimiter $limiter = null,
        private readonly ?\App\Services\PasswordResetService $passwordReset = null
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

    public function showForgotPassword(): void
    {
        $this->resetView('request');
    }

    public function showResetPassword(): void
    {
        header('Referrer-Policy: no-referrer');
        $token = is_string($_GET['token'] ?? null) ? $_GET['token'] : '';
        if (!$this->passwordReset->validLink($token)) {
            http_response_code(422);
            $this->resetView('invalid', 'Link inválido ou expirado. Solicite um novo e-mail.');
            return;
        }
        $this->resetView('reset', resetToken: $token);
    }

    public function sendResetCode(): void
    {
        $this->validateCsrf();
        $email = is_string($_POST['email'] ?? null) ? $_POST['email'] : '';
        try {
            $this->passwordReset->request($email, $_SERVER['REMOTE_ADDR'] ?? 'local');
            $this->resetView('sent', null, '', 'Se o e-mail estiver cadastrado e ativo, você receberá uma mensagem com o código e o botão para redefinir sua senha.');
        } catch (DomainException $e) {
            http_response_code(422);
            $this->resetView('request', $e->getMessage(), $email);
        }
    }

    public function resetPassword(): void
    {
        header('Referrer-Policy: no-referrer');
        $this->validateCsrf();
        $input = static fn(string $key): string => is_string($_POST[$key] ?? null) ? $_POST[$key] : '';
        try {
            $this->passwordReset->reset($input('reset_token'), trim($input('code')), $input('senha'), $input('confirmacao'), $_SERVER['REMOTE_ADDR'] ?? 'local');
            $this->csrf->regenerate();
            $this->resetView('done', null, '', 'Senha atualizada. Entre usando sua nova senha.');
        } catch (DomainException $e) {
            http_response_code(422);
            $valid = $this->passwordReset->validLink($input('reset_token'));
            $this->resetView($valid ? 'reset' : 'invalid', $e->getMessage(), resetToken: $valid ? $input('reset_token') : '');
        }
    }

    private function resetView(string $step, ?string $error = null, string $email = '', ?string $message = null, string $resetToken = ''): void
    {
        View::render('auth/password-reset', [
            'basePath' => $this->basePath, 'pageTitle' => 'Redefinir senha - UaiMoney',
            'csrfToken' => $this->csrf->token(), 'step' => $step,
            'error' => $error, 'email' => $email, 'message' => $message,
            'resetToken' => $resetToken,
        ], 'layouts/auth');
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
