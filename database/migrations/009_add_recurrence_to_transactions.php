<?php

declare(strict_types=1);

return function (PDO $pdo): void {

    $pdo->exec("
        ALTER TABLE transacoes
        ADD COLUMN recorrencia_id INTEGER
            REFERENCES recorrencias(id)
            ON UPDATE CASCADE
            ON DELETE RESTRICT
    ");

    $pdo->exec("
        CREATE INDEX idx_transacoes_recorrencia
        ON transacoes(recorrencia_id)
    ");

};