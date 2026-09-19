<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Money;
use App\Repositories\AccountRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\RecurrenceRepository;
use App\Repositories\TransactionRepository;
use DateTimeImmutable;
use DomainException;
use PDO;
use Throwable;

final class RecurrenceService
{
    private const FREQUENCIES = [
        'diaria',
        'semanal',
        'mensal',
        'anual'
    ];


    private const PAYMENT_METHODS = [
        'pix',
        'dinheiro',
        'debito',
        'credito',
        'boleto',
        'transferencia',
        'outro'
    ];


    public function __construct(
        private readonly PDO $pdo,
        private readonly RecurrenceRepository $recurrenceRepository,
        private readonly TransactionRepository $transactionRepository,
        private readonly CategoryRepository $categoryRepository,
        private readonly AccountRepository $accountRepository
    ) {
    }


    public function create(
        int $usuarioId,
        int $subgrupoId,
        ?int $contaId,
        string $descricao,
        string $valor,
        string $frequencia,
        int $intervalo,
        string $dataInicio,
        ?int $totalOcorrencias,
        ?string $meioPagamento,
        ?string $observacao
    ): int {
        $descricao = trim(
            $descricao
        );


        if ($descricao === '') {
            throw new DomainException(
                'Informe a descrição da recorrência.'
            );
        }


        if (strlen($descricao) > 200) {
            throw new DomainException(
                'A descrição é muito longa.'
            );
        }


        /*
         * Categoria
         */

        $subgrupo = $this
            ->categoryRepository
            ->findSubgroupById(
                $subgrupoId,
                $usuarioId
            );


        if (
            $subgrupo === null
            ||
            (int) $subgrupo['ativo'] !== 1
            ||
            (int) $subgrupo['grupo_ativo'] !== 1
        ) {
            throw new DomainException(
                'Categoria inválida.'
            );
        }


        /*
         * Conta
         */

        if ($contaId !== null) {

            $conta = $this
                ->accountRepository
                ->findById(
                    $contaId,
                    $usuarioId
                );


            if (
                $conta === null
                ||
                (int) $conta['ativo'] !== 1
            ) {
                throw new DomainException(
                    'Conta inválida.'
                );
            }
        }


        /*
         * Valor
         */

        $valorCentavos =
            Money::toCents(
                $valor
            );


        if ($valorCentavos <= 0) {
            throw new DomainException(
                'O valor deve ser maior que zero.'
            );
        }


        /*
         * Frequência
         */

        $frequencia =
            strtolower(
                trim($frequencia)
            );


        if (
            !in_array(
                $frequencia,
                self::FREQUENCIES,
                true
            )
        ) {
            throw new DomainException(
                'Frequência inválida.'
            );
        }


        /*
         * Intervalo
         */

        if (
            $intervalo < 1
            ||
            $intervalo > 120
        ) {
            throw new DomainException(
                'Intervalo inválido.'
            );
        }


        /*
         * Primeira ocorrência
         */

        if (
            !$this->isValidDate(
                $dataInicio
            )
        ) {
            throw new DomainException(
                'Data inicial inválida.'
            );
        }


        /*
         * Quantidade.
         *
         * null = sem término.
         */

        if (
            $totalOcorrencias !== null
            &&
            (
                $totalOcorrencias < 1
                ||
                $totalOcorrencias > 1200
            )
        ) {
            throw new DomainException(
                'Quantidade de ocorrências inválida.'
            );
        }


        /*
         * Meio de pagamento
         */

        $meioPagamento =
            $meioPagamento !== null
            ? strtolower(
                trim($meioPagamento)
            )
            : null;


        if ($meioPagamento === '') {
            $meioPagamento = null;
        }


        if (
            $meioPagamento !== null
            &&
            !in_array(
                $meioPagamento,
                self::PAYMENT_METHODS,
                true
            )
        ) {
            throw new DomainException(
                'Meio de pagamento inválido.'
            );
        }


        /*
         * Observação
         */

        $observacao =
            $observacao !== null
            ? trim($observacao)
            : null;


        if ($observacao === '') {
            $observacao = null;
        }


        return $this
            ->recurrenceRepository
            ->create(
                $usuarioId,
                $subgrupoId,
                $contaId,
                $descricao,
                $valorCentavos,
                $frequencia,
                $intervalo,
                $dataInicio,
                $totalOcorrencias,
                $meioPagamento,
                $observacao
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Processador
    |--------------------------------------------------------------------------
    |
    | usuarioId null:
    |     Job processa todos.
    |
    | usuarioId preenchido:
    |     pode ser usado futuramente como fallback
    |     no login/acesso do usuário.
    |
    */

    public function processDue(
        ?int $usuarioId = null,
        ?string $today = null
    ): array {
        $today ??=
            date('Y-m-d');


        if (!$this->isValidDate($today)) {
            throw new DomainException(
                'Data de processamento inválida.'
            );
        }


        $recorrencias =
            $this
                ->recurrenceRepository
                ->findDue(
                    $today,
                    $usuarioId
                );


        $result = [
            'recorrencias' =>
                count($recorrencias),

            'ocorrencias_criadas' =>
                0,

            'recorrencias_encerradas' =>
                0,

            'erros' =>
                []
        ];


        foreach ($recorrencias as $item) {

            try {

                $processing =
                    $this->processOne(
                        (int) $item['id'],
                        $today
                    );


                $result[
                    'ocorrencias_criadas'
                ] +=
                    $processing[
                        'ocorrencias_criadas'
                    ];


                if (
                    $processing['encerrada']
                ) {
                    $result[
                        'recorrencias_encerradas'
                    ]++;
                }


            } catch (Throwable $e) {

                $result['erros'][] = [
                    'recorrencia_id'
                    => (int) $item['id'],

                    'mensagem'
                    => $e->getMessage()
                ];
            }
        }


        return $result;
    }

    public function getForEdit(
        int $usuarioId,
        int $recorrenciaId
    ): array {

        $recorrencia = $this
            ->recurrenceRepository
            ->findByIdAndUser(
                $recorrenciaId,
                $usuarioId
            );


        if ($recorrencia === null) {

            throw new DomainException(
                'Recorrência não encontrada.'
            );
        }


        if (
            $recorrencia[
                'proxima_ocorrencia'
            ] === null
        ) {

            throw new DomainException(
                'Uma recorrência encerrada não pode ser editada.'
            );
        }


        return $recorrencia;
    }

    public function update(
        int $usuarioId,
        int $recorrenciaId,
        int $subgrupoId,
        ?int $contaId,
        string $descricao,
        string $valor,
        string $frequencia,
        int $intervalo,
        string $proximaOcorrencia,
        ?int $totalOcorrencias,
        ?string $meioPagamento,
        ?string $observacao
    ): void {

        $recorrencia =
            $this->getForEdit(
                $usuarioId,
                $recorrenciaId
            );


        /*
         * Descrição
         */

        $descricao =
            trim($descricao);


        if ($descricao === '') {

            throw new DomainException(
                'Informe a descrição da recorrência.'
            );
        }


        if (strlen($descricao) > 200) {

            throw new DomainException(
                'A descrição é muito longa.'
            );
        }


        /*
         * Categoria
         */

        $subgrupo = $this
            ->categoryRepository
            ->findSubgroupById(
                $subgrupoId,
                $usuarioId
            );


        if (
            $subgrupo === null
            ||
            (int) $subgrupo['ativo'] !== 1
            ||
            (int) $subgrupo['grupo_ativo'] !== 1
        ) {

            throw new DomainException(
                'Categoria inválida.'
            );
        }


        /*
         * Conta
         */

        if ($contaId !== null) {

            $conta = $this
                ->accountRepository
                ->findById(
                    $contaId,
                    $usuarioId
                );


            if (
                $conta === null
                ||
                (int) $conta['ativo'] !== 1
            ) {

                throw new DomainException(
                    'Conta inválida.'
                );
            }
        }


        /*
         * Valor
         */

        $valorCentavos =
            Money::toCents(
                $valor
            );


        if ($valorCentavos <= 0) {

            throw new DomainException(
                'O valor deve ser maior que zero.'
            );
        }


        /*
         * Frequência
         */

        $frequencia =
            strtolower(
                trim($frequencia)
            );


        if (
            !in_array(
                $frequencia,
                self::FREQUENCIES,
                true
            )
        ) {

            throw new DomainException(
                'Frequência inválida.'
            );
        }


        /*
         * Intervalo
         */

        if (
            $intervalo < 1
            ||
            $intervalo > 120
        ) {

            throw new DomainException(
                'Intervalo inválido.'
            );
        }


        /*
         * Próxima ocorrência
         */

        if (
            !$this->isValidDate(
                $proximaOcorrencia
            )
        ) {

            throw new DomainException(
                'Próxima ocorrência inválida.'
            );
        }


        /*
         * Quantidade
         */

        $ocorrenciasGeradas =
            (int) $recorrencia[
                'ocorrencias_geradas'
            ];


        if (
            $totalOcorrencias !== null
            &&
            $totalOcorrencias
            <= $ocorrenciasGeradas
        ) {

            throw new DomainException(
                'O total de ocorrências deve ser maior '
                . 'que a quantidade já gerada.'
            );
        }


        if (
            $totalOcorrencias !== null
            &&
            $totalOcorrencias > 1200
        ) {

            throw new DomainException(
                'Quantidade de ocorrências inválida.'
            );
        }


        /*
         * Meio de pagamento
         */

        $meioPagamento =
            $meioPagamento !== null
            ? strtolower(
                trim($meioPagamento)
            )
            : null;


        if ($meioPagamento === '') {

            $meioPagamento = null;
        }


        if (
            $meioPagamento !== null
            &&
            !in_array(
                $meioPagamento,
                self::PAYMENT_METHODS,
                true
            )
        ) {

            throw new DomainException(
                'Meio de pagamento inválido.'
            );
        }


        /*
         * Observação
         */

        $observacao =
            $observacao !== null
            ? trim($observacao)
            : null;


        if ($observacao === '') {

            $observacao = null;
        }


        /*
         * Atualiza somente a regra futura.
         */

        $this
            ->recurrenceRepository
            ->updateFuture(
                $recorrenciaId,
                $usuarioId,
                $subgrupoId,
                $contaId,
                $descricao,
                $valorCentavos,
                $frequencia,
                $intervalo,
                $proximaOcorrencia,
                $totalOcorrencias,
                $meioPagamento,
                $observacao
            );
    }


    private function processOne(
        int $recorrenciaId,
        string $today
    ): array {
        $created = 0;
        $encerrada = false;


        $this->pdo
            ->beginTransaction();


        try {

            /*
             * Buscamos novamente dentro
             * da transaction.
             */

            $recorrencia =
                $this
                    ->recurrenceRepository
                    ->findById(
                        $recorrenciaId
                    );


            if (
                $recorrencia === null
                ||
                (int) $recorrencia['ativo']
                !== 1
                ||
                $recorrencia[
                    'proxima_ocorrencia'
                ] === null
            ) {

                $this->pdo->commit();

                return [
                    'ocorrencias_criadas'
                    => 0,

                    'encerrada'
                    => false
                ];
            }


            $proximaOcorrencia =
                $recorrencia[
                    'proxima_ocorrencia'
                ];


            $ocorrenciasGeradas =
                (int) $recorrencia[
                    'ocorrencias_geradas'
                ];


            $totalOcorrencias =
                $recorrencia[
                    'total_ocorrencias'
                ] !== null
                ? (int) $recorrencia[
                    'total_ocorrencias'
                ]
                : null;


            /*
             * Enquanto houver vencimentos
             * atrasados, recuperamos todos.
             */

            while (
                $proximaOcorrencia !== null
                &&
                $proximaOcorrencia <= $today
            ) {

                /*
                 * Segurança para recorrências
                 * com quantidade definida.
                 */

                if (
                    $totalOcorrencias !== null
                    &&
                    $ocorrenciasGeradas
                    >= $totalOcorrencias
                ) {
                    $proximaOcorrencia = null;
                    $encerrada = true;

                    break;
                }


                /*
                 * Também respeitamos data_fim,
                 * caso seja usada futuramente
                 * ou já exista em algum registro.
                 */

                if (
                    $recorrencia['data_fim']
                    !== null
                    &&
                    $proximaOcorrencia
                    > $recorrencia['data_fim']
                ) {
                    $proximaOcorrencia = null;
                    $encerrada = true;

                    break;
                }


                /*
                 * Idempotência na aplicação.
                 */

                $ocorrenciaAtual = $proximaOcorrencia;

                $exists =
                    $this
                        ->transactionRepository
                        ->recurringOccurrenceExists(
                            $recorrenciaId,
                            $proximaOcorrencia
                        );


                if (!$exists) {

                    $this
                        ->transactionRepository
                        ->createRecurringOccurrence(
                            (int) $recorrencia[
                                'usuario_id'
                            ],

                            (int) $recorrencia[
                                'subgrupo_id'
                            ],

                            $recorrencia[
                                'conta_id'
                            ] !== null
                            ? (int) $recorrencia[
                                'conta_id'
                            ]
                            : null,

                            $recorrenciaId,

                            $recorrencia[
                                'descricao'
                            ],

                            (int) $recorrencia[
                                'valor_centavos'
                            ],

                            $proximaOcorrencia,

                            $recorrencia[
                                'meio_pagamento'
                            ],

                            $recorrencia[
                                'observacao'
                            ]
                        );


                    $created++;
                }


                /*
                 * Esta data foi processada.
                 */

                $ocorrenciasGeradas++;


                /*
                 * Chegou ao limite?
                 */

                if (
                    $totalOcorrencias !== null
                    &&
                    $ocorrenciasGeradas
                    >= $totalOcorrencias
                ) {

                    $proximaOcorrencia =
                        null;

                    $encerrada =
                        true;

                    break;
                }


                /*
                 * Calculamos sempre em relação
                 * à data inicial.
                 *
                 * Isso evita:
                 *
                 * 31/01
                 * 28/02
                 * 28/03   <- errado
                 *
                 * E mantém:
                 *
                 * 31/01
                 * 28/02
                 * 31/03
                 */

                $proximaOcorrencia =
                    $this->calculateNextOccurrenceDate(
                        $ocorrenciaAtual,

                        $recorrencia['agenda_base']
                        ?? $recorrencia['data_inicio'],

                        $recorrencia['frequencia'],

                        (int) $recorrencia['intervalo']
                    );


                if (
                    $recorrencia['data_fim']
                    !== null
                    &&
                    $proximaOcorrencia
                    > $recorrencia['data_fim']
                ) {

                    $proximaOcorrencia =
                        null;

                    $encerrada =
                        true;

                    break;
                }
            }


            $this
                ->recurrenceRepository
                ->updateProcessingState(
                    $recorrenciaId,
                    $ocorrenciasGeradas,
                    $proximaOcorrencia,
                    !$encerrada
                );


            $this->pdo->commit();


            return [
                'ocorrencias_criadas'
                => $created,

                'encerrada'
                => $encerrada
            ];


        } catch (Throwable $e) {

            if (
                $this->pdo->inTransaction()
            ) {
                $this->pdo
                    ->rollBack();
            }

            throw $e;
        }
    }

    public function list(
        int $usuarioId
    ): array {

        return $this
            ->recurrenceRepository
            ->listByUser(
                $usuarioId
            );
    }


    public function pause(
        int $usuarioId,
        int $recorrenciaId
    ): void {

        $recorrencia = $this
            ->recurrenceRepository
            ->findByIdAndUser(
                $recorrenciaId,
                $usuarioId
            );


        if ($recorrencia === null) {

            throw new DomainException(
                'Recorrência não encontrada.'
            );
        }


        if (
            $recorrencia['proxima_ocorrencia']
            === null
        ) {

            throw new DomainException(
                'Uma recorrência encerrada não pode ser pausada.'
            );
        }


        $this
            ->recurrenceRepository
            ->pause(
                $recorrenciaId,
                $usuarioId
            );
    }


    public function resume(
        int $usuarioId,
        int $recorrenciaId
    ): void {

        $recorrencia = $this
            ->recurrenceRepository
            ->findByIdAndUser(
                $recorrenciaId,
                $usuarioId
            );


        if ($recorrencia === null) {

            throw new DomainException(
                'Recorrência não encontrada.'
            );
        }


        if (
            $recorrencia['proxima_ocorrencia']
            === null
        ) {

            throw new DomainException(
                'Uma recorrência encerrada não pode ser retomada.'
            );
        }


        $this
            ->recurrenceRepository
            ->resume(
                $recorrenciaId,
                $usuarioId
            );
    }


    public function finish(
        int $usuarioId,
        int $recorrenciaId
    ): void {

        $recorrencia = $this
            ->recurrenceRepository
            ->findByIdAndUser(
                $recorrenciaId,
                $usuarioId
            );


        if ($recorrencia === null) {

            throw new DomainException(
                'Recorrência não encontrada.'
            );
        }


        $this
            ->recurrenceRepository
            ->finish(
                $recorrenciaId,
                $usuarioId
            );
    }

    private function calculateNextOccurrenceDate(
        string $ocorrenciaAtual,
        string $agendaBase,
        string $frequencia,
        int $intervalo
    ): string {

        $atual =
            new DateTimeImmutable(
                $ocorrenciaAtual
            );


        $base =
            new DateTimeImmutable(
                $agendaBase
            );


        $diaAncora =
            (int) $base->format('d');


        return match ($frequencia) {

            'diaria' =>
                $atual
                    ->modify(
                        '+'
                        . $intervalo
                        . ' days'
                    )
                    ->format('Y-m-d'),


            'semanal' =>
                $atual
                    ->modify(
                        '+'
                        . (
                            $intervalo * 7
                        )
                        . ' days'
                    )
                    ->format('Y-m-d'),


            'mensal' =>
                $this->addMonthsClamped(
                    $ocorrenciaAtual,
                    $intervalo,
                    $diaAncora
                ),


            'anual' =>
                $this->addMonthsClamped(
                    $ocorrenciaAtual,
                    $intervalo * 12,
                    $diaAncora
                ),


            default =>
                throw new DomainException(
                    'Frequência inválida.'
                )
        };
    }


    private function addMonthsClamped(
        string $date,
        int $months,
        ?int $anchorDay = null
    ): string {

        $base =
            new DateTimeImmutable(
                $date
            );


        $year =
            (int) $base->format('Y');

        $month =
            (int) $base->format('m');


        $day =
            $anchorDay
            ?? (int) $base->format('d');


        $monthIndex =
            ($year * 12)
            + ($month - 1)
            + $months;


        $targetYear =
            intdiv(
                $monthIndex,
                12
            );


        $targetMonth =
            ($monthIndex % 12)
            + 1;


        $firstDay =
            new DateTimeImmutable(
                sprintf(
                    '%04d-%02d-01',
                    $targetYear,
                    $targetMonth
                )
            );


        $lastDay =
            (int) $firstDay
                ->modify(
                    'last day of this month'
                )
                ->format('d');


        return sprintf(
            '%04d-%02d-%02d',
            $targetYear,
            $targetMonth,
            min(
                $day,
                $lastDay
            )
        );
    }


    private function isValidDate(
        string $date
    ): bool {
        $parsed =
            DateTimeImmutable::createFromFormat(
                'Y-m-d',
                $date
            );


        return $parsed !== false
            &&
            $parsed->format('Y-m-d')
            === $date;
    }
}