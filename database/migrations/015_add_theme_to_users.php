<?php

declare(strict_types=1);

return function (PDO $pdo): void {

    $pdo->exec("
        ALTER TABLE usuarios
        ADD COLUMN tema TEXT NOT NULL DEFAULT 'light'
            CHECK (tema IN ('light', 'dark'))
    ");

};