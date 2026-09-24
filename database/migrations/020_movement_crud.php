<?php

declare(strict_types=1);

return function (PDO $pdo): void {
    $pdo->exec('ALTER TABLE transacoes ADD COLUMN excluida_em TEXT');
    $pdo->exec('ALTER TABLE transacoes ADD COLUMN status_antes_exclusao TEXT');
    $pdo->exec('ALTER TABLE transacoes ADD COLUMN versao INTEGER NOT NULL DEFAULT 1');

    $pdo->exec("CREATE TABLE historico_movimentacoes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        usuario_id INTEGER NOT NULL REFERENCES usuarios(id),
        transacao_id INTEGER NOT NULL REFERENCES transacoes(id),
        acao TEXT NOT NULL,
        dados_anteriores TEXT NOT NULL,
        criado_em TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    )");

    /*
     * Lançamentos excluídos e seus estornos deixam de compor
     * os saldos e as análises, preservando os registros para restauração.
     */
    foreach (['consumo', 'movimentos_caixa'] as $view) {
        $statement = $pdo->prepare("SELECT sql FROM sqlite_master WHERE type='view' AND name=?");
        $statement->execute([$view]);
        $sql = (string) $statement->fetchColumn();
        $sql = str_replace(
            ['FROM transacoes t ', 'JOIN transacoes t '],
            ['FROM (SELECT * FROM transacoes WHERE excluida_em IS NULL) t ', 'JOIN (SELECT * FROM transacoes WHERE excluida_em IS NULL) t '],
            $sql
        );
        $pdo->exec('DROP VIEW ' . $view);
        $pdo->exec($sql);
    }
};
