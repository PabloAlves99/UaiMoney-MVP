<?php

declare(strict_types=1);

return function (PDO $pdo): void {

    $pdo->exec("
        ALTER TABLE transacoes
        ADD COLUMN fatura_id INTEGER
            REFERENCES faturas(id)
            ON UPDATE CASCADE
            ON DELETE RESTRICT
    ");

    $pdo->exec("
        CREATE INDEX idx_transacoes_fatura
        ON transacoes(fatura_id)
    ");

};