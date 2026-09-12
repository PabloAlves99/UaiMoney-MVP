<?php

declare(strict_types=1);

return function (PDO $pdo): void {

    $pdo->exec("CREATE TABLE faturas (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,

                    usuario_id INTEGER NOT NULL,

                    cartao_id INTEGER NOT NULL,

                    competencia TEXT NOT NULL,

                    data_fechamento TEXT NOT NULL,

                    data_vencimento TEXT NOT NULL,

                    status TEXT NOT NULL
                        DEFAULT 'aberta'
                        CHECK (
                            status IN (
                                'aberta',
                                'fechada',
                                'cancelada'
                            )
                        ),

                    criado_em TEXT NOT NULL
                        DEFAULT CURRENT_TIMESTAMP,

                    atualizado_em TEXT NOT NULL
                        DEFAULT CURRENT_TIMESTAMP,

                    FOREIGN KEY (usuario_id)
                        REFERENCES usuarios(id)
                        ON UPDATE CASCADE
                        ON DELETE CASCADE,

                    FOREIGN KEY (cartao_id)
                        REFERENCES cartoes(id)
                        ON UPDATE CASCADE
                        ON DELETE RESTRICT,

                    UNIQUE (
                        cartao_id,
                        competencia
                    )
                )
    ");

    $pdo->exec("
        CREATE INDEX idx_faturas_usuario
        ON faturas(usuario_id)
    ");

    $pdo->exec("
        CREATE INDEX idx_faturas_cartao_competencia
        ON faturas(
            cartao_id,
            competencia
        )
    ");

};