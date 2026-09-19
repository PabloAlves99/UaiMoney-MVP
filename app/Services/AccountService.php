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

    public function get(
        int $usuarioId,
        int $contaId
    ): array {
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
                'Conta não encontrada.'
            );
        }


        return $conta;
    }

    public function update(
        int $usuarioId,
        int $contaId,
        string $nome,
        string $tipo,
        ?string $instituicao
    ): void {
        /*
         * Garante que a conta existe,
         * está ativa e pertence ao usuário.
         */

        $this->get(
            $usuarioId,
            $contaId
        );


        $nome = trim(
            $nome
        );


        $tipo = strtolower(
            trim($tipo)
        );


        $instituicao =
            $instituicao !== null
            ? trim($instituicao)
            : null;


        /*
         * Nome
         */

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


        /*
         * Tipo
         */

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


        /*
         * Instituição
         */

        if ($instituicao === '') {
            $instituicao = null;
        }


        /*
         * Nome duplicado
         */

        if (
            $this->accountRepository
                ->existsByNameExceptId(
                    $usuarioId,
                    $nome,
                    $contaId
                )
        ) {
            throw new DomainException(
                'Já existe outra conta com este nome.'
            );
        }


        $updated = $this
            ->accountRepository
            ->updateDetails(
                $contaId,
                $usuarioId,
                $nome,
                $tipo,
                $instituicao
            );


        if (!$updated) {
            throw new DomainException(
                'Não foi possível atualizar a conta.'
            );
        }
    }
}