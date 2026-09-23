<?php
declare(strict_types=1);

return function (PDO $pdo): void {
    $pdo->exec("CREATE TABLE transferencias (
        id INTEGER PRIMARY KEY AUTOINCREMENT, usuario_id INTEGER NOT NULL REFERENCES usuarios(id),
        origem_id INTEGER NOT NULL REFERENCES contas(id), destino_id INTEGER NOT NULL REFERENCES contas(id),
        valor_centavos INTEGER NOT NULL CHECK(valor_centavos > 0), data TEXT NOT NULL,
        descricao TEXT NOT NULL, criado_em TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        CHECK(origem_id <> destino_id))");
    $pdo->exec("CREATE TABLE conferencias (
        id INTEGER PRIMARY KEY AUTOINCREMENT, usuario_id INTEGER NOT NULL REFERENCES usuarios(id),
        conta_id INTEGER NOT NULL REFERENCES contas(id), data TEXT NOT NULL,
        saldo_anterior_centavos INTEGER NOT NULL, saldo_informado_centavos INTEGER NOT NULL,
        ajuste_centavos INTEGER NOT NULL, motivo TEXT NOT NULL,
        criado_em TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP)");
    $pdo->exec("CREATE TABLE estornos (
        id INTEGER PRIMARY KEY AUTOINCREMENT, usuario_id INTEGER NOT NULL REFERENCES usuarios(id),
        transacao_id INTEGER NOT NULL REFERENCES transacoes(id), fatura_id INTEGER REFERENCES faturas(id),
        valor_centavos INTEGER NOT NULL CHECK(valor_centavos > 0), data TEXT NOT NULL, motivo TEXT NOT NULL,
        criado_em TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP)");
    $pdo->exec("CREATE INDEX idx_estornos_transacao ON estornos(usuario_id, transacao_id)");
    $pdo->exec("CREATE TABLE orcamentos (
        id INTEGER PRIMARY KEY AUTOINCREMENT, usuario_id INTEGER NOT NULL REFERENCES usuarios(id),
        ano_mes TEXT NOT NULL, grupo_id INTEGER NOT NULL REFERENCES grupos(id),
        valor_limite_centavos INTEGER NOT NULL CHECK(valor_limite_centavos > 0),
        criado_em TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP, atualizado_em TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(usuario_id, ano_mes, grupo_id))");
    $pdo->exec("CREATE TABLE operacoes (
        usuario_id INTEGER NOT NULL REFERENCES usuarios(id), chave TEXT NOT NULL,
        criado_em TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY(usuario_id, chave))");
    $pdo->exec("CREATE INDEX idx_transacoes_competencia ON transacoes(usuario_id, data_competencia, status)");
    // A single cash ledger is shared by balances, reconciliation and account statements.
    $pdo->exec("CREATE VIEW movimentos_caixa AS
        SELECT t.usuario_id, t.conta_id, t.data_efetivacao AS data, t.descricao,
            CASE g.tipo WHEN 'receita' THEN t.valor_centavos ELSE -t.valor_centavos END AS valor_centavos,
            'lancamento' AS origem, t.id AS origem_id
        FROM transacoes t JOIN subgrupos s ON s.id=t.subgrupo_id
        JOIN grupos g ON g.id=s.grupo_id AND g.usuario_id=t.usuario_id
        WHERE t.status='efetivada' AND t.cartao_id IS NULL AND t.conta_id IS NOT NULL
        UNION ALL
        SELECT usuario_id, origem_id, data, descricao, -valor_centavos, 'transferencia', id FROM transferencias
        UNION ALL
        SELECT usuario_id, destino_id, data, descricao, valor_centavos, 'transferencia', id FROM transferencias
        UNION ALL
        SELECT p.usuario_id, p.conta_id, p.data_pagamento, 'Pagamento de fatura · ' || c.nome,
            -p.valor_centavos, 'fatura', p.id
        FROM pagamentos_fatura p JOIN faturas f ON f.id=p.fatura_id AND f.usuario_id=p.usuario_id
        JOIN cartoes c ON c.id=f.cartao_id AND c.usuario_id=p.usuario_id
        UNION ALL
        SELECT usuario_id, conta_id, data, 'Conferência · ' || motivo, ajuste_centavos, 'conferencia', id FROM conferencias
        UNION ALL
        SELECT e.usuario_id, t.conta_id, e.data, 'Estorno · ' || t.descricao,
            CASE g.tipo WHEN 'despesa' THEN e.valor_centavos ELSE -e.valor_centavos END, 'estorno', e.id
        FROM estornos e JOIN transacoes t ON t.id=e.transacao_id AND t.usuario_id=e.usuario_id
        JOIN subgrupos s ON s.id=t.subgrupo_id JOIN grupos g ON g.id=s.grupo_id AND g.usuario_id=e.usuario_id
        WHERE t.cartao_id IS NULL");
    $pdo->exec("CREATE VIEW consumo AS
        SELECT t.usuario_id, t.id AS transacao_id, t.subgrupo_id, s.grupo_id, g.tipo,
            t.conta_id, t.cartao_id, t.meio_pagamento, t.recorrencia_id,
            t.data_competencia AS data, t.valor_centavos, t.status, t.descricao
        FROM transacoes t JOIN subgrupos s ON s.id=t.subgrupo_id
        JOIN grupos g ON g.id=s.grupo_id AND g.usuario_id=t.usuario_id
        UNION ALL
        SELECT e.usuario_id, t.id, t.subgrupo_id, s.grupo_id, g.tipo,
            t.conta_id, t.cartao_id, t.meio_pagamento, t.recorrencia_id,
            e.data, -e.valor_centavos, 'efetivada', 'Estorno · ' || t.descricao
        FROM estornos e JOIN transacoes t ON t.id=e.transacao_id AND t.usuario_id=e.usuario_id
        JOIN subgrupos s ON s.id=t.subgrupo_id JOIN grupos g ON g.id=s.grupo_id AND g.usuario_id=e.usuario_id");
};
