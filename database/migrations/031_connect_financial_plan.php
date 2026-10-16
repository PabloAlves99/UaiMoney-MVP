<?php
declare(strict_types=1);

return function (PDO $pdo): void {
    $pdo->exec("ALTER TABLE investimentos ADD COLUMN aporte_mensal_centavos INTEGER NOT NULL DEFAULT 0 CHECK(aporte_mensal_centavos>=0)");
    $pdo->exec("ALTER TABLE investimentos ADD COLUMN taxa_referencia_quatro_casas INTEGER NOT NULL DEFAULT 0 CHECK(taxa_referencia_quatro_casas BETWEEN 0 AND 1000000)");
    $pdo->exec("CREATE TABLE investimento_eventos (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        usuario_id INTEGER NOT NULL REFERENCES usuarios(id),
        investimento_id INTEGER NOT NULL REFERENCES investimentos(id),
        tipo TEXT NOT NULL CHECK(tipo IN ('saldo_inicial','aporte','retirada','rendimento','ajuste')),
        valor_centavos INTEGER NOT NULL CHECK(valor_centavos <> 0 OR tipo='rendimento'),
        data TEXT NOT NULL,
        descricao TEXT,
        ativo INTEGER NOT NULL DEFAULT 1 CHECK(ativo IN (0,1)),
        criado_em TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        atualizado_em TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    )");
    $pdo->exec('CREATE INDEX idx_investimento_eventos ON investimento_eventos(usuario_id,investimento_id,data,id)');
    $pdo->exec("CREATE UNIQUE INDEX idx_investimento_rendimento_mes ON investimento_eventos(investimento_id,substr(data,1,7)) WHERE tipo='rendimento' AND ativo=1");
    $pdo->exec("INSERT INTO investimento_eventos(usuario_id,investimento_id,tipo,valor_centavos,data,descricao)
        SELECT usuario_id,id,'saldo_inicial',valor_aplicado_centavos,data_inicio,'Posição anterior ao histórico detalhado'
        FROM investimentos WHERE valor_aplicado_centavos>0");
    $pdo->exec("INSERT INTO investimento_eventos(usuario_id,investimento_id,tipo,valor_centavos,data,descricao)
        SELECT usuario_id,id,'ajuste',valor_atual_centavos-valor_aplicado_centavos,data_inicio,'Diferença inicial informada'
        FROM investimentos WHERE valor_atual_centavos<>valor_aplicado_centavos");

    $pdo->exec("ALTER TABLE metas_financeiras ADD COLUMN prioridade TEXT NOT NULL DEFAULT 'media' CHECK(prioridade IN ('alta','media','baixa'))");
    $pdo->exec("ALTER TABLE metas_financeiras ADD COLUMN horizonte TEXT NOT NULL DEFAULT 'indefinido' CHECK(horizonte IN ('curto','medio','longo','indefinido'))");
    $pdo->exec("ALTER TABLE metas_financeiras ADD COLUMN finalidade TEXT");
    $pdo->exec("ALTER TABLE metas_financeiras ADD COLUMN aporte_mensal_centavos INTEGER NOT NULL DEFAULT 0 CHECK(aporte_mensal_centavos>=0)");
    $pdo->exec("ALTER TABLE metas_financeiras ADD COLUMN valor_legado_centavos INTEGER NOT NULL DEFAULT 0 CHECK(valor_legado_centavos>=0)");
    $pdo->exec('UPDATE metas_financeiras SET valor_legado_centavos=valor_atual_centavos');
    $pdo->exec("CREATE TABLE objetivo_reservas (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        usuario_id INTEGER NOT NULL REFERENCES usuarios(id),
        objetivo_id INTEGER NOT NULL REFERENCES metas_financeiras(id),
        fonte_tipo TEXT NOT NULL CHECK(fonte_tipo IN ('conta','investimento')),
        fonte_id INTEGER NOT NULL,
        valor_centavos INTEGER NOT NULL CHECK(valor_centavos>0),
        ativo INTEGER NOT NULL DEFAULT 1 CHECK(ativo IN (0,1)),
        criado_em TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(objetivo_id,fonte_tipo,fonte_id)
    )");
    $pdo->exec('CREATE INDEX idx_objetivo_reservas_fonte ON objetivo_reservas(usuario_id,fonte_tipo,fonte_id)');
    $pdo->exec("ALTER TABLE orcamentos ADD COLUMN ativo INTEGER NOT NULL DEFAULT 1 CHECK(ativo IN (0,1))");

    // SQLite cannot relax the original source CHECK; recreate only this table.
    $pdo->exec("CREATE TABLE projecoes_novas (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        usuario_id INTEGER NOT NULL REFERENCES usuarios(id) ON DELETE CASCADE,
        nome TEXT NOT NULL CHECK(LENGTH(TRIM(nome)) BETWEEN 1 AND 100),
        origem_tipo TEXT NOT NULL CHECK(origem_tipo IN ('investimento','objetivo','livre')),
        investimento_id INTEGER REFERENCES investimentos(id),
        objetivo_id INTEGER REFERENCES metas_financeiras(id),
        aporte_mensal_centavos INTEGER NOT NULL CHECK(aporte_mensal_centavos>=0),
        taxa_quatro_casas INTEGER NOT NULL CHECK(taxa_quatro_casas BETWEEN 0 AND 1000000),
        periodicidade_taxa TEXT NOT NULL CHECK(periodicidade_taxa IN ('mensal','anual')),
        valor_meta_centavos INTEGER CHECK(valor_meta_centavos IS NULL OR valor_meta_centavos>0),
        valor_inicial_livre_centavos INTEGER NOT NULL DEFAULT 0 CHECK(valor_inicial_livre_centavos>=0),
        ativo INTEGER NOT NULL DEFAULT 1 CHECK(ativo IN (0,1)),
        criado_em TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        atualizado_em TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        CHECK((origem_tipo='investimento' AND investimento_id IS NOT NULL AND objetivo_id IS NULL AND valor_meta_centavos IS NOT NULL)
            OR (origem_tipo='objetivo' AND objetivo_id IS NOT NULL AND investimento_id IS NULL AND valor_meta_centavos IS NULL)
            OR (origem_tipo='livre' AND investimento_id IS NULL AND objetivo_id IS NULL AND valor_meta_centavos IS NOT NULL))
    )");
    $pdo->exec("INSERT INTO projecoes_novas(id,usuario_id,nome,origem_tipo,investimento_id,objetivo_id,aporte_mensal_centavos,taxa_quatro_casas,periodicidade_taxa,valor_meta_centavos,criado_em,atualizado_em)
        SELECT id,usuario_id,nome,origem_tipo,investimento_id,objetivo_id,aporte_mensal_centavos,taxa_quatro_casas,periodicidade_taxa,valor_meta_centavos,criado_em,atualizado_em FROM projecoes");
    $pdo->exec('DROP TABLE projecoes');
    $pdo->exec('ALTER TABLE projecoes_novas RENAME TO projecoes');
    $pdo->exec('CREATE INDEX idx_projecoes_usuario ON projecoes(usuario_id,ativo,atualizado_em DESC)');
    $pdo->exec('CREATE INDEX idx_projecoes_investimento ON projecoes(investimento_id)');
    $pdo->exec('CREATE INDEX idx_projecoes_objetivo ON projecoes(objetivo_id)');
};
