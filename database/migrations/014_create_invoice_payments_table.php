<?php

declare(strict_types=1);

return function (PDO $pdo): void {

    $pdo->exec("CREATE TABLE pagamentos_fatura (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,

                    usuario_id INTEGER NOT NULL,

                    fatura_id INTEGER NOT NULL,

                    conta_id INTEGER NOT NULL,

                    valor_centavos INTEGER NOT NULL
                        CHECK (
                            valor_centavos > 0
                        ),

                    data_pagamento TEXT NOT NULL
                        DEFAULT CURRENT_DATE,

                    observacao TEXT,

                    criado_em TEXT NOT NULL
                        DEFAULT CURRENT_TIMESTAMP,

                    FOREIGN KEY (usuario_id)
                        REFERENCES usuarios(id)
                        ON UPDATE CASCADE
                        ON DELETE CASCADE,

                    FOREIGN KEY (fatura_id)
                        REFERENCES faturas(id)
                        ON UPDATE CASCADE
                        ON DELETE RESTRICT,

                    FOREIGN KEY (conta_id)
                        REFERENCES contas(id)
                        ON UPDATE CASCADE
                        ON DELETE RESTRICT
                )
    ");

    $pdo->exec("
        CREATE INDEX idx_pagamentos_fatura_fatura
        ON pagamentos_fatura(fatura_id)
    ");

    $pdo->exec("
        CREATE INDEX idx_pagamentos_fatura_conta
        ON pagamentos_fatura(conta_id)
    ");

    $pdo->exec("
        CREATE INDEX idx_pagamentos_fatura_usuario
        ON pagamentos_fatura(usuario_id)
    ");

};