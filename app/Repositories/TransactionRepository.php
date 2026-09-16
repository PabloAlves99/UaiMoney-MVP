<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class TransactionRepository
{
    public function __construct(
        private readonly PDO $pdo
    ) {}


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
}
