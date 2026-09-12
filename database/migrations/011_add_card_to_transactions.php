<?php

declare(strict_types=1);

return function (PDO $pdo): void {

    $pdo->exec("
        ALTER TABLE transacoes
        ADD COLUMN cartao_id INTEGER
            REFERENCES cartoes(id)
            ON UPDATE CASCADE
            ON DELETE RESTRICT
    ");

    $pdo->exec("
        CREATE INDEX idx_transacoes_cartao
        ON transacoes(cartao_id)
    ");

};