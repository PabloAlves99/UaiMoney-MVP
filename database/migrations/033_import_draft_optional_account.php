<?php
declare(strict_types=1);

return function (PDO $pdo): void {
    $pdo->exec('CREATE TABLE rascunhos_importacao_novos (
        usuario_id INTEGER PRIMARY KEY REFERENCES usuarios(id) ON DELETE CASCADE,
        conta_id INTEGER REFERENCES contas(id),
        nonce TEXT NOT NULL,
        itens_json TEXT NOT NULL,
        criado_em TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        atualizado_em TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    )');
    $pdo->exec('INSERT INTO rascunhos_importacao_novos(usuario_id,conta_id,nonce,itens_json,criado_em,atualizado_em)
        SELECT usuario_id,conta_id,nonce,itens_json,criado_em,atualizado_em FROM rascunhos_importacao');
    $pdo->exec('DROP TABLE rascunhos_importacao');
    $pdo->exec('ALTER TABLE rascunhos_importacao_novos RENAME TO rascunhos_importacao');
};
