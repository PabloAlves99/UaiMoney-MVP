<?php

declare(strict_types=1);

return function (PDO $pdo): void {
    $pdo->exec("ALTER TABLE grupos ADD COLUMN classificacao TEXT NOT NULL DEFAULT 'nao_classificada' CHECK (classificacao IN ('essencial', 'variavel', 'nao_classificada'))");
    $pdo->exec("CREATE TABLE metas_financeiras (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        usuario_id INTEGER NOT NULL REFERENCES usuarios(id) ON UPDATE CASCADE ON DELETE CASCADE,
        nome TEXT NOT NULL CHECK (LENGTH(TRIM(nome)) > 0),
        tipo TEXT NOT NULL CHECK (tipo IN ('reserva', 'viagem', 'imovel', 'educacao', 'outro')),
        valor_meta_centavos INTEGER NOT NULL CHECK (valor_meta_centavos > 0),
        valor_atual_centavos INTEGER NOT NULL DEFAULT 0 CHECK (valor_atual_centavos >= 0),
        data_alvo TEXT,
        ativo INTEGER NOT NULL DEFAULT 1 CHECK (ativo IN (0, 1)),
        criado_em TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        atualizado_em TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    )");
    $pdo->exec('CREATE INDEX idx_metas_financeiras_usuario_ativo ON metas_financeiras(usuario_id, ativo)');
};
