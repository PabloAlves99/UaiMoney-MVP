<?php

declare(strict_types=1);

return function (PDO $pdo): void {

    $pdo->exec("CREATE TABLE parcelamentos (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,

                    usuario_id INTEGER NOT NULL,

                    descricao TEXT NOT NULL
                        CHECK (
                            LENGTH(TRIM(descricao)) > 0
                        ),

                    valor_total_centavos INTEGER NOT NULL
                        CHECK (
                            valor_total_centavos > 0
                        ),

                    total_parcelas INTEGER NOT NULL
                        CHECK (
                            total_parcelas > 1
                        ),

                    criado_em TEXT NOT NULL
                        DEFAULT CURRENT_TIMESTAMP,

                    atualizado_em TEXT NOT NULL
                        DEFAULT CURRENT_TIMESTAMP,

                    FOREIGN KEY (usuario_id)
                        REFERENCES usuarios(id)
                        ON UPDATE CASCADE
                        ON DELETE CASCADE
                )
    ");

    $pdo->exec("
        CREATE INDEX idx_parcelamentos_usuario
        ON parcelamentos(usuario_id)
    ");

};