<?php

declare(strict_types=1);

return function (PDO $pdo): void {

    $pdo->exec("CREATE TABLE transacoes (
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

                    data_competencia TEXT NOT NULL
                        DEFAULT CURRENT_DATE,

                    data_vencimento TEXT NOT NULL
                        DEFAULT CURRENT_DATE,

                    data_efetivacao TEXT,

                    status TEXT NOT NULL
                        DEFAULT 'pendente'
                        CHECK (
                            status IN (
                                'pendente',
                                'efetivada',
                                'cancelada'
                            )
                        ),

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


    /*
     * Índices
     */

    $pdo->exec("
        CREATE INDEX idx_transacoes_usuario
        ON transacoes(usuario_id)
    ");

    $pdo->exec("
        CREATE INDEX idx_transacoes_usuario_vencimento
        ON transacoes(
            usuario_id,
            data_vencimento
        )
    ");

    $pdo->exec("
        CREATE INDEX idx_transacoes_usuario_status
        ON transacoes(
            usuario_id,
            status
        )
    ");

    $pdo->exec("
        CREATE INDEX idx_transacoes_subgrupo
        ON transacoes(subgrupo_id)
    ");

    $pdo->exec("
        CREATE INDEX idx_transacoes_conta
        ON transacoes(conta_id)
    ");

};