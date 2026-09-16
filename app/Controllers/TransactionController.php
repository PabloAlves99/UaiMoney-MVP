<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\View;
use App\Services\AccountService;
use App\Services\AuthService;
use App\Services\CategoryService;
use App\Services\TransactionService;
use DomainException;

final class TransactionController extends BaseController
{
    public function __construct(
        AuthService $authService,
        private readonly TransactionService $transactionService,
        private readonly CategoryService $categoryService,
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


        $subgrupoId = filter_var(
            $_POST['subgrupo_id'] ?? null,
            FILTER_VALIDATE_INT
        );


        if (
            $subgrupoId === false ||
            $subgrupoId <= 0
        ) {
            http_response_code(422);

            $this->renderIndex(
                $usuario,
                'Categoria inválida.'
            );

            return;
        }


        /*
         * Conta é opcional para pendentes.
         */

        $contaId = null;

        if (
            isset($_POST['conta_id']) &&
            $_POST['conta_id'] !== ''
        ) {

            $parsedContaId = filter_var(
                $_POST['conta_id'],
                FILTER_VALIDATE_INT
            );


            if (
                $parsedContaId === false ||
                $parsedContaId <= 0
            ) {
                http_response_code(422);

                $this->renderIndex(
                    $usuario,
                    'Conta inválida.'
                );

                return;
            }


            $contaId = $parsedContaId;
        }


        try {

            $this->transactionService
                ->create(
                    (int) $usuario['id'],
                    $subgrupoId,
                    $contaId,
                    $_POST['descricao'] ?? '',
                    $_POST['valor'] ?? '',
                    $_POST['data_competencia'] ?? '',
                    $_POST['data_vencimento'] ?? '',
                    $_POST['data_efetivacao'] ?? null,
                    $_POST['status'] ?? '',
                    $_POST['meio_pagamento'] ?? null,
                    $_POST['observacao'] ?? null
                );


            $this->redirect(
                '/movimentacoes'
            );


        } catch (DomainException $e) {

            http_response_code(422);

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
        $usuarioId =
            (int) $usuario['id'];


        $transacoes = $this
            ->transactionService
            ->list($usuarioId);


        $grupos = $this
            ->categoryService
            ->list($usuarioId);


        $contas = $this
            ->accountService
            ->list($usuarioId);


        View::render(
            'transactions/index',
            [
                'usuario' => $usuario,
                'transacoes' => $transacoes,
                'grupos' => $grupos,
                'contas' => $contas,
                'error' => $error,
                'basePath' => $this->basePath,
                'csrfToken' => $this->csrf->token(),
                'pageTitle'
                    => 'Movimentações - UaiMoney'
            ],
            'layouts/app'
        );
    }
}