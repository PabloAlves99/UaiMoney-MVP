<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\View;
use App\Services\AuthService;

abstract class BaseController
{
    public function __construct(
        protected readonly AuthService $authService,
        protected readonly Csrf $csrf,
        protected readonly string $basePath
    ) {
    }


    protected function requireUser(): array
    {
        $usuario = $this
            ->authService
            ->currentUser();

        if ($usuario === null) {
            $this->redirect('/login');
        }

        return $usuario;
    }


    protected function validateCsrf(): void
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


    protected function parseId(
        string $id
    ): int {
        $parsedId = filter_var(
            $id,
            FILTER_VALIDATE_INT
        );

        if (
            $parsedId === false ||
            $parsedId <= 0
        ) {
            http_response_code(404);

            echo '404 - Recurso não encontrado';

            exit;
        }

        return $parsedId;
    }


    protected function redirect(
        string $path
    ): never {
        header(
            'Location: '
            . $this->basePath
            . '/'
            . ltrim($path, '/')
        );

        exit;
    }
}