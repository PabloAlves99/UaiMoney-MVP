<?php

declare(strict_types=1);

return function (PDO $pdo): void {

    $pdo->exec("CREATE TABLE grupos (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,

                    usuario_id INTEGER NOT NULL,

                    nome TEXT NOT NULL
                        COLLATE NOCASE,

                    tipo TEXT NOT NULL
                        CHECK (
                            tipo IN ('receita', 'despesa')
                        ),

                    ativo INTEGER NOT NULL
                        DEFAULT 1
                        CHECK (
                            ativo IN (0, 1)
                        ),

                    criado_em TEXT NOT NULL
                        DEFAULT CURRENT_TIMESTAMP,

                    atualizado_em TEXT NOT NULL
                        DEFAULT CURRENT_TIMESTAMP,

                    FOREIGN KEY (usuario_id)
                        REFERENCES usuarios(id)
                        ON UPDATE CASCADE
                        ON DELETE CASCADE,

                    UNIQUE (
                        usuario_id,
                        tipo,
                        nome
                    )
                )
    ");

};