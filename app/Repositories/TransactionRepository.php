<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class TransactionRepository
{
    public function __construct(
        private readonly PDO $pdo
    ) {
    }


    public function listByUser(
        int $usuarioId
    ): array {
        $stmt = $this->pdo->prepare("
            SELECT
                t.id,

                t.descricao,
                t.valor_centavos,

                t.data_competencia,
                t.data_vencimento,
                t.data_efetivacao,

                t.status,
                t.meio_pagamento,
                t.observacao,

                s.id AS subgrupo_id,
                s.nome AS subgrupo_nome,

                g.id AS grupo_id,
                g.nome AS grupo_nome,
                g.tipo,

                c.id AS conta_id,
                c.nome AS conta_nome

            FROM transacoes t

            INNER JOIN subgrupos s
                ON s.id = t.subgrupo_id

            INNER JOIN grupos g
                ON g.id = s.grupo_id

            LEFT JOIN contas c
                ON c.id = t.conta_id

            WHERE t.usuario_id = :usuario_id

            ORDER BY
                COALESCE(
                    t.data_efetivacao,
                    t.data_vencimento,
                    t.data_competencia
                ) DESC,

                t.id DESC
        ");

        $stmt->execute([
            ':usuario_id' => $usuarioId
        ]);

        return $stmt->fetchAll();
    }

    public function create(
        int $usuarioId,
        int $subgrupoId,
        ?int $contaId,
        string $descricao,
        int $valorCentavos,
        string $dataCompetencia,
        string $dataVencimento,
        ?string $dataEfetivacao,
        string $status,
        ?string $meioPagamento,
        ?string $observacao
    ): int {
        $stmt = $this->pdo->prepare("
        INSERT INTO transacoes (
            usuario_id,
            subgrupo_id,
            conta_id,
            descricao,
            valor_centavos,
            data_competencia,
            data_vencimento,
            data_efetivacao,
            status,
            meio_pagamento,
            observacao
        )
        VALUES (
            :usuario_id,
            :subgrupo_id,
            :conta_id,
            :descricao,
            :valor_centavos,
            :data_competencia,
            :data_vencimento,
            :data_efetivacao,
            :status,
            :meio_pagamento,
            :observacao
        )
    ");

        $stmt->execute([
            ':usuario_id' => $usuarioId,
            ':subgrupo_id' => $subgrupoId,
            ':conta_id' => $contaId,
            ':descricao' => $descricao,
            ':valor_centavos' => $valorCentavos,
            ':data_competencia' => $dataCompetencia,
            ':data_vencimento' => $dataVencimento,
            ':data_efetivacao' => $dataEfetivacao,
            ':status' => $status,
            ':meio_pagamento' => $meioPagamento,
            ':observacao' => $observacao
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function findById(
        int $transacaoId,
        int $usuarioId
    ): ?array {
        $stmt = $this->pdo->prepare("
        SELECT
            id,
            usuario_id,
            subgrupo_id,
            conta_id,
            descricao,
            valor_centavos,
            data_competencia,
            data_vencimento,
            data_efetivacao,
            status,
            meio_pagamento,
            observacao
        FROM transacoes
        WHERE id = :id
          AND usuario_id = :usuario_id
        LIMIT 1
    ");

        $stmt->execute([
            ':id' => $transacaoId,
            ':usuario_id' => $usuarioId
        ]);

        $transacao = $stmt->fetch();

        return $transacao ?: null;
    }


    public function effect(
        int $transacaoId,
        int $usuarioId,
        int $contaId,
        string $dataEfetivacao
    ): bool {
        $stmt = $this->pdo->prepare("
        UPDATE transacoes
        SET
            conta_id = :conta_id,
            data_efetivacao = :data_efetivacao,
            status = 'efetivada',
            atualizado_em = CURRENT_TIMESTAMP
        WHERE id = :id
          AND usuario_id = :usuario_id
          AND status = 'pendente'
    ");

        $stmt->execute([
            ':conta_id' => $contaId,
            ':data_efetivacao' => $dataEfetivacao,
            ':id' => $transacaoId,
            ':usuario_id' => $usuarioId
        ]);

        return $stmt->rowCount() === 1;
    }


    public function cancel(
        int $transacaoId,
        int $usuarioId
    ): bool {
        $stmt = $this->pdo->prepare("
        UPDATE transacoes
        SET
            status = 'cancelada',
            atualizado_em = CURRENT_TIMESTAMP
        WHERE id = :id
          AND usuario_id = :usuario_id
          AND status IN (
              'pendente',
              'efetivada'
          )
    ");

        $stmt->execute([
            ':id' => $transacaoId,
            ':usuario_id' => $usuarioId
        ]);

        return $stmt->rowCount() === 1;
    }
}
