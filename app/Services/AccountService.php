<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Money;
use App\Repositories\AccountRepository;
use DomainException;

final class AccountService
{
    private const TYPES = [
        'corrente',
        'poupanca',
        'dinheiro',
        'carteira_digital',
        'outro'
    ];


    public function __construct(
        private readonly AccountRepository $accountRepository
    ) {
    }


    public function list(
        int $usuarioId
    ): array {
        return $this
            ->accountRepository
            ->listActive(
                $usuarioId
            );
    }


    public function create(
        int $usuarioId,
        string $nome,
        string $tipo,
        ?string $instituicao,
        string $saldoInicial,
        string $saldoInicialEm
    ): int {
        $nome = trim($nome);

        $tipo = strtolower(
            trim($tipo)
        );

        $instituicao =
            $instituicao !== null
            ? trim($instituicao)
            : null;


        if ($nome === '') {
            throw new DomainException(
                'Informe o nome da conta.'
            );
        }


        if (strlen($nome) > 100) {
            throw new DomainException(
                'O nome da conta é muito longo.'
            );
        }


        if (
            !in_array(
                $tipo,
                self::TYPES,
                true
            )
        ) {
            throw new DomainException(
                'Tipo de conta inválido.'
            );
        }


        if ($instituicao === '') {
            $instituicao = null;
        }


        if (
            $this->accountRepository
                ->existsByName(
                    $usuarioId,
                    $nome
                )
        ) {
            throw new DomainException(
                'Já existe uma conta com este nome.'
            );
        }


        $saldoInicialCentavos =
            Money::toCents(
                $saldoInicial
            );


        if (
            !$this->isValidDate(
                $saldoInicialEm
            )
        ) {
            throw new DomainException(
                'Data do saldo inicial inválida.'
            );
        }


        return $this
            ->accountRepository
            ->create(
                $usuarioId,
                $nome,
                $tipo,
                $instituicao,
                $saldoInicialCentavos,
                $saldoInicialEm
            );
    }


    public function deactivate(
        int $usuarioId,
        int $contaId
    ): void {
        $conta = $this
            ->accountRepository
            ->findById(
                $contaId,
                $usuarioId
            );


        if ($conta === null) {
            throw new DomainException(
                'Conta não encontrada.'
            );
        }


        $this->accountRepository
            ->deactivate(
                $contaId,
                $usuarioId
            );
    }


    private function isValidDate(
        string $date
    ): bool {
        $data = \DateTimeImmutable::createFromFormat(
            'Y-m-d',
            $date
        );

        return $data !== false
            && $data->format('Y-m-d')
            === $date;
    }
}