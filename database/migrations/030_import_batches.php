<?php
declare(strict_types=1);

return function (PDO $pdo): void {
    $pdo->exec('CREATE TABLE lotes_importacao (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        usuario_id INTEGER NOT NULL REFERENCES usuarios(id),
        criado_em TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        desfeito_em TEXT
    )');
    $pdo->exec('CREATE TABLE itens_importacao (
        lote_id INTEGER NOT NULL REFERENCES lotes_importacao(id),
        transacao_id INTEGER NOT NULL UNIQUE REFERENCES transacoes(id),
        PRIMARY KEY(lote_id, transacao_id)
    )');
    $pdo->exec('CREATE INDEX idx_lotes_importacao_usuario ON lotes_importacao(usuario_id, id DESC)');
};
