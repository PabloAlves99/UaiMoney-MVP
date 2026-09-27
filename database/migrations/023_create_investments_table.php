<?php

declare(strict_types=1);

return function (PDO $pdo): void {
    $pdo->exec("CREATE TABLE investimentos (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        usuario_id INTEGER NOT NULL REFERENCES usuarios(id) ON UPDATE CASCADE ON DELETE CASCADE,
        nome TEXT NOT NULL CHECK (LENGTH(TRIM(nome)) > 0),
        tipo TEXT NOT NULL CHECK (tipo IN ('renda_fixa', 'renda_variavel', 'fundo', 'previdencia', 'cripto', 'outro')),
        instituicao TEXT,
        objetivo TEXT,
        valor_aplicado_centavos INTEGER NOT NULL DEFAULT 0 CHECK (valor_aplicado_centavos >= 0),
        valor_atual_centavos INTEGER NOT NULL DEFAULT 0 CHECK (valor_atual_centavos >= 0),
        data_inicio TEXT NOT NULL,
        ativo INTEGER NOT NULL DEFAULT 1 CHECK (ativo IN (0, 1)),
        criado_em TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        atualizado_em TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    )");
    $pdo->exec('CREATE INDEX idx_investimentos_usuario_ativo ON investimentos(usuario_id, ativo)');
};
