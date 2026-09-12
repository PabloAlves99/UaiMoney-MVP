<?php

declare(strict_types=1);

return function (PDO $pdo): void {

    $pdo->exec("CREATE TABLE contas (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,

                    usuario_id INTEGER NOT NULL,

                    nome TEXT NOT NULL
                        COLLATE NOCASE,

                    tipo TEXT NOT NULL
                        CHECK (
                            tipo IN (
                                'corrente',
                                'poupanca',
                                'dinheiro',
                                'carteira_digital',
                                'outro'
                            )
                        ),

                    instituicao TEXT,

                    saldo_inicial_centavos INTEGER NOT NULL
                        DEFAULT 0,

                    saldo_inicial_em TEXT NOT NULL
                        DEFAULT CURRENT_DATE,

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
                        nome
                    )
                )
    ");

};