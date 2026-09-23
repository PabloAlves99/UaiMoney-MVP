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
        int $usuarioId,
        array $filters = []
    ): array {
        $sql = "
        SELECT
            t.id,
            t.cartao_id, t.fatura_id, t.recorrencia_id,
            t.descricao,
            t.valor_centavos,

            t.parcelamento_id,
            t.numero_parcela,

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
            c.nome AS conta_nome,

            p.total_parcelas,
            p.descricao AS parcelamento_descricao

        FROM transacoes t

        INNER JOIN subgrupos s
            ON s.id = t.subgrupo_id

        INNER JOIN grupos g
            ON g.id = s.grupo_id

        LEFT JOIN contas c
            ON c.id = t.conta_id

        LEFT JOIN parcelamentos p
            ON p.id = t.parcelamento_id

        WHERE t.usuario_id = :usuario_id
    ";


        $params = [
            ':usuario_id' => $usuarioId
        ];


        /*
        |--------------------------------------------------------------------------
        | Período
        |--------------------------------------------------------------------------
        */

        if (
            isset($filters['data_inicio']) &&
            $filters['data_inicio'] !== ''
        ) {
            $sql .= "
            AND COALESCE(
                t.data_efetivacao,
                t.data_vencimento,
                t.data_competencia
            ) >= :data_inicio
        ";

            $params[':data_inicio'] =
                $filters['data_inicio'];
        }


        if (
            isset($filters['data_fim']) &&
            $filters['data_fim'] !== ''
        ) {
            $sql .= "
            AND COALESCE(
                t.data_efetivacao,
                t.data_vencimento,
                t.data_competencia
            ) <= :data_fim
        ";

            $params[':data_fim'] =
                $filters['data_fim'];
        }


        /*
        |--------------------------------------------------------------------------
        | Status
        |--------------------------------------------------------------------------
        */

        if (
            isset($filters['status']) &&
            $filters['status'] !== ''
        ) {
            $sql .= "
            AND t.status = :status
        ";

            $params[':status'] =
                $filters['status'];
        }


        /*
        |--------------------------------------------------------------------------
        | Tipo
        |--------------------------------------------------------------------------
        */

        if (
            isset($filters['tipo']) &&
            $filters['tipo'] !== ''
        ) {
            $sql .= "
            AND g.tipo = :tipo
        ";

            $params[':tipo'] =
                $filters['tipo'];
        }


        /*
        |--------------------------------------------------------------------------
        | Conta
        |--------------------------------------------------------------------------
        */

        if (
            isset($filters['conta_id']) &&
            $filters['conta_id'] !== null
        ) {
            $sql .= "
            AND t.conta_id = :conta_id
        ";

            $params[':conta_id'] =
                $filters['conta_id'];
        }


        /*
        |--------------------------------------------------------------------------
        | Categoria
        |--------------------------------------------------------------------------
        */

        if (
            isset($filters['grupo_id']) &&
            $filters['grupo_id'] !== null
        ) {
            $sql .= "
            AND g.id = :grupo_id
        ";

            $params[':grupo_id'] =
                $filters['grupo_id'];
        }


        $sql .= "
        ORDER BY
            COALESCE(
                t.data_efetivacao,
                t.data_vencimento,
                t.data_competencia
            ) DESC,

            t.id DESC
    ";


        $stmt = $this->pdo->prepare(
            $sql
        );


        $stmt->execute(
            $params
        );


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
            cartao_id, fatura_id, recorrencia_id, parcelamento_id,
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
          AND status = 'pendente' AND cartao_id IS NULL
    ");

        $stmt->execute([
            ':id' => $transacaoId,
            ':usuario_id' => $usuarioId
        ]);

        return $stmt->rowCount() === 1;
    }

    public function updatePending(
        int $transacaoId,
        int $usuarioId,
        int $subgrupoId,
        ?int $contaId,
        string $descricao,
        int $valorCentavos,
        string $dataCompetencia,
        string $dataVencimento,
        ?string $meioPagamento,
        ?string $observacao
    ): bool {
        $stmt = $this->pdo->prepare("
        UPDATE transacoes
        SET
            subgrupo_id = :subgrupo_id,
            conta_id = :conta_id,
            descricao = :descricao,
            valor_centavos = :valor_centavos,
            data_competencia = :data_competencia,
            data_vencimento = :data_vencimento,
            meio_pagamento = :meio_pagamento,
            observacao = :observacao,
            atualizado_em = CURRENT_TIMESTAMP
        WHERE id = :id
          AND usuario_id = :usuario_id
          AND status = 'pendente'
    ");

        $stmt->execute([
            ':subgrupo_id' => $subgrupoId,
            ':conta_id' => $contaId,
            ':descricao' => $descricao,
            ':valor_centavos' => $valorCentavos,
            ':data_competencia' => $dataCompetencia,
            ':data_vencimento' => $dataVencimento,
            ':meio_pagamento' => $meioPagamento,
            ':observacao' => $observacao,
            ':id' => $transacaoId,
            ':usuario_id' => $usuarioId
        ]);

        return $stmt->rowCount() === 1;
    }

    public function createInstallment(
        int $usuarioId,
        int $subgrupoId,
        ?int $contaId,
        int $parcelamentoId,
        int $numeroParcela,
        string $descricao,
        int $valorCentavos,
        string $dataCompetencia,
        string $dataVencimento,
        ?string $meioPagamento,
        ?string $observacao
    ): int {
        $stmt = $this->pdo->prepare("
        INSERT INTO transacoes (
            usuario_id,
            subgrupo_id,
            conta_id,
            parcelamento_id,
            numero_parcela,
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
            :parcelamento_id,
            :numero_parcela,
            :descricao,
            :valor_centavos,
            :data_competencia,
            :data_vencimento,
            NULL,
            'pendente',
            :meio_pagamento,
            :observacao
        )
    ");

        $stmt->execute([
            ':usuario_id' => $usuarioId,
            ':subgrupo_id' => $subgrupoId,
            ':conta_id' => $contaId,
            ':parcelamento_id' => $parcelamentoId,
            ':numero_parcela' => $numeroParcela,
            ':descricao' => $descricao,
            ':valor_centavos' => $valorCentavos,
            ':data_competencia' => $dataCompetencia,
            ':data_vencimento' => $dataVencimento,
            ':meio_pagamento' => $meioPagamento,
            ':observacao' => $observacao
        ]);

        return (int) 
            $this->pdo->lastInsertId();
    }

    public function recurringOccurrenceExists(
        int $recorrenciaId,
        string $dataVencimento
    ): bool {
        $stmt = $this->pdo->prepare("
        SELECT 1

        FROM transacoes

        WHERE recorrencia_id
            = :recorrencia_id

          AND data_vencimento
            = :data_vencimento

        LIMIT 1
    ");

        $stmt->execute([
            ':recorrencia_id'
            => $recorrenciaId,

            ':data_vencimento'
            => $dataVencimento
        ]);

        return $stmt->fetchColumn()
            !== false;
    }

    public function createRecurringOccurrence(
        int $usuarioId,
        int $subgrupoId,
        ?int $contaId,
        int $recorrenciaId,
        string $descricao,
        int $valorCentavos,
        string $dataVencimento,
        ?string $meioPagamento,
        ?string $observacao
    ): int {
        $stmt = $this->pdo->prepare("
        INSERT INTO transacoes (
            usuario_id,
            subgrupo_id,
            conta_id,
            recorrencia_id,

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
            :recorrencia_id,

            :descricao,
            :valor_centavos,

            :data_competencia,
            :data_vencimento,
            NULL,

            'pendente',
            :meio_pagamento,
            :observacao
        )
    ");

        $stmt->execute([
            ':usuario_id'
            => $usuarioId,

            ':subgrupo_id'
            => $subgrupoId,

            ':conta_id'
            => $contaId,

            ':recorrencia_id'
            => $recorrenciaId,

            ':descricao'
            => $descricao,

            ':valor_centavos'
            => $valorCentavos,

            ':data_competencia'
            => $dataVencimento,

            ':data_vencimento'
            => $dataVencimento,

            ':meio_pagamento'
            => $meioPagamento,

            ':observacao'
            => $observacao
        ]);

        return (int) 
            $this->pdo->lastInsertId();
    }
}
