<?php

declare(strict_types=1);

return function (PDO $pdo): void {

    $pdo->exec("CREATE TABLE recorrencias (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,

                    usuario_id INTEGER NOT NULL,

                    subgrupo_id INTEGER NOT NULL,

                    conta_id INTEGER,

                    descricao TEXT NOT NULL
                        CHECK (
                            LENGTH(TRIM(descricao)) > 0
                        ),

                    valor_centavos INTEGER NOT NULL
                        CHECK (
                            valor_centavos > 0
                        ),

                    frequencia TEXT NOT NULL
                        CHECK (
                            frequencia IN (
                                'diaria',
                                'semanal',
                                'mensal',
                                'anual'
                            )
                        ),

                    intervalo INTEGER NOT NULL
                        DEFAULT 1
                        CHECK (
                            intervalo > 0
                        ),

                    data_inicio TEXT NOT NULL,

                    data_fim TEXT,

                    meio_pagamento TEXT
                        CHECK (
                            meio_pagamento IS NULL
                            OR meio_pagamento IN (
                                'pix',
                                'dinheiro',
                                'debito',
                                'credito',
                                'boleto',
                                'transferencia',
                                'outro'
                            )
                        ),

                    observacao TEXT,

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

                    FOREIGN KEY (subgrupo_id)
                        REFERENCES subgrupos(id)
                        ON UPDATE CASCADE
                        ON DELETE RESTRICT,

                    FOREIGN KEY (conta_id)
                        REFERENCES contas(id)
                        ON UPDATE CASCADE
                        ON DELETE RESTRICT
                )
    ");

    $pdo->exec("
        CREATE INDEX idx_recorrencias_usuario
        ON recorrencias(usuario_id)
    ");

    $pdo->exec("
        CREATE INDEX idx_recorrencias_usuario_ativo
        ON recorrencias(
            usuario_id,
            ativo
        )
    ");

};