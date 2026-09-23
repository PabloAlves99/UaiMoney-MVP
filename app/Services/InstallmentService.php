<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Money;
use App\Repositories\AccountRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\InstallmentRepository;
use App\Repositories\TransactionRepository;
use DomainException;
use PDO;
use Throwable;

final class InstallmentService
{
    private const PAYMENT_METHODS = [
        'pix',
        'dinheiro',
        'debito',
        'boleto',
        'transferencia',
        'outro'
    ];


    public function __construct(
        private readonly PDO $pdo,
        private readonly InstallmentRepository $installmentRepository,
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
        string $valorTotal,
        int $totalParcelas,
        string $primeiroVencimento,
        ?string $meioPagamento,
        ?string $observacao
    ): int {
        $descricao = trim(
            $descricao
        );


        if ($descricao === '') {
            throw new DomainException(
                'Informe a descrição do parcelamento.'
            );
        }


        if (strlen($descricao) > 200) {
            throw new DomainException(
                'A descrição do parcelamento é muito longa.'
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
         * Valor
         */

        $valorTotalCentavos =
            Money::toCents(
                $valorTotal
            );


        if ($valorTotalCentavos <= 0) {
            throw new DomainException(
                'O valor total deve ser maior que zero.'
            );
        }


        /*
         * Parcelas
         */

        if (
            $totalParcelas < 2 ||
            $totalParcelas > 120
        ) {
            throw new DomainException(
                'A quantidade de parcelas deve estar entre 2 e 120.'
            );
        }


        if (
            $valorTotalCentavos
            < $totalParcelas
        ) {
            throw new DomainException(
                'O valor total é muito pequeno para a quantidade de parcelas.'
            );
        }


        /*
         * Primeiro vencimento
         */

        if (
            !$this->isValidDate(
                $primeiroVencimento
            )
        ) {
            throw new DomainException(
                'Primeiro vencimento inválido.'
            );
        }


        /*
         * Meio de pagamento
         */

        $meioPagamento =
            $meioPagamento !== null
            ? strtolower(
                trim(
                    $meioPagamento
                )
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


        /*
         * Distribuição exata do valor.
         */

        $valorBase =
            intdiv(
                $valorTotalCentavos,
                $totalParcelas
            );


        $resto =
            $valorTotalCentavos
            % $totalParcelas;


        /*
         * Operação atômica.
         */

        $this->pdo->beginTransaction();


        try {

            $parcelamentoId =
                $this
                    ->installmentRepository
                    ->create(
                        $usuarioId,
                        $descricao,
                        $valorTotalCentavos,
                        $totalParcelas
                    );


            for (
                $numeroParcela = 1;
                $numeroParcela <= $totalParcelas;
                $numeroParcela++
            ) {

                /*
                 * Distribui eventuais centavos
                 * restantes nas primeiras parcelas.
                 */

                $valorParcela =
                    $valorBase;


                if ($numeroParcela <= $resto) {
                    $valorParcela++;
                }


                /*
                 * Primeira parcela = +0 meses
                 * Segunda          = +1 mês
                 * ...
                 */

                $vencimento =
                    $this->addMonthsClamped(
                        $primeiroVencimento,
                        $numeroParcela - 1
                    );


                $this
                    ->transactionRepository
                    ->createInstallment(
                        $usuarioId,
                        $subgrupoId,
                        $contaId,
                        $parcelamentoId,
                        $numeroParcela,
                        $descricao,
                        $valorParcela,
                        $vencimento,
                        $vencimento,
                        $meioPagamento,
                        $observacao
                    );
            }


            $this->pdo->commit();

            return $parcelamentoId;


        } catch (Throwable $e) {

            if (
                $this->pdo->inTransaction()
            ) {
                $this->pdo->rollBack();
            }

            throw $e;
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


    private function addMonthsClamped(
        string $date,
        int $months
    ): string {
        $base =
            new \DateTimeImmutable(
                $date
            );


        $year =
            (int) $base->format('Y');

        $month =
            (int) $base->format('m');

        $day =
            (int) $base->format('d');


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
            new \DateTimeImmutable(
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


        $targetDay =
            min(
                $day,
                $lastDay
            );


        return sprintf(
            '%04d-%02d-%02d',
            $targetYear,
            $targetMonth,
            $targetDay
        );
    }
}