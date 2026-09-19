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
        'credito',
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
        int $usuarioId
    ): array {
        return $this
            ->transactionRepository
            ->listByUser($usuarioId);
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


        if (
            $transacao['status'] !== 'pendente'
        ) {
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


        if (
            $transacao['status'] === 'cancelada'
        ) {
            throw new DomainException(
                'Esta movimentação já está cancelada.'
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
}