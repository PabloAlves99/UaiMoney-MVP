<?php

declare(strict_types=1);

return function (PDO $pdo): void {

    $pdo->exec("CREATE TABLE subgrupos (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,

                    grupo_id INTEGER NOT NULL,

                    nome TEXT NOT NULL
                        COLLATE NOCASE,

                    descricao TEXT,

                    ativo INTEGER NOT NULL
                        DEFAULT 1
                        CHECK (
                            ativo IN (0, 1)
                        ),

                    criado_em TEXT NOT NULL
                        DEFAULT CURRENT_TIMESTAMP,

                    atualizado_em TEXT NOT NULL
                        DEFAULT CURRENT_TIMESTAMP,

                    FOREIGN KEY (grupo_id)
                        REFERENCES grupos(id)
                        ON UPDATE CASCADE
                        ON DELETE CASCADE,

                    UNIQUE (
                        grupo_id,
                        nome
                    )
                )
    ");

};