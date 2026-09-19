<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\View;
use App\Services\AccountService;
use App\Services\AuthService;
use App\Services\CategoryService;
use App\Services\TransactionService;
use App\Services\InstallmentService;
use DomainException;

final class TransactionController extends BaseController
{
    public function __construct(
        AuthService $authService,
        private readonly TransactionService $transactionService,
        private readonly CategoryService $categoryService,
        private readonly AccountService $accountService,
        private readonly InstallmentService $installmentService,
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


        $filters = $this
            ->transactionService
            ->normalizeFilters(
                $_GET
            );


        $this->renderIndex(
            $usuario,
            null,
            $filters
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
        ?string $error = null,
        array $filters = []
    ): void {
        $usuarioId =
            (int) $usuario['id'];


        $filters = $this
            ->transactionService
            ->normalizeFilters(
                $filters
            );


        $transacoes = $this
            ->transactionService
            ->list(
                $usuarioId,
                $filters
            );


        $grupos = $this
            ->categoryService
            ->list(
                $usuarioId
            );


        $contas = $this
            ->accountService
            ->list(
                $usuarioId
            );


        View::render(
            'transactions/index',
            [
                'usuario' => $usuario,

                'transacoes' =>
                    $transacoes,

                'grupos' =>
                    $grupos,

                'contas' =>
                    $contas,

                'filters' =>
                    $filters,

                'error' =>
                    $error,

                'basePath' =>
                    $this->basePath,

                'csrfToken' =>
                    $this->csrf->token(),

                'pageTitle' =>
                    'Movimentações - UaiMoney'
            ],
            'layouts/app'
        );
    }

    public function effect(
        string $id
    ): void {
        $usuario = $this->requireUser();

        $this->validateCsrf();


        $transacaoId = $this->parseId(
            $id
        );


        $contaId = filter_var(
            $_POST['conta_id'] ?? null,
            FILTER_VALIDATE_INT
        );


        if (
            $contaId === false ||
            $contaId <= 0
        ) {
            http_response_code(422);

            $this->renderIndex(
                $usuario,
                'Selecione uma conta válida.'
            );

            return;
        }


        $dataEfetivacao =
            $_POST['data_efetivacao']
            ?? '';


        try {

            $this->transactionService
                ->effect(
                    (int) $usuario['id'],
                    $transacaoId,
                    $contaId,
                    $dataEfetivacao
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


    public function cancel(
        string $id
    ): void {
        $usuario = $this->requireUser();

        $this->validateCsrf();


        $transacaoId = $this->parseId(
            $id
        );


        try {

            $this->transactionService
                ->cancel(
                    (int) $usuario['id'],
                    $transacaoId
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

    public function edit(
        string $id
    ): void {
        $usuario = $this->requireUser();

        $transacaoId = $this->parseId(
            $id
        );


        try {

            $transacao = $this
                ->transactionService
                ->getPendingForEdit(
                    (int) $usuario['id'],
                    $transacaoId
                );


        } catch (DomainException $e) {

            http_response_code(404);

            $this->renderIndex(
                $usuario,
                $e->getMessage()
            );

            return;
        }


        $usuarioId =
            (int) $usuario['id'];


        $grupos = $this
            ->categoryService
            ->list(
                $usuarioId
            );


        $contas = $this
            ->accountService
            ->list(
                $usuarioId
            );


        View::render(
            'transactions/edit',
            [
                'usuario' => $usuario,
                'transacao' => $transacao,
                'grupos' => $grupos,
                'contas' => $contas,
                'basePath' => $this->basePath,
                'csrfToken' => $this->csrf->token(),
                'error' => null,
                'pageTitle'
                => 'Editar movimentação - UaiMoney'
            ],
            'layouts/app'
        );
    }

    public function update(
        string $id
    ): void {
        $usuario = $this->requireUser();

        $this->validateCsrf();


        $transacaoId = $this->parseId(
            $id
        );


        $subgrupoId = filter_var(
            $_POST['subgrupo_id'] ?? null,
            FILTER_VALIDATE_INT
        );


        if (
            $subgrupoId === false ||
            $subgrupoId <= 0
        ) {
            $this->renderEditError(
                $usuario,
                $transacaoId,
                'Categoria inválida.'
            );

            return;
        }


        /*
         * Conta continua opcional
         * enquanto estiver pendente.
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
                $this->renderEditError(
                    $usuario,
                    $transacaoId,
                    'Conta inválida.'
                );

                return;
            }


            $contaId = $parsedContaId;
        }


        try {

            $this->transactionService
                ->updatePending(
                    (int) $usuario['id'],
                    $transacaoId,
                    $subgrupoId,
                    $contaId,
                    $_POST['descricao'] ?? '',
                    $_POST['valor'] ?? '',
                    $_POST['data_competencia'] ?? '',
                    $_POST['data_vencimento'] ?? '',
                    $_POST['meio_pagamento'] ?? null,
                    $_POST['observacao'] ?? null
                );


            $this->redirect(
                '/movimentacoes'
            );


        } catch (DomainException $e) {

            $this->renderEditError(
                $usuario,
                $transacaoId,
                $e->getMessage()
            );
        }
    }

    private function renderEditError(
        array $usuario,
        int $transacaoId,
        string $error
    ): void {
        try {

            $transacao = $this
                ->transactionService
                ->getPendingForEdit(
                    (int) $usuario['id'],
                    $transacaoId
                );


        } catch (DomainException $e) {

            http_response_code(404);

            $this->renderIndex(
                $usuario,
                $e->getMessage()
            );

            return;
        }


        $usuarioId =
            (int) $usuario['id'];


        $grupos = $this
            ->categoryService
            ->list(
                $usuarioId
            );


        $contas = $this
            ->accountService
            ->list(
                $usuarioId
            );


        http_response_code(422);


        View::render(
            'transactions/edit',
            [
                'usuario' => $usuario,
                'transacao' => $transacao,
                'grupos' => $grupos,
                'contas' => $contas,
                'basePath' => $this->basePath,
                'csrfToken' => $this->csrf->token(),
                'error' => $error,
                'pageTitle'
                => 'Editar movimentação - UaiMoney'
            ],
            'layouts/app'
        );
    }

    public function storeInstallment(): void
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


        $totalParcelas = filter_var(
            $_POST['total_parcelas'] ?? null,
            FILTER_VALIDATE_INT
        );


        if (
            $totalParcelas === false ||
            $totalParcelas <= 0
        ) {
            http_response_code(422);

            $this->renderIndex(
                $usuario,
                'Quantidade de parcelas inválida.'
            );

            return;
        }


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


            $contaId =
                $parsedContaId;
        }


        try {

            $this->installmentService
                ->create(
                    (int) $usuario['id'],
                    $subgrupoId,
                    $contaId,
                    $_POST['descricao'] ?? '',
                    $_POST['valor_total'] ?? '',
                    $totalParcelas,
                    $_POST['primeiro_vencimento']
                    ?? '',
                    $_POST['meio_pagamento']
                    ?? null,
                    $_POST['observacao']
                    ?? null
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
}