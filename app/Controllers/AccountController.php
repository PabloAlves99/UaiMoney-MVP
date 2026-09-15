<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\View;
use App\Services\AccountService;
use App\Services\AuthService;
use DomainException;

final class AccountController extends BaseController
{
    public function __construct(
        AuthService $authService,
        private readonly AccountService $accountService,
        Csrf $csrf,
        string $basePath
    ) {
        parent::__construct(
            $authService,
            $csrf,
            $basePath
        );
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

}