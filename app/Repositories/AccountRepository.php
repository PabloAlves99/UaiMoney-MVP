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
            c.id,
            c.nome,
            c.tipo,
            c.instituicao,
            c.saldo_inicial_centavos,
            c.saldo_inicial_em,
            c.ativo,
            c.criado_em,
            c.atualizado_em,

            (
                c.saldo_inicial_centavos

                +

                COALESCE(
                    (
                        SELECT
                            SUM(
                                CASE
                                    WHEN g.tipo = 'receita'
                                        THEN t.valor_centavos

                                    WHEN g.tipo = 'despesa'
                                        THEN -t.valor_centavos

                                    ELSE 0
                                END
                            )

                        FROM transacoes t

                        INNER JOIN subgrupos s
                            ON s.id = t.subgrupo_id

                        INNER JOIN grupos g
                            ON g.id = s.grupo_id

                        WHERE t.usuario_id = c.usuario_id

                          AND g.usuario_id = c.usuario_id

                          AND t.conta_id = c.id

                          AND t.status = 'efetivada'

                          AND t.data_efetivacao IS NOT NULL

                          AND t.data_efetivacao >= c.saldo_inicial_em
                    ),
                    0
                )
            ) AS saldo_atual_centavos

        FROM contas c

        WHERE c.usuario_id = :usuario_id
          AND c.ativo = 1

        ORDER BY c.nome
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
            c.id,
            c.usuario_id,
            c.nome,
            c.tipo,
            c.instituicao,
            c.saldo_inicial_centavos,
            c.saldo_inicial_em,
            c.ativo,
            c.criado_em,
            c.atualizado_em,

            (
                c.saldo_inicial_centavos

                +

                COALESCE(
                    (
                        SELECT
                            SUM(
                                CASE
                                    WHEN g.tipo = 'receita'
                                        THEN t.valor_centavos

                                    WHEN g.tipo = 'despesa'
                                        THEN -t.valor_centavos

                                    ELSE 0
                                END
                            )

                        FROM transacoes t

                        INNER JOIN subgrupos s
                            ON s.id = t.subgrupo_id

                        INNER JOIN grupos g
                            ON g.id = s.grupo_id

                        WHERE t.usuario_id = c.usuario_id

                          AND g.usuario_id = c.usuario_id

                          AND t.conta_id = c.id

                          AND t.status = 'efetivada'

                          AND t.data_efetivacao IS NOT NULL

                          AND t.data_efetivacao >= c.saldo_inicial_em
                    ),
                    0
                )
            ) AS saldo_atual_centavos

        FROM contas c

        WHERE c.id = :id
          AND c.usuario_id = :usuario_id

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

    public function existsByNameExceptId(
        int $usuarioId,
        string $nome,
        int $contaId
    ): bool {
        $stmt = $this->pdo->prepare("
        SELECT 1
        FROM contas
        WHERE usuario_id = :usuario_id
          AND nome = :nome
          AND id <> :id
        LIMIT 1
    ");

        $stmt->execute([
            ':usuario_id' => $usuarioId,
            ':nome' => $nome,
            ':id' => $contaId
        ]);

        return $stmt->fetchColumn()
            !== false;
    }


    public function updateDetails(
        int $contaId,
        int $usuarioId,
        string $nome,
        string $tipo,
        ?string $instituicao
    ): bool {
        $stmt = $this->pdo->prepare("
        UPDATE contas
        SET
            nome = :nome,
            tipo = :tipo,
            instituicao = :instituicao,
            atualizado_em = CURRENT_TIMESTAMP
        WHERE id = :id
          AND usuario_id = :usuario_id
          AND ativo = 1
    ");

        $stmt->execute([
            ':nome' => $nome,
            ':tipo' => $tipo,
            ':instituicao' => $instituicao,
            ':id' => $contaId,
            ':usuario_id' => $usuarioId
        ]);

        return $stmt->rowCount() === 1;
    }
}
