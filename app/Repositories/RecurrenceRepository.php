<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class RecurrenceRepository
{
    public function __construct(
        private readonly PDO $pdo
    ) {
    }


    public function create(
        int $usuarioId,
        int $subgrupoId,
        ?int $contaId,
        string $descricao,
        int $valorCentavos,
        string $frequencia,
        int $intervalo,
        string $dataInicio,
        ?int $totalOcorrencias,
        ?string $meioPagamento,
        ?string $observacao
    ): int {
        $stmt = $this->pdo->prepare("
        INSERT INTO recorrencias (
            usuario_id,
            subgrupo_id,
            conta_id,
            descricao,
            valor_centavos,
            frequencia,
            intervalo,
            data_inicio,
            data_fim,
            meio_pagamento,
            observacao,
            ativo,
            proxima_ocorrencia,
            agenda_base,
            total_ocorrencias,
            ocorrencias_geradas
        )
        VALUES (
            :usuario_id,
            :subgrupo_id,
            :conta_id,
            :descricao,
            :valor_centavos,
            :frequencia,
            :intervalo,
            :data_inicio,
            NULL,
            :meio_pagamento,
            :observacao,
            1,
            :proxima_ocorrencia,
            :agenda_base,
            :total_ocorrencias,
            0
        )
    ");

        $stmt->execute([
            ':usuario_id' => $usuarioId,
            ':subgrupo_id' => $subgrupoId,
            ':conta_id' => $contaId,
            ':descricao' => $descricao,
            ':valor_centavos' => $valorCentavos,
            ':frequencia' => $frequencia,
            ':intervalo' => $intervalo,
            ':data_inicio' => $dataInicio,
            ':meio_pagamento' => $meioPagamento,
            ':observacao' => $observacao,
            ':proxima_ocorrencia' => $dataInicio,
            ':agenda_base' => $dataInicio,
            ':total_ocorrencias' => $totalOcorrencias
        ]);

        return (int) $this->pdo->lastInsertId();
    }


    public function findDue(
        string $data,
        ?int $usuarioId = null
    ): array {
        $sql = "
            SELECT
                id,
                usuario_id,
                subgrupo_id,
                conta_id,
                descricao,
                valor_centavos,
                frequencia,
                intervalo,
                data_inicio,
                data_fim,
                meio_pagamento,
                observacao,
                ativo,
                proxima_ocorrencia,
                total_ocorrencias,
                ocorrencias_geradas

            FROM recorrencias

            WHERE ativo = 1

              AND proxima_ocorrencia
                  IS NOT NULL

              AND proxima_ocorrencia
                  <= :data
        ";


        $params = [
            ':data' => $data
        ];


        if ($usuarioId !== null) {

            $sql .= "
                AND usuario_id = :usuario_id
            ";

            $params[':usuario_id'] =
                $usuarioId;
        }


        $sql .= "
            ORDER BY
                proxima_ocorrencia ASC,
                id ASC
        ";


        $stmt = $this->pdo->prepare(
            $sql
        );

        $stmt->execute(
            $params
        );

        return $stmt->fetchAll();
    }


    public function findById(
        int $recorrenciaId
    ): ?array {
        $stmt = $this->pdo->prepare("
            SELECT
                id,
                usuario_id,
                subgrupo_id,
                conta_id,
                descricao,
                valor_centavos,
                frequencia,
                intervalo,
                data_inicio,
                data_fim,
                meio_pagamento,
                observacao,
                ativo,
                proxima_ocorrencia,
                agenda_base,
                total_ocorrencias,
                ocorrencias_geradas

            FROM recorrencias

            WHERE id = :id

            LIMIT 1
        ");

        $stmt->execute([
            ':id' => $recorrenciaId
        ]);

        $recorrencia =
            $stmt->fetch();

        return $recorrencia ?: null;
    }


    public function updateProcessingState(
        int $recorrenciaId,
        int $ocorrenciasGeradas,
        ?string $proximaOcorrencia,
        bool $ativo
    ): void {
        $stmt = $this->pdo->prepare("
            UPDATE recorrencias

            SET
                ocorrencias_geradas
                    = :ocorrencias_geradas,

                proxima_ocorrencia
                    = :proxima_ocorrencia,

                ativo
                    = :ativo,

                atualizado_em
                    = CURRENT_TIMESTAMP

            WHERE id = :id
        ");

        $stmt->execute([
            ':ocorrencias_geradas'
            => $ocorrenciasGeradas,

            ':proxima_ocorrencia'
            => $proximaOcorrencia,

            ':ativo'
            => $ativo ? 1 : 0,

            ':id'
            => $recorrenciaId
        ]);
    }

    public function listByUser(
        int $usuarioId
    ): array {

        $stmt = $this->pdo->prepare("
        SELECT
            r.id,
            r.usuario_id,
            r.subgrupo_id,
            r.conta_id,
            r.descricao,
            r.valor_centavos,
            r.frequencia,
            r.intervalo,
            r.data_inicio,
            r.data_fim,
            r.meio_pagamento,
            r.observacao,
            r.ativo,
            r.proxima_ocorrencia,
            r.total_ocorrencias,
            r.ocorrencias_geradas,
            r.criado_em,
            r.atualizado_em,

            s.nome AS subgrupo_nome,

            g.nome AS grupo_nome,
            g.tipo AS tipo,

            c.nome AS conta_nome

        FROM recorrencias r

        INNER JOIN subgrupos s
            ON s.id = r.subgrupo_id

        INNER JOIN grupos g
            ON g.id = s.grupo_id

        LEFT JOIN contas c
            ON c.id = r.conta_id

        WHERE r.usuario_id = :usuario_id

        ORDER BY
            CASE
                WHEN r.ativo = 1 THEN 1
                WHEN r.proxima_ocorrencia IS NOT NULL THEN 2
                ELSE 3
            END,

            r.proxima_ocorrencia ASC,

            r.id DESC
    ");

        $stmt->execute([
            ':usuario_id' => $usuarioId
        ]);

        return $stmt->fetchAll();
    }


    public function findByIdAndUser(
        int $recorrenciaId,
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
            frequencia,
            intervalo,
            data_inicio,
            data_fim,
            meio_pagamento,
            observacao,
            ativo,
            proxima_ocorrencia,
            agenda_base,
            total_ocorrencias,
            ocorrencias_geradas
        FROM recorrencias
        WHERE id = :id
          AND usuario_id = :usuario_id
        LIMIT 1
    ");

        $stmt->execute([
            ':id' => $recorrenciaId,
            ':usuario_id' => $usuarioId
        ]);

        $recorrencia = $stmt->fetch();

        return $recorrencia ?: null;
    }


    public function pause(
        int $recorrenciaId,
        int $usuarioId
    ): void {

        $stmt = $this->pdo->prepare("
        UPDATE recorrencias

        SET
            ativo = 0,
            atualizado_em = CURRENT_TIMESTAMP

        WHERE id = :id
          AND usuario_id = :usuario_id
    ");

        $stmt->execute([
            ':id' => $recorrenciaId,
            ':usuario_id' => $usuarioId
        ]);
    }


    public function resume(
        int $recorrenciaId,
        int $usuarioId
    ): void {

        $stmt = $this->pdo->prepare("
        UPDATE recorrencias

        SET
            ativo = 1,
            atualizado_em = CURRENT_TIMESTAMP

        WHERE id = :id
          AND usuario_id = :usuario_id
          AND proxima_ocorrencia IS NOT NULL
    ");

        $stmt->execute([
            ':id' => $recorrenciaId,
            ':usuario_id' => $usuarioId
        ]);
    }


    public function finish(
        int $recorrenciaId,
        int $usuarioId
    ): void {

        $stmt = $this->pdo->prepare("
        UPDATE recorrencias

        SET
            ativo = 0,
            proxima_ocorrencia = NULL,
            atualizado_em = CURRENT_TIMESTAMP

        WHERE id = :id
          AND usuario_id = :usuario_id
    ");

        $stmt->execute([
            ':id' => $recorrenciaId,
            ':usuario_id' => $usuarioId
        ]);
    }

    public function updateFuture(
        int $recorrenciaId,
        int $usuarioId,
        int $subgrupoId,
        ?int $contaId,
        string $descricao,
        int $valorCentavos,
        string $frequencia,
        int $intervalo,
        string $proximaOcorrencia,
        ?int $totalOcorrencias,
        ?string $meioPagamento,
        ?string $observacao
    ): void {

        $stmt = $this->pdo->prepare("
        UPDATE recorrencias

        SET
            subgrupo_id = :subgrupo_id,
            conta_id = :conta_id,
            descricao = :descricao,
            valor_centavos = :valor_centavos,
            frequencia = :frequencia,
            intervalo = :intervalo,

            proxima_ocorrencia =
                :proxima_ocorrencia,

            agenda_base =
                :agenda_base,

            total_ocorrencias =
                :total_ocorrencias,

            meio_pagamento =
                :meio_pagamento,

            observacao =
                :observacao,

            atualizado_em =
                CURRENT_TIMESTAMP

        WHERE id = :id
          AND usuario_id = :usuario_id
          AND proxima_ocorrencia IS NOT NULL
    ");

        $stmt->execute([
            ':id' => $recorrenciaId,
            ':usuario_id' => $usuarioId,
            ':subgrupo_id' => $subgrupoId,
            ':conta_id' => $contaId,
            ':descricao' => $descricao,
            ':valor_centavos' => $valorCentavos,
            ':frequencia' => $frequencia,
            ':intervalo' => $intervalo,

            ':proxima_ocorrencia' =>
                $proximaOcorrencia,

            ':agenda_base' =>
                $proximaOcorrencia,

            ':total_ocorrencias' =>
                $totalOcorrencias,

            ':meio_pagamento' =>
                $meioPagamento,

            ':observacao' =>
                $observacao
        ]);
    }
}