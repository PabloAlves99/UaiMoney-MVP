<?php

declare(strict_types=1);

return function (PDO $pdo): void {

    $pdo->exec("CREATE TABLE usuarios (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,

                    nome TEXT NOT NULL,

                    login TEXT NOT NULL
                        COLLATE NOCASE
                        UNIQUE,

                    email TEXT NOT NULL
                        COLLATE NOCASE
                        UNIQUE,

                    senha_hash TEXT NOT NULL,

                    tipo TEXT NOT NULL
                        DEFAULT 'usuario'
                        CHECK (
                            tipo IN ('usuario', 'admin')
                        ),

                    ativo INTEGER NOT NULL
                        DEFAULT 1
                        CHECK (
                            ativo IN (0, 1)
                        ),

                    criado_em TEXT NOT NULL
                        DEFAULT CURRENT_TIMESTAMP,

                    atualizado_em TEXT NOT NULL
                        DEFAULT CURRENT_TIMESTAMP
                )
    ");

};