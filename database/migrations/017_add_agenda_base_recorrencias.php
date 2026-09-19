<?php

declare(strict_types=1);

return function (PDO $pdo): void {

    $pdo->exec("
        ALTER TABLE recorrencias

        ADD COLUMN agenda_base DATE NULL
    ");


    /*
     * Para recorrências já existentes,
     * usamos a data inicial como base da agenda.
     */

    $pdo->exec("
        UPDATE recorrencias

        SET agenda_base = data_inicio

        WHERE agenda_base IS NULL
    ");

};