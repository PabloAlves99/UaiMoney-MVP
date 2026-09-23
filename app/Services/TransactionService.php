<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Money;
use App\Repositories\AccountRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\TransactionRepository;
use DomainException;

final class TransactionService
{
    private const STATUSES = [
        'pendente',
        'efetivada'
    ];

    private const PAYMENT_METHODS = [
        'pix',
        'dinheiro',
        'debito',
        'boleto',
        'transferencia',
        'outro'
    ];


    public function __construct(
        private readonly TransactionRepository $transactionRepository,
        private readonly CategoryRepository $categoryRepository,
        private readonly AccountRepository $accountRepository
    ) {
    }


    public function list(
        int $usuarioId,
        array $filters = []
    ): array {
        $filters = $this->normalizeFilters(
            $filters
        );


        return $this
            ->transactionRepository
            ->listByUser(
                $usuarioId,
                $filters
            );
    }


    public function create(
        int $usuarioId,
        int $subgrupoId,
        ?int $contaId,
        string $descricao,
        string $valor,
        string $dataCompetencia,
        string $dataVencimento,
        ?string $dataEfetivacao,
        string $status,
        ?string $meioPagamento,
        ?string $observacao
    ): int {
        $descricao = trim($descricao);

        $status = strtolower(
            trim($status)
        );

        $meioPagamento =
            $meioPagamento !== null
            ? strtolower(trim($meioPagamento))
            : null;

        $observacao =
            $observacao !== null
            ? trim($observacao)
            : null;


        if ($descricao === '') {
            throw new DomainException(
                'Informe a descrição da movimentação.'
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
            $subgrupo === null ||
            (int) $subgrupo['ativo'] !== 1 ||
            (int) $subgrupo['grupo_ativo'] !== 1
        ) {
            throw new DomainException(
                'Categoria inválida.'
            );
        }


        /*
         * Valor
         */

        $valorCentavos = Money::toCents(
            $valor
        );


        if ($valorCentavos <= 0) {
            throw new DomainException(
                'O valor deve ser maior que zero.'
            );
        }


        /*
         * Datas
         */

        if (!$this->isValidDate($dataCompetencia)) {
            throw new DomainException(
                'Data de competência inválida.'
            );
        }


        if (!$this->isValidDate($dataVencimento)) {
            throw new DomainException(
                'Data de vencimento inválida.'
            );
        }


        /*
         * Status
         */

        if (
            !in_array(
                $status,
                self::STATUSES,
                true
            )
        ) {
            throw new DomainException(
                'Status inválido.'
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
                $conta === null ||
                (int) $conta['ativo'] !== 1
            ) {
                throw new DomainException(
                    'Conta inválida.'
                );
            }
        }


        /*
         * Efetivação
         */

        if ($status === 'efetivada') {

            \App\Core\FinancialDate::past((string)$dataEfetivacao);

            if ($contaId === null) {
                throw new DomainException(
                    'Uma movimentação efetivada precisa de uma conta.'
                );
            }


            if (
                $dataEfetivacao === null ||
                !$this->isValidDate(
                    $dataEfetivacao
                )
            ) {
                throw new DomainException(
                    'Informe a data de efetivação.'
                );
            }

        } else {

            /*
             * Movimentação pendente ainda não
             * possui data de efetivação.
             */

            $dataEfetivacao = null;
        }


        /*
         * Meio de pagamento
         */

        if ($meioPagamento === '') {
            $meioPagamento = null;
        }


        if (
            $meioPagamento !== null &&
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


        if ($observacao === '') {
            $observacao = null;
        }


        return $this
            ->transactionRepository
            ->create(
                $usuarioId,
                $subgrupoId,
                $contaId,
                $descricao,
                $valorCentavos,
                $dataCompetencia,
                $dataVencimento,
                $dataEfetivacao,
                $status,
                $meioPagamento,
                $observacao
            );
    }

    public function getPendingForEdit(
        int $usuarioId,
        int $transacaoId
    ): array {
        $transacao = $this
            ->transactionRepository
            ->findById(
                $transacaoId,
                $usuarioId
            );


        if ($transacao === null) {
            throw new DomainException(
                'Movimentação não encontrada.'
            );
        }


        if ($transacao['status'] !== 'pendente' || !empty($transacao['cartao_id'])) {
            throw new DomainException(
                'Somente movimentações pendentes podem ser editadas.'
            );
        }


        return $transacao;
    }


    public function updatePending(
        int $usuarioId,
        int $transacaoId,
        int $subgrupoId,
        ?int $contaId,
        string $descricao,
        string $valor,
        string $dataCompetencia,
        string $dataVencimento,
        ?string $meioPagamento,
        ?string $observacao
    ): void {
        /*
         * Primeiro garantimos que a movimentação
         * existe, pertence ao usuário e continua
         * pendente.
         */

        $this->getPendingForEdit(
            $usuarioId,
            $transacaoId
        );


        $descricao = trim(
            $descricao
        );


        if ($descricao === '') {
            throw new DomainException(
                'Informe a descrição da movimentação.'
            );
        }


        if (strlen($descricao) > 200) {
            throw new DomainException(
                'A descrição da movimentação é muito longa.'
            );
        }


        /*
         * Subcategoria
         */

        $subgrupo = $this
            ->categoryRepository
            ->findSubgroupById(
                $subgrupoId,
                $usuarioId
            );


        if (
            $subgrupo === null ||
            (int) $subgrupo['ativo'] !== 1 ||
            (int) $subgrupo['grupo_ativo'] !== 1
        ) {
            throw new DomainException(
                'Categoria inválida.'
            );
        }


        /*
         * Valor
         */

        $valorCentavos = Money::toCents(
            $valor
        );


        if ($valorCentavos <= 0) {
            throw new DomainException(
                'O valor deve ser maior que zero.'
            );
        }


        /*
         * Datas
         */

        if (
            !$this->isValidDate(
                $dataCompetencia
            )
        ) {
            throw new DomainException(
                'Data de competência inválida.'
            );
        }


        if (
            !$this->isValidDate(
                $dataVencimento
            )
        ) {
            throw new DomainException(
                'Data de vencimento inválida.'
            );
        }


        /*
         * Conta opcional
         */

        if ($contaId !== null) {

            $conta = $this
                ->accountRepository
                ->findById(
                    $contaId,
                    $usuarioId
                );


            if (
                $conta === null ||
                (int) $conta['ativo'] !== 1
            ) {
                throw new DomainException(
                    'Conta inválida.'
                );
            }
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
            $meioPagamento !== null &&
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


        $updated = $this
            ->transactionRepository
            ->updatePending(
                $transacaoId,
                $usuarioId,
                $subgrupoId,
                $contaId,
                $descricao,
                $valorCentavos,
                $dataCompetencia,
                $dataVencimento,
                $meioPagamento,
                $observacao
            );


        if (!$updated) {
            throw new DomainException(
                'Não foi possível atualizar a movimentação.'
            );
        }
    }


    private function isValidDate(
        string $date
    ): bool {
        $parsed =
            \DateTimeImmutable::createFromFormat(
                'Y-m-d',
                $date
            );

        return $parsed !== false
            && $parsed->format('Y-m-d')
            === $date;
    }

    public function effect(
        int $usuarioId,
        int $transacaoId,
        int $contaId,
        string $dataEfetivacao
    ): void {
        \App\Core\FinancialDate::past($dataEfetivacao);
        $transacao = $this
            ->transactionRepository
            ->findById(
                $transacaoId,
                $usuarioId
            );


        if ($transacao === null) {
            throw new DomainException(
                'Movimentação não encontrada.'
            );
        }


        if ($transacao['status'] !== 'pendente' || !empty($transacao['cartao_id'])) {
            throw new DomainException(
                'Somente movimentações pendentes podem ser efetivadas.'
            );
        }


        $conta = $this
            ->accountRepository
            ->findById(
                $contaId,
                $usuarioId
            );


        if (
            $conta === null ||
            (int) $conta['ativo'] !== 1
        ) {
            throw new DomainException(
                'Conta inválida.'
            );
        }


        if (
            !$this->isValidDate(
                $dataEfetivacao
            )
        ) {
            throw new DomainException(
                'Data de efetivação inválida.'
            );
        }


        $efetivada = $this
            ->transactionRepository
            ->effect(
                $transacaoId,
                $usuarioId,
                $contaId,
                $dataEfetivacao
            );


        if (!$efetivada) {
            throw new DomainException(
                'Não foi possível efetivar a movimentação.'
            );
        }
    }


    public function cancel(
        int $usuarioId,
        int $transacaoId
    ): void {
        $transacao = $this
            ->transactionRepository
            ->findById(
                $transacaoId,
                $usuarioId
            );


        if ($transacao === null) {
            throw new DomainException(
                'Movimentação não encontrada.'
            );
        }


        if ($transacao['status'] !== 'pendente' || !empty($transacao['cartao_id'])) {
            throw new DomainException(
                'Somente pendências podem ser canceladas. Para corrigir um lançamento realizado, use o estorno no detalhe.'
            );
        }


        $cancelada = $this
            ->transactionRepository
            ->cancel(
                $transacaoId,
                $usuarioId
            );


        if (!$cancelada) {
            throw new DomainException(
                'Não foi possível cancelar a movimentação.'
            );
        }
    }

    public function normalizeFilters(
        array $filters
    ): array {
        $normalized = [
            'data_inicio' => '',
            'data_fim' => '',
            'status' => '',
            'tipo' => '',
            'conta_id' => null,
            'grupo_id' => null
        ];


        /*
        |--------------------------------------------------------------------------
        | Datas
        |--------------------------------------------------------------------------
        */

        $dataInicio = trim(
            (string) (
                $filters['data_inicio']
                ?? ''
            )
        );


        if (
            $dataInicio !== '' &&
            $this->isValidDate($dataInicio)
        ) {
            $normalized['data_inicio'] =
                $dataInicio;
        }


        $dataFim = trim(
            (string) (
                $filters['data_fim']
                ?? ''
            )
        );


        if (
            $dataFim !== '' &&
            $this->isValidDate($dataFim)
        ) {
            $normalized['data_fim'] =
                $dataFim;
        }


        /*
        |--------------------------------------------------------------------------
        | Status
        |--------------------------------------------------------------------------
        */

        $status = strtolower(
            trim(
                (string) (
                    $filters['status']
                    ?? ''
                )
            )
        );


        if (
            in_array(
                $status,
                [
                    'pendente',
                    'efetivada',
                    'cancelada'
                ],
                true
            )
        ) {
            $normalized['status'] =
                $status;
        }


        /*
        |--------------------------------------------------------------------------
        | Tipo
        |--------------------------------------------------------------------------
        */

        $tipo = strtolower(
            trim(
                (string) (
                    $filters['tipo']
                    ?? ''
                )
            )
        );


        if (
            in_array(
                $tipo,
                [
                    'receita',
                    'despesa'
                ],
                true
            )
        ) {
            $normalized['tipo'] =
                $tipo;
        }


        /*
        |--------------------------------------------------------------------------
        | Conta
        |--------------------------------------------------------------------------
        */

        $contaId = filter_var(
            $filters['conta_id']
            ?? null,
            FILTER_VALIDATE_INT
        );


        if (
            $contaId !== false &&
            $contaId > 0
        ) {
            $normalized['conta_id'] =
                $contaId;
        }


        /*
        |--------------------------------------------------------------------------
        | Categoria
        |--------------------------------------------------------------------------
        */

        $grupoId = filter_var(
            $filters['grupo_id']
            ?? null,
            FILTER_VALIDATE_INT
        );


        if (
            $grupoId !== false &&
            $grupoId > 0
        ) {
            $normalized['grupo_id'] =
                $grupoId;
        }


        return $normalized;
    }
}
