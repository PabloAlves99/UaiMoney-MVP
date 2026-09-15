<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class AccountRepository
{
    public function __construct(
        private readonly PDO $pdo
    ) {
    }


    public function listActive(
        int $usuarioId
    ): array {
        $stmt = $this->pdo->prepare("
            SELECT
                id,
                nome,
                tipo,
                instituicao,
                saldo_inicial_centavos,
                saldo_inicial_em,
                ativo,
                criado_em,
                atualizado_em
            FROM contas
            WHERE usuario_id = :usuario_id
              AND ativo = 1
            ORDER BY nome
        ");

        $stmt->execute([
            ':usuario_id' => $usuarioId
        ]);

        return $stmt->fetchAll();
    }


    public function findById(
        int $contaId,
        int $usuarioId
    ): ?array {
        $stmt = $this->pdo->prepare("
            SELECT
                id,
                usuario_id,
                nome,
                tipo,
                instituicao,
                saldo_inicial_centavos,
                saldo_inicial_em,
                ativo,
                criado_em,
                atualizado_em
            FROM contas
            WHERE id = :id
              AND usuario_id = :usuario_id
            LIMIT 1
        ");

        $stmt->execute([
            ':id' => $contaId,
            ':usuario_id' => $usuarioId
        ]);

        $conta = $stmt->fetch();

        return $conta ?: null;
    }


    public function existsByName(
        int $usuarioId,
        string $nome
    ): bool {
        $stmt = $this->pdo->prepare("
            SELECT 1
            FROM contas
            WHERE usuario_id = :usuario_id
              AND nome = :nome
            LIMIT 1
        ");

        $stmt->execute([
            ':usuario_id' => $usuarioId,
            ':nome' => $nome
        ]);

        return $stmt->fetchColumn()
            !== false;
    }


    public function create(
        int $usuarioId,
        string $nome,
        string $tipo,
        ?string $instituicao,
        int $saldoInicialCentavos,
        string $saldoInicialEm
    ): int {
        $stmt = $this->pdo->prepare("
            INSERT INTO contas (
                usuario_id,
                nome,
                tipo,
                instituicao,
                saldo_inicial_centavos,
                saldo_inicial_em
            )
            VALUES (
                :usuario_id,
                :nome,
                :tipo,
                :instituicao,
                :saldo_inicial_centavos,
                :saldo_inicial_em
            )
        ");

        $stmt->execute([
            ':usuario_id' => $usuarioId,
            ':nome' => $nome,
            ':tipo' => $tipo,
            ':instituicao' => $instituicao,
            ':saldo_inicial_centavos'
            => $saldoInicialCentavos,
            ':saldo_inicial_em'
            => $saldoInicialEm
        ]);

        return (int) 
            $this->pdo->lastInsertId();
    }


    public function deactivate(
        int $contaId,
        int $usuarioId
    ): void {
        $stmt = $this->pdo->prepare("
            UPDATE contas
            SET
                ativo = 0,
                atualizado_em = CURRENT_TIMESTAMP
            WHERE id = :id
              AND usuario_id = :usuario_id
        ");

        $stmt->execute([
            ':id' => $contaId,
            ':usuario_id' => $usuarioId
        ]);
    }
}