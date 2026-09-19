<?php

declare(strict_types=1);

return function (PDO $pdo): void {

    $pdo->exec("
        ALTER TABLE recorrencias
        ADD COLUMN proxima_ocorrencia DATE NULL
    ");

    $pdo->exec("
        ALTER TABLE recorrencias
        ADD COLUMN total_ocorrencias INTEGER NULL
        CHECK (
            total_ocorrencias IS NULL
            OR total_ocorrencias > 0
        )
    ");

    $pdo->exec("
        ALTER TABLE recorrencias
        ADD COLUMN ocorrencias_geradas INTEGER NOT NULL DEFAULT 0
        CHECK (ocorrencias_geradas >= 0)
    ");


    /*
     * Para alguma recorrência antiga eventualmente existente,
     * a primeira ocorrência será data_inicio.
     */

    $pdo->exec("
        UPDATE recorrencias
        SET proxima_ocorrencia = data_inicio
        WHERE ativo = 1
          AND proxima_ocorrencia IS NULL
    ");


    /*
     * Índice usado pelo Job.
     */

    $pdo->exec("
        CREATE INDEX idx_recorrencias_processamento
        ON recorrencias (
            ativo,
            proxima_ocorrencia
        )
    ");


    /*
     * Proteção definitiva contra duplicação.
     *
     * Uma recorrência não pode gerar duas transações
     * para o mesmo vencimento.
     */

    $pdo->exec("
        CREATE UNIQUE INDEX uq_transacoes_recorrencia_vencimento
        ON transacoes (
            recorrencia_id,
            data_vencimento
        )
        WHERE recorrencia_id IS NOT NULL
    ");
};