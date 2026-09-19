<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\View;
use App\Services\AuthService;
use App\Services\RecurrenceService;
use App\Services\AccountService;
use App\Services\CategoryService;
use DomainException;

final class RecurrenceController extends BaseController
{
    public function __construct(
        AuthService $authService,
        private readonly RecurrenceService $recurrenceService,
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

        $recorrencias = $this
            ->recurrenceService
            ->list(
                (int) $usuario['id']
            );


        View::render(
            'recurrences/index',
            [
                'usuario' =>
                    $usuario,

                'recorrencias' =>
                    $recorrencias,

                'basePath' =>
                    $this->basePath,

                'csrfToken' =>
                    $this->csrf->token(),

                'pageTitle' =>
                    'Recorrências - UaiMoney'
            ],
            'layouts/app'
        );
    }


    public function process(): void
    {
        $usuario = $this->requireUser();

        $this->validateCsrf();


        $result = $this
            ->recurrenceService
            ->processDue(
                (int) $usuario['id']
            );


        $query = http_build_query([
            'processado' => 1,

            'criadas' =>
                $result[
                    'ocorrencias_criadas'
                ],

            'encerradas' =>
                $result[
                    'recorrencias_encerradas'
                ],

            'erros' =>
                count(
                    $result['erros']
                )
        ]);


        $this->redirect(
            '/recorrencias?'
            . $query
        );
    }


    public function pause(
        string $id
    ): void {

        $usuario = $this->requireUser();

        $this->validateCsrf();

        $recorrenciaId = $this->parseId(
            $id
        );


        try {

            $this
                ->recurrenceService
                ->pause(
                    (int) $usuario['id'],
                    $recorrenciaId
                );


            $this->redirect(
                '/recorrencias?acao=pausada'
            );


        } catch (DomainException $e) {

            $this->redirect(
                '/recorrencias?erro='
                . urlencode(
                    $e->getMessage()
                )
            );
        }
    }


    public function resume(
        string $id
    ): void {

        $usuario = $this->requireUser();

        $this->validateCsrf();

        $recorrenciaId = $this->parseId(
            $id
        );


        try {

            $this
                ->recurrenceService
                ->resume(
                    (int) $usuario['id'],
                    $recorrenciaId
                );


            $this->redirect(
                '/recorrencias?acao=retomada'
            );


        } catch (DomainException $e) {

            $this->redirect(
                '/recorrencias?erro='
                . urlencode(
                    $e->getMessage()
                )
            );
        }
    }


    public function finish(
        string $id
    ): void {

        $usuario = $this->requireUser();

        $this->validateCsrf();

        $recorrenciaId = $this->parseId(
            $id
        );


        try {

            $this
                ->recurrenceService
                ->finish(
                    (int) $usuario['id'],
                    $recorrenciaId
                );


            $this->redirect(
                '/recorrencias?acao=encerrada'
            );


        } catch (DomainException $e) {

            $this->redirect(
                '/recorrencias?erro='
                . urlencode(
                    $e->getMessage()
                )
            );
        }
    }

    public function edit(
        string $id
    ): void {

        $usuario =
            $this->requireUser();


        $recorrenciaId =
            $this->parseId(
                $id
            );


        try {

            $recorrencia =
                $this
                    ->recurrenceService
                    ->getForEdit(
                        (int) $usuario['id'],
                        $recorrenciaId
                    );


        } catch (DomainException $e) {

            $this->redirect(
                '/recorrencias?erro='
                . urlencode(
                    $e->getMessage()
                )
            );

            return;
        }


        $usuarioId =
            (int) $usuario['id'];


        $grupos =
            $this
                ->categoryService
                ->list(
                    $usuarioId
                );


        $contas =
            $this
                ->accountService
                ->list(
                    $usuarioId
                );


        View::render(
            'recurrences/edit',
            [
                'usuario' =>
                    $usuario,

                'recorrencia' =>
                    $recorrencia,

                'grupos' =>
                    $grupos,

                'contas' =>
                    $contas,

                'basePath' =>
                    $this->basePath,

                'csrfToken' =>
                    $this->csrf->token(),

                'pageTitle' =>
                    'Editar recorrência - UaiMoney'
            ],
            'layouts/app'
        );
    }

    public function update(
        string $id
    ): void {

        $usuario =
            $this->requireUser();


        $this->validateCsrf();


        $recorrenciaId =
            $this->parseId(
                $id
            );


        /*
         * Categoria
         */

        $subgrupoId =
            filter_var(
                $_POST['subgrupo_id']
                ?? null,
                FILTER_VALIDATE_INT
            );


        if (
            $subgrupoId === false
            ||
            $subgrupoId <= 0
        ) {

            $this->redirect(
                '/recorrencias?erro='
                . urlencode(
                    'Categoria inválida.'
                )
            );

            return;
        }


        /*
         * Intervalo
         */

        $intervalo =
            filter_var(
                $_POST['intervalo']
                ?? null,
                FILTER_VALIDATE_INT
            );


        if (
            $intervalo === false
            ||
            $intervalo <= 0
        ) {

            $this->redirect(
                '/recorrencias?erro='
                . urlencode(
                    'Intervalo inválido.'
                )
            );

            return;
        }


        /*
         * Conta
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

                $this->redirect(
                    '/recorrencias?erro='
                    . urlencode(
                        'Conta inválida.'
                    )
                );

                return;
            }


            $contaId =
                $parsedContaId;
        }


        /*
         * Quantidade
         */

        $totalOcorrencias = null;


        if (
            ($_POST['tipo_duracao'] ?? '')
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

                $this->redirect(
                    '/recorrencias?erro='
                    . urlencode(
                        'Quantidade de ocorrências inválida.'
                    )
                );

                return;
            }


            $totalOcorrencias =
                $parsedTotal;
        }


        try {

            $this
                ->recurrenceService
                ->update(
                    (int) $usuario['id'],
                    $recorrenciaId,
                    $subgrupoId,
                    $contaId,
                    $_POST['descricao'] ?? '',
                    $_POST['valor'] ?? '',
                    $_POST['frequencia'] ?? '',
                    $intervalo,
                    $_POST[
                        'proxima_ocorrencia'
                    ] ?? '',
                    $totalOcorrencias,
                    $_POST[
                        'meio_pagamento'
                    ] ?? null,
                    $_POST[
                        'observacao'
                    ] ?? null
                );


            $this->redirect(
                '/recorrencias?acao=editada'
            );


        } catch (DomainException $e) {

            $this->redirect(
                '/recorrencias?erro='
                . urlencode(
                    $e->getMessage()
                )
            );
        }
    }
}