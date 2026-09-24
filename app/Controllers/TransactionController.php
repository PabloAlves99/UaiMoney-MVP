<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Services\AccountService;
use App\Services\AuthService;
use App\Services\CategoryService;
use App\Services\TransactionService;
use App\Services\InstallmentService;
use App\Services\RecurrenceService;
use DomainException;

final class TransactionController extends BaseController
{
    public function __construct(
        AuthService $authService,
        private readonly TransactionService $transactionService,
        private readonly CategoryService $categoryService,
        private readonly AccountService $accountService,
        private readonly InstallmentService $installmentService,
        private readonly RecurrenceService $recurrenceService,
        Csrf $csrf,
        string $basePath
    ) {
        parent::__construct(
            $authService,
            $csrf,
            $basePath
        );
    }


    private function renderIndex(array $usuario, ?string $error = null, array $filters = []): void
    {
        if ($error !== null) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => $error];
            $_SESSION['form_old'] = array_diff_key($_POST, array_flip(['_token', '_operation']));
        }
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
        $target = str_ends_with($uri, '/parcelamentos') ? '/movimentacoes/avancado'
            : (str_ends_with($uri, '/recorrencias') ? '/movimentacoes/nova?modo=recorrente' : '/movimentacoes/nova');
        $this->redirect($error ? $target : '/movimentacoes');
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

    public function storeRecurrence(): void
    {
        $usuario = $this->requireUser();

        $this->validateCsrf();


        $subgrupoId = filter_var(
            $_POST['subgrupo_id'] ?? null,
            FILTER_VALIDATE_INT
        );


        if (
            $subgrupoId === false
            ||
            $subgrupoId <= 0
        ) {

            http_response_code(422);

            $this->renderIndex(
                $usuario,
                'Categoria inválida.'
            );

            return;
        }


        $intervalo = filter_var(
            $_POST['intervalo'] ?? 1,
            FILTER_VALIDATE_INT
        );


        if (
            $intervalo === false
            ||
            $intervalo <= 0
        ) {

            http_response_code(422);

            $this->renderIndex(
                $usuario,
                'Intervalo inválido.'
            );

            return;
        }


        /*
         * Conta opcional.
         */

        $contaId = null;


        if (
            isset($_POST['conta_id'])
            &&
            $_POST['conta_id'] !== ''
        ) {

            $parsedContaId =
                filter_var(
                    $_POST['conta_id'],
                    FILTER_VALIDATE_INT
                );


            if (
                $parsedContaId === false
                ||
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


        /*
         * Duração.
         */

        $tipoDuracao =
            $_POST['tipo_duracao']
            ?? 'indefinida';


        $totalOcorrencias =
            null;


        if (
            $tipoDuracao
            === 'quantidade'
        ) {

            $parsedTotal =
                filter_var(
                    $_POST[
                        'total_ocorrencias'
                    ] ?? null,
                    FILTER_VALIDATE_INT
                );


            if (
                $parsedTotal === false
                ||
                $parsedTotal <= 0
            ) {

                http_response_code(422);

                $this->renderIndex(
                    $usuario,
                    'Quantidade de ocorrências inválida.'
                );

                return;
            }


            $totalOcorrencias =
                $parsedTotal;
        }


        try {

            $this
                ->recurrenceService
                ->create(
                    (int) $usuario['id'],
                    $subgrupoId,
                    $contaId,
                    $_POST['descricao']
                    ?? '',
                    $_POST['valor']
                    ?? '',
                    $_POST['frequencia']
                    ?? '',
                    $intervalo,
                    $_POST['data_inicio']
                    ?? '',
                    $totalOcorrencias,
                    $_POST['meio_pagamento']
                    ?? null,
                    $_POST['observacao']
                    ?? null
                );


            $this->redirect(
                '/recorrencias'
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
