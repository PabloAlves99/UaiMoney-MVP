<?php

declare(strict_types=1);

return function (PDO $pdo): void {

    $pdo->exec("CREATE TABLE cartoes (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,

                    usuario_id INTEGER NOT NULL,

                    conta_pagamento_id INTEGER,

                    nome TEXT NOT NULL
                        COLLATE NOCASE,

                    instituicao TEXT,

                    limite_centavos INTEGER
                        CHECK (
                            limite_centavos IS NULL
                            OR limite_centavos >= 0
                        ),

                    dia_fechamento INTEGER NOT NULL
                        CHECK (
                            dia_fechamento BETWEEN 1 AND 31
                        ),

                    dia_vencimento INTEGER NOT NULL
                        CHECK (
                            dia_vencimento BETWEEN 1 AND 31
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

                    FOREIGN KEY (conta_pagamento_id)
                        REFERENCES contas(id)
                        ON UPDATE CASCADE
                        ON DELETE RESTRICT,

                    UNIQUE (
                        usuario_id,
                        nome
                    )
                )
    ");

    $pdo->exec("
        CREATE INDEX idx_cartoes_usuario
        ON cartoes(usuario_id)
    ");

};