<?php

declare(strict_types=1);

return function (PDO $pdo): void {
    $pdo->exec("CREATE TABLE compartilhamentos_conta (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        usuario_id INTEGER NOT NULL REFERENCES usuarios(id),
        conta_id INTEGER NOT NULL REFERENCES contas(id),
        token_hash TEXT NOT NULL UNIQUE,
        token_hint TEXT NOT NULL,
        senha_hash TEXT,
        ativo INTEGER NOT NULL DEFAULT 1 CHECK(ativo IN (0, 1)),
        criado_em TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        atualizado_em TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        ultimo_acesso_em TEXT,
        UNIQUE(usuario_id, conta_id)
    )");
    $pdo->exec('CREATE INDEX idx_compartilhamentos_conta_token ON compartilhamentos_conta(token_hash, ativo)');
};
