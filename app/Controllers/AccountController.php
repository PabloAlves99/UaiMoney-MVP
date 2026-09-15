<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\View;
use App\Services\AccountService;
use App\Services\AuthService;
use DomainException;

final class AccountController
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly AccountService $accountService,
        private readonly Csrf $csrf,
        private readonly string $basePath
    ) {
    }


    public function index(): void
    {
        $usuario = $this->requireUser();

        $this->renderIndex(
            $usuario
        );
    }


    public function store(): void
    {
        $usuario = $this->requireUser();

        $this->validateCsrf();


        try {

            $this->accountService
                ->create(
                    (int) $usuario['id'],
                    $_POST['nome'] ?? '',
                    $_POST['tipo'] ?? '',
                    $_POST['instituicao'] ?? null,
                    $_POST['saldo_inicial'] ?? '',
                    $_POST['saldo_inicial_em'] ?? ''
                );


            $this->redirect(
                '/contas'
            );


        } catch (DomainException $e) {

            http_response_code(422);

            $this->renderIndex(
                $usuario,
                $e->getMessage()
            );
        }
    }


    public function deactivate(
        string $id
    ): void {
        $usuario = $this->requireUser();

        $this->validateCsrf();


        $contaId = $this->parseId(
            $id
        );


        try {

            $this->accountService
                ->deactivate(
                    (int) $usuario['id'],
                    $contaId
                );


            $this->redirect(
                '/contas'
            );


        } catch (DomainException $e) {

            http_response_code(404);

            $this->renderIndex(
                $usuario,
                $e->getMessage()
            );
        }
    }


    private function renderIndex(
        array $usuario,
        ?string $error = null
    ): void {
        $contas = $this
            ->accountService
            ->list(
                (int) $usuario['id']
            );


        View::render(
            'accounts/index',
            [
                'usuario' => $usuario,
                'contas' => $contas,
                'error' => $error,
                'basePath' => $this->basePath,
                'csrfToken'
                => $this->csrf->token(),
                'pageTitle'
                => 'Contas - UaiMoney'
            ],
            'layouts/app'
        );
    }


    private function requireUser(): array
    {
        $usuario = $this
            ->authService
            ->currentUser();


        if ($usuario === null) {
            $this->redirect(
                '/login'
            );
        }


        return $usuario;
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
                'basePath'
                => $this->basePath,

                'pageTitle'
                => 'Sessão expirada - UaiMoney'
            ],
            'layouts/auth'
        );


        exit;
    }


    private function parseId(
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


    private function redirect(
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