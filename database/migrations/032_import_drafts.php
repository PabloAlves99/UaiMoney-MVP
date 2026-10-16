<?php
declare(strict_types=1);

return function (PDO $pdo): void {
    $pdo->exec('CREATE TABLE rascunhos_importacao (
        usuario_id INTEGER PRIMARY KEY REFERENCES usuarios(id) ON DELETE CASCADE,
        conta_id INTEGER NOT NULL REFERENCES contas(id),
        nonce TEXT NOT NULL,
        itens_json TEXT NOT NULL,
        criado_em TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        atualizado_em TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    )');
};
