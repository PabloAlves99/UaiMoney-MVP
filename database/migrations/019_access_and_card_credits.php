<?php
declare(strict_types=1);
return function(PDO $pdo): void {
    $pdo->exec("CREATE TABLE tentativas_acesso(chave TEXT PRIMARY KEY,tentativas INTEGER NOT NULL,inicio INTEGER NOT NULL)");
    $pdo->exec("ALTER TABLE transacoes ADD COLUMN data_compra TEXT");
    $pdo->exec("CREATE TABLE creditos_fatura (
        id INTEGER PRIMARY KEY AUTOINCREMENT, usuario_id INTEGER NOT NULL REFERENCES usuarios(id),
        origem_id INTEGER NOT NULL REFERENCES faturas(id), destino_id INTEGER NOT NULL REFERENCES faturas(id),
        valor_centavos INTEGER NOT NULL CHECK(valor_centavos>0), criado_em TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        CHECK(origem_id<>destino_id))");
};
