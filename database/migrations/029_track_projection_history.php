<?php

declare(strict_types=1);

return function (PDO $pdo): void {
    $pdo->exec("CREATE TABLE historico_patrimonio (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        usuario_id INTEGER NOT NULL REFERENCES usuarios(id) ON UPDATE CASCADE ON DELETE CASCADE,
        origem_tipo TEXT NOT NULL CHECK (origem_tipo IN ('investimento', 'objetivo')),
        investimento_id INTEGER REFERENCES investimentos(id) ON UPDATE CASCADE ON DELETE CASCADE,
        objetivo_id INTEGER REFERENCES metas_financeiras(id) ON UPDATE CASCADE ON DELETE CASCADE,
        valor_centavos INTEGER NOT NULL CHECK (valor_centavos >= 0),
        data_referencia TEXT NOT NULL,
        registrado_em TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        CHECK (
            (origem_tipo='investimento' AND investimento_id IS NOT NULL AND objetivo_id IS NULL)
            OR (origem_tipo='objetivo' AND objetivo_id IS NOT NULL AND investimento_id IS NULL)
        )
    )");
    $pdo->exec('CREATE INDEX idx_historico_patrimonio_investimento ON historico_patrimonio(usuario_id, investimento_id, data_referencia, id)');
    $pdo->exec('CREATE INDEX idx_historico_patrimonio_objetivo ON historico_patrimonio(usuario_id, objetivo_id, data_referencia, id)');

    $pdo->exec("INSERT INTO historico_patrimonio(usuario_id,origem_tipo,investimento_id,valor_centavos,data_referencia)
        SELECT usuario_id,'investimento',id,valor_aplicado_centavos,data_inicio FROM investimentos");
    $pdo->exec("INSERT INTO historico_patrimonio(usuario_id,origem_tipo,investimento_id,valor_centavos,data_referencia)
        SELECT usuario_id,'investimento',id,valor_atual_centavos,
            CASE WHEN substr(atualizado_em,1,10)<data_inicio THEN data_inicio ELSE substr(atualizado_em,1,10) END
        FROM investimentos WHERE valor_atual_centavos<>valor_aplicado_centavos");
    $pdo->exec("INSERT INTO historico_patrimonio(usuario_id,origem_tipo,objetivo_id,valor_centavos,data_referencia)
        SELECT usuario_id,'objetivo',id,0,substr(criado_em,1,10) FROM metas_financeiras");
    $pdo->exec("INSERT INTO historico_patrimonio(usuario_id,origem_tipo,objetivo_id,valor_centavos,data_referencia)
        SELECT usuario_id,'objetivo',id,valor_atual_centavos,substr(atualizado_em,1,10)
        FROM metas_financeiras WHERE valor_atual_centavos>0");

    $pdo->exec("CREATE TRIGGER trg_investimento_historico_insert AFTER INSERT ON investimentos BEGIN
        INSERT INTO historico_patrimonio(usuario_id,origem_tipo,investimento_id,valor_centavos,data_referencia)
        VALUES(NEW.usuario_id,'investimento',NEW.id,NEW.valor_aplicado_centavos,NEW.data_inicio);
        INSERT INTO historico_patrimonio(usuario_id,origem_tipo,investimento_id,valor_centavos,data_referencia)
        SELECT NEW.usuario_id,'investimento',NEW.id,NEW.valor_atual_centavos,date('now','localtime')
        WHERE NEW.valor_atual_centavos<>NEW.valor_aplicado_centavos;
    END");
    $pdo->exec("CREATE TRIGGER trg_investimento_historico_update AFTER UPDATE OF valor_atual_centavos ON investimentos
        WHEN NEW.valor_atual_centavos<>OLD.valor_atual_centavos BEGIN
        INSERT INTO historico_patrimonio(usuario_id,origem_tipo,investimento_id,valor_centavos,data_referencia)
        VALUES(NEW.usuario_id,'investimento',NEW.id,NEW.valor_atual_centavos,date('now','localtime'));
    END");
    $pdo->exec("CREATE TRIGGER trg_objetivo_historico_insert AFTER INSERT ON metas_financeiras BEGIN
        INSERT INTO historico_patrimonio(usuario_id,origem_tipo,objetivo_id,valor_centavos,data_referencia)
        VALUES(NEW.usuario_id,'objetivo',NEW.id,0,substr(NEW.criado_em,1,10));
        INSERT INTO historico_patrimonio(usuario_id,origem_tipo,objetivo_id,valor_centavos,data_referencia)
        SELECT NEW.usuario_id,'objetivo',NEW.id,NEW.valor_atual_centavos,date('now','localtime') WHERE NEW.valor_atual_centavos>0;
    END");
    $pdo->exec("CREATE TRIGGER trg_objetivo_historico_update AFTER UPDATE OF valor_atual_centavos ON metas_financeiras
        WHEN NEW.valor_atual_centavos<>OLD.valor_atual_centavos BEGIN
        INSERT INTO historico_patrimonio(usuario_id,origem_tipo,objetivo_id,valor_centavos,data_referencia)
        VALUES(NEW.usuario_id,'objetivo',NEW.id,NEW.valor_atual_centavos,date('now','localtime'));
    END");
};
